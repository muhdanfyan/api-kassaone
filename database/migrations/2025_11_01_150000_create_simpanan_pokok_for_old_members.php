<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates Simpanan Pokok for old verified members
     * who don't have savings accounts yet (created before auto-create feature)
     */
    public function up(): void
    {
        // Find all verified members who don't have Simpanan Pokok yet
        $verifiedMembers = Member::where('verification_status', Member::VERIFICATION_VERIFIED)
            ->whereDoesntHave('savingsAccounts', function($query) {
                $query->where('account_type', 'pokok');
            })
            ->get();

        foreach ($verifiedMembers as $member) {
            DB::beginTransaction();
            try {
                // Create Simpanan Pokok account
                $simpananPokok = SavingsAccount::create([
                    'member_id' => $member->id,
                    'account_type' => 'pokok',
                    'balance' => $member->payment_amount ?? 1000000,
                ]);

                // Create transaction record
                Transaction::create([
                    'savings_account_id' => $simpananPokok->id,
                    'member_id' => $member->id,
                    'transaction_type' => 'deposit',
                    'amount' => $member->payment_amount ?? 1000000,
                    'description' => 'Simpanan Pokok (Migrasi Data Lama)',
                    'transaction_date' => $member->payment_verified_at ?? $member->join_date ?? now(),
                ]);

                DB::commit();
                
                echo "✅ Created Simpanan Pokok for: {$member->name} (MEM-{$member->member_id_number})\n";
            } catch (\Exception $e) {
                DB::rollBack();
                echo "❌ Failed for: {$member->name} - {$e->getMessage()}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Simpanan Pokok created by this migration
        // Identified by description "Simpanan Pokok (Migrasi Data Lama)"
        $transactions = Transaction::where('description', 'Simpanan Pokok (Migrasi Data Lama)')->get();
        
        foreach ($transactions as $transaction) {
            DB::beginTransaction();
            try {
                $savingsAccount = SavingsAccount::find($transaction->savings_account_id);
                
                // Delete transaction
                $transaction->delete();
                
                // Delete savings account if it only has this one transaction
                if ($savingsAccount && $savingsAccount->transactions()->count() === 0) {
                    $savingsAccount->delete();
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        }
    }
};
