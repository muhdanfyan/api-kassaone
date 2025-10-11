<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShuDistribution;

class ShuDistributionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ShuDistribution::create([
            'fiscal_year' => 2024,
            'total_shu_amount' => 50000000,
            'distribution_date' => '2024-12-31',
            'reserve_percentage' => 25,
            'member_service_percentage' => 50,
            'management_service_percentage' => 10,
            'education_percentage' => 5,
            'social_percentage' => 7.5,
            'zakat_percentage' => 2.5,
        ]);

        ShuDistribution::create([
            'fiscal_year' => 2023,
            'total_shu_amount' => 45000000,
            'distribution_date' => '2023-12-31',
            'reserve_percentage' => 25,
            'member_service_percentage' => 50,
            'management_service_percentage' => 10,
            'education_percentage' => 5,
            'social_percentage' => 7.5,
            'zakat_percentage' => 2.5,
        ]);
    }
}
