# Module Perumahan - Estate Management System

## 📋 Overview

Module Perumahan adalah sistem manajemen perumahan Tarbiyah Garden yang terintegrasi dengan KassaOne. Module ini terpisah dari flow utama koperasi dan memiliki struktur sendiri.

**Status**: ✅ Phase 1 Completed (Basic Setup & Core Features)

---

## 🏗️ Struktur Module

```
app/Modules/Perumahan/
├── Controllers/
│   ├── DashboardController.php   ✅ Implemented
│   ├── ResidentController.php    ✅ Implemented
│   ├── SecurityController.php    ⏳ TODO
│   ├── WasteController.php       ⏳ TODO
│   ├── ServiceController.php     ⏳ TODO
│   ├── FeeController.php         ⏳ TODO
│   ├── ReportController.php      ⏳ TODO
│   └── SettingsController.php    ⏳ TODO
├── Models/
│   ├── EstateResident.php        ✅ Implemented
│   ├── EstateSecurityLog.php     ✅ Implemented
│   ├── EstateWasteSchedule.php   ✅ Implemented
│   ├── EstateWasteCollection.php ✅ Implemented
│   ├── EstateService.php         ✅ Implemented
│   ├── EstateFee.php             ✅ Implemented
│   ├── EstateFeePayment.php      ✅ Implemented
│   └── EstateSetting.php         ✅ Implemented
├── Middleware/
│   └── PerumahanMiddleware.php   ✅ Implemented
└── Services/
    └── (Business Logic Services)  ⏳ TODO
```

---

## 🗄️ Database Tables

### Implemented Tables:
1. ✅ `estate_residents` - Data penghuni
2. ✅ `estate_security_logs` - Log keamanan
3. ✅ `estate_waste_schedules` - Jadwal sampah
4. ✅ `estate_waste_collections` - Record pengambilan sampah
5. ✅ `estate_services` - Layanan & pengaduan
6. ✅ `estate_fees` - Master iuran
7. ✅ `estate_fee_payments` - Pembayaran iuran
8. ✅ `estate_settings` - Pengaturan

All tables use **UUID** as primary key via `HasCuid` trait.

---

## 🔌 API Endpoints

### Base URL: `/api/perumahan`

### ✅ Implemented:

#### Dashboard
- `GET /api/perumahan/dashboard/stats` - Dashboard statistics
- `GET /api/perumahan/dashboard/charts` - Chart data

#### Residents Management
- `GET /api/perumahan/residents` - List residents (with filters)
- `POST /api/perumahan/residents` - Create resident
- `GET /api/perumahan/residents/{id}` - Get resident details
- `PUT /api/perumahan/residents/{id}` - Update resident
- `DELETE /api/perumahan/residents/{id}` - Deactivate resident

### ⏳ TODO:

#### Security Module
- `GET /api/perumahan/security/logs` - Security logs
- `POST /api/perumahan/security/logs` - Create log
- `POST /api/perumahan/security/incidents` - Report incident
- `PUT /api/perumahan/security/incidents/{id}` - Update incident
- `GET /api/perumahan/security/incidents/active` - Active incidents

#### Waste Management
- `GET /api/perumahan/waste/schedules` - Waste schedules
- `POST /api/perumahan/waste/schedules` - Create schedule
- `GET /api/perumahan/waste/collections` - Collection records
- `POST /api/perumahan/waste/collections` - Record collection
- `GET /api/perumahan/waste/statistics` - Statistics

#### Services & Complaints
- `GET /api/perumahan/services` - Service requests
- `POST /api/perumahan/services` - Create request
- `PUT /api/perumahan/services/{id}/status` - Update status
- `PUT /api/perumahan/services/{id}/resolve` - Resolve request
- `GET /api/perumahan/services/statistics` - Statistics

#### Fee Management
- `GET /api/perumahan/fees` - Fee types
- `POST /api/perumahan/fees` - Create fee
- `GET /api/perumahan/fees/payments` - Payments list
- `POST /api/perumahan/fees/payments` - Record payment
- `POST /api/perumahan/fees/payments/bulk-generate` - Auto-generate
- `GET /api/perumahan/fees/payments/overdue` - Overdue payments
- `GET /api/perumahan/fees/payments/{house}/history` - Payment history

#### Reports
- `GET /api/perumahan/reports/monthly-summary` - Monthly summary
- `GET /api/perumahan/reports/financial` - Financial report
- `GET /api/perumahan/reports/residents-list` - Residents export
- `GET /api/perumahan/reports/payment-status` - Payment status
- `GET /api/perumahan/reports/service-performance` - Performance

#### Settings
- `GET /api/perumahan/settings` - Get settings
- `PUT /api/perumahan/settings` - Update settings

---

## 🔐 Authentication & Authorization

### Middleware Stack:
```php
['auth:admin', PerumahanMiddleware::class]
```

### Role Required:
**Perumahan** - Hanya user dengan role "Perumahan" yang dapat mengakses module ini.

### Login:
```bash
POST /api/admin/login
{
  "username": "perumahan",
  "password": "perumahan123"
}
```

### Headers untuk API Calls:
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

---

## 🚀 Quick Start

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Run Seeder
```bash
php artisan db:seed --class=PerumahanSeeder
```

### 3. Login sebagai Perumahan
```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{"username":"perumahan","password":"perumahan123"}'
```

### 4. Test Dashboard API
```bash
curl -X GET http://localhost:8000/api/perumahan/dashboard/stats \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json"
```

### 5. Test Residents API
```bash
curl -X GET http://localhost:8000/api/perumahan/residents \
  -H "Authorization: Bearer {your-token}" \
  -H "Accept: application/json"
```

---

## 📊 Sample Data (After Seeding)

### Residents: 8 houses
- A-01 to A-05 (Blok A)
- B-01 to B-03 (Blok B)

### Fee Types: 3
- Iuran Keamanan: Rp 50,000/month
- Iuran Kebersihan: Rp 30,000/month
- Iuran Pemeliharaan Jalan: Rp 200,000/year

### Waste Schedules: 2
- Monday morning - Organic waste
- Thursday morning - Organic waste

### Settings: 4
- Estate name, total houses, payment penalties, security settings

---

## 🧪 Testing dengan PowerShell

```powershell
# Login
$response = Invoke-WebRequest -Uri "http://localhost:8000/api/admin/login" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body '{"username":"perumahan","password":"perumahan123"}' `
  -UseBasicParsing | ConvertFrom-Json

$token = $response.access_token

# Get Dashboard Stats
Invoke-WebRequest -Uri "http://localhost:8000/api/perumahan/dashboard/stats" `
  -Method GET `
  -Headers @{"Authorization"="Bearer $token"; "Accept"="application/json"} `
  -UseBasicParsing | Select-Object -ExpandProperty Content

# Get Residents
Invoke-WebRequest -Uri "http://localhost:8000/api/perumahan/residents" `
  -Method GET `
  -Headers @{"Authorization"="Bearer $token"; "Accept"="application/json"} `
  -UseBasicParsing | Select-Object -ExpandProperty Content
```

---

## 📝 Development Guidelines

### Adding New Features

1. **Create Controller**
   ```bash
   # Manual creation in app/Modules/Perumahan/Controllers/
   ```

2. **Add Routes**
   ```php
   // In routes/perumahan.php
   Route::prefix('your-module')->group(function () {
       // Your routes here
   });
   ```

3. **Test with Middleware**
   - All routes automatically protected dengan `PerumahanMiddleware`
   - Log all actions untuk audit trail

### Model Conventions

- Use UUID dengan `HasCuid` trait
- Set `$keyType = 'string'` dan `$incrementing = false`
- Define relationships dengan type hints
- Add query scopes untuk filtering umum

### Controller Conventions

- Gunakan JSON responses konsisten
- Always include `success` boolean
- Return proper HTTP status codes
- Validate input dengan FormRequest atau Validator
- Use try-catch untuk error handling

---

## 🔄 Next Phase (TODO)

### Phase 2: Security & Waste Management
- [ ] Implement SecurityController
- [ ] Implement WasteController
- [ ] Add photo upload functionality
- [ ] Add real-time notifications

### Phase 3: Services & Fees
- [ ] Implement ServiceController
- [ ] Implement FeeController
- [ ] Auto-generate monthly payments (cron)
- [ ] Overdue calculation (cron)
- [ ] WhatsApp notifications

### Phase 4: Reports & Analytics
- [ ] Implement ReportController
- [ ] PDF export functionality
- [ ] Excel export functionality
- [ ] Advanced filtering & search

### Phase 5: Automation & Integration
- [ ] WhatsApp integration
- [ ] Email notifications
- [ ] Automated reminders
- [ ] Payment gateway integration

---

## 📚 References

- Full Specification: [BACKEND_PERUMAHAN_SPECIFICATION.md](../../BACKEND_PERUMAHAN_SPECIFICATION.md)
- API Documentation: See specification file for complete API details
- Database Schema: See specification file for detailed schema

---

## 🤝 Contributing

Untuk menambahkan fitur baru:
1. Lihat specification document
2. Buat controller dan routes
3. Test dengan Postman/curl
4. Update README ini

---

**Last Updated**: January 24, 2026  
**Version**: 1.0.0 (Phase 1 Complete)
