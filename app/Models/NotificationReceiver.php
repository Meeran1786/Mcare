<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationReceiver extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'user_id',
        'fcm_token',
        'status',
        'error',
        'sent_at',
        'opened_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
