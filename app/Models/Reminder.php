<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reminder extends Model
{
    use HasFactory;

    /* =========================================
    | FILLABLE
    ==========================================*/
    protected $fillable = [
        'user_id',
        'time',
        'days',
        'is_active',
        'note',
        'medicine_name',
        'timezone',
        'last_sent_at',
        'last_sent_key',
    ];

    /* =========================================
    | CASTS
    ==========================================*/
    // protected $casts = [
    //     'time' => 'datetime:H:i:s',   // ensures proper format
    //     'days' => 'array',
    //     'is_active' => 'boolean',
    //     'last_sent_at' => 'datetime',
    // ];
    protected $casts = [
        'days' => 'array',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
    ];

    /* =========================================
    | RELATIONSHIP
    ==========================================*/
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* =========================================
    | SCOPES (VERY USEFUL)
    ==========================================*/

    // Active reminders only
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Today's reminders
    public function scopeForToday($query)
    {
        $today = now()->format('D'); // Mon, Tue, etc

        return $query->whereJsonContains('days', $today);
    }

    /* =========================================
    | ACCESSORS (OPTIONAL UI HELPERS)
    ==========================================*/

    // Format time like 12:15 PM
    public function getFormattedTimeAttribute()
    {
        return \Carbon\Carbon::parse($this->time)->format('h:i A');
    }

    // Short days (for UI)
    public function getDaysTextAttribute()
    {
        return implode(', ', $this->days ?? []);
    }
}
