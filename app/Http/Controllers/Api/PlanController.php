<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlanService;
use Illuminate\Http\Request;

/**
 * The signed-in account's plan, for the apps. Prices and buy links are left
 * out on purpose: plans are sold only on the website.
 */
class PlanController extends Controller
{
    public function show(Request $request, PlanService $plans)
    {
        $user = $request->user();
        $plans->startTrialIfNew($user);

        return response()->json(['status' => true, 'data' => $plans->status($user)]);
    }
}
