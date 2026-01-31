<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\EstateWasteSchedule;
use App\Modules\Perumahan\Models\EstateWasteCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WasteController extends Controller
{
    /**
     * Get waste schedules
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSchedules(Request $request)
    {
        try {
            $query = EstateWasteSchedule::query();

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by day of week
            if ($request->has('day_of_week')) {
                $query->where('day_of_week', $request->day_of_week);
            }

            // Filter by waste type
            if ($request->has('waste_type')) {
                $query->where('waste_type', $request->waste_type);
            }

            // Sorting
            $query->orderBy('day_of_week', 'asc')
                  ->orderBy('time', 'asc');

            $schedules = $query->get();

            return response()->json([
                'success' => true,
                'data' => $schedules
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch waste schedules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single waste schedule
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showSchedule($id)
    {
        try {
            $schedule = EstateWasteSchedule::find($id);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waste schedule not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $schedule
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch waste schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new waste schedule
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeSchedule(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'schedule_name' => 'required|string|max:100',
                'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'time' => 'required|date_format:H:i',
                'waste_type' => 'required|in:organic,non_organic,recyclable,mixed',
                'coverage_area' => 'nullable|json',
                'is_active' => 'boolean',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $schedule = EstateWasteSchedule::create([
                'schedule_name' => $request->schedule_name,
                'day_of_week' => $request->day_of_week,
                'time' => $request->time,
                'waste_type' => $request->waste_type,
                'coverage_area' => $request->coverage_area,
                'is_active' => $request->get('is_active', true),
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Waste schedule created successfully',
                'data' => $schedule
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create waste schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update waste schedule
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSchedule(Request $request, $id)
    {
        try {
            $schedule = EstateWasteSchedule::find($id);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waste schedule not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'schedule_name' => 'sometimes|string|max:100',
                'day_of_week' => 'sometimes|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'time' => 'sometimes|date_format:H:i',
                'waste_type' => 'sometimes|in:organic,non_organic,recyclable,mixed',
                'coverage_area' => 'nullable|json',
                'is_active' => 'boolean',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $schedule->update($request->only([
                'schedule_name', 'day_of_week', 'time', 'waste_type',
                'coverage_area', 'is_active', 'notes'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Waste schedule updated successfully',
                'data' => $schedule->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update waste schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete waste schedule
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroySchedule($id)
    {
        try {
            $schedule = EstateWasteSchedule::find($id);

            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waste schedule not found'
                ], 404);
            }

            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Waste schedule deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete waste schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get waste collections
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCollections(Request $request)
    {
        try {
            $query = EstateWasteCollection::with('schedule');

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('collection_date', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('collection_date', '<=', $request->date_to);
            }

            // Filter by schedule
            if ($request->has('schedule_id')) {
                $query->where('schedule_id', $request->schedule_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'collection_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $limit = $request->get('limit', 20);
            $collections = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'collections' => $collections->items(),
                    'pagination' => [
                        'current_page' => $collections->currentPage(),
                        'total_pages' => $collections->lastPage(),
                        'total_items' => $collections->total(),
                        'per_page' => $collections->perPage(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch waste collections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single waste collection
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCollection($id)
    {
        try {
            $collection = EstateWasteCollection::with('schedule')->find($id);

            if (!$collection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waste collection not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $collection
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch waste collection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record waste collection
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCollection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'schedule_id' => 'nullable|exists:estate_waste_schedules,id',
                'collection_date' => 'required|date',
                'collection_time' => 'nullable|date_format:H:i',
                'collector_name' => 'nullable|string|max:100',
                'houses_collected' => 'nullable|json',
                'houses_skipped' => 'nullable|json',
                'total_weight' => 'nullable|numeric|min:0',
                'status' => 'required|in:scheduled,completed,cancelled,delayed',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $collection = EstateWasteCollection::create([
                'schedule_id' => $request->schedule_id,
                'collection_date' => $request->collection_date,
                'collection_time' => $request->collection_time,
                'collector_name' => $request->collector_name,
                'houses_collected' => $request->houses_collected,
                'houses_skipped' => $request->houses_skipped,
                'total_weight' => $request->total_weight,
                'status' => $request->status,
                'notes' => $request->notes,
                'recorded_by' => Auth::guard('admin')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Waste collection recorded successfully',
                'data' => $collection->load('schedule')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record waste collection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update waste collection
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCollection(Request $request, $id)
    {
        try {
            $collection = EstateWasteCollection::find($id);

            if (!$collection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waste collection not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'schedule_id' => 'sometimes|exists:estate_waste_schedules,id',
                'collection_date' => 'sometimes|date',
                'collection_time' => 'nullable|date_format:H:i',
                'collector_name' => 'nullable|string|max:100',
                'houses_collected' => 'nullable|json',
                'houses_skipped' => 'nullable|json',
                'total_weight' => 'nullable|numeric|min:0',
                'status' => 'sometimes|in:scheduled,completed,cancelled,delayed',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $collection->update($request->only([
                'schedule_id', 'collection_date', 'collection_time', 'collector_name',
                'houses_collected', 'houses_skipped', 'total_weight', 'status', 'notes'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Waste collection updated successfully',
                'data' => $collection->fresh()->load('schedule')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update waste collection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete waste collection
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyCollection($id)
    {
        try {
            $collection = EstateWasteCollection::find($id);

            if (!$collection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waste collection not found'
                ], 404);
            }

            $collection->delete();

            return response()->json([
                'success' => true,
                'message' => 'Waste collection deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete waste collection',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get waste collection statistics
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request)
    {
        try {
            $period = $request->get('period', 'month'); // week, month, year
            
            if ($period === 'week') {
                $dateFrom = now()->startOfWeek();
            } elseif ($period === 'year') {
                $dateFrom = now()->startOfYear();
            } else {
                $dateFrom = now()->startOfMonth();
            }

            $stats = [
                'total_collections' => EstateWasteCollection::whereDate('collection_date', '>=', $dateFrom->toDateString())->count(),
                'completed_collections' => EstateWasteCollection::whereDate('collection_date', '>=', $dateFrom->toDateString())
                    ->where('status', 'completed')
                    ->count(),
                'cancelled_collections' => EstateWasteCollection::whereDate('collection_date', '>=', $dateFrom->toDateString())
                    ->where('status', 'cancelled')
                    ->count(),
                'delayed_collections' => EstateWasteCollection::whereDate('collection_date', '>=', $dateFrom->toDateString())
                    ->where('status', 'delayed')
                    ->count(),
                'total_weight_collected' => EstateWasteCollection::whereDate('collection_date', '>=', $dateFrom->toDateString())
                    ->where('status', 'completed')
                    ->sum('total_weight'),
                'active_schedules' => EstateWasteSchedule::where('is_active', true)->count(),
                'collections_by_status' => EstateWasteCollection::whereDate('collection_date', '>=', $dateFrom->toDateString())
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->get(),
                'upcoming_collections' => EstateWasteSchedule::where('is_active', true)
                    ->orderBy('day_of_week', 'asc')
                    ->orderBy('time', 'asc')
                    ->limit(5)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch waste statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's collection schedule
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTodaySchedule()
    {
        try {
            $today = strtolower(now()->format('l')); // 'monday', 'tuesday', etc.
            
            $schedules = EstateWasteSchedule::where('day_of_week', $today)
                ->where('is_active', true)
                ->orderBy('time', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => now()->toDateString(),
                    'day' => $today,
                    'schedules' => $schedules
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch today\'s schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
