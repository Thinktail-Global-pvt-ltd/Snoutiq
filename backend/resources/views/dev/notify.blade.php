<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Snoutiq • Dev Notification Sender</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; margin: 24px; background: #f8fafc; color: #0f172a; }
        form { background: #ffffff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08); max-width: 760px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input[type="text"], textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5f5; margin-bottom: 14px; font-size: 15px; box-sizing: border-box; }
        textarea { min-height: 120px; resize: vertical; }
        button { padding: 10px 18px; border-radius: 8px; border: none; background: #2563eb; color: #ffffff; font-size: 15px; cursor: pointer; font-weight: 500; }
        button:hover { background: #1d4ed8; }
        .flash { padding: 12px 16px; border-radius: 8px; background: #ecfdf5; color: #047857; margin-bottom: 16px; max-width: 760px; }
        .errors { padding: 12px 16px; border-radius: 8px; background: #fef2f2; color: #b91c1c; margin-bottom: 16px; max-width: 760px; }
        .muted { color: #475569; font-size: 13px; }
        code { background: #e2e8f0; padding: 2px 4px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Dev Notification Sender</h1>
    <p class="muted">Send a one-off notification to any user. Uses the existing notification pipeline (FCM tokens from <code>device_tokens</code>, fallback channels if enabled). Also shows a live countdown to the user&rsquo;s next appointment.</p>

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

    <form method="POST" action="{{ route('dev.notify.send') }}">
        @csrf

        <label for="user_id">User ID *</label>
        <input type="text" id="user_id" name="user_id" value="{{ old('user_id') }}" required placeholder="e.g., 437">
        <p class="muted">We’ll look up all FCM tokens for this user in <code>device_tokens</code>.</p>

        <label for="title">Title *</label>
        <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="200" placeholder="Notification title">

        <label for="body">Message *</label>
        <textarea id="body" name="body" required maxlength="1000" placeholder="Notification message">{{ old('body') }}</textarea>

        <label for="type">Type (optional)</label>
        <input type="text" id="type" name="type" value="{{ old('type', 'custom_dev') }}" maxlength="120" placeholder="e.g., custom_dev">

        <label for="payload">Payload JSON (optional)</label>
        <textarea id="payload" name="payload" placeholder='{"foo":"bar","id":123}'>{{ old('payload') }}</textarea>
        <p class="muted">Payload will be attached to the push data. If invalid JSON, we’ll store it as a raw string.</p>

        <button type="submit">Send Notification</button>

        <div style="margin-top:18px; padding:12px; background:#f8fafc; border:1px dashed #cbd5f5; border-radius:10px;">
            <strong>Next Appointment Countdown</strong>
            <p class="muted" id="appt-meta">Enter a user ID and click "Check next appointment" to start the timer.</p>
            <div id="appt-timer" style="font-size:24px; font-weight:700; color:#0f172a;">--:--:--</div>
            <button type="button" id="check-next" style="margin-top:10px;">Check next appointment</button>
        </div>
    </form>

    <script>
        (function () {
            const btn = document.getElementById('check-next');
            const userInput = document.getElementById('user_id');
            const timerEl = document.getElementById('appt-timer');
            const metaEl = document.getElementById('appt-meta');
            let countdownId = null;
            let targetTs = null;

            async function fetchNext() {
                const userId = userInput.value.trim();
                if (!userId) {
                    metaEl.textContent = 'Please enter a user ID first.';
                    return;
                }
                try {
                    const res = await fetch(`{{ route('dev.notify.next') }}?user_id=${encodeURIComponent(userId)}&_=${Date.now()}`);
                    const data = await res.json();
                    if (data.error) {
                        metaEl.textContent = data.error;
                        timerEl.textContent = '--:--:--';
                        return;
                    }
                    if (data.message) {
                        metaEl.textContent = data.message;
                        timerEl.textContent = '--:--:--';
                        return;
                    }
                    targetTs = new Date(data.start_at_iso).getTime();
                    metaEl.textContent = `Appointment #${data.appointment_id} at ${data.start_at_human} ({{ config('app.timezone') }})`;
                    startCountdown();
                } catch (e) {
                    metaEl.textContent = 'Failed to load next appointment.';
                    timerEl.textContent = '--:--:--';
                }
            }

            function startCountdown() {
                if (!targetTs) return;
                if (countdownId) clearInterval(countdownId);
                updateCountdown();
                countdownId = setInterval(updateCountdown, 1000);
            }

            function updateCountdown() {
                const now = Date.now();
                const diff = Math.floor((targetTs - now) / 1000);
                if (diff <= 0) {
                    timerEl.textContent = 'Starting now...';
                    clearInterval(countdownId);
                    return;
                }
                const h = Math.floor(diff / 3600).toString().padStart(2, '0');
                const m = Math.floor((diff % 3600) / 60).toString().padStart(2, '0');
                const s = (diff % 60).toString().padStart(2, '0');
                timerEl.textContent = `${h}:${m}:${s}`;
            }

            btn.addEventListener('click', fetchNext);
        })();
    </script>
</body>
</html>
