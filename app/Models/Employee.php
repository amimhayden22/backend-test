<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;
    
    protected $table = 'employees';
    protected $fillable = ['user_id', 'name', 'phone_number', 'address'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
