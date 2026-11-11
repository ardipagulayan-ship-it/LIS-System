<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PatientType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PatientTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('patient_types')->truncate();
        Schema::enableForeignKeyConstraints();

        $patientTypes = [
            ['name' => 'Inpatient', /* 'description' => 'Patients admitted to the hospital for overnight stay.', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Outpatient', /* 'description' => 'Patients receiving treatment without being admitted.', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Emergency', /* 'description' => 'Patients requiring immediate medical attention.', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Observation', /* 'description' => 'Patients under observation to determine need for admission.', */ 'created_at' => now(), 'updated_at' => now()],
        ];

        PatientType::insert($patientTypes);
        $this->command->info('Patient types seeded successfully.');
    }
}
