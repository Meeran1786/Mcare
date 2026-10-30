<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReminderController extends Controller
{
    private function normalizeTimezone(?string $timezone): string
    {
        $timezone = trim((string) $timezone);

        if ($timezone === '' || $timezone === 'Asia/Calcutta') {
            return 'Asia/Kolkata';
        }

        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : 'Asia/Kolkata';
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $reminders = Reminder::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'time' => $item->formatted_time,
                    'raw_time' => $item->time,
                    'days' => $item->days,
                    'days_text' => $item->days_text,
                    'note' => $item->note,
                    'medicine_name' => $item->medicine_name,
                    'timezone' => $item->timezone ?: config('app.timezone'),
                    'is_active' => $item->is_active,
                ];
            });

        $eligibleOrderStatuses = ['delivered', 'completed'];
        $hasOrders = $user->orders()
            ->whereIn('status', $eligibleOrderStatuses)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => $reminders,
            'has_orders' => $hasOrders,
            'eligible_order_statuses' => $eligibleOrderStatuses
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'time' => 'required|date_format:H:i',
            'days' => 'required|array|min:1',
            'days.*' => 'in:Sun,Mon,Tue,Wed,Thu,Fri,Sat',
            'timezone' => 'nullable|string|max:80',
            'note' => 'nullable|string|max:255',
            'medicine_name' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $reminder = Reminder::create([
            'user_id' => $request->user()->id,
            'time' => $request->time,
            'days' => $request->days,
            'timezone' => $this->normalizeTimezone($request->timezone),
            'note' => $request->note,
            'medicine_name' => $request->medicine_name,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reminder created successfully',
            'data' => $reminder
        ]);
    }

    public function update($id, Request $request)
    {
        $reminder = Reminder::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'time' => 'nullable|date_format:H:i',
            'days' => 'nullable|array',
            'days.*' => 'in:Sun,Mon,Tue,Wed,Thu,Fri,Sat',
            'timezone' => 'nullable|string|max:80',
            'note' => 'nullable|string|max:255',
            'medicine_name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $data = $request->only([
            'time',
            'days',
            'note',
            'medicine_name',
            'is_active'
        ]);

        if ($request->has('timezone')) {
            $data['timezone'] = $this->normalizeTimezone($request->timezone);
        }

        $reminder->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Reminder updated successfully',
            'data' => $reminder
        ]);
    }

    public function toggle($id, Request $request)
    {
        $reminder = Reminder::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $reminder->is_active = !$reminder->is_active;
        $reminder->save();

        return response()->json([
            'success' => true,
            'message' => 'Reminder status updated',
            'data' => [
                'id' => $reminder->id,
                'is_active' => $reminder->is_active
            ]
        ]);
    }

    public function destroy($id, Request $request)
    {
        $reminder = Reminder::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $reminder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reminder deleted successfully'
        ]);
    }

    public function show($id, Request $request)
    {
        $reminder = Reminder::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $reminder
        ]);
    }
}
