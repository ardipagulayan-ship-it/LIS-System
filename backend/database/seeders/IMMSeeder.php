<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaterialMaster;
use App\Models\Vendor;
use App\Models\LotStockRecord;
use Carbon\Carbon;

class IMMSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample vendors
        $vendors = [
            [
                'vendor_name' => 'ABC Laboratory Supplies',
                'contact_person' => 'John Smith',
                'email' => 'john@abclab.com',
                'phone' => '+1-555-0123',
                'address' => '123 Lab Street',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'USA',
                'website' => 'https://abclab.com',
                'currency' => 'USD',
                'is_active' => true
            ],
            [
                'vendor_name' => 'XYZ Medical Equipment',
                'contact_person' => 'Jane Doe',
                'email' => 'jane@xyzmed.com',
                'phone' => '+1-555-0456',
                'address' => '456 Medical Ave',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'postal_code' => '90210',
                'country' => 'USA',
                'website' => 'https://xyzmed.com',
                'currency' => 'USD',
                'is_active' => true
            ],
            [
                'vendor_name' => 'Global Reagents Inc',
                'contact_person' => 'Mike Johnson',
                'email' => 'mike@globalreagents.com',
                'phone' => '+1-555-0789',
                'address' => '789 Reagent Blvd',
                'city' => 'Chicago',
                'state' => 'IL',
                'postal_code' => '60601',
                'country' => 'USA',
                'currency' => 'USD',
                'is_active' => true
            ]
        ];

        $createdVendors = [];
        foreach ($vendors as $vendorData) {
            $createdVendors[] = Vendor::create($vendorData);
        }

        // Create sample materials
        $materials = [
            [
                'generic_name' => 'Glucose Reagent',
                'manufacturer' => 'Roche Diagnostics',
                'catalog_number' => 'GLU-001',
                'description' => 'Enzymatic glucose reagent for clinical chemistry analyzers',
                'standard_unit_of_measure' => 'mL',
                'shelf_life_days' => 365,
                'material_type' => 'reagent',
                'storage_requirements' => 'Store at 2-8°C, protect from light',
                'hazard_classification' => 'Non-hazardous',
                'is_active' => true
            ],
            [
                'generic_name' => 'Quality Control Level 1',
                'manufacturer' => 'Bio-Rad',
                'catalog_number' => 'QC-L1-001',
                'description' => 'Normal range quality control material',
                'standard_unit_of_measure' => 'vial',
                'shelf_life_days' => 180,
                'material_type' => 'control',
                'storage_requirements' => 'Store at 2-8°C',
                'hazard_classification' => 'Non-hazardous',
                'is_active' => true
            ],
            [
                'generic_name' => 'Calibrator Set',
                'manufacturer' => 'Siemens',
                'catalog_number' => 'CAL-SET-001',
                'description' => 'Multi-level calibrator set for chemistry panel',
                'standard_unit_of_measure' => 'set',
                'shelf_life_days' => 90,
                'material_type' => 'calibrator',
                'storage_requirements' => 'Store at 2-8°C',
                'hazard_classification' => 'Non-hazardous',
                'is_active' => true
            ],
            [
                'generic_name' => 'Disposable Pipette Tips',
                'manufacturer' => 'Eppendorf',
                'catalog_number' => 'TIP-200-001',
                'description' => '200μL sterile pipette tips',
                'standard_unit_of_measure' => 'box',
                'shelf_life_days' => 1095,
                'material_type' => 'consumable',
                'storage_requirements' => 'Store at room temperature',
                'hazard_classification' => 'Non-hazardous',
                'is_active' => true
            ],
            [
                'generic_name' => 'Chemistry Analyzer',
                'manufacturer' => 'Beckman Coulter',
                'catalog_number' => 'AU-680-001',
                'description' => 'High-throughput clinical chemistry analyzer',
                'standard_unit_of_measure' => 'unit',
                'shelf_life_days' => null,
                'material_type' => 'equipment',
                'storage_requirements' => 'Install in temperature-controlled environment',
                'hazard_classification' => 'Non-hazardous',
                'is_active' => true
            ]
        ];

        $createdMaterials = [];
        foreach ($materials as $materialData) {
            $createdMaterials[] = MaterialMaster::create($materialData);
        }

        // Create sample lots
        $lots = [
            [
                'material_id' => $createdMaterials[0]->id, // Glucose Reagent
                'lot_number' => 'GLU-2024-001',
                'expiration_date' => Carbon::now()->addDays(300),
                'date_received' => Carbon::now()->subDays(30),
                'vendor_id' => $createdVendors[0]->id,
                'initial_quantity' => 1000,
                'current_stock_level' => 850,
                'reorder_point' => 200,
                'reorder_quantity' => 500,
                'storage_location' => 'Refrigerator A1',
                'cost_per_unit' => 0.15,
                'total_cost' => 150,
                'status' => 'active'
            ],
            [
                'material_id' => $createdMaterials[0]->id, // Glucose Reagent
                'lot_number' => 'GLU-2024-002',
                'expiration_date' => Carbon::now()->addDays(60),
                'date_received' => Carbon::now()->subDays(10),
                'vendor_id' => $createdVendors[0]->id,
                'initial_quantity' => 500,
                'current_stock_level' => 500,
                'reorder_point' => 200,
                'reorder_quantity' => 500,
                'storage_location' => 'Refrigerator A2',
                'cost_per_unit' => 0.18,
                'total_cost' => 90,
                'status' => 'quarantine',
                'quarantine_notes' => 'New lot - pending QC verification'
            ],
            [
                'material_id' => $createdMaterials[1]->id, // Quality Control Level 1
                'lot_number' => 'QC-L1-2024-001',
                'expiration_date' => Carbon::now()->addDays(120),
                'date_received' => Carbon::now()->subDays(15),
                'vendor_id' => $createdVendors[1]->id,
                'initial_quantity' => 50,
                'current_stock_level' => 35,
                'reorder_point' => 10,
                'reorder_quantity' => 25,
                'storage_location' => 'Refrigerator B1',
                'cost_per_unit' => 2.50,
                'total_cost' => 125,
                'status' => 'active'
            ],
            [
                'material_id' => $createdMaterials[3]->id, // Disposable Pipette Tips
                'lot_number' => 'TIP-2024-001',
                'expiration_date' => Carbon::now()->addDays(1000),
                'date_received' => Carbon::now()->subDays(5),
                'vendor_id' => $createdVendors[2]->id,
                'initial_quantity' => 10000,
                'current_stock_level' => 9500,
                'reorder_point' => 2000,
                'reorder_quantity' => 5000,
                'storage_location' => 'Shelf C1',
                'cost_per_unit' => 0.05,
                'total_cost' => 500,
                'status' => 'active'
            ]
        ];

        foreach ($lots as $lotData) {
            LotStockRecord::create($lotData);
        }

        $this->command->info('IMM sample data created successfully!');
        $this->command->info('Created:');
        $this->command->info('- ' . count($createdVendors) . ' vendors');
        $this->command->info('- ' . count($createdMaterials) . ' materials');
        $this->command->info('- ' . count($lots) . ' lots');
    }
}
