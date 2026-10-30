<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    // ALL SERVICES
    public function index()
    {
        $services = Service::orderBy('created_at', 'desc')
            ->get()
            ->map(function ($service) {
                $service->image_url = $service->image
                    ? asset('storage/' . $service->image)
                    : null;

                return $service;
            });

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    // HOME PAGE SERVICES
    public function homeServices()
    {
        $services = Service::where('type', 'home')
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map(function ($service) {
                $service->image_url = $service->image
                    ? asset('storage/' . $service->image)
                    : null;

                return $service;
            });

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    // SERVICES PAGE SERVICES
    public function pageServices()
    {
        $services = Service::where('type', 'service_page')
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map(function ($service) {
                $service->image_url = $service->image
                    ? asset('storage/' . $service->image)
                    : null;

                return $service;
            });

        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    // STORE SERVICE
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',

            // NEW FIELD
            'type' => 'required|in:home,service_page',

            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'nullable'
        ]);

        $data = $request->except('image');

        // IMAGE UPLOAD
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('services', 'public');
            $data['image'] = $path;
        }

        // ACTIVE STATUS
        $data['is_active'] = $request->has('is_active')
            && filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        $service = Service::create($data);

        // IMAGE URL
        $service->image_url = $service->image
            ? asset('storage/' . $service->image)
            : null;

        return response()->json([
            'success' => true,
            'data' => $service,
            'message' => 'Service created successfully.'
        ]);
    }

    // UPDATE SERVICE
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',

            // NEW FIELD
            'type' => 'required|in:home,service_page',

            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'nullable'
        ]);

        $data = $request->except('image');

        // UPDATE IMAGE
        if ($request->hasFile('image')) {

            // DELETE OLD IMAGE
            if (
                $service->image &&
                Storage::disk('public')->exists($service->image)
            ) {
                Storage::disk('public')->delete($service->image);
            }

            $path = $request->file('image')->store('services', 'public');

            $data['image'] = $path;
        }

        // UPDATE ACTIVE STATUS
        if ($request->has('is_active')) {
            $data['is_active'] = filter_var(
                $request->is_active,
                FILTER_VALIDATE_BOOLEAN
            );
        }

        $service->update($data);

        // IMAGE URL
        $service->image_url = $service->image
            ? asset('storage/' . $service->image)
            : null;

        return response()->json([
            'success' => true,
            'data' => $service,
            'message' => 'Service updated successfully.'
        ]);
    }

    // DELETE SERVICE
    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        // DELETE IMAGE
        if (
            $service->image &&
            Storage::disk('public')->exists($service->image)
        ) {
            Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully.'
        ]);
    }
}