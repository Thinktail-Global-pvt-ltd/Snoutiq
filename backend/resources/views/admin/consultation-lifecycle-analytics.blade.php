@extends('layouts.admin-panel')

@section('page-title', 'Consultation Lifecycle Analytics')

@push('styles')
<style>
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
                                    <th>Review Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $txn)
                                    @php
                                        $reviewSubmittedAt = $txn->getAttribute('event_review_submitted_at');
                                        $reviewSubmittedCaptured = (bool) $txn->getAttribute('event_review_submitted_captured');
                                        $reviewSubmittedSecure = (bool) $txn->getAttribute('event_review_submitted_secure');
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
                                        <td data-label="Review Submitted">
                                            <div class="event-block {{ $reviewSubmittedCaptured ? 'done' : '' }}">
                                                <div class="d-flex flex-wrap gap-2 mb-1">
                                                    <span class="fw-semibold small">Review Submitted</span>
                                                    <span class="status-badge {{ $reviewSubmittedCaptured ? 'done' : 'pending' }}">
                                                        {{ $reviewSubmittedCaptured ? 'Submitted' : 'Not Submitted' }}
                                                    </span>
                                                    <span class="status-badge {{ $reviewSubmittedSecure ? 'done' : 'pending' }}">
                                                        {{ $reviewSubmittedSecure ? 'Secure' : 'Not Secure' }}
                                                    </span>
                                                </div>
                                                <div class="text-muted small">
                                                    <div>Timestamp: {{ $formatTimestamp($reviewSubmittedAt) }}</div>
                                                </div>
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
@endsection
