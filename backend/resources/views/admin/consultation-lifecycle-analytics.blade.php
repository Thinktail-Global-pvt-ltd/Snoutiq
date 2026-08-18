@extends('layouts.admin-panel')

@section('page-title', 'Consultation Lifecycle Analytics')

@push('styles')
<style>
    .lifecycle-kpi-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.9rem;
        padding: 1rem;
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        height: 100%;
    }
    .lifecycle-kpi-label {
        font-size: 0.75rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    .lifecycle-kpi-value {
        font-size: 1.5rem;
        line-height: 1;
        font-weight: 800;
        color: #0f172a;
    }
    .lifecycle-kpi-sub {
        color: #475569;
        font-size: 0.8rem;
        margin-top: 0.4rem;
    }
    .chart-panel {
        border: 1px solid #e5e7eb;
        border-radius: 0.9rem;
        background: #ffffff;
        padding: 1rem;
        height: 100%;
    }
    .chart-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }
    .chart-subtitle {
        color: #64748b;
        font-size: 0.78rem;
        margin-bottom: 0.75rem;
    }
    .chart-canvas-wrap {
        position: relative;
        height: 290px;
    }
    .lifecycle-table td,
    .lifecycle-table th {
        vertical-align: top;
    }
    .event-block {
        border: 1px solid #e5e7eb;
        border-radius: 0.65rem;
        padding: 0.55rem 0.65rem;
        margin-bottom: 0.5rem;
        background: #ffffff;
    }
    .event-block:last-child {
        margin-bottom: 0;
    }
    .event-block.done {
        background: #ecfdf3;
        border-color: #86efac;
    }
    /* Stunning visual timeline styles */
    .stunning-timeline {
        position: relative;
        padding-left: 1.25rem;
        margin-top: 0.25rem;
        border-left: 2px solid #e2e8f0;
    }
    .stunning-timeline-item {
        position: relative;
        margin-bottom: 0.75rem;
    }
    .stunning-timeline-item:last-child {
        margin-bottom: 0;
    }
    .stunning-timeline-dot {
        position: absolute;
        left: calc(-1.25rem - 5px);
        top: 6px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #cbd5e1;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #cbd5e1;
        transition: all 0.2s ease;
    }
    .stunning-timeline-item.done .stunning-timeline-dot {
        background: #10b981;
        box-shadow: 0 0 0 2px #10b981;
    }
    .stunning-timeline-content {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.4rem;
        padding: 0.45rem 0.6rem;
        transition: all 0.2s ease;
    }
    .stunning-timeline-item.done .stunning-timeline-content {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }
    .stunning-timeline-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #475569;
    }
    .stunning-timeline-item.done .stunning-timeline-title {
        color: #15803d;
    }
    .stunning-timeline-badges {
        display: flex;
        gap: 3px;
    }
    .stunning-badge {
        font-size: 0.62rem;
        padding: 0.08rem 0.3rem;
        border-radius: 3px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .stunning-badge.captured.done {
        background: #dcfce7;
        color: #15803d;
    }
    .stunning-badge.captured.pending {
        background: #f1f5f9;
        color: #64748b;
    }
    .stunning-badge.secure.done {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .stunning-badge.secure.pending {
        background: #f1f5f9;
        color: #64748b;
    }

    .status-badge {
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.22rem 0.52rem;
        line-height: 1.2;
    }
    .status-badge.done {
        background: #dcfce7;
        color: #166534;
        border-color: #86efac;
    }
    .status-badge.pending {
        background: #ffffff;
        color: #334155;
        border-color: #e5e7eb;
    }
    .wa-attempt {
        border: 1px solid #e5e7eb;
        border-radius: 0.55rem;
        padding: 0.5rem 0.6rem;
        margin-bottom: 0.45rem;
        background: #ffffff;
    }
    .wa-attempt.done {
        background: #ecfdf3;
        border-color: #86efac;
    }
    .wa-attempt:last-child {
        margin-bottom: 0;
    }
    @media (max-width: 991.98px) {
        .chart-canvas-wrap {
            height: 240px;
        }
        .lifecycle-table thead {
            display: none;
        }
        .lifecycle-table,
        .lifecycle-table tbody,
        .lifecycle-table tr,
        .lifecycle-table td {
            display: block;
            width: 100%;
        }
        .lifecycle-table tr {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            margin-bottom: 0.9rem;
            box-shadow: 0 8px 20px rgba(2, 6, 23, 0.06);
            overflow: hidden;
        }
        .lifecycle-table td {
            border: 0;
            border-bottom: 1px dashed #e5e7eb;
            padding: 0.7rem 0.8rem;
            position: relative;
            padding-left: 42%;
            min-height: 2.65rem;
            overflow-wrap: anywhere;
        }
        .lifecycle-table td:last-child {
            border-bottom: 0;
        }
        .lifecycle-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 0.8rem;
            top: 0.72rem;
            width: calc(42% - 1rem);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
            line-height: 1.25;
        }
    }
    @media (max-width: 575.98px) {
        .lifecycle-table td {
            padding-left: 0.72rem;
            padding-top: 2.05rem;
        }
        .lifecycle-table td::before {
            position: static;
            display: block;
            width: 100%;
            margin-bottom: 0.33rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $formatTimestamp = static function ($value) {
        if (empty($value)) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y, H:i:s');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $totalTransactions = (int) ($transactions->count() ?? 0);
    $eventSummaryCollection = collect($eventSummary ?? []);

    $eventChartLabels = $eventSummaryCollection
        ->pluck('label')
        ->values()
        ->all();
    $capturedSeries = $eventSummaryCollection
        ->map(fn ($event) => (int) ($event['captured'] ?? 0))
        ->values()
        ->all();
    $secureSeries = $eventSummaryCollection
        ->map(fn ($event) => (int) ($event['secure'] ?? 0))
        ->values()
        ->all();
    $missingSeries = $eventSummaryCollection
        ->map(fn ($event) => max(0, $totalTransactions - (int) ($event['captured'] ?? 0)))
        ->values()
        ->all();

    $eventCompletionSeries = $eventSummaryCollection
        ->map(fn ($event) => $totalTransactions > 0
            ? round(((int) ($event['captured'] ?? 0) / $totalTransactions) * 100, 2)
            : 0
        )
        ->values()
        ->all();

    $txTypeBreakdown = collect($transactions ?? [])
        ->map(function ($txn): string {
            $type = trim((string) ($txn->type ?? data_get($txn, 'metadata.order_type', '')));
            if ($type === '') {
                $type = 'unknown';
            }

            return strtolower($type);
        })
        ->countBy()
        ->sortDesc();

    $txTypeLabels = $txTypeBreakdown
        ->keys()
        ->map(fn ($type) => \Illuminate\Support\Str::title(str_replace(['_', '-'], ' ', (string) $type)))
        ->values()
        ->all();
    $txTypeValues = $txTypeBreakdown
        ->values()
        ->map(fn ($count) => (int) $count)
        ->values()
        ->all();

    $userCreatedCaptured = (int) data_get($eventSummary, 'user_created.captured', 0);
    $petAddedCaptured = (int) data_get($eventSummary, 'pet_added.captured', 0);
    $consultationCreatedCaptured = (int) data_get($eventSummary, 'consultation_created.captured', 0);
    $notificationSentCaptured = (int) data_get($eventSummary, 'notification_sent.captured', 0);
    $reviewSubmittedCaptured = (int) data_get($eventSummary, 'review_submitted.captured', 0);

    $coveragePercent = static fn (int $count): string => $totalTransactions > 0
        ? number_format(($count / $totalTransactions) * 100, 1) . '%'
        : '0.0%';
@endphp

<div class="d-flex flex-column gap-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h2 class="h5 mb-1">Lifecycle Tracker</h2>
                    @if(isset($filters['user_id']) && $transactions->isNotEmpty())
                        @php $filteredUser = $transactions->first()->user; @endphp
                        @if($filteredUser)
                            <div class="badge bg-success p-2 mb-2" style="font-size: 0.85rem; color: #fff;">
                                Filtering by User: {{ $filteredUser->name }} ({{ $filteredUser->phone }})
                            </div>
                        @endif
                    @endif
                    <p class="text-muted small mb-0">
                        Includes onboarding milestones: <code>users.created_at</code> (User Created)
                        and <code>pets.created_at</code> (Pet Added).
                    </p>
                </div>
                <form class="d-flex align-items-center gap-2 flex-wrap" method="GET" action="{{ route('admin.analytics.consultation-lifecycle') }}">
                    @if(isset($filters['user_id']))
                        <input type="hidden" name="user_id" value="{{ (int) $filters['user_id'] }}">
                    @endif
                    <div>
                        <label class="form-label small text-muted mb-1">Type</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>All types</option>
                            <option value="video_consult" @selected(($filters['type'] ?? 'all') === 'video_consult')>Video consult</option>
                            <option value="excell_export_campaign" @selected(($filters['type'] ?? 'all') === 'excell_export_campaign')>Excel export campaign</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted mb-1">Limit</label>
                        <input
                            type="number"
                            min="1"
                            max="500"
                            name="limit"
                            value="{{ (int) ($filters['limit'] ?? 200) }}"
                            class="form-control form-control-sm"
                            style="width: 110px;"
                        >
                    </div>
                    <div class="d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                        <a href="{{ route('admin.analytics.consultation-lifecycle') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6 col-xl">
            <div class="lifecycle-kpi-card">
                <div class="lifecycle-kpi-label">Total Transactions</div>
                <div class="lifecycle-kpi-value">{{ number_format($totalTransactions) }}</div>
                <div class="lifecycle-kpi-sub">Consultation transactions in current filter.</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="lifecycle-kpi-card">
                <div class="lifecycle-kpi-label">User Created Captured</div>
                <div class="lifecycle-kpi-value">{{ number_format($userCreatedCaptured) }}</div>
                <div class="lifecycle-kpi-sub">Coverage: {{ $coveragePercent($userCreatedCaptured) }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="lifecycle-kpi-card">
                <div class="lifecycle-kpi-label">Pet Added Captured</div>
                <div class="lifecycle-kpi-value">{{ number_format($petAddedCaptured) }}</div>
                <div class="lifecycle-kpi-sub">Coverage: {{ $coveragePercent($petAddedCaptured) }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="lifecycle-kpi-card">
                <div class="lifecycle-kpi-label">Consultation Created</div>
                <div class="lifecycle-kpi-value">{{ number_format($consultationCreatedCaptured) }}</div>
                <div class="lifecycle-kpi-sub">Coverage: {{ $coveragePercent($consultationCreatedCaptured) }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl">
            <div class="lifecycle-kpi-card">
                <div class="lifecycle-kpi-label">Notification Sent</div>
                <div class="lifecycle-kpi-value">{{ number_format($notificationSentCaptured) }}</div>
                <div class="lifecycle-kpi-sub">Feedback Submitted: {{ $coveragePercent($reviewSubmittedCaptured) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="chart-panel">
                <div class="chart-title">Lifecycle Event Coverage Graph</div>
                <div class="chart-subtitle">Captured vs secure vs missing counts across lifecycle stages.</div>
                <div class="chart-canvas-wrap">
                    <canvas id="lifecycleCoverageChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="chart-panel">
                <div class="chart-title">Transaction Mix Graph</div>
                <div class="chart-subtitle">Distribution by transaction type for the current filter set.</div>
                <div class="chart-canvas-wrap">
                    <canvas id="lifecycleTypeMixChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @if($transactions->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-bar-chart-line display-6 d-block mb-2"></i>
                            <p class="mb-0">No matching consultation transactions found.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle lifecycle-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Transaction</th>
                                        <th>User / Pet / Clinic</th>
                                        <th>Consultation Assigned To Vet</th>
                                        <th>Lifecycle Events Timeline</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $txn)
                                        @php
                                            $eventKeys = [
                                                'user_created',
                                                'pet_added',
                                                'consultation_created',
                                                'consultation_assigned_to_vet',
                                                'call_started',
                                                'call_completed',
                                                'prescription_uploaded',
                                                'notification_sent',
                                                'review_requested',
                                                'review_submitted',
                                            ];
                                        @endphp
                                        <tr>
                                            <td data-label="Transaction">
                                                <div class="fw-semibold">
                                                    @if($txn->id === 0)
                                                        Virtual (No Transaction)
                                                    @else
                                                        #{{ $txn->id }}
                                                    @endif
                                                </div>
                                                <div class="text-muted small">
                                                    <div>Created: {{ $txn->id === 0 ? '—' : $formatTimestamp($txn->created_at) }}</div>
                                                </div>
                                            </td>
                                            <td data-label="User / Pet / Clinic">
                                                <div class="fw-semibold">{{ $txn->user->name ?? '—' }}</div>
                                                <div class="text-muted small">
                                                    <div><strong>Phone:</strong> {{ $txn->user->phone ?? '—' }}</div>
                                                    <div><strong>User Created:</strong> {{ $formatTimestamp(optional($txn->user)->created_at) }}</div>
                                                    <div><strong>Pet:</strong> {{ $txn->pet->name ?? '—' }}</div>
                                                    @if(optional($txn->pet)->reported_symptom)
                                                        <div class="text-danger mt-1"><strong>Symptom:</strong> {{ optional($txn->pet)->reported_symptom }}</div>
                                                    @endif
                                                    <div><strong>Pet Added:</strong> {{ $formatTimestamp(optional($txn->pet)->created_at) }}</div>
                                                    @if($txn->getAttribute('connected_clinic_name'))
                                                        <div class="text-primary mt-1">
                                                            <strong>Clinic:</strong> {{ $txn->getAttribute('connected_clinic_name') }}
                                                            @php $docs = $txn->getAttribute('connected_clinic_doctors'); @endphp
                                                            @if(!empty($docs))
                                                                ({{ implode(', ', $docs) }})
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if($txn->doctor && $txn->doctor->doctor_name)
                                                        <div><strong>Assigned Doctor:</strong> {{ $txn->doctor->doctor_name }}</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td data-label="Consultation Assigned To Vet">
                                                @php
                                                    $assignedAt = $txn->getAttribute('event_consultation_assigned_to_vet_at');
                                                    $assignedCaptured = (bool) $txn->getAttribute('event_consultation_assigned_to_vet_captured');
                                                    $assignedSecure = (bool) $txn->getAttribute('event_consultation_assigned_to_vet_secure');
                                                    $assignedSource = (string) ($txn->getAttribute('event_consultation_assigned_to_vet_source') ?? '');
                                                @endphp
                                                <div class="d-flex flex-wrap gap-2 mb-1">
                                                    <span class="status-badge {{ $assignedCaptured ? 'done' : 'pending' }}">
                                                        {{ $assignedCaptured ? 'Captured' : 'Missing' }}
                                                    </span>
                                                    <span class="status-badge {{ $assignedSecure ? 'done' : 'pending' }}">
                                                        {{ $assignedSecure ? 'Secure' : 'Not Secure' }}
                                                    </span>
                                                </div>
                                                <div class="text-muted small">
                                                    <div>Timestamp: {{ $formatTimestamp($assignedAt) }}</div>
                                                    <div>Source: {{ $assignedSource !== '' ? $assignedSource : '—' }}</div>
                                                </div>
                                            </td>
                                            <td data-label="Lifecycle Events Timeline">
                                                <div class="stunning-timeline">
                                                    @foreach($eventKeys as $eventKey)
                                                        @php
                                                            $eventLabel = data_get($eventDefinitions, $eventKey, $eventKey);
                                                            $eventAt = $txn->getAttribute("event_{$eventKey}_at");
                                                            $eventCaptured = (bool) $txn->getAttribute("event_{$eventKey}_captured");
                                                            $eventSecure = (bool) $txn->getAttribute("event_{$eventKey}_secure");
                                                            $eventSource = (string) ($txn->getAttribute("event_{$eventKey}_source") ?? '');
                                                        @endphp
                                                        <div class="stunning-timeline-item {{ $eventCaptured ? 'done' : '' }}">
                                                            <div class="stunning-timeline-dot"></div>
                                                            <div class="stunning-timeline-content">
                                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                                    <span class="stunning-timeline-title">{{ $eventLabel }}</span>
                                                                    <div class="stunning-timeline-badges">
                                                                        <span class="stunning-badge captured {{ $eventCaptured ? 'done' : 'pending' }}">
                                                                            {{ $eventCaptured ? 'Captured' : 'Missing' }}
                                                                        </span>
                                                                        <span class="stunning-badge secure {{ $eventSecure ? 'done' : 'pending' }}">
                                                                            {{ $eventSecure ? 'Secure' : 'Not Secure' }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                @if($eventCaptured)
                                                                    <div class="text-muted mt-1" style="font-size: 0.72rem; line-height: 1.35;">
                                                                        <div><i class="bi bi-clock"></i> {{ $formatTimestamp($eventAt) }}</div>
                                                                        <div class="text-secondary" style="font-size: 0.68rem; font-family: monospace;">Source: {{ $eventSource }}</div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(() => {
    if (typeof window.Chart === 'undefined') {
        return;
    }

    const coverageCanvas = document.getElementById('lifecycleCoverageChart');
    const typeMixCanvas = document.getElementById('lifecycleTypeMixChart');

    const coverageLabels = @json($eventChartLabels);
    const capturedSeries = @json($capturedSeries);
    const secureSeries = @json($secureSeries);
    const missingSeries = @json($missingSeries);
    const completionSeries = @json($eventCompletionSeries);

    if (coverageCanvas && Array.isArray(coverageLabels) && coverageLabels.length) {
        new Chart(coverageCanvas, {
            type: 'bar',
            data: {
                labels: coverageLabels,
                datasets: [
                    {
                        label: 'Captured',
                        data: capturedSeries,
                        backgroundColor: 'rgba(59, 130, 246, 0.75)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                    },
                    {
                        label: 'Secure',
                        data: secureSeries,
                        backgroundColor: 'rgba(16, 185, 129, 0.75)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                    },
                    {
                        label: 'Missing',
                        data: missingSeries,
                        backgroundColor: 'rgba(148, 163, 184, 0.6)',
                        borderColor: 'rgba(100, 116, 139, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                    },
                    {
                        type: 'line',
                        label: 'Completion %',
                        data: completionSeries,
                        yAxisID: 'y1',
                        borderColor: 'rgba(249, 115, 22, 1)',
                        backgroundColor: 'rgba(249, 115, 22, 0.25)',
                        tension: 0.25,
                        borderWidth: 2,
                        pointRadius: 3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                        title: {
                            display: true,
                            text: 'Transactions',
                        },
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            callback: (value) => `${value}%`,
                        },
                        title: {
                            display: true,
                            text: 'Completion Rate',
                        },
                    },
                },
            },
        });
    }

    const typeLabels = @json($txTypeLabels);
    const typeValues = @json($txTypeValues);
    const palette = [
        'rgba(59,130,246,0.8)',
        'rgba(16,185,129,0.8)',
        'rgba(249,115,22,0.8)',
        'rgba(139,92,246,0.8)',
        'rgba(236,72,153,0.8)',
        'rgba(234,179,8,0.8)',
    ];

    if (typeMixCanvas && Array.isArray(typeLabels) && typeLabels.length) {
        new Chart(typeMixCanvas, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [
                    {
                        data: typeValues,
                        backgroundColor: typeLabels.map((_, idx) => palette[idx % palette.length]),
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
            },
        });
    }
})();
</script>
@endpush
