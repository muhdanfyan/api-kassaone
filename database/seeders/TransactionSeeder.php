<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\Transaction;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = Member::all();

        foreach ($members as $member) {
            $savingsPokok = SavingsAccount::where('member_id', $member->id)->where('account_type', 'pokok')->first();
            $savingsWajib = SavingsAccount::where('member_id', $member->id)->where('account_type', 'wajib')->first();
            $savingsSukarela = SavingsAccount::where('member_id', $member->id)->where('account_type', 'sukarela')->first();

            if ($savingsPokok) {
                Transaction::create([
                    'savings_account_id' => $savingsPokok->id,
                    'member_id' => $member->id,
                    'transaction_type' => 'deposit',
                    'amount' => 500000,
                    'description' => 'Setoran Simpanan Pokok',
                    'transaction_date' => now()->subMonths(rand(1, 24)),
                ]);
            }

            if ($savingsWajib) {
                // Add a few monthly wajib savings transactions
                for ($i = 0; $i < rand(3, 12); $i++) {
                    Transaction::create([
                        'savings_account_id' => $savingsWajib->id,
                        'member_id' => $member->id,
                        'transaction_type' => 'deposit',
                        'amount' => 25000,
                        'description' => 'Setoran Simpanan Wajib Bulanan',
                        'transaction_date' => now()->subMonths($i)->day(rand(1, 28)),
                    ]);
                }
            }

            if ($savingsSukarela) {
                // Add some voluntary savings transactions
                for ($i = 0; $i < rand(0, 5); $i++) {
                    Transaction::create([
                        'savings_account_id' => $savingsSukarela->id,
                        'member_id' => $member->id,
                        'transaction_type' => 'deposit',
                        'amount' => rand(1, 10) * 100000, // Random amount between 100k and 1M
                        'description' => 'Setoran Simpanan Sukarela',
                        'transaction_date' => now()->subMonths(rand(1, 12))->day(rand(1, 28)),
                    ]);
                }
            }
        }
    }
}
