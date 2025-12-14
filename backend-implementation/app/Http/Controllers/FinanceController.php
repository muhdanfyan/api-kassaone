<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Finance Controller
 * Handles all finance dashboard operations
 * 
 * @access Admin, Bendahara only (enforced by middleware)
 */
class FinanceController extends Controller
{
    /**
     * GET /api/finance/summary
     * Get finance summary (total kas, pemasukan, pengeluaran, laba/rugi bulan ini)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSummary()
    {
        try {
            // Total Kas = Total Pemasukan - Total Pengeluaran (All time)
            $totalPemasukan = DB::table('savings_transactions')
                ->where('transaction_type', 'deposit')
                ->where('status', 'approved')
                ->sum('amount');

            $totalPengeluaran = DB::table('expenses')
                ->sum('amount');

            $totalKas = $totalPemasukan - $totalPengeluaran;

            // Pemasukan Bulan Ini
            $pemasukanBulanIni = DB::table('savings_transactions')
                ->where('transaction_type', 'deposit')
                ->where('status', 'approved')
                ->whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)
                ->sum('amount');

            // Pengeluaran Bulan Ini
            $pengeluaranBulanIni = DB::table('expenses')
                ->whereMonth('expense_date', now()->month)
                ->whereYear('expense_date', now()->year)
                ->sum('amount');

            // Laba/Rugi Bulan Ini
            $labaRugiBulanIni = $pemasukanBulanIni - $pengeluaranBulanIni;

            return response()->json([
                'success' => true,
                'message' => 'Finance summary retrieved successfully',
                'data' => [
                    'total_kas' => (float) $totalKas,
                    'pemasukan_bulan_ini' => (float) $pemasukanBulanIni,
                    'pengeluaran_bulan_ini' => (float) $pengeluaranBulanIni,
                    'laba_rugi_bulan_ini' => (float) $labaRugiBulanIni,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve finance summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/finance/monthly?months=6
     * Get monthly finance data for chart
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyData(Request $request)
    {
        try {
            $months = $request->input('months', 6);
            
            // Validate months parameter
            if ($months < 1 || $months > 24) {
                return response()->json([
                    'success' => false,
                    'message' => 'Months parameter must be between 1 and 24'
                ], 400);
            }

            $data = [];
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];

            for ($i = $months - 1; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthNum = $date->month;
                $year = $date->year;

                // Pemasukan (from savings_transactions)
                $pemasukan = DB::table('savings_transactions')
                    ->where('transaction_type', 'deposit')
                    ->where('status', 'approved')
                    ->whereMonth('transaction_date', $monthNum)
                    ->whereYear('transaction_date', $year)
                    ->sum('amount');

                // Pengeluaran (from expenses)
                $pengeluaran = DB::table('expenses')
                    ->whereMonth('expense_date', $monthNum)
                    ->whereYear('expense_date', $year)
                    ->sum('amount');

                $data[] = [
                    'month' => $monthNames[$monthNum],
                    'year' => $year,
                    'pemasukan' => (float) $pemasukan,
                    'pengeluaran' => (float) $pengeluaran,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Monthly finance data retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve monthly finance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/finance/transactions/recent?limit=10
     * Get recent transactions (combined savings and expenses)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentTransactions(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            
            // Validate limit parameter
            if ($limit < 1 || $limit > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limit parameter must be between 1 and 100'
                ], 400);
            }

            // Get income transactions (savings)
            $incomeTransactions = DB::table('savings_transactions as st')
                ->join('members as m', 'st.member_id', '=', 'm.id')
                ->join('savings_types as sty', 'st.savings_type_id', '=', 'sty.id')
                ->select(
                    'st.id',
                    'st.transaction_date',
                    DB::raw("'pemasukan' as type"),
                    'st.amount',
                    DB::raw("CONCAT(sty.name, ' - ', m.full_name) as description"),
                    'm.full_name as member_name',
                    'sty.name as account_name',
                    DB::raw("'savings' as source"),
                    'm.full_name as created_by',
                    'st.created_at'
                )
                ->where('st.transaction_type', 'deposit')
                ->where('st.status', 'approved');

            // Get expense transactions
            $expenseTransactions = DB::table('expenses as e')
                ->join('accounts as a', 'e.account_id', '=', 'a.id')
                ->leftJoin('users as u', 'e.created_by', '=', 'u.id')
                ->select(
                    'e.id',
                    'e.expense_date as transaction_date',
                    DB::raw("'pengeluaran' as type"),
                    'e.amount',
                    'e.description',
                    DB::raw("NULL as member_name"),
                    'a.name as account_name',
                    DB::raw("'expense' as source"),
                    DB::raw("COALESCE(u.full_name, 'System') as created_by"),
                    'e.created_at'
                );

            // Combine and get recent transactions
            $transactions = $incomeTransactions
                ->union($expenseTransactions)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Format the data
            $formattedTransactions = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_date' => $transaction->transaction_date,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'description' => $transaction->description,
                    'member_name' => $transaction->member_name,
                    'account_name' => $transaction->account_name,
                    'source' => $transaction->source,
                    'created_by' => $transaction->created_by,
                    'created_at' => $transaction->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Recent transactions retrieved successfully',
                'data' => $formattedTransactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/finance/breakdown?start_date=2024-01-01&end_date=2024-12-31
     * Get detailed breakdown by savings type and expense category
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBreakdown(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            // Pemasukan breakdown by savings type
            $pemasukanByType = DB::table('savings_transactions as st')
                ->join('savings_types as sty', 'st.savings_type_id', '=', 'sty.id')
                ->select(
                    'sty.account_type',
                    DB::raw('SUM(st.amount) as total')
                )
                ->where('st.transaction_type', 'deposit')
                ->where('st.status', 'approved')
                ->whereBetween('st.transaction_date', [$startDate, $endDate])
                ->groupBy('sty.account_type')
                ->get()
                ->map(function ($item) {
                    return [
                        'account_type' => $item->account_type,
                        'total' => (float) $item->total,
                    ];
                });

            $totalPemasukan = $pemasukanByType->sum('total');

            // Pengeluaran breakdown by account
            $pengeluaranByAccount = DB::table('expenses as e')
                ->join('accounts as a', 'e.account_id', '=', 'a.id')
                ->select(
                    'a.id as account_id',
                    'a.name as account_name',
                    DB::raw('SUM(e.amount) as total')
                )
                ->whereBetween('e.expense_date', [$startDate, $endDate])
                ->groupBy('a.id', 'a.name')
                ->get()
                ->map(function ($item) {
                    return [
                        'account_id' => $item->account_id,
                        'account_name' => $item->account_name,
                        'total' => (float) $item->total,
                    ];
                });

            $totalPengeluaran = $pengeluaranByAccount->sum('total');

            // Total laba/rugi
            $totalLabaRugi = $totalPemasukan - $totalPengeluaran;

            return response()->json([
                'success' => true,
                'message' => 'Finance breakdown retrieved successfully',
                'data' => [
                    'pemasukan' => [
                        'total' => (float) $totalPemasukan,
                        'breakdown_by_type' => $pemasukanByType,
                    ],
                    'pengeluaran' => [
                        'total' => (float) $totalPengeluaran,
                        'breakdown_by_account' => $pengeluaranByAccount,
                    ],
                    'total_laba_rugi' => (float) $totalLabaRugi,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve finance breakdown',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
