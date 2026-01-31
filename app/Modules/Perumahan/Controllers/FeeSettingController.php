<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\PerumahanFeeSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FeeSettingController extends Controller
{
    /**
     * Get all fee settings
     * GET /api/perumahan/fee-settings
     */
    public function index(Request $request)
    {
        try {
            $query = PerumahanFeeSetting::query();

            // Filter by active status if provided
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $feeSettings = $query->ordered()->get();

            return response()->json([
                'success' => true,
                'message' => 'Fee settings retrieved successfully',
                'data' => $feeSettings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fee settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fee setting by ID
     * GET /api/perumahan/fee-settings/{id}
     */
    public function show($id)
    {
        try {
            $feeSetting = PerumahanFeeSetting::find($id);

            if (!$feeSetting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee setting not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fee setting retrieved successfully',
                'data' => $feeSetting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve fee setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new fee setting
     * POST /api/perumahan/fee-settings
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fee_code' => 'required|string|max:50|unique:perumahan_fee_settings,fee_code',
                'fee_name' => 'required|string|max:100',
                'amount' => 'required|numeric|min:0',
                'is_active' => 'boolean',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:50',
                'sort_order' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $feeSetting = PerumahanFeeSetting::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Fee setting created successfully',
                'data' => $feeSetting
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create fee setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update fee setting
     * PUT /api/perumahan/fee-settings/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $feeSetting = PerumahanFeeSetting::find($id);

            if (!$feeSetting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee setting not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'fee_name' => 'sometimes|required|string|max:100',
                'amount' => 'sometimes|required|numeric|min:0',
                'is_active' => 'sometimes|boolean',
                'description' => 'nullable|string',
                'icon' => 'nullable|string|max:50',
                'sort_order' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Exclude fee_code from updates (it cannot be changed)
            $data = $validator->validated();
            unset($data['fee_code']);

            $feeSetting->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Fee setting updated successfully',
                'data' => $feeSetting->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fee setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete fee setting
     * DELETE /api/perumahan/fee-settings/{id}
     */
    public function destroy($id)
    {
        try {
            $feeSetting = PerumahanFeeSetting::find($id);

            if (!$feeSetting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fee setting not found'
                ], 404);
            }

            // Check if fee setting is still in use
            if (!$feeSetting->canBeDeleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete fee setting that is still in use',
                    'data' => [
                        'payment_count' => 0 // This would need actual implementation
                    ]
                ], 409);
            }

            $feeSetting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fee setting deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete fee setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update fee settings
     * PUT /api/perumahan/fee-settings/bulk
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array|min:1',
                'settings.*.id' => 'required|string|exists:perumahan_fee_settings,id',
                'settings.*.amount' => 'sometimes|required|numeric|min:0',
                'settings.*.is_active' => 'sometimes|required|boolean',
                'settings.*.sort_order' => 'sometimes|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $updated = 0;

            DB::beginTransaction();
            
            try {
                foreach ($request->settings as $setting) {
                    $feeSetting = PerumahanFeeSetting::find($setting['id']);
                    
                    if ($feeSetting) {
                        $updateData = array_intersect_key($setting, array_flip(['amount', 'is_active', 'sort_order']));
                        $feeSetting->update($updateData);
                        $updated++;
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Fee settings updated successfully',
                    'data' => [
                        'updated_count' => $updated
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update fee settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
