<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Page;

class ManagePageController extends Controller
{
    private array $languages = [
        'en' => 'English',
        'te' => 'Telugu',
        'hi' => 'Hindi',
        'ur' => 'Urdu',
    ];

    // LIST PAGES
    public function index()
    {
        $pages = Page::latest()->get();
        $pageGroups = $pages->map(function (Page $page) {
            return [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title_en ?: $page->title,
                'updated_at' => $page->updated_at,
                'languages' => $this->availableLanguages($page),
            ];
        });
        $languages = $this->languages;

        return view('admin.pages.index', compact('pages', 'pageGroups', 'languages'));
    }

    // CREATE PAGE VIEW
    public function create()
    {
        $languages = $this->languages;
        $translations = collect();

        return view('admin.pages.create', compact('languages', 'translations'));
    }

    // STORE PAGE
    public function store(Request $request)
    {
        $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:pages,slug'],
            'title.en' => ['required', 'string', 'max:255'],
            'content.en' => ['required', 'string'],
            'title.*' => ['nullable', 'string', 'max:255'],
            'content.*' => ['nullable', 'string'],
        ]);

        Page::create($this->pagePayload($request));

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page translations created successfully');
    }

    // EDIT PAGE
    public function edit($id)
    {
        $page = Page::findOrFail($id);
        $languages = $this->languages;
        $translations = $this->translationsForPage($page);

        return view('admin.pages.edit', compact('page', 'languages', 'translations'));
    }

    // UPDATE PAGE
    public function update(Request $request, $id)
    {
        $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:pages,slug,' . $id],
            'title.en' => ['required', 'string', 'max:255'],
            'content.en' => ['required', 'string'],
            'title.*' => ['nullable', 'string', 'max:255'],
            'content.*' => ['nullable', 'string'],
        ]);

        $page = Page::findOrFail($id);
        $page->update($this->pagePayload($request));

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page translations updated successfully');
    }

    public function destroy($id)
    {
        Page::findOrFail($id)->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page deleted successfully');
    }

    private function pagePayload(Request $request): array
    {
        $titles = $request->input('title', []);
        $contents = $request->input('content', []);

        return [
            'title' => trim((string) ($titles['en'] ?? '')),
            'title_en' => trim((string) ($titles['en'] ?? '')),
            'title_te' => trim((string) ($titles['te'] ?? '')) ?: null,
            'title_hi' => trim((string) ($titles['hi'] ?? '')) ?: null,
            'title_ur' => trim((string) ($titles['ur'] ?? '')) ?: null,
            'slug' => $request->slug,
            'content' => trim((string) ($contents['en'] ?? '')),
            'content_en' => trim((string) ($contents['en'] ?? '')),
            'content_te' => trim((string) ($contents['te'] ?? '')) ?: null,
            'content_hi' => trim((string) ($contents['hi'] ?? '')) ?: null,
            'content_ur' => trim((string) ($contents['ur'] ?? '')) ?: null,
        ];
    }

    private function translationsForPage(Page $page)
    {
        return collect($this->languages)->mapWithKeys(function ($label, $code) use ($page) {
            $titleField = "title_{$code}";
            $contentField = "content_{$code}";

            return [
                $code => (object) [
                    'title' => $page->{$titleField} ?? '',
                    'content' => $page->{$contentField} ?? '',
                ],
            ];
        });
    }

    private function availableLanguages(Page $page): array
    {
        return collect(array_keys($this->languages))
            ->filter(function ($code) use ($page) {
                $titleField = "title_{$code}";
                $contentField = "content_{$code}";

                return filled($page->{$titleField}) || filled($page->{$contentField});
            })
            ->values()
            ->all();
    }
}
