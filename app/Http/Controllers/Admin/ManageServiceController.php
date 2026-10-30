<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;

class ManageServiceController extends Controller
{
    // LIST SERVICES
    public function index()
    {
        $services = Service::orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.services.index', compact('services'));
    }

    // STORE SERVICE
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',

            // NEW FIELD
            'type' => 'required|in:home,service_page',

            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
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
            ? true
            : false;

        Service::create($data);

        return back()->with(
            'success',
            'Service created successfully.'
        );
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

            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
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

        // ACTIVE STATUS
        $data['is_active'] = $request->has('is_active')
            ? true
            : false;

        $service->update($data);

        return back()->with(
            'success',
            'Service updated successfully.'
        );
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

        return back()->with(
            'success',
            'Service deleted successfully.'
        );
    }
}