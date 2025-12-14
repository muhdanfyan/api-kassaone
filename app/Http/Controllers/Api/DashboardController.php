<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use App\Models\Meeting;
use App\Models\ShuDistribution;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/stats
     * Get dashboard statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            // Total anggota yang sudah verified
            $totalAnggota = Member::where('verification_status', Member::VERIFICATION_VERIFIED)->count();

            // Anggota baru bulan ini (yang created_at di bulan ini)
            $anggotaBaruBulanIni = Member::whereMonth('created_at', Carbon::now()->month)
                                        ->whereYear('created_at', Carbon::now()->year)
                                        ->count();

            // Total simpanan dari semua savings_accounts
            $totalSimpanan = SavingsAccount::sum('balance');

            // Total pembiayaan (jika ada tipe transaksi pembiayaan)
            // Set to 0 if no financing table exists
            $totalPembiayaan = Transaction::where('transaction_type', 'pembiayaan')->sum('amount');

            // SHU tahun berjalan
            $shuTahunBerjalan = ShuDistribution::where('fiscal_year', Carbon::now()->year)
                                              ->sum('total_shu_amount');

            // Transaksi bulan ini - SEMUA transaksi (deposit & withdrawal)
            $transaksiBulanIni = Transaction::whereMonth('transaction_date', Carbon::now()->month)
                                            ->whereYear('transaction_date', Carbon::now()->year)
                                            ->whereIn('transaction_type', ['deposit', 'withdrawal', 'shu_distribution', 'fee'])
                                            ->count();

            // Rapat yang terjadwal (meeting_date >= hari ini)
            $rapatTerjadwal = Meeting::where('meeting_date', '>=', Carbon::now())->count();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard stats retrieved successfully',
                'data' => [
                    'totalAnggota' => $totalAnggota,
                    'anggotaBaruBulanIni' => $anggotaBaruBulanIni,
                    'totalSimpanan' => (float) $totalSimpanan,
                    'totalPembiayaan' => (float) $totalPembiayaan,
                    'shuTahunBerjalan' => (float) ($shuTahunBerjalan ?? 0),
                    'transaksiBulanIni' => $transaksiBulanIni,
                    'rapatTerjadwal' => $rapatTerjadwal,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get dashboard stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/membership-growth?months=6
     * Get membership growth data for chart
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMembershipGrowth(Request $request)
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
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];

            for ($i = $months - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $monthNum = $date->month;
                $year = $date->year;

                // Count new members for this month
                $newMembers = Member::whereMonth('created_at', $monthNum)
                                   ->whereYear('created_at', $year)
                                   ->count();

                $data[] = [
                    'month' => $monthNames[$monthNum],
                    'year' => $year,
                    'new_members' => $newMembers,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Membership growth data retrieved successfully',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Get membership growth error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve membership growth data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/savings-distribution
     * Get savings distribution by account type
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSavingsDistribution()
    {
        try {
            // Group savings by account_type and calculate total
            $distribution = SavingsAccount::selectRaw('
                    account_type,
                    CASE 
                        WHEN account_type = "pokok" THEN "Simpanan Pokok"
                        WHEN account_type = "wajib" THEN "Simpanan Wajib"
                        WHEN account_type = "sukarela" THEN "Simpanan Sukarela"
                        ELSE account_type
                    END as name,
                    "Ekuitas" as account_type_category,
                    SUM(balance) as total
                ')
                ->groupBy('account_type')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'account_type' => $item->account_type_category,
                        'total' => (float) $item->total,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Savings distribution retrieved successfully',
                'data' => $distribution
            ]);

        } catch (\Exception $e) {
            Log::error('Get savings distribution error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve savings distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/monthly-transactions?months=6
     * Get monthly transaction count for chart
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyTransactions(Request $request)
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
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
            ];

            for ($i = $months - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $monthNum = $date->month;
                $year = $date->year;

                // Count transactions for this month
                $transactionCount = Transaction::whereMonth('transaction_date', $monthNum)
                                               ->whereYear('transaction_date', $year)
                                               ->count();

                $data[] = [
                    'month' => $monthNames[$monthNum],
                    'year' => $year,
                    'transaction_count' => $transactionCount,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Monthly transactions retrieved successfully',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Get monthly transactions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve monthly transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/recent-activities?limit=10
     * Get recent activities (members, transactions, meetings)
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentActivities(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            
            // Validate limit parameter
            if ($limit < 1 || $limit > 50) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limit parameter must be between 1 and 50'
                ], 400);
            }

            $activities = [];

            // Get recent member registrations
            $recentMembers = Member::orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'title' => 'Anggota Baru: ' . $member->full_name,
                        'time' => $this->timeAgo($member->created_at),
                        'timestamp' => $member->created_at,
                        'type' => 'member'
                    ];
                });

            // Get recent transactions
            $recentTransactions = Transaction::with('member')
                ->orderBy('transaction_date', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($transaction) {
                    $type = $transaction->transaction_type === 'deposit' ? 'Setoran' : 'Penarikan';
                    return [
                        'id' => $transaction->id,
                        'title' => $type . ': ' . ($transaction->member->full_name ?? 'Unknown'),
                        'time' => $this->timeAgo($transaction->transaction_date),
                        'timestamp' => $transaction->transaction_date,
                        'type' => 'payment'
                    ];
                });

            // Get recent meetings
            $recentMeetings = Meeting::orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($meeting) {
                    return [
                        'id' => $meeting->id,
                        'title' => 'Rapat: ' . $meeting->title,
                        'time' => $this->timeAgo($meeting->created_at),
                        'timestamp' => $meeting->created_at,
                        'type' => 'meeting'
                    ];
                });

            // Merge and sort all activities by timestamp
            $activities = collect($recentMembers)
                ->concat($recentTransactions)
                ->concat($recentMeetings)
                ->sortByDesc('timestamp')
                ->take($limit)
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Recent activities retrieved successfully',
                'data' => $activities
            ]);

        } catch (\Exception $e) {
            Log::error('Get recent activities error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/upcoming-meetings?limit=10
     * Get upcoming meetings
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUpcomingMeetings(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            
            // Validate limit parameter
            if ($limit < 1 || $limit > 50) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limit parameter must be between 1 and 50'
                ], 400);
            }

            $meetings = Meeting::where('meeting_date', '>=', Carbon::now())
                ->orderBy('meeting_date', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($meeting) {
                    return [
                        'id' => $meeting->id,
                        'title' => $meeting->title,
                        'date' => $meeting->meeting_date,
                        'time' => Carbon::parse($meeting->meeting_date)->format('H:i'),
                        'location' => $meeting->location ?? 'TBA',
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Upcoming meetings retrieved successfully',
                'data' => $meetings
            ]);

        } catch (\Exception $e) {
            Log::error('Get upcoming meetings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming meetings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/shu-distribution
     * Get SHU distribution (optional - not ready yet)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSHUDistribution()
    {
        // Return empty for now since feature is not ready
        return response()->json([
            'success' => true,
            'message' => 'SHU distribution data (feature not ready)',
            'data' => []
        ]);
    }

    /**
     * Helper function to calculate "time ago"
     * 
     * @param string $datetime
     * @return string
     */
    private function timeAgo($datetime)
    {
        $time = Carbon::parse($datetime);
        $now = Carbon::now();
        
        $diff = $time->diffInSeconds($now);
        
        if ($diff < 60) {
            return $diff . ' detik yang lalu';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' menit yang lalu';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' jam yang lalu';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . ' hari yang lalu';
        } elseif ($diff < 2592000) {
            return floor($diff / 604800) . ' minggu yang lalu';
        } elseif ($diff < 31536000) {
            return floor($diff / 2592000) . ' bulan yang lalu';
        } else {
            return floor($diff / 31536000) . ' tahun yang lalu';
        }
    }
}
