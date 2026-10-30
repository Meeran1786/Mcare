<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppSetting;

class ManageAppSettingController extends Controller
{
    public function index()
    {
        $settings = AppSetting::first();

        // Create empty row if not exists
        if (!$settings) {

            $settings = AppSetting::create([]);

        }

        return view(
            'admin.app-settings.index',
            compact('settings')
        );
    }

    public function update(Request $request)
    {
        $request->validate([

            'user_play_store' => 'nullable|url',

            'user_app_store' => 'nullable|url',

            'user_latest_version' => ['nullable', 'regex:/^\d+(\.\d+){0,3}$/'],

            'user_force_update' => 'nullable|boolean',

            'vendor_play_store' => 'nullable|url',

            'vendor_app_store' => 'nullable|url',

            'vendor_latest_version' => ['nullable', 'regex:/^\d+(\.\d+){0,3}$/'],

            'vendor_force_update' => 'nullable|boolean',

        ]);

        $settings = AppSetting::first();

        // Create row if missing
        if (!$settings) {

            $settings = AppSetting::create([]);

        }

        $settings->update([

            'user_play_store' =>
                $request->user_play_store,

            'user_app_store'  =>
                $request->user_app_store,

            'user_latest_version' =>
                $request->user_latest_version,

            'user_force_update' =>
                $request->boolean('user_force_update'),

            'vendor_play_store' =>
                $request->vendor_play_store,

            'vendor_app_store'  =>
                $request->vendor_app_store,

            'vendor_latest_version' =>
                $request->vendor_latest_version,

            'vendor_force_update' =>
                $request->boolean('vendor_force_update'),

        ]);

        return back()->with(
            'success',
            'App settings updated successfully.'
        );
    }
}
