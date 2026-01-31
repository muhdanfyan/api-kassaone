<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Perumahan\Models\PerumahanSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get all settings
     */
    public function index()
    {
        try {
            $settings = PerumahanSetting::select('setting_key as key', 'setting_value as value', 'category', 'data_type', 'description')
                ->orderBy('category')
                ->orderBy('setting_key')
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Settings retrieved successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get settings by category
     */
    public function getByCategory($category)
    {
        try {
            $settings = PerumahanSetting::select('setting_key as key', 'setting_value as value', 'category', 'data_type', 'description')
                ->where('category', $category)
                ->orderBy('setting_key')
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Settings retrieved successfully',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single setting
     */
    public function getSetting($key)
    {
        try {
            $setting = PerumahanSetting::where('setting_key', $key)->first();
            
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Setting retrieved successfully',
                'data' => [
                    'key' => $setting->setting_key,
                    'value' => $setting->setting_value,
                    'category' => $setting->category,
                    'data_type' => $setting->data_type,
                    'description' => $setting->description
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update multiple settings (bulk update)
     */
    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);
        
        try {
            $settings = $request->input('settings');
            $updated = [];
            
            foreach ($settings as $key => $value) {
                $setting = PerumahanSetting::where('setting_key', $key)->first();
                
                if ($setting) {
                    $setting->setting_value = (string) $value;
                    $setting->save();
                    
                    $updated[] = [
                        'key' => $setting->setting_key,
                        'value' => $setting->setting_value,
                        'category' => $setting->category
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update single setting
     */
    public function updateSingle(Request $request, $key)
    {
        $request->validate([
            'value' => 'required|string',
        ]);
        
        try {
            $setting = PerumahanSetting::where('setting_key', $key)->first();
            
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Setting not found'
                ], 404);
            }
            
            $setting->setting_value = $request->input('value');
            $setting->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => [
                    'key' => $setting->setting_key,
                    'value' => $setting->setting_value,
                    'category' => $setting->category,
                    'data_type' => $setting->data_type
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reset settings to default values
     */
    public function reset(Request $request)
    {
        $request->validate([
            'keys' => 'nullable|array',
            'keys.*' => 'string'
        ]);
        
        try {
            $keys = $request->input('keys', []);
            
            // Default values map
            $defaults = $this->getDefaultSettings();
            
            $reset = [];
            
            if (empty($keys)) {
                // Reset all settings
                foreach ($defaults as $key => $value) {
                    $setting = PerumahanSetting::where('setting_key', $key)->first();
                    if ($setting) {
                        $setting->setting_value = $value;
                        $setting->save();
                        
                        $reset[] = [
                            'key' => $setting->setting_key,
                            'value' => $setting->setting_value,
                            'category' => $setting->category
                        ];
                    }
                }
            } else {
                // Reset specific keys
                foreach ($keys as $key) {
                    if (isset($defaults[$key])) {
                        $setting = PerumahanSetting::where('setting_key', $key)->first();
                        if ($setting) {
                            $setting->setting_value = $defaults[$key];
                            $setting->save();
                            
                            $reset[] = [
                                'key' => $setting->setting_key,
                                'value' => $setting->setting_value,
                                'category' => $setting->category
                            ];
                        }
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Settings reset successfully',
                'data' => $reset
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get default settings values
     */
    private function getDefaultSettings(): array
    {
        return [
            // General
            'perumahan_name' => 'Tarbiyah Garden',
            'perumahan_address' => 'Jl. Tarbiyah No. 1, Kota',
            'total_blocks' => '8',
            'total_units' => '156',
            'contact_phone' => '021-12345678',
            'contact_email' => 'info@tarbiyahgarden.com',
            
            // Fee
            'monthly_fee' => '150000',
            'due_date' => '5',
            'penalty_enabled' => 'true',
            'penalty_per_day' => '5000',
            'penalty_max_days' => '30',
            'grace_period_days' => '3',
            
            // Security
            'shifts_per_day' => '3',
            'patrol_interval' => '60',
            'auto_patrol_log' => 'true',
            'require_visitor_photo' => 'false',
            'max_visitor_hours' => '12',
            
            // Notification
            'fee_reminder' => 'true',
            'fee_reminder_days' => '3',
            'service_notification' => 'true',
            'waste_notification' => 'false',
            'waste_notification_hours' => '12',
            'notification_method' => 'whatsapp',
            
            // Waste
            'waste_collection_days' => 'tuesday,friday',
            'waste_collection_time' => '07:00',
            'organic_waste_days' => 'tuesday,thursday',
            'inorganic_waste_days' => 'friday',
        ];
    }
}
