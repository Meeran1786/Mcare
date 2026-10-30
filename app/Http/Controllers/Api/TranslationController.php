<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Support\Carbon;

class TranslationController extends Controller
{
    public function index()
    {
        $resources = collect(Translation::LANGUAGES)
            ->mapWithKeys(fn ($language) => [$language => []])
            ->toArray();

        Translation::query()
            ->select(['key', 'en', 'hi', 'te', 'ur'])
            ->orderBy('key')
            ->chunk(200, function ($translations) use (&$resources) {
                foreach ($translations as $translation) {
                    foreach (Translation::LANGUAGES as $language) {
                        if ($translation->{$language} !== null) {
                            data_set($resources[$language], $translation->key, $translation->{$language});
                        }
                    }
                }
            });

        $updatedAt = Translation::max('updated_at');

        return response()->json([
            'success' => true,
            'data' => $resources,
            'updated_at' => $updatedAt ? Carbon::parse($updatedAt)->toISOString() : null,
        ]);
    }
}
