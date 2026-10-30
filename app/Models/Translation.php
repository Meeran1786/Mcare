<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    public const LANGUAGES = ['en', 'hi', 'te', 'ur'];

    protected $fillable = [
        'key',
        'group',
        'en',
        'hi',
        'te',
        'ur',
    ];
}
