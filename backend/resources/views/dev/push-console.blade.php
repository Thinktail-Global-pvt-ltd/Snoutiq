<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Snoutiq • Push Console</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; margin: 24px; background: #f8fafc; color: #0f172a; }
        .panel { background: #ffffff; padding: 20px; border-radius: 12px; box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08); max-width: 840px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input[type="text"], textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5f5; margin-bottom: 14px; font-size: 15px; box-sizing: border-box; }
        textarea { min-height: 100px; resize: vertical; }
        button { padding: 10px 16px; border-radius: 8px; border: none; background: #2563eb; color: #fff; font-size: 15px; cursor: pointer; font-weight: 600; }
        button:hover { background: #1d4ed8; }
        .flash { padding: 12px 16px; border-radius: 8px; background: #ecfdf5; color: #047857; margin-bottom: 16px; }
        .errors { padding: 12px 16px; border-radius: 8px; background: #fef2f2; color: #b91c1c; margin-bottom: 16px; }
        .tokens { background: #0f172a; color: #e5e7eb; border-radius: 10px; padding: 12px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; }
        .row { display: flex; gap: 12px; }
        .row > div { flex: 1; }
        .muted { color: #475569; font-size: 13px; margin-top: -6px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <h1>Push Console</h1>
    <p class="muted">Send a push to any FCM token and inspect the user’s stored tokens. This calls the same /api/push/test endpoint used in the app.</p>

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

    <div class="panel" style="margin-bottom:18px;">
        <form method="POST" action="{{ route('dev.push-console.send') }}">
            @csrf
            <label for="token">FCM Token *</label>
            <textarea id="token" name="token" required placeholder="Paste FCM token">{{ old('token') }}</textarea>

            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required value="{{ old('title', 'Test push') }}">

            <label for="body">Body *</label>
            <textarea id="body" name="body" required>{{ old('body', 'Hello from Push Console') }}</textarea>

            <label for="data">Data JSON (optional)</label>
            <textarea id="data" name="data" placeholder='{"type":"test"}'>{{ old('data', '{"type":"test"}') }}</textarea>
            <p class="muted">If JSON parse fails, we send it as raw.</p>

            <button type="submit">Send Push</button>
        </form>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('dev.push-console') }}" class="row" style="align-items: flex-end;">
            <div>
                <label for="user_id">Lookup stored tokens by user_id</label>
                <input type="text" id="user_id" name="user_id" value="{{ $userId ?? '' }}" placeholder="e.g., 516">
                <p class="muted">Pulls from device_tokens table.</p>
            </div>
            <div style="flex:0;">
                <button type="submit">Fetch tokens</button>
            </div>
        </form>

        @if(!empty($tokens))
            <p class="muted">Found {{ count($tokens) }} token(s) for user {{ $userId }}:</p>
            <div class="tokens">
                @foreach($tokens as $token)
                    <div>{{ $token }}</div>
                @endforeach
            </div>
        @elseif($userId)
            <p class="muted">No tokens found for user {{ $userId }}.</p>
        @endif
    </div>

    <div class="panel" style="margin-top:18px;">
        <h3>Recent Reminder Sends (from logs)</h3>
        <p class="muted">Latest “Reminder push results” entries from laravel.log (max 20).</p>
        @if(!empty($reminderLogs))
            <div class="tokens">
                @foreach($reminderLogs as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </div>
        @else
            <p class="muted">No reminder log entries found.</p>
        @endif
    </div>
</body>
</html>
