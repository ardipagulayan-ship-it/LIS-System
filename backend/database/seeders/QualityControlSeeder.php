<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Carbon;

class QualityControlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pick an existing equipment id; if none, skip with a warning
        $equipmentId = DB::table('equipment')->inRandomOrder()->value('id');
        if (!$equipmentId) {
            if (method_exists($this->command, 'warn')) {
                $this->command->warn('Skipping QualityControlSeeder: no equipment found. Seed equipment first.');
            }
            return;
        }

        DB::table('quality_controls')->insert([
            [
                'test_parameter_id' => 1,
                'equipment_id' => $equipmentId,
                'control_name' => 'Control A',
                'lot_number' => 'LOT123',
                'expiry_date' => now()->addYear(),
                'mean' => 100.0,
                'sd' => 2.0,
                'target' => 100.0,
                'sd_1_plus' => 102.0,
                'sd_1_minus' => 98.0,
                'sd_2_plus' => 104.0,
                'sd_2_minus' => 96.0,
                'sd_3_plus' => 106.0,
                'sd_3_minus' => 94.0,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'test_parameter_id' => 2,
                'equipment_id' => $equipmentId,
                'control_name' => 'Control B',
                'lot_number' => 'LOT456',
                'expiry_date' => now()->addYear(),
                'mean' => 200.0,
                'sd' => 5.0,
                'target' => 200.0,
                'sd_1_plus' => 205.0,
                'sd_1_minus' => 195.0,
                'sd_2_plus' => 210.0,
                'sd_2_minus' => 190.0,
                'sd_3_plus' => 215.0,
                'sd_3_minus' => 185.0,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
