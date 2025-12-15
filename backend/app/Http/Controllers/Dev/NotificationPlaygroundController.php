<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class NotificationPlaygroundController extends Controller
{
    public function index(): View
    {
        return view('dev.notify');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:1000'],
            'type' => ['nullable', 'string', 'max:120'],
            'payload' => ['nullable', 'string'],
        ]);

        $payload = [];
        if (!empty($validated['payload'])) {
            $decoded = json_decode($validated['payload'], true);
            $payload = is_array($decoded) ? $decoded : ['raw_payload' => $validated['payload']];
        }

        $notification = Notification::create([
            'user_id' => $validated['user_id'],
            'type' => $validated['type'] ?: 'custom_dev',
            'title' => $validated['title'],
            'body' => $validated['body'],
            'payload' => $payload,
            'status' => Notification::STATUS_PENDING,
        ]);

        SendNotificationJob::dispatchSync($notification->id);

        return redirect()
            ->route('dev.notify')
            ->with('success', sprintf('Notification queued for user #%d (ID %d)', $validated['user_id'], $notification->id));
    }

    public function nextAppointment(Request $request): JsonResponse
    {
        $userId = (int) $request->query('user_id');
        if ($userId <= 0) {
            return response()->json(['error' => 'user_id is required'], 422);
        }

        $tz = config('app.timezone', 'UTC');
        $now = Carbon::now($tz);

        $candidates = Appointment::query()
            ->whereIn('status', ['confirmed', 'rescheduled'])
            ->whereNotNull('appointment_date')
            ->whereNotNull('appointment_time')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get()
            ->filter(function (Appointment $appt) use ($userId) {
                return $this->resolvePatientUserId($appt) === $userId;
            })
            ->map(function (Appointment $appt) use ($tz) {
                try {
                    $start = Carbon::createFromFormat('Y-m-d H:i', $appt->appointment_date.' '.$appt->appointment_time, $tz);
                } catch (\Exception) {
                    return null;
                }
                return [
                    'id' => $appt->id,
                    'start_at' => $start,
                ];
            })
            ->filter();

        $next = $candidates
            ->filter(fn ($c) => $c['start_at']->greaterThanOrEqualTo($now))
            ->first()
            ?: $candidates->first();

        if (! $next) {
            return response()->json(['message' => 'No upcoming appointment found']);
        }

        $diffSeconds = $now->diffInSeconds($next['start_at'], false);

        return response()->json([
            'appointment_id' => $next['id'],
            'start_at_iso' => $next['start_at']->toIso8601String(),
            'start_at_human' => $next['start_at']->format('d M Y, h:i A'),
            'seconds_until' => $diffSeconds,
        ]);
    }

    private function resolvePatientUserId(Appointment $appointment): ?int
    {
        if (! empty($appointment->patient_user_id)) {
            return (int) $appointment->patient_user_id;
        }

        $notes = $appointment->notes;
        if (! $notes) {
            return null;
        }
        $decoded = json_decode($notes, true);
        if (is_array($decoded) && isset($decoded['patient_user_id'])) {
            return (int) $decoded['patient_user_id'];
        }

        return null;
    }
}
