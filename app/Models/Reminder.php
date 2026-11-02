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
        'pills_left',
        'pills_per_dose',
        'pills_counted_at',
        'refill_alerted_at',
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
        'pills_left' => 'integer',
        'pills_per_dose' => 'float',
        'pills_counted_at' => 'datetime',
        'refill_alerted_at' => 'datetime',
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

    /* =========================================
    | REFILL ESTIMATE
    ==========================================*/

    /**
     * Tablets left now: the count the patient entered, minus one dose for every
     * scheduled reminder time since then. Null when no count was entered.
     */
    public function pillsRemaining(?\Carbon\Carbon $now = null): ?float
    {
        if ($this->pills_left === null || !$this->pills_counted_at) {
            return null;
        }

        $tz = $this->timezone ?: config('app.timezone');
        $now = ($now ?? \Carbon\Carbon::now())->copy()->setTimezone($tz);
        $from = $this->pills_counted_at->copy()->setTimezone($tz);
        $days = $this->days ?? [];
        $doses = 0;

        // Walk day by day (at most a year) and count dose times in (from, now].
        $day = $from->copy()->startOfDay();
        for ($i = 0; $i < 400 && $day->lessThanOrEqualTo($now); $i++, $day->addDay()) {
            if (!in_array($day->format('D'), $days, true)) {
                continue;
            }
            $at = $day->copy()->setTimeFromTimeString($this->time);
            if ($at->greaterThan($from) && $at->lessThanOrEqualTo($now)) {
                $doses++;
            }
        }

        return max(0, $this->pills_left - $doses * ($this->pills_per_dose ?: 1));
    }

    /** Whole days until the tablets run out, from the weekly schedule. */
    public function daysLeft(?\Carbon\Carbon $now = null): ?int
    {
        $left = $this->pillsRemaining($now);
        $perWeek = count($this->days ?? []) * ($this->pills_per_dose ?: 1);
        if ($left === null || $perWeek <= 0) {
            return null;
        }

        return (int) floor($left / ($perWeek / 7));
    }

    // Short days (for UI)
    public function getDaysTextAttribute()
    {
        return implode(', ', $this->days ?? []);
    }
}
