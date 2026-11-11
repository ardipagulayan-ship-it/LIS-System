<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\QcAnalyteSigmaConfig;
use App\Models\QualityControlTestParameter;

class QualityControlResultSeeder extends Seeder
{
    /**
     * Generate a Gaussian random number using Box-Muller transform.
     */
    private function gaussian(float $mean, float $sd): float
    {
        $u1 = 1.0 - mt_rand() / mt_getrandmax();
        $u2 = 1.0 - mt_rand() / mt_getrandmax();
        $standardNormal = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
        return $mean + $sd * $standardNormal;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periodDays = 90; // seed enough data for Sigma period/trend
        $recordsPerDay = 1; // simple: one QC run per day
        $invalidRate = 0.15; // ~15% invalid to exercise data quality UI
        $operatorId = 1; // if not present, will be stored as null

        $startDate = Carbon::now()->subDays($periodDays);

        // CBC analyte names we want to ensure have QC data
        $cbcAnalyteNames = [
            'White Blood Cell Count',
            'Red Blood Cell Count',
            'Hemoglobin',
            'Hematocrit',
            'Platelet Count',
            'MCV',
            'MCH',
            'MCHC',
            'RDW',
        ];

        // Build a set of target test_parameter_ids: from active TEa configs + CBC list
        $targetTestParameterIds = collect(
            QcAnalyteSigmaConfig::active()->effectiveOn()->pluck('test_parameter_id')->all()
        );

        // Resolve CBC names to IDs using the column test_name
        $byNames = DB::table('test_parameters')
            ->whereIn('test_name', $cbcAnalyteNames)
            ->pluck('id');
        $targetTestParameterIds = $targetTestParameterIds->merge($byNames);

        $targetTestParameterIds = $targetTestParameterIds->unique()->values();

        // Pick an equipment id if needed for creating QC masters
        $equipmentId = DB::table('equipment')->inRandomOrder()->value('id');

        foreach ($targetTestParameterIds as $testParameterId) {
            // Ensure there is at least one QC parameter row for this analyte
            $qcParams = QualityControlTestParameter::where('test_parameter_id', $testParameterId)->get();

            if ($qcParams->isEmpty()) {
                // Create a minimal QC master and QC parameter record
                $controlId = DB::table('quality_controls')->insertGetId([
                    'test_parameter_id' => $testParameterId,
                    'equipment_id' => $equipmentId,
                    'control_name' => 'CBC Control',
                    'lot_number' => 'CBC' . mt_rand(1000, 9999),
                    'expiry_date' => Carbon::now()->addYear(),
                    'mean' => 100.0,
                    'sd' => 2.0,
                    'target' => 100.0,
                    'sd_1_plus' => 102.0,
                    'sd_1_minus' => 98.0,
                    'sd_2_plus' => 104.0,
                    'sd_2_minus' => 96.0,
                    'sd_3_plus' => 106.0,
                    'sd_3_minus' => 94.0,
                    'is_active' => 1,
                    'created_by' => $operatorId,
                    'updated_by' => $operatorId,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                $target = 100.0;
                $sd = 2.0;

                $qcParamId = DB::table('quality_control_test_parameter')->insertGetId([
                    'quality_control_id' => $controlId,
                    'test_parameter_id' => $testParameterId,
                    'target' => $target,
                    'tolerance' => $sd,
                    'minus_3sd' => $target - (3 * $sd),
                    'minus_2sd' => $target - (2 * $sd),
                    'minus_1sd' => $target - (1 * $sd),
                    'plus_1sd' => $target + (1 * $sd),
                    'plus_2sd' => $target + (2 * $sd),
                    'plus_3sd' => $target + (3 * $sd),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                $qcParams = collect([QualityControlTestParameter::find($qcParamId)]);
            }

            foreach ($qcParams as $qcParam) {
                $target = (float) ($qcParam->target ?? 100.0);
                $sd = 0.0;
                if (isset($qcParam->plus_1sd) && isset($qcParam->minus_1sd)) {
                    $sd = abs(((float)$qcParam->plus_1sd - (float)$qcParam->minus_1sd) / 2.0);
                }
                if ($sd <= 0) {
                    $sd = max(0.02 * $target, 0.5);
                }

                $unit = $qcParam->unit ?? '';

                $batch = [];
                for ($d = 0; $d <= $periodDays; $d++) {
                    $date = $startDate->copy()->addDays($d);
                    for ($k = 0; $k < $recordsPerDay; $k++) {
                        $value = $this->gaussian($target, $sd);
                        $isInvalidRandom = mt_rand() / mt_getrandmax() < $invalidRate;
                        $isWithinRange = true;
                        if (isset($qcParam->plus_2sd) && isset($qcParam->minus_2sd)) {
                            $isWithinRange = ($value >= (float)$qcParam->minus_2sd) && ($value <= (float)$qcParam->plus_2sd);
                        }
                        $isValid = !$isInvalidRandom && $isWithinRange;
                        $batch[] = [
                            'quality_control_test_parameter_id' => $qcParam->id,
                            'result_value' => round($value, 4),
                            'standard_deviation_index' => null,
                            'unit' => $unit,
                            'run_date' => $date->toDateString(),
                            'run_time' => $date->copy()->setTime(mt_rand(6, 18), mt_rand(0, 59), mt_rand(0, 59))->toTimeString(),
                            'operator_id' => $operatorId,
                            'westgard_violations' => json_encode([]),
                            'is_valid' => $isValid,
                            'is_within_range' => $isWithinRange,
                            'remarks' => null,
                            'created_by' => $operatorId,
                            'updated_by' => $operatorId,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    }
                }
                foreach (array_chunk($batch, 500) as $chunk) {
                    DB::table('quality_control_results')->insert($chunk);
                }
            }
        }
    }
}
