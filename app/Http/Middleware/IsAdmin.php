<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not logged in
        if (!$user) {
            return response()->json([
                'success' => false,
                'status_code' => 401,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Not admin
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'status_code' => 403,
                'message' => 'You are not authorized.'
            ], 403);
        }

        return $next($request);
    }
}
