<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FinanceController extends Controller
{
    /**
     * Get finance summary (total kas, pemasukan/pengeluaran bulan ini, laba/rugi)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSummary()
    {
        try {
            // Total Kas = Total Pemasukan - Total Pengeluaran (All time)
            $totalPemasukan = DB::table('savings_accounts')
                ->sum('balance');

            $totalPengeluaran = DB::table('expenses')
                ->sum('amount');

            $totalKas = $totalPemasukan - $totalPengeluaran;

            // Pemasukan Bulan Ini (from savings transactions - deposits only)
            $pemasukanBulanIni = DB::table('transactions')
                ->where('transaction_type', 'deposit')
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
            Log::error('Get finance summary error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve finance summary'
            ], 500);
        }
    }

    /**
     * Get monthly finance data for chart (last N months)
     * 
     * @param \Illuminate\Http\Request $request
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
            
            // Indonesian month names (full names)
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];

            for ($i = $months - 1; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthNum = $date->month;
                $year = $date->year;

                // Pemasukan (from transactions - deposits only)
                $pemasukan = DB::table('transactions')
                    ->where('transaction_type', 'deposit')
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
            Log::error('Get monthly finance data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve monthly data'
            ], 500);
        }
    }

    /**
     * Get recent transactions (combined from savings and expenses)
     * 
     * @param \Illuminate\Http\Request $request
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

            // Get income transactions (from transactions table)
            $incomeTransactions = DB::table('transactions as t')
                ->join('members as m', 't.member_id', '=', 'm.id')
                ->join('savings_accounts as sa', 't.savings_account_id', '=', 'sa.id')
                ->select(
                    't.id',
                    't.transaction_date',
                    DB::raw("'pemasukan' as type"),
                    't.amount',
                    DB::raw("CONCAT(
                        CASE sa.account_type 
                            WHEN 'pokok' THEN 'Simpanan Pokok'
                            WHEN 'wajib' THEN 'Simpanan Wajib'
                            WHEN 'sukarela' THEN 'Simpanan Sukarela'
                            ELSE sa.account_type
                        END,
                        ' - ', m.full_name
                    ) as description"),
                    'm.full_name as member_name',
                    DB::raw("CASE sa.account_type 
                        WHEN 'pokok' THEN 'Simpanan Pokok'
                        WHEN 'wajib' THEN 'Simpanan Wajib'
                        WHEN 'sukarela' THEN 'Simpanan Sukarela'
                        ELSE sa.account_type
                    END as account_name"),
                    DB::raw("'savings' as source"),
                    'm.full_name as created_by',
                    't.created_at'
                )
                ->where('t.transaction_type', 'deposit');

            // Get expense transactions
            $expenseTransactions = DB::table('expenses as e')
                ->join('accounts as a', 'e.account_id', '=', 'a.id')
                ->join('members as m', 'e.created_by', '=', 'm.id')
                ->select(
                    'e.id',
                    'e.expense_date as transaction_date',
                    DB::raw("'pengeluaran' as type"),
                    'e.amount',
                    'e.description',
                    DB::raw("NULL as member_name"),
                    'a.name as account_name',
                    DB::raw("'expense' as source"),
                    'm.full_name as created_by',
                    'e.created_at'
                );

            // Combine and get recent transactions
            $transactions = $incomeTransactions
                ->unionAll($expenseTransactions)
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
            Log::error('Get recent transactions error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent transactions'
            ], 500);
        }
    }

    /**
     * Get detailed finance breakdown by savings type and expense categories
     * 
     * @param \Illuminate\Http\Request $request
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

            // Pemasukan breakdown by savings type (account_type)
            $pemasukanByType = DB::table('transactions as t')
                ->join('savings_accounts as sa', 't.savings_account_id', '=', 'sa.id')
                ->select(
                    'sa.account_type',
                    DB::raw('SUM(t.amount) as total')
                )
                ->where('t.transaction_type', 'deposit')
                ->whereBetween('t.transaction_date', [$startDate, $endDate])
                ->groupBy('sa.account_type')
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
                    'a.code as account_code',
                    'a.name as account_name',
                    DB::raw('SUM(e.amount) as total')
                )
                ->whereBetween('e.expense_date', [$startDate, $endDate])
                ->groupBy('a.id', 'a.code', 'a.name')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'account_id' => $item->account_id,
                        'account_code' => $item->account_code,
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
            Log::error('Get finance breakdown error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve finance breakdown'
            ], 500);
        }
    }
}
