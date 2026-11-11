<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TestCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates standard test categories used in clinical laboratories.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hematology',
                'description' => 'Blood cell analysis and coagulation studies',
                'color' => '#c353f3',
            ],
            [
                'name' => 'Clinical Chemistry',
                'description' => 'Biochemistry tests including liver function, kidney function, lipids, and electrolytes',
                'color' => '#FFD700',
            ],
            [
                'name' => 'Coagulation',
                'description' => 'Coagulation and hemostasis studies',
                'color' => '#48a3ee',
            ],
            [
                'name' => 'Serology/Immunology',
                'description' => 'Serological and immunological tests',
                'color' => '#FF69B4',
            ],
            [
                'name' => 'Clinical Microscopy',
                'description' => 'Urine analysis and other microscopic examinations',
                'color' => '#FFA500',
            ],
            [
                'name' => 'Microbiology',
                'description' => 'Microbiological cultures and identifications',
            ],
            [
                'name' => 'Histopathology',
                'description' => 'Tissue examination and pathology',
            ],
            [
                'name' => 'Molecular Diagnostics',
                'description' => 'Molecular and genetic testing',
            ],
        ];

        foreach ($categories as $categoryData) {
            TestCategory::firstOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );
        }

        $this->command->info('Test categories seeded successfully (' . count($categories) . ' categories).');
    }
}
