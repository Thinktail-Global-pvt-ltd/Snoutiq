<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Models\MarketingSingleNotification;
use App\Models\ScheduledPushNotification;
use App\Services\PushService;
use App\Services\Push\FcmService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use App\Jobs\SendFcmEvery5Minutes;
use App\Jobs\SendMarketingSingleNotification;
use Throwable;

class MarketingNotificationController extends Controller
{
    public function __construct(
        private readonly PushService $pushService,
        private readonly FcmService $fcmService
    ) {
    }

    /**
     * Show the marketing notification dashboard
     */
    public function index(): View
    {
        $activeSchedules = ScheduledPushNotification::query()
            ->where('frequency', ScheduledPushNotification::FREQUENCY_FIVE_MINUTES)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        $recentRuns = \App\Models\PushRun::query()
            ->where('trigger', 'marketing')
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get();

        // Check Firebase credentials
        $credentialsPath = config('firebase.projects.app.credentials.file');
        $firebaseStatus = [
            'file_exists' => file_exists($credentialsPath),
            'file_path' => $credentialsPath,
            'is_readable' => file_exists($credentialsPath) ? is_readable($credentialsPath) : false,
            'is_valid_json' => false,
        ];

        if ($firebaseStatus['file_exists'] && $firebaseStatus['is_readable']) {
            $content = file_get_contents($credentialsPath);
            $firebaseStatus['is_valid_json'] = json_decode($content, true) !== null;
        }

        $targetTokenSampleLimit = 25;
        $tokenPreview = DeviceToken::query()
            ->with(['user:id,name,email'])
            ->select(['id', 'user_id', 'token', 'platform', 'device_id', 'last_seen_at', 'created_at'])
            ->whereNotNull('token')
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->limit($targetTokenSampleLimit)
            ->get();

        $tokenPreviewCount = DeviceToken::query()
            ->whereNotNull('token')
            ->count();

        $highlightToken = $tokenPreview->first();
        $primaryFiveMinuteSchedule = $activeSchedules->first();
        $serverNow = now();

        $marketingTestToken = config('push.marketing_test_token');

        return view('marketing.notifications', [
            'activeSchedules' => $activeSchedules,
            'recentRuns' => $recentRuns,
            'firebaseStatus' => $firebaseStatus,
            'tokenPreview' => $tokenPreview,
            'tokenPreviewCount' => $tokenPreviewCount,
            'tokenPreviewLimit' => $targetTokenSampleLimit,
            'highlightToken' => $highlightToken,
            'primaryFiveMinuteSchedule' => $primaryFiveMinuteSchedule,
            'serverNowIso' => $serverNow->toIso8601String(),
            'marketingTestToken' => $marketingTestToken,
        ]);
    }

    /**
     * Show the single-token scheduling dashboard.
     */
    public function single(): View
    {
        $timezone = config('app.timezone', 'UTC');
        $pendingNotifications = MarketingSingleNotification::query()
            ->where('status', MarketingSingleNotification::STATUS_PENDING)
            ->orderBy('send_at')
            ->get();

        $recentNotifications = MarketingSingleNotification::query()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $targetTokenSampleLimit = 25;
        $tokenPreview = DeviceToken::query()
            ->with(['user:id,name,email'])
            ->select(['id', 'user_id', 'token', 'platform', 'device_id', 'last_seen_at', 'created_at'])
            ->whereNotNull('token')
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->limit($targetTokenSampleLimit)
            ->get();

        $tokenPreviewCount = DeviceToken::query()
            ->whereNotNull('token')
            ->count();

        return view('marketing.single-notifications', [
            'pendingNotifications' => $pendingNotifications,
            'recentNotifications' => $recentNotifications,
            'timezone' => $timezone,
            'tokenPreview' => $tokenPreview,
            'tokenPreviewLimit' => $targetTokenSampleLimit,
            'tokenPreviewCount' => $tokenPreviewCount,
            'defaultScheduledFor' => now()->timezone($timezone)->addMinutes(10)->format('Y-m-d\TH:i'),
        ]);
    }

    /**
     * Store a one-off notification that sends one minute before the selected time.
     */
    public function scheduleSingle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'scheduled_for' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);

        $timezone = config('app.timezone', 'UTC');
        $scheduledFor = Carbon::createFromFormat('Y-m-d\TH:i', $validated['scheduled_for'], $timezone);
        $sendAt = $scheduledFor->copy()->subMinute();
        if ($sendAt->lessThan(now())) {
            $sendAt = now()->addSeconds(5);
        }

        $notification = MarketingSingleNotification::create([
            'token' => $validated['token'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'scheduled_for' => $scheduledFor,
            'send_at' => $sendAt,
            'status' => MarketingSingleNotification::STATUS_PENDING,
        ]);

        $pending = SendMarketingSingleNotification::dispatch($notification->id);
        $pending->delay($sendAt);

        return redirect()
            ->route('marketing.notifications.single')
            ->with('success', sprintf(
                'Notification queued! It will be sent at %s (%s) which is 1 minute before your target time.',
                $sendAt->timezone($timezone)->toDayDateTimeString(),
                $timezone
            ));
    }

    /**
     * Send test notification to a specific device token
     */
    public function sendTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'token' => ['required', 'string', 'max:500'],
        ]);

        // Check Firebase credentials file
        $credentialsPath = config('firebase.projects.app.credentials.file');
        if (!file_exists($credentialsPath)) {
            return redirect()
                ->route('marketing.notifications')
                ->withErrors(['test' => sprintf(
                    'Firebase credentials file not found at: %s. Please download it from Firebase Console and place it there.',
                    $credentialsPath
                )]);
        }

        // Check if credentials file is readable and valid JSON
        $credentialsContent = file_get_contents($credentialsPath);
        $credentials = json_decode($credentialsContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()
                ->route('marketing.notifications')
                ->withErrors(['test' => 'Firebase credentials file is not valid JSON. Please check the file.']);
        }

        try {
            $this->fcmService->sendToToken(
                $validated['token'],
                $validated['title'],
                $validated['body'],
                ['type' => 'marketing_test', 'trigger' => 'marketing_test']
            );

            return redirect()
                ->route('marketing.notifications')
                ->with('success', sprintf(
                    'Test notification sent successfully to token: %s',
                    \Illuminate\Support\Str::limit($validated['token'], 50)
                ));
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'TLS') || str_contains($errorMsg, 'SSL') || str_contains($errorMsg, 'CURL error')) {
                $errorMsg = 'Network/SSL connection error. This might be due to: ' . PHP_EOL .
                    '1. Network connectivity issues' . PHP_EOL .
                    '2. Outdated cURL/OpenSSL - try: brew upgrade curl openssl (on macOS)' . PHP_EOL .
                    '3. Firewall/proxy blocking Google APIs' . PHP_EOL .
                    '4. Try: composer update kreait/laravel-firebase' . PHP_EOL .
                    'Original error: ' . $e->getMessage();
            }
            report($e);
            return redirect()
                ->route('marketing.notifications')
                ->withErrors(['test' => 'Failed to send test notification: ' . $errorMsg]);
        } catch (Throwable $e) {
            report($e);
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'TLS') || str_contains($errorMsg, 'SSL') || str_contains($errorMsg, 'CURL error')) {
                $errorMsg = 'Network/SSL connection error. Check your internet connection and try updating cURL/OpenSSL. Original: ' . $e->getMessage();
            }
            return redirect()
                ->route('marketing.notifications')
                ->withErrors(['test' => 'Failed to send test notification: ' . $errorMsg]);
        }
    }

    /**
     * Send notification to all users immediately
     */
    public function sendNow(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
        ]);

        try {
            $run = $this->pushService->broadcast(
                null, // no schedule for immediate sends
                $validated['title'],
                $validated['body'],
                'marketing',
                'MarketingNotificationController@sendNow → PushService@broadcast'
            );

            return redirect()
                ->route('marketing.notifications')
                ->with('success', sprintf(
                    'Notification sent to all users! Run ID: %s. Targeted: %d, Success: %d, Failed: %d',
                    $run->id,
                    $run->targeted_count,
                    $run->success_count,
                    $run->failure_count
                ));
        } catch (Throwable $e) {
            report($e);
            return redirect()
                ->route('marketing.notifications')
                ->withErrors(['send_now' => 'Failed to send notification: ' . $e->getMessage()]);
        }
    }

    /**
     * Schedule notification to be sent every 5 minutes
     */
    public function scheduleEvery5Minutes(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
        ]);

        try {
            $now = Carbon::now();

            // Check if there's already an active schedule
            $existingNotification = ScheduledPushNotification::where('frequency', ScheduledPushNotification::FREQUENCY_FIVE_MINUTES)
                ->where('is_active', true)
                ->first();

            // Create or update the 5-minute schedule
            $notification = ScheduledPushNotification::firstOrNew(
                ['frequency' => ScheduledPushNotification::FREQUENCY_FIVE_MINUTES]
            );

            $wasActive = $notification->is_active;
            
            $notification->fill([
                'title' => $validated['title'],
                'body' => $validated['body'],
                'data' => ['type' => 'marketing', 'trigger' => 'marketing'],
                'is_active' => true,
                'next_run_at' => $now->copy()->addMinutes(5),
            ]);

            // Only update last_run_at if it's a new notification
            if (!$notification->exists) {
                $notification->last_run_at = null;
            }

            $notification->save();

            // Only start the ticker if it wasn't already active (to avoid duplicate jobs)
            if (!$wasActive) {
                $this->startFiveMinuteTicker($notification);
            }

            return redirect()
                ->route('marketing.notifications')
                ->with('success', sprintf(
                    'Scheduled notification "%s" to be sent every 5 minutes. Next run: %s',
                    $notification->title,
                    $notification->next_run_at->toDayDateTimeString()
                ));
        } catch (Throwable $e) {
            report($e);
            return redirect()
                ->route('marketing.notifications')
                ->withErrors(['schedule' => 'Failed to schedule notification: ' . $e->getMessage()]);
        }
    }

    /**
     * Run the scheduled notification immediately (for testing)
     */
    public function runNow(ScheduledPushNotification $notification): RedirectResponse
    {
        if ($notification->frequency !== ScheduledPushNotification::FREQUENCY_FIVE_MINUTES) {
            return redirect()
                ->route('marketing.notifications')
                ->withErrors(['schedule' => 'Invalid schedule type']);
        }

        try {
            $run = $this->pushService->broadcast(
                $notification,
                $notification->title,
                $notification->body ?? '',
                'marketing',
                'MarketingNotificationController@runNow → PushService@broadcast'
            );

            // Update last run time
            $notification->last_run_at = now();
            $notification->save();

            return redirect()
                ->route('marketing.notifications')
                ->with('success', sprintf(
                    'Notification sent immediately! Run ID: %s. Targeted: %d, Success: %d, Failed: %d',
                    $run->id,
                    $run->targeted_count,
                    $run->success_count,
                    $run->failure_count
                ));
        } catch (Throwable $e) {
            report($e);
            return redirect()
                ->route('marketing.notifications')
                ->withErrors(['run_now' => 'Failed to run notification: ' . $e->getMessage()]);
        }
    }

    /**
     * Stop the 5-minute scheduled notification
     */
    public function stopSchedule(ScheduledPushNotification $notification): RedirectResponse
    {
        if ($notification->frequency !== ScheduledPushNotification::FREQUENCY_FIVE_MINUTES) {
            return redirect()
                ->route('marketing.notifications')
                ->withErrors(['schedule' => 'Invalid schedule type']);
        }

        $notification->is_active = false;
        $notification->next_run_at = null;
        $notification->save();

        return redirect()
            ->route('marketing.notifications')
            ->with('success', 'Scheduled notification stopped successfully.');
    }

    /**
     * Start the 5-minute ticker job
     */
    protected function startFiveMinuteTicker(ScheduledPushNotification $notification): void
    {
        if ($notification->frequency !== ScheduledPushNotification::FREQUENCY_FIVE_MINUTES || !$notification->is_active) {
            return;
        }

        $pending = SendFcmEvery5Minutes::dispatch($notification->getKey());

        if ($connection = SendFcmEvery5Minutes::preferredConnection()) {
            $pending->onConnection($connection);
        }

        // Delay first run to 5 minutes from now
        $pending->delay(now()->addMinutes(5));
    }
}
