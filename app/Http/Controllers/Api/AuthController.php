<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Member;
use App\Models\Role;
use App\Models\CsrfToken;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Log incoming request data for debugging
        Log::info('Register request received', [
            'data' => $request->except(['password', 'password_confirmation']),
            'has_ktp' => $request->hasFile('ktp_scan'),
            'has_selfie' => $request->hasFile('selfie_with_ktp'),
        ]);

        $request->validate([
            'full_name' => 'required|string|max:255',
            'member_id_number' => 'nullable|string|max:100|unique:members',
            'username' => 'nullable|string|max:100|unique:members',
            'email' => 'nullable|string|email|max:255|unique:members',
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'join_date' => 'nullable|date',
            'password' => 'required|string|min:8|confirmed',
            'member_type' => 'required|' . Member::memberTypeRule(),
            'role_id' => 'sometimes|required|exists:roles,id',
            'ktp_scan' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'selfie_with_ktp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'nik' => 'nullable|string|max:20',
        ]);

        // Find 'Anggota' role ID, or use provided role_id
        $roleId = $request->role_id;
        if (!$roleId) {
            $anggotaRole = Role::where('name', 'Anggota')->first();
            if (!$anggotaRole) {
                return response()->json(['message' => 'Default role "Anggota" not found.'], 500);
            }
            $roleId = $anggotaRole->id;
        }

        // Generate member_id_number if not provided
        $memberIdNumber = $request->member_id_number;
        if (!$memberIdNumber) {
            // Get the last member_id_number and increment
            $lastMember = Member::whereNotNull('member_id_number')
                ->orderBy('member_id_number', 'desc')
                ->first();
            
            if ($lastMember && preg_match('/MEM-(\d+)/', $lastMember->member_id_number, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
            
            // Ensure uniqueness - if exists, keep incrementing
            do {
                $memberIdNumber = 'MEM-' . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
                $exists = Member::where('member_id_number', $memberIdNumber)->exists();
                if ($exists) {
                    $nextNumber++;
                }
            } while ($exists);
        }

        // Use current date if join_date not provided
        $joinDate = $request->join_date ?? now()->format('Y-m-d');

        // Handle file uploads
        $ktpScanPath = null;
        $selfieWithKtpPath = null;

        if ($request->hasFile('ktp_scan')) {
            $ktpScanPath = $request->file('ktp_scan')->store('members/ktp', 'public');
        }

        if ($request->hasFile('selfie_with_ktp')) {
            $selfieWithKtpPath = $request->file('selfie_with_ktp')->store('members/selfie', 'public');
        }

        // Get Simpanan Pokok amount from system settings
        $simpananPokokAmount = SystemSetting::get('simpanan_pokok_amount', 1000000);

        // Generate username if not provided (format: NAMAPERTAMANAMAKEDUA-KASSA####)
        $username = $request->username;
        if (!$username) {
            // Parse full name to get first and second name
            $nameParts = preg_split('/\s+/', trim($request->full_name));
            $firstName = strtoupper($nameParts[0] ?? '');
            $secondName = strtoupper($nameParts[1] ?? '');
            
            // Combine first + second name (or just first if no second)
            $namePrefix = $secondName ? $firstName . $secondName : $firstName;
            // Remove non-alphanumeric characters
            $namePrefix = preg_replace('/[^A-Z0-9]/', '', $namePrefix);
            
            // Ensure uniqueness with random 4-digit number
            do {
                $randomNum = str_pad((string)rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $username = $namePrefix . '-KASSA' . $randomNum;
                $exists = Member::where('username', $username)->exists();
            } while ($exists);
        }

        $member = Member::create([
            'full_name' => $request->full_name,
            'member_id_number' => $memberIdNumber,
            'username' => $username,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'ktp_scan' => $ktpScanPath,
            'selfie_with_ktp' => $selfieWithKtpPath,
            'nik' => $request->nik,
            'join_date' => $joinDate,
            'password' => Hash::make($request->password),
            'status' => 'Aktif',
            'member_type' => $request->member_type ?? 'Biasa',
            'role_id' => $roleId,
            'verification_status' => Member::VERIFICATION_PENDING, // Set pending, need payment
            'payment_amount' => $simpananPokokAmount, // Get from system settings
        ]);

        // Format amount for message
        $formattedAmount = 'Rp ' . number_format($simpananPokokAmount, 0, ',', '.');

        // Send WhatsApp welcome message (async, don't block response)
        if ($request->phone_number) {
            try {
                $whatsappServiceUrl = config('services.whatsapp.service_url', 'http://localhost:3001');
                $loginUrl = config('app.frontend_url', 'https://dev.kassaone.id');
                
                $welcomeMessage = "🕌 *Ahlan Wa Marhaban Bikum!*\n\n";
                $welcomeMessage .= "Assalamu'alaikum *{$member->full_name}*,\n\n";
                $welcomeMessage .= "Selamat bergabung di *KASSA ONE* ☺️\n\n";
                $welcomeMessage .= "━━━━━━━━━━━━━━━\n";
                $welcomeMessage .= "🔑 *AKSES LOGIN*\n";
                $welcomeMessage .= "━━━━━━━━━━━━━━━\n";
                $welcomeMessage .= "👤 Username: `{$member->username}`\n";
                $welcomeMessage .= "🔐 Password: `{$request->password}`\n";
                $welcomeMessage .= "🌐 {$loginUrl}\n\n";
                $welcomeMessage .= "━━━━━━━━━━━━━━━\n";
                $welcomeMessage .= "📋 *LANGKAH SELANJUTNYA*\n";
                $welcomeMessage .= "━━━━━━━━━━━━━━━\n";
                $welcomeMessage .= "1️⃣ Login ke akun Anda\n";
                $welcomeMessage .= "2️⃣ Upload KTP & Selfie\n";
                $welcomeMessage .= "3️⃣ Bayar Simpanan Pokok\n";
                $welcomeMessage .= "4️⃣ Tunggu verifikasi admin\n\n";
                $welcomeMessage .= "⚠️ _Simpan info ini & jangan bagikan password Anda_\n\n";
                $welcomeMessage .= "Jazakumullahu Khairan 🙏";

                Http::timeout(10)->post("{$whatsappServiceUrl}/send", [
                    'phone' => $request->phone_number,
                    'message' => $welcomeMessage,
                ]);
                
                Log::info('WhatsApp welcome message sent to: ' . $request->phone_number);
            } catch (\Exception $e) {
                // Don't fail registration if WhatsApp fails
                Log::error('Failed to send WhatsApp welcome message: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Pendaftaran berhasil! Silakan login dan upload bukti pembayaran Simpanan Pokok sebesar {$formattedAmount}",
            'username' => $member->username,
            'data' => [
                'username' => $member->username,
                'full_name' => $member->full_name,
                'member_id_number' => $member->member_id_number,
                'payment_amount' => $simpananPokokAmount,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid credentials',
                    'error' => 'invalid_credentials'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Could not create token',
                'error' => 'token_creation_failed'
            ], 500);
        }

        // Get authenticated member
        $member = JWTAuth::user();
        $member->load('role');

        // Check verification status
        if (!$member->canLogin()) {
            $statusMessages = [
                Member::VERIFICATION_PENDING => 'Your account is pending verification. Please complete payment to proceed.',
                Member::VERIFICATION_PAYMENT_PENDING => 'Your payment is being verified by admin. Please wait for confirmation.',
                Member::VERIFICATION_REJECTED => 'Your account has been rejected. Reason: ' . ($member->rejected_reason ?? 'Not specified'),
            ];

            return response()->json([
                'message' => $statusMessages[$member->verification_status] ?? 'Account verification required',
                'error' => 'account_not_verified',
                'verification_status' => $member->verification_status,
                'payment_amount' => $member->payment_amount,
                'rejected_reason' => $member->rejected_reason,
                'user' => $member,
            ], 403);
        }

        // Generate CSRF token (expires in 24 hours)
        $csrfToken = CsrfToken::create([
            'member_id' => $member->id,
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'csrf_token' => $csrfToken->id,
            'csrf_expires_at' => $csrfToken->expires_at->toIso8601String(),
            'user' => $member,
        ]);
    }

    public function logout(Request $request)
    {
        try {
            // Get authenticated user
            $user = JWTAuth::parseToken()->authenticate();
            
            // Delete all CSRF tokens for this user
            CsrfToken::where('member_id', $user->id)->delete();
            
            // Invalidate JWT token
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Logged out successfully']);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Failed to logout',
                'error' => 'logout_failed'
            ], 500);
        }
    }

    public function user(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $user->load('role');
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'User not found',
                'error' => 'user_not_found'
            ], 404);
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            
            return response()->json([
                'access_token' => $newToken,
                'token_type' => 'Bearer',
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token could not be refreshed',
                'error' => 'token_refresh_failed'
            ], 500);
        }
    }

    public function refreshCsrfToken(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            // Generate new CSRF token
            $csrfToken = CsrfToken::create([
                'member_id' => $user->id,
                'expires_at' => now()->addHours(24),
            ]);

            return response()->json([
                'csrf_token' => $csrfToken->id,
                'csrf_expires_at' => $csrfToken->expires_at->toIso8601String(),
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Authentication failed',
                'error' => 'authentication_failed'
            ], 401);
        }
    }

    public function uploadPaymentProof(Request $request)
    {
        $request->validate([
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120', // Max 5MB
        ]);

        try {
            Log::info('🔐 Upload Payment Proof - Attempting authentication');
            Log::info('🔐 Authorization Header: ' . $request->header('Authorization'));
            
            $member = JWTAuth::parseToken()->authenticate();
            
            Log::info('✅ Member authenticated: ' . $member->id);
            Log::info('📋 Member status: ' . $member->verification_status);

            // Check if member is in pending status
            if ($member->verification_status !== Member::VERIFICATION_PENDING) {
                return response()->json([
                    'message' => 'Payment proof can only be uploaded for pending accounts',
                    'current_status' => $member->verification_status,
                ], 400);
            }

            // Handle file upload
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                // Delete old payment proof if exists
                if ($member->payment_proof && Storage::disk('public')->exists($member->payment_proof)) {
                    Storage::disk('public')->delete($member->payment_proof);
                }

                $paymentProofPath = $request->file('payment_proof')->store('members/payment_proofs', 'public');
            }

            // Update member status
            $member->update([
                'payment_proof' => $paymentProofPath,
                'payment_uploaded_at' => now(),
                'verification_status' => Member::VERIFICATION_PAYMENT_PENDING,
            ]);

            return response()->json([
                'message' => 'Payment proof uploaded successfully. Waiting for admin verification.',
                'verification_status' => $member->verification_status,
                'payment_uploaded_at' => $member->payment_uploaded_at,
            ]);
        } catch (JWTException $e) {
            Log::error('❌ JWT Authentication failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Token tidak valid atau telah kadaluarsa. Silakan login ulang.',
                'error' => 'authentication_failed',
                'details' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            Log::error('❌ Upload payment proof error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat upload bukti pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload payment proof using payment_upload_token (public endpoint, no JWT required)
     * This allows pending users to upload payment proof without authentication issues
     */
    public function uploadPaymentProofPublic(Request $request)
    {
        try {
            Log::info('🔓 Public Upload Payment Proof - Start');
            
            $request->validate([
                'payment_upload_token' => 'required|string|size:64',
                'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120', // Max 5MB
            ]);

            Log::info('✅ Validation passed, looking up member by token');

            // Find member by payment upload token (only pending members)
            $member = Member::where('payment_upload_token', $request->payment_upload_token)
                ->where('verification_status', Member::VERIFICATION_PENDING)
                ->first();

            if (!$member) {
                Log::warning('❌ Invalid or expired payment upload token');
                return response()->json([
                    'message' => 'Token tidak valid atau telah kadaluarsa. Silakan daftar ulang.',
                    'error' => 'invalid_token'
                ], 404);
            }

            Log::info('✅ Member found: ' . $member->id . ' (' . $member->username . ')');

            // Delete old payment proof if exists
            if ($member->payment_proof && Storage::disk('public')->exists($member->payment_proof)) {
                Storage::disk('public')->delete($member->payment_proof);
                Log::info('🗑️ Old payment proof deleted');
            }

            // Store new payment proof
            $paymentProofPath = $request->file('payment_proof')->store('members/payment_proofs', 'public');
            Log::info('💾 New payment proof stored: ' . $paymentProofPath);

            // Update member record
            $member->update([
                'payment_proof' => $paymentProofPath,
                'payment_uploaded_at' => now(),
                'verification_status' => Member::VERIFICATION_PAYMENT_PENDING,
                // Clear the token after successful upload for security
                'payment_upload_token' => null,
            ]);

            Log::info('✅ Member updated, status changed to: ' . Member::VERIFICATION_PAYMENT_PENDING);

            return response()->json([
                'message' => 'Bukti pembayaran berhasil diupload. Menunggu verifikasi admin.',
                'payment_proof_url' => asset('storage/' . $paymentProofPath),
                'verification_status' => Member::VERIFICATION_PAYMENT_PENDING,
                'next_step' => 'waiting_verification',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ Public upload payment proof error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat upload bukti pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMemberStatus(Request $request)
    {
        try {
            $member = JWTAuth::parseToken()->authenticate();
            $member->load('role');

            return response()->json([
                'user' => $member,
                'verification_status' => $member->verification_status,
                'can_login' => $member->canLogin(),
                'payment_amount' => $member->payment_amount,
                'payment_uploaded_at' => $member->payment_uploaded_at,
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Authentication failed',
                'error' => 'authentication_failed'
            ], 401);
        }
    }
}
