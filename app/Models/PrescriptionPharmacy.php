<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionPharmacy extends Model
{
    protected $fillable = [
        'prescription_id',
        'pharmacy_id',
        'status',
        'rejection_reason',
    ];

    // Belongs to prescription
    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    // Belongs to pharmacy
    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    // Shortcut to vendor (optional but useful)
    public function vendor()
    {
        return $this->pharmacy->user ?? null;
    }
    
}