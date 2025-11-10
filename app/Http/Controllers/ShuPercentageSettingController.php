<?php

namespace App\Http\Controllers;

use App\Models\ShuPercentageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShuPercentageSettingController extends Controller
{
    /**
     * GET /api/shu-settings
     * List all percentage settings
     */
    public function index(Request $request)
    {
        $query = ShuPercentageSetting::with('creator');

        if ($request->fiscal_year) {
            $query->forYear($request->fiscal_year);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $settings = $query->orderBy('fiscal_year', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * POST /api/shu-settings
     * Create new percentage setting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'fiscal_year' => 'required|digits:4',
            'cadangan_percentage' => 'required|numeric|min:30|max:100',
            'anggota_percentage' => 'required|numeric|min:0|max:70',
            'pengurus_percentage' => 'nullable|numeric|min:0|max:100',
            'karyawan_percentage' => 'nullable|numeric|min:0|max:100',
            'dana_sosial_percentage' => 'nullable|numeric|min:0|max:100',
            'jasa_modal_percentage' => 'required|numeric|min:0|max:100',
            'jasa_usaha_percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Validate percentages sum to 100
        $level1Total = $validated['cadangan_percentage'] + 
                       $validated['anggota_percentage'] + 
                       ($validated['pengurus_percentage'] ?? 0) + 
                       ($validated['karyawan_percentage'] ?? 0) + 
                       ($validated['dana_sosial_percentage'] ?? 0);

        if (abs($level1Total - 100) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Total persentase Level 1 harus = 100%',
                'current_total' => $level1Total,
            ], 422);
        }

        $level2Total = $validated['jasa_modal_percentage'] + $validated['jasa_usaha_percentage'];
        if (abs($level2Total - 100) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Total jasa modal + jasa usaha harus = 100%',
                'current_total' => $level2Total,
            ], 422);
        }

        DB::beginTransaction();
        try {
            // If set as active, deactivate others for same fiscal year
            if ($validated['is_active'] ?? false) {
                ShuPercentageSetting::where('fiscal_year', $validated['fiscal_year'])
                                   ->update(['is_active' => false]);
            }

            $setting = ShuPercentageSetting::create(array_merge($validated, [
                'created_by' => auth()->user()->member_id,
            ]));

            DB::commit();

            Log::info('SHU Percentage Setting created', [
                'setting_id' => $setting->id,
                'fiscal_year' => $setting->fiscal_year,
                'created_by' => auth()->user()->member_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Setting persentase berhasil dibuat',
                'data' => $setting->load('creator'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create SHU percentage setting', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat setting: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/shu-settings/{id}
     * Get setting detail
     */
    public function show(string $id)
    {
        $setting = ShuPercentageSetting::with('creator')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

    /**
     * PUT /api/shu-settings/{id}
     * Update setting
     */
    public function update(Request $request, string $id)
    {
        $setting = ShuPercentageSetting::findOrFail($id);

        // Check if already used
        if ($setting->distributions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Setting tidak dapat diubah karena sudah digunakan pada distribusi SHU',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'string|max:100',
            'cadangan_percentage' => 'numeric|min:30|max:100',
            'anggota_percentage' => 'numeric|min:0|max:70',
            'pengurus_percentage' => 'nullable|numeric|min:0|max:100',
            'karyawan_percentage' => 'nullable|numeric|min:0|max:100',
            'dana_sosial_percentage' => 'nullable|numeric|min:0|max:100',
            'jasa_modal_percentage' => 'numeric|min:0|max:100',
            'jasa_usaha_percentage' => 'numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Validate if percentages provided
        if (isset($validated['cadangan_percentage']) || isset($validated['anggota_percentage'])) {
            $level1Total = ($validated['cadangan_percentage'] ?? $setting->cadangan_percentage) + 
                           ($validated['anggota_percentage'] ?? $setting->anggota_percentage) + 
                           ($validated['pengurus_percentage'] ?? $setting->pengurus_percentage) + 
                           ($validated['karyawan_percentage'] ?? $setting->karyawan_percentage) + 
                           ($validated['dana_sosial_percentage'] ?? $setting->dana_sosial_percentage);

            if (abs($level1Total - 100) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total persentase Level 1 harus = 100%',
                    'current_total' => $level1Total,
                ], 422);
            }
        }

        if (isset($validated['jasa_modal_percentage']) || isset($validated['jasa_usaha_percentage'])) {
            $level2Total = ($validated['jasa_modal_percentage'] ?? $setting->jasa_modal_percentage) + 
                           ($validated['jasa_usaha_percentage'] ?? $setting->jasa_usaha_percentage);
            if (abs($level2Total - 100) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total jasa modal + jasa usaha harus = 100%',
                    'current_total' => $level2Total,
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            if ($validated['is_active'] ?? false) {
                ShuPercentageSetting::where('fiscal_year', $setting->fiscal_year)
                                   ->where('id', '!=', $id)
                                   ->update(['is_active' => false]);
            }

            $setting->update($validated);
            DB::commit();

            Log::info('SHU Percentage Setting updated', [
                'setting_id' => $setting->id,
                'updated_by' => auth()->user()->member_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Setting berhasil diupdate',
                'data' => $setting->fresh()->load('creator'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update SHU percentage setting', [
                'setting_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/shu-settings/{id}/activate
     * Set as active setting
     */
    public function activate(string $id)
    {
        $setting = ShuPercentageSetting::findOrFail($id);

        DB::beginTransaction();
        try {
            // Deactivate others
            ShuPercentageSetting::where('fiscal_year', $setting->fiscal_year)
                               ->update(['is_active' => false]);

            $setting->update(['is_active' => true]);

            DB::commit();

            Log::info('SHU Percentage Setting activated', [
                'setting_id' => $setting->id,
                'fiscal_year' => $setting->fiscal_year,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Setting berhasil diaktifkan',
                'data' => $setting->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to activate SHU percentage setting', [
                'setting_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal aktivasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/shu-settings/{id}/preview
     * Preview calculation with sample amount
     */
    public function preview(Request $request, string $id)
    {
        $setting = ShuPercentageSetting::findOrFail($id);

        $validated = $request->validate([
            'total_shu' => 'required|numeric|min:0',
        ]);

        $breakdown = $setting->calculateBreakdown($validated['total_shu']);

        return response()->json([
            'success' => true,
            'message' => 'Preview perhitungan berhasil',
            'data' => $breakdown,
        ]);
    }

    /**
     * DELETE /api/shu-settings/{id}
     * Delete setting
     */
    public function destroy(string $id)
    {
        $setting = ShuPercentageSetting::findOrFail($id);

        if ($setting->distributions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Setting tidak dapat dihapus karena sudah digunakan pada distribusi SHU',
            ], 422);
        }

        try {
            $setting->delete();

            Log::info('SHU Percentage Setting deleted', [
                'setting_id' => $id,
                'deleted_by' => auth()->user()->member_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Setting berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete SHU percentage setting', [
                'setting_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus setting: ' . $e->getMessage(),
            ], 500);
        }
    }
}
