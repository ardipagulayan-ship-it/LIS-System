<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validates a patient (non-QC) result payload from the Python LIS Bridge forwarder.
 *
 * This FormRequest is used for POST /api/v1/patient-results.
 * It enforces the wire-format contract defined in the LIS Bridge spec.
 *
 * Cross-field invariants enforced here:
 *   - is_qc must be false
 *   - control_id must be null (QC fields are not allowed in patient payloads)
 *
 * The forwarder's send_to_lis() treats HTTP 422 as a hard-fail (no inner retry),
 * so validation failures will be retried up to max_retries (default 5) poll cycles.
 * Only return 422 for genuinely malformed payloads.
 *
 * @see LisBridgeReceiverController::ingestPatient()
 * @see https://github.com/.../LIS%20Bridge%20Forwarder%20%E2%80%94%20Wire%20Form.md
 */
class StoreLisPatientResultRequest extends FormRequest
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
     * All 14 wire-format fields are validated here. Fields are typed strictly
     * to catch forwarder bugs early. Custom error messages are operator-facing —
     * the forwarder truncates the body to 500 chars and stores it as
     * Result.last_lis_error, so messages must be self-contained.
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

            // analyzer_serial is the new required field (replaces analyzer_id lookup).
            // Kept as string to match equipment.serial_no.
            'analyzer_serial' => [
                'required',
                'string',
                'max:128',
            ],

            // Backwards-compatible — ignored by receiver, but accepted so that
            // forwarder instances that haven't updated yet still work.
            'analyzer_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

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

            // Patient payloads MUST NOT carry a control_id.
            // This is the cross-field invariant that distinguishes patient from QC flows.
            'control_id' => [
                'nullable',
                'string',
                'max:64',
            ],

            'test_code' => [
                'required',
                'string',
                'max:64',
            ],

            // value is ALWAYS a string per wire spec. Never numeric. Even "7.5" is quoted.
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

            // ASTM single-character status codes.
            'result_status' => [
                'required',
                'string',
                'size:1',
                'in:F,P,C,X',
            ],

            // run_number: 1 = first run, 2+ = rerun/correction.
            // max:65535 matches unsignedSmallInteger column.
            'run_number' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],

            // ISO-8601 with timezone. Laravel's date rule is intentionally lenient;
            // prepareForValidation() normalizes to ISO-8601.
            'observed_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    /**
     * Custom error messages for operator-facing errors.
     *
     * These messages appear in Result.last_lis_error on the forwarder side.
     * The forwarder truncates at 500 chars, so messages must be self-contained.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message_type.in' => 'message_type must be "patient" or "qc". Received patient payload — route mismatch.',
            'result_status.in' => 'result_status must be F (Final), P (Preliminary), C (Corrected), or X (Cancelled).',
            'result_status.size' => 'result_status must be a single character (F/P/C/X). Received: :input.',
            'is_qc.boolean' => 'is_qc must be a boolean (true for QC, false for patient).',
            'control_id.max' => 'control_id must be 64 characters or fewer.',
            'observed_at.date' => 'observed_at must be ISO-8601 format, e.g. 2026-09-07T00:50:00+00:00.',
            'run_number.max' => 'run_number must not exceed 65535.',
        ];
    }

    /**
     * Normalize the payload before validation runs.
     *
     * This is the defensive layer for type coercion — the forwarder sends real
     * booleans today, but hand-crafted test payloads and future version drift
     * may send string forms.
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

        // Lowercase message_type to guard against "Patient" / "QC" from misconfigured instances
        if (isset($input['message_type'])) {
            $input['message_type'] = strtolower(trim($input['message_type']));
        }

        // Trim all string fields
        foreach (['message_type', 'patient_id', 'sample_id', 'control_id', 'test_code', 'value', 'unit', 'result_status'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = trim($input[$field]);
            }
        }

        // Normalize observed_at to ISO-8601 string if present
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
     * Runs after prepareForValidation() and before rules().
     * Used here to add cross-field invariants.
     *
     * @param  Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->valid();

            // Cross-field invariant: is_qc must be false for patient payloads
            if (isset($data['is_qc']) && $data['is_qc'] !== false) {
                $validator->errors()->add(
                    'is_qc',
                    'is_qc must be false for patient payloads. Received is_qc=true — route mismatch.'
                );
            }

            // Cross-field invariant: control_id must be null for patient payloads
            if (isset($data['control_id']) && $data['control_id'] !== null && $data['control_id'] !== '') {
                $validator->errors()->add(
                    'control_id',
                    'control_id must be null for patient payloads. Received: "' . $data['control_id'] . '".'
                );
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * Returns HTTP 422 with field-level error messages.
     * The forwarder's send_to_lis() treats 422 as a hard-fail (no inner retry).
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
