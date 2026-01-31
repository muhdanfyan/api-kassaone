<?php

namespace App\Helpers;

use App\Modules\Perumahan\Models\PerumahanSetting;
use Illuminate\Support\Facades\Cache;

class SettingHelper
{
    /**
     * Get setting value with caching
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = PerumahanSetting::where('setting_key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            // Parse value based on data type
            switch ($setting->data_type) {
                case 'number':
                    return is_numeric($setting->setting_value) ? (float) $setting->setting_value : $default;
                case 'boolean':
                    return filter_var($setting->setting_value, FILTER_VALIDATE_BOOLEAN);
                case 'json':
                    return json_decode($setting->setting_value, true) ?? $default;
                default:
                    return $setting->setting_value;
            }
        });
    }
    
    /**
     * Get all settings by category
     */
    public static function getByCategory(string $category): array
    {
        return Cache::remember("settings_{$category}", 3600, function () use ($category) {
            $settings = PerumahanSetting::where('category', $category)->get();
            
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->setting_key] = $setting->parsed_value;
            }
            
            return $result;
        });
    }
    
    /**
     * Clear settings cache
     */
    public static function clearCache(?string $key = null)
    {
        if ($key) {
            Cache::forget("setting_{$key}");
        } else {
            Cache::flush();
        }
    }
}
