<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationReceiver;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PushNotificationController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'user')->orderBy('name')->get();
        $vendors = User::where('role', 'vendor')->orderBy('name')->get();
        $baseQuery = Notification::whereNull('user_id');

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'sent' => (clone $baseQuery)->where('status', 'sent')->count(),
            'failed' => (clone $baseQuery)->where('status', 'failed')->count(),
            'drafts' => (clone $baseQuery)->where('status', 'draft')->count(),
        ];

        $notifications = (clone $baseQuery)
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->when(request('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when(request('date'), function ($query, $date) {
                $query->whereDate('created_at', $date);
            })
            ->latest()
            ->withCount('receivers')
            ->paginate(10)
            ->withQueryString();

        $recentRecipients = NotificationReceiver::with('user')
            ->latest()
            ->take(8)
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        return view('admin.notifications.index', compact('users', 'vendors', 'notifications', 'stats', 'recentRecipients'));
    }

    public function store(Request $request, FirebaseService $firebase)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:1000',
            'target_type' => 'required|in:users,vendors,both',
            'send_mode' => 'required|in:single,multiple,bulk',
            'receiver_ids' => 'nullable|array',
            'receiver_ids.*' => 'integer|exists:users,id',
            'scheduled_at' => 'nullable|date',
            'image' => 'nullable|image|max:2048',
            'action' => 'required|in:send,draft',
        ]);

        if (in_array($data['send_mode'], ['single', 'multiple']) && empty($data['receiver_ids'])) {
            return back()->withErrors(['receiver_ids' => 'Please choose at least one recipient.'])->withInput();
        }

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('notifications', 'public')
            : null;

        $scheduledAt = $data['scheduled_at'] ?? null;

        $notification = Notification::create([
            'user_id' => null,
            'title' => $data['title'],
            'body' => $data['body'],
            'image_path' => $imagePath,
            'type' => 'admin_push',
            'target_type' => $data['target_type'],
            'send_mode' => $data['send_mode'],
            'scheduled_at' => $scheduledAt,
            'status' => $data['action'] === 'draft' ? 'draft' : ($scheduledAt ? 'scheduled' : 'sent'),
        ]);

        if ($data['action'] === 'draft' || !empty($scheduledAt)) {
            return back()->with('success', 'Notification saved.');
        }

        [$sent, $failed] = $this->sendToReceivers($notification, $data, $imagePath, $firebase);

        $notification->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $failed > 0 && $sent === 0 ? 'failed' : 'sent',
        ]);

        return back()->with('success', "Notification sent. Sent: {$sent}, Failed: {$failed}");
    }

    public function resend(Notification $notification, FirebaseService $firebase)
    {
        $receiverIds = $notification->receivers()->pluck('user_id')->all();
        $data = [
            'title' => $notification->title,
            'body' => $notification->body,
            'target_type' => $notification->target_type ?: 'users',
            'send_mode' => $notification->send_mode ?: 'bulk',
            'receiver_ids' => $receiverIds,
        ];

        [$sent, $failed] = $this->sendToReceivers($notification, $data, $notification->image_path, $firebase);

        $notification->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $failed > 0 && $sent === 0 ? 'failed' : 'sent',
            'scheduled_at' => null,
        ]);

        return back()->with('success', "Notification resent. Sent: {$sent}, Failed: {$failed}");
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return back()->with('success', 'Notification deleted.');
    }

    private function sendToReceivers(Notification $notification, array $data, ?string $imagePath, FirebaseService $firebase): array
    {
        $receivers = $this->resolveReceivers($data);
        $imageUrl = $imagePath ? Storage::disk('public')->url($imagePath) : null;
        $sent = 0;
        $failed = 0;

        foreach ($receivers as $user) {
            $receiver = NotificationReceiver::create([
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'fcm_token' => $user->fcm_token,
                'status' => 'pending',
            ]);

            Notification::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'image_path' => $imagePath,
                'type' => 'admin_push',
                'reference_id' => $notification->id,
                'is_read' => false,
            ]);

            if (!$user->fcm_token) {
                $failed++;
                $receiver->update(['status' => 'failed', 'error' => 'Missing FCM token']);
                continue;
            }

            $response = $firebase->sendNotification($user->fcm_token, $data['title'], $data['body'], [
                'type' => 'ADMIN_PUSH',
                'notification_id' => (string) $notification->id,
                'image_url' => $imageUrl,
                'image' => $imageUrl,
                'sound' => 'admin_push_sound',
                'channel_id' => $user->role === 'vendor' ? 'admin_channel_v3' : 'admin_channel_v2',
                'click_action' => 'OPEN_MCARE_APP',
            ]);

            if ($response) {
                $sent++;
                $receiver->update(['status' => 'sent', 'sent_at' => now()]);
            } else {
                $failed++;
                $receiver->update(['status' => 'failed', 'error' => 'FCM send failed']);
            }
        }

        return [$sent, $failed];
    }

    private function resolveReceivers(array $data)
    {
        $query = User::query();

        if ($data['target_type'] === 'users') {
            $query->where('role', 'user');
        } elseif ($data['target_type'] === 'vendors') {
            $query->where('role', 'vendor');
        } else {
            $query->whereIn('role', ['user', 'vendor']);
        }

        if (in_array($data['send_mode'], ['single', 'multiple'])) {
            $query->whereIn('id', $data['receiver_ids'] ?? []);
        }

        return $query->get();
    }
}

