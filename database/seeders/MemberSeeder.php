<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
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
                'name' => 'Ahmad Sudirman',
                'email' => 'ahmad@email.com',
                'phone_number' => '081234567890',
                'address' => 'Jl. Merdeka No. 123, Jakarta',
                'member_type' => 'Pendiri',
                'date_joined' => '2020-01-15',
                'status' => 'Aktif',
            ],
            [
                'name' => 'Siti Rahayu',
                'email' => 'siti@email.com',
                'phone_number' => '081234567891',
                'address' => 'Jl. Kebangsaan No. 456, Jakarta',
                'member_type' => 'Biasa',
                'date_joined' => '2021-03-20',
                'status' => 'Aktif',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@email.com',
                'phone_number' => '081234567892',
                'address' => 'Jl. Pancasila No. 789, Jakarta',
                'member_type' => 'Calon',
                'date_joined' => '2024-01-10',
                'status' => 'Aktif',
            ],
        ];

        foreach ($membersData as $key => $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role_id' => $memberRole->id,
            ]);

            Member::create([
                'user_id' => $user->id,
                'member_id_number' => 'MEMBER-' . ($key + 1),
                'name' => $data['name'],
                'username' => $data['email'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'address' => $data['address'],
                'phone_number' => $data['phone_number'],
                'date_joined' => $data['date_joined'],
                'member_type' => $data['member_type'],
                'status' => $data['status'],
                'role_id' => $memberRole->id,
            ]);
        }
    }
}
