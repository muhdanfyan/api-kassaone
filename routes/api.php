<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GeneralTransactionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\SavingsAccountController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\MeetingAttendanceController;
use App\Http\Controllers\Api\ShuDistributionController as ApiShuDistributionController;
use App\Http\Controllers\Api\ShuMemberAllocationController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\SHUDistributionController;
use App\Http\Controllers\ShuPercentageSettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/public/upload-payment-proof', [AuthController::class, 'uploadPaymentProofPublic']);

// Routes for pending members (requires JWT but allows pending status)
Route::middleware(['auth:api'])->group(function () {
    // Allow pending members to upload payment proof
    Route::post('/member/upload-payment-proof', [AuthController::class, 'uploadPaymentProof']);
    Route::get('/member/status', [AuthController::class, 'getMemberStatus']);
});

// Routes that require JWT authentication only (GET requests)
Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/refresh-csrf', [AuthController::class, 'refreshCsrfToken']);
    
    // GET routes - only JWT required
    Route::get('/general-transactions', [GeneralTransactionController::class, 'index']);
    Route::get('/general-transactions/chart', [GeneralTransactionController::class, 'chart']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/member-types', [MemberController::class, 'getMemberTypes']);
    Route::get('/members', [MemberController::class, 'index']);
    Route::get('/members/{member}', [MemberController::class, 'show']);
    Route::get('/members/{member}/savings', [SavingsAccountController::class, 'index']);
    
    // Payment Management (Admin)
    Route::get('/members/verification/status', [MemberController::class, 'getByVerificationStatus']);
    Route::get('/members/payment/stats', [MemberController::class, 'getPaymentStats']);
    Route::get('/members/simpanan-pokok/stats', [MemberController::class, 'getSimpananPokokStats']);
    Route::post('/members/migrate-old-simpanan-pokok', [MemberController::class, 'migrateOldMembersSimpananPokok']);
    
    Route::get('/savings/{savingsAccount}', [SavingsAccountController::class, 'show']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::get('/meetings', [MeetingController::class, 'index']);
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show']);
    Route::get('/meetings/{meeting}/attendance', [MeetingAttendanceController::class, 'index']);
    
    // SHU Distributions - Enhanced with new endpoints
    Route::get('/shu-distributions', [SHUDistributionController::class, 'index']);
    Route::get('/shu-distributions/{id}', [SHUDistributionController::class, 'show']);
    Route::get('/shu-distributions/{id}/allocations', [SHUDistributionController::class, 'getAllocations']);
    Route::get('/shu-distributions/{id}/report', [SHUDistributionController::class, 'report']);
    
    // SHU Percentage Settings
    Route::get('/shu-settings', [ShuPercentageSettingController::class, 'index']);
    Route::get('/shu-settings/{id}', [ShuPercentageSettingController::class, 'show']);
    
    // Legacy SHU routes (if needed)
    // Route::get('/shu-distributions', [ApiShuDistributionController::class, 'index']);
    // Route::get('/shu-distributions/{shuDistribution}', [ApiShuDistributionController::class, 'show']);
    // Route::get('/shu-distributions/{shuDistribution}/allocations', [ShuMemberAllocationController::class, 'index']);
    Route::get('/shu-allocations/{shuMemberAllocation}', [ShuMemberAllocationController::class, 'show']);
    
    // Organization Management
    Route::get('/organization', [OrganizationController::class, 'index']);
    Route::get('/roles', [OrganizationController::class, 'getRoles']);
    
    // System Settings
    Route::get('/settings', [SettingsController::class, 'index']);
});

// Routes that require both JWT authentication AND CSRF token (POST, PUT, PATCH, DELETE)
Route::middleware(['auth:api', \App\Http\Middleware\ValidateCsrfToken::class])->group(function () {
    // Logout requires CSRF
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Member Management
    Route::post('/members', [MemberController::class, 'store']);
    Route::put('/members/{member}', [MemberController::class, 'update']);
    Route::delete('/members/{member}', [MemberController::class, 'destroy']);
    
    // Payment Approval (Admin only)
    Route::post('/members/{member}/approve-payment', [MemberController::class, 'approvePayment']);
    Route::post('/members/{member}/reject-payment', [MemberController::class, 'rejectPayment']);
    
    // Member Profile Updates (for pending members)
    Route::put('/members/{member}/personal-info', [MemberController::class, 'updatePersonalInfo']);
    Route::put('/members/{member}/heir-info', [MemberController::class, 'updateHeirInfo']);
    Route::put('/members/{member}/monthly-savings', [MemberController::class, 'updateMonthlySavings']);

    // Savings Accounts
    Route::post('/members/{member}/savings', [SavingsAccountController::class, 'store']);
    Route::put('/savings/{savingsAccount}', [SavingsAccountController::class, 'update']);

    // Transactions
    Route::post('/transactions', [TransactionController::class, 'store']);

    // Meetings
    Route::post('/meetings', [MeetingController::class, 'store']);
    Route::put('/meetings/{meeting}', [MeetingController::class, 'update']);
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy']);

    // Meeting Attendance
    Route::post('/meetings/{meeting}/attendance', [MeetingAttendanceController::class, 'store']);
    Route::put('/meeting-attendance/{meetingAttendance}', [MeetingAttendanceController::class, 'update']);

    // SHU Distributions - Enhanced with new endpoints
    Route::post('/shu-distributions', [SHUDistributionController::class, 'store']);
    Route::put('/shu-distributions/{id}', [SHUDistributionController::class, 'update']);
    Route::delete('/shu-distributions/{id}', [SHUDistributionController::class, 'destroy']);
    Route::post('/shu-distributions/{id}/calculate', [SHUDistributionController::class, 'calculateAllocations']);
    Route::post('/shu-distributions/{id}/approve', [SHUDistributionController::class, 'approve']);
    Route::post('/shu-distributions/{id}/payout', [SHUDistributionController::class, 'batchPayout']);
    
    // SHU Percentage Settings
    Route::post('/shu-settings', [ShuPercentageSettingController::class, 'store']);
    Route::put('/shu-settings/{id}', [ShuPercentageSettingController::class, 'update']);
    Route::delete('/shu-settings/{id}', [ShuPercentageSettingController::class, 'destroy']);
    Route::post('/shu-settings/{id}/activate', [ShuPercentageSettingController::class, 'activate']);
    Route::post('/shu-settings/{id}/preview', [ShuPercentageSettingController::class, 'preview']);
    
    // Legacy SHU routes (if needed)
    // Route::post('/shu-distributions', [ApiShuDistributionController::class, 'store']);
    // Route::put('/shu-distributions/{shuDistribution}', [ApiShuDistributionController::class, 'update']);
    // Route::delete('/shu-distributions/{shuDistribution}', [ApiShuDistributionController::class, 'destroy']);

    // SHU Member Allocations
    Route::post('/shu-distributions/{shuDistribution}/allocations', [ShuMemberAllocationController::class, 'store']);
    Route::put('/shu-allocations/{shuMemberAllocation}', [ShuMemberAllocationController::class, 'update']);

    // Testimonials
    Route::post('/testimonials', [TestimonialController::class, 'store']);
    
    // Organization Management
    Route::put('/members/{member}/position', [OrganizationController::class, 'updatePosition']);
    
    // System Settings (Admin only)
    Route::put('/settings', [SettingsController::class, 'update']);
});