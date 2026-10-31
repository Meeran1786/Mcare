<?php

namespace App\Console\Commands;

use App\Support\PrivateFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MakeSensitiveFilesPrivate extends Command
{
    protected $signature = 'storage:make-private {--dry-run : List the files without moving them}';

    protected $description = 'Move prescriptions and pharmacy licence/GST documents from the public disk to private storage';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk(PrivateFiles::DISK);
        $moved = 0;

        foreach (PrivateFiles::DIRECTORIES as $directory) {
            foreach ($public->files($directory) as $path) {
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
        }

        $verb = $this->option('dry-run') ? 'Would move' : 'Moved';
        $this->info("{$verb} {$moved} file(s) to private storage.");

        return self::SUCCESS;
    }
}
