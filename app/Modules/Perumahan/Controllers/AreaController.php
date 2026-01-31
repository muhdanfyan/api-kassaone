<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\PerumahanArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AreaController extends Controller
{
    /**
     * Get list of areas
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = PerumahanArea::query();

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $areas = $query->orderBy('area_code')->get();

            return response()->json([
                'success' => true,
                'data' => $areas
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch areas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single area
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $area = PerumahanArea::find($id);

            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'Area not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $area
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch area',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new area
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'area_code' => 'required|string|max:20|unique:perumahan_areas,area_code',
                'area_name' => 'required|string|max:100',
                'description' => 'nullable|string',
                'house_count' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $area = PerumahanArea::create([
                'area_code' => $request->area_code,
                'area_name' => $request->area_name,
                'description' => $request->description,
                'house_count' => $request->house_count,
                'is_active' => $request->get('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Area created successfully',
                'data' => $area
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create area',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update area
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $area = PerumahanArea::find($id);

            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'Area not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'area_code' => 'sometimes|string|max:20|unique:perumahan_areas,area_code,' . $id,
                'area_name' => 'sometimes|string|max:100',
                'description' => 'nullable|string',
                'house_count' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $area->update($request->only([
                'area_code', 'area_name', 'description', 'house_count', 'is_active'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Area updated successfully',
                'data' => $area->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update area',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete area
     * 
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $area = PerumahanArea::find($id);

            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'Area not found'
                ], 404);
            }

            $area->delete();

            return response()->json([
                'success' => true,
                'message' => 'Area deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete area',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
