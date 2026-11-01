<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaregiverLink extends Model
{
    protected $fillable = ['patient_id', 'caregiver_id', 'invite_code', 'invite_expires_at', 'status'];

    protected $casts = [
        'invite_expires_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function caregiver()
    {
        return $this->belongsTo(User::class, 'caregiver_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->whereNotNull('caregiver_id');
    }
}
