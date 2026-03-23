@extends('layouts.admin-panel')

@section('page-title', 'Lead Management')

@push('styles')
<style>
    .lead-summary-card {
        border: 0;
        border-radius: 0.9rem;
        background: #f8fafc;
        padding: 1rem;
        height: 100%;
    }
    .lead-summary-card .value {
        font-size: 1.4rem;
        font-weight: 700;
        line-height: 1;
        color: #0f172a;
    }
    .lead-summary-card .label {
        margin-top: 0.35rem;
        color: #64748b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 600;
    }
    .lead-table td,
    .lead-table th {
        vertical-align: top;
    }
    .lead-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 700;
        padding: 0.25rem 0.58rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .lead-chip.overdue {
        background: #fee2e2;
        color: #b91c1c;
    }
    .lead-chip.today {
        background: #fef3c7;
        color: #b45309;
    }
    .lead-chip.upcoming {
        background: #dcfce7;
        color: #166534;
    }
    .lead-chip.neutral {
        background: #e2e8f0;
        color: #475569;
    }
    @media (max-width: 991.98px) {
        .lead-table thead {
            display: none;
        }
        .lead-table,
        .lead-table tbody,
        .lead-table tr,
        .lead-table td {
            display: block;
            width: 100%;
        }
        .lead-table tr {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            margin-bottom: 0.85rem;
            overflow: hidden;
        }
        .lead-table td {
            border: 0;
            border-bottom: 1px dashed #e5e7eb;
            padding: 0.7rem 0.8rem;
            padding-left: 42%;
            position: relative;
            min-height: 2.8rem;
            overflow-wrap: anywhere;
        }
        .lead-table td:last-child {
            border-bottom: 0;
        }
        .lead-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 0.8rem;
            top: 0.74rem;
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
        .lead-table td {
            padding-left: 0.8rem;
            padding-top: 2rem;
        }
        .lead-table td::before {
            position: static;
            display: block;
            width: 100%;
            margin-bottom: 0.3rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $formatDate = static function ($value): string {
        if (empty($value)) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $formatDateTime = static function ($value): string {
        if (empty($value)) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y, H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $todayDate = \Illuminate\Support\Carbon::today()->toDateString();
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="h5 mb-1">Lead Categories</h2>
                        <p class="text-muted mb-0">
                            Category-wise lead list from <code>users</code>, <code>pets</code>, <code>transactions</code>, and <code>prescriptions</code>.
                        </p>
                    </div>
                    <form class="d-flex align-items-center gap-2" method="GET" action="{{ route('admin.lead-management') }}">
                        <label for="limit" class="small text-muted text-nowrap mb-0">Rows per category</label>
                        <input id="limit" name="limit" type="number" class="form-control form-control-sm" min="25" max="1000" value="{{ $limit }}" style="width: 110px;">
                        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    </form>
                </div>

                <div class="row g-3">
                    <div class="col-6 col-lg-3">
                        <div class="lead-summary-card">
                            <div class="value">{{ number_format($summary['all_users'] ?? 0) }}</div>
                            <div class="label">All User Leads</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="lead-summary-card">
                            <div class="value">{{ number_format($summary['neutering_leads'] ?? 0) }}</div>
                            <div class="label">Neutering Package Leads</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="lead-summary-card">
                            <div class="value">{{ number_format($summary['video_follow_up_leads'] ?? 0) }}</div>
                            <div class="label">Video Follow-up Leads</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="lead-summary-card">
                            <div class="value">{{ number_format($summary['target_users'] ?? 0) }}</div>
                            <div class="label">Unique Target Users</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                    <div>
                        <h3 class="h6 mb-1">Category 1: All Leads (Users Table)</h3>
                        <p class="text-muted mb-0">
                            Showing latest {{ number_format($allUserLeads->count()) }} out of {{ number_format($summary['all_users'] ?? 0) }} users.
                        </p>
                    </div>
                </div>

                @if($allUserLeads->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-people display-6 d-block mb-2"></i>
                        <p class="mb-0">No user leads found.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle lead-table">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Contact</th>
                                    <th>City</th>
                                    <th class="text-nowrap">Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allUserLeads as $leadUser)
                                    <tr>
                                        <td data-label="User">
                                            <span class="fw-semibold">{{ $leadUser->name ?? 'Unnamed user' }}</span>
                                            <div class="text-muted small">ID: {{ $leadUser->id }}</div>
                                        </td>
                                        <td data-label="Contact">
                                            <div>{{ $leadUser->phone ?? 'No phone' }}</div>
                                            <div class="text-muted small">{{ $leadUser->email ?? 'No email' }}</div>
                                        </td>
                                        <td data-label="City">{{ $leadUser->city ?? '—' }}</td>
                                        <td class="text-nowrap" data-label="Joined">{{ $formatDateTime($leadUser->created_at) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                    <div>
                        <h3 class="h6 mb-1">Category 2: Sell Neutering Package</h3>
                        <p class="text-muted mb-0">
                            Pets where <code>pets.is_neutered = 'N'</code> (fallback supported for <code>is_nuetered</code>).
                        </p>
                    </div>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">
                        {{ number_format($summary['neutering_leads'] ?? 0) }} leads
                    </span>
                </div>

                @if(!($leadConfig['supports_neutering'] ?? false))
                    <div class="alert alert-warning mb-0">
                        Neutering leads cannot be computed because neither <code>pets.is_neutered</code> nor <code>pets.is_nuetered</code> exists.
                    </div>
                @elseif($neuteringLeads->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-check2-circle display-6 d-block mb-2"></i>
                        <p class="mb-0">No pets currently flagged with neutering status <code>N</code>.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle lead-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Pet</th>
                                    <th>Owner</th>
                                    <th>Neutering Flag</th>
                                    <th class="text-nowrap">Pet Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($neuteringLeads as $petLead)
                                    @php
                                        $neuteringFlag = strtoupper(trim((string) ($petLead->is_neutered ?? $petLead->is_nuetered ?? '')));
                                    @endphp
                                    <tr>
                                        <td data-label="Pet">
                                            <span class="fw-semibold">{{ $petLead->name ?? 'Unnamed pet' }}</span>
                                            <div class="text-muted small">
                                                {{ $petLead->pet_type ?? $petLead->type ?? 'Unknown type' }}
                                                @if(!empty($petLead->breed))
                                                    • {{ $petLead->breed }}
                                                @endif
                                            </div>
                                            <div class="text-muted small">Pet ID: {{ $petLead->id }}</div>
                                        </td>
                                        <td data-label="Owner">
                                            <div class="fw-semibold">{{ $petLead->owner->name ?? 'Unknown owner' }}</div>
                                            <div class="text-muted small">{{ $petLead->owner->phone ?? 'No phone' }}</div>
                                            <div class="text-muted small">{{ $petLead->owner->email ?? 'No email' }}</div>
                                        </td>
                                        <td data-label="Neutering Flag">
                                            <span class="badge {{ $neuteringFlag === 'N' ? 'text-bg-warning' : 'text-bg-light' }}">
                                                {{ $neuteringFlag !== '' ? $neuteringFlag : 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-nowrap" data-label="Pet Added">{{ $formatDateTime($petLead->created_at) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                    <div>
                        <h3 class="h6 mb-1">Category 3: Video Consultation Follow-up Leads</h3>
                        <p class="text-muted mb-0">
                            <code>transactions.type IN ('video_consult', 'excell_export_campaign')</code> joined with
                            <code>prescriptions.call_session</code> using <code>{{ $leadConfig['transaction_session_column'] ?? 'channel_name' }}</code>,
                            filtered by <code>prescriptions.follow_up_date</code>.
                        </p>
                    </div>
                    <span class="badge text-bg-success-subtle text-success-emphasis px-3 py-2">
                        {{ number_format($summary['video_follow_up_leads'] ?? 0) }} leads
                    </span>
                </div>

                @if(!($leadConfig['supports_video_follow_up'] ?? false))
                    <div class="alert alert-warning mb-0">
                        Video follow-up leads cannot be computed because one or more required columns are missing.
                    </div>
                @elseif($videoFollowUpLeads->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-camera-video-off display-6 d-block mb-2"></i>
                        <p class="mb-0">No follow-up consultation leads found yet.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle lead-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Follow-up</th>
                                    <th>Transaction</th>
                                    <th>User</th>
                                    <th>Pet</th>
                                    <th>Doctor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($videoFollowUpLeads as $followUpLead)
                                    @php
                                        $followUpDateValue = $followUpLead->getAttribute('lead_follow_up_date');
                                        $followUpDate = null;
                                        try {
                                            $followUpDate = $followUpDateValue ? \Illuminate\Support\Carbon::parse($followUpDateValue) : null;
                                        } catch (\Throwable $e) {
                                            $followUpDate = null;
                                        }

                                        $followUpDateKey = $followUpDate?->toDateString();
                                        $followUpLabel = 'Unknown';
                                        $followUpClass = 'neutral';
                                        if ($followUpDateKey) {
                                            if ($followUpDateKey < $todayDate) {
                                                $followUpLabel = 'Overdue';
                                                $followUpClass = 'overdue';
                                            } elseif ($followUpDateKey === $todayDate) {
                                                $followUpLabel = 'Due Today';
                                                $followUpClass = 'today';
                                            } else {
                                                $followUpLabel = 'Upcoming';
                                                $followUpClass = 'upcoming';
                                            }
                                        }

                                        $transactionType = strtolower((string) ($followUpLead->type ?? data_get($followUpLead->metadata, 'order_type', '')));
                                        $amountInr = number_format(((int) ($followUpLead->amount_paise ?? 0)) / 100, 2);
                                    @endphp
                                    <tr>
                                        <td data-label="Follow-up">
                                            <div class="fw-semibold">{{ $followUpDate ? $formatDate($followUpDate) : '—' }}</div>
                                            <span class="lead-chip {{ $followUpClass }}">{{ $followUpLabel }}</span>
                                            <div class="text-muted small mt-1">Prescription ID: {{ $followUpLead->getAttribute('lead_prescription_id') ?? '—' }}</div>
                                        </td>
                                        <td data-label="Transaction">
                                            <div class="fw-semibold">#{{ $followUpLead->id }}</div>
                                            <div class="text-muted small">Type: {{ $transactionType !== '' ? $transactionType : 'n/a' }}</div>
                                            <div class="text-muted small">Amount: ₹{{ $amountInr }}</div>
                                            <div class="text-muted small">{{ $leadConfig['transaction_session_column'] ?? 'channel_name' }}: {{ $followUpLead->getAttribute('lead_call_session') ?? '—' }}</div>
                                        </td>
                                        <td data-label="User">
                                            <div class="fw-semibold">{{ $followUpLead->user->name ?? 'Unknown user' }}</div>
                                            <div class="text-muted small">{{ $followUpLead->user->phone ?? 'No phone' }}</div>
                                            <div class="text-muted small">{{ $followUpLead->user->email ?? 'No email' }}</div>
                                        </td>
                                        <td data-label="Pet">
                                            <div class="fw-semibold">{{ $followUpLead->pet->name ?? 'Unknown pet' }}</div>
                                            <div class="text-muted small">
                                                {{ $followUpLead->pet->pet_type ?? $followUpLead->pet->type ?? 'Unknown type' }}
                                                @if(!empty($followUpLead->pet->breed))
                                                    • {{ $followUpLead->pet->breed }}
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="Doctor">
                                            <div class="fw-semibold">{{ $followUpLead->doctor->doctor_name ?? 'Unassigned' }}</div>
                                            <div class="text-muted small">{{ $followUpLead->doctor->doctor_mobile ?? 'No phone' }}</div>
                                            <div class="text-muted small">{{ $followUpLead->clinic->name ?? 'No clinic' }}</div>
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
