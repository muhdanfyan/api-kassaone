<?php

namespace App\Services;

use App\Models\ShuDistribution;
use App\Models\ShuMemberAllocation;
use App\Models\ShuPercentageSetting;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SHUCalculationService
{
    /**
     * Hitung pembagian SHU untuk tahun tertentu dengan setting custom
     * 
     * @param int $fiscalYear
     * @param float $totalSHU
     * @param ShuPercentageSetting $setting
     * @return array
     */
    public function calculateDistribution(int $fiscalYear, float $totalSHU, ShuPercentageSetting $setting): array
    {
        // 1. Hitung cadangan
        $cadanganAmount = $totalSHU * ($setting->cadangan_percentage / 100);
        
        // 2. Hitung bagian anggota
        $anggotaAmount = $totalSHU * ($setting->anggota_percentage / 100);
        
        // 3. Breakdown jasa modal (dari bagian anggota)
        $jasaModalAmount = $anggotaAmount * ($setting->jasa_modal_percentage / 100);
        
        // 4. Breakdown jasa usaha (dari bagian anggota)
        $jasaUsahaAmount = $anggotaAmount * ($setting->jasa_usaha_percentage / 100);

        // 5. Hitung alokasi opsional
        $pengurusAmount = $totalSHU * ($setting->pengurus_percentage / 100);
        $karyawanAmount = $totalSHU * ($setting->karyawan_percentage / 100);
        $danaSosialAmount = $totalSHU * ($setting->dana_sosial_percentage / 100);

        Log::info('SHU Distribution Calculation', [
            'fiscal_year' => $fiscalYear,
            'setting_id' => $setting->id,
            'setting_name' => $setting->name,
            'total_shu' => $totalSHU,
            'cadangan' => $cadanganAmount,
            'anggota' => $anggotaAmount,
            'jasa_modal' => $jasaModalAmount,
            'jasa_usaha' => $jasaUsahaAmount,
            'pengurus' => $pengurusAmount,
            'karyawan' => $karyawanAmount,
            'dana_sosial' => $danaSosialAmount,
        ]);

        return [
            'total_shu' => round($totalSHU, 2),
            'cadangan_amount' => round($cadanganAmount, 2),
            'anggota_amount' => round($anggotaAmount, 2),
            'jasa_modal_amount' => round($jasaModalAmount, 2),
            'jasa_usaha_amount' => round($jasaUsahaAmount, 2),
            'pengurus_amount' => round($pengurusAmount, 2),
            'karyawan_amount' => round($karyawanAmount, 2),
            'dana_sosial_amount' => round($danaSosialAmount, 2),
            'percentages' => [
                'cadangan' => (float) $setting->cadangan_percentage,
                'anggota' => (float) $setting->anggota_percentage,
                'jasa_modal' => (float) $setting->jasa_modal_percentage,
                'jasa_usaha' => (float) $setting->jasa_usaha_percentage,
                'pengurus' => (float) $setting->pengurus_percentage,
                'karyawan' => (float) $setting->karyawan_percentage,
                'dana_sosial' => (float) $setting->dana_sosial_percentage,
            ]
        ];
    }

    /**
     * Hitung alokasi SHU per member berdasarkan jasa modal dan jasa usaha
     * 
     * @param ShuDistribution $distribution
     * @return array
     * @throws \Exception
     */
    public function calculateMemberAllocations(ShuDistribution $distribution): array
    {
        $fiscalYear = $distribution->fiscal_year;
        
        // 1. Hitung total simpanan semua member
        $totalSavings = SavingsAccount::sum('balance');
        
        // 2. Hitung total transaksi deposit tahun berjalan
        $totalTransactions = Transaction::where('transaction_type', 'deposit')
            ->whereYear('transaction_date', $fiscalYear)
            ->sum('amount');

        Log::info('Member Allocation Calculation Started', [
            'distribution_id' => $distribution->id,
            'fiscal_year' => $fiscalYear,
            'total_savings' => $totalSavings,
            'total_transactions' => $totalTransactions,
        ]);

        // Validasi: pastikan ada data
        if ($totalSavings == 0) {
            throw new \Exception('Tidak ada data simpanan member untuk tahun ' . $fiscalYear . '. Pastikan member memiliki simpanan.');
        }

        if ($totalTransactions == 0) {
            throw new \Exception('Tidak ada transaksi deposit untuk tahun ' . $fiscalYear . '. Pastikan ada transaksi yang tercatat.');
        }

        // 3. Loop semua member aktif yang punya simpanan
        $members = Member::whereHas('savingsAccounts')->with('savingsAccounts')->get();
        $allocations = [];

        foreach ($members as $member) {
            // Hitung total simpanan member
            $memberSavings = $member->savingsAccounts->sum('balance');
            
            // Hitung total transaksi deposit member tahun berjalan
            $memberTransactions = Transaction::where('member_id', $member->id)
                ->where('transaction_type', 'deposit')
                ->whereYear('transaction_date', $fiscalYear)
                ->sum('amount');

            // Proporsi jasa modal berdasarkan simpanan
            $jasaModalProportion = $totalSavings > 0 ? ($memberSavings / $totalSavings) : 0;
            $jasaModal = $jasaModalProportion * $distribution->jasa_modal_amount;
            
            // Proporsi jasa usaha berdasarkan transaksi
            $jasaUsahaProportion = $totalTransactions > 0 ? ($memberTransactions / $totalTransactions) : 0;
            $jasaUsaha = $jasaUsahaProportion * $distribution->jasa_usaha_amount;
            
            // Total alokasi untuk member ini
            $totalAllocation = $jasaModal + $jasaUsaha;

            // Skip member yang tidak mendapat alokasi (tidak ada simpanan & transaksi)
            if ($totalAllocation <= 0) {
                continue;
            }

            $allocations[] = [
                'member_id' => $member->id,
                'member_name' => $member->full_name,
                'member_number' => $member->member_number ?? 'N/A',
                'member_savings' => round($memberSavings, 2),
                'member_transactions' => round($memberTransactions, 2),
                'jasa_modal_proportion' => round($jasaModalProportion * 100, 4),
                'jasa_usaha_proportion' => round($jasaUsahaProportion * 100, 4),
                'jasa_modal_amount' => round($jasaModal, 2),
                'jasa_usaha_amount' => round($jasaUsaha, 2),
                'amount_allocated' => round($totalAllocation, 2),
            ];
        }

        // Sort by total allocation descending
        usort($allocations, function($a, $b) {
            return $b['amount_allocated'] <=> $a['amount_allocated'];
        });

        Log::info('Member Allocation Calculation Completed', [
            'total_members' => count($allocations),
            'total_allocated' => array_sum(array_column($allocations, 'amount_allocated')),
        ]);

        return $allocations;
    }

    /**
     * Simpan alokasi ke database
     * 
     * @param ShuDistribution $distribution
     * @param array $allocations
     * @return void
     * @throws \Exception
     */
    public function saveAllocations(ShuDistribution $distribution, array $allocations): void
    {
        DB::beginTransaction();
        
        try {
            // Hapus alokasi lama jika ada (untuk recalculation)
            $distribution->allocations()->delete();

            // Insert alokasi baru
            foreach ($allocations as $allocation) {
                ShuMemberAllocation::create([
                    'shu_distribution_id' => $distribution->id,
                    'member_id' => $allocation['member_id'],
                    'jasa_modal_amount' => $allocation['jasa_modal_amount'],
                    'jasa_usaha_amount' => $allocation['jasa_usaha_amount'],
                    'amount_allocated' => $allocation['amount_allocated'],
                    'is_paid_out' => false,
                    'payout_transaction_id' => null,
                    'paid_out_at' => null,
                ]);
            }

            DB::commit();

            Log::info('Allocations Saved Successfully', [
                'distribution_id' => $distribution->id,
                'members_count' => count($allocations),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to Save Allocations', [
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validasi apakah distribusi dapat dilakukan payout
     * 
     * @param ShuDistribution $distribution
     * @return array ['can_payout' => bool, 'message' => string]
     */
    public function validateForPayout(ShuDistribution $distribution): array
    {
        // Check 1: Status harus approved
        if ($distribution->status !== 'approved') {
            return [
                'can_payout' => false,
                'message' => 'SHU Distribution must be approved before payout. Current status: ' . $distribution->status
            ];
        }

        // Check 2: Harus ada alokasi
        if ($distribution->allocations->count() === 0) {
            return [
                'can_payout' => false,
                'message' => 'No allocations found. Please calculate allocations first.'
            ];
        }

        // Check 3: Tidak boleh semua sudah dibayar
        $unpaidCount = $distribution->allocations()->where('is_paid_out', false)->count();
        if ($unpaidCount === 0) {
            return [
                'can_payout' => false,
                'message' => 'All allocations have been paid out already.'
            ];
        }

        return [
            'can_payout' => true,
            'message' => 'Ready for payout',
            'unpaid_count' => $unpaidCount,
            'unpaid_amount' => $distribution->total_unpaid,
        ];
    }

    /**
     * Get summary statistics untuk SHU Distribution
     * 
     * @param ShuDistribution $distribution
     * @return array
     */
    public function getDistributionSummary(ShuDistribution $distribution): array
    {
        $distribution->load('allocations.member');

        return [
            'distribution_id' => $distribution->id,
            'fiscal_year' => $distribution->fiscal_year,
            'status' => $distribution->status,
            'total_shu' => $distribution->total_shu_amount,
            'cadangan' => $distribution->cadangan_amount,
            'jasa_modal' => $distribution->jasa_modal_amount,
            'jasa_usaha' => $distribution->jasa_usaha_amount,
            'distribution_date' => $distribution->distribution_date?->format('Y-m-d'),
            'members_count' => $distribution->total_members,
            'paid_members' => $distribution->paid_members_count,
            'unpaid_members' => $distribution->total_members - $distribution->paid_members_count,
            'total_allocated' => $distribution->allocations->sum('amount_allocated'),
            'total_paid_out' => $distribution->total_paid_out,
            'total_unpaid' => $distribution->total_unpaid,
            'payment_progress' => $distribution->payment_progress,
            'approved_at' => $distribution->approved_at?->format('Y-m-d H:i:s'),
            'approved_by' => $distribution->approver?->full_name,
        ];
    }
}
