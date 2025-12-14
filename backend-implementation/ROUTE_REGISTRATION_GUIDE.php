<?php

/**
 * SETUP GUIDE: Register Dashboard and Finance Routes
 * 
 * Copy code snippet di bawah ini ke salah satu file berikut:
 * 
 * Option 1: bootstrap/app.php (Laravel 11+)
 * Option 2: routes/api.php (Laravel 8-10)
 */

// ============================================================
// OPTION 1: For Laravel 11+ (bootstrap/app.php)
// ============================================================

/*
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // =============================================
            // REGISTER DASHBOARD ROUTES HERE
            // =============================================
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('backend-implementation/routes/dashboard-routes.php'));
            
            // =============================================
            // REGISTER FINANCE ROUTES HERE (if not already)
            // =============================================
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('backend-implementation/routes/finance-routes.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Your middleware configuration
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Your exceptions configuration
    })->create();
*/

// ============================================================
// OPTION 2: For Laravel 8-10 (routes/api.php)
// ============================================================

/*
use Illuminate\Support\Facades\Route;

// Your existing routes...

// =============================================
// REGISTER DASHBOARD ROUTES HERE
// =============================================
require __DIR__.'/../backend-implementation/routes/dashboard-routes.php';

// =============================================
// REGISTER FINANCE ROUTES HERE (if not already)
// =============================================
require __DIR__.'/../backend-implementation/routes/finance-routes.php';
*/

// ============================================================
// AFTER REGISTRATION, RUN THESE COMMANDS:
// ============================================================

/*
# Clear all caches
php artisan route:clear
php artisan optimize:clear

# Cache routes again
php artisan route:cache

# Verify routes registered
php artisan route:list | grep dashboard
php artisan route:list | grep finance

# Expected output:
# GET|HEAD  api/dashboard/stats
# GET|HEAD  api/dashboard/membership-growth
# GET|HEAD  api/dashboard/savings-distribution
# GET|HEAD  api/dashboard/monthly-transactions
# GET|HEAD  api/dashboard/shu-distribution
# GET|HEAD  api/dashboard/recent-activities
# GET|HEAD  api/dashboard/upcoming-meetings
# GET|HEAD  api/finance/summary
# GET|HEAD  api/finance/monthly
# GET|HEAD  api/finance/transactions/recent
# GET|HEAD  api/finance/breakdown
*/

// ============================================================
// TESTING API:
// ============================================================

/*
# Get bearer token from login first
TOKEN="your_bearer_token_here"

# Test Dashboard Stats
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/dashboard/stats

# Test Finance Summary (Admin/Bendahara only)
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/finance/summary

# Test Membership Growth
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/dashboard/membership-growth?months=6

# Expected Response Format:
{
  "success": true,
  "message": "Dashboard statistics retrieved successfully",
  "data": {
    "totalAnggota": 150,
    "anggotaBaruBulanIni": 12,
    "totalSimpanan": 5000000000,
    ...
  }
}
*/

// ============================================================
// TROUBLESHOOTING:
// ============================================================

/*
1. Routes not found (404)?
   - Make sure routes are registered in bootstrap/app.php or routes/api.php
   - Run: php artisan route:clear && php artisan route:cache
   - Verify: php artisan route:list | grep dashboard

2. CORS error?
   - Check config/cors.php
   - Add frontend URL to allowed_origins
   - Make sure supports_credentials is true

3. 403 Forbidden for finance endpoints?
   - Finance endpoints require Admin or Bendahara role
   - Check user role in database
   - Verify middleware in finance-routes.php

4. Empty data?
   - Check database has records:
     SELECT COUNT(*) FROM members;
     SELECT COUNT(*) FROM savings_transactions;
     SELECT COUNT(*) FROM expenses;
   - Check status fields are correct (active, approved, scheduled)

5. Authentication error?
   - Make sure auth:sanctum middleware working
   - Check Authorization header: Bearer <token>
   - Verify token is valid and not expired
*/
