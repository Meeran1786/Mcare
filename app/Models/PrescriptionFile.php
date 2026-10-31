<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class PrescriptionFile extends Model
{
    /**
     * Prescription images are medical records. New uploads go to this private
     * disk (storage/app/private) and can only be fetched with a signed link
     * that expires.
     */
    public const DISK = 'local';

    public const DIRECTORY = 'prescriptions';

    public const LINK_MINUTES = 60;

    protected $fillable = [
        'prescription_id',
        'file_path',
    ];

    protected $appends = ['url'];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function isPrivate(): bool
    {
        return $this->file_path
            && dirname($this->file_path) === self::DIRECTORY
            && Storage::disk(self::DISK)->exists($this->file_path);
    }

    /**
     * Path relative to "/storage/", as the live apps expect it.
     * For a private file this carries the signature, so an app that builds
     * "<site>/storage/<file_path>" still gets a working, expiring link.
     */
    public function publicPath(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        if (!$this->isPrivate()) {
            // Legacy file still on the public disk (before prescriptions:make-private runs).
            return $this->file_path;
        }

        $signed = URL::temporarySignedRoute(
            'prescription-files.show',
            now()->addMinutes(self::LINK_MINUTES),
            ['file' => basename($this->file_path)],
            absolute: false
        );

        return ltrim(substr($signed, strlen('/storage/')), '/');
    }

    public function getUrlAttribute(): ?string
    {
        $path = $this->publicPath();

        return $path ? url('storage/'.$path) : null;
    }

    public function toArray()
    {
        $data = parent::toArray();
        $data['file_path'] = $this->publicPath();

        return $data;
    }
}
