<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Analyzer;
use App\Models\TestCategory;
use Illuminate\Support\Facades\DB;

class AnalyzerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear the table first
        //DB::table('analyzers')->truncate();

        // Get test categories (assuming they exist)
        $hematologyCategory = TestCategory::where('name', 'Hematology')->first();
        $chemistryCategory = TestCategory::where('name', 'Clinical Chemistry')->first();
        $microbiologyCategory = TestCategory::where('name', 'Microbiology')->first();
        $serologyCategory = TestCategory::where('name', 'Serology')->first();
        
        // Create standard analyzers
        $analyzers = [
            // Hematology Analyzers
            [
                'name' => 'Sysmex XN-550',
                'brand' => 'Sysmex',
                'model' => 'XN-550',
                'serial_no' => 'SYSXN123456',
                'remarks' => 'Automated Hematology Analyzer',
                'test_category_id' => $hematologyCategory ? $hematologyCategory->id : null,
                'isActive' => true,
            ],
            [
                'name' => 'Mindray BC-6200',
                'brand' => 'Mindray',
                'model' => 'BC-6200',
                'serial_no' => 'MINBC123456',
                'remarks' => 'Automated Hematology Analyzer',
                'test_category_id' => $hematologyCategory ? $hematologyCategory->id : null,
                'isActive' => true,
            ],
            
            // Chemistry Analyzers
            [
                'name' => 'Roche Cobas c501',
                'brand' => 'Roche',
                'model' => 'Cobas c501',
                'serial_no' => 'ROC501123456',
                'remarks' => 'Clinical Chemistry Analyzer',
                'test_category_id' => $chemistryCategory ? $chemistryCategory->id : null,
                'isActive' => true,
            ],
            [
                'name' => 'Abbott Architect c4000',
                'brand' => 'Abbott',
                'model' => 'Architect c4000',
                'serial_no' => 'ABB4000123456',
                'remarks' => 'Clinical Chemistry Analyzer',
                'test_category_id' => $chemistryCategory ? $chemistryCategory->id : null,
                'isActive' => true,
            ],
            
            // Microbiology Analyzers
            [
                'name' => 'BacT/ALERT 3D',
                'brand' => 'BioMérieux',
                'model' => 'BacT/ALERT 3D',
                'serial_no' => 'BIO3D123456',
                'remarks' => 'Microbial Detection System',
                'test_category_id' => $microbiologyCategory ? $microbiologyCategory->id : null,
                'isActive' => true,
            ],
            
            // Serology Analyzers
            [
                'name' => 'Abbott ARCHITECT i2000SR',
                'brand' => 'Abbott',
                'model' => 'ARCHITECT i2000SR',
                'serial_no' => 'ABBI2000123456',
                'remarks' => 'Immunoassay Analyzer',
                'test_category_id' => $serologyCategory ? $serologyCategory->id : null,
                'isActive' => true,
            ],
            
            // Generic Methods
            [
                'name' => 'Manual',
                'brand' => 'Generic',
                'model' => 'Manual Method',
                'serial_no' => 'MANUAL001',
                'remarks' => 'Manual testing methods',
                'test_category_id' => null, // Applicable to all categories
                'isActive' => true,
            ],
            [
                'name' => 'Microscopy',
                'brand' => 'Generic',
                'model' => 'Microscope',
                'serial_no' => 'MICRO001',
                'remarks' => 'Microscopic examination',
                'test_category_id' => null, // Applicable to all categories
                'isActive' => true,
            ],
        ];
        
        // Insert analyzers
        foreach ($analyzers as $analyzer) {
            Analyzer::create($analyzer);
        }
        
        $this->command->info('Analyzers seeded successfully!');
    }
}
