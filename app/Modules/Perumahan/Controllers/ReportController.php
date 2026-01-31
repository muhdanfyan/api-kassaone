<?php

namespace App\Modules\Perumahan\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\Perumahan\Models\EstateResident;
use App\Modules\Perumahan\Models\EstateFeePayment;
use App\Modules\Perumahan\Models\EstateService;
use App\Modules\Perumahan\Models\EstateSecurityLog;
use App\Modules\Perumahan\Models\EstateWasteCollection;
use App\Modules\Perumahan\Services\PdfReportService;
use Carbon\Carbon;

class ReportController
{
    protected $pdfService;
    
    public function __construct(PdfReportService $pdfService)
    {
        $this->pdfService = $pdfService;
    }
    
    /**
     * Get monthly summary report
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function monthlySummary(Request $request)
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            
            // Validate month and year
            if ($month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid month. Must be between 1-12'
                ], 400);
            }
            
            $periodStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
            $periodEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();
            $periodName = $periodStart->format('F Y');
            
            // Residents statistics
            $totalResidents = EstateResident::count();
            $activeResidents = EstateResident::where('status', 'active')->count();
            $vacantHouses = EstateResident::where('house_status', 'vacant')->count();
            $newThisMonth = EstateResident::whereBetween('created_at', [$periodStart, $periodEnd])->count();
            
            // Fee statistics
            $feeStats = EstateFeePayment::whereBetween('created_at', [$periodStart, $periodEnd])
                ->selectRaw('
                    COUNT(*) as total_payments,
                    SUM(amount) as total_expected,
                    SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as total_collected,
                    COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count,
                    COUNT(CASE WHEN status = "overdue" THEN 1 END) as overdue_count
                ')
                ->first();
            
            $collectionRate = $feeStats->total_expected > 0 
                ? round(($feeStats->total_collected / $feeStats->total_expected) * 100, 2) 
                : 0;
            
            // Service statistics
            $serviceStats = EstateService::whereBetween('created_at', [$periodStart, $periodEnd])
                ->selectRaw('
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status = "resolved" THEN 1 END) as resolved,
                    COUNT(CASE WHEN status = "closed" THEN 1 END) as closed,
                    COUNT(CASE WHEN status IN ("submitted", "acknowledged", "in_progress") THEN 1 END) as pending,
                    AVG(CASE 
                        WHEN resolved_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) / 24 
                        ELSE NULL 
                    END) as avg_resolution_days
                ')
                ->first();
            
            // Security statistics
            $securityStats = EstateSecurityLog::whereBetween('created_at', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->selectRaw('
                    COUNT(*) as total_logs,
                    COUNT(CASE WHEN log_type = "incident" THEN 1 END) as incidents,
                    COUNT(CASE WHEN log_type = "patrol" THEN 1 END) as patrols,
                    COUNT(CASE WHEN log_type = "entry" THEN 1 END) as entries,
                    COUNT(CASE WHEN log_type = "exit" THEN 1 END) as exits
                ')
                ->first();
            
            // Waste statistics
            $wasteStats = EstateWasteCollection::whereBetween('collection_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->selectRaw('
                    COUNT(*) as total_collections,
                    COUNT(DISTINCT house_number) as houses_served,
                    SUM(total_weight) as total_weight,
                    AVG(total_weight) as avg_weight
                ')
                ->first();
            
            $wasteCompliance = $activeResidents > 0 
                ? round(($wasteStats->houses_served / $activeResidents) * 100, 2) 
                : 0;
            
            return response()->json([
                'success' => true,
                'message' => 'Monthly summary retrieved successfully',
                'data' => [
                    'period' => [
                        'month' => (int) $month,
                        'year' => (int) $year,
                    ],
                    'residents' => [
                        'total' => $totalResidents,
                        'active' => $activeResidents,
                        'new_this_month' => $newThisMonth,
                        'vacant' => $vacantHouses,
                    ],
                    'fees' => [
                        'total_billed' => (float) $feeStats->total_expected,
                        'total_collected' => (float) $feeStats->total_collected,
                        'collection_rate' => $collectionRate,
                        'outstanding' => (float) ($feeStats->total_expected - $feeStats->total_collected),
                    ],
                    'services' => [
                        'total_requests' => $serviceStats->total_requests,
                        'resolved' => $serviceStats->resolved,
                        'avg_resolution_time' => $serviceStats->avg_resolution_days 
                            ? round($serviceStats->avg_resolution_days, 1)
                            : 0,
                        'pending' => $serviceStats->pending,
                    ],
                    'security' => [
                        'total_incidents' => $securityStats->incidents,
                        'resolved_incidents' => $securityStats->incidents, // All logged incidents are considered resolved
                        'patrols_conducted' => $securityStats->patrols,
                    ],
                    'waste' => [
                        'scheduled_collections' => $wasteStats->total_collections,
                        'completed_collections' => $wasteStats->total_collections,
                        'compliance_rate' => $wasteCompliance,
                        'total_weight_kg' => (float) $wasteStats->total_weight,
                    ],
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate monthly summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get financial report
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function financial(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());
            
            // Calculate totals for the period
            $totalStats = EstateFeePayment::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('
                    SUM(amount) as total_billed,
                    SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as total_collected,
                    SUM(CASE WHEN status IN ("pending", "overdue") THEN amount ELSE 0 END) as total_outstanding
                ')
                ->first();
            
            $collectionRate = $totalStats->total_billed > 0 
                ? round(($totalStats->total_collected / $totalStats->total_billed) * 100, 2) 
                : 0;
            
            // Payment by fee type
            $byFeeType = EstateFeePayment::join('estate_fees', 'estate_fee_payments.fee_id', '=', 'estate_fees.id')
                ->whereBetween('estate_fee_payments.created_at', [$dateFrom, $dateTo])
                ->selectRaw('
                    estate_fees.fee_name,
                    SUM(estate_fee_payments.amount) as amount_billed,
                    SUM(CASE WHEN estate_fee_payments.status = "paid" THEN estate_fee_payments.amount ELSE 0 END) as amount_collected,
                    SUM(CASE WHEN estate_fee_payments.status IN ("pending", "overdue") THEN estate_fee_payments.amount ELSE 0 END) as outstanding
                ')
                ->groupBy('estate_fees.id', 'estate_fees.fee_name')
                ->get()
                ->map(function($item) {
                    return [
                        'fee_name' => $item->fee_name,
                        'amount_billed' => (float) $item->amount_billed,
                        'amount_collected' => (float) $item->amount_collected,
                        'outstanding' => (float) $item->outstanding,
                    ];
                });
            
            // Monthly trend (last 6 months)
            $monthlyTrend = EstateFeePayment::whereBetween('created_at', [
                    \Carbon\Carbon::parse($dateFrom)->subMonths(5)->startOfMonth(),
                    $dateTo
                ])
                ->selectRaw('
                    DATE_FORMAT(created_at, "%Y-%m") as month,
                    SUM(amount) as billed,
                    SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as collected
                ')
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(function($item) {
                    return [
                        'month' => $item->month,
                        'billed' => (float) $item->billed,
                        'collected' => (float) $item->collected,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'from' => $dateFrom,
                        'to' => $dateTo,
                    ],
                    'summary' => [
                        'total_billed' => (float) $totalStats->total_billed,
                        'total_collected' => (float) $totalStats->total_collected,
                        'total_outstanding' => (float) $totalStats->total_outstanding,
                        'collection_rate' => $collectionRate,
                    ],
                    'by_fee_type' => $byFeeType,
                    'monthly_trend' => $monthlyTrend,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate financial report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get residents list report
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function residentsList(Request $request)
    {
        try {
            $status = $request->input('status'); // active, inactive
            $houseStatus = $request->input('house_status'); // owner_occupied, rented, vacant
            
            $query = EstateResident::query();
            
            if ($status) {
                $query->where('status', $status);
            }
            
            if ($houseStatus) {
                $query->where('house_status', $houseStatus);
            }
            
            $residents = $query->orderBy('house_number')->get()->map(function($resident) {
                return [
                    'id' => $resident->id,
                    'house_number' => $resident->house_number,
                    'owner_name' => $resident->owner_name,
                    'owner_phone' => $resident->owner_phone,
                    'house_status' => $resident->house_status,
                    'house_type' => 'type_' . $resident->house_type, // Add 'type_' prefix for frontend
                    'occupant_count' => $resident->total_occupants ?? 1,
                    'status' => $resident->status,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $residents,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate residents list',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get payment status report
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentStatus(Request $request)
    {
        try {
            $periodMonth = $request->input('period_month', now()->month);
            $periodYear = $request->input('period_year', now()->year);
            
            $payments = EstateFeePayment::with(['resident', 'fee'])
                ->where('period_month', $periodMonth)
                ->where('period_year', $periodYear)
                ->orderBy('house_number')
                ->get();
            
            // Transform to details array matching specification
            $details = $payments->map(function($payment) {
                return [
                    'house_number' => $payment->house_number,
                    'owner_name' => $payment->resident->owner_name ?? 'Unknown',
                    'status' => $payment->status,
                    'amount' => (float) $payment->amount,
                    'paid_date' => $payment->status === 'paid' ? $payment->payment_date : null,
                ];
            });
            
            // Count unique houses
            $uniqueHouses = $payments->unique('house_number')->count();
            
            // Summary statistics
            $summary = [
                'total_houses' => $uniqueHouses > 0 ? $uniqueHouses : EstateResident::where('status', 'active')->count(),
                'paid' => $payments->where('status', 'paid')->unique('house_number')->count(),
                'pending' => $payments->where('status', 'pending')->unique('house_number')->count(),
                'overdue' => $payments->where('status', 'overdue')->unique('house_number')->count(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'month' => (int) $periodMonth,
                        'year' => (int) $periodYear,
                    ],
                    'summary' => $summary,
                    'details' => $details,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payment status report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get service performance report
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function servicePerformance(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());
            
            // Overall statistics
            $overall = EstateService::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('
                    COUNT(*) as total_requests,
                    COUNT(CASE WHEN status = "resolved" THEN 1 END) as resolved,
                    COUNT(CASE WHEN status IN ("submitted", "acknowledged", "in_progress") THEN 1 END) as pending,
                    AVG(CASE WHEN resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END) as avg_resolution_hours
                ')
                ->first();
            
            // By category
            $byCategory = EstateService::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('
                    category,
                    COUNT(*) as count,
                    COUNT(CASE WHEN status = "resolved" THEN 1 END) as resolved,
                    AVG(CASE WHEN resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) END) as avg_resolution_hours
                ')
                ->groupBy('category')
                ->get()
                ->map(function($item) {
                    return [
                        'category' => $item->category,
                        'count' => $item->count,
                        'resolved' => $item->resolved,
                        'avg_resolution_hours' => $item->avg_resolution_hours ? round($item->avg_resolution_hours, 1) : 0,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'from' => $dateFrom,
                        'to' => $dateTo,
                    ],
                    'summary' => [
                        'total_requests' => $overall->total_requests,
                        'resolved' => $overall->resolved,
                        'pending' => $overall->pending,
                        'avg_resolution_hours' => $overall->avg_resolution_hours ? round($overall->avg_resolution_hours, 1) : 0,
                    ],
                    'by_category' => $byCategory,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate service performance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get fee collection history for the last N months
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function feeHistory(Request $request)
    {
        try {
            $months = $request->input('months', 6);
            $history = [];
            
            // Month names in Indonesian
            $monthNames = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
            ];
            
            // Get last N months data
            for ($i = $months - 1; $i >= 0; $i--) {
                $date = \Carbon\Carbon::now()->subMonths($i);
                $month = $date->month;
                $year = $date->year;
                
                // Get payments for this month
                $collected = EstateFeePayment::where('period_month', $month)
                    ->where('period_year', $year)
                    ->where('status', 'paid')
                    ->sum(DB::raw('CAST(amount AS DECIMAL(15,2))'));
                
                // Get outstanding payments (pending, overdue, or unpaid)
                $outstanding = EstateFeePayment::where('period_month', $month)
                    ->where('period_year', $year)
                    ->whereIn('status', ['pending', 'overdue', 'unpaid'])
                    ->sum(DB::raw('CAST(amount AS DECIMAL(15,2))'));
                
                // Calculate collection rate
                $total = $collected + $outstanding;
                $rate = $total > 0 ? round(($collected / $total) * 100, 2) : 100;
                
                $history[] = [
                    'month' => $month,
                    'year' => $year,
                    'month_label' => $monthNames[$month] . ' ' . $year,
                    'collected' => (float) $collected,
                    'outstanding' => (float) $outstanding,
                    'rate' => (float) $rate,
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fee history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get service request history for the last N months
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function serviceHistory(Request $request)
    {
        try {
            $months = $request->input('months', 6);
            $history = [];
            
            // Month names in Indonesian
            $monthNames = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
                9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
            ];
            
            // Get last N months data
            for ($i = $months - 1; $i >= 0; $i--) {
                $date = \Carbon\Carbon::now()->subMonths($i);
                $startDate = $date->copy()->startOfMonth();
                $endDate = $date->copy()->endOfMonth();
                $month = $date->month;
                $year = $date->year;
                
                // Get services created in this month
                $stats = EstateService::selectRaw('
                        COUNT(*) as total,
                        COUNT(CASE WHEN status IN ("resolved", "closed") THEN 1 END) as resolved,
                        COUNT(CASE WHEN status = "in_progress" THEN 1 END) as in_progress,
                        COUNT(CASE WHEN status IN ("submitted", "acknowledged") THEN 1 END) as pending
                    ')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->first();
                
                $history[] = [
                    'month' => $month,
                    'year' => $year,
                    'month_label' => $monthNames[$month] . ' ' . $year,
                    'total' => (int) ($stats->total ?? 0),
                    'resolved' => (int) ($stats->resolved ?? 0),
                    'in_progress' => (int) ($stats->in_progress ?? 0),
                    'pending' => (int) ($stats->pending ?? 0),
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Generate monthly fee report (PDF or JSON)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function generateFeeReport(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'format' => 'nullable|in:json,pdf'
        ]);
        
        try {
            $month = $request->input('month');
            $year = $request->input('year');
            $format = $request->input('format', 'pdf');
            
            // Get report data
            $data = $this->getFeeReportData($month, $year);
            
            // Return JSON if requested
            if ($format === 'json') {
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }
            
            // Generate PDF
            return $this->pdfService->generateFeePdf($data);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate fee report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate security log report (PDF or JSON)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function generateSecurityReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|in:patrol,incident,entry,exit',
            'format' => 'nullable|in:json,pdf'
        ]);
        
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $type = $request->input('type');
            $format = $request->input('format', 'pdf');
            
            // Get report data
            $data = $this->getSecurityReportData($startDate, $endDate, $type);
            
            // Return JSON if requested
            if ($format === 'json') {
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }
            
            // Generate PDF
            return $this->pdfService->generateSecurityPdf($data);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate security report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate service request report (PDF or JSON)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function generateServiceReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'category' => 'nullable|in:maintenance,complaint,request,facility_booking,emergency',
            'status' => 'nullable|in:pending,in_progress,resolved',
            'format' => 'nullable|in:json,pdf'
        ]);
        
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $category = $request->input('category');
            $status = $request->input('status');
            $format = $request->input('format', 'pdf');
            
            // Get report data
            $data = $this->getServiceReportData($startDate, $endDate, $category, $status);
            
            // Return JSON if requested
            if ($format === 'json') {
                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }
            
            // Generate PDF
            return $this->pdfService->generateServicePdf($data);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate service report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get fee report data
     */
    private function getFeeReportData($month, $year)
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        // Get payments for the month
        $payments = DB::table('estate_fee_payments as p')
            ->join('estate_residents as r', 'p.house_number', '=', 'r.house_number')
            ->join('estate_fees as f', 'p.fee_id', '=', 'f.id')
            ->select(
                'r.house_number',
                'r.owner_name',
                'f.fee_name',
                DB::raw('CAST(p.amount AS DECIMAL(15,2)) as amount'),
                'p.status',
                'p.payment_date',
                'p.payment_method',
                DB::raw('CAST(p.penalty_amount AS DECIMAL(15,2)) as penalty_amount'),
                'p.payment_number'
            )
            ->where('p.period_month', $month)
            ->where('p.period_year', $year)
            ->orderBy('r.house_number')
            ->orderBy('f.fee_name')
            ->get();
        
        // Calculate summary
        $totalBilled = DB::table('estate_fee_payments')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->sum(DB::raw('CAST(amount AS DECIMAL(15,2))'));
            
        $totalCollected = DB::table('estate_fee_payments')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('status', 'paid')
            ->sum(DB::raw('CAST(amount AS DECIMAL(15,2))'));
            
        $totalOutstanding = $totalBilled - $totalCollected;
        $collectionRate = $totalBilled > 0 ? ($totalCollected / $totalBilled) * 100 : 0;
        
        $totalResidents = DB::table('estate_residents')
            ->where('status', 'active')
            ->count();
        
        return [
            'period' => [
                'month' => $month,
                'year' => $year,
                'month_name' => $monthNames[$month]
            ],
            'summary' => [
                'total_residents' => $totalResidents,
                'total_billed' => (float) $totalBilled,
                'total_collected' => (float) $totalCollected,
                'total_outstanding' => (float) $totalOutstanding,
                'collection_rate' => round($collectionRate, 2)
            ],
            'details' => $payments
        ];
    }
    
    /**
     * Get security report data
     */
    private function getSecurityReportData($startDate, $endDate, $type = null)
    {
        $query = DB::table('estate_security_logs')
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        if ($type) {
            $query->where('log_type', $type);
        }
        
        $logs = $query->orderBy('created_at', 'desc')->get();
        
        // Calculate summary
        $summary = DB::table('estate_security_logs')
            ->selectRaw('
                COUNT(*) as total_logs,
                COUNT(CASE WHEN log_type = "patrol" THEN 1 END) as patrols,
                COUNT(CASE WHEN log_type = "incident" THEN 1 END) as incidents,
                COUNT(CASE WHEN log_type = "entry" THEN 1 END) as entries,
                COUNT(CASE WHEN log_type = "exit" THEN 1 END) as exits
            ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->first();
        
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days
            ],
            'summary' => [
                'total_logs' => (int) $summary->total_logs,
                'patrols' => (int) $summary->patrols,
                'incidents' => (int) $summary->incidents,
                'entries' => (int) $summary->entries,
                'exits' => (int) $summary->exits
            ],
            'details' => $logs
        ];
    }
    
    /**
     * Get service report data
     */
    private function getServiceReportData($startDate, $endDate, $category = null, $status = null)
    {
        $query = DB::table('estate_services as s')
            ->join('estate_residents as r', 's.house_number', '=', 'r.house_number')
            ->select(
                's.id as ticket_number',
                's.created_at',
                'r.house_number',
                's.reporter_name',
                's.category',
                's.sub_category',
                's.title',
                's.priority',
                's.status',
                's.assigned_to',
                's.resolved_at',
                DB::raw('DATEDIFF(s.resolved_at, s.created_at) as resolution_time_days')
            )
            ->whereBetween('s.created_at', [$startDate, $endDate]);
            
        if ($category) {
            $query->where('s.category', $category);
        }
        
        if ($status) {
            if ($status === 'pending') {
                $query->whereIn('s.status', ['submitted', 'acknowledged']);
            } else {
                $query->where('s.status', $status);
            }
        }
        
        $services = $query->orderBy('s.created_at', 'desc')->get();
        
        // Calculate summary
        $summaryQuery = DB::table('estate_services')
            ->selectRaw('
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status IN ("resolved", "closed") THEN 1 END) as resolved,
                COUNT(CASE WHEN status = "in_progress" THEN 1 END) as in_progress,
                COUNT(CASE WHEN status IN ("submitted", "acknowledged") THEN 1 END) as pending,
                AVG(CASE WHEN resolved_at IS NOT NULL THEN DATEDIFF(resolved_at, created_at) END) as avg_resolution_days
            ')
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        $summary = $summaryQuery->first();
        
        // By category
        $byCategory = DB::table('estate_services')
            ->selectRaw('
                category,
                COUNT(*) as count,
                COUNT(CASE WHEN status IN ("resolved", "closed") THEN 1 END) as resolved
            ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('category')
            ->get();
        
        $days = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days
            ],
            'summary' => [
                'total_requests' => (int) $summary->total_requests,
                'resolved' => (int) $summary->resolved,
                'in_progress' => (int) $summary->in_progress,
                'pending' => (int) $summary->pending,
                'avg_resolution_days' => round($summary->avg_resolution_days ?? 0, 1)
            ],
            'by_category' => $byCategory,
            'details' => $services
        ];
    }
}
