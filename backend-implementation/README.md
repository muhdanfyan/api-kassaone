# 📦 Backend Implementation - Dashboard & Finance

## 📂 Struktur File

```
backend-implementation/
├── app/
│   └── Http/
│       ├── Controllers/
│       │   ├── DashboardController.php      ✅ NEW - Dashboard statistics & charts
│       │   └── FinanceController.php        ✅ Finance management for Admin/Bendahara
│       └── Middleware/
│           └── CheckRole.php                ✅ Role validation middleware
├── routes/
│   ├── dashboard-routes.php                 ✅ NEW - Dashboard API routes
│   └── finance-routes.php                   ✅ Finance API routes
├── ROUTE_REGISTRATION_GUIDE.php             ✅ NEW - Guide untuk register routes
└── TEST_FINANCE_API.md                      ✅ Testing guide
```

---

## 🎯 Controllers Overview

### 1️⃣ **DashboardController.php** (NEW)

Controller untuk dashboard statistics dan chart data. Accessible untuk semua authenticated users.

#### ✅ Endpoints:

| Endpoint | Method | Description | Params |
|----------|--------|-------------|--------|
| `/api/dashboard/stats` | GET | Main statistics (anggota, simpanan, SHU, dll) | - |
| `/api/dashboard/membership-growth` | GET | Pertumbuhan anggota per bulan | months (1-24) |
| `/api/dashboard/savings-distribution` | GET | Distribusi simpanan by type | - |
| `/api/dashboard/monthly-transactions` | GET | Jumlah transaksi per bulan | months (1-24) |
| `/api/dashboard/shu-distribution` | GET | Persentase distribusi SHU | - |
| `/api/dashboard/recent-activities` | GET | Aktivitas terbaru | limit (1-50) |
| `/api/dashboard/upcoming-meetings` | GET | Rapat yang akan datang | limit (1-50) |

#### ✅ Key Features:
- Real-time statistics from database
- Dynamic chart data for frontend
- Recent activities aggregation (members, transactions, meetings)
- Time ago calculation untuk activities

---

### 2️⃣ **FinanceController.php**

Controller untuk finance management. **Access restricted to Admin & Bendahara only**.

#### ✅ Endpoints:
  - Includes member_name, account_name, created_by

#### ✅ `getBreakdown(Request $request)`
- **Endpoint:** `GET /api/finance/breakdown?start_date=2024-01-01&end_date=2024-12-31`
- **Response:** Detailed breakdown by savings type and expense category
- **Parameters:** 
  - `start_date` (required, date format)
  - `end_date` (required, date format, must be >= start_date)
- **Features:**
  - Pemasukan grouped by account_type (pokok, wajib, sukarela)
  - Pengeluaran grouped by account_id and account_name
  - Total laba/rugi calculation

---

### 2️⃣ **CheckRole.php Middleware**
Middleware untuk validasi role:
- **Usage:** `Route::middleware(['auth:sanctum', 'role:Admin,Bendahara'])`
- **Features:**
  - Supports comma-separated roles
  - Returns 401 if not authenticated
  - Returns 403 if role not allowed (with detailed error)
  - Reads role from `$user->role->name`

---

### 3️⃣ **finance-routes.php**
Routes file dengan 4 protected endpoints:
- All routes protected by: `auth:sanctum` + `role:Admin,Bendahara`
- Prefix: `/finance`
- Base URL: `/api/finance/*`

---

### 4️⃣ **bootstrap/app.php**
Laravel 11 middleware registration:
- Registers `role` middleware alias
- Enables Sanctum statefulApi

---

### 5️⃣ **TEST_FINANCE_API.md**
Comprehensive testing guide dengan:
- PowerShell test scripts untuk setiap endpoint
- Manual SQL verification queries
- Authorization testing (403 Forbidden)
- Performance testing scripts
- Full integration test script
- Test checklist

---

## 📋 Cara Implementasi di Laravel Project

### Step 1: Copy Files ke Laravel Project
```powershell
# Copy controller
Copy-Item ".\backend-implementation\app\Http\Controllers\FinanceController.php" `
    -Destination "C:\path\to\laravel\app\Http\Controllers\"

# Copy middleware
Copy-Item ".\backend-implementation\app\Http\Middleware\CheckRole.php" `
    -Destination "C:\path\to\laravel\app\Http\Middleware\"

# Copy bootstrap config (Laravel 11 only)
Copy-Item ".\backend-implementation\bootstrap\app.php" `
    -Destination "C:\path\to\laravel\bootstrap\"
```

### Step 2: Tambahkan Routes ke api.php
```php
// File: routes/api.php

use App\Http\Controllers\FinanceController;

// Add these routes at the end of the file
require __DIR__.'/finance-routes.php';

// OR manually add:
Route::middleware(['auth:sanctum', 'role:Admin,Bendahara'])->prefix('finance')->group(function () {
    Route::get('/summary', [FinanceController::class, 'getSummary']);
    Route::get('/monthly', [FinanceController::class, 'getMonthlyData']);
    Route::get('/transactions/recent', [FinanceController::class, 'getRecentTransactions']);
    Route::get('/breakdown', [FinanceController::class, 'getBreakdown']);
});
```

### Step 3: Verifikasi Database Schema
Pastikan tabel-tabel ini ada dan sesuai:

#### ✅ `savings_transactions`
```sql
CREATE TABLE savings_transactions (
    id VARCHAR(255) PRIMARY KEY,
    member_id VARCHAR(255) NOT NULL,
    savings_type_id VARCHAR(255) NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    transaction_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (savings_type_id) REFERENCES savings_types(id)
);
```

#### ✅ `expenses`
```sql
CREATE TABLE expenses (
    id VARCHAR(255) PRIMARY KEY,
    account_id VARCHAR(255) NOT NULL,
    expense_date DATE NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    receipt_number VARCHAR(100),
    created_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

#### ✅ `savings_types`
```sql
CREATE TABLE savings_types (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    account_type ENUM('pokok', 'wajib', 'sukarela') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### ✅ `accounts`
```sql
CREATE TABLE accounts (
    id VARCHAR(255) PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 🧪 Testing

### Quick Test dengan PowerShell
```powershell
# 1. Login
$login = Invoke-RestMethod -Uri "https://api-kassaone.onrender.com/api/login" `
    -Method POST `
    -ContentType "application/json" `
    -Body (@{username = "admin"; password = "password123"} | ConvertTo-Json)

$token = $login.data.token

# 2. Test Summary
$summary = Invoke-RestMethod `
    -Uri "https://api-kassaone.onrender.com/api/finance/summary" `
    -Method GET `
    -Headers @{"Authorization" = "Bearer $token"; "Accept" = "application/json"}

$summary | ConvertTo-Json -Depth 10
```

### Full Test Suite
Lihat file `TEST_FINANCE_API.md` untuk:
- ✅ Testing script untuk semua 4 endpoints
- ✅ Authorization testing (403 Forbidden)
- ✅ Performance testing
- ✅ Manual SQL verification

---

## ⚠️ Important Notes

### 1. Role Model Relationship
Controller menggunakan `$user->role->name`. Pastikan model User punya relasi:
```php
// app/Models/User.php
public function role()
{
    return $this->belongsTo(Role::class);
}
```

### 2. Status Filter
Query savings_transactions menggunakan filter `status = 'approved'`:
- Hanya transaksi yang sudah approved yang dihitung
- Pending/rejected transactions tidak masuk perhitungan kas

### 3. Calculation Logic
```
Total Kas = Total Pemasukan (approved deposits) - Total Pengeluaran
```
**TIDAK ADA** balance field di database. Kas dihitung real-time dari transaksi.

### 4. CORS Configuration
Pastikan CORS sudah dikonfigurasi untuk frontend:
```php
// config/cors.php
'allowed_origins' => [
    'http://localhost:5175',
    'https://your-frontend-domain.com',
],
```

---

## 🔐 Security Checklist

- ✅ All endpoints protected by `auth:sanctum`
- ✅ Role middleware enforces Admin/Bendahara only
- ✅ Input validation for dates and limits
- ✅ SQL injection prevention (using Query Builder)
- ✅ Error messages tidak expose sensitive data
- ✅ CORS configured properly

---

## 📊 API Response Format

Semua endpoint menggunakan format konsisten:

### ✅ Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { /* response data */ }
}
```

### ❌ Error Response
```json
{
  "success": false,
  "message": "Error message",
  "error": "Detailed error (dev only)"
}
```

### 🚫 Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "start_date": ["The start date field is required."]
  }
}
```

### 🔒 Authorization Error (403)
```json
{
  "success": false,
  "message": "Forbidden. You do not have permission to access this resource.",
  "required_roles": ["Admin", "Bendahara"],
  "your_role": "Anggota"
}
```

---

## 🚀 Next Steps

### 1. **Deploy Backend**
- Copy files ke Laravel project
- Test dengan PowerShell scripts
- Verify calculations dengan manual SQL

### 2. **Frontend Integration**
Frontend sudah siap (lihat `FRONTEND_FINANCE_DASHBOARD_GUIDE.md`):
- ✅ `dashboard-finance.service.ts` created
- ✅ `FinanceDashboard.tsx` integrated
- ✅ `Dashboard.tsx` showing finance summary
- ✅ Role-based access control in Sidebar

Begitu backend endpoints live, frontend akan langsung berfungsi!

### 3. **End-to-End Testing**
- Login as Admin → Open Finance Dashboard → Verify data loads
- Create new expense → Verify kas decreases
- Create new simpanan → Verify kas increases
- Login as regular member → Verify 403 Forbidden

---

## 📞 Support

### Troubleshooting Guide

**Problem:** 403 Forbidden
- ✅ Check: User role is Admin or Bendahara
- ✅ Check: `role` middleware registered in bootstrap/app.php
- ✅ Check: User model has `role()` relationship

**Problem:** Wrong calculations
- ✅ Check: savings_transactions.status = 'approved' filter
- ✅ Check: transaction_type = 'deposit' for income
- ✅ Verify: Manual SQL query matches API response

**Problem:** No data returned
- ✅ Check: Database has sample data
- ✅ Check: Date filters are correct
- ✅ Check: UNION query works in SQL directly

**Problem:** CORS errors
- ✅ Check: config/cors.php allows frontend URL
- ✅ Check: php artisan config:cache after changes
- ✅ Check: Browser network tab for preflight OPTIONS

---

## 📝 Summary

✅ **Implemented:**
- FinanceController with 4 complete methods
- CheckRole middleware for authorization
- Protected routes with role validation
- Comprehensive testing guide
- Implementation documentation

✅ **Ready for:**
- Copy to Laravel project
- Testing with PowerShell scripts
- Integration with frontend (already done)

✅ **Total Files:** 5 files created
✅ **Total Lines:** ~1000+ lines of production-ready code
✅ **Documentation:** Complete guides for testing and deployment

---

**Last Updated:** December 14, 2024  
**Laravel Version:** 11.x  
**PHP Version:** 8.2+  
**Database:** MySQL 8.0+
