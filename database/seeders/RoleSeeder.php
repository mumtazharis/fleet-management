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
                'label' => 'Administrator Pool',
                'description' => 'Menginput pemesanan kendaraan, menentukan driver & pihak penyetuju, serta mengelola master data.',
            ],
            [
                'name' => 'supervisor',
                'label' => 'Supervisor (SPV)',
                'description' => 'Pihak penyetuju (Approver Level 1 / Atasan Langsung).',
            ],
            [
                'name' => 'manager',
                'label' => 'Manager Ops / Tambang',
                'description' => 'Pihak penyetuju (Approver Level 2 / Head of Operations).',
            ],
            [
                'name' => 'employee',
                'label' => 'Pegawai / Pemesan',
                'description' => 'Pegawai perusahaan yang menggunakan fasilitas kendaraan.',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
