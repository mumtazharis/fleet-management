<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleAdmin = Role::where('name', 'admin')->first();
        $roleSpv = Role::where('name', 'supervisor')->first();
        $roleManager = Role::where('name', 'manager')->first();

        $locationHq = Location::where('type', 'head_office')->first();
        $locationMine = Location::where('type', 'mine_site')->first();

        $users = [
            [
                'name' => 'Admin Fleet Pool',
                'email' => 'admin@fleet.com',
                'password' => Hash::make('password123'),
                'role_id' => $roleAdmin?->id,
                'location_id' => $locationHq?->id,
                'phone' => '081234567890',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'spv@fleet.com',
                'password' => Hash::make('password123'),
                'role_id' => $roleSpv?->id,
                'location_id' => $locationMine?->id,
                'phone' => '081298765432',
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'manager@fleet.com',
                'password' => Hash::make('password123'),
                'role_id' => $roleManager?->id,
                'location_id' => $locationMine?->id,
                'phone' => '081355556666',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
