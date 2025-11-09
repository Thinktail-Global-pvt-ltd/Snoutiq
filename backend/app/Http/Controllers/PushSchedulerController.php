<?php

namespace App\Http\Controllers;

use App\Models\ScheduledPushNotification;
use App\Models\DeviceToken;
use App\Models\PushRun;
use App\Services\Logging\FounderAudit;
use App\Services\PushService;
use App\Support\QueryTracker;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use App\Jobs\SendFcmEvery10Seconds;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PushSchedulerController extends Controller
{
    public function __construct(private readonly PushService $pushService)
    {
    }

    public function index(Request $request): View
    {
        $notifications = ScheduledPushNotification::query()
            ->orderBy('frequency')
            ->get()
            ->keyBy('frequency');

        $deviceTokens = DeviceToken::query()
            ->select(['id', 'user_id', 'platform', 'token', 'last_seen_at'])
            ->orderBy('id')
            ->get();

        $filters = [
            'trigger' => $request->input('filter_trigger') ?: null,
            'status' => $request->input('filter_status') ?: null,
            'date' => $request->input('filter_date') ?: null,
        ];

        $pushRunsQuery = PushRun::query()
            ->with('schedule')
            ->latest('started_at');

        if ($filters['trigger']) {
            $pushRunsQuery->where('trigger', $filters['trigger']);
        }

        if ($filters['status'] === 'has_failures') {
            $pushRunsQuery->where('failure_count', '>', 0);
        } elseif ($filters['status'] === 'success_only') {
            $pushRunsQuery->where('failure_count', '=', 0);
        }

        if ($filters['date']) {
            try {
                $date = Carbon::createFromFormat('Y-m-d', $filters['date'])->toDateString();
                $pushRunsQuery->whereDate('started_at', $date);
            } catch (Throwable) {
                $filters['date'] = null;
            }
        }

        $pushRuns = $pushRunsQuery->paginate(100)->withQueryString();

        return view('fcm.scheduler', [
            'notifications' => $notifications,
            'frequencies' => ScheduledPushNotification::frequencyLabels(),
            'deviceTokens' => $deviceTokens,
            'pushRuns' => $pushRuns,
            'filters' => $filters,
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

        if ($notification->frequency === ScheduledPushNotification::FREQUENCY_TEN_SECONDS) {
            $notification->next_run_at = null;
        } else {
            $notification->next_run_at = $notification->computeNextRun($now);
        }
        $notification->last_run_at = null;
        $notification->save();

        if ($notification->frequency === ScheduledPushNotification::FREQUENCY_TEN_SECONDS) {
            $this->startTenSecondTicker($notification);
        }

        return redirect()
            ->route('dev.push-scheduler')
            ->with('status', sprintf(
                'Scheduled "%s" to broadcast %s. Next run: %s',
                $notification->title,
                ScheduledPushNotification::frequencyLabels()[$notification->frequency] ?? $notification->frequency,
                $notification->frequency === ScheduledPushNotification::FREQUENCY_TEN_SECONDS
                    ? 'Ticker running every 10 seconds'
                    : optional($notification->next_run_at)->toDayDateTimeString()
            ));
    }

    public function update(Request $request, ScheduledPushNotification $notification): RedirectResponse
    {
        $action = $request->validate([
            'action' => ['required', 'string', Rule::in(['pause', 'resume'])],
        ])['action'];

        $now = Carbon::now();
        $message = null;

        if ($action === 'pause') {
            $notification->is_active = false;
            $notification->next_run_at = null;
            $notification->save();
            $message = sprintf('Paused %s schedule.', ScheduledPushNotification::frequencyLabels()[$notification->frequency] ?? $notification->frequency);
        } elseif ($action === 'resume') {
            $notification->is_active = true;
            $notification->next_run_at = $notification->frequency === ScheduledPushNotification::FREQUENCY_TEN_SECONDS
                ? null
                : $notification->computeNextRun($now);
            $notification->save();
            if ($notification->frequency === ScheduledPushNotification::FREQUENCY_TEN_SECONDS) {
                $this->startTenSecondTicker($notification);
            }
            $message = sprintf(
                'Resumed %s schedule. Next run: %s',
                ScheduledPushNotification::frequencyLabels()[$notification->frequency] ?? $notification->frequency,
                optional($notification->next_run_at)->toDayDateTimeString()
            );
        }

        return redirect()
            ->route('dev.push-scheduler')
            ->with('status', $message);
    }

    public function runNow(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'schedule_id' => ['nullable', 'integer', 'exists:scheduled_push_notifications,id'],
        ]);

        $handler = 'PushSchedulerController@runNow';
        $tracker = new QueryTracker();
        $tracker->start();
        $startedAt = microtime(true);
        $run = null;
        $response = null;

        FounderAudit::info('run_now.start', ['handler' => $handler]);

        $schedule = null;

        if (! empty($validated['schedule_id'])) {
            $schedule = ScheduledPushNotification::query()
                ->active()
                ->whereKey($validated['schedule_id'])
                ->first();
        }

        if (! $schedule) {
            $schedule = ScheduledPushNotification::query()
                ->active()
                ->orderBy('frequency')
                ->first();
        }

        if (! $schedule) {
            $response = redirect()
                ->route('dev.push-scheduler')
                ->withErrors(['schedule_id' => 'No active schedule found to run immediately.']);
        } else {
            try {
                $run = $this->pushService->broadcast(
                    $schedule,
                    $schedule->title,
                    $schedule->body ?? 'Snoutiq scheduled notification',
                    'run_now',
                    'PushSchedulerController@runNow → PushService@broadcast'
                );

                $response = redirect()
                    ->route('dev.push-scheduler')
                    ->with('status', sprintf('Queued immediate push (run %s).', $run->id));
            } catch (Throwable $e) {
                report($e);
                FounderAudit::error('run_now.exception', $e, ['handler' => $handler]);

                $response = redirect()
                    ->route('dev.push-scheduler')
                    ->withErrors(['run_now' => 'Failed to queue push: '.$e->getMessage()]);
            }
        }

        $dbMetrics = $tracker->finish();
        FounderAudit::info('run_now.db', $dbMetrics + ['handler' => $handler]);

        if ($run) {
            FounderAudit::info('run_now.fcm', [
                'handler' => $handler,
                'run_id' => $run->id,
                'targeted' => $run->targeted_count,
                'success' => $run->success_count,
                'failed' => $run->failure_count,
            ]);
        }

        FounderAudit::info('run_now.end', [
            'handler' => $handler,
            'durationMs' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);

        return $response
            ?? redirect()
                ->route('dev.push-scheduler')
                ->withErrors(['schedule_id' => 'Unable to run push notification.']);
    }

    public function showLog(PushRun $run): BinaryFileResponse
    {
        if (! $run->log_file || ! is_file($run->log_file)) {
            abort(404, 'Log file not found.');
        }

        return response()->file($run->log_file);
    }

    protected function startTenSecondTicker(ScheduledPushNotification $notification): void
    {
        if ($notification->frequency !== ScheduledPushNotification::FREQUENCY_TEN_SECONDS || ! $notification->is_active) {
            return;
        }

        $pending = SendFcmEvery10Seconds::dispatch($notification->getKey());

        if ($connection = SendFcmEvery10Seconds::preferredConnection()) {
            $pending->onConnection($connection);
        }
    }
}
