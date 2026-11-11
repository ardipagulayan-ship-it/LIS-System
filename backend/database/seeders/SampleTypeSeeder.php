<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SampleType; // Assuming model name is SampleType
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Import Schema facade

class SampleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints(); // Disable checks
        DB::table('sample_types')->truncate(); // Assuming table name is sample_types
        Schema::enableForeignKeyConstraints(); // Re-enable checks

        $sampleTypes = [
            ['name' => 'Blood', /* 'description' => 'Whole blood sample', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Urine', /* 'description' => 'Urine sample', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Serum', /* 'description' => 'Blood serum sample', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Plasma', /* 'description' => 'Blood plasma sample', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Stool', /* 'description' => 'Stool sample', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tissue', /* 'description' => 'Biopsy tissue sample', */ 'created_at' => now(), 'updated_at' => now()],
        ];

        // Check if SampleType model exists before inserting
        if (class_exists(SampleType::class)) {
            SampleType::insert($sampleTypes);
            $this->command->info('Sample types seeded successfully.');
        } else {
            // Fallback if model doesn't exist (e.g., insert directly)
            // DB::table('sample_types')->insert($sampleTypes);
            $this->command->warn('SampleType model not found. Skipping seeding.');
        }
    }
} 