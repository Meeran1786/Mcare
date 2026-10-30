<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionFile extends Model
{
    protected $fillable = [
        'prescription_id',
        'file_path',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
}
