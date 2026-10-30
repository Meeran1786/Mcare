<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportTranslations extends Command
{
    protected $signature = 'translations:import {path? : Folder containing en.json, hi.json, te.json, ur.json}';

    protected $description = 'Import mobile app translation JSON files into the translations table';

    public function handle(): int
    {
        $path = $this->argument('path') ?: $this->defaultPath();

        if (!File::isDirectory($path)) {
            $this->error("Translation folder not found: {$path}");
            $this->line('Pass the folder path explicitly, for example:');
            $this->line('php artisan translations:import "E:\ReactNative\Elder Medical App\mcareUserApp\localization\resources"');
            return self::FAILURE;
        }

        $resources = [];

        foreach (Translation::LANGUAGES as $language) {
            $file = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "{$language}.json";

            if (!File::exists($file)) {
                $this->error("Missing file: {$file}");
                return self::FAILURE;
            }

            $decoded = json_decode(File::get($file), true);

            if (!is_array($decoded)) {
                $this->error("Invalid JSON file: {$file}");
                return self::FAILURE;
            }

            $resources[$language] = $this->flatten($decoded);
        }

        $keys = collect($resources)
            ->flatMap(fn ($items) => array_keys($items))
            ->unique()
            ->sort()
            ->values();

        foreach ($keys as $key) {
            $payload = [
                'group' => str_contains($key, '.') ? str($key)->before('.')->toString() : 'app',
            ];

            foreach (Translation::LANGUAGES as $language) {
                $payload[$language] = $resources[$language][$key] ?? null;
            }

            Translation::updateOrCreate(['key' => $key], $payload);
        }

        $this->info("Imported {$keys->count()} translation keys.");

        return self::SUCCESS;
    }

    private function defaultPath(): string
    {
        $paths = [
            base_path('../mcareUserApp/localization/resources'),
            'E:\ReactNative\Elder Medical App\mcareUserApp\localization\resources',
        ];

        foreach ($paths as $path) {
            if (File::isDirectory($path)) {
                return $path;
            }
        }

        return $paths[0];
    }

    private function flatten(array $items, string $prefix = ''): array
    {
        $flat = [];

        foreach ($items as $key => $value) {
            $nextKey = $prefix === '' ? $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $flat += $this->flatten($value, $nextKey);
                continue;
            }

            $flat[$nextKey] = $value === null ? null : (string) $value;
        }

        return $flat;
    }
}
