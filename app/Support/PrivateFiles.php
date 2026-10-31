<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Sensitive uploads (prescriptions, pharmacy licence and GST documents) live on
 * the private disk and are only reachable through an expiring signed link.
 *
 * The links keep the old public URL shape, "/storage/<path>", because the live
 * apps build image URLs as "<site>/storage/<file_path>".
 */
class PrivateFiles
{
    public const DISK = 'local';

    public const LINK_MINUTES = 60;

    /** Folders whose files are private. Everything else on the public disk stays public. */
    public const DIRECTORIES = [
        'prescriptions',
        'pharmacies/licenses',
        'pharmacies/gst',
    ];

    /** Route constraint for {path}: one of DIRECTORIES plus a plain file name. */
    public static function routePattern(): string
    {
        $dirs = implode('|', array_map(fn ($d) => preg_quote($d, '#'), self::DIRECTORIES));

        return '(?:'.$dirs.')/[A-Za-z0-9._-]+';
    }

    public static function isAllowed(?string $path): bool
    {
        return $path !== null
            && in_array(dirname($path), self::DIRECTORIES, true)
            && preg_match('/^[A-Za-z0-9._-]+$/', basename($path)) === 1;
    }

    public static function isPrivate(?string $path): bool
    {
        return self::isAllowed($path) && Storage::disk(self::DISK)->exists($path);
    }

    /**
     * Path relative to "/storage/". Signed for private files; unchanged for
     * legacy files still on the public disk (before storage:make-private runs).
     */
    public static function storagePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (!self::isPrivate($path)) {
            return $path;
        }

        $signed = URL::temporarySignedRoute(
            'private-files.show',
            now()->addMinutes(self::LINK_MINUTES),
            ['path' => $path],
            absolute: false
        );

        return ltrim(substr($signed, strlen('/storage/')), '/');
    }

    public static function url(?string $path): ?string
    {
        $relative = self::storagePath($path);

        return $relative ? url('storage/'.$relative) : null;
    }

    /** Delete a file from whichever disk it is on. */
    public static function delete(?string $path): void
    {
        if (!$path) {
            return;
        }

        foreach ([self::DISK, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
