<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedSpecific extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seed-specific 
                            {--category= : Seed category (test, qc, delta, all)}
                            {--list : List all available seeders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed specific parts of the database without running the entire DatabaseSeeder';

    /**
     * Available seeders grouped by category
     */
    protected $seeders = [
        'test' => [
            'TestCategorySeeder' => 'Test Categories',
            'ComprehensiveTestParameterSeeder' => 'Tests and Parameters (Comprehensive)',
            'TestParameterSeeder' => 'Test Parameters (Basic)',
            'ReferenceRangeSeeder' => 'Reference Ranges',
        ],
        'qc' => [
            'QcRuleSeeder' => 'QC Rules',
            'QcAnalyteSigmaConfigSeeder' => 'QC Sigma Configurations',
            'QcSigmaMetricResultSeeder' => 'QC Sigma Results',
        ],
        'delta' => [
            'DeltaCheckRuleSeeder' => 'Delta Check Rules',
        ],
        'users' => [
            'UserSeeder' => 'Users',
            'UserRoleSeeder' => 'User Roles',
        ],
        'core' => [
            'WardSeeder' => 'Wards',
            'RoomSeeder' => 'Rooms',
            'PatientTypeSeeder' => 'Patient Types',
            'SampleTypeSeeder' => 'Sample Types',
            'DoctorSeeder' => 'Doctors',
            'PatientSeeder' => 'Patients',
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('list')) {
            $this->listSeeders();
            return 0;
        }

        $category = $this->option('category');

        if (!$category) {
            $this->error('Please specify a category using --category option');
            $this->listSeeders();
            return 1;
        }

        if ($category === 'all') {
            return $this->seedAll();
        }

        if (!isset($this->seeders[$category])) {
            $this->error("Unknown category: {$category}");
            $this->listSeeders();
            return 1;
        }

        return $this->seedCategory($category);
    }

    /**
     * List all available seeders
     */
    protected function listSeeders()
    {
        $this->info('Available Seeder Categories:');
        $this->newLine();

        foreach ($this->seeders as $category => $seeders) {
            $this->line("<fg=cyan>{$category}</fg=cyan>:");
            foreach ($seeders as $seeder => $description) {
                $this->line("  - <fg=green>{$seeder}</fg=green>: {$description}");
            }
            $this->newLine();
        }

        $this->info('Usage Examples:');
        $this->line('  php artisan db:seed-specific --category=test');
        $this->line('  php artisan db:seed-specific --category=qc');
        $this->line('  php artisan db:seed-specific --category=delta');
        $this->line('  php artisan db:seed-specific --category=all');
        $this->newLine();
        $this->line('To run a specific seeder:');
        $this->line('  php artisan db:seed --class=ComprehensiveTestParameterSeeder');
    }

    /**
     * Seed all categories
     */
    protected function seedAll()
    {
        if (!$this->confirm('This will seed ALL categories. Continue?')) {
            $this->info('Seeding cancelled.');
            return 0;
        }

        $this->info('Seeding all categories...');
        $this->newLine();

        foreach (array_keys($this->seeders) as $category) {
            $this->seedCategory($category);
        }

        $this->newLine();
        $this->info('All seeding completed!');
        return 0;
    }

    /**
     * Seed a specific category
     */
    protected function seedCategory(string $category)
    {
        $seeders = $this->seeders[$category];
        
        $this->info("Seeding category: <fg=cyan>{$category}</fg=cyan>");
        $this->newLine();

        foreach ($seeders as $seeder => $description) {
            $this->line("Running: <fg=yellow>{$description}</fg=yellow> ({$seeder})...");
            
            try {
                Artisan::call('db:seed', ['--class' => $seeder]);
                $this->line("<fg=green>✓</fg=green> {$seeder} completed");
            } catch (\Exception $e) {
                $this->error("✗ {$seeder} failed: {$e->getMessage()}");
                if (!$this->confirm('Continue with next seeder?')) {
                    return 1;
                }
            }
            
            $this->newLine();
        }

        $this->info("Category '{$category}' seeding completed!");
        return 0;
    }
}


