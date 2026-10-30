<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    private array $supportedLanguages = ['en', 'te', 'hi', 'ur'];

    public function show(Request $request, $slug)
    {
        $language = strtolower((string) (
            $request->query('language')
            ?: $request->header('X-App-Language')
            ?: $request->header('Accept-Language')
            ?: 'en'
        ));
        $language = explode(',', $language)[0];
        $language = explode('-', $language)[0];
        $language = in_array($language, $this->supportedLanguages, true) ? $language : 'en';

        $slugAliases = [
            'terms-and-conditions' => 'terms-conditions',
            'terms-conditions' => 'terms-and-conditions',
        ];
        $slugs = array_values(array_filter(array_unique([$slug, $slugAliases[$slug] ?? null])));

        $page = Page::whereIn('slug', $slugs)->first();

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
                'message_key' => 'pageNotFound',
            ], 404);
        }

        $titleField = "title_{$language}";
        $contentField = "content_{$language}";

        $title = $page->{$titleField} ?: $page->title_en ?: $page->title;
        $content = $page->{$contentField} ?: $page->content_en ?: $page->content;
        $fallbackUsed = $language !== 'en' && empty($page->{$contentField});

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $title,
                'content' => $content,
                'language' => $fallbackUsed ? 'en' : $language,
            ],
            'meta' => [
                'requested_language' => $language,
                'language' => $fallbackUsed ? 'en' : $language,
                'fallback_used' => $fallbackUsed,
            ],
        ]);
    }
}
