<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Reminder Tester</title>
    <style>
        body {
            font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
            background:#f5f5f5;
            color:#111;
            margin:0;
            padding:2rem;
        }
        .card {
            padding:1.5rem;
            border-radius:12px;
            background:#fff;
            box-shadow:0 12px 24px rgba(0,0,0,.08);
            max-width:640px;
            margin:1rem auto;
        }
        button {
            display:inline-flex;
            align-items:center;
            gap:.5rem;
            padding:.65rem 1.4rem;
            border:none;
            border-radius:8px;
            background:#16a34a;
            color:#fff;
            font-size:1rem;
            cursor:pointer;
        }
        button:disabled {
            background:#94a3b8;
        }
        .status {
            margin-top:1rem;
            padding:1rem;
            border-radius:8px;
            background:#eef2ff;
            color:#312e81;
        }
        small {
            color:#6b7280;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Consultation Reminder Scheduler</h1>
        <p>Trigger the same reminders workflow that the scheduler already executes.</p>

        <form method="POST" action="{{ route('dev.reminders.dispatch') }}">
            @csrf
            <button type="submit">Run reminders now</button>
            <p><small>The command calls <code>AppointmentReminderService::dispatch()</code>.</small></p>
        </form>

        @if($status)
            <div class="status">
                <strong>{{ $status }}</strong>
                @if($count !== null)
                    <p>{{ $count }} reminder(s) dispatched.</p>
                @endif
                @if($last_run)
                    <p>Last run: {{ $last_run }}</p>
                @endif
            </div>
        @endif
    </div>
</body>
</html>
