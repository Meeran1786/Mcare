<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class ManageAppVersionController extends Controller
{
    public function index()
    {
        $settings = AppSetting::firstOrCreate([]);

        return view('admin.app-versions.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'user_play_store' => 'nullable|url',
            'user_app_store' => 'nullable|url',
            'user_latest_version' => ['nullable', 'regex:/^\d+(\.\d+){0,3}$/'],
            'user_minimum_version' => ['nullable', 'regex:/^\d+(\.\d+){0,3}$/'],
            'user_force_update' => 'nullable|boolean',
            'vendor_play_store' => 'nullable|url',
            'vendor_app_store' => 'nullable|url',
            'vendor_latest_version' => ['nullable', 'regex:/^\d+(\.\d+){0,3}$/'],
            'vendor_minimum_version' => ['nullable', 'regex:/^\d+(\.\d+){0,3}$/'],
            'vendor_force_update' => 'nullable|boolean',
        ], [
            '*.regex' => 'Version must be like 1.0.0 or 1.0.0.1.',
        ]);

        $settings = AppSetting::firstOrCreate([]);

        $settings->update([
            'user_play_store' => $validated['user_play_store'] ?? null,
            'user_app_store' => $validated['user_app_store'] ?? null,
            'user_latest_version' => $validated['user_latest_version'] ?? null,
            'user_minimum_version' => $validated['user_minimum_version'] ?? null,
            'user_force_update' => $request->boolean('user_force_update'),
            'vendor_play_store' => $validated['vendor_play_store'] ?? null,
            'vendor_app_store' => $validated['vendor_app_store'] ?? null,
            'vendor_latest_version' => $validated['vendor_latest_version'] ?? null,
            'vendor_minimum_version' => $validated['vendor_minimum_version'] ?? null,
            'vendor_force_update' => $request->boolean('vendor_force_update'),
        ]);

        return back()->with('success', 'App version settings updated successfully.');
    }
}
