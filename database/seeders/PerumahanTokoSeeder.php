<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PerumahanTokoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏠 Seeding Perumahan & Toko roles and users...');

        // Create Roles
        $perumahanRole = Role::firstOrCreate(
            ['name' => 'Perumahan'],
            ['description' => 'Pengelola Estate Management System Perumahan Tarbiyah Garden']
        );

        $tokoRole = Role::firstOrCreate(
            ['name' => 'Toko'],
            ['description' => 'Pengelola Point of Sale (POS) untuk toko koperasi']
        );

        // Create User for Perumahan
        User::firstOrCreate(
            ['username' => 'perumahan'],
            [
                'name' => 'Pengelola Perumahan',
                'email' => 'perumahan@kassaone.id',
                'password' => Hash::make('perumahan123'),
                'role_id' => $perumahanRole->id,
                'status' => 'active',
            ]
        );

        // Create User for Toko
        User::firstOrCreate(
            ['username' => 'toko'],
            [
                'name' => 'Kasir Toko Koperasi',
                'email' => 'toko@kassaone.id',
                'password' => Hash::make('toko123'),
                'role_id' => $tokoRole->id,
                'status' => 'active',
            ]
        );

        $this->command->info('✅ Seeding completed!');
        $this->command->newLine();
        $this->command->info('Login Credentials:');
        $this->command->line('  Perumahan → perumahan / perumahan123');
        $this->command->line('  Toko → toko / toko123');
    }
}
