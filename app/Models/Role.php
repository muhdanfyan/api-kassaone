<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasCuid;

    protected $fillable = ['name', 'description'];
    public $timestamps = false; // Roles table does not have timestamps

    public function members()
    {
        return $this->hasMany(Member::class);
    }
}
