<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\EstateResident;
use App\Modules\Perumahan\Models\EstateFeePayment;
use App\Modules\Perumahan\Models\EstateService;
use App\Modules\Perumahan\Models\EstateSecurityLog;
use App\Modules\Perumahan\Models\EstateWasteCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats()
    {
        $totalHouses = EstateResident::count();
        $occupiedHouses = EstateResident::whereIn('house_status', ['owner_occupied', 'rented'])->count();
        $vacantHouses = EstateResident::where('house_status', 'vacant')->count();
        
        $totalResidents = EstateResident::active()->sum('total_occupants');
        
        $activeComplaints = EstateService::active()->count();
        
        $pendingPayments = EstateFeePayment::pending()->count();
        
        $securityIncidentsThisMonth = EstateSecurityLog::incidents()
            ->whereMonth('log_datetime', now()->month)
            ->whereYear('log_datetime', now()->year)
            ->count();
        
        // Check today's waste collection
        $wasteCollectionToday = EstateWasteCollection::byDate(now())
            ->with('schedule')
            ->first();

        $recentActivities = $this->getRecentActivities();

        return response()->json([
            'success' => true,
            'data' => [
                'total_houses' => $totalHouses,
                'occupied_houses' => $occupiedHouses,
                'vacant_houses' => $vacantHouses,
                'total_residents' => $totalResidents,
                'active_complaints' => $activeComplaints,
                'pending_payments' => $pendingPayments,
                'security_incidents_this_month' => $securityIncidentsThisMonth,
                'waste_collection_today' => $wasteCollectionToday ? [
                    'scheduled' => true,
                    'time' => $wasteCollectionToday->collection_time ? $wasteCollectionToday->collection_time->format('H:i') : null,
                    'status' => $wasteCollectionToday->status,
                    'waste_type' => $wasteCollectionToday->schedule->waste_type ?? null,
                ] : [
                    'scheduled' => false,
                ],
                'recent_activities' => $recentActivities,
            ],
        ]);
    }

    /**
     * Get chart data for dashboard
     */
    public function charts(Request $request)
    {
        $period = $request->input('period', 'month');

        $paymentTrend = $this->getPaymentTrend($period);
        $complaintCategories = $this->getComplaintCategories();
        $securityIncidents = $this->getSecurityIncidents($period);

        return response()->json([
            'success' => true,
            'data' => [
                'payment_trend' => $paymentTrend,
                'complaint_categories' => $complaintCategories,
                'security_incidents' => $securityIncidents,
            ],
        ]);
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities()
    {
        $activities = [];

        // Recent security logs
        $recentSecurity = EstateSecurityLog::orderBy('log_datetime', 'desc')
            ->take(3)
            ->get(['log_type', 'guard_name', 'log_datetime', 'notes']);

        foreach ($recentSecurity as $log) {
            $activities[] = [
                'type' => 'security',
                'description' => ucfirst($log->log_type) . ' - ' . ($log->guard_name ?? 'Security'),
                'timestamp' => $log->log_datetime,
            ];
        }

        // Recent service requests
        $recentServices = EstateService::orderBy('created_at', 'desc')
            ->take(3)
            ->get(['ticket_number', 'category', 'status', 'created_at']);

        foreach ($recentServices as $service) {
            $activities[] = [
                'type' => 'service',
                'description' => 'Ticket ' . $service->ticket_number . ' - ' . $service->status,
                'timestamp' => $service->created_at,
            ];
        }

        // Sort by timestamp
        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Get payment trend data
     */
    private function getPaymentTrend($period)
    {
        $data = [];
        
        if ($period === 'month') {
            // Last 6 months
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $month = $date->format('M');
                $year = $date->year;
                $monthNum = $date->month;

                $paid = EstateFeePayment::paid()
                    ->byPeriod($monthNum, $year)
                    ->count();

                $pending = EstateFeePayment::pending()
                    ->byPeriod($monthNum, $year)
                    ->count();

                $data[] = [
                    'month' => $month,
                    'paid' => $paid,
                    'pending' => $pending,
                ];
            }
        }

        return $data;
    }

    /**
     * Get complaint categories data
     */
    private function getComplaintCategories()
    {
        return EstateService::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => ucfirst(str_replace('_', ' ', $item->category)),
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get security incidents data
     */
    private function getSecurityIncidents($period)
    {
        $data = [];
        
        if ($period === 'month') {
            // Last 30 days
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');

                $count = EstateSecurityLog::incidents()
                    ->whereDate('log_datetime', $date)
                    ->count();

                if ($count > 0) {
                    $data[] = [
                        'date' => $date,
                        'count' => $count,
                    ];
                }
            }
        }

        return $data;
    }
}
