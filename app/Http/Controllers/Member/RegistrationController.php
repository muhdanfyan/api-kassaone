<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    /**
     * Complete registration for admin-created member
     * Upload documents and set password
     */
    public function completeRegistration(Request $request)
    {
        $member = Auth::user()->member;
        
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member tidak ditemukan.',
            ], 404);
        }
        
        // Validate
        $request->validate([
            'ktp_scan' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'selfie_with_ktp' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'nik' => 'required|digits:16',
            'phone_number' => 'required|string|max:50',
            'address' => 'required|string',
            'temporary_password' => 'required|string',
            'new_password' => 'required|string|min:8|different:temporary_password',
            'new_password_confirmation' => 'required|same:new_password',
        ]);
        
        // Verify temporary password
        if (!Hash::check($request->temporary_password, $member->temporary_password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password sementara tidak valid.',
            ], 422);
        }
        
        DB::beginTransaction();
        try {
            // Upload files
            $ktpPath = $request->file('ktp_scan')->store('members/ktp', 'public');
            $selfiePath = $request->file('selfie_with_ktp')->store('members/selfie', 'public');
            $paymentPath = $request->file('payment_proof')->store('members/payment_proofs', 'public');
            
            // Update member
            $member->update([
                'ktp_scan' => $ktpPath,
                'selfie_with_ktp' => $selfiePath,
                'payment_proof' => $paymentPath,
                'nik' => $request->nik,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'password' => Hash::make($request->new_password),
                'temporary_password' => null,
                'password_changed_at' => now(),
                'verification_status' => 'payment_pending',
                'payment_uploaded_at' => now(),
            ]);
            
            DB::commit();
            
            Log::info('Member completed registration', [
                'member_id' => $member->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diupload. Menunggu verifikasi admin.',
                'member' => $member->fresh(),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete registration', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload dokumen.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Alternative: Set password only (without documents)
     * For members who want to change password from temporary
     */
    public function setPassword(Request $request)
    {
        $member = Auth::user()->member;
        
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member tidak ditemukan.',
            ], 404);
        }
        
        $request->validate([
            'temporary_password' => 'required|string',
            'new_password' => 'required|string|min:8|different:temporary_password',
            'new_password_confirmation' => 'required|same:new_password',
        ]);
        
        // Verify temporary password
        if (!Hash::check($request->temporary_password, $member->temporary_password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password sementara tidak valid.',
            ], 422);
        }
        
        try {
            $member->update([
                'password' => Hash::make($request->new_password),
                'temporary_password' => null,
                'password_changed_at' => now(),
            ]);
            
            Log::info('Member set new password', [
                'member_id' => $member->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to set password', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
