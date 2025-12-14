<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinanceController;

/**
 * Finance Dashboard API Routes
 * 
 * All routes are protected by:
 * - auth:sanctum - User must be authenticated
 * - role:Admin,Bendahara - User must have Admin or Bendahara role
 * 
 * Base URL: /api/finance
 */

Route::middleware(['auth:sanctum', 'role:Admin,Bendahara'])->prefix('finance')->group(function () {
    
    // GET /api/finance/summary
    // Get finance summary (total kas, pemasukan, pengeluaran, laba/rugi bulan ini)
    Route::get('/summary', [FinanceController::class, 'getSummary']);
    
    // GET /api/finance/monthly?months=6
    // Get monthly finance data for chart
    Route::get('/monthly', [FinanceController::class, 'getMonthlyData']);
    
    // GET /api/finance/transactions/recent?limit=10
    // Get recent transactions (combined savings and expenses)
    Route::get('/transactions/recent', [FinanceController::class, 'getRecentTransactions']);
    
    // GET /api/finance/breakdown?start_date=2024-01-01&end_date=2024-12-31
    // Get detailed breakdown by savings type and expense category
    Route::get('/breakdown', [FinanceController::class, 'getBreakdown']);
    
});
