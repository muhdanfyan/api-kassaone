<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends Controller
{
    /**
     * Get organizational structure grouped by roles
     * Returns: Pengurus (Board of Directors), Pengawas (Supervisors), and Karyawan (Staff)
     */
    public function index()
    {
        try {
            // Get all roles
            $roles = Role::with(['members' => function($query) {
                $query->where('status', 'Aktif')
                      ->orderBy('position');
            }])->get();

            // Group members by role category
            $organization = [
                'pengurus' => [],      // Board of Directors
                'pengawas' => [],      // Supervisors
                'karyawan' => [],      // Staff/Employees
            ];

            foreach ($roles as $role) {
                $roleNameLower = strtolower($role->name);
                
                foreach ($role->members as $member) {
                    $memberData = [
                        'id' => $member->id,
                        'username' => $member->username,
                        'name' => $member->full_name,
                        'position' => $member->position,
                        'role_id' => $member->role_id,
                        'role_name' => $role->name,
                        'email' => $member->email,
                        'phone_number' => $member->phone_number,
                    ];

                    // Categorize based on role name
                    if (str_contains($roleNameLower, 'pengurus') || str_contains($roleNameLower, 'ketua') || str_contains($roleNameLower, 'sekretaris') || str_contains($roleNameLower, 'bendahara')) {
                        $organization['pengurus'][] = $memberData;
                    } elseif (str_contains($roleNameLower, 'pengawas')) {
                        $organization['pengawas'][] = $memberData;
                    } else {
                        $organization['karyawan'][] = $memberData;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $organization,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch organizational structure',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update member's organizational position
     */
    public function updatePosition(Request $request, Member $member)
    {
        try {
            $validator = Validator::make($request->all(), [
                'position' => 'nullable|string|max:100',
                'role_id' => 'nullable|exists:roles,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $updateData = [];
            
            if ($request->has('position')) {
                $updateData['position'] = $request->position;
            }
            
            if ($request->has('role_id')) {
                $updateData['role_id'] = $request->role_id;
            }

            if (!empty($updateData)) {
                $member->update($updateData);
            }

            // Load fresh data with role
            $member->load('role');

            return response()->json([
                'success' => true,
                'message' => 'Position updated successfully',
                'data' => [
                    'id' => $member->id,
                    'username' => $member->member_id_number,
                    'name' => $member->full_name,
                    'position' => $member->position,
                    'role_id' => $member->role_id,
                    'role_name' => $member->role?->name,
                    'email' => $member->email,
                    'phone_number' => $member->phone_number,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update position',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all available roles
     */
    public function getRoles()
    {
        try {
            $roles = Role::all();

            return response()->json([
                'success' => true,
                'data' => $roles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
