<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SocialLinkController extends Controller
{
    // LIST PAGE
    public function index()
    {
        $links = SocialLink::orderBy('sort_order')
                    ->orderBy('label')
                    ->get();

        return view('admin.social-links.index', compact('links'));
    }

    // CREATE PAGE
    public function create()
    {
        return view('admin.social-links.create');
    }

    // STORE
    public function store(Request $request)
    {
        $data = $this->validated($request);

        SocialLink::create($data);

        return redirect()
            ->route('admin.social-links.index')
            ->with('success', 'Social link created successfully.');
    }

    // EDIT PAGE
    public function edit($id)
    {
        $link = SocialLink::findOrFail($id);

        return view('admin.social-links.edit', compact('link'));
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $link = SocialLink::findOrFail($id);

        $link->update($this->validated($request));

        return redirect()
            ->route('admin.social-links.index')
            ->with('success', 'Social link updated successfully.');
    }

    // DELETE
    public function destroy($id)
    {
        SocialLink::findOrFail($id)->delete();

        return redirect()
            ->route('admin.social-links.index')
            ->with('success', 'Social link deleted successfully.');
    }

    // VALIDATION
    private function validated(Request $request): array
    {
        $data = $request->validate([

            'platform' => [
                'required',
                'string',
                Rule::in([
                    'facebook',
                    'instagram',
                    'linkedin',
                    'x',
                    'whatsapp',
                    'youtube'
                ])
            ],

            'label' => 'required|string|max:100',

            'url' => 'required|url|max:500',

            'sort_order' => 'nullable|integer|min:0',

            'is_active' => 'nullable|boolean',

        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        $data['is_active'] = $request->has('is_active');

        return $data;
    }
}