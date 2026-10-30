<?php

namespace App\Services;

use App\Models\Notification;

class OrderNotificationService
{
    public function sendDeliveryUpdate($user, $order, $firebase)
    {
        if (!$user) {
            return;
        }

        $deliveryTime = $order->delivery_time
            ? $order->delivery_time->format('d M Y, h:i A')
            : trim(($order->delivery_date?->format('d M Y') ?? '') . ' ' . ($order->delivery_clock_time ?? ''));

        $amount = $order->total_amount ? 'Rs ' . number_format((float) $order->total_amount, 2) : 'Amount pending';
        $title = 'Delivery Update';
        $message = "Estimated delivery updated to {$deliveryTime}. Final amount: {$amount}.";

        $extra = [
            'type' => 'DELIVERY_UPDATE',
            'order_id' => (string) $order->id,
            'prescription_id' => (string) $order->prescription_id,
            'channel_id' => 'app_channel_v2',
            'sound' => 'app_push_sound',
            'amount' => (string) ($order->total_amount ?? ''),
            'delivery_time' => (string) ($order->delivery_time ?? ''),
            'delivery_date' => (string) ($order->delivery_date ?? ''),
            'delivery_clock_time' => (string) ($order->delivery_clock_time ?? ''),
            'delivery_note' => (string) ($order->delivery_note ?? ''),
        ];

        if ($user->fcm_token) {
            $firebase->sendNotification($user->fcm_token, $title, $message, $extra);
        }

        Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $message,
            'type' => 'order',
            'reference_id' => $order->id,
            'is_read' => false,
        ]);
    }

    public function send($user, $order, $status, $firebase)
    {
        if (!$user) {
            return;
        }

        $order->loadMissing('pharmacy');

        $pharmacyName = $order->pharmacy->name ?? $order->pharmacy->pharmacy_name ?? 'Your pharmacy';
        $amount = $order->total_amount ? 'Rs ' . number_format((float) $order->total_amount, 2) : 'Amount pending';
        $deliveryTime = $order->delivery_time
            ? $order->delivery_time->format('d M Y, h:i A')
            : trim(($order->delivery_date?->format('d M Y') ?? '') . ' ' . ($order->delivery_clock_time ?? ''));

        if (!$deliveryTime) {
            $deliveryTime = 'time to be confirmed';
        }

        $messages = [
            'pending' => "{$pharmacyName} received your order #{$order->id}.",
            'accepted' => "{$pharmacyName} accepted your order #{$order->id}.",
            'preparing' => "{$pharmacyName} is preparing order #{$order->id}.",
            'ready_for_delivery' => "Order #{$order->id} is ready for delivery.",
            'out_for_delivery' => "{$pharmacyName} is delivering order #{$order->id}. Amount: {$amount}. ETA: {$deliveryTime}.",
            'delivered' => "{$pharmacyName} marked order #{$order->id} as delivered. Please confirm delivery completion.",
            'completed' => "Order #{$order->id} is completed. Thank you.",
            'rejected' => "Order #{$order->id} was rejected.",
            'cancelled' => "Order #{$order->id} was cancelled.",
        ];

        $title = in_array($status, ['out_for_delivery', 'delivered'])
            ? 'Delivery Update'
            : 'Order Update';
        $message = $messages[$status] ?? 'Order updated';

        $extra = [
            'type' => 'ORDER_STATUS',
            'order_id' => (string) $order->id,
            'prescription_id' => (string) $order->prescription_id,
            'status' => (string) $status,
            'channel_id' => 'app_channel_v2',
            'sound' => 'app_push_sound',
            'pharmacy_name' => (string) $pharmacyName,
            'amount' => (string) ($order->total_amount ?? ''),
            'delivery_time' => (string) ($order->delivery_time ?? ''),
            'delivery_date' => (string) ($order->delivery_date ?? ''),
            'delivery_clock_time' => (string) ($order->delivery_clock_time ?? ''),
            'delivery_note' => (string) ($order->delivery_note ?? ''),
        ];

        if ($user->fcm_token) {
            $firebase->sendNotification($user->fcm_token, $title, $message, $extra);
        }

        Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $message,
            'type' => 'order',
            'reference_id' => $order->id,
            'is_read' => false,
        ]);
    }
}
