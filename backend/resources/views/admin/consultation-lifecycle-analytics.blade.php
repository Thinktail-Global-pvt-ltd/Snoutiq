@extends('layouts.admin-panel')

@section('page-title', 'Consultation Lifecycle Analytics')

@push('styles')
<style>
    .lifecycle-table td,
    .lifecycle-table th {
        vertical-align: top;
    }
    .event-block {
        border: 1px solid #e2e8f0;
        border-radius: 0.65rem;
        padding: 0.55rem 0.65rem;
        margin-bottom: 0.5rem;
        background: #f8fafc;
    }
    .event-block:last-child {
        margin-bottom: 0;
    }
    .json-preview {
        max-height: 180px;
        overflow: auto;
        background: #0f172a;
        color: #e2e8f0;
        border-radius: 0.55rem;
        padding: 0.65rem;
        font-size: 0.75rem;
        margin-bottom: 0.5rem;
    }
    .json-preview:last-child {
        margin-bottom: 0;
    }
    .event-summary-card .card-body {
        padding: 0.85rem;
    }
    .event-summary-card h3 {
        font-size: 0.92rem;
        margin-bottom: 0.6rem;
    }
    @media (max-width: 991.98px) {
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
    $total = $transactions->count();
    $typeFilter = data_get($filters, 'type', 'all');
    $limitFilter = (int) data_get($filters, 'limit', 200);
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
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Consultation Lifecycle Event Tracking</h2>
                        <p class="text-muted mb-0">
                            Base dataset: <code>transactions</code> where <code>type</code> or <code>metadata.order_type</code> is
                            <strong>video_consult</strong> or <strong>excell_export_campaign</strong>.
                            Joins used: <code>transactions.channel_name = call_sessions.channel_name</code>,
                            then to <code>calls</code> and <code>prescriptions.call_session</code>.
                        </p>
                    </div>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($total) }} records</span>
                </div>

                <form method="GET" action="{{ route('admin.analytics.consultation-lifecycle') }}" class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label mb-1">Type</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="all" @selected($typeFilter === 'all')>All (video_consult + excell_export_campaign)</option>
                            <option value="video_consult" @selected($typeFilter === 'video_consult')>video_consult</option>
                            <option value="excell_export_campaign" @selected($typeFilter === 'excell_export_campaign')>excell_export_campaign</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label mb-1">Limit</label>
                        <input type="number" name="limit" min="1" max="500" class="form-control form-control-sm" value="{{ $limitFilter }}">
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                        <a href="{{ route('admin.analytics.consultation-lifecycle') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach($joinAvailability as $joinKey => $isAvailable)
                        <span class="badge {{ $isAvailable ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-danger-subtle text-danger-emphasis' }}">
                            {{ $joinKey }}: {{ $isAvailable ? 'available' : 'missing' }}
                        </span>
                    @endforeach
                </div>
                <p class="text-muted small mb-0">
                    <strong>Note:</strong> <code>notification_sent</code>, <code>review_requested</code>, and <code>review_submitted</code>
                    combine direct joins and log/feedback lookups. Rows marked <code>Not Secure</code> are inferred.
                </p>
            </div>
        </div>
    </div>

    @foreach($eventSummary as $eventKey => $summary)
        @php
            $captured = (int) data_get($summary, 'captured', 0);
            $secure = (int) data_get($summary, 'secure', 0);
            $capturedPct = $total > 0 ? round(($captured / $total) * 100, 1) : 0;
            $securePct = $total > 0 ? round(($secure / $total) * 100, 1) : 0;
        @endphp
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm event-summary-card h-100">
                <div class="card-body">
                    <h3 class="fw-semibold mb-2">{{ data_get($summary, 'label', $eventKey) }}</h3>
                    <div class="small">
                        <div class="mb-1">
                            <span class="badge text-bg-primary-subtle text-primary-emphasis">{{ number_format($captured) }} / {{ number_format($total) }}</span>
                            <span class="text-muted">Captured ({{ $capturedPct }}%)</span>
                        </div>
                        <div>
                            <span class="badge text-bg-success-subtle text-success-emphasis">{{ number_format($secure) }} / {{ number_format($total) }}</span>
                            <span class="text-muted">Secure ({{ $securePct }}%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

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
                                    <th>User / Pet</th>
                                    <th>Consultation Assigned To Vet</th>
                                    <th>Lifecycle Events</th>
                                    <th>Joined Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $txn)
                                    @php
                                        $eventKeys = [
                                            'consultation_created',
                                            'consultation_assigned_to_vet',
                                            'call_started',
                                            'call_completed',
                                            'prescription_uploaded',
                                            'notification_sent',
                                            'review_requested',
                                            'review_submitted',
                                        ];
                                        $callSessionDetails = $txn->getAttribute('joined_call_session_details') ?? [];
                                        $callDetails = $txn->getAttribute('joined_call_details') ?? [];
                                        $prescriptionDetails = $txn->getAttribute('joined_prescription_details') ?? [];
                                    @endphp
                                    <tr>
                                        <td data-label="Transaction">
                                            <div class="fw-semibold">#{{ $txn->id }}</div>
                                            <div class="text-muted small">
                                                <div>Type: {{ $txn->type ?? data_get($txn->metadata, 'order_type', 'n/a') }}</div>
                                                <div>Status: {{ strtoupper((string) ($txn->status ?? 'n/a')) }}</div>
                                                <div>Created: {{ $formatTimestamp($txn->created_at) }}</div>
                                                <div>Channel: <code>{{ $txn->channel_name ?? '—' }}</code></div>
                                            </div>
                                        </td>
                                        <td data-label="User / Pet">
                                            <div class="fw-semibold">{{ $txn->user->name ?? '—' }}</div>
                                            <div class="text-muted small">
                                                <div>User ID: {{ $txn->user_id ?? '—' }}</div>
                                                <div>Phone: {{ $txn->user->phone ?? '—' }}</div>
                                                <div>Pet: {{ $txn->pet->name ?? '—' }} (ID: {{ $txn->pet_id ?? '—' }})</div>
                                                <div>Doctor: {{ $txn->doctor->doctor_name ?? '—' }} (ID: {{ $txn->doctor_id ?? '—' }})</div>
                                            </div>
                                        </td>
                                        <td data-label="Consultation Assigned To Vet">
                                            @php
                                                $assignedAt = $txn->getAttribute('event_consultation_assigned_to_vet_at');
                                                $assignedCaptured = (bool) $txn->getAttribute('event_consultation_assigned_to_vet_captured');
                                                $assignedSecure = (bool) $txn->getAttribute('event_consultation_assigned_to_vet_secure');
                                            @endphp
                                            <div class="d-flex flex-wrap gap-2 mb-1">
                                                <span class="badge {{ $assignedCaptured ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-danger-subtle text-danger-emphasis' }}">
                                                    {{ $assignedCaptured ? 'Captured' : 'Missing' }}
                                                </span>
                                                <span class="badge {{ $assignedSecure ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-warning-subtle text-warning-emphasis' }}">
                                                    {{ $assignedSecure ? 'Secure' : 'Not Secure' }}
                                                </span>
                                            </div>
                                            <div class="text-muted small">
                                                <div>Timestamp: {{ $formatTimestamp($assignedAt) }}</div>
                                                <div>Source: <code>{{ $txn->getAttribute('event_consultation_assigned_to_vet_source') ?? '—' }}</code></div>
                                            </div>
                                        </td>
                                        <td data-label="Lifecycle Events">
                                            @foreach($eventKeys as $eventKey)
                                                @php
                                                    $eventLabel = data_get($eventDefinitions, $eventKey, $eventKey);
                                                    $eventAt = $txn->getAttribute("event_{$eventKey}_at");
                                                    $eventSource = $txn->getAttribute("event_{$eventKey}_source");
                                                    $eventCaptured = (bool) $txn->getAttribute("event_{$eventKey}_captured");
                                                    $eventSecure = (bool) $txn->getAttribute("event_{$eventKey}_secure");
                                                @endphp
                                                <div class="event-block">
                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                        <span class="fw-semibold small">{{ $eventLabel }}</span>
                                                        <span class="badge {{ $eventCaptured ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-danger-subtle text-danger-emphasis' }}">
                                                            {{ $eventCaptured ? 'Captured' : 'Missing' }}
                                                        </span>
                                                        <span class="badge {{ $eventSecure ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-warning-subtle text-warning-emphasis' }}">
                                                            {{ $eventSecure ? 'Secure' : 'Not Secure' }}
                                                        </span>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <div>Timestamp: {{ $formatTimestamp($eventAt) }}</div>
                                                        <div>Source: <code>{{ $eventSource ?? '—' }}</code></div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td data-label="Joined Data">
                                            <details class="mb-2">
                                                <summary class="fw-semibold">
                                                    call_sessions
                                                    <span class="text-muted small">(count: {{ (int) ($txn->getAttribute('joined_call_session_count') ?? 0) }})</span>
                                                </summary>
                                                @if(!empty($callSessionDetails))
                                                    <pre class="json-preview">{{ json_encode($callSessionDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                @else
                                                    <div class="text-muted small mt-1">No joined call_session data.</div>
                                                @endif
                                            </details>

                                            <details class="mb-2">
                                                <summary class="fw-semibold">
                                                    calls
                                                    <span class="text-muted small">(count: {{ (int) ($txn->getAttribute('joined_call_count') ?? 0) }})</span>
                                                </summary>
                                                @if(!empty($callDetails))
                                                    <pre class="json-preview">{{ json_encode($callDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                @else
                                                    <div class="text-muted small mt-1">No joined call data.</div>
                                                @endif
                                            </details>

                                            <details>
                                                <summary class="fw-semibold">
                                                    prescriptions
                                                    <span class="text-muted small">(count: {{ (int) ($txn->getAttribute('joined_prescription_count') ?? 0) }})</span>
                                                </summary>
                                                @if(!empty($prescriptionDetails))
                                                    <pre class="json-preview">{{ json_encode($prescriptionDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                @else
                                                    <div class="text-muted small mt-1">No joined prescription data.</div>
                                                @endif
                                            </details>
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
@endsection

