<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use Illuminate\Support\Facades\Storage;

class ManageBannerController extends Controller
{
    private const BANNER_IMAGE_RULE = 'image|mimes:jpeg,png,jpg,gif,webp|max:2048|dimensions:width=1920,height=600';

    public function index()
    {
        $banners = Banner::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.banners.index', compact('banners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'eyebrow' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|' . self::BANNER_IMAGE_RULE,
            'is_active' => 'nullable'
        ], [
            'image.dimensions' => 'Banner image must be exactly 1920x600 pixels.',
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $data['image'] = $path;
        }

        $data['is_active'] = $request->has('is_active') ? true : false;

        Banner::create($data);

        return back()->with('success', 'Banner created successfully.');
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'eyebrow' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|' . self::BANNER_IMAGE_RULE,
            'is_active' => 'nullable'
        ], [
            'image.dimensions' => 'Banner image must be exactly 1920x600 pixels.',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }
            $path = $request->file('image')->store('banners', 'public');
            $data['image'] = $path;
        }

        $data['is_active'] = $request->has('is_active') ? true : false;

        $banner->update($data);

        return back()->with('success', 'Banner updated successfully.');
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        if ($banner->image && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return back()->with('success', 'Banner deleted successfully.');
    }
}
