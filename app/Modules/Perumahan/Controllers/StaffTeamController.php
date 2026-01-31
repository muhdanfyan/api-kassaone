<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\PerumahanStaffTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StaffTeamController extends Controller
{
    /**
     * Get list of staff teams
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = PerumahanStaffTeam::query();

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $teams = $query->orderBy('team_code')->get();

            return response()->json([
                'success' => true,
                'data' => $teams
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch staff teams',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single staff team
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $team = PerumahanStaffTeam::find($id);

            if (!$team) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff team not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $team
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch staff team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new staff team
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'team_name' => 'required|string|max:100',
                'team_code' => 'required|string|max:20|unique:perumahan_staff_teams,team_code',
                'description' => 'nullable|string',
                'member_count' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $team = PerumahanStaffTeam::create([
                'team_name' => $request->team_name,
                'team_code' => $request->team_code,
                'description' => $request->description,
                'member_count' => $request->get('member_count', 0),
                'is_active' => $request->get('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Staff team created successfully',
                'data' => $team
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create staff team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update staff team
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $team = PerumahanStaffTeam::find($id);

            if (!$team) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff team not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'team_name' => 'sometimes|string|max:100',
                'team_code' => 'sometimes|string|max:20|unique:perumahan_staff_teams,team_code,' . $id,
                'description' => 'nullable|string',
                'member_count' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $team->update($request->only([
                'team_name', 'team_code', 'description', 'member_count', 'is_active'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Staff team updated successfully',
                'data' => $team->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update staff team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete staff team
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $team = PerumahanStaffTeam::find($id);

            if (!$team) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff team not found'
                ], 404);
            }

            // Check if team is assigned to any waste schedule
            if ($team->isAssignedToSchedules()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete staff team. It is currently assigned to waste schedules.'
                ], 400);
            }

            $team->delete();

            return response()->json([
                'success' => true,
                'message' => 'Staff team deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete staff team',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
