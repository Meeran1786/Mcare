<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;

class AppSettingController extends Controller
{
    public function index()
    {
        $settings = AppSetting::first();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
}