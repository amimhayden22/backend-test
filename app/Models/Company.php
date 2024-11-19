<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $table = 'companies';
    protected $fillable = ['name', 'email', 'phone_number'];

    public function user()
    {
        return $this->hasMany(User::class);
    }
}
