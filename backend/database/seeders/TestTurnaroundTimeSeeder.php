<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Test;

class TestTurnaroundTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Common test turnaround times (in minutes)
        $testTurnaroundTimes = [
            // Hematology tests
            'Complete Blood Count (CBC)' => 60,
            'Hemoglobin' => 30,
            'Hematocrit' => 30,
            'White Blood Cell Count' => 45,
            'Platelet Count' => 45,
            'Differential Count' => 90,
            
            // Chemistry tests
            'Fasting Blood Sugar (FBS)' => 120,
            'Random Blood Sugar (RBS)' => 90,
            'HbA1c' => 180,
            'Lipid Profile' => 150,
            'Cholesterol' => 120,
            'Triglycerides' => 120,
            'Creatinine' => 60,
            'BUN' => 60,
            'Uric Acid' => 90,
            'ALT' => 120,
            'AST' => 120,
            'ALP' => 120,
            'Total Protein' => 90,
            'Albumin' => 90,
            'Bilirubin' => 90,
            
            // Microbiology tests
            'Gram Stain' => 180,
            'Culture and Sensitivity' => 1440, // 24 hours
            'Urinalysis' => 60,
            'Stool Examination' => 120,
            
            // Immunology tests
            'HIV Test' => 240,
            'Hepatitis B' => 180,
            'Hepatitis C' => 180,
            'Syphilis Test' => 180,
            
            // Coagulation tests
            'PT' => 60,
            'PTT' => 60,
            'INR' => 60,
            
            // Other common tests
            'Pregnancy Test' => 30,
            'Drug Screen' => 180,
            'Blood Type' => 60,
            'Crossmatch' => 120,
        ];

        foreach ($testTurnaroundTimes as $testName => $turnaroundTime) {
            Test::where('name', 'LIKE', "%{$testName}%")
                ->update(['turnaround_time_minutes' => $turnaroundTime]);
        }

        // Set default turnaround times for tests that don't match specific names
        Test::whereNull('turnaround_time_minutes')
            ->update(['turnaround_time_minutes' => 120]); // Default 2 hours
    }
}