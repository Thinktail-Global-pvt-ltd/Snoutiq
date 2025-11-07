<?php

namespace App\Http\Controllers;

use App\Jobs\BroadcastScheduledNotification;
use App\Models\ScheduledPushNotification;
use App\Models\ScheduledPushDispatchLog;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PushSchedulerController extends Controller
{
    public function index(): View
    {
        $notifications = ScheduledPushNotification::query()
            ->orderBy('frequency')
            ->get()
            ->keyBy('frequency');

        $logs = ScheduledPushDispatchLog::query()
            ->with('notification')
            ->latest('dispatched_at')
            ->limit(50)
            ->get();

        $deviceTokens = DeviceToken::query()
            ->select(['id', 'user_id', 'platform', 'token', 'last_seen_at'])
            ->orderBy('id')
            ->get();

        return view('fcm.scheduler', [
            'notifications' => $notifications,
            'frequencies' => ScheduledPushNotification::frequencyLabels(),
            'logs' => $logs,
            'deviceTokens' => $deviceTokens,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'frequency' => ['required', 'string', 'in:'.implode(',', ScheduledPushNotification::FREQUENCIES)],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:500'],
        ]);

        $now = Carbon::now();

        $notification = ScheduledPushNotification::firstOrNew(
            ['frequency' => $validated['frequency']]
        );

        $notification->fill([
            'title' => $validated['title'],
            'body' => $validated['body'] ?? 'Snoutiq scheduled notification',
            'data' => ['type' => 'scheduled_test'],
            'is_active' => true,
        ]);

        $notification->next_run_at = $notification->computeNextRun($now);
        $notification->last_run_at = null;
        $notification->save();

        return redirect()
            ->route('dev.push-scheduler')
            ->with('status', sprintf(
                'Scheduled "%s" to broadcast %s. Next run: %s',
                $notification->title,
                ScheduledPushNotification::frequencyLabels()[$notification->frequency] ?? $notification->frequency,
                optional($notification->next_run_at)->toDayDateTimeString()
            ));
    }

    public function update(Request $request, ScheduledPushNotification $notification): RedirectResponse
    {
        $action = $request->validate([
            'action' => ['required', 'string', Rule::in(['pause', 'resume', 'run_now'])],
        ])['action'];

        $now = Carbon::now();
        $message = null;

        if ($action === 'pause') {
            $notification->is_active = false;
            $notification->save();
            $message = sprintf('Paused %s schedule.', ScheduledPushNotification::frequencyLabels()[$notification->frequency] ?? $notification->frequency);
        } elseif ($action === 'resume') {
            $notification->is_active = true;
            $notification->next_run_at = $notification->computeNextRun($now);
            $notification->save();
            $message = sprintf(
                'Resumed %s schedule. Next run: %s',
                ScheduledPushNotification::frequencyLabels()[$notification->frequency] ?? $notification->frequency,
                optional($notification->next_run_at)->toDayDateTimeString()
            );
        } elseif ($action === 'run_now') {
            BroadcastScheduledNotification::dispatch($notification->getKey());
            $notification->last_run_at = $now;
            $notification->next_run_at = $notification->computeNextRun($now);
            $notification->save();
            $message = sprintf('Queued immediate push for %s schedule.', ScheduledPushNotification::frequencyLabels()[$notification->frequency] ?? $notification->frequency);
        }

        return redirect()
            ->route('dev.push-scheduler')
            ->with('status', $message);
    }
}
