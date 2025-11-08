<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $pengurusRole = Role::firstOrCreate(
            ['name' => 'Pengurus'],
            ['description' => 'Pengurus Koperasi']
        );

        $pengawasRole = Role::firstOrCreate(
            ['name' => 'Pengawas'],
            ['description' => 'Pengawas Koperasi']
        );

        $karyawanRole = Role::firstOrCreate(
            ['name' => 'Karyawan'],
            ['description' => 'Karyawan Koperasi']
        );

        // Update existing members or create sample organization members
        // Pengurus
        Member::updateOrCreate(
            ['member_id_number' => 'KOP-001'],
            [
                'full_name' => 'Ahmad Subarjo',
                'username' => 'ahmad.subarjo',
                'email' => 'ahmad.subarjo@kassa.coop',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567001',
                'address' => 'Jakarta',
                'nik' => '3171010101010001',
                'join_date' => '2020-01-01',
                'member_type' => Member::MEMBER_TYPE_PENDIRI,
                'status' => 'Aktif',
                'role_id' => $pengurusRole->id,
                'position' => 'Ketua',
            ]
        );

        Member::updateOrCreate(
            ['member_id_number' => 'KOP-002'],
            [
                'full_name' => 'Siti Aminah',
                'username' => 'siti.aminah',
                'email' => 'siti.aminah@kassa.coop',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567002',
                'address' => 'Jakarta',
                'nik' => '3171010101010002',
                'join_date' => '2020-01-01',
                'member_type' => Member::MEMBER_TYPE_PENDIRI,
                'status' => 'Aktif',
                'role_id' => $pengurusRole->id,
                'position' => 'Sekretaris',
            ]
        );

        Member::updateOrCreate(
            ['member_id_number' => 'KOP-003'],
            [
                'full_name' => 'Budi Santoso',
                'username' => 'budi.santoso',
                'email' => 'budi.santoso@kassa.coop',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567003',
                'address' => 'Jakarta',
                'nik' => '3171010101010003',
                'join_date' => '2020-01-01',
                'member_type' => Member::MEMBER_TYPE_PENDIRI,
                'status' => 'Aktif',
                'role_id' => $pengurusRole->id,
                'position' => 'Bendahara',
            ]
        );

        // Pengawas
        Member::updateOrCreate(
            ['member_id_number' => 'KOP-004'],
            [
                'full_name' => 'Dewi Lestari',
                'username' => 'dewi.lestari',
                'email' => 'dewi.lestari@kassa.coop',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567004',
                'address' => 'Jakarta',
                'nik' => '3171010101010004',
                'join_date' => '2020-01-01',
                'member_type' => Member::MEMBER_TYPE_PENDIRI,
                'status' => 'Aktif',
                'role_id' => $pengawasRole->id,
                'position' => 'Ketua Pengawas',
            ]
        );

        Member::updateOrCreate(
            ['member_id_number' => 'KOP-005'],
            [
                'full_name' => 'Eko Prasetyo',
                'username' => 'eko.prasetyo',
                'email' => 'eko.prasetyo@kassa.coop',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567005',
                'address' => 'Jakarta',
                'nik' => '3171010101010005',
                'join_date' => '2020-01-01',
                'member_type' => Member::MEMBER_TYPE_PENDIRI,
                'status' => 'Aktif',
                'role_id' => $pengawasRole->id,
                'position' => 'Anggota Pengawas',
            ]
        );

        // Karyawan
        Member::updateOrCreate(
            ['member_id_number' => 'KOP-006'],
            [
                'full_name' => 'Rina Marlina',
                'username' => 'rina.marlina',
                'email' => 'rina.marlina@kassa.coop',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567006',
                'address' => 'Jakarta',
                'nik' => '3171010101010006',
                'join_date' => '2020-06-01',
                'member_type' => Member::MEMBER_TYPE_BIASA,
                'status' => 'Aktif',
                'role_id' => $karyawanRole->id,
                'position' => 'Manajer Operasional',
            ]
        );

        Member::updateOrCreate(
            ['member_id_number' => 'KOP-007'],
            [
                'full_name' => 'Agus Salim',
                'username' => 'agus.salim',
                'email' => 'agus.salim@kassa.coop',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567007',
                'address' => 'Jakarta',
                'nik' => '3171010101010007',
                'join_date' => '2020-06-01',
                'member_type' => Member::MEMBER_TYPE_BIASA,
                'status' => 'Aktif',
                'role_id' => $karyawanRole->id,
                'position' => 'Staf Keuangan',
            ]
        );

        Member::updateOrCreate(
            ['member_id_number' => 'KOP-008'],
            [
                'full_name' => 'Putri Ayu',
                'username' => 'putri.ayu',
                'email' => 'putri.ayu@kassa.coop',
                'password' => Hash::make('password123'),
                'phone_number' => '081234567008',
                'address' => 'Jakarta',
                'nik' => '3171010101010008',
                'join_date' => '2021-01-01',
                'member_type' => Member::MEMBER_TYPE_BIASA,
                'status' => 'Aktif',
                'role_id' => $karyawanRole->id,
                'position' => 'Staf Administrasi',
            ]
        );

        $this->command->info('Organization structure seeded successfully!');
    }
}
