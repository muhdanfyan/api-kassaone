<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PerumahanStaffTeamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            [
                'id' => (string) Str::ulid(),
                'team_code' => 'TIM-01',
                'team_name' => 'Tim Sampah Pagi',
                'description' => 'Tim pengangkut sampah shift pagi (06:00 - 12:00)',
                'member_count' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::ulid(),
                'team_code' => 'TIM-02',
                'team_name' => 'Tim Sampah Sore',
                'description' => 'Tim pengangkut sampah shift sore (12:00 - 18:00)',
                'member_count' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::ulid(),
                'team_code' => 'TIM-03',
                'team_name' => 'Tim Khusus B3',
                'description' => 'Tim khusus penanganan sampah B3',
                'member_count' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('perumahan_staff_teams')->insert($teams);
    }
}
