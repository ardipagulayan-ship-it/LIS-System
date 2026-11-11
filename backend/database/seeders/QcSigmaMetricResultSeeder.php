<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QcSigmaMetricResult;
use App\Models\QcAnalyteSigmaConfig;
use App\Models\TestParameter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QcSigmaMetricResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all test parameters with TE_a configurations
        $configs = QcAnalyteSigmaConfig::with('testParameter')
            ->where('is_active', true)
            ->get();

        if ($configs->isEmpty()) {
            $this->command->warn('No TE_a configurations found. Please seed QcAnalyteSigmaConfig first.');
            return;
        }

        $resultsCreated = 0;
        $daysBack = 90; // Generate results for last 90 days
        $resultsPerParameter = 30; // Generate ~30 results per parameter (roughly every 3 days)

        foreach ($configs as $config) {
            $testParameter = $config->testParameter;
            if (!$testParameter) {
                continue;
            }

            // Get QC test parameter to get target mean
            $qcTestParameter = DB::table('quality_control_test_parameter')
                ->where('test_parameter_id', $testParameter->id)
                ->orderBy('id', 'desc')
                ->first();

            if (!$qcTestParameter) {
                // Skip if no QC configuration exists
                continue;
            }

            $targetMean = (float) ($qcTestParameter->target ?? 100.0);
            $teA = (float) $config->t_e_a;

            // Generate historical Sigma metric results
            for ($i = 0; $i < $resultsPerParameter; $i++) {
                $calculationDate = Carbon::now()->subDays(rand(0, $daysBack))->subHours(rand(0, 23));

                // Simulate realistic Sigma values with some variation
                // Target Sigma around 5.0-6.0 (good performance)
                // But include some lower values to show variation
                $baseSigma = 5.5;
                $variation = rand(-20, 15) / 10; // -2.0 to +1.5
                $sigmaMetric = max(2.0, $baseSigma + $variation); // Minimum 2.0

                // Calculate observed values based on Sigma formula: Σ = (TE_a - |x̄ - μ|) / s
                // Rearranging: s = (TE_a - |x̄ - μ|) / Σ
                // We'll set a small bias and calculate SD
                $bias = rand(0, 5) / 100; // 0 to 5% bias
                $observedMean = $targetMean * (1 + ($bias * (rand(0, 1) ? 1 : -1)));
                $observedStdDev = ($teA - abs($observedMean - $targetMean)) / max(0.1, $sigmaMetric);
                
                // Ensure realistic values
                if ($observedStdDev <= 0 || $observedStdDev > ($targetMean * 0.2)) {
                    $observedStdDev = $targetMean * 0.05; // 5% CV default
                    // Recalculate observed mean to fit
                    $observedMean = $targetMean + (($teA - $observedStdDev * $sigmaMetric) * (rand(0, 1) ? 1 : -1));
                }

                // Check if result already exists for this date
                $existing = QcSigmaMetricResult::where('test_parameter_id', $testParameter->id)
                    ->whereDate('calculation_date', $calculationDate->toDateString())
                    ->first();

                if (!$existing) {
                    QcSigmaMetricResult::create([
                        'test_parameter_id' => $testParameter->id,
                        'sigma_metric' => round($sigmaMetric, 4),
                        'calculation_date' => $calculationDate,
                        'observed_mean' => round($observedMean, 4),
                        'observed_std_dev' => round($observedStdDev, 4),
                        'target_mean' => round($targetMean, 4),
                        'te_a' => $teA,
                        'period_days' => 30,
                    ]);
                    $resultsCreated++;
                }
            }
        }

        $this->command->info("Created {$resultsCreated} historical Sigma metric results.");
    }
}



