<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\EstateResident;
use App\Modules\Perumahan\Models\EstateFeePayment;
use App\Modules\Perumahan\Models\EstateService;
use App\Modules\Perumahan\Requests\StoreResidentRequest;
use App\Modules\Perumahan\Requests\UpdateResidentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResidentController extends Controller
{
    /**
     * Get residents list with filters
     */
    public function index(Request $request)
    {
        $query = EstateResident::query();

        // Filters
        if ($request->has('house_number')) {
            $query->where('house_number', 'like', '%' . $request->house_number . '%');
        }

        if ($request->has('house_status')) {
            $query->where('house_status', $request->house_status);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('owner_name', 'like', '%' . $search . '%')
                  ->orWhere('owner_phone', 'like', '%' . $search . '%')
                  ->orWhere('house_number', 'like', '%' . $search . '%');
            });
        }

        $perPage = $request->input('limit', 20);
        $residents = $query->orderBy('house_number')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'residents' => $residents->items(),
                'pagination' => [
                    'current_page' => $residents->currentPage(),
                    'total_pages' => $residents->lastPage(),
                    'total_items' => $residents->total(),
                    'per_page' => $residents->perPage(),
                ],
            ],
        ]);
    }

    /**
     * Create new resident
     */
    public function store(StoreResidentRequest $request)
    {
        try {
            $resident = EstateResident::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Penghuni berhasil ditambahkan',
                'data' => $resident,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan penghuni',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get resident details
     */
    public function show($id)
    {
        $resident = EstateResident::find($id);

        if (!$resident) {
            return response()->json([
                'success' => false,
                'message' => 'Resident not found',
            ], 404);
        }

        // Get payment summary
        $paymentSummary = [
            'total_paid' => EstateFeePayment::where('resident_id', $id)->paid()->count(),
            'total_pending' => EstateFeePayment::where('resident_id', $id)->pending()->count(),
            'last_payment_date' => EstateFeePayment::where('resident_id', $id)
                ->paid()
                ->orderBy('payment_date', 'desc')
                ->value('payment_date'),
        ];

        // Get recent service requests
        $serviceRequests = EstateService::where('resident_id', $id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get(['ticket_number', 'category', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'resident' => $resident,
                'payment_summary' => $paymentSummary,
                'service_requests' => $serviceRequests,
            ],
        ]);
    }

    /**
     * Update resident
     */
    public function update(UpdateResidentRequest $request, $id)
    {
        $resident = EstateResident::find($id);

        if (!$resident) {
            return response()->json([
                'success' => false,
                'message' => 'Resident not found',
            ], 404);
        }

        try {
            $resident->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Penghuni berhasil diperbarui',
                'data' => $resident->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui penghuni',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete resident (soft delete - set status to inactive)
     */
    public function destroy($id)
    {
        $resident = EstateResident::find($id);

        if (!$resident) {
            return response()->json([
                'success' => false,
                'message' => 'Resident not found',
            ], 404);
        }

        $resident->update(['status' => 'inactive']);

        return response()->json([
            'success' => true,
            'message' => 'Resident deactivated successfully',
        ]);
    }

    /**
     * Get resident statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        try {
            $total = EstateResident::count();
            $active = EstateResident::where('status', 'active')->count();
            $inactive = EstateResident::where('status', 'inactive')->count();

            // By house status
            $byHouseStatus = [
                'owner_occupied' => EstateResident::where('house_status', 'owner_occupied')->count(),
                'rented' => EstateResident::where('house_status', 'rented')->count(),
                'vacant' => EstateResident::where('house_status', 'vacant')->count(),
            ];

            // By house type
            $byHouseType = [
                'type_36' => EstateResident::where('house_type', '36')->count(),
                'type_45' => EstateResident::where('house_type', '45')->count(),
                'type_54' => EstateResident::whereIn('house_type', ['54', '60'])->count(),
            ];

            // Total occupants count
            $totalOccupants = EstateResident::where('status', 'active')->sum('total_occupants');

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'active' => $active,
                    'inactive' => $inactive,
                    'total_occupants' => (int) $totalOccupants,
                    'by_house_status' => $byHouseStatus,
                    'by_house_type' => $byHouseType,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get resident statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
