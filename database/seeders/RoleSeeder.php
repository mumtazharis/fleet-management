<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'level' => 0,
                'label' => 'Administrator Pool',
                'description' => 'Menginput pemesanan kendaraan, menentukan driver & pihak penyetuju, serta mengelola master data.',
            ],
            [
                'name' => 'supervisor',
                'level' => 1,
                'label' => 'Supervisor',
                'description' => 'Pihak penyetuju (Approver Level 1 / Atasan Langsung).',
            ],
            [
                'name' => 'manager',
                'level' => 2,
                'label' => 'Manager',
                'description' => 'Pihak penyetuju (Approver Level 2 / Head of Operations).',
            ],
            [
                'name' => 'employee',
                'level' => 0,
                'label' => 'Pegawai / Pemesan',
                'description' => 'Pegawai perusahaan yang menggunakan fasilitas kendaraan.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
