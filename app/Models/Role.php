<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description'];
    public $timestamps = false; // Roles table does not have timestamps

    public function members()
    {
        return $this->hasMany(Member::class);
    }
}
