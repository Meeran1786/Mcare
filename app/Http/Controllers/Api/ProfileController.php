<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Complete user profile (name only)
     */
    public function completeProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255',
        ]);

        $user = Auth::user();

        $user->name = $request->name;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile completed successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'mobile_number' => $user->mobile_number,
                'role' => $user->role,
            ],
        ]);
    }

        public function setupProfile(Request $request)
        {
            try {

                \Log::info('PROFILE UPDATE REQUEST', $request->all());

                $request->validate([
                    'name' => 'nullable|string|min:2|max:255',
                    'email' => 'nullable|email|max:255',
                    'dob' => 'nullable|string',
                    'gender' => 'nullable|string|max:20',
                    'preferred_language' => 'nullable|string',
                ]);

                $user = $request->user();

                // UPDATE NAME
                if ($request->filled('name')) {
                    $user->name = $request->name;
                }

                // UPDATE EMAIL
                if ($request->filled('email')) {
                    $user->email = $request->email;
                }

                // UPDATE GENDER
                if ($request->filled('gender')) {
                    $user->gender = $request->gender;
                }

                // UPDATE LANGUAGE
                if ($request->filled('preferred_language')) {
                    $user->preferred_language = $request->preferred_language;
                }

                // UPDATE DOB
                if ($request->filled('dob')) {

                    try {

                        // FRONTEND FORMAT => 22/05/2026
                        $dob = Carbon::createFromFormat('d/m/Y', $request->dob);

                        // SAVE AS MYSQL FORMAT => 2026-05-22
                        $user->dob = $dob->format('Y-m-d');

                    } catch (\Exception $e) {

                        \Log::error('DOB FORMAT ERROR', [
                            'dob' => $request->dob,
                            'message' => $e->getMessage()
                        ]);
                    }
                }

                // SAVE USER
                $saved = $user->save();

                \Log::info('PROFILE UPDATED', [
                    'saved' => $saved,
                    'user' => $user
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'mobile_number' => $user->mobile_number,
                        'email' => $user->email,
                        'dob' => $user->dob,
                        'gender' => $user->gender,
                        'preferred_language' => $user->preferred_language,
                    ]
                ]);

            } catch (\Exception $e) {

                \Log::error('PROFILE UPDATE ERROR', [
                    'message' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
        }



    /**
     * Get logged-in user profile
     */
    public function getProfile()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'mobile_number' => $user->mobile_number,
                'email' => $user->email,

                // ADD THESE
                'dob' => $user->dob,
                'gender' => $user->gender,

                'role' => $user->role,
                'status' => $user->status,
                'preferred_language' => $user->preferred_language,

                'is_profile_complete' =>
                    !is_null($user->name) &&
                    !is_null($user->preferred_language),
            ],
        ]);
    }
}
