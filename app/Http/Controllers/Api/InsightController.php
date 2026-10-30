<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pharmacy;
use App\Models\Order;
use App\Models\Prescription;

class InsightController extends Controller
{
    public function index()
    {
        return response()->json([
            'users' => User::where('role','user')->count(),
            'vendors' => User::where('role','vendor')->count(),
            'completed_orders' => Order::where('status','completed')->count(),
            'total_orders' => Order::count(),
        ]);
    }
}
