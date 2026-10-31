<?php

namespace App\Http\Controllers;

use App\Support\PrivateFiles;
use Illuminate\Support\Facades\Storage;

class PrivateFileController extends Controller
{
    /**
     * Serves a private file. The route only accepts a valid, unexpired signed
     * link, which the API hands out only to people allowed to see the file.
     */
    public function show(string $path)
    {
        abort_unless(PrivateFiles::isAllowed($path), 404);

        $disk = Storage::disk(PrivateFiles::DISK);

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
