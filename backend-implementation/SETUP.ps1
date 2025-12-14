# 🚀 Quick Setup Script - Finance Dashboard Backend
# Copy this script to your Laravel project root and run it

Write-Host "`n=== Finance Dashboard Backend Setup ===" -ForegroundColor Cyan
Write-Host "This script will copy all necessary files to your Laravel project`n" -ForegroundColor Yellow

# Configuration
$sourceDir = ".\backend-implementation"
$laravelRoot = Read-Host "Enter your Laravel project path (e.g., C:\xampp\htdocs\kassaone-api)"

if (-not (Test-Path $laravelRoot)) {
    Write-Host "❌ Laravel project path not found: $laravelRoot" -ForegroundColor Red
    exit 1
}

Write-Host "`n📂 Laravel project found: $laravelRoot" -ForegroundColor Green

# Step 1: Copy FinanceController
Write-Host "`n1. Copying FinanceController..." -ForegroundColor Yellow
$controllerDest = Join-Path $laravelRoot "app\Http\Controllers"
New-Item -ItemType Directory -Force -Path $controllerDest | Out-Null
Copy-Item "$sourceDir\app\Http\Controllers\FinanceController.php" -Destination $controllerDest -Force
Write-Host "   ✅ FinanceController.php copied" -ForegroundColor Green

# Step 2: Copy CheckRole Middleware
Write-Host "`n2. Copying CheckRole Middleware..." -ForegroundColor Yellow
$middlewareDest = Join-Path $laravelRoot "app\Http\Middleware"
New-Item -ItemType Directory -Force -Path $middlewareDest | Out-Null
Copy-Item "$sourceDir\app\Http\Middleware\CheckRole.php" -Destination $middlewareDest -Force
Write-Host "   ✅ CheckRole.php copied" -ForegroundColor Green

# Step 3: Copy Routes
Write-Host "`n3. Copying finance routes..." -ForegroundColor Yellow
$routesDest = Join-Path $laravelRoot "routes"
Copy-Item "$sourceDir\routes\finance-routes.php" -Destination $routesDest -Force
Write-Host "   ✅ finance-routes.php copied" -ForegroundColor Green

# Step 4: Copy Testing Guide
Write-Host "`n4. Copying testing guide..." -ForegroundColor Yellow
Copy-Item "$sourceDir\TEST_FINANCE_API.md" -Destination $laravelRoot -Force
Write-Host "   ✅ TEST_FINANCE_API.md copied to project root" -ForegroundColor Green

# Step 5: Update bootstrap/app.php (Laravel 11 only)
Write-Host "`n5. Checking Laravel version..." -ForegroundColor Yellow
$composerJson = Get-Content (Join-Path $laravelRoot "composer.json") | ConvertFrom-Json
$laravelVersion = $composerJson.require."laravel/framework" -replace '[^0-9.]', ''

if ($laravelVersion -ge "11.0") {
    Write-Host "   📦 Laravel 11 detected" -ForegroundColor Cyan
    $updateBootstrap = Read-Host "   Update bootstrap/app.php? (Y/N)"
    
    if ($updateBootstrap -eq "Y" -or $updateBootstrap -eq "y") {
        # Backup original
        $bootstrapPath = Join-Path $laravelRoot "bootstrap\app.php"
        Copy-Item $bootstrapPath "$bootstrapPath.backup" -Force
        Write-Host "   💾 Backup created: bootstrap\app.php.backup" -ForegroundColor Gray
        
        # Copy new version
        Copy-Item "$sourceDir\bootstrap\app.php" -Destination (Join-Path $laravelRoot "bootstrap") -Force
        Write-Host "   ✅ bootstrap/app.php updated" -ForegroundColor Green
    } else {
        Write-Host "   ⏭️  Skipped bootstrap/app.php update" -ForegroundColor Gray
        Write-Host "   ⚠️  Remember to manually register 'role' middleware!" -ForegroundColor Yellow
    }
} else {
    Write-Host "   📦 Laravel $laravelVersion detected (not 11)" -ForegroundColor Cyan
    Write-Host "   ⚠️  Manually register middleware in app/Http/Kernel.php:" -ForegroundColor Yellow
    Write-Host "      protected `$middlewareAliases = [" -ForegroundColor Gray
    Write-Host "          'role' => \App\Http\Middleware\CheckRole::class," -ForegroundColor Gray
    Write-Host "      ];" -ForegroundColor Gray
}

# Step 6: Add routes to api.php
Write-Host "`n6. Updating routes/api.php..." -ForegroundColor Yellow
$apiRoutesPath = Join-Path $laravelRoot "routes\api.php"
$apiRoutes = Get-Content $apiRoutesPath -Raw

if ($apiRoutes -notlike "*finance-routes*") {
    Add-Content -Path $apiRoutesPath -Value "`n// Finance Dashboard Routes (Admin & Bendahara only)`nrequire __DIR__.'/finance-routes.php';"
    Write-Host "   ✅ Finance routes added to api.php" -ForegroundColor Green
} else {
    Write-Host "   ℹ️  Finance routes already exist in api.php" -ForegroundColor Gray
}

# Summary
Write-Host "`n=== Setup Complete ===" -ForegroundColor Cyan
Write-Host "`n📋 Files Copied:" -ForegroundColor Yellow
Write-Host "   ✅ app/Http/Controllers/FinanceController.php" -ForegroundColor Green
Write-Host "   ✅ app/Http/Middleware/CheckRole.php" -ForegroundColor Green
Write-Host "   ✅ routes/finance-routes.php" -ForegroundColor Green
Write-Host "   ✅ TEST_FINANCE_API.md" -ForegroundColor Green

Write-Host "`n🔧 Next Steps:" -ForegroundColor Yellow
Write-Host "   1. Check database schema (savings_transactions, expenses, accounts, savings_types)" -ForegroundColor White
Write-Host "   2. Ensure User model has role() relationship" -ForegroundColor White
Write-Host "   3. Configure CORS in config/cors.php" -ForegroundColor White
Write-Host "   4. Clear config cache: php artisan config:cache" -ForegroundColor White
Write-Host "   5. Test API with TEST_FINANCE_API.md scripts" -ForegroundColor White

Write-Host "`n🧪 Quick Test:" -ForegroundColor Yellow
Write-Host "   cd $laravelRoot" -ForegroundColor Gray
Write-Host "   php artisan serve" -ForegroundColor Gray
Write-Host "   # Then run scripts from TEST_FINANCE_API.md" -ForegroundColor Gray

Write-Host "`n✨ Setup completed successfully!`n" -ForegroundColor Green
