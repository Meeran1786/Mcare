<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionPharmacy;
use App\Models\PrescriptionFile;
use App\Models\Pharmacy;
use App\Models\Order;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PrescriptionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | USER: Upload Prescription
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
{
    $user = auth()->user();

    if ($user->role !== 'user') {
        return response()->json([
            'success' => false,
            'message' => 'Only users can upload prescriptions.'
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'files' => 'required|array|min:1',
        'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        'mode' => 'required|in:nearby,single',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'pharmacy_id' => 'required_if:mode,single|exists:pharmacies,id',
        'address_id' => 'nullable|exists:addresses,id',
        'notes' => 'nullable|string|max:500'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors()
        ], 422);
    }

    DB::beginTransaction();

    try {


        // âœ… Create prescription
        $prescription = Prescription::create([
            'user_id' => $user->id,
            'address_id' => $request->address_id,
            'latitude' => $request->latitude ?? null,
            'longitude' => $request->longitude ?? null,
            'address_id' => $request->address_id,
            'notes' => $request->notes,
            'status' => 'sent'
        ]);

        // âœ… Save files
        foreach ($request->file('files') as $file) {
            $path = $file->store(PrescriptionFile::DIRECTORY, PrescriptionFile::DISK);

            PrescriptionFile::create([
                'prescription_id' => $prescription->id,
                'file_path' => $path
            ]);
        }

        $firebase = app(\App\Services\FirebaseService::class);
        $assigned = 0;

        /*
        ================= SINGLE PHARMACY =================
        */
        if ($request->mode === 'single') {

            $pharmacy = Pharmacy::find($request->pharmacy_id);

            if (!$pharmacy) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy not found'
                ], 404);
            }

            if (!$pharmacy->is_verified || !$pharmacy->is_delivery_available) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This pharmacy is currently offline and not accepting new orders.'
                ], 422);
            }

            $distance = $this->distanceKm(
                $request->latitude,
                $request->longitude,
                $pharmacy->latitude,
                $pharmacy->longitude
            );

            if ($distance === null || $distance > 5) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This pharmacy is currently outside the delivery service area for your selected address.',
                    'distance_km' => $distance ? round($distance, 2) : null
                ], 422);
            }

            PrescriptionPharmacy::create([
                'prescription_id' => $prescription->id,
                'pharmacy_id' => $pharmacy->id,
                'status' => 'sent'
            ]);

            // ðŸ”” DB Notification
            Notification::create([
                'user_id' => $pharmacy->user_id,
                'title' => 'New Prescription Received',
                'body' => 'A new prescription has been assigned.',
                'type' => 'prescription',
                'reference_id' => $prescription->id,
            ]);

            // ðŸ”” PUSH â†’ PHARMACY
            $pharmacyUser = \App\Models\User::find($pharmacy->user_id);

            if ($pharmacyUser && $pharmacyUser->fcm_token) {
                $firebase->sendNotification(
                    $pharmacyUser->fcm_token,
                    "New Prescription ðŸ“©",
                    "You received a new prescription request",
                    [
                        "type" => "NEW_PRESCRIPTION",
                        "prescription_id" => (string)$prescription->id,
                        "channel_id" => "admin_channel_v2",
                        "sound" => "app_push_sound"
                    ]
                );
                            }

            $assigned = 1;
        }

        /*
        ================= NEARBY PHARMACIES =================
        */
        elseif ($request->mode === 'nearby') {

            $pharmacies = Pharmacy::where('is_verified', true)
                ->where('is_delivery_available', true)
                ->selectRaw("id, user_id, (6371 * acos(LEAST(1, GREATEST(-1,
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )))) AS distance", [
                    $request->latitude,
                    $request->longitude,
                    $request->latitude
                ])
                ->having('distance', '<=', 5)
                ->orderBy('distance')
                ->get();

            if ($pharmacies->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No nearby pharmacies found.'
                ], 404);
            }

            foreach ($pharmacies as $pharmacy) {

                PrescriptionPharmacy::create([
                    'prescription_id' => $prescription->id,
                    'pharmacy_id' => $pharmacy->id,
                    'status' => 'sent'
                ]);

                // ðŸ”” DB Notification
                Notification::create([
                    'user_id' => $pharmacy->user_id,
                    'title' => 'New Prescription Received',
                    'body' => 'A new prescription has been assigned.',
                    'type' => 'prescription',
                    'reference_id' => $prescription->id,
                ]);

                // ðŸ”” PUSH â†’ PHARMACY
                $pharmacyUser = \App\Models\User::find($pharmacy->user_id);

                if ($pharmacyUser && $pharmacyUser->fcm_token) {

                    $firebase->sendNotification(
                    $pharmacyUser->fcm_token,
                    "New Prescription ðŸ“©",
                    "You received a new prescription request",
                    [
                        "type" => "NEW_PRESCRIPTION",
                        "prescription_id" => (string)$prescription->id,
                        "channel_id" => "admin_channel_v2",
                        "sound" => "app_push_sound"
                    ]
                );
                }

                $assigned++;
            }
        }

        // Push to user. Notification failures are logged by FirebaseService and must not fail the order.
        if ($user->fcm_token) {
            $firebase->sendNotification(
                $user->fcm_token,
                "Prescription Uploaded",
                "Your prescription has been sent successfully",
                [
                    "type" => "PRESCRIPTION_UPLOADED",
                    "prescription_id" => (string)$prescription->id,
                    "channel_id" => "app_channel_v2",
                    "sound" => "app_push_sound"
                ]
            );
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Prescription uploaded successfully.',
            'prescription_id' => $prescription->id,
            'assigned_pharmacies' => $assigned
        ], 201);

    } catch (\Exception $e) {

        DB::rollBack();

        \Log::error("UPLOAD ERROR:", [
            'message' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while uploading prescription.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /*
    |--------------------------------------------------------------------------
    | VENDOR: Accept / Reject Prescription
    |--------------------------------------------------------------------------
    */

    public function updateStatus(Request $request, $id)
    {
        if ($request->user()->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Only vendors can update prescription status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,rejected',
            'rejection_reason' => 'required_if:status,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $pharmacy = Pharmacy::where('user_id', $request->user()->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        if ($request->status === 'accepted' && !$pharmacy->is_delivery_available) {
            return response()->json([
                'success' => false,
                'message' => 'You are offline. Please go online before accepting new prescriptions.'
            ], 422);
        }

        $assignment = PrescriptionPharmacy::with('prescription')
            ->where('id', $id)
            ->where('pharmacy_id', $pharmacy->id)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Prescription not found or not assigned to your pharmacy.'
            ], 404);
        }

        if (in_array($assignment->prescription->status, ['completed', 'delivered', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Prescription already completed.'
            ], 400);
        }

        DB::beginTransaction();

        try {
            // âœ… Update current pharmacy status
            $assignment->update([
                'status' => $request->status,
                'rejection_reason' => $request->status === 'rejected'
                    ? $request->rejection_reason
                    : null
            ]);

            /*
            |--------------------------------------------------------------------------
            | ACCEPT FLOW
            |--------------------------------------------------------------------------
            */

            if ($request->status === 'accepted') {

                // âœ… Prevent duplicate order
                $existingOrder = Order::where('prescription_id', $assignment->prescription_id)->first();

                if ($existingOrder) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Order already created for this prescription.'
                    ], 400);
                }

                // Reject all other pharmacies
                PrescriptionPharmacy::where('prescription_id', $assignment->prescription_id)
                    ->where('id', '!=', $assignment->id)
                    ->update([
                        'status' => 'rejected',
                        'rejection_reason' => 'Accepted by another pharmacy'
                    ]);

                // Mark prescription accepted (NOT completed)
                $assignment->prescription->update([
                    'status' => 'accepted'
                ]);

                /*
                |--------------------------------------------------------------------------
                | GET USER DEFAULT ADDRESS
                |--------------------------------------------------------------------------
                */

                $addressId = $assignment->prescription->address_id;
                    

                /*
                |--------------------------------------------------------------------------
                | CREATE ORDER
                |--------------------------------------------------------------------------
                */

                $order = Order::create([
                    'user_id' => $assignment->prescription->user_id,
                    'pharmacy_id' => $pharmacy->id,
                    'prescription_id' => $assignment->prescription_id,
                    'address_id' => $addressId, // âœ… IMPORTANT
                    'delivery_lat' => $assignment->prescription->latitude,
                    'delivery_lng' => $assignment->prescription->longitude,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'payment_method' => 'cod'
                ]);

                // ðŸ”” Notify user
                Notification::create([
                    'user_id' => $assignment->prescription->user_id,
                    'title' => 'Prescription Accepted',
                    'body' => 'Your prescription has been accepted. Order #' . $order->id . ' created.',
                    'type' => 'order',
                    'reference_id' => $order->id,
                ]);
                $firebase = app(\App\Services\FirebaseService::class);

                $user = \App\Models\User::find($assignment->prescription->user_id);

                if ($user && $user->fcm_token) {
                   $firebase->sendNotification(
                        $user->fcm_token,
                        "Prescription Accepted ðŸŽ‰",
                        "Your prescription has been accepted. Order #" . $order->id . " created.",
                        [
                            "type" => "PRESCRIPTION_ACCEPTED",
                            "prescription_id" => (string)$assignment->prescription_id,
                            "order_id" => (string)$order->id,
                            "channel_id" => "app_channel_v2",
                            "sound" => "app_push_sound"
                        ]
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | REJECT FLOW
            |--------------------------------------------------------------------------
            */

            if ($request->status === 'rejected') {
                $hasOpenAssignments = PrescriptionPharmacy::where('prescription_id', $assignment->prescription_id)
                    ->where('status', '!=', 'rejected')
                    ->exists();

                if (!$hasOpenAssignments) {
                    $assignment->prescription->update([
                        'status' => 'rejected'
                    ]);
                }

                Notification::create([
                    'user_id' => $assignment->prescription->user_id,
                    'title' => 'Prescription Rejected',
                    'body' => 'A pharmacy rejected your prescription.',
                    'type' => 'prescription',
                    'reference_id' => $assignment->prescription_id,
                ]);

                $firebase = app(\App\Services\FirebaseService::class);

                $user = \App\Models\User::find($assignment->prescription->user_id);

                if ($user && $user->fcm_token) {
                    $firebase->sendNotification(
                        $user->fcm_token,
                        "Prescription Rejected âŒ",
                        "A pharmacy rejected your prescription",
                        [
                            "type" => "PRESCRIPTION_REJECTED",
                            "prescription_id" => (string)$assignment->prescription_id,
                            "channel_id" => "app_channel_v2",
                            "sound" => "app_push_sound"
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Prescription status updated successfully.'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update prescription status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function vendorInbox(Request $request)
    {
        if ($request->user()->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Only vendors can access prescriptions.'
            ], 403);
        }

        $pharmacy = Pharmacy::where('user_id', $request->user()->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        $assignments = PrescriptionPharmacy::with([
                'prescription',
                'prescription.order',
                'prescription.address',
                'prescription.user',
                'prescription.user.address',
                'prescription.files',
                'prescription.order.address'
            ])
        ->where('pharmacy_id', $pharmacy->id)
        ->whereIn('status', $pharmacy->is_delivery_available ? ['sent', 'accepted'] : ['accepted'])
        ->whereHas('prescription', function ($query) {

                // âœ… ACTIVE PRESCRIPTIONS ONLY
                $query->whereIn('status', [
                    'sent',
                    'accepted'
                ]);

                // âœ… HIDE CLOSED ORDERS
                $query->whereDoesntHave('order', function ($orderQuery) {

                    $orderQuery->whereIn('status', [
                        'delivered',
                        'completed',
                        'cancelled',
                        'rejected'
                    ]);

                });

            })
        ->latest()
        ->get();

        $data = [];

        foreach ($assignments as $item) {

            $prescription = $item->prescription;

            $distance = null;

            $deliveryAddress = $prescription->address
                ?: optional($prescription->order)->address
                ?: optional($prescription->user)->address;

            $lat2 = $deliveryAddress->latitude ?? $prescription->latitude;
            $lng2 = $deliveryAddress->longitude ?? $prescription->longitude;

            $distance = $this->distanceKm($pharmacy->latitude, $pharmacy->longitude, $lat2, $lng2);

            // âœ… ADD DISTANCE TO ITEM
            $item->distance_km = $distance ? round($distance, 2) : null;

            $data[] = $item;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    public function updatePrescription(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Only users can update prescriptions.'
            ], 403);
        }

        $request->validate([
            'prescription_id' => 'nullable|integer',
            'order_id' => 'nullable|integer',
            'prescription' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // 🔍 Identify prescription. Only the patient who owns it may replace the image.
        if ($request->prescription_id) {
            $prescription = Prescription::with('files')
                ->where('id', $request->prescription_id)
                ->where('user_id', $user->id)
                ->first();
        } elseif ($request->order_id) {
            $order = Order::with('prescription.files')
                ->where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->first();
            $prescription = $order->prescription ?? null;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Prescription or Order required'
            ], 422);
        }

        if (!$prescription || (int) $prescription->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Prescription not found'
            ], 404);
        }

        // ✅ upload file to private storage
        $file = $request->file('prescription');
        $path = $file->store(PrescriptionFile::DIRECTORY, PrescriptionFile::DISK);

        $existingFile = $prescription->files->first();

        if ($existingFile) {
            $existingFile->update(['file_path' => $path]);
        } else {
            $existingFile = $prescription->files()->create(['file_path' => $path]);
        }

        return response()->json([
            'success' => true,
            'image' => $existingFile->url
        ]);
    }

    private function distanceKm($lat1, $lng1, $lat2, $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $lat1 = (float) $lat1;
        $lng1 = (float) $lng1;
        $lat2 = (float) $lat2;
        $lng2 = (float) $lng2;

        $cosine = cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            cos(deg2rad($lng2) - deg2rad($lng1)) +
            sin(deg2rad($lat1)) *
            sin(deg2rad($lat2));

        return 6371 * acos(min(1, max(-1, $cosine)));
    }
}

