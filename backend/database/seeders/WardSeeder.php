<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('wards')->truncate();
        Schema::enableForeignKeyConstraints();

        $wards = [
            ['name' => 'General Ward', /* 'description' => 'General patient care', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pediatric Ward', /* 'description' => 'For children', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Maternity Ward', /* 'description' => 'For expectant and new mothers', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ICU', /* 'description' => 'Intensive Care Unit', */ 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'OPD', /* 'description' => 'Out-Patient Department', */ 'created_at' => now(), 'updated_at' => now()],
        ];

        Ward::insert($wards);
        $this->command->info('Wards seeded successfully.');
    }
}
