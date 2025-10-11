<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
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

        // Create Admin User and Member
        $adminUser = User::create([
            'name' => 'Admin KASSA',
            'email' => 'admin@kassa.co.id',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
        ]);

        Member::create([
            'user_id' => $adminUser->id,
            'member_id_number' => 'ADMIN-1',
            'name' => 'Admin KASSA',
            'username' => 'admin',
            'email' => 'admin@kassa.co.id',
            'password' => Hash::make('password123'),
            'date_joined' => now(),
            'status' => 'Aktif',
            'role_id' => $adminRole->id,
        ]);

        // Create Ketua User and Member
        $ketuaUser = User::create([
            'name' => 'Ketua KASSA',
            'email' => 'ketua@kassa.com',
            'password' => Hash::make('password'),
            'role_id' => $pengurusRole->id,
        ]);

        Member::create([
            'user_id' => $ketuaUser->id,
            'member_id_number' => 'PENGURUS-1',
            'name' => 'Ketua KASSA',
            'username' => 'ketua',
            'email' => 'ketua@kassa.com',
            'password' => Hash::make('password'),
            'date_joined' => now(),
            'status' => 'Aktif',
            'role_id' => $pengurusRole->id,
        ]);

        // Create Pengawas User and Member
        $pengawasUser = User::create([
            'name' => 'Pengawas KASSA',
            'email' => 'pengawas@kassa.com',
            'password' => Hash::make('password'),
            'role_id' => $pengawasRole->id,
        ]);

        Member::create([
            'user_id' => $pengawasUser->id,
            'member_id_number' => 'PENGAWAS-1',
            'name' => 'Pengawas KASSA',
            'username' => 'pengawas',
            'email' => 'pengawas@kassa.com',
            'password' => Hash::make('password'),
            'date_joined' => now(),
            'status' => 'Aktif',
            'role_id' => $pengawasRole->id,
        ]);
    }
}
