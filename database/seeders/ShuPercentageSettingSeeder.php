<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShuPercentageSetting;

class ShuPercentageSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default setting sesuai UU Koperasi No. 25/1992
        ShuPercentageSetting::create([
            'name' => 'Default UU Koperasi 2024',
            'fiscal_year' => '2024',
            'is_active' => true,
            'cadangan_percentage' => 30.00,
            'anggota_percentage' => 70.00,
            'pengurus_percentage' => 0.00,
            'karyawan_percentage' => 0.00,
            'dana_sosial_percentage' => 0.00,
            'jasa_modal_percentage' => 40.00,
            'jasa_usaha_percentage' => 60.00,
            'description' => 'Setting default sesuai UU Koperasi No. 25 Tahun 1992: Cadangan 30%, Anggota 70% (Jasa Modal 40%, Jasa Usaha 60%)',
        ]);

        // Setting 2025 (aktif untuk tahun depan)
        ShuPercentageSetting::create([
            'name' => 'Default UU Koperasi 2025',
            'fiscal_year' => '2025',
            'is_active' => true,
            'cadangan_percentage' => 30.00,
            'anggota_percentage' => 70.00,
            'pengurus_percentage' => 0.00,
            'karyawan_percentage' => 0.00,
            'dana_sosial_percentage' => 0.00,
            'jasa_modal_percentage' => 40.00,
            'jasa_usaha_percentage' => 60.00,
            'description' => 'Setting default sesuai UU Koperasi No. 25 Tahun 1992',
        ]);

        // Contoh: Custom setting dengan bonus pengurus & karyawan
        ShuPercentageSetting::create([
            'name' => 'Custom dengan Bonus 2024',
            'fiscal_year' => '2024',
            'is_active' => false,
            'cadangan_percentage' => 30.00,
            'anggota_percentage' => 63.00,
            'pengurus_percentage' => 5.00,
            'karyawan_percentage' => 2.00,
            'dana_sosial_percentage' => 0.00,
            'jasa_modal_percentage' => 40.00,
            'jasa_usaha_percentage' => 60.00,
            'description' => 'Setting custom dengan alokasi bonus untuk pengurus (5%) dan karyawan (2%)',
        ]);

        // Contoh: Setting dengan CSR/Dana Sosial
        ShuPercentageSetting::create([
            'name' => 'Setting CSR 2024',
            'fiscal_year' => '2024',
            'is_active' => false,
            'cadangan_percentage' => 30.00,
            'anggota_percentage' => 60.00,
            'pengurus_percentage' => 3.00,
            'karyawan_percentage' => 2.00,
            'dana_sosial_percentage' => 5.00,
            'jasa_modal_percentage' => 35.00,
            'jasa_usaha_percentage' => 65.00,
            'description' => 'Setting dengan alokasi dana sosial/CSR (5%) untuk kegiatan sosial koperasi',
        ]);
    }
}

