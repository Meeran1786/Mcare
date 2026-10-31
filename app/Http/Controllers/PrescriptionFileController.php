<?php

namespace App\Http\Controllers;

use App\Models\PrescriptionFile;
use Illuminate\Support\Facades\Storage;

class PrescriptionFileController extends Controller
{
    /**
     * Serves a private prescription image. The route is protected by a signed,
     * expiring link, which the API hands out only to the patient, the
     * pharmacies the prescription was sent to, and admins.
     */
    public function show(string $file)
    {
        $path = PrescriptionFile::DIRECTORY.'/'.basename($file);
        $disk = Storage::disk(PrescriptionFile::DISK);

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
