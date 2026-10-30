<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use App\Services\TranslationJsonSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManageTranslationController extends Controller
{
    public function __construct(private TranslationJsonSyncService $jsonSync)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $translations = Translation::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('key', 'like', "%{$search}%")
                    ->orWhere('en', 'like', "%{$search}%")
                    ->orWhere('hi', 'like', "%{$search}%")
                    ->orWhere('te', 'like', "%{$search}%")
                    ->orWhere('ur', 'like', "%{$search}%");
            })
            ->orderBy('key')
            ->paginate(80)
            ->withQueryString();

        return view('admin.translations.index', [
            'translations' => $translations,
            'languages' => Translation::LANGUAGES,
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.-]+$/', 'unique:translations,key'],
            'en' => ['nullable', 'string'],
            'hi' => ['nullable', 'string'],
            'te' => ['nullable', 'string'],
            'ur' => ['nullable', 'string'],
        ]);

        $translation = Translation::create([
            'key' => $validated['key'],
            'group' => str_contains($validated['key'], '.') ? str($validated['key'])->before('.')->toString() : 'app',
            'en' => $validated['en'] ?? '',
            'hi' => $validated['hi'] ?? '',
            'te' => $validated['te'] ?? '',
            'ur' => $validated['ur'] ?? '',
        ]);

        $this->jsonSync->appendKey($translation);

        return response()->json([
            'success' => true,
            'message' => 'Translation key created.',
            'translation' => $translation,
        ]);
    }

    public function update(Request $request, Translation $translation)
    {
        $validated = $request->validate([
            'language' => ['required', Rule::in(Translation::LANGUAGES)],
            'value' => ['nullable', 'string'],
        ]);

        $translation->update([
            $validated['language'] => $validated['value'] ?? '',
        ]);

        $this->jsonSync->updateKeyValue($translation, $validated['language']);

        return response()->json([
            'success' => true,
            'message' => 'Translation saved.',
            'translation' => [
                'id' => $translation->id,
                'key' => $translation->key,
                'language' => $validated['language'],
                'value' => $translation->{$validated['language']},
            ],
        ]);
    }
}
