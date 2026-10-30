<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('created_at', 'desc')->get()->map(function ($banner) {
            $banner->image_url = asset('storage/' . $banner->image);
            return $banner;
        });

        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'eyebrow' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'boolean'
        ]);

        $data = $request->except('image');
        
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $data['image'] = $path;
        }

        if(!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        $banner = Banner::create($data);
        $banner->image_url = asset('storage/' . $banner->image);

        return response()->json([
            'success' => true,
            'message' => 'Banner created successfully.',
            'data' => $banner
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'eyebrow' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'boolean'
        ]);

        $data = $request->except('image');

        // Handle string 'true' / 'false' if sent from FormData
        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->hasFile('image')) {
            // Delete old image
            if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }
            
            $path = $request->file('image')->store('banners', 'public');
            $data['image'] = $path;
        }

        $banner->update($data);
        $banner->image_url = asset('storage/' . $banner->image);

        return response()->json([
            'success' => true,
            'message' => 'Banner updated successfully.',
            'data' => $banner
        ]);
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        if ($banner->image && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banner deleted successfully.'
        ]);
    }
}
