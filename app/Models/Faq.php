<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'type',
        'question',
        'question_en',
        'question_te',
        'question_hi',
        'question_ur',
        'answer',
        'answer_en',
        'answer_te',
        'answer_hi',
        'answer_ur',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
