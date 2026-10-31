<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\PrivateFiles;
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

    /** Licence and GST documents are private: signed, expiring link or null. */
    public function documentUrl(string $attribute): ?string
    {
        return PrivateFiles::url($this->{$attribute} ?? null);
    }

    public function toArray()
    {
        $data = parent::toArray();

        // Signed paths, so apps that build "<site>/storage/<file>" still work.
        foreach (['license_file', 'gst_file'] as $document) {
            if (array_key_exists($document, $data)) {
                $data[$document] = PrivateFiles::storagePath($this->{$document});
            }
        }

        return $data;
    }

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