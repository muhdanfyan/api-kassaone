<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PerumahanFeeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $feeSettings = [
            [
                'id' => (string) Str::ulid(),
                'fee_code' => 'security',
                'fee_name' => 'Iuran Keamanan',
                'amount' => 50000.00,
                'is_active' => true,
                'description' => 'Iuran untuk biaya satpam dan keamanan perumahan',
                'icon' => 'shield',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::ulid(),
                'fee_code' => 'cleaning',
                'fee_name' => 'Iuran Kebersihan',
                'amount' => 30000.00,
                'is_active' => true,
                'description' => 'Iuran untuk biaya kebersihan lingkungan',
                'icon' => 'trash',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::ulid(),
                'fee_code' => 'road_maintenance',
                'fee_name' => 'Iuran Perbaikan Jalan',
                'amount' => 20000.00,
                'is_active' => true,
                'description' => 'Iuran untuk pemeliharaan jalan dan infrastruktur',
                'icon' => 'construction',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('perumahan_fee_settings')->insert($feeSettings);
    }
}
