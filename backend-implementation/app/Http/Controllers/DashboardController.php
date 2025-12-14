<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dashboard Controller
 * Handles all main dashboard statistics and data
 * 
 * @access All authenticated users
 */
class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/stats
     * Get main dashboard statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        try {
            // Total Anggota
            $totalAnggota = DB::table('members')
                ->where('status', 'active')
                ->count();

            // Anggota Baru Bulan Ini
            $anggotaBaruBulanIni = DB::table('members')
                ->where('status', 'active')
                ->whereMonth('join_date', now()->month)
                ->whereYear('join_date', now()->year)
                ->count();

            // Total Simpanan (Semua jenis simpanan yang approved)
            $totalSimpanan = DB::table('savings_transactions')
                ->where('transaction_type', 'deposit')
                ->where('status', 'approved')
                ->sum('amount');

            // Total Pembiayaan (dari tabel pembiayaan jika ada, untuk sementara 0)
            $totalPembiayaan = 0;

            // SHU Tahun Berjalan
            $shuTahunBerjalan = DB::table('shu_distributions')
                ->whereYear('distribution_year', now()->year)
                ->sum('total_shu');

            // Transaksi Bulan Ini (deposit + withdrawal)
            $transaksiBulanIni = DB::table('savings_transactions')
                ->whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)
                ->where('status', 'approved')
                ->count();

            // Rapat Terjadwal (upcoming meetings)
            $rapatTerjadwal = DB::table('meetings')
                ->where('meeting_date', '>=', now())
                ->where('status', 'scheduled')
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => [
                    'totalAnggota' => $totalAnggota,
                    'anggotaBaruBulanIni' => $anggotaBaruBulanIni,
                    'totalSimpanan' => (float) $totalSimpanan,
                    'totalPembiayaan' => (float) $totalPembiayaan,
                    'shuTahunBerjalan' => (float) $shuTahunBerjalan,
                    'transaksiBulanIni' => $transaksiBulanIni,
                    'rapatTerjadwal' => $rapatTerjadwal,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/membership-growth?months=6
     * Get membership growth data for chart
     * 
     * @param Request $request
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
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
            ];

            for ($i = $months - 1; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthNum = $date->month;
                $year = $date->year;

                // Count new members in this month
                $newMembers = DB::table('members')
                    ->where('status', 'active')
                    ->whereMonth('join_date', $monthNum)
                    ->whereYear('join_date', $year)
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve membership growth data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/savings-distribution
     * Get savings distribution by type (Pokok, Wajib, Sukarela)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSavingsDistribution()
    {
        try {
            $distribution = DB::table('savings_transactions as st')
                ->join('savings_types as sty', 'st.savings_type_id', '=', 'sty.id')
                ->select(
                    'sty.name',
                    'sty.account_type',
                    DB::raw('SUM(st.amount) as total')
                )
                ->where('st.transaction_type', 'deposit')
                ->where('st.status', 'approved')
                ->groupBy('sty.id', 'sty.name', 'sty.account_type')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'account_type' => $item->account_type,
                        'total' => (float) $item->total,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Savings distribution retrieved successfully',
                'data' => $distribution
            ]);
        } catch (\Exception $e) {
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
     * @param Request $request
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
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
            ];

            for ($i = $months - 1; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthNum = $date->month;
                $year = $date->year;

                // Count transactions in this month
                $transactionCount = DB::table('savings_transactions')
                    ->where('status', 'approved')
                    ->whereMonth('transaction_date', $monthNum)
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve monthly transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/shu-distribution
     * Get SHU distribution percentages
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSHUDistribution()
    {
        try {
            // Get the latest SHU distribution percentages
            // These are typically fixed percentages defined in the system
            $distribution = [
                ['category' => 'Cadangan', 'percentage' => 25],
                ['category' => 'Anggota', 'percentage' => 50],
                ['category' => 'Pengurus', 'percentage' => 10],
                ['category' => 'Pendidikan', 'percentage' => 5],
                ['category' => 'Sosial', 'percentage' => 7.5],
                ['category' => 'Zakat', 'percentage' => 2.5],
            ];

            return response()->json([
                'success' => true,
                'message' => 'SHU distribution retrieved successfully',
                'data' => $distribution
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve SHU distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/recent-activities?limit=10
     * Get recent activities in the system
     * 
     * @param Request $request
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

            // Combine different types of activities
            $activities = [];

            // New members
            $newMembers = DB::table('members')
                ->select(
                    'id',
                    'full_name as name',
                    'created_at',
                    DB::raw("'member' as type"),
                    DB::raw("'Anggota baru bergabung' as action")
                )
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($newMembers as $member) {
                $activities[] = [
                    'id' => $member->id,
                    'action' => $member->action,
                    'name' => $member->name,
                    'time' => $this->getTimeAgo($member->created_at),
                    'type' => $member->type,
                    'created_at' => $member->created_at,
                ];
            }

            // Recent savings transactions
            $recentTransactions = DB::table('savings_transactions as st')
                ->join('members as m', 'st.member_id', '=', 'm.id')
                ->join('savings_types as sty', 'st.savings_type_id', '=', 'sty.id')
                ->select(
                    'st.id',
                    'm.full_name as name',
                    'st.created_at',
                    DB::raw("'payment' as type"),
                    DB::raw("CONCAT('Pembayaran ', sty.name) as action")
                )
                ->where('st.transaction_type', 'deposit')
                ->where('st.status', 'approved')
                ->orderBy('st.created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($recentTransactions as $transaction) {
                $activities[] = [
                    'id' => $transaction->id,
                    'action' => $transaction->action,
                    'name' => $transaction->name,
                    'time' => $this->getTimeAgo($transaction->created_at),
                    'type' => $transaction->type,
                    'created_at' => $transaction->created_at,
                ];
            }

            // Recent meetings
            $recentMeetings = DB::table('meetings')
                ->select(
                    'id',
                    'title as action',
                    'created_at',
                    DB::raw("'System' as name"),
                    DB::raw("'meeting' as type")
                )
                ->where('status', 'scheduled')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($recentMeetings as $meeting) {
                $activities[] = [
                    'id' => $meeting->id,
                    'action' => $meeting->action,
                    'name' => $meeting->name,
                    'time' => $this->getTimeAgo($meeting->created_at),
                    'type' => $meeting->type,
                    'created_at' => $meeting->created_at,
                ];
            }

            // Sort all activities by created_at
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Limit to requested number
            $activities = array_slice($activities, 0, $limit);

            // Remove created_at from final response
            $activities = array_map(function($activity) {
                unset($activity['created_at']);
                return $activity;
            }, $activities);

            return response()->json([
                'success' => true,
                'message' => 'Recent activities retrieved successfully',
                'data' => $activities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent activities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get time ago from datetime
     * 
     * @param string $datetime
     * @return string
     */
    private function getTimeAgo($datetime)
    {
        $time = strtotime($datetime);
        $diff = time() - $time;

        if ($diff < 60) {
            return 'baru saja';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' menit yang lalu';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' jam yang lalu';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' hari yang lalu';
        } else {
            return date('d M Y', $time);
        }
    }

    /**
     * GET /api/dashboard/upcoming-meetings?limit=10
     * Get upcoming scheduled meetings
     * 
     * @param Request $request
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

            $meetings = DB::table('meetings')
                ->select(
                    'id',
                    'title',
                    'meeting_date',
                    'meeting_time',
                    'location',
                    'status'
                )
                ->where('meeting_date', '>=', now()->toDateString())
                ->where('status', 'scheduled')
                ->orderBy('meeting_date', 'asc')
                ->orderBy('meeting_time', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($meeting) {
                    return [
                        'id' => $meeting->id,
                        'title' => $meeting->title,
                        'date' => Carbon::parse($meeting->meeting_date)->format('d F Y'),
                        'time' => Carbon::parse($meeting->meeting_time)->format('H:i') . ' WIB',
                        'location' => $meeting->location,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Upcoming meetings retrieved successfully',
                'data' => $meetings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming meetings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
