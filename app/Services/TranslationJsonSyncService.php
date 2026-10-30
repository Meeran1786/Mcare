<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Support\Facades\File;

class TranslationJsonSyncService
{
    public function appendKey(Translation $translation): void
    {
        $path = $this->resourcesPath();

        if (!$path || !File::isDirectory($path)) {
            return;
        }

        foreach (Translation::LANGUAGES as $language) {
            $file = $path . DIRECTORY_SEPARATOR . "{$language}.json";
            $data = File::exists($file)
                ? json_decode(File::get($file), true)
                : [];

            if (!is_array($data)) {
                $data = [];
            }

            data_set($data, $translation->key, $translation->{$language} ?? '');

            File::put(
                $file,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
            );
        }
    }

    public function updateKeyValue(Translation $translation, string $language): void
    {
        $path = $this->resourcesPath();

        if (!$path || !File::isDirectory($path) || !in_array($language, Translation::LANGUAGES, true)) {
            return;
        }

        $file = $path . DIRECTORY_SEPARATOR . "{$language}.json";
        $data = File::exists($file)
            ? json_decode(File::get($file), true)
            : [];

        if (!is_array($data)) {
            $data = [];
        }

        data_set($data, $translation->key, $translation->{$language} ?? '');

        File::put(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }

    public function sync(): void
    {
        $path = $this->resourcesPath();

        if (!$path || !File::isDirectory($path)) {
            return;
        }

        $translations = Translation::query()
            ->select(['key', 'en', 'hi', 'te', 'ur'])
            ->orderBy('key')
            ->get();

        foreach (Translation::LANGUAGES as $language) {
            $data = [];

            foreach ($translations as $translation) {
                data_set($data, $translation->key, $translation->{$language} ?? '');
            }

            File::put(
                $path . DIRECTORY_SEPARATOR . "{$language}.json",
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
            );
        }
    }

    public function resourcesPath(): ?string
    {
        $configured = config('translations.resources_path');

        $paths = array_filter([
            $configured,
            env('TRANSLATION_JSON_PATH'),
            'E:\ReactNative\Elder Medical App\mcareUserApp\localization\resources',
        ]);

        foreach ($paths as $path) {
            if (File::isDirectory($path)) {
                return $path;
            }
        }

        return null;
    }
}
