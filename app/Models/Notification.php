<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'image_path',
        'type',
        'reference_id',
        'target_type',
        'send_mode',
        'status',
        'scheduled_at',
        'sent_count',
        'failed_count',
        'opened_count',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'scheduled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function receivers()
    {
        return $this->hasMany(NotificationReceiver::class);
    }
}
