<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use App\Models\Meeting;
use App\Models\ShuDistribution;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        // Total anggota yang sudah verified
        $totalAnggota = Member::where('verification_status', Member::VERIFICATION_VERIFIED)->count();

        // Anggota baru bulan ini (yang created_at di bulan ini)
        $anggotaBaruBulanIni = Member::whereMonth('created_at', Carbon::now()->month)
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->count();

        // Total simpanan dari semua savings_accounts
        $totalSimpanan = SavingsAccount::sum('balance');

        // Assuming 'pembiayaan' (financing) is a type of transaction or a separate model
        // For now, let's assume it's a transaction type for simplicity
        $totalPembiayaan = Transaction::where('transaction_type', 'pembiayaan')->sum('amount');

        // SHU tahun berjalan
        $shuTahunBerjalan = ShuDistribution::where('fiscal_year', Carbon::now()->year)->sum('total_shu_amount');

        // Transaksi bulan ini - SEMUA transaksi (deposit & withdrawal dari pokok, wajib, sukarela)
        // Hitung dari tabel transactions berdasarkan transaction_date bulan ini
        $transaksiBulanIni = Transaction::whereMonth('transaction_date', Carbon::now()->month)
                                        ->whereYear('transaction_date', Carbon::now()->year)
                                        ->whereIn('transaction_type', ['deposit', 'withdrawal'])
                                        ->count();

        // Rapat yang terjadwal (meeting_date >= hari ini)
        $rapatTerjadwal = Meeting::where('meeting_date', '>=', Carbon::now())->count();

        return response()->json([
            'totalAnggota' => $totalAnggota,
            'anggotaBaruBulanIni' => $anggotaBaruBulanIni,
            'totalSimpanan' => $totalSimpanan,
            'totalPembiayaan' => $totalPembiayaan,
            'shuTahunBerjalan' => $shuTahunBerjalan ?? 0,
            'transaksiBulanIni' => $transaksiBulanIni,
            'rapatTerjadwal' => $rapatTerjadwal,
        ]);
    }

    /**
     * Get membership growth data for chart
     */
    public function membershipGrowth(Request $request)
    {
        $months = $request->get('months', 6);
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->translatedFormat('M Y');
            
            $count = Member::whereYear('created_at', $date->year)
                          ->whereMonth('created_at', $date->month)
                          ->count();
            
            $data[] = [
                'month' => $monthName,
                'count' => $count,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get savings distribution by account type
     */
    public function savingsDistribution()
    {
        $distribution = SavingsAccount::select('account_type', DB::raw('SUM(balance) as total'))
            ->groupBy('account_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->account_type,
                    'total' => (float) $item->total,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $distribution,
        ]);
    }

    /**
     * Get monthly transactions data for chart
     */
    public function monthlyTransactions(Request $request)
    {
        $months = $request->get('months', 6);
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->translatedFormat('M Y');
            
            $deposits = Transaction::where('transaction_type', 'deposit')
                ->whereYear('transaction_date', $date->year)
                ->whereMonth('transaction_date', $date->month)
                ->sum('amount');
            
            $withdrawals = Transaction::where('transaction_type', 'withdrawal')
                ->whereYear('transaction_date', $date->year)
                ->whereMonth('transaction_date', $date->month)
                ->sum('amount');
            
            $data[] = [
                'month' => $monthName,
                'deposits' => (float) $deposits,
                'withdrawals' => (float) $withdrawals,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get SHU distribution summary
     */
    public function shuDistribution()
    {
        $currentYear = Carbon::now()->year;
        
        $distributions = ShuDistribution::select('fiscal_year', 'total_shu_amount', 'status')
            ->orderBy('fiscal_year', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $distributions,
        ]);
    }

    /**
     * Get recent activities
     */
    public function recentActivities(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        // Get recent transactions
        $transactions = Transaction::with(['member:id,full_name', 'savingsAccount:id,account_type'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'type' => 'transaction',
                    'action' => $tx->transaction_type,
                    'description' => ($tx->transaction_type === 'deposit' ? 'Setoran' : 'Penarikan') . 
                                   ' ' . ($tx->savingsAccount->account_type ?? 'Simpanan'),
                    'amount' => (float) $tx->amount,
                    'member_name' => $tx->member->full_name ?? 'Unknown',
                    'created_at' => $tx->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Get upcoming meetings
     */
    public function upcomingMeetings(Request $request)
    {
        $limit = $request->get('limit', 5);
        
        $meetings = Meeting::where('meeting_date', '>=', Carbon::now())
            ->orderBy('meeting_date', 'asc')
            ->limit($limit)
            ->get(['id', 'title', 'meeting_date', 'location', 'type', 'status']);

        return response()->json([
            'success' => true,
            'data' => $meetings,
        ]);
    }
}
