# 🚀 SHU Management Quick Start Guide

## Prerequisites

- PHP >= 8.1
- Laravel 10.x
- MySQL/MariaDB
- Composer
- Postman (untuk testing API)

## Installation

### 1. Database Migration
```bash
php artisan migrate
```

Migration yang akan dijalankan:
- `2025_11_10_103303_add_enhanced_columns_to_shu_tables`

### 2. Verify Routes
```bash
php artisan route:list --path=shu
```

Expected output: 13 routes terdaftar

## API Endpoints Overview

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/shu-distributions` | Get all distributions | Yes |
| POST | `/api/shu-distributions` | Create new distribution | Yes + CSRF |
| GET | `/api/shu-distributions/{id}` | Get distribution detail | Yes |
| PUT | `/api/shu-distributions/{id}` | Update distribution | Yes + CSRF |
| DELETE | `/api/shu-distributions/{id}` | Delete distribution | Yes + CSRF |
| POST | `/api/shu-distributions/{id}/calculate` | Calculate allocations | Yes + CSRF |
| GET | `/api/shu-distributions/{id}/allocations` | Get allocations | Yes |
| POST | `/api/shu-distributions/{id}/approve` | Approve distribution | Yes + CSRF |
| POST | `/api/shu-distributions/{id}/payout` | Batch payout | Yes + CSRF |
| GET | `/api/shu-distributions/{id}/report` | Get report | Yes |

## Quick Test with Postman

### Step 1: Login & Get Token
```http
POST /api/login
Content-Type: application/json

{
  "username": "your_username",
  "password": "your_password"
}
```

Save the `access_token` from response.

### Step 2: Create Distribution
```http
POST /api/shu-distributions
Content-Type: application/json
Authorization: Bearer YOUR_ACCESS_TOKEN

{
  "fiscal_year": 2025,
  "total_shu_amount": 100000000,
  "distribution_date": "2026-01-15",
  "notes": "Test SHU 2025"
}
```

Expected Response:
```json
{
  "message": "SHU Distribution created successfully",
  "data": {
    "id": "cm3abc123",
    "fiscal_year": 2025,
    "total_shu_amount": "100000000.00",
    "cadangan_amount": "30000000.00",
    "jasa_modal_amount": "28000000.00",
    "jasa_usaha_amount": "42000000.00",
    "status": "draft"
  },
  "breakdown": {
    "total_shu": 100000000,
    "cadangan_amount": 30000000,
    "jasa_modal_amount": 28000000,
    "jasa_usaha_amount": 42000000
  }
}
```

Save the `id` for next steps.

### Step 3: Calculate Allocations
```http
POST /api/shu-distributions/{id}/calculate
Authorization: Bearer YOUR_ACCESS_TOKEN
```

Expected: List of member allocations created

### Step 4: View Allocations
```http
GET /api/shu-distributions/{id}/allocations
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Step 5: Approve Distribution
```http
POST /api/shu-distributions/{id}/approve
Content-Type: application/json
Authorization: Bearer YOUR_ACCESS_TOKEN

{
  "approved_by": "admin_member_id"
}
```

### Step 6: Batch Payout
```http
POST /api/shu-distributions/{id}/payout
Authorization: Bearer YOUR_ACCESS_TOKEN
```

Expected: All members paid, status changed to `paid_out`

### Step 7: View Report
```http
GET /api/shu-distributions/{id}/report
Authorization: Bearer YOUR_ACCESS_TOKEN
```

## Workflow States

```
draft → approved → paid_out
  ↓         ↓         ↓
Create  Approve  Payout
  ↓
Calculate
```

## Common Errors & Solutions

### Error: "Can only calculate allocations for draft distributions"
**Solution:** Distribution must be in `draft` status. Cannot recalculate after approval.

### Error: "No allocations found. Please calculate allocations first."
**Solution:** Run `/calculate` endpoint before approving.

### Error: "Can only approve draft distributions"
**Solution:** Distribution is already approved or paid out.

### Error: "Only draft distributions can be updated"
**Solution:** Cannot update after approval. Delete and recreate if needed.

### Error: "No savings account found"
**Solution:** Ensure all members have at least one savings account.

### Error: "Tidak ada data simpanan member"
**Solution:** Ensure members have savings data in `savings_accounts` table.

### Error: "Tidak ada transaksi deposit"
**Solution:** Ensure there are deposit transactions for the fiscal year.

## Frontend Integration

See complete guide in:
- **`SHU_FRONTEND_IMPLEMENTATION_GUIDE.md`** - Complete React implementation
- **`api-doc.md`** - API documentation
- **`SHU_IMPLEMENTATION_SUMMARY.md`** - Technical summary

## File Structure

```
app/
├── Http/Controllers/
│   └── SHUDistributionController.php    # Main controller
├── Models/
│   ├── ShuDistribution.php              # Distribution model
│   └── ShuMemberAllocation.php          # Allocation model
└── Services/
    └── SHUCalculationService.php        # Business logic

database/migrations/
└── 2025_11_10_103303_add_enhanced_columns_to_shu_tables.php

routes/
└── api.php                              # API routes
```

## Testing Checklist

- [ ] Create distribution
- [ ] View distribution list
- [ ] Calculate allocations
- [ ] View allocations
- [ ] Approve distribution
- [ ] Batch payout
- [ ] View report
- [ ] Update draft distribution
- [ ] Delete draft distribution
- [ ] Test error cases

## Production Deployment

### 1. Environment Setup
```bash
# Set production environment
APP_ENV=production
APP_DEBUG=false
```

### 2. Run Migrations
```bash
php artisan migrate --force
```

### 3. Clear Cache
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Queue Workers (Optional)
If you want to process payouts in background:
```bash
php artisan queue:work
```

## Support

For questions or issues:
1. Check `SHU_IMPLEMENTATION_SUMMARY.md` for technical details
2. Check `SHU_FRONTEND_IMPLEMENTATION_GUIDE.md` for frontend help
3. Check Laravel logs: `storage/logs/laravel.log`

## License

MIT License - See LICENSE file for details

---

**Created:** November 10, 2025
**Version:** 1.0.0
**Status:** Production Ready ✅
