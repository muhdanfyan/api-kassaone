<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Perumahan\Controllers\DashboardController;
use App\Modules\Perumahan\Controllers\ResidentController;
use App\Modules\Perumahan\Controllers\SecurityController;
use App\Modules\Perumahan\Controllers\WasteController;
use App\Modules\Perumahan\Controllers\ServiceController;
use App\Modules\Perumahan\Controllers\FeeController;
use App\Modules\Perumahan\Controllers\FeeSettingController;
use App\Modules\Perumahan\Controllers\ReportController;
use App\Modules\Perumahan\Controllers\SettingController;
use App\Modules\Perumahan\Controllers\StaffTeamController;
use App\Modules\Perumahan\Controllers\AreaController;
use App\Modules\Perumahan\Controllers\PaymentExportController;
use App\Modules\Perumahan\Controllers\InfoController;
use App\Modules\Perumahan\Middleware\PerumahanMiddleware;

/*
|--------------------------------------------------------------------------
| Perumahan Module Routes
|--------------------------------------------------------------------------
|
| Routes untuk Estate Management System (Perumahan)
| Base URL: /api/perumahan
| Middleware: auth:admin + PerumahanMiddleware
|
*/

Route::prefix('perumahan')->middleware(['auth:admin', PerumahanMiddleware::class])->group(function () {
    
    // System Info API (public info - no extra middleware)
    Route::get('/info', [InfoController::class, 'getSystemInfo']);
    
    // Dashboard APIs
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::get('/charts', [DashboardController::class, 'charts']);
    });

    // Residents Management
    Route::prefix('residents')->group(function () {
        Route::get('/statistics', [ResidentController::class, 'statistics']);
        Route::get('/', [ResidentController::class, 'index']);
        Route::post('/', [ResidentController::class, 'store']);
        Route::get('/{id}', [ResidentController::class, 'show']);
        Route::put('/{id}', [ResidentController::class, 'update']);
        Route::delete('/{id}', [ResidentController::class, 'destroy']);
    });

    // Security Module
    Route::prefix('security')->group(function () {
        // Statistics and special endpoints (before dynamic routes)
        Route::get('/statistics', [SecurityController::class, 'getStatistics']);
        Route::get('/incidents', [SecurityController::class, 'getIncidents']);
        Route::get('/incidents/active', [SecurityController::class, 'getActiveIncidents']);
        Route::post('/patrol/generate-schedule', [SecurityController::class, 'generatePatrolSchedule']);
        
        // Security Logs CRUD
        Route::get('/', [SecurityController::class, 'getLogs']);
        Route::post('/', [SecurityController::class, 'store']);
        Route::get('/{id}', [SecurityController::class, 'show']);
        Route::put('/{id}', [SecurityController::class, 'update']);
        Route::delete('/{id}', [SecurityController::class, 'destroy']);
    });
    // Waste Management
    Route::prefix('waste')->group(function () {
        // Schedules
        Route::get('/schedules', [WasteController::class, 'getSchedules']);
        Route::post('/schedules', [WasteController::class, 'storeSchedule']);
        Route::get('/schedules/today', [WasteController::class, 'getTodaySchedule']);
        Route::get('/schedules/{id}', [WasteController::class, 'showSchedule']);
        Route::put('/schedules/{id}', [WasteController::class, 'updateSchedule']);
        Route::delete('/schedules/{id}', [WasteController::class, 'destroySchedule']);
        
        // Collections
        Route::get('/collections', [WasteController::class, 'getCollections']);
        Route::post('/collections', [WasteController::class, 'storeCollection']);
        Route::get('/collections/{id}', [WasteController::class, 'showCollection']);
        Route::put('/collections/{id}', [WasteController::class, 'updateCollection']);
        Route::delete('/collections/{id}', [WasteController::class, 'destroyCollection']);
        
        // Statistics
        Route::get('/statistics', [WasteController::class, 'getStatistics']);
    });

    // Services & Complaints
    Route::prefix('services')->group(function () {
        Route::get('/statistics', [ServiceController::class, 'getStatistics']);
        Route::get('/', [ServiceController::class, 'index']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::get('/{id}', [ServiceController::class, 'show']);
        Route::put('/{id}', [ServiceController::class, 'update']);
        Route::delete('/{id}', [ServiceController::class, 'destroy']);
        Route::put('/{id}/status', [ServiceController::class, 'updateStatus']);
        Route::put('/{id}/feedback', [ServiceController::class, 'addFeedback']);
    });

    // Fee Management
    Route::prefix('fees')->group(function () {
        // Statistics (must be before /{id})
        Route::get('/statistics', [FeeController::class, 'getStatistics']);
        
        // Export Payments (must be before other payment routes)
        Route::get('/payments/export', [PaymentExportController::class, 'export']);
        
        // Payments (must be before /{id})
        Route::get('/payments', [FeeController::class, 'getPayments']);
        Route::post('/payments', [FeeController::class, 'recordPayment']);
        Route::post('/payments/bulk-generate', [FeeController::class, 'bulkGenerate']);
        Route::get('/payments/overdue', [FeeController::class, 'getOverduePayments']);
        Route::get('/payments/{id}', [FeeController::class, 'showPayment']);
        Route::put('/payments/{id}', [FeeController::class, 'updatePayment']);
        Route::delete('/payments/{id}', [FeeController::class, 'destroyPayment']);
        Route::get('/payments/{house_number}/history', [FeeController::class, 'getPaymentHistory']);
        
        // Fee Types
        Route::get('/', [FeeController::class, 'index']);
        Route::post('/', [FeeController::class, 'store']);
        Route::get('/{id}', [FeeController::class, 'show']);
        Route::put('/{id}', [FeeController::class, 'update']);
        Route::delete('/{id}', [FeeController::class, 'destroy']);
    });

    // Reports APIs
    Route::prefix('reports')->group(function () {
        Route::get('/monthly-summary', [ReportController::class, 'monthlySummary']);
        Route::get('/financial', [ReportController::class, 'financial']);
        Route::get('/residents-list', [ReportController::class, 'residentsList']);
        Route::get('/payment-status', [ReportController::class, 'paymentStatus']);
        Route::get('/service-performance', [ReportController::class, 'servicePerformance']);
        Route::get('/fee-history', [ReportController::class, 'feeHistory']);
        Route::get('/service-history', [ReportController::class, 'serviceHistory']);
        
        // PDF Report endpoints
        Route::get('/fee-monthly/pdf', [ReportController::class, 'generateFeeReport']);
        Route::get('/security/pdf', [ReportController::class, 'generateSecurityReport']);
        Route::get('/services/pdf', [ReportController::class, 'generateServiceReport']);
    });

    // Settings APIs
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::get('/category/{category}', [SettingController::class, 'getByCategory']);
        Route::get('/{key}', [SettingController::class, 'getSetting']);
        Route::put('/', [SettingController::class, 'update']);
        Route::put('/{key}', [SettingController::class, 'updateSingle']);
        Route::post('/reset', [SettingController::class, 'reset']);
    });

    // Fee Settings APIs (Master Data for Fee Types)
    Route::prefix('fee-settings')->group(function () {
        Route::put('/bulk', [FeeSettingController::class, 'bulkUpdate']);
        Route::get('/', [FeeSettingController::class, 'index']);
        Route::post('/', [FeeSettingController::class, 'store']);
        Route::get('/{id}', [FeeSettingController::class, 'show']);
        Route::put('/{id}', [FeeSettingController::class, 'update']);
        Route::delete('/{id}', [FeeSettingController::class, 'destroy']);
    });

    // Master Data APIs
    Route::prefix('master')->group(function () {
        // Staff Teams
        Route::get('/staff-teams', [StaffTeamController::class, 'index']);
        Route::get('/staff-teams/{id}', [StaffTeamController::class, 'show']);
        Route::post('/staff-teams', [StaffTeamController::class, 'store']);
        Route::put('/staff-teams/{id}', [StaffTeamController::class, 'update']);
        Route::delete('/staff-teams/{id}', [StaffTeamController::class, 'destroy']);

        // Areas
        Route::get('/areas', [AreaController::class, 'index']);
        Route::get('/areas/{id}', [AreaController::class, 'show']);
        Route::post('/areas', [AreaController::class, 'store']);
        Route::put('/areas/{id}', [AreaController::class, 'update']);
        Route::delete('/areas/{id}', [AreaController::class, 'destroy']);
    });
});
