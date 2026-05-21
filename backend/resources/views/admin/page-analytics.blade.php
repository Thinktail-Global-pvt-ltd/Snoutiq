@extends('layouts.admin-panel')

@section('page-title', 'Page Analytics')

@section('content')
@php
    $formatDuration = static function ($seconds): string {
        $seconds = (int) round((float) $seconds);
        if ($seconds <= 0) {
            return '0s';
        }
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;
        if ($minutes < 60) {
            return $minutes . 'm ' . $remainingSeconds . 's';
        }
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;
        return $hours . 'h ' . $remainingMinutes . 'm';
    };

    $cards = [
        ['label' => 'Page visits', 'value' => number_format($summary['total_visits'] ?? 0), 'icon' => 'bi-window-stack', 'theme' => 'primary'],
        ['label' => 'Unique users', 'value' => number_format($summary['unique_users'] ?? 0), 'icon' => 'bi-people', 'theme' => 'success'],
        ['label' => 'Completed sessions', 'value' => number_format($summary['completed_visits'] ?? 0), 'icon' => 'bi-box-arrow-right', 'theme' => 'info'],
        ['label' => 'Avg. time/page', 'value' => $formatDuration($summary['avg_duration_seconds'] ?? 0), 'icon' => 'bi-stopwatch', 'theme' => 'warning'],
        ['label' => 'Button clicks', 'value' => number_format($summary['total_button_clicks'] ?? 0), 'icon' => 'bi-cursor-fill', 'theme' => 'danger'],
    ];
@endphp

@if (!$hasVisits || !$hasClicks)
    <div class="alert alert-warning border-0 shadow-sm">
        Missing analytics table(s).
        @unless($hasVisits) <code>user_page_visits</code> @endunless
        @unless($hasClicks) <code>user_button_clicks</code> @endunless
        Run the tracking migrations before using this report.
    </div>
@endif

<div class="d-flex flex-column gap-4">
    <div class="card admin-card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.analytics.pages') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="days" class="form-label small text-muted text-uppercase">Window</label>
                    <select id="days" name="days" class="form-select">
                        @foreach ([7, 14, 30, 60, 90, 180, 365] as $option)
                            <option value="{{ $option }}" @selected((int) $days === $option)>Last {{ $option }} days</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="page" class="form-label small text-muted text-uppercase">Page</label>
                    <select id="page" name="page" class="form-select">
                        <option value="">All pages</option>
                        @foreach ($pageOptions as $pageOption)
                            <option value="{{ $pageOption }}" @selected($pageFilter === $pageOption)>{{ $pageOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label for="user_id" class="form-label small text-muted text-uppercase">User ID</label>
                    <input
                        id="user_id"
                        name="user_id"
                        type="number"
                        min="1"
                        value="{{ $userIdFilter ?? '' }}"
                        class="form-control"
                        placeholder="1471"
                    >
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-funnel me-1"></i> Apply
                    </button>
                    <a href="{{ route('admin.analytics.pages') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        @foreach ($cards as $card)
            <div class="col-12 col-md-6 col-xl">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-sm rounded-circle bg-{{ $card['theme'] }}-subtle text-{{ $card['theme'] }}-emphasis d-inline-flex align-items-center justify-content-center">
                            <i class="bi {{ $card['icon'] }} fs-4"></i>
                        </span>
                        <div>
                            <p class="text-muted text-uppercase small mb-1">{{ $card['label'] }}</p>
                            <h2 class="fw-bold mb-0">{{ $card['value'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-semibold mb-1">Page Wise Performance</h2>
                    <p class="text-muted small mb-0">Time spent comes from enter/exit events. Button clicks come from the click tracking API.</p>
                </div>
                <span class="badge text-bg-light align-self-start">{{ $summary['window_label'] ?? '' }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Page</th>
                            <th class="text-end">Visits</th>
                            <th class="text-end">Users</th>
                            <th class="text-end">Completed</th>
                            <th class="text-end">Avg. Time</th>
                            <th class="text-end">Total Time</th>
                            <th class="text-end">Clicks</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pageRows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row->page_name }}</td>
                                <td class="text-end">{{ number_format($row->visits ?? 0) }}</td>
                                <td class="text-end">{{ number_format($row->users ?? 0) }}</td>
                                <td class="text-end">{{ number_format($row->completed_visits ?? 0) }}</td>
                                <td class="text-end">{{ $formatDuration($row->avg_duration_seconds ?? 0) }}</td>
                                <td class="text-end">{{ $formatDuration($row->total_duration_seconds ?? 0) }}</td>
                                <td class="text-end">{{ number_format($row->button_clicks ?? 0) }}</td>
                                <td class="text-muted small">{{ $row->last_seen_at ? \Illuminate\Support\Carbon::parse($row->last_seen_at)->format('d M, H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No page analytics found for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3">Top Button Clicks</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Page</th>
                                    <th>Button</th>
                                    <th class="text-end">Clicks</th>
                                    <th class="text-end">Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topButtons as $button)
                                    <tr>
                                        <td class="text-muted small">{{ $button->page_name }}</td>
                                        <td class="fw-semibold">{{ $button->button_name }}</td>
                                        <td class="text-end">{{ number_format($button->clicks ?? 0) }}</td>
                                        <td class="text-end">{{ number_format($button->users ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No button clicks found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3">Recent Page Sessions</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Page</th>
                                    <th>Route</th>
                                    <th class="text-end">Time</th>
                                    <th>Entered</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentVisits as $visit)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">#{{ $visit->user_id }} {{ $visit->user_name ?: '' }}</div>
                                            <div class="text-muted small">{{ $visit->user_phone ?: '—' }}</div>
                                        </td>
                                        <td>{{ $visit->page_name }}</td>
                                        <td class="text-muted small">{{ $visit->route_path ?: '—' }}</td>
                                        <td class="text-end">{{ $visit->exited_at ? $formatDuration($visit->duration_seconds ?? 0) : 'Open' }}</td>
                                        <td class="text-muted small">{{ $visit->entered_at ? \Illuminate\Support\Carbon::parse($visit->entered_at)->format('d M, H:i') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No recent page sessions found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
