<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Snoutiq • Push Scheduler</title>
    <style>
        :root {
            color-scheme: light dark;
        }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
            margin: 24px;
            background: #f8fafc;
            color: #0f172a;
        }
        h1, h2 {
            margin-bottom: 12px;
        }
        form, .card {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            margin-bottom: 24px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }
        select, input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #cbd5f5;
            margin-bottom: 14px;
            font-size: 15px;
        }
        button {
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            background: #2563eb;
            color: #ffffff;
            font-size: 15px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #eff6ff;
            font-weight: 600;
        }
        .status-active {
            color: #166534;
            font-weight: 600;
        }
        .status-inactive {
            color: #b91c1c;
            font-weight: 600;
        }
        .flash {
            padding: 12px 16px;
            border-radius: 8px;
            background: #ecfdf5;
            color: #047857;
            margin-bottom: 16px;
        }
        .errors {
            padding: 12px 16px;
            border-radius: 8px;
            background: #fef2f2;
            color: #b91c1c;
            margin-bottom: 16px;
        }
        .errors ul {
            padding-left: 20px;
            margin: 0;
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .actions form {
            display: inline;
        }
        .actions button {
            background: #0f172a;
            padding: 8px 12px;
        }
        .actions button.secondary {
            background: #475569;
        }
        .actions button.danger {
            background: #b91c1c;
        }
        .logs-table td.token {
            font-family: ui-monospace, SFMono-Regular, SFMono, Menlo, Monaco, Consolas, Liberation Mono, Courier New, monospace;
            font-size: 12px;
            word-break: break-all;
        }
        .logs-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <h1>Snoutiq Push Scheduler</h1>
    <p>Select a cadence and message to broadcast a push/test notification to every registered device token.</p>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="errors">
            <strong>Validation issues:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('dev.push-scheduler.store') }}">
        @csrf
        <label for="frequency">Send cadence</label>
        <select id="frequency" name="frequency" required>
            <option value="">Choose frequency…</option>
            @foreach ($frequencies as $value => $label)
                <option value="{{ $value }}" @selected(old('frequency') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <label for="title">Notification title</label>
        <input id="title" type="text" name="title" value="{{ old('title', 'Snoutiq Alert') }}" maxlength="120" required />

        <label for="body">Notification body</label>
        <textarea id="body" name="body" rows="3" maxlength="500" placeholder="Body shown on the device">{{ old('body', 'Scheduled push from Snoutiq admin console') }}</textarea>

        <button type="submit">Save &amp; activate schedule</button>
    </form>

    <div class="card">
        <h2>Current schedules</h2>
        <table>
            <thead>
                <tr>
                    <th>Frequency</th>
                    <th>Status</th>
                    <th>Title</th>
                    <th>Next run</th>
                    <th>Last run</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($frequencies as $value => $label)
                    @php /** @var \App\Models\ScheduledPushNotification|null $notification */ $notification = $notifications->get($value); @endphp
                    <tr>
                        <td>{{ $label }}</td>
                        @if ($notification)
                            <td class="{{ $notification->is_active ? 'status-active' : 'status-inactive' }}">
                                {{ $notification->is_active ? 'Active' : 'Paused' }}
                            </td>
                            <td>{{ $notification->title }}</td>
                            <td>
                                {{ $notification->next_run_at?->toDayDateTimeString() ?? '—' }}
                            </td>
                            <td>
                                {{ $notification->last_run_at?->toDayDateTimeString() ?? '—' }}
                            </td>
                            <td>
                                <div class="actions">
                                    <form method="POST" action="{{ route('dev.push-scheduler.update', $notification) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="run_now" />
                                        <button type="submit">Run now</button>
                                    </form>
                                    @if ($notification->is_active)
                                        <form method="POST" action="{{ route('dev.push-scheduler.update', $notification) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="pause" />
                                            <button type="submit" class="secondary">Pause</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('dev.push-scheduler.update', $notification) }}">
                                            @csrf
                                            <input type="hidden" name="action" value="resume" />
                                            <button type="submit" class="secondary">Resume</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        @else
                            <td class="status-inactive">Not configured</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Registered device tokens</h2>
        <table class="logs-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Platform</th>
                <th>Last seen</th>
                <th>Token</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($deviceTokens as $deviceToken)
                <tr>
                    <td>{{ $deviceToken->id }}</td>
                    <td>{{ $deviceToken->user_id ?? 'N/A' }}</td>
                    <td>{{ $deviceToken->platform ?? 'N/A' }}</td>
                    <td>{{ optional($deviceToken->last_seen_at)->toDayDateTimeString() ?? 'Never' }}</td>
                    <td class="token">{{ $deviceToken->token }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No device tokens registered yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Recent dispatch log</h2>
        <table class="logs-table">
            <thead>
            <tr>
                <th>Sent at</th>
                <th>Frequency</th>
                <th>Title</th>
                <th>Body</th>
                <th>User ID</th>
                <th>Device token ID</th>
                <th>FCM token</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->dispatched_at?->toDayDateTimeString() }}</td>
                    <td>{{ $log->notification?->frequency ? ($frequencies[$log->notification->frequency] ?? $log->notification->frequency) : '—' }}</td>
                    <td>{{ $log->notification?->title ?? '—' }}</td>
                    <td>{{ $log->notification?->body ?? '—' }}</td>
                    <td>{{ $log->user_id ?? 'N/A' }}</td>
                    <td>{{ $log->device_token_id ?? 'N/A' }}</td>
                    <td class="token">{{ $log->token }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No dispatches recorded yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
