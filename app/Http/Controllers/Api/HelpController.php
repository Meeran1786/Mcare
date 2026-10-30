<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HelpRequest;

class HelpController extends Controller
{
    // ✅ Submit help request
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $help = HelpRequest::create([
            'user_id' => auth()->id(), // requires auth
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Request submitted successfully',
            'data' => $help
        ]);
    }

    // ✅ Get user requests
    public function index()
    {
        $data = HelpRequest::where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}