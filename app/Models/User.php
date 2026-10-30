<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'mobile_number',
        'email',
        'role',
        'status',
        'preferred_language',
        'fcm_token', 
    ];

    protected $hidden = [
        'remember_token',
    ];
    public function profile()
    {
        return $this->hasOne(\App\Models\UserProfile::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function address()
    {
        return $this->hasOne(\App\Models\Address::class, 'user_id', 'id')
                    ->where('is_default', 1);
    }
    public function pharmacy()
{
    return $this->hasOne(\App\Models\Pharmacy::class, 'user_id');
}

}
