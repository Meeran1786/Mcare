<?php

namespace App\Console\Commands;

use App\Models\PrescriptionFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MakePrescriptionFilesPrivate extends Command
{
    protected $signature = 'prescriptions:make-private {--dry-run : List the files without moving them}';

    protected $description = 'Move prescription images from the public disk to private storage';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk(PrescriptionFile::DISK);
        $moved = 0;

        foreach ($public->files(PrescriptionFile::DIRECTORY) as $path) {
            if ($this->option('dry-run')) {
                $this->line("would move {$path}");
                $moved++;
                continue;
            }

            if (!$private->exists($path)) {
                $stream = $public->readStream($path);
                $private->writeStream($path, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if ($private->exists($path) && $private->size($path) === $public->size($path)) {
                $public->delete($path);
                $moved++;
            } else {
                $this->error("Could not copy {$path}; left it in place.");
            }
        }

        $verb = $this->option('dry-run') ? 'Would move' : 'Moved';
        $this->info("{$verb} {$moved} prescription file(s) to private storage.");

        return self::SUCCESS;
    }
}
