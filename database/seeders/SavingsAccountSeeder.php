<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\SavingsAccount;

class SavingsAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = Member::all();

        foreach ($members as $member) {
            // Simpanan Pokok
            SavingsAccount::create([
                'member_id' => $member->id,
                'account_type' => 'pokok',
                'balance' => 500000, // Initial balance for Simpanan Pokok
            ]);

            // Simpanan Wajib
            SavingsAccount::create([
                'member_id' => $member->id,
                'account_type' => 'wajib',
                'balance' => 25000, // Initial balance for Simpanan Wajib
            ]);

            // Simpanan Sukarela
            SavingsAccount::create([
                'member_id' => $member->id,
                'account_type' => 'sukarela',
                'balance' => 0, // Initial balance for Simpanan Sukarela
            ]);
        }
    }
}
