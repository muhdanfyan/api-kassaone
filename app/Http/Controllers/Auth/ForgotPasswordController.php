<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\PasswordResetRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    /**
     * Handle forgot password request from member using NIK only
     * NIK is unique identifier - simplifies matching process
     */
    public function requestReset(Request $request)
    {
        // Validate NIK only (16 digits required)
        $request->validate([
            'nik' => 'required|digits:16',
        ], [
            'nik.required' => 'NIK harus diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
        ]);
        
        try {
            // Find member by NIK (unique constraint ensures only one match)
            $member = Member::where('nik', $request->nik)->first();

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'NIK tidak ditemukan dalam sistem. Pastikan NIK yang Anda masukkan benar.'
                ], 404);
            }

            // Create password reset request with NIK and matched member
            $passwordResetRequest = PasswordResetRequest::create([
                'nik' => $request->nik,
                'matched_member_id' => $member->id,
                'status' => 'pending',
            ]);
            
            Log::info('Password reset request created', [
                'request_id' => $passwordResetRequest->id,
                'member_id' => $member->id,
                'nik' => $request->nik,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Permintaan reset password berhasil dikirim. Silahkan tunggu konfirmasi dari admin.',
                'request_id' => $passwordResetRequest->id,
                'member_info' => [
                    'full_name' => $member->full_name,
                    'email' => $member->email,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create password reset request', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim permintaan reset password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
