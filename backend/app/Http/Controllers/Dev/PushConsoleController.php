<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\Push\FcmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PushConsoleController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->query('user_id');
        $tokens = [];

        if ($userId) {
            $tokens = DeviceToken::query()
                ->where('user_id', $userId)
                ->orderByDesc('last_seen_at')
                ->pluck('token')
                ->all();
        }

        return view('dev.push-console', [
            'tokens' => $tokens,
            'userId' => $userId,
            'reminderLogs' => $this->recentReminderLogs(),
        ]);
    }

    public function send(Request $request, FcmService $fcm): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:1000'],
            'data' => ['nullable', 'string'],
        ]);

        $data = [];
        if (!empty($validated['data'])) {
            $decoded = json_decode($validated['data'], true);
            $data = is_array($decoded) ? $decoded : ['raw' => $validated['data']];
        }

        $fcm->sendToToken(
            $validated['token'],
            $validated['title'],
            $validated['body'],
            array_merge(['type' => 'dev_console'], $data)
        );

        return redirect()
            ->route('dev.push-console')
            ->with('success', 'Push sent to token (check device).');
    }

    /**
     * Fetch recent reminder push result logs from laravel.log for visibility.
     *
     * @return array<int,string>
     */
    private function recentReminderLogs(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!is_readable($logPath)) {
            return [];
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return [];
        }

        $matches = [];
        for ($i = count($lines) - 1; $i >= 0 && count($matches) < 20; $i--) {
            if (str_contains($lines[$i], 'Reminder push results')) {
                $matches[] = $lines[$i];
            }
        }

        return array_reverse($matches);
    }
}
