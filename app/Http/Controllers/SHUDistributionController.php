<?php

namespace App\Http\Controllers;

use App\Models\ShuDistribution;
use App\Models\ShuMemberAllocation;
use App\Models\ShuPercentageSetting;
use App\Models\Transaction;
use App\Services\SHUCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SHUDistributionController extends Controller
{
    protected $shuService;

    public function __construct(SHUCalculationService $shuService)
    {
        $this->shuService = $shuService;
    }

    /**
     * Get all SHU distributions with pagination
     * GET /api/shu-distributions
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            
            $query = ShuDistribution::with(['allocations', 'approver'])
                ->orderBy('fiscal_year', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            $distributions = $query->paginate($perPage);

            // Add computed fields
            $distributions->getCollection()->transform(function ($distribution) {
                return [
                    'id' => $distribution->id,
                    'fiscal_year' => $distribution->fiscal_year,
                    'total_shu_amount' => $distribution->total_shu_amount,
                    'cadangan_amount' => $distribution->cadangan_amount,
                    'jasa_modal_amount' => $distribution->jasa_modal_amount,
                    'jasa_usaha_amount' => $distribution->jasa_usaha_amount,
                    'distribution_date' => $distribution->distribution_date?->format('Y-m-d'),
                    'status' => $distribution->status,
                    'approved_at' => $distribution->approved_at?->format('Y-m-d H:i:s'),
                    'approved_by' => $distribution->approver?->full_name,
                    'notes' => $distribution->notes,
                    'total_members' => $distribution->total_members,
                    'paid_members' => $distribution->paid_members_count,
                    'payment_progress' => $distribution->payment_progress,
                    'total_paid_out' => $distribution->total_paid_out,
                    'total_unpaid' => $distribution->total_unpaid,
                ];
            });

            return response()->json($distributions);

        } catch (\Exception $e) {
            Log::error('Error fetching SHU distributions', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch distributions'], 500);
        }
    }

    /**
     * Get specific SHU distribution by ID
     * GET /api/shu-distributions/{id}
     */
    public function show($id)
    {
        try {
            $distribution = ShuDistribution::with(['allocations.member', 'approver'])->findOrFail($id);
            
            return response()->json([
                'data' => $distribution,
                'summary' => $this->shuService->getDistributionSummary($distribution),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Distribution not found'], 404);
        }
    }

    /**
     * Create new SHU distribution (Step 1)
     * POST /api/shu-distributions
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fiscal_year' => 'required|integer|min:2000|max:2100|unique:shu_distributions,fiscal_year',
            'total_shu_amount' => 'required|numeric|min:0',
            'setting_id' => 'required|exists:shu_percentage_settings,id',
            'distribution_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get setting
            $setting = ShuPercentageSetting::findOrFail($request->setting_id);

            // Validate setting is for correct fiscal year
            if ($setting->fiscal_year != $request->fiscal_year) {
                return response()->json([
                    'error' => 'Setting fiscal year does not match distribution fiscal year'
                ], 422);
            }

            // Hitung breakdown SHU dengan setting
            $breakdown = $this->shuService->calculateDistribution(
                $request->fiscal_year,
                $request->total_shu_amount,
                $setting
            );

            // Buat distribution record
            $distribution = ShuDistribution::create([
                'fiscal_year' => $request->fiscal_year,
                'setting_id' => $request->setting_id,
                'total_shu_amount' => $request->total_shu_amount,
                'cadangan_amount' => $breakdown['cadangan_amount'],
                'jasa_modal_amount' => $breakdown['jasa_modal_amount'],
                'jasa_usaha_amount' => $breakdown['jasa_usaha_amount'],
                'distribution_date' => $request->distribution_date,
                'status' => 'draft',
                'notes' => $request->notes ?? null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'SHU Distribution created successfully',
                'data' => $distribution->load('setting'),
                'breakdown' => $breakdown,
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating SHU distribution', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update SHU distribution
     * PUT /api/shu-distributions/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'total_shu_amount' => 'sometimes|numeric|min:0',
            'distribution_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $distribution = ShuDistribution::findOrFail($id);

            // Only draft distributions can be updated
            if ($distribution->status !== 'draft') {
                return response()->json([
                    'error' => 'Only draft distributions can be updated'
                ], 422);
            }

            DB::beginTransaction();

            // If total_shu_amount changed, recalculate breakdown
            if ($request->has('total_shu_amount')) {
                $setting = $distribution->setting;
                
                if (!$setting) {
                    return response()->json([
                        'error' => 'Setting not found for this distribution'
                    ], 422);
                }
                
                $breakdown = $this->shuService->calculateDistribution(
                    $distribution->fiscal_year,
                    $request->total_shu_amount,
                    $setting
                );

                $distribution->update([
                    'total_shu_amount' => $request->total_shu_amount,
                    'cadangan_amount' => $breakdown['cadangan_amount'],
                    'jasa_modal_amount' => $breakdown['jasa_modal_amount'],
                    'jasa_usaha_amount' => $breakdown['jasa_usaha_amount'],
                ]);

                // Delete existing allocations if any (need recalculation)
                $distribution->allocations()->delete();
            }

            // Update other fields
            $distribution->update($request->only(['distribution_date', 'notes']));

            DB::commit();

            return response()->json([
                'message' => 'SHU Distribution updated successfully',
                'data' => $distribution,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating SHU distribution', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete SHU distribution
     * DELETE /api/shu-distributions/{id}
     */
    public function destroy($id)
    {
        try {
            $distribution = ShuDistribution::findOrFail($id);

            // Only draft distributions can be deleted
            if ($distribution->status !== 'draft') {
                return response()->json([
                    'error' => 'Only draft distributions can be deleted'
                ], 422);
            }

            DB::beginTransaction();

            // Delete allocations first
            $distribution->allocations()->delete();
            
            // Delete distribution
            $distribution->delete();

            DB::commit();

            return response()->json([
                'message' => 'SHU Distribution deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error deleting SHU distribution', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Calculate member allocations (Step 2)
     * POST /api/shu-distributions/{id}/calculate
     */
    public function calculateAllocations($id)
    {
        try {
            $distribution = ShuDistribution::findOrFail($id);

            if ($distribution->status !== 'draft') {
                return response()->json([
                    'error' => 'Can only calculate allocations for draft distributions'
                ], 422);
            }

            // Hitung alokasi
            $allocations = $this->shuService->calculateMemberAllocations($distribution);

            // Simpan ke database
            $this->shuService->saveAllocations($distribution, $allocations);

            // Reload dengan relasi
            $distribution->load('allocations.member');

            return response()->json([
                'message' => 'Allocations calculated and saved successfully',
                'data' => $distribution,
                'summary' => $this->shuService->getDistributionSummary($distribution),
                'allocations' => $allocations,
            ]);

        } catch (\Exception $e) {
            Log::error('Error calculating allocations', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get allocations for a distribution
     * GET /api/shu-distributions/{id}/allocations
     */
    public function getAllocations($id, Request $request)
    {
        try {
            $distribution = ShuDistribution::findOrFail($id);
            $perPage = $request->get('per_page', 15);
            $paidStatus = $request->get('is_paid_out'); // filter: true, false, or null (all)

            $query = ShuMemberAllocation::where('shu_distribution_id', $id)
                ->with(['member', 'payoutTransaction']);

            if ($paidStatus !== null) {
                $query->where('is_paid_out', filter_var($paidStatus, FILTER_VALIDATE_BOOLEAN));
            }

            $allocations = $query->orderBy('amount_allocated', 'desc')
                ->paginate($perPage);

            return response()->json($allocations);

        } catch (\Exception $e) {
            Log::error('Error fetching allocations', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve distribution (Step 3)
     * POST /api/shu-distributions/{id}/approve
     */
    public function approve($id, Request $request)
    {
        try {
            $distribution = ShuDistribution::findOrFail($id);

            if ($distribution->status !== 'draft') {
                return response()->json([
                    'error' => 'Can only approve draft distributions. Current status: ' . $distribution->status
                ], 422);
            }

            if ($distribution->allocations->count() === 0) {
                return response()->json([
                    'error' => 'No allocations found. Please calculate allocations first.'
                ], 422);
            }

            // Get authenticated member ID (assuming you have auth middleware)
            // For now, we'll accept it from request or use first member
            $approvedBy = $request->input('approved_by') ?? $request->user()?->id;

            $distribution->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
            ]);

            return response()->json([
                'message' => 'SHU Distribution approved successfully',
                'data' => $distribution->load('approver'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error approving distribution', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Batch payout SHU to all unpaid members (Step 4)
     * POST /api/shu-distributions/{id}/payout
     */
    public function batchPayout($id, Request $request)
    {
        try {
            $distribution = ShuDistribution::findOrFail($id);

            // Validate if distribution can be paid out
            $validation = $this->shuService->validateForPayout($distribution);
            if (!$validation['can_payout']) {
                return response()->json(['error' => $validation['message']], 422);
            }

            DB::beginTransaction();

            // Get all unpaid allocations
            $allocations = ShuMemberAllocation::where('shu_distribution_id', $id)
                ->where('is_paid_out', false)
                ->with('member.savingsAccounts')
                ->get();

            $paidCount = 0;
            $paidAmount = 0;
            $errors = [];

            foreach ($allocations as $allocation) {
                try {
                    // Get member's first savings account
                    $savingsAccount = $allocation->member->savingsAccounts->first();
                    
                    if (!$savingsAccount) {
                        $errors[] = [
                            'member_id' => $allocation->member_id,
                            'member_name' => $allocation->member->full_name,
                            'error' => 'No savings account found'
                        ];
                        continue;
                    }

                    // Create transaction
                    $transaction = Transaction::create([
                        'savings_account_id' => $savingsAccount->id,
                        'member_id' => $allocation->member_id,
                        'transaction_type' => 'shu_distribution',
                        'amount' => $allocation->amount_allocated,
                        'description' => "Pembayaran SHU Tahun {$distribution->fiscal_year}",
                        'transaction_date' => now(),
                    ]);

                    // Update savings balance
                    $savingsAccount->balance += $allocation->amount_allocated;
                    $savingsAccount->save();

                    // Update allocation status
                    $allocation->update([
                        'is_paid_out' => true,
                        'payout_transaction_id' => $transaction->id,
                        'paid_out_at' => now(),
                    ]);

                    $paidCount++;
                    $paidAmount += $allocation->amount_allocated;

                } catch (\Exception $e) {
                    $errors[] = [
                        'member_id' => $allocation->member_id,
                        'member_name' => $allocation->member->full_name,
                        'error' => $e->getMessage()
                    ];
                    Log::error('Error processing payout for member', [
                        'member_id' => $allocation->member_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update distribution status if all paid
            $remainingUnpaid = ShuMemberAllocation::where('shu_distribution_id', $id)
                ->where('is_paid_out', false)
                ->count();

            if ($remainingUnpaid === 0) {
                $distribution->update(['status' => 'paid_out']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Batch payout completed',
                'paid_count' => $paidCount,
                'paid_amount' => round($paidAmount, 2),
                'errors' => $errors,
                'distribution_status' => $distribution->fresh()->status,
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error during batch payout', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get SHU distribution report
     * GET /api/shu-distributions/{id}/report
     */
    public function report($id)
    {
        try {
            $distribution = ShuDistribution::with([
                'allocations.member',
                'allocations.payoutTransaction',
                'approver'
            ])->findOrFail($id);

            $summary = $this->shuService->getDistributionSummary($distribution);

            // Additional statistics
            $topMembers = $distribution->allocations()
                ->with('member')
                ->orderBy('amount_allocated', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($allocation) {
                    return [
                        'member_name' => $allocation->member->full_name,
                        'member_number' => $allocation->member->member_number ?? 'N/A',
                        'amount_allocated' => $allocation->amount_allocated,
                        'jasa_modal' => $allocation->jasa_modal_amount,
                        'jasa_usaha' => $allocation->jasa_usaha_amount,
                        'is_paid_out' => $allocation->is_paid_out,
                    ];
                });

            return response()->json([
                'summary' => $summary,
                'top_members' => $topMembers,
                'distribution_details' => [
                    'total_shu' => $distribution->total_shu_amount,
                    'cadangan' => $distribution->cadangan_amount,
                    'for_members' => $distribution->jasa_modal_amount + $distribution->jasa_usaha_amount,
                    'breakdown' => [
                        'jasa_modal' => $distribution->jasa_modal_amount,
                        'jasa_usaha' => $distribution->jasa_usaha_amount,
                    ]
                ],
                'payment_status' => [
                    'paid_members' => $distribution->paid_members_count,
                    'unpaid_members' => $distribution->total_members - $distribution->paid_members_count,
                    'paid_amount' => $distribution->total_paid_out,
                    'unpaid_amount' => $distribution->total_unpaid,
                    'progress_percentage' => $distribution->payment_progress,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating report', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
