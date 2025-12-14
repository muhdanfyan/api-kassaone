# 🧪 Testing Finance Dashboard API

## Prerequisites
- Backend server running (`php artisan serve`)
- User authenticated with JWT token
- User role must be **Admin** or **Bendahara**

---

## 🔑 Get Authentication Token

```powershell
# Login to get token
$loginResponse = Invoke-RestMethod -Uri "https://api-kassaone.onrender.com/api/login" `
    -Method POST `
    -ContentType "application/json" `
    -Body (@{
        username = "admin"  # Replace with actual username
        password = "password123"  # Replace with actual password
    } | ConvertTo-Json)

$token = $loginResponse.data.token
Write-Host "Token: $token" -ForegroundColor Green
```

---

## 1️⃣ Test Finance Summary Endpoint

### Request
```powershell
# GET /api/finance/summary
$summaryResponse = Invoke-RestMethod `
    -Uri "https://api-kassaone.onrender.com/api/finance/summary" `
    -Method GET `
    -Headers @{
        "Authorization" = "Bearer $token"
        "Accept" = "application/json"
    }

$summaryResponse | ConvertTo-Json -Depth 10
```

### Expected Response
```json
{
  "success": true,
  "message": "Finance summary retrieved successfully",
  "data": {
    "total_kas": 50000000.0,
    "pemasukan_bulan_ini": 10000000.0,
    "pengeluaran_bulan_ini": 3000000.0,
    "laba_rugi_bulan_ini": 7000000.0
  }
}
```

### Validation Checks
- ✅ `total_kas` = Total Pemasukan - Total Pengeluaran (all time)
- ✅ `pemasukan_bulan_ini` = Sum of deposits this month
- ✅ `pengeluaran_bulan_ini` = Sum of expenses this month
- ✅ `laba_rugi_bulan_ini` = Pemasukan - Pengeluaran this month

### Manual Verification (SQL)
```sql
-- Total Kas
SELECT 
    (SELECT COALESCE(SUM(amount), 0) FROM savings_transactions WHERE transaction_type = 'deposit' AND status = 'approved') -
    (SELECT COALESCE(SUM(amount), 0) FROM expenses) AS total_kas;

-- Pemasukan Bulan Ini
SELECT COALESCE(SUM(amount), 0) AS pemasukan_bulan_ini
FROM savings_transactions
WHERE transaction_type = 'deposit'
  AND status = 'approved'
  AND MONTH(transaction_date) = MONTH(CURDATE())
  AND YEAR(transaction_date) = YEAR(CURDATE());

-- Pengeluaran Bulan Ini
SELECT COALESCE(SUM(amount), 0) AS pengeluaran_bulan_ini
FROM expenses
WHERE MONTH(expense_date) = MONTH(CURDATE())
  AND YEAR(expense_date) = YEAR(CURDATE());
```

---

## 2️⃣ Test Monthly Data Endpoint

### Request
```powershell
# GET /api/finance/monthly?months=6
$monthlyResponse = Invoke-RestMethod `
    -Uri "https://api-kassaone.onrender.com/api/finance/monthly?months=6" `
    -Method GET `
    -Headers @{
        "Authorization" = "Bearer $token"
        "Accept" = "application/json"
    }

$monthlyResponse | ConvertTo-Json -Depth 10
```

### Expected Response
```json
{
  "success": true,
  "message": "Monthly finance data retrieved successfully",
  "data": [
    {
      "month": "Juli",
      "year": 2024,
      "pemasukan": 8000000.0,
      "pengeluaran": 2500000.0
    },
    {
      "month": "Agustus",
      "year": 2024,
      "pemasukan": 9000000.0,
      "pengeluaran": 3000000.0
    }
    // ... 4 more months
  ]
}
```

### Validation Checks
- ✅ Array should contain exactly 6 months
- ✅ Months should be in chronological order (oldest first)
- ✅ Each month has: month, year, pemasukan, pengeluaran
- ✅ Values should be floats

### Test Different Parameters
```powershell
# Test with 12 months
$monthly12 = Invoke-RestMethod `
    -Uri "https://api-kassaone.onrender.com/api/finance/monthly?months=12" `
    -Method GET `
    -Headers @{"Authorization" = "Bearer $token"; "Accept" = "application/json"}

Write-Host "Data Points: $($monthly12.data.Count)" -ForegroundColor Cyan
```

---

## 3️⃣ Test Recent Transactions Endpoint

### Request
```powershell
# GET /api/finance/transactions/recent?limit=10
$transactionsResponse = Invoke-RestMethod `
    -Uri "https://api-kassaone.onrender.com/api/finance/transactions/recent?limit=10" `
    -Method GET `
    -Headers @{
        "Authorization" = "Bearer $token"
        "Accept" = "application/json"
    }

$transactionsResponse | ConvertTo-Json -Depth 10
```

### Expected Response
```json
{
  "success": true,
  "message": "Recent transactions retrieved successfully",
  "data": [
    {
      "id": "cm5wdh7ss0001pqgxv0kw6n5n",
      "transaction_date": "2024-12-14",
      "type": "pemasukan",
      "amount": 100000.0,
      "description": "Simpanan Wajib - Budi Santoso",
      "member_name": "Budi Santoso",
      "account_name": "Simpanan Wajib",
      "source": "savings",
      "created_by": "Budi Santoso",
      "created_at": "2024-12-14T10:30:00.000000Z"
    },
    {
      "id": "cm5wdh7ss0002pqgxv0kw6n5o",
      "transaction_date": "2024-12-13",
      "type": "pengeluaran",
      "amount": 250000.0,
      "description": "Pembelian Alat Tulis",
      "member_name": null,
      "account_name": "Operasional",
      "source": "expense",
      "created_by": "Admin",
      "created_at": "2024-12-13T14:20:00.000000Z"
    }
  ]
}
```

### Validation Checks
- ✅ Array should contain at most 10 transactions (or limit value)
- ✅ Transactions sorted by date DESC
- ✅ Type is either "pemasukan" or "pengeluaran"
- ✅ member_name is null for expenses
- ✅ All required fields present

### Test Different Limits
```powershell
# Test with limit=20
$transactions20 = Invoke-RestMethod `
    -Uri "https://api-kassaone.onrender.com/api/finance/transactions/recent?limit=20" `
    -Method GET `
    -Headers @{"Authorization" = "Bearer $token"; "Accept" = "application/json"}

Write-Host "Transactions Count: $($transactions20.data.Count)" -ForegroundColor Cyan
```

---

## 4️⃣ Test Breakdown Endpoint

### Request
```powershell
# GET /api/finance/breakdown?start_date=2024-01-01&end_date=2024-12-31
$breakdownResponse = Invoke-RestMethod `
    -Uri "https://api-kassaone.onrender.com/api/finance/breakdown?start_date=2024-01-01&end_date=2024-12-31" `
    -Method GET `
    -Headers @{
        "Authorization" = "Bearer $token"
        "Accept" = "application/json"
    }

$breakdownResponse | ConvertTo-Json -Depth 10
```

### Expected Response
```json
{
  "success": true,
  "message": "Finance breakdown retrieved successfully",
  "data": {
    "pemasukan": {
      "total": 50000000.0,
      "breakdown_by_type": [
        {
          "account_type": "pokok",
          "total": 20000000.0
        },
        {
          "account_type": "wajib",
          "total": 25000000.0
        },
        {
          "account_type": "sukarela",
          "total": 5000000.0
        }
      ]
    },
    "pengeluaran": {
      "total": 15000000.0,
      "breakdown_by_account": [
        {
          "account_id": "cm5wdh7ss0001pqgxv0kw6n5n",
          "account_name": "Operasional",
          "total": 8000000.0
        },
        {
          "account_id": "cm5wdh7ss0002pqgxv0kw6n5o",
          "account_name": "Konsumsi",
          "total": 5000000.0
        }
      ]
    },
    "total_laba_rugi": 35000000.0
  }
}
```

### Validation Checks
- ✅ `pemasukan.total` = Sum of all breakdown_by_type totals
- ✅ `pengeluaran.total` = Sum of all breakdown_by_account totals
- ✅ `total_laba_rugi` = pemasukan.total - pengeluaran.total

### Test Different Date Ranges
```powershell
# This month only
$thisMonth = Get-Date -Format "yyyy-MM-01"
$nextMonth = (Get-Date).AddMonths(1).ToString("yyyy-MM-01")

$breakdownMonth = Invoke-RestMethod `
    -Uri "https://api-kassaone.onrender.com/api/finance/breakdown?start_date=$thisMonth&end_date=$nextMonth" `
    -Method GET `
    -Headers @{"Authorization" = "Bearer $token"; "Accept" = "application/json"}

Write-Host "This Month Laba/Rugi: Rp $($breakdownMonth.data.total_laba_rugi)" -ForegroundColor Yellow
```

---

## ⚠️ Test Authorization (403 Forbidden)

### Test with Non-Admin/Bendahara User
```powershell
# Login as regular member
$memberLogin = Invoke-RestMethod -Uri "https://api-kassaone.onrender.com/api/login" `
    -Method POST `
    -ContentType "application/json" `
    -Body (@{
        username = "member123"  # Regular member
        password = "password123"
    } | ConvertTo-Json)

$memberToken = $memberLogin.data.token

# Try to access finance endpoint (should get 403)
try {
    $forbiddenTest = Invoke-RestMethod `
        -Uri "https://api-kassaone.onrender.com/api/finance/summary" `
        -Method GET `
        -Headers @{"Authorization" = "Bearer $memberToken"; "Accept" = "application/json"}
} catch {
    Write-Host "Expected 403 Error:" -ForegroundColor Red
    $_.Exception.Response.StatusCode
}
```

### Expected Response (403)
```json
{
  "success": false,
  "message": "Forbidden. You do not have permission to access this resource.",
  "required_roles": ["Admin", "Bendahara"],
  "your_role": "Anggota"
}
```

---

## 🧪 Full Integration Test Script

```powershell
# Complete test script for Finance Dashboard API

Write-Host "`n=== Finance Dashboard API Testing ===" -ForegroundColor Cyan

# Step 1: Login
Write-Host "`n1. Authenticating..." -ForegroundColor Yellow
$loginResponse = Invoke-RestMethod -Uri "https://api-kassaone.onrender.com/api/login" `
    -Method POST `
    -ContentType "application/json" `
    -Body (@{
        username = "admin"
        password = "password123"
    } | ConvertTo-Json)

$token = $loginResponse.data.token
$userName = $loginResponse.data.user.full_name
$userRole = $loginResponse.data.user.role.name

Write-Host "   ✅ Logged in as: $userName ($userRole)" -ForegroundColor Green

# Headers for subsequent requests
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

# Step 2: Test Summary
Write-Host "`n2. Testing Finance Summary..." -ForegroundColor Yellow
$summary = Invoke-RestMethod -Uri "https://api-kassaone.onrender.com/api/finance/summary" -Method GET -Headers $headers
Write-Host "   ✅ Total Kas: Rp $('{0:N0}' -f $summary.data.total_kas)" -ForegroundColor Green
Write-Host "   ✅ Pemasukan Bulan Ini: Rp $('{0:N0}' -f $summary.data.pemasukan_bulan_ini)" -ForegroundColor Green
Write-Host "   ✅ Pengeluaran Bulan Ini: Rp $('{0:N0}' -f $summary.data.pengeluaran_bulan_ini)" -ForegroundColor Green
Write-Host "   ✅ Laba/Rugi Bulan Ini: Rp $('{0:N0}' -f $summary.data.laba_rugi_bulan_ini)" -ForegroundColor Green

# Step 3: Test Monthly Data
Write-Host "`n3. Testing Monthly Data (6 months)..." -ForegroundColor Yellow
$monthly = Invoke-RestMethod -Uri "https://api-kassaone.onrender.com/api/finance/monthly?months=6" -Method GET -Headers $headers
Write-Host "   ✅ Retrieved $($monthly.data.Count) months of data" -ForegroundColor Green
foreach ($month in $monthly.data) {
    Write-Host "   📅 $($month.month) $($month.year): Rp $('{0:N0}' -f $month.pemasukan) - Rp $('{0:N0}' -f $month.pengeluaran)" -ForegroundColor Gray
}

# Step 4: Test Recent Transactions
Write-Host "`n4. Testing Recent Transactions (10)..." -ForegroundColor Yellow
$transactions = Invoke-RestMethod -Uri "https://api-kassaone.onrender.com/api/finance/transactions/recent?limit=10" -Method GET -Headers $headers
Write-Host "   ✅ Retrieved $($transactions.data.Count) transactions" -ForegroundColor Green
foreach ($tx in $transactions.data | Select-Object -First 3) {
    $typeColor = if ($tx.type -eq "pemasukan") { "Green" } else { "Red" }
    Write-Host "   📌 $($tx.transaction_date) - $($tx.type): Rp $('{0:N0}' -f $tx.amount)" -ForegroundColor $typeColor
}

# Step 5: Test Breakdown
Write-Host "`n5. Testing Finance Breakdown (2024)..." -ForegroundColor Yellow
$breakdown = Invoke-RestMethod -Uri "https://api-kassaone.onrender.com/api/finance/breakdown?start_date=2024-01-01&end_date=2024-12-31" -Method GET -Headers $headers
Write-Host "   ✅ Pemasukan Total: Rp $('{0:N0}' -f $breakdown.data.pemasukan.total)" -ForegroundColor Green
Write-Host "   ✅ Pengeluaran Total: Rp $('{0:N0}' -f $breakdown.data.pengeluaran.total)" -ForegroundColor Green
Write-Host "   ✅ Total Laba/Rugi: Rp $('{0:N0}' -f $breakdown.data.total_laba_rugi)" -ForegroundColor Green

Write-Host "`n=== All Tests Completed Successfully ===" -ForegroundColor Cyan
```

---

## 📊 Performance Testing

### Test Response Times
```powershell
# Measure endpoint response times
$endpoints = @(
    @{Name="Summary"; Url="https://api-kassaone.onrender.com/api/finance/summary"},
    @{Name="Monthly"; Url="https://api-kassaone.onrender.com/api/finance/monthly?months=6"},
    @{Name="Transactions"; Url="https://api-kassaone.onrender.com/api/finance/transactions/recent?limit=10"},
    @{Name="Breakdown"; Url="https://api-kassaone.onrender.com/api/finance/breakdown?start_date=2024-01-01&end_date=2024-12-31"}
)

foreach ($endpoint in $endpoints) {
    $stopwatch = [System.Diagnostics.Stopwatch]::StartNew()
    Invoke-RestMethod -Uri $endpoint.Url -Method GET -Headers $headers | Out-Null
    $stopwatch.Stop()
    Write-Host "$($endpoint.Name): $($stopwatch.ElapsedMilliseconds)ms" -ForegroundColor Cyan
}
```

---

## ✅ Test Checklist

- [ ] Finance Summary endpoint returns correct data
- [ ] Total Kas = Pemasukan - Pengeluaran (verified with SQL)
- [ ] Monthly data returns correct number of months
- [ ] Recent transactions sorted by date DESC
- [ ] Breakdown totals match sum of breakdowns
- [ ] Authorization works (Admin & Bendahara only)
- [ ] 403 error for non-authorized roles
- [ ] All response times < 500ms
- [ ] Error handling works correctly
- [ ] Date filters work correctly

---

**Last Updated:** December 14, 2024
