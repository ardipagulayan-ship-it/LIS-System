<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default pathologists
        $pathologists = [
            ['name' => 'Dr. Maria Santos', 'email' => 'maria.santos@lab.com'],
            ['name' => 'Dr. John Smith', 'email' => 'john.smith@lab.com'],
            ['name' => 'Dr. Sarah Chen', 'email' => 'sarah.chen@lab.com'],
        ];
        
        foreach ($pathologists as $pathologist) {
            $user = User::factory()->create([
                'name' => $pathologist['name'],
                'email' => $pathologist['email'],
                'password' => Hash::make('password'),
            ]);
            
            UserRole::create([
                'user_id' => $user->id,
                'role_type' => 'pathologist',
                'is_active' => true,
            ]);
        }
        
        // Create default verifiers
        $verifiers = [
            ['name' => 'Emma Wilson', 'email' => 'emma.wilson@lab.com'],
            ['name' => 'Michael Lee', 'email' => 'michael.lee@lab.com'],
            ['name' => 'David Garcia', 'email' => 'david.garcia@lab.com'],
        ];
        
        foreach ($verifiers as $verifier) {
            $user = User::factory()->create([
                'name' => $verifier['name'],
                'email' => $verifier['email'],
                'password' => Hash::make('password'),
            ]);
            
            UserRole::create([
                'user_id' => $user->id,
                'role_type' => 'verifier',
                'is_active' => true,
            ]);
        }
        
        // Create default performers (lab technicians)
        $performers = [
            ['name' => 'Lisa Kim', 'email' => 'lisa.kim@lab.com'],
            ['name' => 'James Rodriguez', 'email' => 'james.rodriguez@lab.com'],
            ['name' => 'Michelle Brown', 'email' => 'michelle.brown@lab.com'],
            ['name' => 'Robert Johnson', 'email' => 'robert.johnson@lab.com'],
        ];
        
        foreach ($performers as $performer) {
            $user = User::factory()->create([
                'name' => $performer['name'],
                'email' => $performer['email'],
                'password' => Hash::make('password'),
            ]);
            
            UserRole::create([
                'user_id' => $user->id,
                'role_type' => 'performer',
                'is_active' => true,
            ]);
        }
        
        // Create some users with multiple roles
        $multiRoleUser = User::factory()->create([
            'name' => 'Alex Taylor',
            'email' => 'alex.taylor@lab.com',
            'password' => Hash::make('password'),
        ]);
        
        UserRole::create([
            'user_id' => $multiRoleUser->id,
            'role_type' => 'verifier',
            'is_active' => true,
        ]);
        
        UserRole::create([
            'user_id' => $multiRoleUser->id,
            'role_type' => 'performer',
            'is_active' => true,
        ]);
    }
}
