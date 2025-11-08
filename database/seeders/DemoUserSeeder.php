<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $pengurusRole = Role::where('name', 'Pengurus')->first();
        $pengawasRole = Role::where('name', 'Pengawas')->first();

        // Create Admin Member (with authentication)
        Member::create([
            'member_id_number' => 'ADMIN-1',
            'full_name' => 'Admin KASSA',
            'username' => 'admin',
            'email' => 'admin@kassa.co.id',
            'password' => Hash::make('password'),
            'join_date' => now(),
            'member_type' => 'Pendiri',
            'status' => 'Aktif',
            'role_id' => $adminRole->id,
        ]);

        // Create Ketua Member (with authentication)
        Member::create([
            'member_id_number' => 'PENGURUS-1',
            'full_name' => 'Ketua KASSA',
            'username' => 'ketua',
            'email' => 'ketua@kassa.com',
            'password' => Hash::make('password'),
            'join_date' => now(),
            'member_type' => 'Pendiri',
            'status' => 'Aktif',
            'role_id' => $pengurusRole->id,
        ]);

        // Create Pengawas Member (with authentication)
        Member::create([
            'member_id_number' => 'PENGAWAS-1',
            'full_name' => 'Pengawas KASSA',
            'username' => 'pengawas',
            'email' => 'pengawas@kassa.com',
            'password' => Hash::make('password'),
            'join_date' => now(),
            'member_type' => 'Pendiri',
            'status' => 'Aktif',
            'role_id' => $pengawasRole->id,
        ]);
    }
}
