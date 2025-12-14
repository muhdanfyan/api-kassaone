<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

/**
 * Dashboard API Routes
 * 
 * All routes are protected by auth:sanctum
 * 
 * Base URL: /api/dashboard
 */

Route::middleware(['auth:sanctum'])->prefix('dashboard')->group(function () {
    
    // GET /api/dashboard/stats
    // Get main dashboard statistics (total anggota, simpanan, SHU, etc)
    Route::get('/stats', [DashboardController::class, 'getStats']);
    
    // GET /api/dashboard/membership-growth?months=6
    // Get membership growth data for chart
    Route::get('/membership-growth', [DashboardController::class, 'getMembershipGrowth']);
    
    // GET /api/dashboard/savings-distribution
    // Get savings distribution by type (Pokok, Wajib, Sukarela)
    Route::get('/savings-distribution', [DashboardController::class, 'getSavingsDistribution']);
    
    // GET /api/dashboard/monthly-transactions?months=6
    // Get monthly transaction count for chart
    Route::get('/monthly-transactions', [DashboardController::class, 'getMonthlyTransactions']);
    
    // GET /api/dashboard/shu-distribution
    // Get SHU distribution percentages
    Route::get('/shu-distribution', [DashboardController::class, 'getSHUDistribution']);
    
    // GET /api/dashboard/recent-activities?limit=10
    // Get recent activities in the system
    Route::get('/recent-activities', [DashboardController::class, 'getRecentActivities']);
    
    // GET /api/dashboard/upcoming-meetings?limit=10
    // Get upcoming scheduled meetings
    Route::get('/upcoming-meetings', [DashboardController::class, 'getUpcomingMeetings']);
    
});
