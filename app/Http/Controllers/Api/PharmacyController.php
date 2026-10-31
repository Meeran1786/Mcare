<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use Illuminate\Http\Request;
use App\Support\PrivateFiles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PharmacyController extends Controller
{

    /**
     * Ensure storage directory exists
     */
    private function ensureDirectory($path)
    {
        if (!Storage::disk('public')->exists($path)) {
            Storage::disk('public')->makeDirectory($path);
        }
    }

    
    private function resolveAuthenticatedUser(Request $request)
    {
        $user = $request->user();

        if ($user) {
            return $user;
        }

        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return null;
        }

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);

        return $accessToken?->tokenable;
    }
/**
     * Get vendor pharmacy (used on app start)
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'status_code' => 403,
                'message' => 'You are not allowed to access pharmacy details.'
            ], 403);
        }

        $pharmacy = Pharmacy::where('user_id', $user->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => true,
                'status_code' => 200,
                'message' => 'Pharmacy not created yet.',
                'data' => ['exists' => false]
            ], 200);
        }

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'Pharmacy details fetched successfully.',
            'data' => [
                'exists' => true,
                'pharmacy' => [
                    'id' => $pharmacy->id,
                    'pharmacy_name' => $pharmacy->pharmacy_name,
                    'verification_status' => $pharmacy->verification_status,
                    'is_verified' => $pharmacy->is_verified,
                    'is_delivery_available' => $pharmacy->is_delivery_available,
                ]
            ]
        ]);
    }

    /**
     * Store pharmacy basic details
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'status_code' => 403,
                'message' => 'Only vendors can create pharmacy profiles.'
            ], 403);
        }

        if (Pharmacy::where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'status_code' => 409,
                'message' => 'Pharmacy already exists for this vendor.'
            ], 409);
        }

        $data = $request->validate([
            'owner_name' => 'required|string|max:255',
            'pharmacy_name' => 'required|string|max:255',

            'license_number' => 'required|string|max:100',
            'registration_number' => 'required|string|max:100',

            'gst_number' => 'nullable|string|max:100',

            'address_line' => 'required|string|max:255',
            'area' => 'required|string|max:100',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',

            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',

            //'opening_time' => 'nullable',
            //'closing_time' => 'nullable',
            //'is_delivery_available' => 'nullable|boolean',

            'pharmacy_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048'
        ]);

        $data['user_id'] = $user->id;
        $data['is_verified'] = false;
        $data['verification_status'] = 'pending';

        /**
         * Upload pharmacy photo
         */
        if ($request->hasFile('pharmacy_photo')) {

            $this->ensureDirectory('pharmacies/photos');

            $extension = $request->file('pharmacy_photo')->getClientOriginalExtension();

            $filename = $user->id . '-' . Str::slug($data['pharmacy_name']) . '.' . $extension;

            $data['pharmacy_photo'] = $request->file('pharmacy_photo')
                ->storeAs('pharmacies/photos', $filename, 'public');
        }

        $pharmacy = Pharmacy::create($data);

        return response()->json([
            'success' => true,
            'status_code' => 201,
            'message' => 'Pharmacy details saved successfully. Please upload documents for verification.',
            'data' => ['pharmacy_id' => $pharmacy->id]
        ], 201);
    }

    /**
     * Upload pharmacy documents
     */
    public function uploadDocuments(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'status_code' => 403,
                'message' => 'Only vendors can upload pharmacy documents.'
            ], 403);
        }

        $pharmacy = Pharmacy::where('user_id', $user->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'status_code' => 404,
                'message' => 'Pharmacy profile not found.'
            ], 404);
        }

        if ($pharmacy->verification_status === 'approved') {
            return response()->json([
                'success' => false,
                'status_code' => 403,
                'message' => 'Approved pharmacy documents cannot be changed.'
            ], 403);
        }

        $request->validate([
            'license_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'gst_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        /**
         * Upload License File
         */
        if ($request->hasFile('license_file')) {

            PrivateFiles::delete($pharmacy->license_file);

            $ext = $request->file('license_file')->getClientOriginalExtension();

            $filename = $user->id . '-' . Str::slug($pharmacy->pharmacy_name) . '-license.' . $ext;

            $pharmacy->license_file = $request->file('license_file')
                ->storeAs('pharmacies/licenses', $filename, PrivateFiles::DISK);
        }

        /**
         * Upload GST File
         */
        if ($request->hasFile('gst_file')) {

            PrivateFiles::delete($pharmacy->gst_file);

            $ext = $request->file('gst_file')->getClientOriginalExtension();

            $filename = $user->id . '-' . Str::slug($pharmacy->pharmacy_name) . '-gst.' . $ext;

            $pharmacy->gst_file = $request->file('gst_file')
                ->storeAs('pharmacies/gst', $filename, PrivateFiles::DISK);
        }

        $pharmacy->verification_status = 'pending';
        $pharmacy->is_verified = false;
        $pharmacy->save();

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'Documents uploaded successfully. Verification is in progress.',
            'data' => [
                'verification_status' => $pharmacy->verification_status
            ]
        ]);
    }

    /**
     * Get full pharmacy details
     */
    public function details(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'status_code' => 403,
                'message' => 'Access denied.'
            ], 403);
        }

        $pharmacy = Pharmacy::with('user')->where('user_id', $user->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'status_code' => 404,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'Pharmacy full details fetched successfully.',
            'data' => [
                'pharmacy' => [

                    'id' => $pharmacy->id,
                    'owner_name' => $pharmacy->owner_name,
                    'pharmacy_name' => $pharmacy->pharmacy_name,

                    'license_number' => $pharmacy->license_number,
                    'registration_number' => $pharmacy->registration_number,
                    'gst_number' => $pharmacy->gst_number,

                    'address_line' => $pharmacy->address_line,
                    'area' => $pharmacy->area,
                    'city' => $pharmacy->city,
                    'state' => $pharmacy->state,
                    'pincode' => $pharmacy->pincode,
                    'landmark' => $pharmacy->landmark,

                    'latitude' => $pharmacy->latitude,
                    'longitude' => $pharmacy->longitude,

                    'opening_time' => $pharmacy->opening_time,
                    'closing_time' => $pharmacy->closing_time,
                    'is_delivery_available' => $pharmacy->is_delivery_available,

                    'pharmacy_photo_url' => $pharmacy->pharmacy_photo
                        ? Storage::disk('public')->url($pharmacy->pharmacy_photo)
                        : null,

                    'license_file_url' => PrivateFiles::url($pharmacy->license_file),

                    'gst_file_url' => PrivateFiles::url($pharmacy->gst_file),

                    'verification_status' => $pharmacy->verification_status,
                    'is_verified' => $pharmacy->is_verified,
                ],

                'user' => [
                    'name' => $pharmacy->user->name ?? null,
                    'email' => $pharmacy->user->email ?? null,
                    'mobile_number' => $pharmacy->user->mobile_number ?? null,
                ]
            ]
        ]);
    }

    /**
     * Update pharmacy
     */
    public function update(Request $request)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login again.'
            ], 401);
        }

        $pharmacy = Pharmacy::where('user_id', $user->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'status_code' => 404,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'mobile_number' => ['required', 'string', 'max:20', Rule::unique('users', 'mobile_number')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'pharmacy_name' => 'required|string|max:255',
            'license_number' => 'required|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'gst_number' => 'nullable|string|max:100',
            'address_line' => 'required|string|max:255',
            'area' => 'required|string|max:100',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|max:10',
            'is_delivery_available' => 'nullable|boolean',
            'pharmacy_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'license_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user->update([
            'name' => $data['name'],
            'mobile_number' => $data['mobile_number'],
            'email' => $data['email'] ?? null,
        ]);

        $pharmacyData = collect($data)->only([
            'pharmacy_name',
            'license_number',
            'registration_number',
            'gst_number',
            'address_line',
            'area',
            'landmark',
            'city',
            'state',
            'pincode',
            'is_delivery_available',
        ])->toArray();

        $pharmacyData['owner_name'] = $data['name'];

        if ($request->hasFile('pharmacy_photo')) {
            $this->ensureDirectory('pharmacies/photos');

            if ($pharmacy->pharmacy_photo && Storage::disk('public')->exists($pharmacy->pharmacy_photo)) {
                Storage::disk('public')->delete($pharmacy->pharmacy_photo);
            }

            $pharmacyData['pharmacy_photo'] = $request->file('pharmacy_photo')->store('pharmacies/photos', 'public');
        }

        if ($request->hasFile('license_file')) {
            PrivateFiles::delete($pharmacy->license_file);

            $pharmacyData['license_file'] = $request->file('license_file')->store('pharmacies/licenses', PrivateFiles::DISK);
            $pharmacyData['verification_status'] = 'pending';
            $pharmacyData['is_verified'] = false;
        }

        $pharmacy->update($pharmacyData);

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'Pharmacy details updated successfully.',
            'data' => [
                'pharmacy' => $pharmacy->fresh(),
                'user' => $user->fresh(),
            ]
        ]);
    }

    public function toggleStatus(Request $request)
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login again.'
            ], 401);
        }

        if ($user->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Only vendors can update receive orders status.'
            ], 403);
        }

        $pharmacy = Pharmacy::where('user_id', $user->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $pharmacy->update([
            'is_delivery_available' => (bool) $data['is_active'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Receive orders status updated successfully.',
            'data' => [
                'is_active' => $pharmacy->is_delivery_available,
            ]
        ]);
    }

    public function updatePhoto(Request $request)
    {
        $user = $request->user();

        $pharmacy = Pharmacy::where('user_id', $user->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        $request->validate([
            'pharmacy_photo' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // delete old
        if ($pharmacy->pharmacy_photo && Storage::disk('public')->exists($pharmacy->pharmacy_photo)) {
            Storage::disk('public')->delete($pharmacy->pharmacy_photo);
        }

        $path = $request->file('pharmacy_photo')
            ->store('pharmacies/photos', 'public');

        $pharmacy->update([
            'pharmacy_photo' => $path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Photo updated successfully'
        ]);
    }
    public function getProfile(Request $request)
    {
        $user = $request->user();

        $pharmacy = Pharmacy::where('user_id', $user->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'status' => false,
                'message' => 'Pharmacy not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'name' => $pharmacy->pharmacy_name,
                'location' => $pharmacy->area . ', ' . $pharmacy->city,
            ]
        ]);
    }
}