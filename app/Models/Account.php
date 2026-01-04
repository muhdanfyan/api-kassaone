<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasCuid;

    /**
     * The primary key type
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'parent_id',
        'code',
        'name',
        'type',
        'group',
        'description',
        'created_by',
    ];

    /**
     * Get the parent account
     */
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get the child accounts
     */
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the member who created this account
     */
    public function creator()
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    /**
     * Get the expenses for this account
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'account_id');
    }
}
