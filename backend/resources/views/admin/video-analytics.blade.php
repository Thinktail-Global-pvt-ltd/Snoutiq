@extends('layouts.admin-panel')

@section('page-title', 'Video Consult Lifecycle Analytics')

@section('content')
@php
    $overview = $overviewMetrics ?? [];
    $userLifecycle = $userLifecycleSteps ?? [];
    $doctorLifecycle = $doctorLifecycleSteps ?? [];
    $conversionMetrics = $conversionBenchmarks ?? [];
    $recentUserEvents = $recentUserTimeline ?? [];
    $recentDoctorEvents = $recentDoctorTimeline ?? [];
    $dropOffInsights = $dropOffBreakdown ?? [];

    $summaryCards = [
        [
            'label' => 'Registered Users',
            'value' => number_format(data_get($overview, 'users.total', 0)),
            'description' => 'All pet parents and caretakers inside the platform.',
            'icon' => 'bi-people-fill',
            'theme' => 'primary',
        ],
        [
            'label' => 'Active Doctors',
            'value' => number_format(data_get($overview, 'doctors.active', 0)),
            'description' => 'Doctors with at least one consultation in the selected range.',
            'icon' => 'bi-heart-pulse-fill',
            'theme' => 'success',
        ],
        [
            'label' => 'Scheduled Video Sessions',
            'value' => number_format(data_get($overview, 'video_sessions.total', 0)),
            'description' => 'All sessions created through the video scheduling flow.',
            'icon' => 'bi-camera-video-fill',
            'theme' => 'info',
        ],
        [
            'label' => 'Completed Consults',
            'value' => number_format(data_get($overview, 'video_sessions.completed', 0)),
            'description' => 'Sessions that reached an ended state with both parties present.',
            'icon' => 'bi-check-circle-fill',
            'theme' => 'warning',
        ],
    ];

    $formatPercent = static fn ($value) => isset($value) ? number_format($value, 1) . '%' : '—';
    $formatDuration = static fn ($minutes) => isset($minutes) ? number_format($minutes, 1) . ' min' : '—';
@endphp

<div class="d-flex flex-column gap-4">
    <div class="row g-4">
        @foreach ($summaryCards as $card)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar-sm rounded-circle bg-{{ $card['theme'] }}-subtle text-{{ $card['theme'] }}-emphasis d-inline-flex align-items-center justify-content-center">
                                <i class="bi {{ $card['icon'] }} fs-4"></i>
                            </span>
                            <div>
                                <p class="text-muted text-uppercase small mb-1">{{ $card['label'] }}</p>
                                <h2 class="fw-bold mb-0">{{ $card['value'] }}</h2>
                            </div>
                        </div>
                        <p class="mb-0 text-muted small">{{ $card['description'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 fw-semibold mb-1">User Lifecycle Funnel</h2>
                    <p class="text-muted small mb-0">Step-by-step progress of every user through the video consultation flow.</p>
                </div>
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <i class="bi bi-clock-history"></i>
                    <span>Last updated: {{ data_get($overview, 'meta.refreshed_at', '—') }}</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Step</th>
                            <th class="text-nowrap">Users Reached</th>
                            <th class="text-nowrap">Conversion from Previous</th>
                            <th class="text-nowrap">Cumulative Conversion</th>
                            <th class="text-nowrap">Avg. Time Spent</th>
                            <th class="text-nowrap">Drop-Off Reasons</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($userLifecycle as $step)
                            <tr>
                                <td class="fw-semibold">{{ data_get($step, 'label', '—') }}</td>
                                <td>{{ number_format(data_get($step, 'users', 0)) }}</td>
                                <td>{{ $formatPercent(data_get($step, 'conversion_step')) }}</td>
                                <td>{{ $formatPercent(data_get($step, 'conversion_total')) }}</td>
                                <td>{{ $formatDuration(data_get($step, 'avg_time_minutes')) }}</td>
                                <td class="text-muted small">{{ data_get($step, 'top_reason', '—') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No lifecycle data available for the selected range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 fw-semibold mb-1">Doctor Activation Journey</h2>
                    <p class="text-muted small mb-0">Track how providers progress from registration to successful consultations.</p>
                </div>
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <i class="bi bi-calendar-range"></i>
                    <span>Reporting window: {{ data_get($overview, 'meta.window_label', '—') }}</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Milestone</th>
                            <th class="text-nowrap">Doctors Reached</th>
                            <th class="text-nowrap">Conversion from Previous</th>
                            <th class="text-nowrap">Avg. Activation Time</th>
                            <th class="text-nowrap">Quality Score</th>
                            <th class="text-nowrap">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($doctorLifecycle as $step)
                            <tr>
                                <td class="fw-semibold">{{ data_get($step, 'label', '—') }}</td>
                                <td>{{ number_format(data_get($step, 'doctors', 0)) }}</td>
                                <td>{{ $formatPercent(data_get($step, 'conversion_step')) }}</td>
                                <td>{{ $formatDuration(data_get($step, 'avg_time_minutes')) }}</td>
                                <td>{{ $formatPercent(data_get($step, 'quality_score')) }}</td>
                                <td class="text-muted small">{{ data_get($step, 'note', '—') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Doctor journey data is not available yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-semibold mb-0">Conversion Benchmarks</h2>
                        <span class="badge text-bg-light text-uppercase">Comparative</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">Stage</th>
                                    <th class="text-nowrap">Current</th>
                                    <th class="text-nowrap">Target</th>
                                    <th class="text-nowrap">Delta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($conversionMetrics as $metric)
                                    @php
                                        $current = data_get($metric, 'current');
                                        $target = data_get($metric, 'target');
                                        $delta = isset($current, $target) ? $current - $target : null;
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ data_get($metric, 'label', '—') }}</td>
                                        <td>{{ $formatPercent($current) }}</td>
                                        <td>{{ $formatPercent($target) }}</td>
                                        <td>
                                            @if (isset($delta))
                                                <span class="badge text-bg-{{ $delta >= 0 ? 'success' : 'danger' }}-subtle text-{{ $delta >= 0 ? 'success' : 'danger' }}-emphasis">
                                                    {{ ($delta >= 0 ? '+' : '') . number_format($delta, 1) }}%
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No benchmark data provided.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-semibold mb-0">Drop-Off Insights</h2>
                        <span class="badge text-bg-light text-uppercase">User Sentiment</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">Stage</th>
                                    <th class="text-nowrap">Users Lost</th>
                                    <th class="text-nowrap">Share of Funnel</th>
                                    <th class="text-nowrap">Primary Cause</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dropOffInsights as $insight)
                                    <tr>
                                        <td class="fw-semibold">{{ data_get($insight, 'label', '—') }}</td>
                                        <td>{{ number_format(data_get($insight, 'users', 0)) }}</td>
                                        <td>{{ $formatPercent(data_get($insight, 'share')) }}</td>
                                        <td class="text-muted small">{{ data_get($insight, 'reason', '—') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No drop-off analysis captured yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-semibold mb-0">Recent User Timeline</h2>
                        <span class="badge text-bg-light text-uppercase">Live Feed</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">User</th>
                                    <th class="text-nowrap">Event</th>
                                    <th class="text-nowrap">Step</th>
                                    <th class="text-nowrap">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentUserEvents as $event)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ data_get($event, 'name', '—') }}</div>
                                            <div class="text-muted small">ID: {{ data_get($event, 'id', '—') }}</div>
                                        </td>
                                        <td class="text-muted small">{{ data_get($event, 'description', '—') }}</td>
                                        <td>{{ data_get($event, 'step', '—') }}</td>
                                        <td class="text-nowrap">{{ data_get($event, 'time', '—') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No recent user events to display.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-semibold mb-0">Recent Doctor Timeline</h2>
                        <span class="badge text-bg-light text-uppercase">Live Feed</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">Doctor</th>
                                    <th class="text-nowrap">Event</th>
                                    <th class="text-nowrap">Stage</th>
                                    <th class="text-nowrap">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentDoctorEvents as $event)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ data_get($event, 'name', '—') }}</div>
                                            <div class="text-muted small">ID: {{ data_get($event, 'id', '—') }}</div>
                                        </td>
                                        <td class="text-muted small">{{ data_get($event, 'description', '—') }}</td>
                                        <td>{{ data_get($event, 'stage', '—') }}</td>
                                        <td class="text-nowrap">{{ data_get($event, 'time', '—') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No doctor events logged for the period.</td>
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
