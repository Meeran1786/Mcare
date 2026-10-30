<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManageFaqController extends Controller
{
    private array $types = [
        'help' => 'Need Help Screen',
        'payment' => 'Payment Screen',
    ];

    private array $languages = [
        'en' => 'English',
        'te' => 'Telugu',
        'hi' => 'Hindi',
        'ur' => 'Urdu',
    ];

    public function index(Request $request)
    {
        $selectedType = $request->query('type');

        $faqs = Faq::query()
            ->when($selectedType, fn ($query) => $query->where('type', $selectedType))
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.faqs.index', [
            'faqs' => $faqs,
            'types' => $this->types,
            'languages' => $this->languages,
            'selectedType' => $selectedType,
        ]);
    }

    public function create()
    {
        return view('admin.faqs.create', [
            'types' => $this->types,
            'languages' => $this->languages,
        ]);
    }

    public function store(Request $request)
    {
        Faq::create($this->validatedData($request));

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'FAQ created successfully');
    }

    public function edit($id)
    {
        $faq = Faq::findOrFail($id);

        return view('admin.faqs.edit', [
            'faq' => $faq,
            'types' => $this->types,
            'languages' => $this->languages,
        ]);
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);
        $faq->update($this->validatedData($request));

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'FAQ updated successfully');
    }

    public function destroy($id)
    {
        Faq::findOrFail($id)->delete();

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'FAQ deleted successfully');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys($this->types))],
            'question.en' => ['required', 'string', 'max:255'],
            'answer.en' => ['required', 'string'],
            'question.*' => ['nullable', 'string', 'max:255'],
            'answer.*' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $questions = $request->input('question', []);
        $answers = $request->input('answer', []);

        return [
            'type' => $data['type'],
            'question' => trim((string) ($questions['en'] ?? '')),
            'question_en' => trim((string) ($questions['en'] ?? '')),
            'question_te' => trim((string) ($questions['te'] ?? '')) ?: null,
            'question_hi' => trim((string) ($questions['hi'] ?? '')) ?: null,
            'question_ur' => trim((string) ($questions['ur'] ?? '')) ?: null,
            'answer' => trim((string) ($answers['en'] ?? '')),
            'answer_en' => trim((string) ($answers['en'] ?? '')),
            'answer_te' => trim((string) ($answers['te'] ?? '')) ?: null,
            'answer_hi' => trim((string) ($answers['hi'] ?? '')) ?: null,
            'answer_ur' => trim((string) ($answers['ur'] ?? '')) ?: null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
