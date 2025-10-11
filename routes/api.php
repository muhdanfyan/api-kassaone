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
use App\Http\Controllers\Api\ShuDistributionController;
use App\Http\Controllers\Api\ShuMemberAllocationController;
use App\Http\Controllers\Api\TestimonialController;

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

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/general-transactions', [GeneralTransactionController::class, 'index']);
    Route::get('/general-transactions/chart', [GeneralTransactionController::class, 'chart']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Member Management
    Route::apiResource('/members', MemberController::class);

    // Savings Accounts
    Route::get('/members/{member}/savings', [SavingsAccountController::class, 'index']);
    Route::post('/members/{member}/savings', [SavingsAccountController::class, 'store']);
    Route::get('/savings/{savingsAccount}', [SavingsAccountController::class, 'show']);
    Route::put('/savings/{savingsAccount}', [SavingsAccountController::class, 'update']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('/transactions', [TransactionController::class, 'store']);

    // Meetings
    Route::apiResource('/meetings', MeetingController::class);

    // Meeting Attendance
    Route::get('/meetings/{meeting}/attendance', [MeetingAttendanceController::class, 'index']);
    Route::post('/meetings/{meeting}/attendance', [MeetingAttendanceController::class, 'store']);
    Route::put('/meeting-attendance/{meetingAttendance}', [MeetingAttendanceController::class, 'update']);

    // SHU Distributions
    Route::apiResource('/shu-distributions', ShuDistributionController::class);

    // SHU Member Allocations
    Route::get('/shu-distributions/{shuDistribution}/allocations', [ShuMemberAllocationController::class, 'index']);
    Route::post('/shu-distributions/{shuDistribution}/allocations', [ShuMemberAllocationController::class, 'store']);
    Route::get('/shu-allocations/{shuMemberAllocation}', [ShuMemberAllocationController::class, 'show']);
    Route::put('/shu-allocations/{shuMemberAllocation}', [ShuMemberAllocationController::class, 'update']);

    // Testimonials
    Route::post('/testimonials', [TestimonialController::class, 'store']);
});