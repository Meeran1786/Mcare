<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [

        'user_play_store',

        'user_app_store',

        'vendor_play_store',

        'vendor_app_store',

        'user_latest_version',

        'user_force_update',

        'vendor_latest_version',

        'vendor_force_update',

    ];

    protected $casts = [
        'user_force_update' => 'boolean',
        'vendor_force_update' => 'boolean',
    ];
}
