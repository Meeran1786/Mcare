<?php

namespace App\Models;

use App\Support\PrivateFiles;
use Illuminate\Database\Eloquent\Model;

class PrescriptionFile extends Model
{
    /** Prescription images are medical records: stored privately, served by signed link. */
    public const DISK = PrivateFiles::DISK;

    public const DIRECTORY = 'prescriptions';

    protected $fillable = [
        'prescription_id',
        'file_path',
    ];

    protected $appends = ['url'];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function getUrlAttribute(): ?string
    {
        return PrivateFiles::url($this->file_path);
    }

    public function toArray()
    {
        $data = parent::toArray();
        // Signed path, so apps that build "<site>/storage/<file_path>" still work.
        $data['file_path'] = PrivateFiles::storagePath($this->file_path);

        return $data;
    }
}
