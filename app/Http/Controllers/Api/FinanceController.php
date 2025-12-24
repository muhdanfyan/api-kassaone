<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use App\Models\ShuDistribution;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    /**
     * Get finance summary
     */
    public function summary()
    {
        // Total simpanan by type
        $simpananPokok = SavingsAccount::where('account_type', 'pokok')->sum('balance');
        $simpananWajib = SavingsAccount::where('account_type', 'wajib')->sum('balance');
        $simpananSukarela = SavingsAccount::where('account_type', 'sukarela')->sum('balance');
        $totalSimpanan = SavingsAccount::sum('balance');

        // Current month transactions
        $currentMonth = Carbon::now();
        $depositsThisMonth = Transaction::where('transaction_type', 'deposit')
            ->whereYear('transaction_date', $currentMonth->year)
            ->whereMonth('transaction_date', $currentMonth->month)
            ->sum('amount');
        
        $withdrawalsThisMonth = Transaction::where('transaction_type', 'withdrawal')
            ->whereYear('transaction_date', $currentMonth->year)
            ->whereMonth('transaction_date', $currentMonth->month)
            ->sum('amount');

        // SHU info
        $currentYearShu = ShuDistribution::where('fiscal_year', $currentMonth->year)
            ->sum('total_shu_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_simpanan' => (float) $totalSimpanan,
                'simpanan_pokok' => (float) $simpananPokok,
                'simpanan_wajib' => (float) $simpananWajib,
                'simpanan_sukarela' => (float) $simpananSukarela,
                'deposits_this_month' => (float) $depositsThisMonth,
                'withdrawals_this_month' => (float) $withdrawalsThisMonth,
                'net_this_month' => (float) ($depositsThisMonth - $withdrawalsThisMonth),
                'shu_current_year' => (float) $currentYearShu,
            ],
        ]);
    }

    /**
     * Get monthly finance data for charts
     */
    public function monthly(Request $request)
    {
        $months = $request->get('months', 6);
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->translatedFormat('M Y');
            
            // Get deposits for this month
            $deposits = Transaction::where('transaction_type', 'deposit')
                ->whereYear('transaction_date', $date->year)
                ->whereMonth('transaction_date', $date->month)
                ->sum('amount');
            
            // Get withdrawals for this month
            $withdrawals = Transaction::where('transaction_type', 'withdrawal')
                ->whereYear('transaction_date', $date->year)
                ->whereMonth('transaction_date', $date->month)
                ->sum('amount');

            // Get balance at end of month (approximate - sum all transactions up to end of month)
            $endOfMonth = $date->copy()->endOfMonth();
            $balance = SavingsAccount::sum('balance'); // Current balance as approximation
            
            $data[] = [
                'month' => $monthName,
                'deposits' => (float) $deposits,
                'withdrawals' => (float) $withdrawals,
                'net' => (float) ($deposits - $withdrawals),
                'balance' => (float) $balance,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get transaction summary by type
     */
    public function transactionSummary(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $summary = Transaction::select('transaction_type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('transaction_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $summary,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }
}
