<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['user_id', 'plan', 'kind', 'starts_at', 'ends_at', 'payment_id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCurrent($query)
    {
        return $query->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }
}
