<?php

namespace App\Modules\Perumahan\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\SettingHelper;

class InfoController extends Controller
{
    /**
     * Get system information for header/sidebar
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSystemInfo()
    {
        try {
            $info = [
                'perumahan_name' => SettingHelper::get('perumahan_name', 'Tarbiyah Garden'),
                'perumahan_address' => SettingHelper::get('perumahan_address', 'Jl. Tarbiyah No. 1'),
                'contact_phone' => SettingHelper::get('contact_phone', '021-12345678'),
                'contact_email' => SettingHelper::get('contact_email', 'info@tarbiyahgarden.com'),
                'total_blocks' => (int) SettingHelper::get('total_blocks', 8),
                'total_units' => (int) SettingHelper::get('total_units', 156),
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'System info retrieved successfully',
                'data' => $info
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve system info',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
