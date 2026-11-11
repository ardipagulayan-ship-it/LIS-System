<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validates a QC result payload from the Python LIS Bridge forwarder.
 *
 * This FormRequest is used for POST /api/v1/qc-results.
 * It enforces the wire-format contract defined in the LIS Bridge spec.
 *
 * Cross-field invariants enforced here:
 *   - is_qc must be true
 *   - control_id MUST be present and non-empty (QC payloads require it)
 *   - patient_id must equal "QC" (the sentinel set by result_persistence)
 *
 * The forwarder's send_to_lis() treats HTTP 422 as a hard-fail (no inner retry),
 * so validation failures will be retried up to max_retries (default 5) poll cycles.
 * Only return 422 for genuinely malformed payloads.
 *
 * @see LisBridgeReceiverController::ingestQc()
 * @see https://github.com/.../LIS%20Bridge%20Forwarder%20%E2%80%94%20Wire%20Form.md
 */
class StoreLisQcResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled by VerifyLisBridgeApiKey middleware — not here.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * All 14 wire-format fields are validated here.
     * The key difference from the patient request:
     *   - is_qc must be true
     *   - control_id is REQUIRED (not nullable)
     *   - patient_id must be "QC" sentinel
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // ── Discriminator (enforced by route, but validated here for safety) ──
            'message_type' => [
                'required',
                'string',
                'in:patient,qc',
            ],

            'is_qc' => [
                'required',
                'boolean',
            ],

            // ── Core result fields ──
            'result_id' => [
                'required',
                'integer',
                'min:1',
            ],

            // analyzer_serial: the equipment.serial_no that generated this QC result.
            'analyzer_serial' => [
                'required',
                'string',
                'max:128',
            ],

            // Backwards-compatible — ignored by receiver.
            'analyzer_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            // For QC payloads, patient_id must be the sentinel "QC".
            'patient_id' => [
                'required',
                'string',
                'max:64',
            ],

            'sample_id' => [
                'required',
                'string',
                'max:64',
            ],

            // QC payloads MUST carry a control_id. This is the QC material identifier
            // (e.g. "QC_NORMAL", "QC_HIGH", "QC_LOW"). Mapped via control_id_map JSON
            // to local quality_controls.id.
            'control_id' => [
                'required',
                'string',
                'max:64',
            ],

            'test_code' => [
                'required',
                'string',
                'max:64',
            ],

            // value is ALWAYS a string. Even QC numeric values like "13.5" are quoted.
            // Widen to varchar(255) to support "NEG", "POS", ">100" etc.
            'value' => [
                'required',
                'string',
                'max:255',
            ],

            'unit' => [
                'nullable',
                'string',
                'max:32',
            ],

            'result_status' => [
                'required',
                'string',
                'size:1',
                'in:F,P,C,X',
            ],

            'run_number' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],

            'observed_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    /**
     * Custom error messages for operator-facing errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message_type.in' => 'message_type must be "patient" or "qc". Received QC payload — route mismatch.',
            'result_status.in' => 'result_status must be F (Final), P (Preliminary), C (Corrected), or X (Cancelled).',
            'result_status.size' => 'result_status must be a single character (F/P/C/X). Received: :input.',
            'is_qc.boolean' => 'is_qc must be a boolean (true for QC, false for patient).',
            'control_id.required' => 'control_id is required for QC payloads. This is the QC material identifier (e.g. "QC_NORMAL").',
            'control_id.max' => 'control_id must be 64 characters or fewer.',
            'observed_at.date' => 'observed_at must be ISO-8601 format, e.g. 2026-09-07T00:55:00+00:00.',
            'run_number.max' => 'run_number must not exceed 65535.',
        ];
    }

    /**
     * Normalize the payload before validation runs.
     *
     * @return void
     */
    public function prepareForValidation(): void
    {
        $input = $this->all();

        // Normalize is_qc from string/int to boolean
        if (isset($input['is_qc'])) {
            $input['is_qc'] = filter_var($input['is_qc'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        // Lowercase message_type
        if (isset($input['message_type'])) {
            $input['message_type'] = strtolower(trim($input['message_type']));
        }

        // Trim all string fields
        foreach (['message_type', 'patient_id', 'sample_id', 'control_id', 'test_code', 'value', 'unit', 'result_status'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = trim($input[$field]);
            }
        }

        // Normalize observed_at to ISO-8601 string
        if (!empty($input['observed_at'])) {
            try {
                $input['observed_at'] = \Carbon\Carbon::parse($input['observed_at'])->toIso8601String();
            } catch (\Exception $e) {
                // Let the date rule catch an unparseable value
            }
        }

        $this->replace($input);
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->valid();

            // Cross-field invariant: is_qc must be true for QC payloads
            if (isset($data['is_qc']) && $data['is_qc'] !== true) {
                $validator->errors()->add(
                    'is_qc',
                    'is_qc must be true for QC payloads. Received is_qc=false — route mismatch.'
                );
            }

            // Cross-field invariant: patient_id must be the QC sentinel
            if (isset($data['patient_id']) && $data['patient_id'] !== 'QC') {
                $validator->errors()->add(
                    'patient_id',
                    'patient_id must be "QC" for QC payloads. Received: "' . $data['patient_id'] . '".'
                );
            }

            // Cross-field invariant: control_id must be non-empty for QC
            if (isset($data['control_id']) && trim($data['control_id']) === '') {
                $validator->errors()->add(
                    'control_id',
                    'control_id cannot be empty for QC payloads. Provide the QC material identifier (e.g. "QC_NORMAL").'
                );
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator $validator
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $validator->errors()->toArray(),
            ], 422)
        );
    }
}
