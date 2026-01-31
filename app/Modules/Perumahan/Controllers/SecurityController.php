<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\EstateSecurityLog;
use App\Modules\Perumahan\Models\EstateResident;
use App\Helpers\SettingHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    /**
     * Get security logs with advanced filtering
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLogs(Request $request)
    {
        try {
            $query = EstateSecurityLog::with('resident:id,house_number,owner_name');

            // Filter by log type
            if ($request->has('log_type')) {
                $query->where('log_type', $request->log_type);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('log_datetime', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('log_datetime', '<=', $request->date_to);
            }

            // Filter by house number
            if ($request->filled('house_number')) {
                $query->where('house_number', 'like', '%' . $request->house_number . '%');
            }

            // Filter by incident status (for incidents only)
            if ($request->has('incident_status')) {
                $query->where('log_type', 'incident')
                      ->where('incident_status', $request->incident_status);
            }

            // Filter by guard shift
            if ($request->has('guard_shift')) {
                $query->where('guard_shift', $request->guard_shift);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('visitor_name', 'like', "%{$search}%")
                      ->orWhere('guard_name', 'like', "%{$search}%")
                      ->orWhere('vehicle_plate', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'log_datetime');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $limit = $request->get('limit', 20);
            $logs = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs->items(),
                    'pagination' => [
                        'current_page' => $logs->currentPage(),
                        'total_pages' => $logs->lastPage(),
                        'total_items' => $logs->total(),
                        'per_page' => $logs->perPage(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch security logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single security log detail
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $log = EstateSecurityLog::with('resident')->find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Security log not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $log
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch security log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new security log (entry/exit/patrol)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'log_type' => 'required|in:entry,exit,incident,patrol',
                'log_datetime' => 'required|date',
                
                // Entry/Exit fields
                'resident_id' => 'nullable|exists:estate_residents,id',
                'house_number' => 'nullable|string|max:10',
                'visitor_name' => 'nullable|string|max:100',
                'visitor_phone' => 'nullable|string|max:20',
                'visitor_purpose' => 'nullable|string',
                'vehicle_plate' => 'nullable|string|max:20',
                
                // Incident fields
                'incident_type' => 'nullable|string|max:50',
                'incident_description' => 'nullable|string',
                'incident_severity' => 'nullable|in:low,medium,high',
                'incident_status' => 'nullable|in:open,investigating,resolved',
                
                // Patrol fields
                'patrol_area' => 'nullable|string|max:50',
                'patrol_notes' => 'nullable|string',
                
                // Common fields
                'guard_name' => 'nullable|string|max:100',
                'guard_shift' => 'nullable|in:morning,afternoon,night',
                'notes' => 'nullable|string',
                'photo_url' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $log = EstateSecurityLog::create([
                'log_type' => $request->log_type,
                'log_datetime' => $request->log_datetime,
                'resident_id' => $request->resident_id,
                'house_number' => $request->house_number,
                'visitor_name' => $request->visitor_name,
                'visitor_phone' => $request->visitor_phone,
                'visitor_purpose' => $request->visitor_purpose,
                'vehicle_plate' => $request->vehicle_plate,
                'incident_type' => $request->incident_type,
                'incident_description' => $request->incident_description,
                'incident_severity' => $request->incident_severity ?? 'low',
                'incident_status' => $request->incident_status ?? 'open',
                'patrol_area' => $request->patrol_area,
                'patrol_notes' => $request->patrol_notes,
                'guard_name' => $request->guard_name,
                'guard_shift' => $request->guard_shift,
                'notes' => $request->notes,
                'photo_url' => $request->photo_url,
                'created_by' => Auth::guard('admin')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Security log created successfully',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create security log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update security log
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $log = EstateSecurityLog::find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Security log not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'log_type' => 'sometimes|in:entry,exit,incident,patrol',
                'log_datetime' => 'sometimes|date',
                'resident_id' => 'nullable|exists:estate_residents,id',
                'house_number' => 'nullable|string|max:10',
                'visitor_name' => 'nullable|string|max:100',
                'visitor_phone' => 'nullable|string|max:20',
                'visitor_purpose' => 'nullable|string',
                'vehicle_plate' => 'nullable|string|max:20',
                'incident_type' => 'nullable|string|max:50',
                'incident_description' => 'nullable|string',
                'incident_severity' => 'nullable|in:low,medium,high',
                'incident_status' => 'nullable|in:open,investigating,resolved',
                'patrol_area' => 'nullable|string|max:50',
                'patrol_notes' => 'nullable|string',
                'guard_name' => 'nullable|string|max:100',
                'guard_shift' => 'nullable|in:morning,afternoon,night',
                'notes' => 'nullable|string',
                'photo_url' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $log->update($request->only([
                'log_type', 'log_datetime', 'resident_id', 'house_number',
                'visitor_name', 'visitor_phone', 'visitor_purpose', 'vehicle_plate',
                'incident_type', 'incident_description', 'incident_severity', 'incident_status',
                'patrol_area', 'patrol_notes', 'guard_name', 'guard_shift', 'notes', 'photo_url'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Security log updated successfully',
                'data' => $log->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update security log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete security log
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $log = EstateSecurityLog::find($id);

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'message' => 'Security log not found'
                ], 404);
            }

            $log->delete();

            return response()->json([
                'success' => true,
                'message' => 'Security log deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete security log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all security incidents (filtered list)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIncidents(Request $request)
    {
        try {
            $query = EstateSecurityLog::where('log_type', 'incident')
                ->with('resident:id,house_number,owner_name');

            // Filter by status
            if ($request->has('status')) {
                $query->where('incident_status', $request->status);
            }

            // Filter by severity
            if ($request->has('severity')) {
                $query->where('incident_severity', $request->severity);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('log_datetime', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('log_datetime', '<=', $request->date_to);
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('incident_type', 'like', "%{$search}%")
                      ->orWhere('incident_description', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            $limit = $request->get('limit', 20);
            $incidents = $query->orderBy('log_datetime', 'desc')->paginate($limit);

            // Transform to match spec
            $formattedIncidents = $incidents->getCollection()->map(function ($incident) {
                return [
                    'id' => $incident->id,
                    'incident_number' => 'INC-' . $incident->id,
                    'incident_datetime' => $incident->log_datetime,
                    'location' => $incident->house_number ?? $incident->patrol_area ?? 'Unknown',
                    'description' => $incident->incident_description,
                    'severity' => $incident->incident_severity ?? 'low',
                    'status' => $incident->incident_status ?? 'open',
                    'reported_by' => $incident->guard_name ?? 'Security',
                    'assigned_to' => null,
                    'resolution' => $incident->notes,
                    'resolved_at' => $incident->incident_status === 'resolved' ? $incident->updated_at : null,
                    'created_at' => $incident->created_at,
                    'updated_at' => $incident->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'incidents' => $formattedIncidents,
                    'pagination' => [
                        'current_page' => $incidents->currentPage(),
                        'total_pages' => $incidents->lastPage(),
                        'total_items' => $incidents->total(),
                        'per_page' => $incidents->perPage(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch security incidents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active incidents
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveIncidents()
    {
        try {
            $incidents = EstateSecurityLog::where('log_type', 'incident')
                ->whereIn('incident_status', ['open', 'investigating'])
                ->with('resident:id,house_number,owner_name')
                ->orderBy('incident_severity', 'desc')
                ->orderBy('log_datetime', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $incidents
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active incidents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get security statistics
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request)
    {
        try {
            $period = $request->get('period', 'month'); // week, month, year
            
            $dateFrom = match($period) {
                'week' => now()->startOfWeek(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            };

            $stats = [
                'total_entries' => EstateSecurityLog::where('log_type', 'entry')
                    ->where('log_datetime', '>=', $dateFrom)
                    ->count(),
                'total_exits' => EstateSecurityLog::where('log_type', 'exit')
                    ->where('log_datetime', '>=', $dateFrom)
                    ->count(),
                'total_incidents' => EstateSecurityLog::where('log_type', 'incident')
                    ->where('log_datetime', '>=', $dateFrom)
                    ->count(),
                'active_incidents' => EstateSecurityLog::where('log_type', 'incident')
                    ->whereIn('incident_status', ['open', 'investigating'])
                    ->count(),
                'total_patrols' => EstateSecurityLog::where('log_type', 'patrol')
                    ->where('log_datetime', '>=', $dateFrom)
                    ->count(),
                'incidents_by_severity' => EstateSecurityLog::where('log_type', 'incident')
                    ->where('log_datetime', '>=', $dateFrom)
                    ->select('incident_severity', DB::raw('count(*) as count'))
                    ->groupBy('incident_severity')
                    ->get(),
                'incidents_by_type' => EstateSecurityLog::where('log_type', 'incident')
                    ->where('log_datetime', '>=', $dateFrom)
                    ->select('incident_type', DB::raw('count(*) as count'))
                    ->groupBy('incident_type')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch security statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate patrol schedule based on settings
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generatePatrolSchedule(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'guard_names' => 'nullable|array',
                'guard_names.*' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $date = $request->input('date');
            $guardNames = $request->input('guard_names', []);

            // Get settings
            $shiftsPerDay = (int) SettingHelper::get('shifts_per_day', 3);
            $patrolInterval = (int) SettingHelper::get('patrol_interval', 60);
            $autoPatrolLog = SettingHelper::get('auto_patrol_log', true);

            if (!$autoPatrolLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Auto patrol log is disabled in settings'
                ], 400);
            }

            $schedules = [];

            // Generate schedules based on shifts
            if ($shiftsPerDay === 2) {
                // 2 shifts: 07:00-19:00, 19:00-07:00
                $shifts = [
                    ['start' => '07:00', 'end' => '19:00', 'name' => 'Pagi-Siang', 'guard_index' => 0],
                    ['start' => '19:00', 'end' => '07:00', 'name' => 'Malam', 'guard_index' => 1],
                ];
            } else {
                // 3 shifts: 07:00-15:00, 15:00-23:00, 23:00-07:00
                $shifts = [
                    ['start' => '07:00', 'end' => '15:00', 'name' => 'Pagi', 'guard_index' => 0],
                    ['start' => '15:00', 'end' => '23:00', 'name' => 'Siang', 'guard_index' => 1],
                    ['start' => '23:00', 'end' => '07:00', 'name' => 'Malam', 'guard_index' => 2],
                ];
            }

            foreach ($shifts as $shift) {
                $guardName = isset($guardNames[$shift['guard_index']]) 
                    ? $guardNames[$shift['guard_index']] 
                    : 'Satpam ' . ($shift['guard_index'] + 1);

                $schedules[] = [
                    'date' => $date,
                    'shift_name' => $shift['name'],
                    'start_time' => $shift['start'],
                    'end_time' => $shift['end'],
                    'guard_name' => $guardName,
                    'patrol_interval_minutes' => $patrolInterval,
                    'expected_patrols' => $this->calculateExpectedPatrols($shift['start'], $shift['end'], $patrolInterval),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Patrol schedule generated successfully',
                'data' => [
                    'schedules' => $schedules,
                    'settings' => [
                        'shifts_per_day' => $shiftsPerDay,
                        'patrol_interval' => $patrolInterval,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate patrol schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate expected number of patrols based on shift duration and interval
     * 
     * @param string $startTime
     * @param string $endTime
     * @param int $intervalMinutes
     * @return int
     */
    private function calculateExpectedPatrols($startTime, $endTime, $intervalMinutes): int
    {
        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($endTime);

        // Handle overnight shifts
        if ($end->lt($start)) {
            $end->addDay();
        }

        $durationMinutes = $start->diffInMinutes($end);
        return (int) floor($durationMinutes / $intervalMinutes);
    }}