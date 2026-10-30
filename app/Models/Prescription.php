<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'notes',
        'status',
        'address_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    // Prescription belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Files attached to prescription
    public function files()
    {
        return $this->hasMany(PrescriptionFile::class);
    }

    // Pharmacies assigned to prescription
    public function pharmacies()
    {
        return $this->hasMany(PrescriptionPharmacy::class);
    }

    // Order created from prescription
    public function order()
    {
        return $this->hasOne(Order::class);
    }
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}