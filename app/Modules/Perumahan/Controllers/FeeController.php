<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\EstateFee;
use App\Modules\Perumahan\Models\EstateFeePayment;
use App\Modules\Perumahan\Models\EstateResident;
use App\Services\FeeCalculationService;
use App\Helpers\SettingHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller
{
    /**
     * Get fee types
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = EstateFee::query();

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by fee type
            if ($request->has('fee_type')) {
                $query->where('fee_type', $request->fee_type);
            }

            // Sorting
            $query->orderBy('fee_type', 'asc')
                  ->orderBy('fee_name', 'asc');

            $fees = $query->get();

            return response()->json([
                'success' => true,
                'data' => $fees
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fee types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single fee type
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $fee = EstateFee::find($id);

            if (!$fee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee type not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $fee
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fee type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new fee type
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fee_name' => 'required|string|max:100',
                'fee_type' => 'required|in:monthly,yearly,one_time',
                'amount' => 'required|numeric|min:0',
                'applies_to' => 'nullable|in:all,owners_only,tenants_only,specific_houses',
                'specific_houses' => 'nullable|json',
                'description' => 'nullable|string',
                'is_mandatory' => 'boolean',
                'is_active' => 'boolean',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fee = EstateFee::create([
                'fee_name' => $request->fee_name,
                'fee_type' => $request->fee_type,
                'amount' => $request->amount,
                'applies_to' => $request->get('applies_to', 'all'),
                'specific_houses' => $request->specific_houses,
                'description' => $request->description,
                'is_mandatory' => $request->get('is_mandatory', true),
                'is_active' => $request->get('is_active', true),
                'effective_from' => $request->effective_from,
                'effective_until' => $request->effective_until,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fee type created successfully',
                'data' => $fee
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create fee type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update fee type
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $fee = EstateFee::find($id);

            if (!$fee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee type not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'fee_name' => 'sometimes|string|max:100',
                'fee_type' => 'sometimes|in:monthly,yearly,one_time',
                'amount' => 'sometimes|numeric|min:0',
                'applies_to' => 'nullable|in:all,owners_only,tenants_only,specific_houses',
                'specific_houses' => 'nullable|json',
                'description' => 'nullable|string',
                'is_mandatory' => 'boolean',
                'is_active' => 'boolean',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fee->update($request->only([
                'fee_name', 'fee_type', 'amount', 'applies_to', 'specific_houses',
                'description', 'is_mandatory', 'is_active', 'effective_from', 'effective_until'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Fee type updated successfully',
                'data' => $fee->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fee type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete fee type
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $fee = EstateFee::find($id);

            if (!$fee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee type not found'
                ], 404);
            }

            // Check if fee has payments
            $hasPayments = EstateFeePayment::where('fee_id', $id)->exists();
            
            if ($hasPayments) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete fee type with existing payments. Consider deactivating instead.'
                ], 400);
            }

            $fee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fee type deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete fee type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fee payments with filters
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPayments(Request $request)
    {
        try {
            $query = EstateFeePayment::with(['resident:id,house_number,owner_name', 'fee:id,fee_name,amount']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by fee type
            if ($request->has('fee_id')) {
                $query->where('fee_id', $request->fee_id);
            }

            // Filter by house number
            if ($request->filled('house_number')) {
                $query->where('house_number', 'like', '%' . $request->house_number . '%');
            }

            // Filter by area code
            if ($request->filled('area_code')) {
                $areaCode = $request->area_code;
                // Assuming house_number format is "A-01", "B-12", etc.
                $query->where('house_number', 'like', "{$areaCode}-%");
            }

            // Filter by period
            if ($request->has('period_year')) {
                $query->where('period_year', $request->period_year);
            }
            if ($request->has('period_month')) {
                $query->where('period_month', $request->period_month);
            }

            // Filter by payment date range
            if ($request->has('payment_date_from')) {
                $query->whereDate('payment_date', '>=', $request->payment_date_from);
            }
            if ($request->has('payment_date_to')) {
                $query->whereDate('payment_date', '<=', $request->payment_date_to);
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('payment_number', 'like', "%{$search}%")
                      ->orWhere('house_number', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'payment_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $limit = $request->get('limit', 20);
            $payments = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments->items(),
                    'pagination' => [
                        'current_page' => $payments->currentPage(),
                        'total_pages' => $payments->lastPage(),
                        'total_items' => $payments->total(),
                        'per_page' => $payments->perPage(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record a payment
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordPayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'resident_id' => 'required|exists:estate_residents,id',
                'house_number' => [
                    'required',
                    'string',
                    'max:10',
                    // Validate house_number format matches an active area
                    function ($attribute, $value, $fail) {
                        if (strpos($value, '-') !== false) {
                            $areaCode = explode('-', $value)[0];
                            $areaExists = \App\Modules\Perumahan\Models\PerumahanArea::where('area_code', $areaCode)
                                ->where('is_active', true)
                                ->exists();
                            if (!$areaExists) {
                                $fail("Area/Blok '{$areaCode}' tidak valid atau tidak aktif.");
                            }
                        }
                    },
                ],
                'fee_id' => 'required|exists:estate_fees,id',
                'period_month' => 'required|integer|min:1|max:12',
                'period_year' => 'required|integer|min:2020',
                'amount' => 'required|numeric|min:0',
                'payment_date' => 'required|date',
                'payment_method' => 'required|in:cash,transfer,qris,other',
                'status' => 'nullable|in:pending,paid,overdue,cancelled',
                'due_date' => 'nullable|date',
                'receipt_number' => 'nullable|string|max:50',
                'notes' => 'nullable|string',
                'proof_url' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate payment
            $existingPayment = EstateFeePayment::where('resident_id', $request->resident_id)
                ->where('fee_id', $request->fee_id)
                ->where('period_year', $request->period_year)
                ->where('period_month', $request->period_month)
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment for this period already exists'
                ], 400);
            }

            // Generate payment number
            $paymentNumber = $this->generatePaymentNumber();

            // Calculate late days and penalty if applicable using settings
            $lateDays = 0;
            $penaltyAmount = 0;
            $penaltyData = [];
            
            if ($request->due_date) {
                $feeService = new FeeCalculationService();
                $penaltyData = $feeService->calculatePenalty($request->due_date, $request->payment_date);
                $lateDays = $penaltyData['late_days'];
                $penaltyAmount = $penaltyData['penalty_amount'];
            }

            $totalAmount = $request->amount + $penaltyAmount;

            $payment = EstateFeePayment::create([
                'payment_number' => $paymentNumber,
                'resident_id' => $request->resident_id,
                'house_number' => $request->house_number,
                'fee_id' => $request->fee_id,
                'period_month' => $request->period_month,
                'period_year' => $request->period_year,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'status' => $request->get('status', 'paid'),
                'due_date' => $request->due_date,
                'late_days' => $lateDays,
                'penalty_amount' => $penaltyAmount,
                'total_amount' => $totalAmount,
                'receipt_number' => $request->receipt_number,
                'notes' => $request->notes,
                'proof_url' => $request->proof_url,
                'recorded_by' => Auth::guard('admin')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => [
                    'payment' => $payment->load(['resident', 'fee']),
                    'penalty_info' => $penaltyData
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get overdue payments
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOverduePayments()
    {
        try {
            $overduePayments = EstateFeePayment::with(['resident', 'fee'])
                ->where('status', 'overdue')
                ->orWhere(function ($query) {
                    $query->where('status', 'pending')
                          ->where('due_date', '<', now()->toDateString());
                })
                ->orderBy('due_date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $overduePayments
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch overdue payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history for a house
     * 
     * @param string $houseNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistory($houseNumber)
    {
        try {
            $payments = EstateFeePayment::with(['fee'])
                ->where('house_number', $houseNumber)
                ->orderBy('period_year', 'desc')
                ->orderBy('period_month', 'desc')
                ->get();

            $resident = EstateResident::where('house_number', $houseNumber)->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'house_number' => $houseNumber,
                    'resident' => $resident,
                    'payments' => $payments,
                    'total_paid' => $payments->where('status', 'paid')->sum('amount'),
                    'total_pending' => $payments->where('status', 'pending')->sum('amount'),
                    'total_overdue' => $payments->where('status', 'overdue')->sum('amount'),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single payment detail
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showPayment($id)
    {
        try {
            $payment = EstateFeePayment::with(['resident', 'fee'])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $payment
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a payment
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePayment(Request $request, $id)
    {
        try {
            $payment = EstateFeePayment::find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'amount' => 'sometimes|numeric|min:0',
                'payment_date' => 'sometimes|date',
                'payment_method' => 'sometimes|in:cash,transfer,qris,other',
                'status' => 'sometimes|in:pending,paid,overdue,cancelled',
                'due_date' => 'nullable|date',
                'receipt_number' => 'nullable|string|max:50',
                'notes' => 'nullable|string',
                'proof_url' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // If status is being changed to paid and payment_date not provided, set it to now
            if ($request->status === 'paid' && $payment->status !== 'paid' && !$request->has('payment_date')) {
                $request->merge(['payment_date' => now()->toDateString()]);
            }

            // Recalculate late days and penalty if payment date or due date changed
            if (($request->has('payment_date') || $request->has('due_date')) && $payment->due_date) {
                $dueDate = $request->get('due_date', $payment->due_date);
                $paymentDate = $request->get('payment_date', $payment->payment_date);
                
                $feeService = new FeeCalculationService();
                $penaltyData = $feeService->calculatePenalty($dueDate, $paymentDate);
                
                $request->merge([
                    'late_days' => $penaltyData['late_days'],
                    'penalty_amount' => $penaltyData['penalty_amount'],
                    'total_amount' => $request->get('amount', $payment->amount) + $penaltyData['penalty_amount']
                ]);
            }

            $payment->update($request->only([
                'amount', 'payment_date', 'payment_method', 'status', 
                'due_date', 'late_days', 'penalty_amount', 'total_amount',
                'receipt_number', 'notes', 'proof_url'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $payment->fresh()->load(['resident', 'fee'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a payment
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyPayment($id)
    {
        try {
            $payment = EstateFeePayment::find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            // Only allow deletion of pending payments
            if ($payment->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete paid payments. Consider cancelling instead.'
                ], 400);
            }

            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk generate monthly payments for all residents
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkGenerate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'period_month' => 'required|integer|min:1|max:12',
                'period_year' => 'required|integer|min:2020',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $periodMonth = $request->period_month;
            $periodYear = $request->period_year;

            // Get all active monthly fees
            $monthlyFees = EstateFee::where('fee_type', 'monthly')
                ->where('is_active', true)
                ->get();

            if ($monthlyFees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active monthly fees found'
                ], 400);
            }

            // Get all active residents
            $residents = EstateResident::where('status', 'active')->get();

            if ($residents->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active residents found'
                ], 400);
            }

            // Get settings for default values
            $defaultMonthlyFee = (float) SettingHelper::get('monthly_fee', 150000);
            $dueDateDay = (int) SettingHelper::get('due_date', 5);

            $generated = 0;
            $skipped = 0;

            DB::beginTransaction();

            try {
                foreach ($residents as $resident) {
                    foreach ($monthlyFees as $fee) {
                        // Check if payment already exists
                        $exists = EstateFeePayment::where('resident_id', $resident->id)
                            ->where('fee_id', $fee->id)
                            ->where('period_year', $periodYear)
                            ->where('period_month', $periodMonth)
                            ->exists();

                        if (!$exists) {
                            // Generate payment
                            $paymentNumber = $this->generatePaymentNumber();
                            
                            // Set due date from settings
                            $dueDate = \Carbon\Carbon::create($periodYear, $periodMonth, $dueDateDay);

                            // Use amount from fee or default from settings
                            $amount = $fee->amount > 0 ? $fee->amount : $defaultMonthlyFee;

                            EstateFeePayment::create([
                                'payment_number' => $paymentNumber,
                                'resident_id' => $resident->id,
                                'house_number' => $resident->house_number,
                                'fee_id' => $fee->id,
                                'period_month' => $periodMonth,
                                'period_year' => $periodYear,
                                'amount' => $amount,
                                'penalty_amount' => 0,
                                'total_amount' => $amount,
                                'payment_date' => null,
                                'payment_method' => null,
                                'status' => 'pending',
                                'due_date' => $dueDate->toDateString(),
                                'recorded_by' => Auth::guard('admin')->id(),
                            ]);

                            $generated++;
                        } else {
                            $skipped++;
                        }
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Bulk payment generation completed',
                    'data' => [
                        'period' => "{$periodYear}-{$periodMonth}",
                        'generated' => $generated,
                        'skipped' => $skipped,
                        'total_residents' => $residents->count(),
                        'total_fees' => $monthlyFees->count(),
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate bulk payments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment statistics
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            
            if ($period === 'week') {
                $dateFrom = now()->startOfWeek();
            } elseif ($period === 'year') {
                $dateFrom = now()->startOfYear();
            } else {
                $dateFrom = now()->startOfMonth();
            }

            $stats = [
                'total_payments' => EstateFeePayment::whereDate('payment_date', '>=', $dateFrom->toDateString())->count(),
                'total_amount_paid' => EstateFeePayment::where('status', 'paid')
                    ->whereDate('payment_date', '>=', $dateFrom->toDateString())
                    ->sum('amount'),
                'total_amount_pending' => EstateFeePayment::where('status', 'pending')
                    ->sum('amount'),
                'total_amount_overdue' => EstateFeePayment::where('status', 'overdue')
                    ->sum('amount'),
                'payment_by_status' => EstateFeePayment::whereDate('payment_date', '>=', $dateFrom->toDateString())
                    ->select('status', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                    ->groupBy('status')
                    ->get(),
                'payment_by_method' => EstateFeePayment::where('status', 'paid')
                    ->whereDate('payment_date', '>=', $dateFrom->toDateString())
                    ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
                    ->groupBy('payment_method')
                    ->get(),
                'overdue_count' => EstateFeePayment::where('status', 'overdue')
                    ->orWhere(function ($query) {
                        $query->where('status', 'pending')
                              ->where('due_date', '<', now()->toDateString());
                    })
                    ->count(),
                'active_fees' => EstateFee::where('is_active', true)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique payment number
     * Format: PAY-YYYY-####
     * 
     * @return string
     */
    private function generatePaymentNumber(): string
    {
        $year = now()->year;
        $prefix = "PAY-{$year}-";
        
        // Get last payment number for current year
        $lastPayment = EstateFeePayment::where('payment_number', 'like', "{$prefix}%")
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
