<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoseLog;
use App\Models\Reminder;
use Illuminate\Http\Request;

/**
 * Dose history: the patient marks a reminder's dose as taken or skipped.
 * Missed doses are written by the doses:check-missed command.
 */
class DoseController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
        ]);

        $from = $data['from'] ?? now()->subDays(6)->format('Y-m-d');
        $to = $data['to'] ?? now()->addDay()->format('Y-m-d');

        $logs = DoseLog::where('user_id', $request->user()->id)
            ->where('scheduled_for', '>=', $from . ' 00:00')
            ->where('scheduled_for', '<=', $to . ' 23:59')
            ->orderBy('scheduled_for')
            ->get(['id', 'reminder_id', 'scheduled_for', 'status', 'taken_at']);

        return response()->json(['status' => true, 'data' => $logs]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reminder_id' => 'required|integer',
            'scheduled_for' => 'required|date_format:Y-m-d H:i',
            'status' => 'required|in:taken,skipped',
        ]);

        $reminder = Reminder::where('id', $data['reminder_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$reminder) {
            return response()->json(['status' => false, 'message' => 'Reminder not found'], 404);
        }

        $log = DoseLog::updateOrCreate(
            ['reminder_id' => $reminder->id, 'scheduled_for' => $data['scheduled_for']],
            [
                'user_id' => $reminder->user_id,
                'status' => $data['status'],
                'taken_at' => $data['status'] === 'taken' ? now() : null,
            ]
        );

        return response()->json([
            'status' => true,
            'data' => $log->only(['id', 'reminder_id', 'scheduled_for', 'status', 'taken_at']),
        ]);
    }
}
