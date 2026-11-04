<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\PrivateFiles;
use Illuminate\Database\Eloquent\Model;

class Pharmacy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'owner_name',
        'pharmacy_name',
        'pharmacy_photo',

        'license_number',
        'license_file',

        'gst_number',
        'gst_file',

        'registration_number',

        'address_line',
        'area',
        'landmark',
        'city',
        'state',
        'pincode',

        'latitude',
        'longitude',

        'opening_time',
        'closing_time',

        'verification_status',
        'rejection_reason',

        'is_verified',
        'is_delivery_available',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_delivery_available' => 'boolean',
    ];

    /** Licence and GST documents are private: signed, expiring link or null. */
    public function documentUrl(string $attribute): ?string
    {
        return PrivateFiles::url($this->{$attribute} ?? null);
    }

    public function toArray()
    {
        $data = parent::toArray();

        // Signed paths, so apps that build "<site>/storage/<file>" still work.
        foreach (['license_file', 'gst_file'] as $document) {
            if (array_key_exists($document, $data)) {
                $data[$document] = PrivateFiles::storagePath($this->{$document});
            }
        }

        return $data;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Extra shop photos and document pages. */
    public function files()
    {
        return $this->hasMany(PharmacyFile::class)->orderBy('id');
    }

    /** Signed or public links for one kind of file: photo, license or gst. */
    public function fileUrls(string $kind): array
    {
        return $this->files->where('kind', $kind)->map(fn ($f) => $f->url())->filter()->values()->all();
    }

    /** Cover photo first, then the extra shop photos. */
    public function photoUrls(): array
    {
        $cover = $this->pharmacy_photo ? [\Illuminate\Support\Facades\Storage::disk('public')->url($this->pharmacy_photo)] : [];

        return array_merge($cover, $this->fileUrls('photo'));
    }

    /**
     * Every page of a document. Pharmacies that uploaded before multi-page
     * support have only the single file in the old column.
     */
    public function documentPageUrls(string $kind): array
    {
        $pages = $this->fileUrls($kind);
        if ($pages) {
            return $pages;
        }
        $single = $this->documentUrl($kind.'_file');

        return $single ? [$single] : [];
    }

    public function orders()
    {
        return $this->hasMany(
            \App\Models\Order::class,
            'pharmacy_id'
        );
    }
}