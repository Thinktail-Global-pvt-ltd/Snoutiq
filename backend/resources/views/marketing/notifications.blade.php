<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Snoutiq • Marketing Notifications</title>
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
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #cbd5f5;
            margin-bottom: 14px;
            font-size: 15px;
            box-sizing: border-box;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
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
        button:hover {
            background: #1d4ed8;
        }
        button.secondary {
            background: #10b981;
        }
        button.secondary:hover {
            background: #059669;
        }
        button.danger {
            background: #ef4444;
        }
        button.danger:hover {
            background: #dc2626;
        }
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <h1>Marketing Notifications</h1>
    <p>Send FCM notifications to all users. You can send immediately or schedule them to be sent every 5 minutes.</p>

    @if(session('success'))
        <div class="flash">
            {{ session('success') }}
        </div>
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

    <div class="info-box">
        <p><strong>Note:</strong> Notifications will be sent to all users who have registered FCM tokens. Make sure your notification content is clear and valuable.</p>
    </div>

    @if(!$firebaseStatus['file_exists'] || !$firebaseStatus['is_valid_json'])
        <div class="errors">
            <strong>⚠️ Firebase Configuration Issue!</strong>
            <ul>
                @if(!$firebaseStatus['file_exists'])
                    <li>Firebase credentials file not found at: <code>{{ $firebaseStatus['file_path'] }}</code></li>
                    <li>Please download the service account JSON from Firebase Console and place it at the above path.</li>
                @elseif(!$firebaseStatus['is_valid_json'])
                    <li>Firebase credentials file exists but is not valid JSON.</li>
                    <li>Please verify the file at: <code>{{ $firebaseStatus['file_path'] }}</code></li>
                @endif
            </ul>
        </div>
    @endif

    <!-- Test Notification Form -->
    <form method="POST" action="{{ route('marketing.notifications.test') }}" style="background: #fef3c7; border-left: 4px solid #f59e0b;">
        @csrf
        <h2>🧪 Test Notification (Single Device Token)</h2>
        <p style="color: #92400e; margin-bottom: 16px;">Test with a specific device token from <a href="{{ route('dev.fcm-test') }}" target="_blank" style="color: #b45309;">/dev/fcm-test</a>. This is useful for testing before sending to all users.</p>
        
        <label for="test_token">Device FCM Token *</label>
        <input type="text" id="test_token" name="token" value="{{ old('token') }}" required maxlength="500" placeholder="Paste FCM token from /dev/fcm-test page">
        
        <label for="test_title">Title *</label>
        <input type="text" id="test_title" name="title" value="{{ old('title') }}" required maxlength="120" placeholder="e.g., Test Notification">
        
        <label for="test_body">Message *</label>
        <textarea id="test_body" name="body" required maxlength="500" placeholder="Enter your test notification message here...">{{ old('body') }}</textarea>
        
        <div class="button-group">
            <button type="submit" style="background: #f59e0b;">Send Test Notification</button>
        </div>
    </form>

    @if($recentRuns->count() > 0 && $recentRuns->first()->failure_count > 0 && $recentRuns->first()->success_count == 0)
        <div class="errors">
            <strong>⚠️ All notifications failed!</strong>
            <p>This usually means:</p>
            <ul>
                <li>FCM tokens in the database are invalid or expired</li>
                <li>Firebase credentials are not properly configured</li>
                <li>Check the log file for detailed error messages</li>
            </ul>
            <p><strong>To fix:</strong></p>
            <ul>
                <li>Verify Firebase service account file exists at: <code>storage/app/firebase/service-account.json</code></li>
                <li>Check that FCM tokens are valid by testing at <a href="{{ route('dev.fcm-test') }}" target="_blank">/dev/fcm-test</a></li>
                <li>Review error details in the "Recent Notification Runs" table below</li>
            </ul>
        </div>
    @endif

    <!-- Send Now Form -->
    <form method="POST" action="{{ route('marketing.notifications.send-now') }}">
        @csrf
        <h2>Send Notification Now</h2>
        <p style="color: #64748b; margin-bottom: 16px;">Send a notification to all users immediately.</p>
        
        <label for="send_now_title">Title *</label>
        <input type="text" id="send_now_title" name="title" value="{{ old('title') }}" required maxlength="120" placeholder="e.g., Special Offer Today!">
        
        <label for="send_now_body">Message *</label>
        <textarea id="send_now_body" name="body" required maxlength="500" placeholder="Enter your notification message here...">{{ old('body') }}</textarea>
        
        <div class="button-group">
            <button type="submit">Send to All Users Now</button>
        </div>
    </form>

    <!-- Schedule Every 5 Minutes Form -->
    <form method="POST" action="{{ route('marketing.notifications.schedule-5min') }}">
        @csrf
        <h2>Schedule Notification Every 5 Minutes</h2>
        <p style="color: #64748b; margin-bottom: 16px;">Schedule a notification to be automatically sent every 5 minutes to all users.</p>
        
        <label for="schedule_title">Title *</label>
        <input type="text" id="schedule_title" name="title" value="{{ old('title') }}" required maxlength="120" placeholder="e.g., Weekly Reminder">
        
        <label for="schedule_body">Message *</label>
        <textarea id="schedule_body" name="body" required maxlength="500" placeholder="Enter your notification message here...">{{ old('body') }}</textarea>
        
        <div class="button-group">
            <button type="submit" class="secondary">Schedule Every 5 Minutes</button>
        </div>
    </form>

    <!-- Active Schedules -->
    @if($activeSchedules->count() > 0)
        <div class="card">
            <h2>Active Scheduled Notifications</h2>
            <div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b; margin-bottom: 16px;">
                <p style="color: #92400e; margin: 0;">
                    <strong>⚠️ Important:</strong> For automated notifications to work, you must run the queue worker in a separate terminal:
                    <code style="background: #fde68a; padding: 2px 6px; border-radius: 4px;">php artisan queue:work</code>
                    <br>Click "Run Now" to test immediately without waiting 5 minutes.
                </p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Last Run</th>
                        <th>Next Run</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeSchedules as $schedule)
                        <tr>
                            <td>{{ $schedule->title }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($schedule->body, 50) }}</td>
                            <td>
                                @if($schedule->is_active)
                                    <span class="status-active">Active</span>
                                @else
                                    <span class="status-inactive">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $schedule->last_run_at ? $schedule->last_run_at->format('Y-m-d H:i:s') : 'Never' }}</td>
                            <td>{{ $schedule->next_run_at ? $schedule->next_run_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    @if($schedule->is_active)
                                        <form method="POST" action="{{ route('marketing.notifications.run-now', $schedule) }}" style="display: inline;">
                                            @csrf
                                            @method('POST')
                                            <button type="submit" class="secondary" style="padding: 6px 12px; font-size: 13px; background: #10b981;">Run Now</button>
                                        </form>
                                        <form method="POST" action="{{ route('marketing.notifications.stop', $schedule) }}" style="display: inline;">
                                            @csrf
                                            @method('POST')
                                            <button type="submit" class="danger" style="padding: 6px 12px; font-size: 13px;">Stop</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Recent Notification Runs -->
    @if($recentRuns->count() > 0)
        <div class="card">
            <h2>Recent Notification Runs</h2>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Trigger</th>
                        <th>Started At</th>
                        <th>Targeted</th>
                        <th>Success</th>
                        <th>Failed</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentRuns as $run)
                        <tr>
                            <td>{{ $run->title }}</td>
                            <td>{{ $run->trigger }}</td>
                            <td>{{ $run->started_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $run->targeted_count }}</td>
                            <td style="color: #166534;">{{ $run->success_count }}</td>
                            <td style="color: #b91c1c;">
                                {{ $run->failure_count }}
                                @if($run->failure_count > 0 && isset($run->sample_errors) && !empty($run->sample_errors))
                                    <br><small style="color: #991b1b; font-size: 11px;">
                                        @foreach(array_slice($run->sample_errors, 0, 2) as $error)
                                            {{ \Illuminate\Support\Str::limit($error, 50) }}<br>
                                        @endforeach
                                    </small>
                                @endif
                            </td>
                            <td>{{ $run->duration_ms }}ms</td>
                            <td>
                                @if($run->log_file && file_exists($run->log_file))
                                    <a href="{{ route('dev.push-scheduler.log', $run) }}" target="_blank" style="color: #2563eb; text-decoration: none; font-size: 13px;">View Log</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</body>
</html>

