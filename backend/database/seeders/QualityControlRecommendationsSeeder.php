<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\DeltaCheckParameter;

class QualityControlRecommendationsSeeder extends Seeder
{
    public function run()
    {
        // Get a valid parameter ID (use glucose as default for global recommendations)
        $parameter = DeltaCheckParameter::where('parameter_name', 'glucose')->first();
        $parameterId = $parameter ? $parameter->id : null;
        
        // If no parameter exists, we can't seed these records due to FK constraint
        if (!$parameterId) {
            $this->command->warn('QualityControlRecommendationsSeeder: No parameter found. Skipping global QC recommendations. Run DeltaCheckRecommendationSeeder first.');
            return;
        }
        
        DB::table('delta_check_quality_control')->insert([
            [
                'parameter_id' => $parameterId,
                'qc_type' => 'instrument_check',
                'title' => 'Perform Instrument Maintenance',
                'description' => 'Ensure maintenance and calibration for all analyzers are performed as per manufacturer specifications before running QC.',
                'qc_steps' => json_encode([
                    'Verify instrument logs and service schedules.',
                    'Follow manufacturer maintenance checklist.'
                ]),
                'order' => 1,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'parameter_id' => $parameterId,
                'qc_type' => 'qc_review',
                'title' => 'Use Appropriate Controls',
                'description' => 'QC materials should be appropriate for each test (lot number, expiration date, analyte coverage) and stored per manufacturer guidelines.',
                'qc_steps' => json_encode([
                    'Check QC material lot and expiration before use.',
                    'Store controls under recommended conditions.'
                ]),
                'order' => 2,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'parameter_id' => $parameterId,
                'qc_type' => 'qc_review',
                'title' => 'Document All QC Issues and Corrections',
                'description' => 'All QC failures, corrective actions, and maintenance should be promptly documented in the LIS or QC log book for traceability.',
                'qc_steps' => json_encode([
                    'Document failed QC flags immediately.',
                    'Enter actions taken and personnel involved.',
                    'Maintain clear audit trail for inspections.'
                ]),
                'order' => 3,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'parameter_id' => $parameterId,
                'qc_type' => 'qc_review',
                'title' => 'Review QC Results Before Releasing Patient Results',
                'description' => 'Patient results must not be validated or released if associated QC fails or is under investigation. Confirm two levels of QC are in control.',
                'qc_steps' => json_encode([
                    'Review QC data for all shifts and analyzers.',
                    'Only release results if QC is verified and in range.'
                ]),
                'order' => 4,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ],
            [
                'parameter_id' => $parameterId,
                'qc_type' => 'corrective_action',
                'title' => 'Take Corrective Actions as per SOP for QC Failures',
                'description' => 'For any rule violation (e.g., 1-3s, 2-2s, R-4s), investigate, re-run controls, and document steps before rerunning patient samples. Take equipment out of service if necessary.',
                'qc_steps' => json_encode([
                    'Review Westgard rule violations and root cause.',
                    'Repeat QC, recalibrate, or replace reagents as needed.',
                    'Escalate unresolved failures to senior staff or service.',
                    'Never report results until QC passes.'
                ]),
                'order' => 5,
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        ]);
    }
}
