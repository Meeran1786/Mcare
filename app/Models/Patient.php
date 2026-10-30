<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $table = 'patients';

    protected $fillable = [
        'user_id',
        'name',
        'age',
        'gender',
        'is_self'
    ];

    protected $casts = [
        'is_self' => 'boolean'
    ];

    /*
    ======================================
    RELATIONSHIP WITH USER
    ======================================
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}