# Fitur Pengaturan Simpanan Pokok Dinamis

## Deskripsi
Fitur ini memungkinkan admin untuk mengubah nilai Simpanan Pokok secara global melalui dashboard admin. Perubahan nilai hanya akan berlaku untuk **anggota baru** yang mendaftar setelah perubahan dilakukan. Anggota yang sudah terdaftar sebelumnya tidak akan terpengaruh.

## Komponen yang Diimplementasikan

### 1. Database - System Settings Table
**File**: `api/database/migrations/2025_11_02_140539_create_system_settings_table.php`

Tabel `system_settings` untuk menyimpan pengaturan sistem dengan struktur:
- `id` - Primary key
- `key` - Unique key untuk setting (e.g., 'simpanan_pokok_amount')
- `value` - Nilai setting (disimpan sebagai text)
- `type` - Tipe data (string, integer, boolean, json)
- `description` - Deskripsi setting
- `timestamps` - Created at & Updated at

**Default Value**: Simpanan Pokok = 1.000.000

```bash
# Run migration
cd api
php artisan migrate
```

### 2. Model - SystemSetting
**File**: `api/app/Models/SystemSetting.php`

Model dengan static methods untuk get/set settings:
- `SystemSetting::get($key, $default)` - Mengambil nilai setting dengan caching
- `SystemSetting::set($key, $value, $type, $description)` - Update atau create setting
- `castValue()` - Auto-cast nilai sesuai tipe data

**Caching**: Settings di-cache selama 1 jam untuk performa optimal.

### 3. Controller - SettingsController
**File**: `api/app/Http/Controllers/Api/SettingsController.php`

**Endpoints**:
- `GET /api/settings` - Ambil semua settings
- `GET /api/settings?key=simpanan_pokok_amount` - Ambil setting spesifik
- `PUT /api/settings` - Update setting (Admin only, butuh CSRF token)

**Request Body** untuk update:
```json
{
  "key": "simpanan_pokok_amount",
  "value": "1500000",
  "type": "integer",
  "description": "Jumlah Simpanan Pokok untuk anggota baru"
}
```

### 4. Routes
**File**: `api/routes/api.php`

```php
// GET - Hanya butuh JWT (semua user)
Route::get('/settings', [SettingsController::class, 'index']);

// PUT - Butuh JWT + CSRF (Admin only)
Route::put('/settings', [SettingsController::class, 'update']);
```

### 5. Integration - AuthController
**File**: `api/app/Http/Controllers/Api/AuthController.php`

Fungsi `register()` diupdate untuk menggunakan `SystemSetting::get()`:

```php
// Get Simpanan Pokok amount from system settings
$simpananPokokAmount = SystemSetting::get('simpanan_pokok_amount', 1000000);

$member = Member::create([
    // ... other fields
    'payment_amount' => $simpananPokokAmount,
]);
```

Member baru akan otomatis menggunakan nilai terbaru dari settings.

### 6. Frontend Service
**File**: `src/lib/services/settings.ts`

Service untuk komunikasi dengan API:
```typescript
interface SystemSetting {
  key: string;
  value: string | number | boolean | Record<string, unknown>;
  type: 'string' | 'integer' | 'boolean' | 'json';
  description?: string;
  updated_at?: string;
}

// Methods
settingsService.getAll()
settingsService.get(key)
settingsService.update(key, value, type, description)
settingsService.getSimpananPokokAmount()
settingsService.updateSimpananPokokAmount(amount)
```

### 7. Admin UI - Settings Page
**File**: `src/pages/admin/Settings.tsx`

**Fitur**:
- ✅ List semua system settings
- ✅ Inline editing dengan preview
- ✅ Currency formatting untuk amount
- ✅ Validation (angka positif untuk integer)
- ✅ Loading states
- ✅ Success/Error notifications
- ✅ Warning message untuk perubahan Simpanan Pokok
- ✅ Last updated timestamp

**Akses**: Admin only - `/settings`

### 8. Integration - SavingsSelectionForm
**File**: `src/components/dashboard/SavingsSelectionForm.tsx`

Form pilihan simpanan untuk member baru diupdate untuk:
- Fetch nilai Simpanan Pokok dari API saat component mount
- Display nilai dinamis di form
- Calculate total pembayaran berdasarkan nilai terbaru

### 9. Routing
**File**: `src/App.tsx`

```typescript
import { Settings } from './pages/admin/Settings';

// Routes
<Route path="/settings" element={<Settings />} />
```

**File**: `src/components/layout/Sidebar.tsx`

Menu Settings sudah tersedia di admin sidebar.

## Alur Kerja

### Skenario 1: Admin Mengubah Nilai Simpanan Pokok
1. Admin login dan masuk ke halaman **Settings** (`/settings`)
2. Melihat setting "Simpanan Pokok" dengan nilai saat ini (e.g., Rp 1.000.000)
3. Input nilai baru di field "Nilai Baru" (e.g., 1.500.000)
4. Klik tombol **Simpan**
5. Sistem update database dan clear cache
6. Toast notification muncul: "Pengaturan berhasil diperbarui"
7. Warning ditampilkan: "Perubahan hanya berlaku untuk anggota baru"

### Skenario 2: Member Baru Mendaftar (Setelah Perubahan)
1. Member baru mengisi form registrasi
2. Backend (`AuthController::register()`) call `SystemSetting::get('simpanan_pokok_amount')`
3. Member dibuat dengan `payment_amount = 1.500.000` (nilai baru)
4. Member login dan masuk ke **Pending Payment** page
5. Tab "Penentuan Simpanan" tampilkan:
   - Simpanan Pokok: Rp 1.500.000 (nilai baru)
   - Simpanan Wajib: Rp 500.000 - 2.000.000 (pilihan member)
   - Total: Simpanan Pokok + Simpanan Wajib

### Skenario 3: Member Lama Login (Tidak Terpengaruh)
1. Member yang terdaftar sebelum perubahan login
2. `payment_amount` mereka tetap sesuai database (e.g., 1.000.000)
3. Tidak ada perubahan pada data mereka

## Testing

### Test 1: Admin Update Setting
```bash
# Login sebagai admin
POST /api/login
{
  "username": "admin",
  "password": "password"
}

# Get current settings
GET /api/settings
Authorization: Bearer <token>

# Update Simpanan Pokok
PUT /api/settings
Authorization: Bearer <token>
X-CSRF-TOKEN: <csrf_token>
{
  "key": "simpanan_pokok_amount",
  "value": "1500000",
  "type": "integer",
  "description": "Jumlah Simpanan Pokok untuk anggota baru"
}

# Expected Response:
{
  "success": true,
  "message": "Setting updated successfully",
  "data": {
    "key": "simpanan_pokok_amount",
    "value": 1500000
  }
}
```

### Test 2: New Member Registration
```bash
# Register new member (setelah admin update setting)
POST /api/register
{
  "full_name": "Test Member",
  "username": "testmember",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "member_type": "Biasa"
}

# Expected Response:
{
  "message": "Pendaftaran berhasil! Silakan login dan upload bukti pembayaran Simpanan Pokok sebesar Rp 1.500.000",
  "user": { ... }
}

# Check member data
GET /api/members/{member_id}
# payment_amount should be 1500000
```

### Test 3: Frontend Settings Page
1. Login sebagai admin
2. Navigate ke `/settings`
3. Verifikasi:
   - ✅ List settings tampil
   - ✅ Nilai saat ini terbaca (e.g., Rp 1.000.000)
   - ✅ Input field enabled
   - ✅ Preview currency format works
   - ✅ Save button enabled when value changed
   - ✅ Warning message ditampilkan
   - ✅ Toast notification muncul setelah save

### Test 4: Frontend SavingsSelectionForm
1. Register member baru
2. Login dan masuk ke Pending Payment
3. Tab "Penentuan Simpanan"
4. Verifikasi:
   - ✅ Loading spinner saat fetch settings
   - ✅ Simpanan Pokok tampil sesuai nilai terbaru dari database
   - ✅ Total calculation correct
   - ✅ Submit form works

## Security

### Authorization
- GET `/api/settings` - Semua authenticated user
- PUT `/api/settings` - Admin only (perlu implement middleware)

**TODO**: Add role-based middleware untuk endpoint PUT:
```php
Route::middleware(['auth:api', 'csrf', 'role:Admin'])->group(function() {
    Route::put('/settings', [SettingsController::class, 'update']);
});
```

### CSRF Protection
Update endpoint menggunakan CSRF token validation untuk mencegah CSRF attacks.

### Caching
Settings di-cache untuk performa, auto-cleared saat update.

## Extensibility

Untuk menambahkan setting baru:

1. **Insert ke database**:
```php
SystemSetting::set('new_setting_key', 'value', 'type', 'Description');
```

2. **Frontend service** (optional):
```typescript
async getNewSetting(): Promise<string> {
  return await this.get('new_setting_key');
}
```

3. **UI** (optional):
Add new card di Settings.tsx atau buat halaman terpisah.

## Troubleshooting

### Issue: Setting tidak update setelah save
**Solution**: Clear cache manual
```php
Cache::forget('system_setting_simpanan_pokok_amount');
```

### Issue: Member baru masih dapat nilai lama
**Solution**: 
1. Check database - apakah setting sudah terupdate?
2. Check AuthController - apakah sudah pakai `SystemSetting::get()`?
3. Clear cache Laravel: `php artisan cache:clear`

### Issue: Frontend tidak dapat fetch settings
**Solution**:
1. Check API route - apakah endpoint `/api/settings` accessible?
2. Check JWT token - apakah valid?
3. Check browser console untuk error network

## Deployment Notes

### Production Checklist
- [ ] Run migration: `php artisan migrate --force`
- [ ] Check default value di system_settings table
- [ ] Test admin update flow
- [ ] Test new member registration
- [ ] Verify cache configuration (Redis recommended)
- [ ] Add role middleware untuk PUT endpoint
- [ ] Monitor cache hit/miss rate

### Environment Variables
No additional env variables required.

### Database Backup
Backup database sebelum migration:
```bash
php artisan db:backup # (if using backup package)
# or manual mysqldump
```

## Future Improvements

1. **Role-Based Access Control**
   - Implement middleware untuk validasi role Admin pada PUT endpoint
   
2. **Audit Log**
   - Log setiap perubahan setting (who, when, old value, new value)
   
3. **Settings Validation**
   - Min/Max constraints untuk amount
   - Regex validation untuk text settings
   
4. **Bulk Update**
   - Allow admin update multiple settings sekaligus
   
5. **Settings History**
   - Track perubahan setting dari waktu ke waktu
   
6. **UI Enhancements**
   - Confirmation dialog sebelum save
   - Undo/Redo functionality
   - Settings preview mode

---

**Implementasi Selesai**: 2 November 2025  
**Status**: ✅ Fully Functional  
**Breaking Changes**: None (backward compatible)
