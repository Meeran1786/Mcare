<?php

namespace App\Models;

use App\Support\PrivateFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** An extra shop photo (public) or one page of the licence or GST document (private). */
class PharmacyFile extends Model
{
    public const KINDS = ['photo', 'license', 'gst'];

    protected $fillable = ['pharmacy_id', 'kind', 'path'];

    public function url(): ?string
    {
        return $this->kind === 'photo'
            ? Storage::disk('public')->url($this->path)
            : PrivateFiles::url($this->path);
    }

    public function deleteWithFile(): void
    {
        if ($this->kind === 'photo') {
            Storage::disk('public')->delete($this->path);
        } else {
            PrivateFiles::delete($this->path);
        }
        $this->delete();
    }
}
