# Perumahan Module - Quick Reference

## 🚀 Quick Commands

```bash
# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed --class=PerumahanSeeder

# View routes
php artisan route:list --path=perumahan

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## 🔑 Login Credentials

```json
{
  "username": "perumahan",
  "password": "perumahan123"
}
```

## 📊 Implemented APIs

### Dashboard
```bash
GET /api/perumahan/dashboard/stats
GET /api/perumahan/dashboard/charts?period=month
```

### Residents
```bash
GET /api/perumahan/residents
GET /api/perumahan/residents?search=ahmad
GET /api/perumahan/residents?house_status=owner_occupied
GET /api/perumahan/residents/{id}
POST /api/perumahan/residents
PUT /api/perumahan/residents/{id}
DELETE /api/perumahan/residents/{id}
```

## 📦 Sample Request Bodies

### Create Resident
```json
{
  "house_number": "C-01",
  "owner_name": "Bapak Joni",
  "owner_phone": "081234567890",
  "owner_email": "joni@email.com",
  "house_type": "45",
  "house_status": "owner_occupied",
  "total_occupants": 4,
  "has_vehicle": true,
  "vehicle_count": 2,
  "joined_date": "2026-01-24"
}
```

### Update Resident
```json
{
  "owner_phone": "081234567899",
  "total_occupants": 5,
  "vehicle_count": 3
}
```

## 🔍 Filter Examples

```bash
# Search by name/phone
?search=ahmad

# Filter by house status
?house_status=owner_occupied  # owner_occupied, rented, vacant

# Filter by active status
?status=active  # active, inactive

# Pagination
?page=1&limit=10

# Combined filters
?house_status=owner_occupied&search=ahmad&page=1&limit=20
```

## ✅ Phase 1 Status

**Completed:**
- ✅ 8 Database tables created & migrated
- ✅ 8 Eloquent Models with relationships
- ✅ Middleware for authorization (Perumahan role only)
- ✅ Dashboard APIs (stats & charts)
- ✅ Residents CRUD APIs with filters
- ✅ Sample data seeder
- ✅ Route registration
- ✅ UUID implementation

**Testing:**
- ✅ Login works
- ✅ Dashboard stats returns data
- ✅ Residents list works with pagination
- ✅ Authorization middleware works

## 📋 Next Steps (Phase 2)

1. Security Module Controller & APIs
2. Waste Management Controller & APIs
3. Service Requests Controller & APIs
4. Fee Management Controller & APIs
5. Reports Controller & APIs
6. Settings Controller & APIs

## 🛠️ Development Tips

- All models use UUID (via HasCuid trait)
- All routes protected with auth:admin + PerumahanMiddleware
- Check middleware logs in storage/logs/laravel.log
- Use eager loading to prevent N+1 queries
- Always return consistent JSON format

---

**Quick Test Script (PowerShell):**
```powershell
# Get token
$token = (Invoke-WebRequest -Uri "http://localhost:8000/api/admin/login" -Method POST -Headers @{"Content-Type"="application/json"} -Body '{"username":"perumahan","password":"perumahan123"}' -UseBasicParsing | ConvertFrom-Json).access_token

# Test API
Invoke-WebRequest -Uri "http://localhost:8000/api/perumahan/dashboard/stats" -Headers @{"Authorization"="Bearer $token"; "Accept"="application/json"} -UseBasicParsing | ConvertFrom-Json | ConvertTo-Json
```
