<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\EstateService;
use App\Modules\Perumahan\Models\EstateResident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * Get service requests with filters
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = EstateService::with('resident:id,house_number,owner_name');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Filter by priority
            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            // Filter by house number
            if ($request->filled('house_number')) {
                $query->where('house_number', 'like', '%' . $request->house_number . '%');
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                      ->orWhere('title', 'like', "%{$search}%")
                      ->orWhere('reporter_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $limit = $request->get('limit', 20);
            $services = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'services' => $services->items(),
                    'pagination' => [
                        'current_page' => $services->currentPage(),
                        'total_pages' => $services->lastPage(),
                        'total_items' => $services->total(),
                        'per_page' => $services->perPage(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single service request detail
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $service = EstateService::with('resident')->find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service request not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $service
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new service request
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'resident_id' => 'nullable|exists:estate_residents,id',
                'house_number' => 'required|string|max:10',
                'reporter_name' => 'required|string|max:100',
                'reporter_phone' => 'nullable|string|max:20',
                'category' => 'required|in:complaint,maintenance,facility_booking,information,emergency',
                'sub_category' => 'nullable|string|max:50',
                'title' => 'required|string|max:200',
                'description' => 'required|string',
                'location' => 'nullable|string|max:200',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'photos' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate ticket number
            $ticketNumber = $this->generateTicketNumber();

            $service = EstateService::create([
                'ticket_number' => $ticketNumber,
                'resident_id' => $request->resident_id,
                'house_number' => $request->house_number,
                'reporter_name' => $request->reporter_name,
                'reporter_phone' => $request->reporter_phone,
                'category' => $request->category,
                'sub_category' => $request->sub_category,
                'title' => $request->title,
                'description' => $request->description,
                'location' => $request->location,
                'priority' => $request->priority ?? 'medium',
                'status' => 'submitted',
                'photos' => $request->photos,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service request created successfully',
                'data' => $service
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update service request
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $service = EstateService::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service request not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'category' => 'sometimes|in:complaint,maintenance,facility_booking,information,emergency',
                'sub_category' => 'nullable|string|max:50',
                'title' => 'sometimes|string|max:200',
                'description' => 'sometimes|string',
                'location' => 'nullable|string|max:200',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'photos' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $service->update($request->only([
                'category', 'sub_category', 'title', 'description',
                'location', 'priority', 'photos'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Service request updated successfully',
                'data' => $service->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update service request status
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $service = EstateService::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service request not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:submitted,acknowledged,in_progress,resolved,closed,rejected',
                'assigned_to' => 'nullable|string|max:100',
                'resolution_notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = ['status' => $request->status];

            // Set assigned_at when status changes to acknowledged or in_progress
            if (in_array($request->status, ['acknowledged', 'in_progress']) && !$service->assigned_at) {
                $updateData['assigned_at'] = now();
            }

            // Set resolved_at when status changes to resolved
            if ($request->status === 'resolved' && !$service->resolved_at) {
                $updateData['resolved_at'] = now();
            }

            if ($request->filled('assigned_to')) {
                $updateData['assigned_to'] = $request->assigned_to;
            }

            if ($request->filled('resolution_notes')) {
                $updateData['resolution_notes'] = $request->resolution_notes;
            }

            $service->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Service request status updated successfully',
                'data' => $service->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service request status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add rating and feedback to resolved service
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addFeedback(Request $request, $id)
    {
        try {
            $service = EstateService::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service request not found'
                ], 404);
            }

            if (!in_array($service->status, ['resolved', 'closed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Feedback can only be added to resolved or closed services'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'feedback' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $service->update([
                'rating' => $request->rating,
                'feedback' => $request->feedback,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback added successfully',
                'data' => $service->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add feedback',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete service request
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $service = EstateService::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service request not found'
                ], 404);
            }

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service request deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service statistics
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
                'total_requests' => EstateService::whereDate('created_at', '>=', $dateFrom->toDateString())->count(),
                'by_status' => EstateService::whereDate('created_at', '>=', $dateFrom->toDateString())
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->get(),
                'by_category' => EstateService::whereDate('created_at', '>=', $dateFrom->toDateString())
                    ->select('category', DB::raw('count(*) as count'))
                    ->groupBy('category')
                    ->orderBy('count', 'desc')
                    ->get(),
                'by_priority' => EstateService::whereDate('created_at', '>=', $dateFrom->toDateString())
                    ->select('priority', DB::raw('count(*) as count'))
                    ->groupBy('priority')
                    ->get(),
                'average_rating' => EstateService::whereNotNull('rating')
                    ->whereDate('created_at', '>=', $dateFrom->toDateString())
                    ->avg('rating'),
                'pending_requests' => EstateService::whereIn('status', ['submitted', 'acknowledged', 'in_progress'])
                    ->count(),
                'urgent_requests' => EstateService::where('priority', 'urgent')
                    ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique ticket number
     * Format: EST-YYYY-####
     * 
     * @return string
     */
    private function generateTicketNumber(): string
    {
        $year = now()->year;
        $prefix = "EST-{$year}-";
        
        // Get last ticket number for current year
        $lastTicket = EstateService::where('ticket_number', 'like', "{$prefix}%")
            ->orderBy('ticket_number', 'desc')
            ->first();

        if ($lastTicket) {
            // Extract number and increment
            $lastNumber = (int) substr($lastTicket->ticket_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
