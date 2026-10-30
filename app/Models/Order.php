<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Fillable Fields
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'user_id',
        'pharmacy_id',
        'prescription_id',
        'address_id',
        'delivery_lat',
        'delivery_lng',
        'status',
        'payment_status',
        'payment_method',
        'total_amount',
        'delivery_time',
        'delivery_date',
        'delivery_clock_time',
        'delivery_note',
        'completed_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Order belongs to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Order belongs to Pharmacy
    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    // Order belongs to Prescription (optional)
    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'delivery_lat' => 'float',
        'delivery_lng' => 'float',
        'total_amount' => 'decimal:2',
        'delivery_time' => 'datetime',
        'delivery_date' => 'date',
        'completed_at' => 'datetime',
    ];
}
