<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterAkunSeeder extends Seeder
{
    public function run(): void
    {
        // Get first admin or any member to be the creator
        $admin = Member::whereHas('role', function($q) {
            $q->where('name', 'Admin');
        })->first() ?? Member::first();

        if (!$admin) {
            $this->command->error('No member found to associate as creator.');
            return;
        }

        // Disable foreign key checks to truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Account::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [
            ['code' => '1101', 'name' => 'Kas Kecil', 'group' => 'Aset Lancar', 'type' => 'Aktiva'],
            ['code' => '1102', 'name' => 'Kas Besar', 'group' => 'Aset Lancar', 'type' => 'Aktiva'],
            ['code' => '1103', 'name' => 'Bank – Giro', 'group' => 'Aset Lancar', 'type' => 'Aktiva'],
            ['code' => '1104', 'name' => 'Bank – Tabungan', 'group' => 'Aset Lancar', 'type' => 'Aktiva'],
            ['code' => '1105', 'name' => 'Piutang Usaha Anggota', 'group' => 'Aset Lancar', 'type' => 'Aktiva'],
            ['code' => '1106', 'name' => 'Piutang Usaha Non Anggota', 'group' => 'Aset Lancar', 'type' => 'Aktiva'],
            ['code' => '1107', 'name' => 'Piutang Simpan Pinjam', 'group' => 'Aset Lancar', 'type' => 'Aktiva'],
            ['code' => '1108', 'name' => 'Persediaan Barang Dagang', 'group' => 'Aset Lancar', 'type' => 'Aktiva'],
            ['code' => '1109', 'name' => 'Perlengkapan', 'group' => 'Aset Lancar', 'type' => 'Aktiva'],
            ['code' => '1110', 'name' => 'Biaya Dibayar Dimuka', 'group' => 'Aset Lancar', 'type' => 'Aktiva'],
            ['code' => '1201', 'name' => 'Tanah', 'group' => 'Aset Tetap', 'type' => 'Aktiva'],
            ['code' => '1202', 'name' => 'Bangunan', 'group' => 'Aset Tetap', 'type' => 'Aktiva'],
            ['code' => '1203', 'name' => 'Kendaraan', 'group' => 'Aset Tetap', 'type' => 'Aktiva'],
            ['code' => '1204', 'name' => 'Peralatan Kantor', 'group' => 'Aset Tetap', 'type' => 'Aktiva'],
            ['code' => '1205', 'name' => 'Inventaris Kantor', 'group' => 'Aset Tetap', 'type' => 'Aktiva'],
            ['code' => '1291', 'name' => 'Akumulasi Penyusutan Bangunan', 'group' => 'Aset Tetap', 'type' => 'Kontra Aset'],
            ['code' => '1292', 'name' => 'Akumulasi Penyusutan Kendaraan', 'group' => 'Aset Tetap', 'type' => 'Kontra Aset'],
            ['code' => '1293', 'name' => 'Akumulasi Penyusutan Peralatan', 'group' => 'Aset Tetap', 'type' => 'Kontra Aset'],
            ['code' => '2101', 'name' => 'Hutang Usaha', 'group' => 'Kewajiban Jangka Pendek', 'type' => 'Kewajiban'],
            ['code' => '2102', 'name' => 'Hutang Dagang', 'group' => 'Kewajiban Jangka Pendek', 'type' => 'Kewajiban'],
            ['code' => '2103', 'name' => 'Hutang Bunga', 'group' => 'Kewajiban Jangka Pendek', 'type' => 'Kewajiban'],
            ['code' => '2104', 'name' => 'Hutang Biaya', 'group' => 'Kewajiban Jangka Pendek', 'type' => 'Kewajiban'],
            ['code' => '2105', 'name' => 'Hutang Pajak', 'group' => 'Kewajiban Jangka Pendek', 'type' => 'Kewajiban'],
            ['code' => '2201', 'name' => 'Hutang Bank', 'group' => 'Kewajiban Jangka Panjang', 'type' => 'Kewajiban'],
            ['code' => '2202', 'name' => 'Hutang Simpanan Berjangka Anggota', 'group' => 'Kewajiban Jangka Panjang', 'type' => 'Kewajiban'],
            ['code' => '2203', 'name' => 'Hutang Pembiayaan', 'group' => 'Kewajiban Jangka Panjang', 'type' => 'Kewajiban'],
            ['code' => '3101', 'name' => 'Simpanan Pokok', 'group' => 'Modal / Ekuitas', 'type' => 'Ekuitas'],
            ['code' => '3102', 'name' => 'Simpanan Wajib', 'group' => 'Modal / Ekuitas', 'type' => 'Ekuitas'],
            ['code' => '3103', 'name' => 'Simpanan Sukarela', 'group' => 'Modal / Ekuitas', 'type' => 'Ekuitas'],
            ['code' => '3104', 'name' => 'Dana Cadangan', 'group' => 'Modal / Ekuitas', 'type' => 'Ekuitas'],
            ['code' => '3105', 'name' => 'Modal Penyertaan', 'group' => 'Modal / Ekuitas', 'type' => 'Ekuitas'],
            ['code' => '3106', 'name' => 'SHU Ditahan', 'group' => 'Modal / Ekuitas', 'type' => 'Ekuitas'],
            ['code' => '3107', 'name' => 'SHU Tahun Berjalan', 'group' => 'Modal / Ekuitas', 'type' => 'Ekuitas'],
            ['code' => '4101', 'name' => 'Pendapatan Jasa Simpan Pinjam', 'group' => 'Pendapatan Usaha', 'type' => 'Pendapatan'],
            ['code' => '4102', 'name' => 'Pendapatan Penjualan Barang', 'group' => 'Pendapatan Usaha', 'type' => 'Pendapatan'],
            ['code' => '4103', 'name' => 'Pendapatan Jasa Unit Toko', 'group' => 'Pendapatan Usaha', 'type' => 'Pendapatan'],
            ['code' => '4104', 'name' => 'Pendapatan Unit Waserda', 'group' => 'Pendapatan Usaha', 'type' => 'Pendapatan'],
            ['code' => '4201', 'name' => 'Pendapatan Bunga Bank', 'group' => 'Pendapatan Lain-lain', 'type' => 'Pendapatan'],
            ['code' => '4202', 'name' => 'Pendapatan Sewa', 'group' => 'Pendapatan Lain-lain', 'type' => 'Pendapatan'],
            ['code' => '4203', 'name' => 'Pendapatan Administrasi', 'group' => 'Pendapatan Lain-lain', 'type' => 'Pendapatan'],
            ['code' => '5101', 'name' => 'Persediaan Awal', 'group' => 'HPP', 'type' => 'Beban'],
            ['code' => '5102', 'name' => 'Pembelian', 'group' => 'HPP', 'type' => 'Beban'],
            ['code' => '5103', 'name' => 'Retur Pembelian', 'group' => 'HPP', 'type' => 'Beban'],
            ['code' => '5104', 'name' => 'Potongan Pembelian', 'group' => 'HPP', 'type' => 'Beban'],
            ['code' => '5105', 'name' => 'Persediaan Akhir', 'group' => 'HPP', 'type' => 'Beban'],
            ['code' => '5106', 'name' => 'Harga Pokok Penjualan', 'group' => 'HPP', 'type' => 'Beban'],
            ['code' => '6101', 'name' => 'Beban Gaji dan Upah', 'group' => 'Beban Operasional', 'type' => 'Beban'],
            ['code' => '6102', 'name' => 'Beban Transportasi', 'group' => 'Beban Operasional', 'type' => 'Beban'],
            ['code' => '6103', 'name' => 'Beban ATK', 'group' => 'Beban Operasional', 'type' => 'Beban'],
            ['code' => '6104', 'name' => 'Beban Listrik dan Air', 'group' => 'Beban Operasional', 'type' => 'Beban'],
            ['code' => '6105', 'name' => 'Beban Sewa', 'group' => 'Beban Operasional', 'type' => 'Beban'],
            ['code' => '6106', 'name' => 'Beban Penyusutan', 'group' => 'Beban Operasional', 'type' => 'Beban'],
            ['code' => '6107', 'name' => 'Beban Rapat Anggota', 'group' => 'Beban Operasional', 'type' => 'Beban'],
            ['code' => '6108', 'name' => 'Beban Pembinaan Koperasi', 'group' => 'Beban Operasional', 'type' => 'Beban'],
            ['code' => '6201', 'name' => 'Beban Penyisihan Piutang Tak Tertagih', 'group' => 'Beban Simpan Pinjam', 'type' => 'Beban'],
            ['code' => '6202', 'name' => 'Beban Bunga Simpanan', 'group' => 'Beban Simpan Pinjam', 'type' => 'Beban'],
            ['code' => '7101', 'name' => 'Pendapatan Luar Usaha', 'group' => 'Pendapatan Non Operasional', 'type' => 'Pendapatan'],
            ['code' => '7201', 'name' => 'Beban Luar Usaha', 'group' => 'Beban Non Operasional', 'type' => 'Beban'],
            ['code' => '7301', 'name' => 'Keuntungan Penjualan Aset', 'group' => 'Non Operasional', 'type' => 'Pendapatan'],
            ['code' => '7302', 'name' => 'Kerugian Penjualan Aset', 'group' => 'Non Operasional', 'type' => 'Beban'],
            ['code' => '8101', 'name' => 'Dana Pendidikan', 'group' => 'Dana Khusus Koperasi', 'type' => 'Dana'],
            ['code' => '8102', 'name' => 'Dana Sosial', 'group' => 'Dana Khusus Koperasi', 'type' => 'Dana'],
            ['code' => '8103', 'name' => 'Dana Pengurus', 'group' => 'Dana Khusus Koperasi', 'type' => 'Dana'],
            ['code' => '8104', 'name' => 'Dana Karyawan', 'group' => 'Dana Khusus Koperasi', 'type' => 'Dana'],
        ];

        $types = [];
        $groups = [];

        foreach ($data as $item) {
            // Level 1: Tipe
            if (!isset($types[$item['type']])) {
                $typeAccount = Account::create([
                    'code' => strtoupper(substr($item['type'], 0, 3)) . '-' . (count($types) + 1),
                    'name' => $item['type'],
                    'type' => $item['type'],
                    'created_by' => $admin->id,
                ]);
                $types[$item['type']] = $typeAccount->id;
            }

            // Level 2: Kelompok
            $groupKey = $item['type'] . '|' . $item['group'];
            if (!isset($groups[$groupKey])) {
                $groupAccount = Account::create([
                    'parent_id' => $types[$item['type']],
                    'code' => strtoupper(substr($item['group'], 0, 3)) . '-' . (count($groups) + 1),
                    'name' => $item['group'],
                    'type' => $item['type'],
                    'group' => $item['group'],
                    'created_by' => $admin->id,
                ]);
                $groups[$groupKey] = $groupAccount->id;
            }

            // Level 3: Individual Account
            Account::create([
                'parent_id' => $groups[$groupKey],
                'code' => $item['code'],
                'name' => $item['name'],
                'type' => $item['type'],
                'group' => $item['group'],
                'created_by' => $admin->id,
            ]);
        }
    }
}
