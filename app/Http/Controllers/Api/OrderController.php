<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Pharmacy;
use App\Models\Prescription;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private array $closedStatuses = ['delivered', 'completed', 'cancelled', 'rejected'];

    private function syncPrescriptionStatus(Order $order, string $status): void
    {
        if (!$order->prescription_id) {
            return;
        }
        $prescriptionStatus = match ($status) {
            'delivered', 'completed' => 'completed',
            'cancelled', 'rejected' => $status,
            default => 'accepted',
        };

        Prescription::where('id', $order->prescription_id)
            ->update(['status' => $prescriptionStatus]);
    }

    private function distanceKm($lat1, $lng1, $lat2, $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $cosine = cos(deg2rad((float) $lat1)) *
            cos(deg2rad((float) $lat2)) *
            cos(deg2rad((float) $lng2) - deg2rad((float) $lng1)) +
            sin(deg2rad((float) $lat1)) *
            sin(deg2rad((float) $lat2));

        return 6371 * acos(min(1, max(-1, $cosine)));
    }


    /*
    |--------------------------------------------------------------------------
    | DISABLED: DIRECT ORDER CREATION
    |--------------------------------------------------------------------------
    */

    // public function store(Request $request)
    // {
    //     try {

    //         $validator = \Validator::make($request->all(), [
    //             'prescription_id' => 'required|exists:prescriptions,id',
    //             'pharmacy_id' => 'required|exists:pharmacies,id',
    //             'delivery_lat' => 'required',
    //             'delivery_lng' => 'required',
    //             'payment_method' => 'required',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         $order = \App\Models\Order::create([
    //             'user_id' => $request->user()->id,
    //             'pharmacy_id' => $request->pharmacy_id,
    //             'prescription_id' => $request->prescription_id,
    //             'delivery_lat' => $request->delivery_lat,   // ✅ ADD THIS
    //             'delivery_lng' => $request->delivery_lng,   // ✅ ADD THIS
    //             'total_amount' => $request->total_amount ?? 0,
    //             'status' => 'pending',
    //             'payment_method' => $request->payment_method,
    //             'payment_status' => 'pending'
    //         ]);

    //         $firebase = app(\App\Services\FirebaseService::class);

    //         // 🔔 notify user
    //         $user = $request->user();

    //         if ($user && $user->fcm_token) {
    //             $firebase->sendNotification(
    //                 $user->fcm_token,
    //                 "Order Placed 🎉",
    //                 "Your order has been placed successfully"
    //             );
    //         }

    //         // 🔔 notify pharmacy (vendor)
    //         $pharmacy = \App\Models\Pharmacy::find($request->pharmacy_id);

    //         if ($pharmacy) {
    //             $vendor = \App\Models\User::find($pharmacy->user_id);

    //             if ($vendor && $vendor->fcm_token) {
    //                 $firebase->sendNotification(
    //                     $vendor->fcm_token,
    //                     "New Order 🛒",
    //                     "You received a new order"
    //                 );
    //             }
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Order placed successfully',
    //             'data' => $order
    //         ]);

    //     } catch (\Exception $e) {

    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage()
    //         ], 500);

    //     }
    // }

    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Direct order creation is disabled. Use prescription approval.'
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | USER: View My Orders
    |--------------------------------------------------------------------------
    */

    public function userOrders(Request $request)
    {
        if ($request->user()->role !== 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Only users can view orders.'
            ], 403);
        }

        $status = $request->query('status');

        $orders = Order::with(['pharmacy', 'prescription.files'])
            ->where('user_id', $request->user()->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Orders fetched successfully.',
            'data' => $orders
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VENDOR: View Pharmacy Orders
    |--------------------------------------------------------------------------
    */

    public function vendorOrders(Request $request)
    {
        if ($request->user()->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Only vendors can view orders.'
            ], 403);
        }

        $pharmacy = Pharmacy::where('user_id', $request->user()->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        $status = $request->query('status');

        $orders = Order::with(['user', 'prescription.files'])
            ->where('pharmacy_id', $pharmacy->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when(!$status, fn ($query) => $query->whereNotIn('status', $this->closedStatuses))
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Orders fetched successfully.',
            'data' => $orders
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER: View Order Details (User or Vendor)
    |--------------------------------------------------------------------------
    */

    public function show(Request $request, $id)
    {
        $distance = null;

        Log::info("ORDER SHOW REQUEST", [
            'order_id' => $id,
            'user_id' => $request->user()->id,
            'user_role' => $request->user()->role
        ]);

        $order = Order::with([
            'address',
            'pharmacy',
            'user',
            'user.address',
            'prescription.address',
            'address',
            'prescription.user.address',
            'prescription.user',
            'prescription.files'
        ])->where('id', $id)->first();

        if (!$order) {
            Log::warning("ORDER NOT FOUND", ['id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | VENDOR ACCESS CHECK
        |--------------------------------------------------------------------------
        */

        if ($request->user()->role === 'vendor') {

            $pharmacy = Pharmacy::where('user_id', $request->user()->id)->first();

            if (!$pharmacy || $order->pharmacy_id !== $pharmacy->id) {

                Log::warning("UNAUTHORIZED ORDER ACCESS", [
                    'vendor_pharmacy_id' => $pharmacy?->id,
                    'order_pharmacy_id' => $order->pharmacy_id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.'
                ], 403);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | USER ACCESS CHECK
        |--------------------------------------------------------------------------
        */

        if ($request->user()->role === 'user') {

            if ($order->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.'
                ], 403);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DISTANCE CALCULATION (User Address → Pharmacy)
        |--------------------------------------------------------------------------
        */

        $lat1 = null;
        $lng1 = null;

        /* 1. order address */
        if ($order->address) {
            $lat1 = $order->address->latitude;
            $lng1 = $order->address->longitude;
        }

        /* 2. user address */
        elseif ($order->user && $order->user->address) {
            $lat1 = $order->user->address->latitude;
            $lng1 = $order->user->address->longitude;
        }

        /* 3. prescription */
        elseif ($order->prescription) {
            $lat1 = $order->prescription->latitude;
            $lng1 = $order->prescription->longitude;
        }

        $lat2 = $order->pharmacy?->latitude;
        $lng2 = $order->pharmacy?->longitude;

        $distance = $this->distanceKm($lat1, $lng1, $lat2, $lng2);

        return response()->json([
            'success' => true,
            'message' => 'Order details fetched.',
            'data' => $order,
            'distance_km' => $distance ? round($distance, 2) : null // ✅ added
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VENDOR: Update Order Status
    |--------------------------------------------------------------------------
    */

    public function updateStatus(Request $request, $id)
    {
        if ($request->user()->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Only vendors can update order status.'
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,accepted,preparing,ready_for_delivery,out_for_delivery,delivered,rejected',
            'amount' => 'nullable|numeric|min:0',
            'delivery_time' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'delivery_clock_time' => 'nullable|date_format:H:i',
            'delivery_note' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | GET PHARMACY
        |--------------------------------------------------------------------------
        */

        $pharmacy = Pharmacy::where('user_id', $request->user()->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | GET ORDER
        |--------------------------------------------------------------------------
        */

        $order = Order::where('id', $id)
            ->where('pharmacy_id', $pharmacy->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or not assigned to your pharmacy.'
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | RESTRICT INVALID STATES
        |--------------------------------------------------------------------------
        */

        if (in_array($order->status, $this->closedStatuses, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be updated.'
            ], 400);
        }

        $hasDeliveryDate = $request->filled('delivery_date') || $order->delivery_date;
        $hasDeliveryTime = $request->filled('delivery_clock_time') || $request->filled('delivery_time') || $order->delivery_clock_time || $order->delivery_time;
        $hasAmount = $request->filled('amount') || $order->total_amount;

        if (in_array($request->status, ['out_for_delivery', 'delivered']) && (!$hasDeliveryDate || !$hasDeliveryTime || !$hasAmount)) {
            return response()->json([
                'success' => false,
                'message' => 'Please update delivery date, delivery time, and final amount before dispatch.',
                'requires_delivery_update' => true,
                'missing_fields' => [
                    'delivery_date' => !$hasDeliveryDate,
                    'delivery_time' => !$hasDeliveryTime,
                    'amount' => !$hasAmount,
                ],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE ORDER
        |--------------------------------------------------------------------------
        */


        $statusChanged = $order->status !== $request->status;
        $deliveryChanged = $request->hasAny(['amount', 'delivery_time', 'delivery_date', 'delivery_clock_time', 'delivery_note']);

        $data = [
            'status' => $request->status
        ];

        // 💰 amount
        if ($request->has('amount')) {
            $data['total_amount'] = $request->amount;
        }

        // 🕒 delivery time
        if ($request->has('delivery_time')) {
            $data['delivery_time'] = $request->delivery_time;
        }

        if ($request->has('delivery_date')) {
            $data['delivery_date'] = $request->delivery_date;
        }

        if ($request->has('delivery_clock_time')) {
            $data['delivery_clock_time'] = $request->delivery_clock_time;
        }

        if ($request->has('delivery_note')) {
            $data['delivery_note'] = $request->delivery_note;
        }

        DB::transaction(function () use ($order, $data, $request) {
            $order->update($data);
            $this->syncPrescriptionStatus($order, $request->status);
        });

        $order->refresh();
        $firebase = app(\App\Services\FirebaseService::class);
        $orderNotify = app(\App\Services\OrderNotificationService::class);

        if ($deliveryChanged && !$statusChanged) {
            $orderNotify->sendDeliveryUpdate($order->user, $order, $firebase);
        } else {
            $orderNotify->send(
                $order->user,
                $order,
                $request->status,
                $firebase
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PAYMENT UPDATE (ONLY AFTER USER CONFIRM DELIVERY)
        |--------------------------------------------------------------------------
        */

        // ⚠️ vendor should NOT mark delivered
        // so do NOT update payment here

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => $order
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | USER: Cancel Order
    |--------------------------------------------------------------------------
    */

    public function cancel(Request $request, $id)
    {
        if ($request->user()->role !== 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Only users can cancel orders.'
            ], 403);
        }

        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        if (in_array($order->status, ['delivered', 'completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Delivered order cannot be cancelled.'
            ], 400);
        }

        $order->update([
            'status' => 'cancelled'
        ]);
        $this->syncPrescriptionStatus($order, 'cancelled');

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.'
        ]);
    }

    public function confirmDelivery(Request $request, $id)
    {
        if ($request->user()->role !== 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Only users can confirm delivery.'
            ], 403);
        }

        $order = Order::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        if (!in_array($order->status, ['out_for_delivery', 'delivered'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order not ready for delivery confirmation.'
            ], 400);
        }

        $order->update([
            'status' => 'completed',
            'payment_status' => 'paid',
            'completed_at' => now()
        ]);
        $this->syncPrescriptionStatus($order, 'completed');

        $order->load(['user', 'pharmacy', 'prescription.files']);

        $firebase = app(\App\Services\FirebaseService::class);
        $orderNotify = app(\App\Services\OrderNotificationService::class);

        $orderNotify->send(
            $order->user,
            $order,
            'completed',
            $firebase
        );

        return response()->json([
            'success' => true,
            'message' => 'Delivery completed successfully.',
            'data' => $order
        ]);
    }
    public function reorder(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::with('prescription.files')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        DB::beginTransaction();

        try {

            // 🔁 Create NEW prescription
            $prescription = \App\Models\Prescription::create([
                'user_id' => $user->id,
                'latitude' => $order->delivery_lat,
                'longitude' => $order->delivery_lng,
                'notes' => $order->prescription->notes,
                'status' => 'sent'
            ]);

            // 📂 Copy files
            foreach ($order->prescription->files as $file) {
                \App\Models\PrescriptionFile::create([
                    'prescription_id' => $prescription->id,
                    'file_path' => $file->file_path
                ]);
            }

            // 🏥 Assign same pharmacy
            $pharmacy = \App\Models\Pharmacy::find($order->pharmacy_id);

            if (!$pharmacy || !$pharmacy->is_verified || !$pharmacy->is_delivery_available) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'This pharmacy is currently offline and not accepting new orders.'
                ], 422);
            }

            \App\Models\PrescriptionPharmacy::create([
                'prescription_id' => $prescription->id,
                'pharmacy_id' => $order->pharmacy_id,
                'status' => 'sent'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Re-order placed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function history(Request $request)
    {
        $user = $request->user();
        $closedStatuses = ['delivered', 'completed', 'cancelled', 'rejected'];

        $query = Order::with(['user', 'pharmacy', 'prescription.files'])
            ->latest();

        if ($user->role === 'vendor') {
            $pharmacy = Pharmacy::where('user_id', $user->id)->first();

            if (!$pharmacy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pharmacy not found.'
                ], 404);
            }

            $query->where('pharmacy_id', $pharmacy->id)
                ->whereIn('status', $closedStatuses);
        } else {
            $query->where('user_id', $user->id);
        }

        $orders = $query
            ->get()
            ->map(function ($order) {
                $file = optional(optional($order->prescription)->files)->first()?->file_path;

                return [
                    'id' => $order->id,
                    'status' => strtolower($order->status),
                    'status_label' => ucwords(str_replace('_', ' ', $order->status)),
                    'created_at' => $order->created_at,
                    'completed_at' => $order->completed_at,
                    'total_amount' => $order->total_amount,
                    'delivery_time' => $order->delivery_time,
                    'delivery_note' => $order->delivery_note,
                    'user' => [
                        'name' => $order->user->name ?? null,
                        'mobile_number' => $order->user->mobile_number ?? null,
                    ],
                    'pharmacy' => [
                        'name' => $order->pharmacy->name ?? $order->pharmacy->pharmacy_name ?? null,
                        'pharmacy_name' => $order->pharmacy->pharmacy_name ?? null,
                        'address_line' => $order->pharmacy->address_line ?? null,
                        'city' => $order->pharmacy->city ?? null,
                    ],
                    'prescription_image' => $file ? asset('storage/' . $file) : null,
                    'prescription' => $order->prescription,
                ];
            });

        $prescriptions = Prescription::with('files')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($prescription) {
                $file = optional($prescription->files)->first()?->file_path;

                return [
                    'id' => $prescription->id,
                    'status' => $prescription->status,
                    'created_at' => $prescription->created_at,
                    'image' => $file ? asset('storage/' . $file) : null,
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'prescriptions' => $prescriptions
        ]);
    }

    public function vendorAnalytics(Request $request)
    {
        if ($request->user()->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Only vendors can view analytics.'
            ], 403);
        }

        $pharmacy = Pharmacy::where('user_id', $request->user()->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        $base = Order::where('pharmacy_id', $pharmacy->id)
            ->where('status', 'completed');

        $daily = (clone $base)
            ->selectRaw('DATE(COALESCE(completed_at, updated_at)) as label, COUNT(*) as total, COALESCE(SUM(total_amount), 0) as revenue')
            ->where('updated_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $weekly = (clone $base)
            ->selectRaw('YEARWEEK(COALESCE(completed_at, updated_at), 1) as label, COUNT(*) as total, COALESCE(SUM(total_amount), 0) as revenue')
            ->where('updated_at', '>=', now()->subWeeks(7)->startOfWeek())
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $monthly = (clone $base)
            ->selectRaw("DATE_FORMAT(COALESCE(completed_at, updated_at), '%Y-%m') as label, COUNT(*) as total, COALESCE(SUM(total_amount), 0) as revenue")
            ->where('updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $yearly = (clone $base)
            ->selectRaw('YEAR(COALESCE(completed_at, updated_at)) as label, COUNT(*) as total, COALESCE(SUM(total_amount), 0) as revenue')
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'completed_deliveries' => (clone $base)->count(),
                    'total_earnings' => (float) (clone $base)->sum('total_amount'),
                ],
                'daily' => $daily,
                'weekly' => $weekly,
                'monthly' => $monthly,
                'yearly' => $yearly,
            ]
        ]);
    }

    public function exportDeliveredPrescriptions(Request $request)
    {
        if ($request->user()->role !== 'vendor') {
            return response()->json([
                'success' => false,
                'message' => 'Only vendors can export delivered prescriptions.'
            ], 403);
        }

        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'format' => 'nullable|in:pdf,excel',
        ]);

        $pharmacy = Pharmacy::where('user_id', $request->user()->id)->first();

        if (!$pharmacy) {
            return response()->json([
                'success' => false,
                'message' => 'Pharmacy not found.'
            ], 404);
        }

        $query = Order::with(['user', 'prescription.files'])
            ->where('pharmacy_id', $pharmacy->id)
            ->whereIn('status', ['delivered', 'completed']);

        if ($request->filled('from_date')) {
            $query->whereDate(DB::raw('COALESCE(completed_at, updated_at)'), '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate(DB::raw('COALESCE(completed_at, updated_at)'), '<=', $request->to_date);
        }

        $rows = $query->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'prescription_count' => $order->prescription?->files?->count() ?? 0,
                    'delivered_date' => optional($order->completed_at ?? $order->updated_at)->format('Y-m-d'),
                    'amount' => (float) ($order->total_amount ?? 0),
                    'delivery_status' => $order->status,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'format' => $request->input('format', 'excel'),
            'summary' => [
                'total_prescriptions_delivered' => $rows->sum('prescription_count'),
                'total_earnings' => $rows->sum('amount'),
                'orders_count' => $rows->count(),
            ],
            'data' => $rows,
        ]);
    }
}
