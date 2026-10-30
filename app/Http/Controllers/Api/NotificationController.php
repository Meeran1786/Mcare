<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Prescription;

class NotificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get Notifications
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        $notifications->getCollection()->transform(function ($notification) {
            $type = strtolower((string) $notification->type);
            $order = null;
            $prescription = null;

            if ($type === 'order' && $notification->reference_id) {
                $order = Order::with('prescription')->find($notification->reference_id);
                $prescription = $order?->prescription;
            }

            if ($type === 'prescription' && $notification->reference_id) {
                $prescription = Prescription::find($notification->reference_id);
                $order = Order::where('prescription_id', $notification->reference_id)->first();
            }

            $orderStatus = $order?->status;
            $prescriptionStatus = $prescription?->status;
            $pendingStatuses = ['pending', 'sent', 'new_order'];
            $target = in_array(strtolower((string) ($orderStatus ?: $prescriptionStatus)), $pendingStatuses, true)
                ? 'prescription'
                : ($order ? 'order' : ($prescription ? 'prescription' : null));

            $notification->order_id = $order?->id;
            $notification->prescription_id = $prescription?->id;
            $notification->order_status = $orderStatus;
            $notification->prescription_status = $prescriptionStatus;
            $notification->navigation_target = $target;

            return $notification;
        });

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Notifications fetched successfully.',
            'unread_count' => $unreadCount,
            'data' => $notifications
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Unread Count Only
    |--------------------------------------------------------------------------
    */

    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Single Notification as Read
    |--------------------------------------------------------------------------
    */

    public function markRead(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.'
            ], 404);
        }

        $notification->update([
            'is_read' => true
        ]);

        if ($notification->type === 'admin_push' && $notification->reference_id) {
            Notification::where('id', $notification->reference_id)->increment('opened_count');
            \App\Models\NotificationReceiver::where('notification_id', $notification->reference_id)
                ->where('user_id', $request->user()->id)
                ->whereNull('opened_at')
                ->update(['opened_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Mark All as Read
    |--------------------------------------------------------------------------
    */

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification cleared.'
        ]);
    }
}
