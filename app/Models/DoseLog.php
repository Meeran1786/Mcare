<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoseLog extends Model
{
    public const STATUSES = ['taken', 'skipped', 'missed'];

    protected $fillable = ['user_id', 'reminder_id', 'scheduled_for', 'status', 'taken_at', 'caregivers_notified_at'];

    protected $casts = [
        'taken_at' => 'datetime',
        'caregivers_notified_at' => 'datetime',
    ];

    public function reminder()
    {
        return $this->belongsTo(Reminder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
