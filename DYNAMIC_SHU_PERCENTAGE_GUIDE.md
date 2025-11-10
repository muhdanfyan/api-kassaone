# 📊 Dynamic SHU Percentage Configuration Guide

## 📖 Table of Contents
1. [Apa itu SHU?](#apa-itu-shu)
2. [Alur Lengkap SHU Management](#alur-lengkap-shu-management)
3. [Konsep Persentase SHU](#konsep-persentase-shu)
4. [Implementasi Custom Persentase](#implementasi-custom-persentase)
5. [Contoh Kasus](#contoh-kasus)

---

## 🎯 Apa itu SHU?

### Definisi
**SHU (Sisa Hasil Usaha)** adalah keuntungan/laba bersih koperasi yang dibagikan kepada anggota berdasarkan partisipasi mereka.

### Analogi Sederhana
Bayangkan koperasi seperti warung kelontong:
- **Total Penjualan**: Rp 100.000.000
- **Total Pengeluaran**: Rp 60.000.000
- **Laba Bersih (SHU)**: Rp 40.000.000 ← **Ini yang akan dibagi**

---

## 🔄 Alur Lengkap SHU Management

### Visual Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│                    TAHUN BUKU KOPERASI                          │
│                    (Misal: 2024)                                │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  STEP 1: HITUNG LABA/RUGI TAHUNAN                               │
│                                                                  │
│  Total Pendapatan:     Rp 500.000.000                          │
│  Total Pengeluaran:    Rp 350.000.000                          │
│  ─────────────────────────────────────                         │
│  LABA BERSIH (SHU):    Rp 150.000.000  ← Input di sistem       │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  STEP 2: TENTUKAN PERSENTASE PEMBAGIAN                          │
│                                                                  │
│  ┌─────────────────────────────────────────┐                   │
│  │  SETTING PERSENTASE (Customizable!)     │                   │
│  ├─────────────────────────────────────────┤                   │
│  │  1. Cadangan Koperasi:      30%         │                   │
│  │  2. Untuk Anggota:          70%         │                   │
│  │     ├─ Jasa Modal:          40%         │                   │
│  │     └─ Jasa Usaha:          60%         │                   │
│  │  3. Untuk Pengurus:         0%  (opsional)                  │
│  │  4. Untuk Karyawan:         0%  (opsional)                  │
│  │  5. Dana Sosial:            0%  (opsional)                  │
│  └─────────────────────────────────────────┘                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  STEP 3: BREAKDOWN PERHITUNGAN                                  │
│                                                                  │
│  Total SHU: Rp 150.000.000                                      │
│                                                                  │
│  A. Cadangan (30%):                                             │
│     = 30% × Rp 150.000.000                                      │
│     = Rp 45.000.000  ← Masuk kas koperasi                      │
│                                                                  │
│  B. Anggota (70%):                                              │
│     = 70% × Rp 150.000.000                                      │
│     = Rp 105.000.000  ← Dibagi ke anggota                      │
│                                                                  │
│     B.1. Jasa Modal (40% dari anggota):                         │
│          = 40% × Rp 105.000.000                                 │
│          = Rp 42.000.000  ← Dibagi proporsional simpanan       │
│                                                                  │
│     B.2. Jasa Usaha (60% dari anggota):                         │
│          = 60% × Rp 105.000.000                                 │
│          = Rp 63.000.000  ← Dibagi proporsional transaksi      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  STEP 4: HITUNG ALOKASI PER ANGGOTA                             │
│                                                                  │
│  Contoh: Anggota "Budi"                                         │
│                                                                  │
│  Data Budi:                                                     │
│  - Total Simpanan (Pokok+Wajib): Rp 10.000.000                 │
│  - Total Transaksi tahun 2024:   Rp 50.000.000                 │
│                                                                  │
│  Total Simpanan Semua Anggota:    Rp 500.000.000               │
│  Total Transaksi Semua Anggota:   Rp 2.000.000.000             │
│                                                                  │
│  ┌───────────────────────────────────────────────┐             │
│  │  HITUNG JASA MODAL BUDI                       │             │
│  ├───────────────────────────────────────────────┤             │
│  │  = (Simpanan Budi / Total Simpanan) × Rp 42jt│             │
│  │  = (10jt / 500jt) × 42jt                      │             │
│  │  = 2% × 42jt                                  │             │
│  │  = Rp 840.000                                 │             │
│  └───────────────────────────────────────────────┘             │
│                                                                  │
│  ┌───────────────────────────────────────────────┐             │
│  │  HITUNG JASA USAHA BUDI                       │             │
│  ├───────────────────────────────────────────────┤             │
│  │  = (Transaksi Budi / Total Transaksi) × Rp 63jt             │
│  │  = (50jt / 2.000jt) × 63jt                   │             │
│  │  = 2.5% × 63jt                                │             │
│  │  = Rp 1.575.000                               │             │
│  └───────────────────────────────────────────────┘             │
│                                                                  │
│  TOTAL SHU BUDI = Rp 840.000 + Rp 1.575.000                    │
│                 = Rp 2.415.000                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  STEP 5: APPROVE & PAYOUT                                       │
│                                                                  │
│  Status: DRAFT → APPROVED → PAID_OUT                            │
│                                                                  │
│  Setelah di-approve, lakukan payout:                            │
│  - Transfer ke rekening anggota, atau                           │
│  - Masukkan ke saldo simpanan anggota                           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 💰 Konsep Persentase SHU

### 1. Pembagian Level 1: Total SHU

```
Total SHU (100%)
    │
    ├─ Cadangan Koperasi (%)      ← Untuk modal & cadangan
    ├─ Anggota (%)                ← Dibagi ke anggota
    ├─ Pengurus (%) [opsional]    ← Bonus pengurus
    ├─ Karyawan (%) [opsional]    ← Bonus karyawan
    └─ Dana Sosial (%) [opsional] ← CSR/Sosial
```

### 2. Pembagian Level 2: Bagian Anggota

```
Bagian Anggota (dari Level 1)
    │
    ├─ Jasa Modal (%)     ← Berdasarkan simpanan
    └─ Jasa Usaha (%)     ← Berdasarkan transaksi
```

### 3. Aturan Persentase

#### ✅ Sesuai UU Koperasi No. 25/1992:
```yaml
Cadangan:    Min 30% dari total SHU
Anggota:     Max 70% dari total SHU
  - Jasa Modal: 30-40% dari bagian anggota
  - Jasa Usaha: 60-70% dari bagian anggota
```

#### 🎯 Koperasi Bisa Customize:
```yaml
# Contoh 1: Sesuai UU (Default)
cadangan: 30%
anggota: 70%
  jasa_modal: 40%
  jasa_usaha: 60%

# Contoh 2: Lebih banyak ke anggota
cadangan: 30%
anggota: 65%
pengurus: 3%
karyawan: 2%

# Contoh 3: Ada dana sosial
cadangan: 30%
anggota: 60%
pengurus: 3%
karyawan: 2%
dana_sosial: 5%
```

---

## 🛠️ Implementasi Custom Persentase

### Database Schema Enhancement

#### 1. Tambah Tabel: `shu_percentage_settings`

```sql
CREATE TABLE shu_percentage_settings (
    id CHAR(25) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,           -- Nama setting (contoh: "Setting 2024")
    fiscal_year VARCHAR(4) NOT NULL,      -- Tahun fiskal
    is_active BOOLEAN DEFAULT false,      -- Setting aktif
    
    -- Level 1: Pembagian Total SHU
    cadangan_percentage DECIMAL(5,2) NOT NULL DEFAULT 30.00,
    anggota_percentage DECIMAL(5,2) NOT NULL DEFAULT 70.00,
    pengurus_percentage DECIMAL(5,2) DEFAULT 0.00,
    karyawan_percentage DECIMAL(5,2) DEFAULT 0.00,
    dana_sosial_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Level 2: Pembagian Bagian Anggota
    jasa_modal_percentage DECIMAL(5,2) NOT NULL DEFAULT 40.00,  -- dari anggota_percentage
    jasa_usaha_percentage DECIMAL(5,2) NOT NULL DEFAULT 60.00,  -- dari anggota_percentage
    
    -- Metadata
    description TEXT,
    created_by CHAR(25),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    CONSTRAINT check_total_level1 CHECK (
        cadangan_percentage + anggota_percentage + 
        pengurus_percentage + karyawan_percentage + 
        dana_sosial_percentage = 100.00
    ),
    CONSTRAINT check_total_level2 CHECK (
        jasa_modal_percentage + jasa_usaha_percentage = 100.00
    ),
    CONSTRAINT check_min_cadangan CHECK (cadangan_percentage >= 30.00),
    
    FOREIGN KEY (created_by) REFERENCES members(id)
);
```

#### 2. Update `shu_distributions` Table

```sql
ALTER TABLE shu_distributions ADD COLUMN setting_id CHAR(25);
ALTER TABLE shu_distributions ADD CONSTRAINT fk_setting 
    FOREIGN KEY (setting_id) REFERENCES shu_percentage_settings(id);
```

### Model: ShuPercentageSetting.php

```php
<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;

class ShuPercentageSetting extends Model
{
    use HasCuid;

    protected $fillable = [
        'name',
        'fiscal_year',
        'is_active',
        'cadangan_percentage',
        'anggota_percentage',
        'pengurus_percentage',
        'karyawan_percentage',
        'dana_sosial_percentage',
        'jasa_modal_percentage',
        'jasa_usaha_percentage',
        'description',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cadangan_percentage' => 'decimal:2',
        'anggota_percentage' => 'decimal:2',
        'pengurus_percentage' => 'decimal:2',
        'karyawan_percentage' => 'decimal:2',
        'dana_sosial_percentage' => 'decimal:2',
        'jasa_modal_percentage' => 'decimal:2',
        'jasa_usaha_percentage' => 'decimal:2',
    ];

    // Relationships
    public function distributions()
    {
        return $this->hasMany(ShuDistribution::class, 'setting_id');
    }

    public function creator()
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    // Validation
    public function validatePercentages(): bool
    {
        // Level 1 harus = 100%
        $level1Total = $this->cadangan_percentage + 
                       $this->anggota_percentage + 
                       $this->pengurus_percentage + 
                       $this->karyawan_percentage + 
                       $this->dana_sosial_percentage;

        if (abs($level1Total - 100) > 0.01) {
            return false;
        }

        // Level 2 harus = 100%
        $level2Total = $this->jasa_modal_percentage + $this->jasa_usaha_percentage;
        if (abs($level2Total - 100) > 0.01) {
            return false;
        }

        // Cadangan min 30%
        if ($this->cadangan_percentage < 30) {
            return false;
        }

        return true;
    }

    // Helper: Get breakdown amounts
    public function calculateBreakdown(float $totalShu): array
    {
        $cadangan = $totalShu * ($this->cadangan_percentage / 100);
        $anggota = $totalShu * ($this->anggota_percentage / 100);
        $pengurus = $totalShu * ($this->pengurus_percentage / 100);
        $karyawan = $totalShu * ($this->karyawan_percentage / 100);
        $danaSosial = $totalShu * ($this->dana_sosial_percentage / 100);

        $jasaModal = $anggota * ($this->jasa_modal_percentage / 100);
        $jasaUsaha = $anggota * ($this->jasa_usaha_percentage / 100);

        return [
            'total_shu' => $totalShu,
            'cadangan' => [
                'percentage' => $this->cadangan_percentage,
                'amount' => $cadangan,
            ],
            'anggota' => [
                'percentage' => $this->anggota_percentage,
                'amount' => $anggota,
                'breakdown' => [
                    'jasa_modal' => [
                        'percentage' => $this->jasa_modal_percentage,
                        'amount' => $jasaModal,
                    ],
                    'jasa_usaha' => [
                        'percentage' => $this->jasa_usaha_percentage,
                        'amount' => $jasaUsaha,
                    ],
                ],
            ],
            'pengurus' => [
                'percentage' => $this->pengurus_percentage,
                'amount' => $pengurus,
            ],
            'karyawan' => [
                'percentage' => $this->karyawan_percentage,
                'amount' => $karyawan,
            ],
            'dana_sosial' => [
                'percentage' => $this->dana_sosial_percentage,
                'amount' => $danaSosial,
            ],
        ];
    }

    // Scope
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, string $year)
    {
        return $query->where('fiscal_year', $year);
    }
}
```

### Controller: ShuPercentageSettingController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\ShuPercentageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShuPercentageSettingController extends Controller
{
    /**
     * GET /api/shu-settings
     * List all percentage settings
     */
    public function index(Request $request)
    {
        $query = ShuPercentageSetting::with('creator');

        if ($request->fiscal_year) {
            $query->forYear($request->fiscal_year);
        }

        if ($request->is_active) {
            $query->active();
        }

        $settings = $query->orderBy('fiscal_year', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * POST /api/shu-settings
     * Create new percentage setting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'fiscal_year' => 'required|digits:4',
            'cadangan_percentage' => 'required|numeric|min:30|max:100',
            'anggota_percentage' => 'required|numeric|min:0|max:70',
            'pengurus_percentage' => 'nullable|numeric|min:0|max:100',
            'karyawan_percentage' => 'nullable|numeric|min:0|max:100',
            'dana_sosial_percentage' => 'nullable|numeric|min:0|max:100',
            'jasa_modal_percentage' => 'required|numeric|min:0|max:100',
            'jasa_usaha_percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Validate percentages sum to 100
        $level1Total = $validated['cadangan_percentage'] + 
                       $validated['anggota_percentage'] + 
                       ($validated['pengurus_percentage'] ?? 0) + 
                       ($validated['karyawan_percentage'] ?? 0) + 
                       ($validated['dana_sosial_percentage'] ?? 0);

        if (abs($level1Total - 100) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Total persentase Level 1 harus = 100%',
                'current_total' => $level1Total,
            ], 422);
        }

        $level2Total = $validated['jasa_modal_percentage'] + $validated['jasa_usaha_percentage'];
        if (abs($level2Total - 100) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Total jasa modal + jasa usaha harus = 100%',
                'current_total' => $level2Total,
            ], 422);
        }

        DB::beginTransaction();
        try {
            // If set as active, deactivate others for same fiscal year
            if ($validated['is_active'] ?? false) {
                ShuPercentageSetting::where('fiscal_year', $validated['fiscal_year'])
                                   ->update(['is_active' => false]);
            }

            $setting = ShuPercentageSetting::create(array_merge($validated, [
                'created_by' => auth()->user()->member_id,
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Setting persentase berhasil dibuat',
                'data' => $setting->load('creator'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat setting: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/shu-settings/{id}
     * Get setting detail
     */
    public function show(string $id)
    {
        $setting = ShuPercentageSetting::with('creator')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

    /**
     * PUT /api/shu-settings/{id}
     * Update setting
     */
    public function update(Request $request, string $id)
    {
        $setting = ShuPercentageSetting::findOrFail($id);

        // Check if already used
        if ($setting->distributions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Setting tidak dapat diubah karena sudah digunakan',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'string|max:100',
            'cadangan_percentage' => 'numeric|min:30|max:100',
            'anggota_percentage' => 'numeric|min:0|max:70',
            'pengurus_percentage' => 'nullable|numeric|min:0|max:100',
            'karyawan_percentage' => 'nullable|numeric|min:0|max:100',
            'dana_sosial_percentage' => 'nullable|numeric|min:0|max:100',
            'jasa_modal_percentage' => 'numeric|min:0|max:100',
            'jasa_usaha_percentage' => 'numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Validate if percentages provided
        if (isset($validated['cadangan_percentage'])) {
            $level1Total = ($validated['cadangan_percentage'] ?? $setting->cadangan_percentage) + 
                           ($validated['anggota_percentage'] ?? $setting->anggota_percentage) + 
                           ($validated['pengurus_percentage'] ?? $setting->pengurus_percentage) + 
                           ($validated['karyawan_percentage'] ?? $setting->karyawan_percentage) + 
                           ($validated['dana_sosial_percentage'] ?? $setting->dana_sosial_percentage);

            if (abs($level1Total - 100) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total persentase Level 1 harus = 100%',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            if ($validated['is_active'] ?? false) {
                ShuPercentageSetting::where('fiscal_year', $setting->fiscal_year)
                                   ->where('id', '!=', $id)
                                   ->update(['is_active' => false]);
            }

            $setting->update($validated);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Setting berhasil diupdate',
                'data' => $setting->fresh()->load('creator'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal update: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/shu-settings/{id}/activate
     * Set as active setting
     */
    public function activate(string $id)
    {
        $setting = ShuPercentageSetting::findOrFail($id);

        DB::beginTransaction();
        try {
            // Deactivate others
            ShuPercentageSetting::where('fiscal_year', $setting->fiscal_year)
                               ->update(['is_active' => false]);

            $setting->update(['is_active' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Setting berhasil diaktifkan',
                'data' => $setting->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal aktivasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/shu-settings/{id}/preview
     * Preview calculation with sample amount
     */
    public function preview(Request $request, string $id)
    {
        $setting = ShuPercentageSetting::findOrFail($id);

        $validated = $request->validate([
            'total_shu' => 'required|numeric|min:0',
        ]);

        $breakdown = $setting->calculateBreakdown($validated['total_shu']);

        return response()->json([
            'success' => true,
            'data' => $breakdown,
        ]);
    }

    /**
     * DELETE /api/shu-settings/{id}
     * Delete setting
     */
    public function destroy(string $id)
    {
        $setting = ShuPercentageSetting::findOrFail($id);

        if ($setting->distributions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Setting tidak dapat dihapus karena sudah digunakan',
            ], 422);
        }

        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Setting berhasil dihapus',
        ]);
    }
}
```

### Routes (api.php)

```php
// SHU Percentage Settings
Route::middleware(['auth:api'])->prefix('shu-settings')->group(function () {
    Route::get('/', [ShuPercentageSettingController::class, 'index']);
    Route::post('/', [ShuPercentageSettingController::class, 'store']);
    Route::get('/{id}', [ShuPercentageSettingController::class, 'show']);
    Route::put('/{id}', [ShuPercentageSettingController::class, 'update']);
    Route::delete('/{id}', [ShuPercentageSettingController::class, 'destroy']);
    Route::post('/{id}/activate', [ShuPercentageSettingController::class, 'activate']);
    Route::post('/{id}/preview', [ShuPercentageSettingController::class, 'preview']);
});
```

---

## 📝 Contoh Kasus

### Kasus 1: Setting Default (Sesuai UU)

```json
{
  "name": "Setting Default 2024",
  "fiscal_year": "2024",
  "cadangan_percentage": 30,
  "anggota_percentage": 70,
  "pengurus_percentage": 0,
  "karyawan_percentage": 0,
  "dana_sosial_percentage": 0,
  "jasa_modal_percentage": 40,
  "jasa_usaha_percentage": 60,
  "is_active": true
}
```

**Total SHU: Rp 150.000.000**

Breakdown:
- Cadangan: 30% × 150jt = **Rp 45.000.000**
- Anggota: 70% × 150jt = **Rp 105.000.000**
  - Jasa Modal: 40% × 105jt = **Rp 42.000.000**
  - Jasa Usaha: 60% × 105jt = **Rp 63.000.000**

---

### Kasus 2: Custom dengan Bonus Pengurus & Karyawan

```json
{
  "name": "Setting Custom 2024",
  "fiscal_year": "2024",
  "cadangan_percentage": 30,
  "anggota_percentage": 63,
  "pengurus_percentage": 5,
  "karyawan_percentage": 2,
  "dana_sosial_percentage": 0,
  "jasa_modal_percentage": 40,
  "jasa_usaha_percentage": 60,
  "is_active": true
}
```

**Total SHU: Rp 150.000.000**

Breakdown:
- Cadangan: 30% × 150jt = **Rp 45.000.000**
- Anggota: 63% × 150jt = **Rp 94.500.000**
  - Jasa Modal: 40% × 94,5jt = **Rp 37.800.000**
  - Jasa Usaha: 60% × 94,5jt = **Rp 56.700.000**
- Pengurus: 5% × 150jt = **Rp 7.500.000**
- Karyawan: 2% × 150jt = **Rp 3.000.000**

---

### Kasus 3: Dengan Dana Sosial

```json
{
  "name": "Setting CSR 2024",
  "fiscal_year": "2024",
  "cadangan_percentage": 30,
  "anggota_percentage": 60,
  "pengurus_percentage": 3,
  "karyawan_percentage": 2,
  "dana_sosial_percentage": 5,
  "jasa_modal_percentage": 35,
  "jasa_usaha_percentage": 65,
  "is_active": true
}
```

**Total SHU: Rp 150.000.000**

Breakdown:
- Cadangan: 30% × 150jt = **Rp 45.000.000**
- Anggota: 60% × 150jt = **Rp 90.000.000**
  - Jasa Modal: 35% × 90jt = **Rp 31.500.000**
  - Jasa Usaha: 65% × 90jt = **Rp 58.500.000**
- Pengurus: 3% × 150jt = **Rp 4.500.000**
- Karyawan: 2% × 150jt = **Rp 3.000.000**
- Dana Sosial: 5% × 150jt = **Rp 7.500.000**

---

## 🎯 Cara Menggunakan di Frontend

### 1. Create Setting Page

```typescript
// Component: ShuSettingCreate.tsx
const createSetting = async (data: SettingFormData) => {
  const response = await api.post('/shu-settings', {
    name: data.name,
    fiscal_year: data.fiscalYear,
    cadangan_percentage: data.cadangan,
    anggota_percentage: data.anggota,
    pengurus_percentage: data.pengurus || 0,
    karyawan_percentage: data.karyawan || 0,
    dana_sosial_percentage: data.danaSosial || 0,
    jasa_modal_percentage: data.jasaModal,
    jasa_usaha_percentage: data.jasaUsaha,
    is_active: true
  });
  
  return response.data;
};
```

### 2. Preview Calculation

```typescript
// Preview before creating distribution
const previewCalculation = async (settingId: string, totalShu: number) => {
  const response = await api.post(`/shu-settings/${settingId}/preview`, {
    total_shu: totalShu
  });
  
  // Response will show breakdown
  console.log(response.data);
  /*
  {
    cadangan: { percentage: 30, amount: 45000000 },
    anggota: {
      percentage: 70,
      amount: 105000000,
      breakdown: {
        jasa_modal: { percentage: 40, amount: 42000000 },
        jasa_usaha: { percentage: 60, amount: 63000000 }
      }
    },
    ...
  }
  */
};
```

### 3. Create Distribution dengan Setting

```typescript
// Saat create distribution, pilih setting
const createDistribution = async (data: DistributionData) => {
  const response = await api.post('/shu-distributions', {
    fiscal_year: data.fiscalYear,
    total_shu: data.totalShu,
    setting_id: data.settingId,  // ← Link ke setting
    description: data.description
  });
  
  return response.data;
};
```

---

## ✅ Validation Rules

### Frontend Validation

```typescript
const validatePercentages = (values: SettingFormValues) => {
  const errors: any = {};
  
  // Level 1: Must sum to 100
  const level1Total = 
    values.cadangan + 
    values.anggota + 
    (values.pengurus || 0) + 
    (values.karyawan || 0) + 
    (values.danaSosial || 0);
  
  if (Math.abs(level1Total - 100) > 0.01) {
    errors.total = `Total harus 100% (saat ini: ${level1Total}%)`;
  }
  
  // Level 2: Must sum to 100
  const level2Total = values.jasaModal + values.jasaUsaha;
  if (Math.abs(level2Total - 100) > 0.01) {
    errors.jasaTotal = `Jasa Modal + Jasa Usaha harus 100% (saat ini: ${level2Total}%)`;
  }
  
  // Minimum cadangan 30%
  if (values.cadangan < 30) {
    errors.cadangan = 'Cadangan minimal 30% sesuai UU Koperasi';
  }
  
  return errors;
};
```

---

## 🚀 Next Steps

1. **Buat migration** untuk tabel `shu_percentage_settings`
2. **Buat model** `ShuPercentageSetting.php`
3. **Buat controller** `ShuPercentageSettingController.php`
4. **Update** `SHUCalculationService` untuk baca dari setting
5. **Update** frontend untuk manage settings
6. **Testing** dengan berbagai skenario persentase

---

## 📚 References

- UU No. 25 Tahun 1992 tentang Perkoperasian
- Permenkop No. 15/Per/M.KUKM/IX/2015
- Prinsip Koperasi Indonesia

---

**Happy Coding! 🎉**
