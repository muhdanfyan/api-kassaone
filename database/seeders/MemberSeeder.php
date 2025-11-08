<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $memberRole = Role::where('name', 'Anggota')->first();

        $membersData = [
            [
                'full_name' => 'Ahmad Sudirman',
                'email' => 'ahmad@email.com',
                'phone_number' => '081234567890',
                'address' => 'Jl. Merdeka No. 123, Jakarta',
                'member_type' => 'Pendiri',
                'join_date' => '2020-01-15',
                'status' => 'Aktif',
            ],
            [
                'full_name' => 'Siti Rahayu',
                'email' => 'siti@email.com',
                'phone_number' => '081234567891',
                'address' => 'Jl. Kebangsaan No. 456, Jakarta',
                'member_type' => 'Biasa',
                'join_date' => '2021-03-20',
                'status' => 'Aktif',
            ],
            [
                'full_name' => 'Budi Santoso',
                'email' => 'budi@email.com',
                'phone_number' => '081234567892',
                'address' => 'Jl. Pancasila No. 789, Jakarta',
                'member_type' => 'Calon',
                'join_date' => '2024-01-10',
                'status' => 'Aktif',
            ],
        ];

        foreach ($membersData as $key => $data) {
            Member::create([
                'member_id_number' => 'MEMBER-' . ($key + 1),
                'full_name' => $data['full_name'],
                'username' => strtolower(explode(' ', $data['full_name'])[0]), // Use first name as username
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'address' => $data['address'],
                'phone_number' => $data['phone_number'],
                'join_date' => $data['join_date'],
                'member_type' => $data['member_type'],
                'status' => $data['status'],
                'role_id' => $memberRole->id,
            ]);
        }
    }
}
