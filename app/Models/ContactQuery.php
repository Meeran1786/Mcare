<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactQuery extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'service',
        'message',
        'consent',
        'is_read'
    ];

    protected $casts = [
        'consent' => 'boolean',
        'is_read' => 'boolean'
    ];
}
