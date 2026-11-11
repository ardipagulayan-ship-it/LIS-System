<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Test;
use App\Models\TestCategory; // If using categories
use App\Models\LaboratoryRequest;
use Illuminate\Support\Facades\DB; // For transaction
use Illuminate\Support\Facades\Hash; // <-- Add this line
use Throwable; // For catching errors
use Database\Seeders\TestCategorySeeder;
use Database\Seeders\TestSeeder;
use Database\Seeders\PatientSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\WardSeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\PatientTypeSeeder;
use Database\Seeders\TestParameterSeeder;
use Database\Seeders\ReferenceRangeSeeder;
use Database\Seeders\SampleTypeSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\UserRoleSeeder;
use Database\Seeders\QcAnalyteSigmaConfigSeeder;
use Database\Seeders\QcSigmaMetricResultSeeder;
use Database\Seeders\DeltaCheckRuleSeeder;
use Database\Seeders\QcRuleSeeder;
use Database\Seeders\ComprehensiveTestParameterSeeder;
use Database\Seeders\QualityControlSeeder;
use Database\Seeders\QualityControlTestParameterSeeder;
use Database\Seeders\QualityControlResultSeeder;
use Database\Seeders\EquipmentSeeder;
use Database\Seeders\AnalyzerSeeder;
use Database\Seeders\ComprehensiveReferenceRangeSeeder;
use Database\Seeders\CdsCorrelationRuleSeeder;
use Database\Seeders\DeltaCheckRecommendationSeeder;
use Database\Seeders\QualityControlRecommendationsSeeder;
use Database\Seeders\TestTurnaroundTimeSeeder;
use Database\Seeders\IMMSeeder;
use Database\Seeders\ReferenceRangeAnalyzerSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('--- Starting Database Seeding ---');

        try {
            // 1. Seed essential lookup tables & Doctors
            $this->command->info('[1/7] Seeding Core Data (Wards, Rooms, Types, Categories, Doctors)...');
            $this->call([
                WardSeeder::class,
                RoomSeeder::class,
                PatientTypeSeeder::class,
                SampleTypeSeeder::class,
                TestCategorySeeder::class,
                DoctorSeeder::class,
            ]);
            $this->command->line(' -> Core data seeded.');

            // 2. Seed Admin User
            $this->command->info('[2/7] Checking/Seeding Admin User...');
            $adminUser = User::firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Admin User',
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
             if ($adminUser->wasRecentlyCreated) {
                 $this->command->line(' -> Admin User created.');
             } else {
                 $this->command->line(' -> Admin User already exists.');
             }
             
            // 3. Seed User Roles (Pathologists, Verifiers, Performers)
            $this->command->info('[3/7] Seeding Lab Staff User Roles...');
            $this->call(UserRoleSeeder::class);
            $this->command->line(' -> Lab Staff User Roles seeded.');

            // 4. Seed Patients
            $this->command->info('[4/7] Seeding Patients...');
            Patient::factory(50)->create();
            $this->command->line(' -> Patients seeded (' . Patient::count() . ' total).');

            // 5. Seed Tests & Parameters
            $this->command->info('[5/7] Seeding Tests, Parameters, Ranges, and Sigma Configs...');
            
            // Use comprehensive seeder instead of factory for structured data
            $this->call(ComprehensiveTestParameterSeeder::class);
            $this->command->line(' -> Tests and Parameters seeded with proper category relationships.');
            
            // Seed Test Turnaround Time targets (depends on tests)
            $this->call(TestTurnaroundTimeSeeder::class);
            $this->command->line(' -> Test Turnaround Times seeded.');
            
            // Seed CDS Correlation Rules (depends on test parameters)
            $this->call(CdsCorrelationRuleSeeder::class);
            $this->command->line(' -> CDS Correlation Rules seeded.');
            
            // Seed Delta Check Recommendations (depends on test parameters)
            $this->call(DeltaCheckRecommendationSeeder::class);
            $this->command->line(' -> Delta Check Recommendations seeded.');
            
            // Keep factory for additional random tests if needed (optional)
            // Test::factory(5)->create();
            
            $this->call(ReferenceRangeSeeder::class);
            $this->command->line(' -> Reference Ranges seeded.');
            
            // Seed QC master data and results used for Sigma calculations
            $this->call(QualityControlSeeder::class);
            $this->call(QualityControlTestParameterSeeder::class);
            
            // Seed Quality Control Recommendations
            $this->call(QualityControlRecommendationsSeeder::class);
            $this->command->line(' -> Quality Control Recommendations seeded.');
            
            // Seed Equipment
            $this->command->info('[6/7] Seeding Equipment...');
            $this->call(EquipmentSeeder::class);
            $this->command->line(' -> Equipment seeded.');
            
            // Seed Analyzers (required for analyzer-specific reference ranges) - after equipment
            $this->call(AnalyzerSeeder::class);
            $this->command->line(' -> Analyzers seeded.');
            
            // Seed Comprehensive Reference Ranges with equipment-specific ranges - after equipment & analyzers
            $this->call(ComprehensiveReferenceRangeSeeder::class);
            $this->command->line(' -> Comprehensive Reference Ranges seeded.');
            
            // Seed Reference Range Analyzer mappings (if needed) - after analyzers
            $this->call(ReferenceRangeAnalyzerSeeder::class);
            $this->command->line(' -> Reference Range Analyzer mappings seeded.');
            
            // Seed IMM (Inventory Management Module) - after equipment
            $this->call(IMMSeeder::class);
            $this->command->line(' -> Inventory Management data seeded.');
            
            $this->call(QualityControlResultSeeder::class);

            // Seed Sigma Metric configurations and sample results
            $this->call(QcAnalyteSigmaConfigSeeder::class);
            $this->call(QcSigmaMetricResultSeeder::class);
            $this->command->line(' -> Sigma Metric configurations and sample results seeded.');
            
            // Seed Delta Check and QC Rules
            $this->command->info('[5.1/7] Seeding Delta Check and QC Rules...');
            $this->call(DeltaCheckRuleSeeder::class);
            $this->call(QcRuleSeeder::class);
            $this->command->line(' -> Delta Check and QC Rules seeded.');

            // 6. Seed Laboratory Requests
            $this->command->info('[6/7] Seeding Laboratory Requests...');
             if (!Test::exists() || !Patient::exists() || !Doctor::exists()) {
                  $this->command->error(' -> Cannot seed Laboratory Requests: Missing dependencies (Tests, Patients, or Doctors).');
                  throw new \Exception('Missing dependencies for Lab Request seeding.');
             }
            $requestsCreatedCount = 0;
            $totalRequestsToCreate = 100;
            LaboratoryRequest::factory($totalRequestsToCreate)
                ->create()
                ->each(function ($request) use (&$requestsCreatedCount) {
                     $testsToAttach = Test::inRandomOrder()->limit(rand(1, 5))->get();
                     if ($testsToAttach->isEmpty()) {
                         $this->command->warn(" -> Could not find random tests for request ID: {$request->id}. Skipping attachment.");
                         return;
                     }
                     $pivotData = [];
                     foreach ($testsToAttach as $test) {
                         $price = $test->price ?? 0;
                         $pivotData[$test->id] = ['price_at_request' => $price];
                     }
                     $request->tests()->attach($pivotData);
                     $requestsCreatedCount++;
                 });
             $this->command->line(" -> Laboratory Requests seeded ({$requestsCreatedCount} / {$totalRequestsToCreate} created with attachments).");

            // 8. Seed LIS Bridge configuration (optional — only if not already seeded)
            //    Run independently via: php artisan db:seed --class=LisBridgeConfigSeeder
            //    Requires Equipment to be seeded first (resolves by serial_no).
            if (class_exists(\App\Models\LisBridgeConfig::class)) {
                $this->command->info('[8/8] Seeding LIS Bridge Config and API Key...');
                $this->call(LisBridgeConfigSeeder::class);
                $this->command->line(' -> LIS Bridge config seeded.');
            }

            $this->command->info('[7/7] Database Seeding Completed Successfully! ---');

        } catch (Throwable $e) {
            $this->command->error('--- Database Seeding Failed During Execution! ---');
            $this->command->error('Error Message: ' . $e->getMessage());
            $this->command->error('File: ' . $e->getFile() . ' (Line: ' . $e->getLine() . ')');
            report($e);
        }
    }
}
