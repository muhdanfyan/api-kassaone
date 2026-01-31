<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PerumahanAreasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            ['area_code' => 'A-01', 'area_name' => 'Blok A Utara', 'house_count' => 20],
            ['area_code' => 'A-02', 'area_name' => 'Blok A Selatan', 'house_count' => 15],
            ['area_code' => 'A-03', 'area_name' => 'Blok A Timur', 'house_count' => 18],
            ['area_code' => 'B-01', 'area_name' => 'Blok B Utara', 'house_count' => 22],
            ['area_code' => 'B-02', 'area_name' => 'Blok B Selatan', 'house_count' => 20],
            ['area_code' => 'C-01', 'area_name' => 'Blok C', 'house_count' => 30],
            ['area_code' => 'D-01', 'area_name' => 'Blok D', 'house_count' => 25],
        ];

        foreach ($areas as $area) {
            DB::table('perumahan_areas')->insert([
                'id' => (string) Str::ulid(),
                'area_code' => $area['area_code'],
                'area_name' => $area['area_name'],
                'description' => null,
                'house_count' => $area['house_count'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
