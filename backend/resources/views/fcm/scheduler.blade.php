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
        .actions button.disabled {
            background: #cbd5f5;
            color: #475569;
            cursor: not-allowed;
        }
        .logs-table td.token {
            font-family: ui-monospace, SFMono-Regular, SFMono, Menlo, Monaco, Consolas, Liberation Mono, Courier New, monospace;
            font-size: 12px;
            word-break: break-all;
        }
        .logs-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }
        .filters .field {
            display: flex;
            flex-direction: column;
            min-width: 180px;
        }
        .filters .actions {
            margin-top: 4px;
        }
        .button-link {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5f5;
            text-decoration: none;
            color: #0f172a;
            font-size: 14px;
        }
        .pagination {
            margin-top: 16px;
        }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }
        .modal.open {
            display: flex;
        }
        .modal-content {
            background: #fff;
            color: #0f172a;
            border-radius: 12px;
            padding: 24px;
            width: min(480px, 92%);
            box-shadow: 0 25px 50px rgba(15, 23, 42, 0.25);
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 10px;
            right: 12px;
            border: none;
            background: transparent;
            font-size: 24px;
            cursor: pointer;
        }
        .modal-section ul {
            padding-left: 18px;
        }
        .modal-log-link {
            display: inline-flex;
            margin-top: 12px;
            color: #2563eb;
        }
        .modal-log-link.disabled {
            pointer-events: none;
            opacity: 0.5;
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
                                    @if ($notification && $notification->is_active)
                                        <form method="POST" action="{{ route('dev.push-scheduler.run-now') }}">
                                            @csrf
                                            <input type="hidden" name="schedule_id" value="{{ $notification->id }}" />
                                            <button type="submit">Run now</button>
                                        </form>
                                    @else
                                        <button type="button" class="disabled" disabled>Run now</button>
                                    @endif
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
        <h2>Push run history</h2>
        <form method="GET" action="{{ route('dev.push-scheduler') }}" class="filters">
            <div class="field">
                <label for="filter_trigger">Trigger</label>
                <select id="filter_trigger" name="filter_trigger">
                    <option value="">All triggers</option>
                    <option value="scheduled" @selected(($filters['trigger'] ?? null) === 'scheduled')>Scheduled</option>
                    <option value="run_now" @selected(($filters['trigger'] ?? null) === 'run_now')>Run now</option>
                </select>
            </div>
            <div class="field">
                <label for="filter_status">Status</label>
                <select id="filter_status" name="filter_status">
                    <option value="">All</option>
                    <option value="success_only" @selected(($filters['status'] ?? null) === 'success_only')>Success only</option>
                    <option value="has_failures" @selected(($filters['status'] ?? null) === 'has_failures')>Has failures</option>
                </select>
            </div>
            <div class="field">
                <label for="filter_date">Date</label>
                <input type="date" id="filter_date" name="filter_date" value="{{ $filters['date'] ?? '' }}" />
            </div>
            <div class="field actions-inline">
                <label>&nbsp;</label>
                <div class="actions">
                    <button type="submit" class="secondary">Apply filters</button>
                    <a href="{{ route('dev.push-scheduler') }}" class="button-link">Reset</a>
                </div>
            </div>
        </form>
        @if ($pushRuns->count())
            <table class="logs-table">
                <thead>
                <tr>
                    <th>Time</th>
                    <th>Trigger</th>
                    <th>Title</th>
                    <th>Targeted</th>
                    <th>Success</th>
                    <th>Fail</th>
                    <th>Duration (ms)</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($pushRuns as $run)
                    <tr>
                        <td>{{ $run->started_at?->toDayDateTimeString() ?? '—' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $run->trigger)) }}</td>
                        <td>{{ $run->title }}</td>
                        <td>{{ number_format($run->targeted_count) }}</td>
                        <td class="{{ $run->failure_count > 0 ? 'status-inactive' : 'status-active' }}">{{ number_format($run->success_count) }}</td>
                        <td class="{{ $run->failure_count > 0 ? 'status-inactive' : '' }}">{{ number_format($run->failure_count) }}</td>
                        <td>{{ $run->duration_ms ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <button
                                    type="button"
                                    class="secondary view-details"
                                    data-run-id="{{ $run->id }}"
                                    data-devices='@json($run->sample_device_ids ?? [])'
                                    data-errors='@json($run->sample_errors ?? [])'
                                    data-log="{{ $run->log_file ? route('dev.push-scheduler.log', $run) : '' }}"
                                >
                                    View details
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="pagination">
                {{ $pushRuns->links() }}
            </div>
        @else
            <p>No runs recorded yet.</p>
        @endif
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

    <div id="run-details-modal" class="modal" aria-hidden="true">
        <div class="modal-content">
            <button type="button" class="close-modal" data-close-modal>&times;</button>
            <h3>Push run details</h3>
            <p class="modal-run-id"></p>
            <div class="modal-section">
                <strong>Sample device IDs</strong>
                <ul class="modal-devices"></ul>
            </div>
            <div class="modal-section">
                <strong>Sample errors</strong>
                <ul class="modal-errors"></ul>
            </div>
            <a href="#" target="_blank" rel="noopener" class="modal-log-link">Open log file</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('run-details-modal');
            if (!modal) {
                return;
            }
            const runText = modal.querySelector('.modal-run-id');
            const deviceList = modal.querySelector('.modal-devices');
            const errorList = modal.querySelector('.modal-errors');
            const logLink = modal.querySelector('.modal-log-link');
            const closeButtons = modal.querySelectorAll('[data-close-modal]');

            document.querySelectorAll('.view-details').forEach(button => {
                button.addEventListener('click', () => {
                    const devices = JSON.parse(button.getAttribute('data-devices') || '[]');
                    const errors = JSON.parse(button.getAttribute('data-errors') || '[]');
                    const logUrl = button.getAttribute('data-log') || '#';
                    const runId = button.getAttribute('data-run-id');

                    runText.textContent = `Run ${runId}`;
                    const renderList = (target, items, fallback) => {
                        target.innerHTML = '';
                        if (!items.length) {
                            const li = document.createElement('li');
                            li.textContent = fallback;
                            target.appendChild(li);
                            return;
                        }
                        items.forEach(item => {
                            const li = document.createElement('li');
                            li.textContent = item;
                            target.appendChild(li);
                        });
                    };
                    renderList(deviceList, devices, 'No samples available.');
                    renderList(errorList, errors, 'No errors recorded.');
                    logLink.href = logUrl;
                    logLink.classList.toggle('disabled', !logUrl);

                    modal.setAttribute('aria-hidden', 'false');
                    modal.classList.add('open');
                });
            });

            const closeModal = () => {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('open');
            };

            closeButtons.forEach(button => button.addEventListener('click', closeModal));
            modal.addEventListener('click', event => {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && modal.classList.contains('open')) {
                    closeModal();
                }
            });
        });
    </script>
</body>
</html>
