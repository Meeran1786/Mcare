<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = [
        'title',
        'title_en',
        'title_te',
        'title_hi',
        'title_ur',
        'slug',
        'content',
        'content_en',
        'content_te',
        'content_hi',
        'content_ur',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }
}
