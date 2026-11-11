<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('rooms')->truncate();
        Schema::enableForeignKeyConstraints();

        // Fetch existing ward IDs or create them if they don't exist
        $generalWard = Ward::firstOrCreate(['name' => 'General Ward']);
        $pediatricWard = Ward::firstOrCreate(['name' => 'Pediatric Ward']);
        $maternityWard = Ward::firstOrCreate(['name' => 'Maternity Ward']);
        $icuWard = Ward::firstOrCreate(['name' => 'ICU']);
        $opdWard = Ward::firstOrCreate(['name' => 'OPD']);


        $rooms = [
            // General Ward Rooms
            ['name' => 'G101', 'ward_id' => $generalWard->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'G102', 'ward_id' => $generalWard->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'G103', 'ward_id' => $generalWard->id, 'created_at' => now(), 'updated_at' => now()],

            // Pediatric Ward Rooms
            ['name' => 'P201', 'ward_id' => $pediatricWard->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'P202', 'ward_id' => $pediatricWard->id, 'created_at' => now(), 'updated_at' => now()],

            // Maternity Ward Rooms
            ['name' => 'M301', 'ward_id' => $maternityWard->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'M302', 'ward_id' => $maternityWard->id, 'created_at' => now(), 'updated_at' => now()],

            // ICU Rooms
            ['name' => 'ICU01', 'ward_id' => $icuWard->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ICU02', 'ward_id' => $icuWard->id, 'created_at' => now(), 'updated_at' => now()],

            // OPD Rooms (Exam Rooms, etc.)
            ['name' => 'OPD-E1', 'ward_id' => $opdWard->id, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'OPD-E2', 'ward_id' => $opdWard->id, 'created_at' => now(), 'updated_at' => now()],
        ];

        Room::insert($rooms);
        $this->command->info('Rooms seeded successfully.');
    }
}
