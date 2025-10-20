<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable; // Use Authenticatable for Sanctum

class Member extends Authenticatable // Extend Authenticatable instead of Model
{
    use HasApiTokens;

    protected $fillable = [
        'member_id_number',
        'full_name',
        'username',
        'password',
        'email',
        'phone_number',
        'address',
        'join_date',
        'status',
        'role_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
