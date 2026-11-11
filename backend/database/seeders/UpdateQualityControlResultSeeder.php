<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\QualityControlTestParameter;

class UpdateQualityControlResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assuming QualityControlTestParameter with ID 1 is the one causing issues
        // You might need to adjust the ID or add more specific conditions if needed
        $qcTestParameter = QualityControlTestParameter::find(1);

        if ($qcTestParameter) {
            // Set new target and tolerance values based on the existing results (around 100)
            $qcTestParameter->target = 100.00;
            $qcTestParameter->tolerance = 2.00; // Example tolerance, adjust as needed

            // Recalculate SD limits based on the new target and tolerance
            // Assuming a simple calculation for SD limits, usually: target +/- N * tolerance
            $qcTestParameter->minus_1sd = $qcTestParameter->target - $qcTestParameter->tolerance;
            $qcTestParameter->plus_1sd = $qcTestParameter->target + $qcTestParameter->tolerance;
            $qcTestParameter->minus_2sd = $qcTestParameter->target - (2 * $qcTestParameter->tolerance);
            $qcTestParameter->plus_2sd = $qcTestParameter->target + (2 * $qcTestParameter->tolerance);
            $qcTestParameter->minus_3sd = $qcTestParameter->target - (3 * $qcTestParameter->tolerance);
            $qcTestParameter->plus_3sd = $qcTestParameter->target + (3 * $qcTestParameter->tolerance);

            $qcTestParameter->save();

            $this->command->info('QualityControlTestParameter with ID 1 updated successfully.');
        } else {
            $this->command->warn('QualityControlTestParameter with ID 1 not found.');
        }
    }
}
