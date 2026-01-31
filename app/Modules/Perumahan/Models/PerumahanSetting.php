<?php

namespace App\Modules\Perumahan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PerumahanSetting extends Model
{
    protected $table = 'perumahan_settings';
    
    protected $fillable = [
        'setting_key',
        'setting_value',
        'category',
        'data_type',
        'description',
        'is_system',
    ];
    
    protected $casts = [
        'is_system' => 'boolean',
    ];
    
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
    
    /**
     * Get parsed value based on data_type
     */
    public function getParsedValueAttribute()
    {
        switch ($this->data_type) {
            case 'number':
                return is_numeric($this->setting_value) ? (float) $this->setting_value : 0;
            case 'boolean':
                return filter_var($this->setting_value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($this->setting_value, true);
            default:
                return $this->setting_value;
        }
    }
}
