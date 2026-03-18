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
    .wa-attempt {
        border: 1px solid #e5e7eb;
        border-radius: 0.55rem;
        padding: 0.5rem 0.6rem;
        margin-bottom: 0.45rem;
        background: #f8fafc;
    }
    .wa-attempt:last-child {
        margin-bottom: 0;
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
                                    <th>WhatsApp Notifications</th>
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
                                        $whatsAppRows = $txn->getAttribute('whatsapp_notifications_for_channel') ?? [];
                                        $whatsAppStatusSummary = $txn->getAttribute('whatsapp_notification_status_summary') ?? [];
                                        $whatsAppLastStatus = strtolower((string) ($txn->getAttribute('whatsapp_notification_last_status') ?? ''));
                                    @endphp
                                    <tr>
                                        <td data-label="Transaction">
                                            <div class="fw-semibold">#{{ $txn->id }}</div>
                                            <div class="text-muted small">
                                                <div>Created: {{ $formatTimestamp($txn->created_at) }}</div>
                                            </div>
                                        </td>
                                        <td data-label="User / Pet">
                                            <div class="fw-semibold">{{ $txn->user->name ?? '—' }}</div>
                                            <div class="text-muted small">
                                                <div>Phone: {{ $txn->user->phone ?? '—' }}</div>
                                                <div>Pet: {{ $txn->pet->name ?? '—' }}</div>
                                                <div>Doctor: {{ $txn->doctor->doctor_name ?? '—' }}</div>
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
                                            </div>
                                        </td>
                                        <td data-label="Lifecycle Events">
                                            @foreach($eventKeys as $eventKey)
                                                @php
                                                    $eventLabel = data_get($eventDefinitions, $eventKey, $eventKey);
                                                    $eventAt = $txn->getAttribute("event_{$eventKey}_at");
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
                                                    </div>
                                                </div>
                                            @endforeach
                                        </td>
                                        <td data-label="WhatsApp Notifications">
                                            <details>
                                                <summary class="fw-semibold">
                                                    whatsapp_notifications
                                                    <span class="text-muted small">(count: {{ count($whatsAppRows) }})</span>
                                                </summary>
                                                <div class="mt-1">
                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                        @foreach(collect($whatsAppStatusSummary)->sortKeys() as $statusKey => $statusCount)
                                                            @php
                                                                $normalizedStatus = strtolower(trim((string) $statusKey));
                                                                $summaryBadgeClass = match ($normalizedStatus) {
                                                                    'sent' => 'text-bg-success-subtle text-success-emphasis',
                                                                    'failed' => 'text-bg-danger-subtle text-danger-emphasis',
                                                                    'pending', 'queued' => 'text-bg-warning-subtle text-warning-emphasis',
                                                                    default => 'text-bg-secondary-subtle text-secondary-emphasis',
                                                                };
                                                            @endphp
                                                            <span class="badge {{ $summaryBadgeClass }}">
                                                                {{ strtoupper($normalizedStatus !== '' ? $normalizedStatus : 'unknown') }}: {{ (int) $statusCount }}
                                                            </span>
                                                        @endforeach
                                                        @if($whatsAppLastStatus !== '')
                                                            <span class="badge {{ $whatsAppLastStatus === 'sent' ? 'text-bg-success-subtle text-success-emphasis' : 'text-bg-secondary-subtle text-secondary-emphasis' }}">
                                                                Last status: {{ strtoupper($whatsAppLastStatus) }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    @if(!empty($whatsAppRows))
                                                        @foreach($whatsAppRows as $whatsAppRow)
                                                            @php
                                                                $rowStatus = strtolower((string) ($whatsAppRow['status'] ?? 'unknown'));
                                                                $rowStatusBadge = match ($rowStatus) {
                                                                    'sent' => 'text-bg-success-subtle text-success-emphasis',
                                                                    'failed' => 'text-bg-danger-subtle text-danger-emphasis',
                                                                    'pending', 'queued' => 'text-bg-warning-subtle text-warning-emphasis',
                                                                    default => 'text-bg-secondary-subtle text-secondary-emphasis',
                                                                };
                                                            @endphp
                                                            <div class="wa-attempt">
                                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                                    <span class="badge {{ $rowStatusBadge }}">{{ strtoupper($rowStatus) }}</span>
                                                                    <span class="small text-muted">Template: <code>{{ $whatsAppRow['template_name'] ?? '—' }}</code></span>
                                                                    <span class="small text-muted">Type: <code>{{ $whatsAppRow['message_type'] ?? '—' }}</code></span>
                                                                </div>
                                                                <div class="small text-muted">
                                                                    <div>Attempted: {{ $formatTimestamp($whatsAppRow['attempted_at'] ?? null) }}</div>
                                                                    <div>Sent At: {{ $formatTimestamp($whatsAppRow['sent_at'] ?? null) }}</div>
                                                                    <div>HTTP Status: <code>{{ $whatsAppRow['http_status'] ?? '—' }}</code></div>
                                                                    @if(!empty($whatsAppRow['error_message']))
                                                                        <div>Error: <code>{{ $whatsAppRow['error_message'] }}</code></div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <div class="text-muted small">No WhatsApp notifications found for this channel.</div>
                                                    @endif
                                                </div>
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
