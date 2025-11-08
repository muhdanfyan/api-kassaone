<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Role;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::with('role')->get();
        return response()->json($members);
    }

    public function show(Member $member)
    {
        $member->load('role');
        return response()->json($member);
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'member_id_number' => 'nullable|string|max:100|unique:members',
            'username' => 'required|string|max:100|unique:members',
            'email' => 'nullable|string|email|max:255|unique:members',
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'join_date' => 'required|date',
            'password' => 'required|string|min:8|confirmed',
            'member_type' => 'required|' . Member::memberTypeRule(),
            'status' => 'nullable|in:Aktif,Tidak Aktif,Ditangguhkan',
            'role_id' => 'nullable|exists:roles,id',
            'nik' => 'nullable|string|max:16',
            'ktp_scan' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'selfie_with_ktp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Auto-generate member_id_number if not provided
        $memberIdNumber = $request->member_id_number;
        if (empty($memberIdNumber)) {
            // Determine prefix based on member_type
            $prefix = match($request->member_type) {
                'Pendiri' => 'PENDIRI',
                'Biasa' => 'BIASA',
                'Calon' => 'CALON',
                'Kehormatan' => 'HORMATAN',
                default => 'MEMBER',
            };
            
            // Get the last member with same prefix
            $lastMember = Member::where('member_id_number', 'like', $prefix . '%')
                ->orderBy('member_id_number', 'desc')
                ->first();
            
            if ($lastMember && $lastMember->member_id_number) {
                // Extract number from PENDIRI-001 -> 001
                $lastNumber = (int) substr($lastMember->member_id_number, strlen($prefix) + 1);
                $nextNumber = $lastNumber + 1;
            } else {
                // No members with this prefix yet, start from 1
                $nextNumber = 1;
            }
            
            $memberIdNumber = $prefix . '-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
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
            'member_id_number' => $memberIdNumber,
            'username' => $request->username,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'join_date' => $request->join_date,
            'password' => Hash::make($request->password),
            'member_type' => $request->member_type,
            'status' => $request->status ?? 'Aktif',
            'role_id' => $roleId,
            'nik' => $request->nik,
            'ktp_scan' => $ktpScanPath,
            'selfie_with_ktp' => $selfieWithKtpPath,
        ]);

        $member->load('role');

        return response()->json($member, 201);
    }

    public function update(Request $request, Member $member)
    {
        $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'member_id_number' => 'sometimes|required|string|max:100|unique:members,member_id_number,' . $member->id,
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

    public function destroy(Member $member)
    {
        $member->delete();
        return response()->json(['message' => 'Member deleted successfully']);
    }

    /**
     * Get available member types for dropdown
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
     * Dan otomatis buat Simpanan Pokok
     */
    public function approvePayment($memberId)
    {
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
            
            // Create or get Simpanan Pokok account
            $simpananPokok = SavingsAccount::firstOrCreate(
                [
                    'member_id' => $member->id,
                    'account_type' => 'pokok',
                ],
                [
                    'balance' => 0,
                ]
            );
            
            // Create transaction for Simpanan Pokok
            $transaction = Transaction::create([
                'savings_account_id' => $simpananPokok->id,
                'member_id' => $member->id,
                'transaction_type' => 'deposit',
                'amount' => $member->payment_amount,
                'description' => 'Simpanan Pokok dari pembayaran pendaftaran',
                'transaction_date' => now(),
            ]);
            
            // Update balance
            $simpananPokok->increment('balance', $member->payment_amount);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Pembayaran berhasil diverifikasi dan Simpanan Pokok telah dicatat.',
                'member' => $member->load('role'),
                'simpanan_pokok' => $simpananPokok->fresh(),
                'transaction' => $transaction,
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
     * Reject member payment (admin only)
     * Ubah status dari payment_pending ke rejected dengan alasan
     */
    public function rejectPayment(Request $request, $memberId)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);
        
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
                
                Log::info("Created Simpanan Pokok for member: {$member->name} ({$member->member_id_number})");
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
     */
    public function updateMonthlySavings(Request $request, Member $member)
    {
        $request->validate([
            'monthly_savings_amount' => 'required|numeric|in:500000,1000000,1500000,2000000',
        ]);

        $monthlySavingsAmount = $request->monthly_savings_amount;
        $simpananPokok = 500000; // Fixed Simpanan Pokok amount
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
                'simpanan_wajib_pertama' => $monthlySavingsAmount,
                'total' => $totalPaymentAmount,
            ],
        ]);
    }
}
