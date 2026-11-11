<?php

namespace App\Services;

use App\Exceptions\LisIngestException;
use App\Models\Equipment;
use App\Models\LaboratoryRequest;
use App\Models\LisBridgeConfig;
use App\Models\LisBridgeTranslation;
use App\Models\QualityControl;
use App\Models\QualityControlResult;
use App\Models\TestParameter;
use App\Models\TestResult;
use Illuminate\Support\Facades\DB;

/**
 * Core ingestion service for the LIS Bridge receiver.
 *
 * Handles the complete ingestion pipeline for both patient and QC results:
 *   1. Idempotency dedup check
 *   2. Analyzer resolution via Equipment::serial_number
 *   3. QC control resolution via control_id_map JSON
 *   4. Laboratory request resolution via sample_id
 *   5. Test parameter resolution via test_parameters.test_code
 *   6. Result status translation (ASTM F/P/C/X → internal enum)
 *   7. Re-run supersession via parent_result_id chain
 *   8. Single atomic transaction: INSERT result + INSERT translation row
 *   9. Returns the internal result for queue job dispatch
 *
 * The translation table (lis_bridge_translation) is the SOLE source of truth for
 * dedup. Result tables carry no additive LIS Bridge columns.
 *
 * @see LisBridgeTranslation::alreadyProcessed()
 * @see Equipment::where('serial_number', $serial)
 */
class LisResultIngestionService
{
    /**
     * Ingest a patient (non-QC) result.
     *
     * @param  LisBridgeConfig $config   The active bridge config (from VerifyLisBridgeApiKey)
     * @param  array          $payload  Validated payload from StoreLisPatientResultRequest
     * @return array  ['result' => TestResult, 'is_new' => bool, 'translation' => LisBridgeTranslation]
     *
     * @throws LisIngestException  On any processing failure (maps to HTTP 422/503)
     */
    public function ingestPatient(LisBridgeConfig $config, array $payload): array
    {
        // 1. Idempotency check
        $existing = $this->dedupCheck($config, $payload['result_id']);
        if ($existing !== null) {
            return [
                'result'      => $existing->internalModel,
                'is_new'      => false,
                'translation'  => $existing,
            ];
        }

        // 2. Resolve analyzer via serial_number
        $equipmentId = $this->resolveEquipment($payload['analyzer_serial']);

        // 3. Resolve laboratory request via sample_id
        $labRequest = $this->resolveLabRequest($payload['sample_id']);

        // 4. Resolve test parameter via test_code
        $testParameterId = $this->resolveTestParameter($payload['test_code']);

        // 5. Translate result_status
        $internalStatus = $this->translateStatus($payload['result_status']);

        // 6. Determine if this is a re-run and build parent_result_id chain
        $parentResultId = $this->resolveParentResult(
            $equipmentId,
            $labRequest->id,
            $testParameterId,
            $payload['run_number']
        );

        // 7. Atomic write: result + translation row
        return DB::transaction(function () use (
            $config, $payload, $equipmentId, $labRequest,
            $testParameterId, $internalStatus, $parentResultId
        ) {
            // Get test_id from test_parameter
            $testParameter = TestParameter::find($testParameterId);
            $testId = $testParameter?->test_id;

            $testResult = TestResult::create([
                'laboratory_request_id' => $labRequest->id,
                'test_id'               => $testId,
                'test_parameter_id'    => $testParameterId,
                'result_value'         => $payload['value'],
                'flag'                 => null,  // computed by downstream queue job
                'unit'                 => $payload['unit'] ?? null,
                'equipment_id'          => $equipmentId,
                'result_comment'       => null,
                'performer_id'        => null,  // set by downstream
                'verifier_id'          => null,
                'pathologist_id'      => null,
                'status'               => $internalStatus,
                'version'              => $this->runNumberToVersion($payload['run_number']),
                'parent_result_id'     => $parentResultId,
            ]);

            $translation = $this->recordTranslation(
                $config,
                $payload,
                'test_results',
                $testResult->id
            );

            return [
                'result'     => $testResult,
                'is_new'     => true,
                'translation' => $translation,
            ];
        });
    }

    /**
     * Ingest a QC result.
     *
     * @param  LisBridgeConfig $config
     * @param  array          $payload  Validated payload from StoreLisQcResultRequest
     * @return array  ['result' => QualityControlResult, 'is_new' => bool, 'translation' => LisBridgeTranslation]
     *
     * @throws LisIngestException
     */
    public function ingestQc(LisBridgeConfig $config, array $payload): array
    {
        // 1. Idempotency check
        $existing = $this->dedupCheck($config, $payload['result_id']);
        if ($existing !== null) {
            return [
                'result'      => $existing->internalModel,
                'is_new'      => false,
                'translation'  => $existing,
            ];
        }

        // 2. Resolve analyzer via serial_no
        $equipmentId = $this->resolveEquipment($payload['analyzer_serial']);

        // 3. Resolve QC control via control_id_map JSON
        $qualityControlId = $this->resolveControl($config, $payload['control_id']);

        // 4. Resolve test parameter via test_code
        $testParameterId = $this->resolveTestParameter($payload['test_code']);

        // 5. Atomic write: QC result + translation row
        return DB::transaction(function () use (
            $config, $payload, $equipmentId, $qualityControlId, $testParameterId
        ) {
            $qcResult = QualityControlResult::create([
                'quality_control_test_parameter_id' => $qualityControlId,
                'result_value'                   => $payload['value'],
                'unit'                           => $payload['unit'] ?? null,
                'run_date'                       => now()->toDateString(),
                'run_time'                       => $payload['observed_at']
                    ? \Carbon\Carbon::parse($payload['observed_at'])->format('H:i:s')
                    : now()->format('H:i:s'),
                'operator_id'                    => null,
                'westgard_violations'            => null, // computed by downstream job
                'is_valid'                      => null,  // computed by downstream job
                'remarks'                       => null,
                'created_by'                    => null,
                'updated_by'                    => null,
            ]);

            $translation = $this->recordTranslation(
                $config,
                $payload,
                'quality_control_results',
                $qcResult->id
            );

            return [
                'result'     => $qcResult,
                'is_new'     => true,
                'translation' => $translation,
            ];
        });
    }

    // ─── Resolution Methods ───────────────────────────────────────────────────

    /**
     * Check if a wire result has already been processed (idempotency dedup).
     *
     * @param  LisBridgeConfig $config
     * @param  int            $wireResultId  The middleware's Result.id
     * @return LisBridgeTranslation|null
     */
    protected function dedupCheck(LisBridgeConfig $config, int $wireResultId): ?LisBridgeTranslation
    {
        return LisBridgeTranslation::where('lis_bridge_config_id', $config->id)
            ->where('wire_result_id', $wireResultId)
            ->first();
    }

    /**
     * Resolve an analyzer by its serial number.
     *
     * The wire format sends analyzer_serial. We look up equipment.serial_number.
     * Throws if no match — the operator must register the analyzer first.
     *
     * @param  string $serial  equipment.serial_number
     * @return int             equipment.id
     *
     * @throws LisIngestException  422 if analyzer not found
     */
    protected function resolveEquipment(string $serial): int
    {
        $equipment = Equipment::where('serial_number', $serial)->first();

        if ($equipment === null) {
            throw new LisIngestException(
                "Unknown analyzer_serial: '{$serial}'.",
                ['analyzer_serial' => $serial],
                422,
                'analyzer_serial'
            );
        }

        return $equipment->id;
    }

    /**
     * Resolve a QC control by its vendor-supplied name via control_id_map JSON.
     *
     * @param  LisBridgeConfig $config
     * @param  string          $controlId  Wire-format control_id, e.g. "QC_NORMAL"
     * @return int             quality_controls.id
     *
     * @throws LisIngestException  422 if control_id is not in the map or the mapped ID doesn't exist
     */
    protected function resolveControl(LisBridgeConfig $config, string $controlId): int
    {
        $mappedId = $config->resolveControlId($controlId);

        if ($mappedId === null) {
            throw new LisIngestException(
                "control_id '{$controlId}' has no mapping configured for this bridge.",
                ['control_id' => $controlId],
                422,
                'control_id'
            );
        }

        $qc = QualityControl::find($mappedId);
        if ($qc === null) {
            throw new LisIngestException(
                "control_id '{$controlId}' mapped to quality_controls.id={$mappedId} but that record does not exist.",
                ['control_id' => $controlId, 'mapped_id' => $mappedId],
                422,
                'control_id'
            );
        }

        return $qc->id;
    }

    /**
     * Resolve a laboratory request by its sample_id.
     *
     * The wire's sample_id is the barcode. We look up laboratory_requests.sample_id.
     *
     * Note: if no match is found, the implementation strategy depends on Phase 0 decisions.
     * Options: (a) auto-create a stub request, (b) reject with 422, (c) queue for manual mapping.
     * Default: throw 422.
     *
     * @param  string $sampleId
     * @return LaboratoryRequest
     *
     * @throws LisIngestException  422 if sample_id not found
     */
    protected function resolveLabRequest(string $sampleId): LaboratoryRequest
    {
        $request = LaboratoryRequest::where('sample_id', $sampleId)->first();

        if ($request === null) {
            throw new LisIngestException(
                "sample_id '{$sampleId}' does not match any laboratory request.",
                ['sample_id' => $sampleId],
                422,
                'sample_id'
            );
        }

        return $request;
    }

    /**
     * Resolve a test parameter by its LIS-mapped test code.
     *
     * The wire sends test_code (e.g. "HGB", "WBC"). We look up test_parameters.test_code.
     *
     * @param  string $testCode
     * @return int    test_parameters.id
     *
     * @throws LisIngestException  422 if test_code not found
     */
    protected function resolveTestParameter(string $testCode): int
    {
        $param = TestParameter::where('test_code', $testCode)->first();

        if ($param === null) {
            throw new LisIngestException(
                "test_code '{$testCode}' is not registered in test_parameters.",
                ['test_code' => $testCode],
                422,
                'test_code'
            );
        }

        return $param->id;
    }

    /**
     * Translate wire-format ASTM status codes to internal status values.
     *
     * Wire values: F (Final), P (Preliminary), C (Corrected), X (Cancelled)
     * Internal values (test_results.status enum): pending, completed, validated, released, superseded, replaced, in-process
     *
     * @param  string $wireStatus
     * @return string  Internal status value
     *
     * @throws LisIngestException  422 on unknown code
     */
    protected function translateStatus(string $wireStatus): string
    {
        $map = [
            'F' => 'pending',   // Final result → released
            'P' => 'pending',    // Preliminary
            'C' => 'completed',  // Corrected — also means validated in this app
            'X' => 'superseded', // Cancelled
        ];

        if (!isset($map[$wireStatus])) {
            throw new LisIngestException(
                "Unknown result_status: '{$wireStatus}'. Must be F/P/C/X.",
                ['result_status' => $wireStatus],
                422,
                'result_status'
            );
        }

        return $map[$wireStatus];
    }

    /**
     * Convert run_number (wire-format integer) to version (internal enum R0/R1/R2).
     *
     * run_number: 1 = first run, 2 = first re-run, 3 = second re-run, ...
     * version:     R0 = first, R1 = first re-run, R2 = second re-run
     *
     * @param  int $runNumber
     * @return string
     */
    protected function runNumberToVersion(int $runNumber): string
    {
        // Clamp to the max version the app supports
        $index = min($runNumber - 1, 2); // 1→R0, 2→R1, 3+→R2
        return 'R' . $index;
    }

    /**
     * Find the parent result for re-run supersession.
     *
     * When run_number > 1, this method finds the prior result for the same
     * (equipment, lab_request, test_parameter) triple with the previous run_number.
     * The new result gets parent_result_id pointing to the prior row.
     *
     * @param  int $equipmentId
     * @param  int $labRequestId
     * @param  int $testParameterId
     * @param  int $runNumber
     * @return int|null  Prior TestResult.id, or null if this is run_number=1
     */
    protected function resolveParentResult(
        int $equipmentId,
        int $labRequestId,
        int $testParameterId,
        int $runNumber
    ): ?int {
        if ($runNumber <= 1) {
            return null;
        }

        $priorRun = $runNumber - 1;

        $parent = TestResult::where('equipment_id', $equipmentId)
            ->where('laboratory_request_id', $labRequestId)
            ->where('test_parameter_id', $testParameterId)
            ->where('parent_result_id', null) // only top-level results
            ->orderBy('created_at', 'desc')
            ->first();

        // Fallback: look for any result with run_number close to priorRun
        if ($parent === null) {
            $parent = TestResult::where('equipment_id', $equipmentId)
                ->where('laboratory_request_id', $labRequestId)
                ->where('test_parameter_id', $testParameterId)
                ->orderBy('created_at', 'desc')
                ->skip($priorRun - 1)
                ->first();
        }

        return $parent?->id;
    }

    /**
     * Record the translation row in lis_bridge_translation.
     *
     * Called inside the same DB transaction as the result insert to ensure
     * atomicity — we never have a translation row pointing to a non-existent
     * internal row, or vice versa.
     *
     * @param  LisBridgeConfig $config
     * @param  array          $payload
     * @param  string         $internalTable  'test_results' | 'quality_control_results'
     * @param  int            $internalId
     * @return LisBridgeTranslation
     */
    protected function recordTranslation(
        LisBridgeConfig $config,
        array $payload,
        string $internalTable,
        int $internalId
    ): LisBridgeTranslation {
        return LisBridgeTranslation::create([
            'lis_bridge_config_id'  => $config->id,
            'wire_result_id'       => $payload['result_id'],
            'is_qc'                => $payload['is_qc'],
            'internal_table'       => $internalTable,
            'internal_id'          => $internalId,
            'wire_analyzer_serial' => $payload['analyzer_serial'] ?? null,
            'wire_patient_id'      => $payload['patient_id'] ?? null,
            'wire_sample_id'       => $payload['sample_id'] ?? null,
            'wire_test_code'       => $payload['test_code'] ?? null,
            'wire_observed_at'     => !empty($payload['observed_at'])
                ? \Carbon\Carbon::parse($payload['observed_at'])->toDateTimeString()
                : null,
            'wire_value_raw'       => $payload['value'] ?? null,
            'wire_result_status'   => $payload['result_status'] ?? null,
            'wire_run_number'      => $payload['run_number'] ?? null,
            'wire_control_id'      => $payload['control_id'] ?? null,
            'ingested_at'          => now(),
        ]);
    }
}
