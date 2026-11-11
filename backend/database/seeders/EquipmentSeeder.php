<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Equipment;
use App\Models\TestCategory;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing equipment
        // Equipment::truncate(); // Be careful with truncate if foreign keys exist

        $hematology = TestCategory::where('name', 'Hematology')->first();
        $chemistry = TestCategory::where('name', 'Clinical Chemistry')->first();
        
        // Create some specific equipment
        Equipment::create([
            'unique_asset_id' => 'HEM-001',
            'manufacturer' => 'Sysmex',
            'model' => 'XN-1000',
            'serial_number' => 'SN-SYS-1001',
            'purchase_date' => '2023-01-15',
            'warranty_expiration_date' => '2026-01-15',
            'location_lab' => 'Main Lab',
            'location_bench' => 'Hematology Bench 1',
            'current_status' => 'in_service',
            'test_category_id' => $hematology ? $hematology->id : null,
            'sop_operation_manual_link' => 'http://example.com/manuals/sysmex-xn1000',
            'cycle_count' => 1500,
            'runtime_hours' => 360.5,
            'last_maintenance_date' => '2023-12-15',
            'next_maintenance_date' => '2024-06-15',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        Equipment::create([
            'unique_asset_id' => 'CHEM-001',
            'manufacturer' => 'Roche',
            'model' => 'Cobas c311',
            'serial_number' => 'SN-ROC-3111',
            'purchase_date' => '2022-05-20',
            'warranty_expiration_date' => '2025-05-20',
            'location_lab' => 'Main Lab',
            'location_bench' => 'Chemistry Bench 1',
            'current_status' => 'in_service',
            'test_category_id' => $chemistry ? $chemistry->id : null,
            'sop_operation_manual_link' => 'http://example.com/manuals/roche-cobas',
            'cycle_count' => 5000,
            'runtime_hours' => 1200.0,
            'last_maintenance_date' => '2024-01-10',
            'next_maintenance_date' => '2024-04-10',
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        // Create 20 random equipment
        Equipment::factory()->count(20)->create();
    }
}
