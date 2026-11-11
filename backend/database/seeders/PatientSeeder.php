<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('patients')->truncate();
        Schema::enableForeignKeyConstraints();

        // Use the factory to create 50 patients
        Patient::factory()->count(50)->create();

        $this->command->info('Patients seeded successfully.');
    }
} 