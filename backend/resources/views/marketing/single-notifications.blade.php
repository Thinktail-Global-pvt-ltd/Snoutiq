<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Snoutiq • Schedule Single Token Notification</title>
    <style>
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
            margin: 24px;
            background: #f8fafc;
            color: #0f172a;
        }
        .card, form {
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
        input[type="text"], textarea, input[type="datetime-local"] {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #cbd5f5;
            margin-bottom: 14px;
            font-size: 15px;
            box-sizing: border-box;
        }
        textarea { min-height: 100px; resize: vertical; }
        button {
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            background: #2563eb;
            color: #ffffff;
            font-size: 15px;
            cursor: pointer;
            font-weight: 500;
        }
        button:hover { background: #1d4ed8; }
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th { background: #eff6ff; }
        .muted { color: #475569; font-size: 13px; }
        .token-debug-box {
            border: 1px dashed #cbd5f5;
            border-radius: 10px;
            background: #f8fafc;
            padding: 14px 16px;
            margin-top: 20px;
        }
        .token-debug-list {
            margin: 12px 0 0;
            padding-left: 18px;
            list-style: decimal;
            max-height: 240px;
            overflow-y: auto;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 13px;
        }
        .token-debug-list li { margin-bottom: 10px; word-break: break-all; }
        .token-debug-meta {
            display: block;
            margin-top: 2px;
            color: #475569;
            font-size: 12px;
        }
        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fef9c3; color: #a16207; }
        .status-sent { background: #dcfce7; color: #166534; }
        .status-failed { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <h1>Schedule Notification for a Single FCM Token</h1>
    <p class="muted">Timezone: <strong>{{ $timezone }}</strong>. The notification will be sent <strong>exactly one minute before</strong> the time you select below.</p>

    @if(session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="errors">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('marketing.notifications.single.schedule') }}">
        @csrf
        <h2>Schedule a Token</h2>
        <p class="muted">The queue worker will dispatch this notification one minute before your selected time.</p>

        <label for="token">Device FCM Token *</label>
        <textarea id="token" name="token" required maxlength="500" placeholder="Paste the device token">{{ old('token') }}</textarea>

        <label for="title">Title *</label>
        <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="120" placeholder="e.g., Appointment Reminder">

        <label for="body">Message *</label>
        <textarea id="body" name="body" required maxlength="500" placeholder="Enter notification message">{{ old('body') }}</textarea>

        <label for="scheduled_for">Target Date & Time ({{ $timezone }}) *</label>
        <input type="datetime-local" id="scheduled_for" name="scheduled_for" value="{{ old('scheduled_for', $defaultScheduledFor) }}" required>
        <p class="muted">We will deliver at: your selected time minus 1 minute.</p>

        <button type="submit">Schedule Notification</button>
    </form>

    <div class="card">
        <h2>Pending Notifications</h2>
        @if($pendingNotifications->isEmpty())
            <p class="muted">No pending notifications.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Send At ({{ $timezone }})</th>
                        <th>Target Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingNotifications as $notification)
                        <tr>
                            <td><code>{{ \Illuminate\Support\Str::limit($notification->token, 90) }}</code></td>
                            <td>{{ optional($notification->send_at)->timezone($timezone)->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($notification->scheduled_for)->timezone($timezone)->format('Y-m-d H:i') }}</td>
                            <td><span class="status-pill status-pending">Pending</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h2>Recent Runs</h2>
        @if($recentNotifications->isEmpty())
            <p class="muted">No history yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Token</th>
                        <th>Scheduled For</th>
                        <th>Sent At</th>
                        <th>Status</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentNotifications as $notification)
                        <tr>
                            <td>{{ $notification->title }}</td>
                            <td><code>{{ \Illuminate\Support\Str::limit($notification->token, 60) }}</code></td>
                            <td>{{ optional($notification->scheduled_for)->timezone($timezone)->format('Y-m-d H:i') }}</td>
                            <td>
                                {{ $notification->sent_at ? $notification->sent_at->timezone($timezone)->format('Y-m-d H:i') : '—' }}
                            </td>
                            <td>
                                @php
                                    $statusClass = $notification->status === \App\Models\MarketingSingleNotification::STATUS_SENT ? 'status-sent' : ($notification->status === \App\Models\MarketingSingleNotification::STATUS_FAILED ? 'status-failed' : 'status-pending');
                                @endphp
                                <span class="status-pill {{ $statusClass }}">{{ ucfirst($notification->status) }}</span>
                            </td>
                            <td class="muted">{{ \Illuminate\Support\Str::limit($notification->error_message, 60) ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="token-debug-box">
        <strong>Available Device Tokens (reference)</strong>
        <p class="muted">Showing {{ $tokenPreview->count() }} of {{ $tokenPreviewCount }} stored tokens (limit {{ $tokenPreviewLimit }}).</p>
        @if($tokenPreviewCount === 0)
            <p style="color: #b45309;">No tokens found. Ask a user to open the app so their token registers.</p>
        @else
            <ul class="token-debug-list">
                @foreach($tokenPreview as $token)
                    <li>
                        <code>{{ \Illuminate\Support\Str::limit($token->token, 200) }}</code>
                        <span class="token-debug-meta">
                            {{ $token->platform ?? 'unknown platform' }} · {{ $token->device_id ?? 'device-'.$token->id }}
                            @if($token->user)
                                · {{ $token->user->name ?? 'User #'.$token->user_id }}
                            @elseif($token->user_id)
                                · User #{{ $token->user_id }}
                            @endif
                            @if($token->last_seen_at)
                                · last seen {{ $token->last_seen_at->diffForHumans() }}
                            @else
                                · created {{ $token->created_at?->diffForHumans() }}
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</body>
</html>
