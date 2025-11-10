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
            'total_shu' => round($totalShu, 2),
            'cadangan' => [
                'percentage' => (float) $this->cadangan_percentage,
                'amount' => round($cadangan, 2),
            ],
            'anggota' => [
                'percentage' => (float) $this->anggota_percentage,
                'amount' => round($anggota, 2),
                'breakdown' => [
                    'jasa_modal' => [
                        'percentage' => (float) $this->jasa_modal_percentage,
                        'amount' => round($jasaModal, 2),
                    ],
                    'jasa_usaha' => [
                        'percentage' => (float) $this->jasa_usaha_percentage,
                        'amount' => round($jasaUsaha, 2),
                    ],
                ],
            ],
            'pengurus' => [
                'percentage' => (float) $this->pengurus_percentage,
                'amount' => round($pengurus, 2),
            ],
            'karyawan' => [
                'percentage' => (float) $this->karyawan_percentage,
                'amount' => round($karyawan, 2),
            ],
            'dana_sosial' => [
                'percentage' => (float) $this->dana_sosial_percentage,
                'amount' => round($danaSosial, 2),
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
