<?php

namespace Database\Seeders;

use App\Models\BookingApproval;
use App\Models\Driver;
use App\Models\Location;
use App\Models\RentalCompany;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBooking;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Rental Companies
        $rental1 = RentalCompany::firstOrCreate(
            ['name' => 'PT Trans Tambang Utama'],
            ['contact_person' => 'Hendra Syahputra', 'phone' => '082111223344', 'address' => 'Morowali Industrial Park']
        );

        $rental2 = RentalCompany::firstOrCreate(
            ['name' => 'PT Nusantara Fleet Rent'],
            ['contact_person' => 'Dewi Lestari', 'phone' => '082199887766', 'address' => 'Kendari Trade Center']
        );

        $locations = Location::all();
        $hq = $locations->where('type', 'head_office')->first();
        $mine1 = $locations->where('type', 'mine_site')->first();
        $mine2 = $locations->where('type', 'mine_site')->skip(1)->first();

        // Vehicles
        $vehicles = [
            [
                'name' => 'Toyota Hilux Single Cab 4x4',
                'license_plate' => 'DN 8102 AB',
                'type' => 'cargo',
                'ownership' => 'company',
                'rental_company_id' => null,
                'location_id' => $mine1?->id,
                'status' => 'available',
                'fuel_type' => 'Dexlite',
            ],
            [
                'name' => 'Mitsubishi Triton Double Cab 4x4',
                'license_plate' => 'DN 8405 BC',
                'type' => 'passenger',
                'ownership' => 'company',
                'rental_company_id' => null,
                'location_id' => $mine1?->id,
                'status' => 'available',
                'fuel_type' => 'Dexlite',
            ],
            [
                'name' => 'Toyota HiAce Commuter (15 Seat)',
                'license_plate' => 'B 7099 SAA',
                'type' => 'passenger',
                'ownership' => 'rented',
                'rental_company_id' => $rental1->id,
                'location_id' => $hq?->id,
                'status' => 'available',
                'fuel_type' => 'Solar',
            ],
            [
                'name' => 'Hino Ranger Dump Truck 20 Ton',
                'license_plate' => 'DT 9912 NK',
                'type' => 'cargo',
                'ownership' => 'rented',
                'rental_company_id' => $rental2->id,
                'location_id' => $mine2?->id,
                'status' => 'available',
                'fuel_type' => 'Solar',
            ],
        ];

        foreach ($vehicles as $v) {
            Vehicle::firstOrCreate(
                ['license_plate' => $v['license_plate']],
                $v
            );
        }

        // Drivers
        $drivers = [
            ['name' => 'Rudi Hermawan', 'phone' => '085211112222', 'license_number' => 'SIM BII UMUM - 902181', 'status' => 'available'],
            ['name' => 'Agus Pratama', 'phone' => '085233334444', 'license_number' => 'SIM A UMUM - 442109', 'status' => 'available'],
            ['name' => 'Bambang Triyono', 'phone' => '085255556666', 'license_number' => 'SIM BII UMUM - 110293', 'status' => 'available'],
        ];

        foreach ($drivers as $d) {
            Driver::firstOrCreate(
                ['name' => $d['name']],
                $d
            );
        }
    }
}
