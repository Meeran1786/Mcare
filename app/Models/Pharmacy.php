<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pharmacy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'owner_name',
        'pharmacy_name',
        'pharmacy_photo',

        'license_number',
        'license_file',

        'gst_number',
        'gst_file',

        'registration_number',

        'address_line',
        'area',
        'landmark',
        'city',
        'state',
        'pincode',

        'latitude',
        'longitude',

        'opening_time',
        'closing_time',

        'verification_status',
        'rejection_reason',

        'is_verified',
        'is_delivery_available',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_delivery_available' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(
            \App\Models\Order::class,
            'pharmacy_id'
        );
    }
}