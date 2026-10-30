<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'name',
        'phone',
        'address_line',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'is_default'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function addresses()
    {
        return $this->hasMany(\App\Models\Address::class);
    }
}
