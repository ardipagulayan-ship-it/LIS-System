<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Since doctors might be referenced by existing requests after a refresh,
        // it might be safer to delete than truncate if constraints cause issues.
        // However, truncate is faster if constraints are handled.
        Schema::disableForeignKeyConstraints();
        DB::table('doctors')->truncate();
        Schema::enableForeignKeyConstraints();

        // Use the factory to create 10 doctors
        Doctor::factory()->count(10)->create();

        $this->command->info('Doctors seeded successfully.');
    }
} 