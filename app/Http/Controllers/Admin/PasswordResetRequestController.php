<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordResetRequestController extends Controller
{
    /**
     * Get all password reset requests with filters
     */
    public function index(Request $request)
    {
        $query = PasswordResetRequest::with(['member', 'processedBy']);
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Search by name, username, email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $requests = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get summary statistics
        $stats = [
            'total' => PasswordResetRequest::count(),
            'pending' => PasswordResetRequest::where('status', 'pending')->count(),
            'completed' => PasswordResetRequest::where('status', 'completed')->count(),
            'rejected' => PasswordResetRequest::where('status', 'rejected')->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
            'stats' => $stats,
        ]);
    }
    
    /**
     * Reset password for a member
     */
    public function reset(Request $request, $id)
    {
        $request->validate([
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|same:new_password',
            'send_email' => 'boolean',
            'send_whatsapp' => 'boolean',
        ]);
        
        $passwordResetRequest = PasswordResetRequest::findOrFail($id);
        
        if ($passwordResetRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request ini sudah diproses sebelumnya.',
            ], 400);
        }
        
        if (!$passwordResetRequest->matched_member_id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada member yang cocok dengan data request ini.',
            ], 400);
        }
        
        DB::beginTransaction();
        try {
            $member = $passwordResetRequest->member;
            
            if (!$member) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Member tidak ditemukan.',
                ], 404);
            }
            
            // Hash the new password
            $hashedPassword = Hash::make($request->new_password);
            
            // Update password directly on the model
            $member->password = $hashedPassword;
            $member->password_changed_at = now();
            $member->save();
            
            // Verify the password was saved correctly
            $member->refresh();
            $passwordVerification = Hash::check($request->new_password, $member->password);
            
            Log::info('Password reset attempt', [
                'member_id' => $member->id,
                'username' => $member->username,
                'password_verification' => $passwordVerification ? 'SUCCESS' : 'FAILED',
                'hash_sample' => substr($member->password, 0, 20),
            ]);
            
            if (!$passwordVerification) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memverifikasi password baru. Silakan coba lagi.',
                ], 500);
            }
            
            // Update request status
            $passwordResetRequest->update([
                'status' => 'completed',
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);
            
            DB::commit();
            
            Log::info('Password reset completed by admin', [
                'request_id' => $passwordResetRequest->id,
                'member_id' => $member->id,
                'admin_id' => Auth::id(),
            ]);
            
            // TODO: Send email/WhatsApp notification if requested
            // if ($request->send_email) {
            //     Mail::to($member->email)->send(new PasswordResetByAdmin($member, $request->new_password));
            // }
            
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset.',
                'member' => [
                    'id' => $member->id,
                    'username' => $member->username,
                    'email' => $member->email,
                    'full_name' => $member->full_name,
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset password', [
                'request_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal reset password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Reject password reset request
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);
        
        $passwordResetRequest = PasswordResetRequest::findOrFail($id);
        
        if ($passwordResetRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request ini sudah diproses sebelumnya.',
            ], 400);
        }
        
        try {
            $passwordResetRequest->update([
                'status' => 'rejected',
                'notes' => $request->notes,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);
            
            Log::info('Password reset request rejected', [
                'request_id' => $passwordResetRequest->id,
                'admin_id' => Auth::id(),
                'reason' => $request->notes,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Permintaan reset password ditolak.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to reject password reset request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menolak request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
