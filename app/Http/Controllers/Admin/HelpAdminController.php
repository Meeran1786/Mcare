<?php

// app/Http/Controllers/Admin/HelpAdminController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpRequest;

class HelpAdminController extends Controller
{
    public function index()
    {
        $requests = HelpRequest::with('user')->latest()->get();

        return view('admin.help.index', compact('requests'));
    }
}