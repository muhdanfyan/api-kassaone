<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Role;
use App\Models\SavingsAccount;
use App\Models\SystemSetting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Mail\MemberCredentialsEmail;

class MemberController extends Controller
{
    /**
     * Get all members with optional filters
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Member::with('role');

        // Filter by perumahan status
        if ($request->has('is_perumahan')) {
            $isPerumahan = filter_var($request->is_perumahan, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_perumahan', $isPerumahan);
        }

        // Filter by member type
        if ($request->has('member_type')) {
            $query->where('member_type', $request->member_type);
        }

        // Filter by verification status
        if ($request->has('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        // Filter by status (Aktif/Tidak Aktif)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $members = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $members,
            'count' => $members->count()
        ]);
    }

    /**
     * Get a specific member
     * 
     * @param \App\Models\Member $member
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Member $member)
    {
        $member->load('role');
        return response()->json($member);
    }

    /**
     * Store a newly created member
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'username' => 'nullable|string|min:3|max:50|unique:members',
            'email' => 'nullable|email|max:255|unique:members',
            'phone_number' => 'nullable|string|min:10|max:13|unique:members',
            'nik' => 'required|string|size:16|regex:/^[0-9]+$/|unique:members',
            'address' => 'nullable|string|max:500',
            'join_date' => 'required|date|before_or_equal:today',
            'member_type' => 'required|' . Member::memberTypeRule(),
            'password' => 'required|string|min:8|confirmed',
            'status' => 'nullable|in:Aktif,Tidak Aktif,Ditangguhkan',
            'role_id' => 'nullable|exists:roles,id',
            'ktp_scan' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'selfie_with_ktp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'send_email_notification' => 'boolean',
            'send_whatsapp_notification' => 'boolean',
        ]);

        // Auto-generate username if not provided with format MEM-####
        $username = $request->username;
        if (empty($username)) {
            $lastMember = Member::where('username', 'LIKE', 'MEM-%')
                ->orderBy('username', 'desc')
                ->first();
            
            if ($lastMember) {
                $lastNumber = intval(substr($lastMember->username, 4));
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            $username = 'MEM-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        }

        // Auto-set role to 'Anggota' if not provided
        $roleId = $request->role_id;
        if (empty($roleId)) {
            $anggotaRole = Role::where('name', 'Anggota')->first();
            $roleId = $anggotaRole ? $anggotaRole->id : null;
        }

        // Handle file uploads
        $ktpScanPath = null;
        $selfieWithKtpPath = null;

        if ($request->hasFile('ktp_scan')) {
            $ktpScanPath = $request->file('ktp_scan')->store('member_documents', 'public');
        }

        if ($request->hasFile('selfie_with_ktp')) {
            $selfieWithKtpPath = $request->file('selfie_with_ktp')->store('member_documents', 'public');
        }
        
        $member = Member::create([
            'full_name' => $request->full_name,
            'username' => $username,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'nik' => $request->nik,
            'address' => $request->address,
            'join_date' => $request->join_date,
            'password' => Hash::make($request->password),
            'member_type' => $request->member_type,
            'status' => $request->status ?? 'Aktif',
            'verification_status' => Member::VERIFICATION_PENDING, // IMPORTANT: Set to pending
            'role_id' => $roleId,
            'ktp_scan' => $ktpScanPath,
            'selfie_with_ktp' => $selfieWithKtpPath,
        ]);

        $member->load('role');
        
        // Send email notification if requested
        if ($request->send_email_notification && $member->email) {
            try {
                Mail::to($member->email)->send(
                    new \App\Mail\MemberCreatedByAdmin($member, $request->password)
                );
                Log::info("Email notification sent to member: {$member->username}");
            } catch (\Exception $e) {
                Log::error('Failed to send member credentials email: ' . $e->getMessage());
            }
        }
        
        // Send WhatsApp notification if requested
        if ($request->send_whatsapp_notification && $member->phone_number) {
            try {
                \App\Services\WhatsAppService::send($member->phone_number, [
                    'name' => $member->full_name,
                    'username' => $member->username,
                    'password' => $request->password,
                    'login_url' => config('app.frontend_url') . '/login',
                ]);
                Log::info("WhatsApp notification sent to member: {$member->username}");
            } catch (\Exception $e) {
                Log::error('Failed to send WhatsApp notification: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Member berhasil dibuat',
            'member' => $member,
        ], 201);
    }

    /**
     * Update an existing member
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Member $member
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Member $member)
    {
        $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:100|unique:members,username,' . $member->id,
            'email' => 'nullable|string|email|max:255|unique:members,email,' . $member->id,
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'join_date' => 'sometimes|required|date',
            'password' => 'sometimes|required|string|min:8|confirmed',
            'member_type' => 'sometimes|required|' . Member::memberTypeRule(),
            'status' => 'sometimes|required|in:active,inactive,suspended',
            'role_id' => 'sometimes|required|exists:roles,id',
        ]);

        $member->fill($request->except('password'));

        if ($request->has('password')) {
            $member->password = Hash::make($request->password);
        }

        $member->save();
        $member->load('role');

        return response()->json($member);
    }

    /**
     * Delete a member
     * 
     * @param \App\Models\Member $member
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Member $member)
    {
        $member->delete();
        return response()->json(['message' => 'Member deleted successfully']);
    }

    /**
     * Get available member types for dropdown
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMemberTypes()
    {
        return response()->json([
            'member_types' => Member::getMemberTypes(),
        ]);
    }

    /**
     * Get members by verification status (untuk admin)
     * Filter member berdasarkan status: pending, payment_pending, verified, rejected
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByVerificationStatus(Request $request)
    {
        $status = $request->query('status');
        
        $query = Member::with('role');
        
        if ($status) {
            $query->where('verification_status', $status);
        }
        
        $members = $query->orderBy('created_at', 'desc')->get();
        
        // Group by status untuk summary
        $summary = [
            'pending' => Member::where('verification_status', Member::VERIFICATION_PENDING)->count(),
            'payment_pending' => Member::where('verification_status', Member::VERIFICATION_PAYMENT_PENDING)->count(),
            'verified' => Member::where('verification_status', Member::VERIFICATION_VERIFIED)->count(),
            'rejected' => Member::where('verification_status', Member::VERIFICATION_REJECTED)->count(),
        ];
        
        return response()->json([
            'members' => $members,
            'summary' => $summary,
            'current_filter' => $status ?? 'all',
        ]);
    }

    /**
     * Approve member payment (admin only)
     * Ubah status dari payment_pending ke verified
     * Dan otomatis buat 3 Savings Accounts terpisah
     * 
     * @param string $memberId
     * @return \Illuminate\Http\JsonResponse
     */
    public function approvePayment(string $memberId)
    {
        /** @var \App\Models\Member $member */
        $member = Member::findOrFail($memberId);
        
        if ($member->verification_status !== Member::VERIFICATION_PAYMENT_PENDING) {
            return response()->json([
                'message' => 'Member ini tidak dalam status menunggu verifikasi pembayaran',
                'current_status' => $member->verification_status,
            ], 400);
        }
        
        DB::beginTransaction();
        try {
            // Update member verification status
            $member->update([
                'verification_status' => Member::VERIFICATION_VERIFIED,
                'payment_verified_at' => now(),
                'payment_verified_by' => Auth::id(), // ID admin yang approve
            ]);
            
            // Get Simpanan Pokok amount from system settings
            /** @var int $simpananPokokAmount */
            $simpananPokokAmount = (int) SystemSetting::get('simpanan_pokok_amount', 1000000);
            
            // Create 3 separate savings accounts
            $accounts = $this->createInitialSavingsAccounts($member, $simpananPokokAmount);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Pembayaran berhasil diverifikasi dan Savings Accounts telah dibuat.',
                'member' => $member->load('role'),
                'savings_accounts' => $accounts,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving payment: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Terjadi kesalahan saat memverifikasi pembayaran',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Create initial savings accounts for verified member
     * Creates 3 separate accounts: pokok, wajib, sukarela
     * 
     * @param \App\Models\Member $member
     * @param float|int $simpananPokokAmount
     * @return array<string, \App\Models\SavingsAccount>
     */
    private function createInitialSavingsAccounts(Member $member, float|int $simpananPokokAmount): array
    {
        $accounts = [];
        
        // 1. Simpanan Pokok (one-time, from settings)
        $simpananPokok = SavingsAccount::create([
            'member_id' => $member->id,
            'account_type' => 'pokok',
            'balance' => $simpananPokokAmount,
        ]);
        
        Transaction::create([
            'savings_account_id' => $simpananPokok->id,
            'member_id' => $member->id,
            'transaction_type' => 'deposit',
            'amount' => $simpananPokokAmount,
            'description' => 'Simpanan Pokok dari pembayaran pendaftaran',
            'transaction_date' => now(),
        ]);
        
        $accounts['pokok'] = $simpananPokok;
        
        // 2. Simpanan Wajib (first month, from member selection)
        $simpananWajibAmount = $member->monthly_savings_amount ?? 0;
        $simpananWajib = SavingsAccount::create([
            'member_id' => $member->id,
            'account_type' => 'wajib',
            'balance' => $simpananWajibAmount,
        ]);
        
        if ($simpananWajibAmount > 0) {
            Transaction::create([
                'savings_account_id' => $simpananWajib->id,
                'member_id' => $member->id,
                'transaction_type' => 'deposit',
                'amount' => $simpananWajibAmount,
                'description' => 'Simpanan Wajib bulan pertama dari pembayaran pendaftaran',
                'transaction_date' => now(),
            ]);
        }
        
        $accounts['wajib'] = $simpananWajib;
        
        // 3. Simpanan Sukarela (initial 0)
        $simpananSukarela = SavingsAccount::create([
            'member_id' => $member->id,
            'account_type' => 'sukarela',
            'balance' => 0,
        ]);
        
        $accounts['sukarela'] = $simpananSukarela;
        
        return $accounts;
    }

    /**
     * Reject member payment (admin only)
     * Ubah status dari payment_pending ke rejected dengan alasan
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $memberId
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectPayment(Request $request, string $memberId)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);
        
        /** @var \App\Models\Member $member */
        $member = Member::findOrFail($memberId);
        
        if ($member->verification_status !== Member::VERIFICATION_PAYMENT_PENDING) {
            return response()->json([
                'message' => 'Member ini tidak dalam status menunggu verifikasi pembayaran',
                'current_status' => $member->verification_status,
            ], 400);
        }
        
        // Generate new payment upload token untuk upload ulang
        $newPaymentUploadToken = bin2hex(random_bytes(32));
        
        $member->update([
            'verification_status' => Member::VERIFICATION_REJECTED,
            'rejected_reason' => $request->rejected_reason,
            'rejected_at' => now(),
            'rejected_by' => Auth::id(), // ID admin yang reject
            'payment_upload_token' => $newPaymentUploadToken, // Generate token baru untuk upload ulang
        ]);
        
        return response()->json([
            'message' => 'Pembayaran ditolak. Member perlu upload ulang bukti pembayaran.',
            'member' => $member->load('role'),
            'payment_upload_token' => $newPaymentUploadToken, // Kirim token baru ke frontend
        ]);
    }

    /**
     * Get payment statistics
     * Untuk dashboard admin
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentStats()
    {
        $stats = [
            'belum_bayar' => Member::where('verification_status', Member::VERIFICATION_PENDING)->count(),
            'sudah_upload_belum_verifikasi' => Member::where('verification_status', Member::VERIFICATION_PAYMENT_PENDING)->count(),
            'sudah_verified' => Member::where('verification_status', Member::VERIFICATION_VERIFIED)->count(),
            'ditolak' => Member::where('verification_status', Member::VERIFICATION_REJECTED)->count(),
            'total_revenue_verified' => Member::where('verification_status', Member::VERIFICATION_VERIFIED)
                ->sum('payment_amount'),
            'pending_revenue' => Member::where('verification_status', Member::VERIFICATION_PAYMENT_PENDING)
                ->sum('payment_amount'),
        ];
        
        // Recent payments (7 hari terakhir)
        $recentPayments = Member::where('payment_uploaded_at', '>=', now()->subDays(7))
            ->with('role')
            ->orderBy('payment_uploaded_at', 'desc')
            ->get();
        
        return response()->json([
            'stats' => $stats,
            'recent_payments' => $recentPayments,
        ]);
    }

    /**
     * Get Simpanan Pokok statistics
     * Untuk dashboard admin
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSimpananPokokStats()
    {
        // Total Simpanan Pokok dari semua anggota
        $totalSimpananPokok = SavingsAccount::where('account_type', 'pokok')
            ->sum('balance');
        
        // Jumlah anggota yang sudah punya Simpanan Pokok
        $membersWithSimpananPokok = SavingsAccount::where('account_type', 'pokok')
            ->where('balance', '>', 0)
            ->count();
        
        // Recent Simpanan Pokok transactions (7 hari terakhir)
        $recentTransactions = Transaction::with(['member', 'savingsAccount'])
            ->whereHas('savingsAccount', function($query) {
                $query->where('account_type', 'pokok');
            })
            ->where('transaction_date', '>=', now()->subDays(7))
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json([
            'total_simpanan_pokok' => $totalSimpananPokok,
            'members_count' => $membersWithSimpananPokok,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    /**
     * Migrate old verified members to have Simpanan Pokok
     * For members created before auto-create feature was implemented
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function migrateOldMembersSimpananPokok()
    {
        // Find all verified members who don't have Simpanan Pokok yet
        $verifiedMembers = Member::where('verification_status', Member::VERIFICATION_VERIFIED)
            ->whereDoesntHave('savingsAccounts', function($query) {
                $query->where('account_type', 'pokok');
            })
            ->get();

        $created = 0;
        $failed = 0;
        $errors = [];

        foreach ($verifiedMembers as $member) {
            DB::beginTransaction();
            try {
                // Create Simpanan Pokok account
                $simpananPokok = SavingsAccount::create([
                    'member_id' => $member->id,
                    'account_type' => 'pokok',
                    'balance' => $member->payment_amount ?? 1000000,
                ]);

                // Create transaction record
                Transaction::create([
                    'savings_account_id' => $simpananPokok->id,
                    'member_id' => $member->id,
                    'transaction_type' => 'deposit',
                    'amount' => $member->payment_amount ?? 1000000,
                    'description' => 'Simpanan Pokok (Migrasi Data Lama)',
                    'transaction_date' => $member->payment_verified_at ?? $member->join_date ?? now(),
                ]);

                DB::commit();
                $created++;
                
                Log::info("Created Simpanan Pokok for member: {$member->name} ({$member->username})");
            } catch (\Exception $e) {
                DB::rollBack();
                $failed++;
                $errors[] = [
                    'member' => $member->name,
                    'error' => $e->getMessage(),
                ];
                
                Log::error("Failed to create Simpanan Pokok for member: {$member->name} - {$e->getMessage()}");
            }
        }

        return response()->json([
            'message' => "Migrasi selesai: {$created} berhasil, {$failed} gagal",
            'created' => $created,
            'failed' => $failed,
            'total_processed' => $verifiedMembers->count(),
            'errors' => $errors,
        ]);
    }

    /**
     * Update member personal information
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Member $member
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePersonalInfo(Request $request, Member $member)
    {
        $request->validate([
            'gender' => 'nullable|in:Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|max:100',
            'education' => 'nullable|string|max:100',
            'occupation' => 'nullable|string|max:100',
            'office_name' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:Belum Menikah,Menikah,Cerai Hidup,Cerai Mati',
        ]);

        $member->update($request->only([
            'gender',
            'birth_place',
            'birth_date',
            'religion',
            'education',
            'occupation',
            'office_name',
            'marital_status',
        ]));

        return response()->json([
            'message' => 'Informasi pribadi berhasil diperbarui',
            'member' => $member->fresh(),
        ]);
    }

    /**
     * Update member heir information
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Member $member
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateHeirInfo(Request $request, Member $member)
    {
        $request->validate([
            'heir_name' => 'required|string|max:255',
            'heir_relationship' => 'required|string|max:100',
            'heir_address' => 'required|string',
            'heir_phone' => 'required|string|max:50',
        ]);

        $member->update($request->only([
            'heir_name',
            'heir_relationship',
            'heir_address',
            'heir_phone',
        ]));

        return response()->json([
            'message' => 'Informasi ahli waris berhasil diperbarui',
            'member' => $member->fresh(),
        ]);
    }

    /**
     * Update member monthly savings amount
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Member $member
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMonthlySavings(Request $request, Member $member)
    {
        $request->validate([
            'monthly_savings_amount' => 'required|numeric|in:500000,1000000,1500000,2000000',
        ]);

        $monthlySavingsAmount = $request->monthly_savings_amount;
        
        // ✅ Get Simpanan Pokok amount from system settings
        $simpananPokok = (int) SystemSetting::get('simpanan_pokok_amount', 1000000);
        
        $totalPaymentAmount = $simpananPokok + $monthlySavingsAmount;

        $member->update([
            'monthly_savings_amount' => $monthlySavingsAmount,
            'payment_amount' => $totalPaymentAmount,
        ]);

        return response()->json([
            'message' => 'Jumlah simpanan bulanan berhasil diperbarui',
            'member' => $member->fresh(),
            'payment_breakdown' => [
                'simpanan_pokok' => $simpananPokok,
                'simpanan_wajib_pertama' => (int) $monthlySavingsAmount,
                'total' => $totalPaymentAmount,
            ],
        ]);
    }
    
    /**
     * Member change password (self-service)
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|different:current_password',
            'new_password_confirmation' => 'required|same:new_password',
        ]);
        
        /** @var \App\Models\Member|null $member */
        $member = Auth::user()->member;
        
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member tidak ditemukan.',
            ], 404);
        }
        
        // Verify current password
        if (!Hash::check($request->current_password, $member->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai.',
            ], 422);
        }
        
        try {
            $member->update([
                'password' => Hash::make($request->new_password),
                'password_changed_at' => now(),
            ]);
            
            Log::info('Member changed password', [
                'member_id' => $member->id,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah.',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to change password', [
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

    /**
     * Get perumahan statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPerumahanStats()
    {
        $stats = [
            'total' => Member::count(),
            'perumahan' => Member::where('is_perumahan', true)->count(),
            'non_perumahan' => Member::where('is_perumahan', false)->count(),
            'percentage_perumahan' => 0,
            'percentage_non_perumahan' => 0,
        ];

        if ($stats['total'] > 0) {
            $stats['percentage_perumahan'] = round(($stats['perumahan'] / $stats['total']) * 100, 2);
            $stats['percentage_non_perumahan'] = round(($stats['non_perumahan'] / $stats['total']) * 100, 2);
        }

        // Get breakdown by member type
        $breakdown = [
            'perumahan_by_type' => [],
            'non_perumahan_by_type' => []
        ];

        $memberTypes = Member::getMemberTypes();
        foreach ($memberTypes as $type) {
            $breakdown['perumahan_by_type'][$type] = Member::where('is_perumahan', true)
                ->where('member_type', $type)
                ->count();
            $breakdown['non_perumahan_by_type'][$type] = Member::where('is_perumahan', false)
                ->where('member_type', $type)
                ->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $stats,
                'breakdown' => $breakdown
            ]
        ]);
    }
}
