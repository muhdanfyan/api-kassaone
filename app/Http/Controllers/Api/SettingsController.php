<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get all settings or specific setting by key
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $key = $request->query('key');
            
            if ($key) {
                // Get specific setting
                $value = SystemSetting::get($key);
                
                if ($value === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Setting not found'
                    ], 404);
                }
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'key' => $key,
                        'value' => $value
                    ]
                ]);
            }
            
            // Get all settings
            $settings = SystemSetting::all();
            
            // Format settings with proper type casting
            $formattedSettings = $settings->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => SystemSetting::get($setting->key),
                    'type' => $setting->type,
                    'description' => $setting->description,
                    'updated_at' => $setting->updated_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedSettings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a setting value
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'key' => 'required|string',
                'value' => 'required',
                'type' => 'nullable|string|in:string,integer,boolean,json',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $key = $request->input('key');
            $value = $request->input('value');
            $type = $request->input('type', 'string');
            $description = $request->input('description');

            // Validate type-specific values
            if ($type === 'integer' && !is_numeric($value)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Value must be numeric for integer type'
                ], 422);
            }

            if ($type === 'boolean' && !in_array(strtolower($value), ['true', 'false', '0', '1'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Value must be true or false for boolean type'
                ], 422);
            }

            $success = SystemSetting::set($key, $value, $type, $description);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Setting updated successfully',
                    'data' => [
                        'key' => $key,
                        'value' => SystemSetting::get($key)
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update setting'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
