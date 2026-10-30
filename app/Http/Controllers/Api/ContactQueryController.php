<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactQuery;
use Illuminate\Support\Facades\Validator;

class ContactQueryController extends Controller
{
    public function index()
    {
        $queries = ContactQuery::orderBy('created_at', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $queries
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'service' => 'nullable|string|max:255',
            'message' => 'required|string',
            'consent' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = ContactQuery::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully.',
            'data' => $query
        ], 201);
    }

    public function markRead($id)
    {
        $query = ContactQuery::find($id);
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Contact query not found'
            ], 404);
        }

        $query->is_read = true;
        $query->save();

        return response()->json([
            'success' => true,
            'message' => 'Marked as read',
            'data' => $query
        ]);
    }

    public function unreadCount()
    {
        $count = ContactQuery::where('is_read', false)->count();
        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count]
        ]);
    }
}
