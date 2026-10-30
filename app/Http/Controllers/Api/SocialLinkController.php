<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;

class SocialLinkController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => SocialLink::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(),
        ]);
    }
}
