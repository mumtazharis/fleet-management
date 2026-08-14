<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regionSulteng = Region::firstOrCreate(
            ['name' => 'Sulawesi Tengah'],
            ['description' => 'Region Operasional Morowali & Palu']
        );

        $regionSulsel = Region::firstOrCreate(
            ['name' => 'Sulawesi Selatan'],
            ['description' => 'Region Kantor Cabang Makassar']
        );

        $regionJakarta = Region::firstOrCreate(
            ['name' => 'DKI Jakarta'],
            ['description' => 'Kantor Pusat Jakarta']
        );

        $regionMaluku = Region::firstOrCreate(
            ['name' => 'Maluku Utara'],
            ['description' => 'Region Tambang Halmahera']
        );

        // 1 Kantor Pusat
        Location::firstOrCreate(
            ['name' => 'Kantor Pusat Jakarta'],
            ['region_id' => $regionJakarta->id, 'type' => 'head_office', 'address' => 'Jl. Jend. Sudirman No. 45, Jakarta Selatan']
        );

        // 1 Kantor Cabang
        Location::firstOrCreate(
            ['name' => 'Kantor Cabang Makassar'],
            ['region_id' => $regionSulsel->id, 'type' => 'branch_office', 'address' => 'Jl. AP Pettarani No. 12, Makassar']
        );

        // 6 Lokasi Tambang
        $mines = [
            ['name' => 'Lokasi Tambang Morowali Site A', 'region_id' => $regionSulteng->id],
            ['name' => 'Lokasi Tambang Morowali Site B', 'region_id' => $regionSulteng->id],
            ['name' => 'Lokasi Tambang Kolonodale Site C', 'region_id' => $regionSulteng->id],
            ['name' => 'Lokasi Tambang Weda Bay Site D', 'region_id' => $regionMaluku->id],
            ['name' => 'Lokasi Tambang Obi Island Site E', 'region_id' => $regionMaluku->id],
            ['name' => 'Lokasi Tambang Halmahera Site F', 'region_id' => $regionMaluku->id],
        ];

        foreach ($mines as $mine) {
            Location::firstOrCreate(
                ['name' => $mine['name']],
                [
                    'region_id' => $mine['region_id'],
                    'type' => 'mine_site',
                    'address' => $mine['name'] . ' Area Pertambangan Nikel'
                ]
            );
        }
    }
}
