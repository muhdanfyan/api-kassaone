<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Role::create(['name' => 'Admin', 'description' => 'Akses penuh ke semua fitur manajemen']);
        \App\Models\Role::create(['name' => 'Pengurus', 'description' => 'Akses manajemen sebagai pengurus']);
        \App\Models\Role::create(['name' => 'Pengawas', 'description' => 'Akses untuk pengawasan']);
        \App\Models\Role::create(['name' => 'Anggota', 'description' => 'Akses dasar ke data pribadi dan simpanan']);
    }
}
