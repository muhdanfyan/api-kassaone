<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    use HasCuid;

    protected $fillable = [
        'code',
        'name',
        'description',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->code)) {
                $lastAccount = self::orderBy('code', 'desc')->first();
                $nextNumber = 1;
                if ($lastAccount && preg_match('/ACC-(\d+)/', $lastAccount->code, $matches)) {
                    $nextNumber = (int)$matches[1] + 1;
                }
                $model->code = 'ACC-' . str_pad((string)$nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by');
    }
}
