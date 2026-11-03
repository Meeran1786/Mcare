<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaregiverLink;
use App\Models\DoseLog;
use App\Models\Reminder;
use App\Models\User;
use App\Services\PlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Family links. A patient creates a 6-digit invite code, a family member
 * enters it, and from then on the family member sees the patient's doses
 * and is alerted when a dose is missed.
 */
class CaregiverController extends Controller
{
    private const INVITE_MINUTES = 30;

    public function index(Request $request)
    {
        $me = $request->user();

        $myCaregivers = CaregiverLink::active()->where('patient_id', $me->id)->with('caregiver')->get()
            ->map(fn ($l) => ['id' => $l->id, 'name' => $l->caregiver->name, 'mobile_number' => $l->caregiver->mobile_number]);

        $iCareFor = CaregiverLink::active()->where('caregiver_id', $me->id)->with('patient')->get()
            ->map(fn ($l) => ['id' => $l->id, 'patient_id' => $l->patient_id, 'name' => $l->patient->name, 'mobile_number' => $l->patient->mobile_number]);

        return response()->json([
            'status' => true,
            'data' => ['my_caregivers' => $myCaregivers->values(), 'i_care_for' => $iCareFor->values()],
        ]);
    }

    public function invite(Request $request)
    {
        $me = $request->user();

        // One open invite per patient: replace any earlier unused one.
        CaregiverLink::where('patient_id', $me->id)->where('status', 'pending')->delete();

        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (CaregiverLink::where('invite_code', $code)->exists());

        $link = CaregiverLink::create([
            'patient_id' => $me->id,
            'invite_code' => $code,
            'invite_expires_at' => now()->addMinutes(self::INVITE_MINUTES),
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'data' => ['code' => $code, 'expires_at' => $link->invite_expires_at->toIso8601String()],
        ]);
    }

    public function accept(Request $request)
    {
        $data = $request->validate(['code' => 'required|digits:6']);
        $me = $request->user();

        $link = CaregiverLink::where('invite_code', $data['code'])->where('status', 'pending')->first();

        if (!$link || $link->invite_expires_at->isPast()) {
            return response()->json(['status' => false, 'message' => 'This code is wrong or has expired.'], 422);
        }

        if ((int) $link->patient_id === (int) $me->id) {
            return response()->json(['status' => false, 'message' => 'You cannot add yourself.'], 422);
        }

        $existing = CaregiverLink::where('patient_id', $link->patient_id)->where('caregiver_id', $me->id)->first();

        // Free accounts have one family member; the Family plan allows more.
        // People already linked stay linked if the plan ends.
        if (!$existing || $existing->status !== 'active') {
            $patient = User::find($link->patient_id);
            $plans = app(PlanService::class);
            $plans->startTrialIfNew($patient);
            $max = $plans->limit($patient, 'family_members');
            $count = CaregiverLink::active()->where('patient_id', $patient->id)->count();

            if ($max !== null && $count >= $max) {
                return response()->json([
                    'status' => false,
                    'message' => 'This family already has the most members their plan allows.',
                    'message_key' => 'familyLimitReached',
                ], 422);
            }
        }

        if ($existing) {
            $link->delete();
            $existing->update(['status' => 'active']);
            $link = $existing;
        } else {
            $link->update([
                'caregiver_id' => $me->id,
                'status' => 'active',
                'invite_code' => null,
                'invite_expires_at' => null,
            ]);
        }

        $patient = User::find($link->patient_id);

        return response()->json([
            'status' => true,
            'data' => ['id' => $link->id, 'patient_id' => $patient->id, 'name' => $patient->name],
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $me = $request->user();

        // Either side can end the link.
        $link = CaregiverLink::where('id', $id)
            ->where(fn ($q) => $q->where('patient_id', $me->id)->orWhere('caregiver_id', $me->id))
            ->first();

        if (!$link) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        $link->delete();

        return response()->json(['status' => true]);
    }

    /** Today's doses of a patient I care for. */
    public function summary(Request $request, $patientId)
    {
        $me = $request->user();

        $allowed = CaregiverLink::active()->where('patient_id', $patientId)->where('caregiver_id', $me->id)->exists();
        if (!$allowed) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        $patient = User::find($patientId);
        $reminders = Reminder::where('user_id', $patientId)->where('is_active', true)->orderBy('time')->get();

        $doses = [];
        foreach ($reminders as $r) {
            $tz = $r->timezone ?: config('app.timezone');
            $now = Carbon::now($tz);
            if (!in_array($now->format('D'), $r->days ?? [], true)) {
                continue;
            }
            $at = $now->copy()->setTimeFromTimeString($r->time);
            $key = $at->format('Y-m-d H:i');
            $log = DoseLog::where('reminder_id', $r->id)->where('scheduled_for', $key)->first();

            $doses[] = [
                'reminder_id' => $r->id,
                'medicine_name' => $r->medicine_name,
                'time' => $at->format('h:i A'),
                'scheduled_for' => $key,
                'status' => $log?->status ?? ($at->isFuture() ? 'upcoming' : 'due'),
                'taken_at' => $log?->taken_at,
            ];
        }

        $since = now()->subDays(6)->format('Y-m-d') . ' 00:00';
        $week = DoseLog::where('user_id', $patientId)->where('scheduled_for', '>=', $since)
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return response()->json([
            'status' => true,
            'data' => [
                'patient' => ['id' => $patient->id, 'name' => $patient->name, 'mobile_number' => $patient->mobile_number],
                'today' => $doses,
                'last_7_days' => [
                    'taken' => (int) ($week['taken'] ?? 0),
                    'skipped' => (int) ($week['skipped'] ?? 0),
                    'missed' => (int) ($week['missed'] ?? 0),
                ],
            ],
        ]);
    }
}
