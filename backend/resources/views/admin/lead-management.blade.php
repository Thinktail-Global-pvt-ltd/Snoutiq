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
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 700;
        padding: 0.2rem 0.58rem;
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
    $filterLabels = [
        'all' => 'All Targeted Users',
        'neutering' => 'Neutering Package Leads',
        'video_follow_up' => 'Video Follow-up Leads',
        'both' => 'Users In Both Categories',
    ];

    $activeFilterLabel = $filterLabels[$leadFilter] ?? $filterLabels['all'];
    $todayDate = \Illuminate\Support\Carbon::today()->toDateString();

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
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="h5 mb-1">Lead Categories</h2>
                        <p class="text-muted mb-0">Showing only users from Neutering and Video Follow-up lead buckets.</p>
                    </div>
                    <form class="d-flex align-items-center gap-2 flex-wrap" method="GET" action="{{ route('admin.lead-management') }}">
                        <label for="lead_filter" class="small text-muted text-nowrap mb-0">Category filter</label>
                        <select id="lead_filter" name="lead_filter" class="form-select form-select-sm" style="min-width: 240px;">
                            <option value="all" @selected($leadFilter === 'all')>All targeted users</option>
                            <option value="neutering" @selected($leadFilter === 'neutering')>Neutering package leads</option>
                            <option value="video_follow_up" @selected($leadFilter === 'video_follow_up')>Video follow-up leads</option>
                            <option value="both" @selected($leadFilter === 'both')>Users in both categories</option>
                        </select>

                        <label for="limit" class="small text-muted text-nowrap mb-0">Rows per category</label>
                        <input id="limit" name="limit" type="number" class="form-control form-control-sm" min="25" max="1000" value="{{ $limit }}" style="width: 110px;">
                        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    </form>
                </div>

                @if(!($leadConfig['supports_neutering'] ?? false))
                    <div class="alert alert-warning py-2 mb-2">Neutering category is unavailable on this database (missing <code>pets.is_neutered</code>/<code>pets.is_nuetered</code>).</div>
                @endif
                @if(!($leadConfig['supports_video_follow_up'] ?? false))
                    <div class="alert alert-warning py-2 mb-3">Video follow-up category is unavailable on this database (missing join columns).</div>
                @endif

                <div class="row g-3">
                    <div class="col-6 col-lg-3">
                        <div class="lead-summary-card">
                            <div class="value">{{ number_format($summary['neutering_leads'] ?? 0) }}</div>
                            <div class="label">Neutering Leads</div>
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
                    <div class="col-6 col-lg-3">
                        <div class="lead-summary-card">
                            <div class="value">{{ number_format($summary['filtered_users'] ?? 0) }}</div>
                            <div class="label">Filtered Users</div>
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
                        <h3 class="h6 mb-1">Filtered User Leads</h3>
                        <p class="text-muted mb-0">Filter: <strong>{{ $activeFilterLabel }}</strong> • Showing {{ number_format($summary['filtered_users'] ?? 0) }} users.</p>
                    </div>
                </div>

                @if($filteredTargetUsers->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-filter-circle display-6 d-block mb-2"></i>
                        <p class="mb-0">No users match the selected lead filter.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle lead-table">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Contact</th>
                                    <th>City</th>
                                    <th>Lead Categories</th>
                                    <th>Neutering Pets</th>
                                    <th>Video Follow-up</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filteredTargetUsers as $leadUser)
                                    @php
                                        $nextFollowUpDate = $leadUser['next_follow_up_date'] ?? null;
                                        $followUpChipClass = 'neutral';
                                        $followUpChipLabel = 'No Date';
                                        if (!empty($nextFollowUpDate)) {
                                            if ($nextFollowUpDate < $todayDate) {
                                                $followUpChipClass = 'overdue';
                                                $followUpChipLabel = 'Overdue';
                                            } elseif ($nextFollowUpDate === $todayDate) {
                                                $followUpChipClass = 'today';
                                                $followUpChipLabel = 'Due Today';
                                            } else {
                                                $followUpChipClass = 'upcoming';
                                                $followUpChipLabel = 'Upcoming';
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td data-label="User">
                                            <div class="fw-semibold">{{ $leadUser['name'] ?: 'Unnamed user' }}</div>
                                            <div class="text-muted small">ID: {{ $leadUser['id'] }}</div>
                                        </td>
                                        <td data-label="Contact">
                                            <div>{{ $leadUser['phone'] ?: 'No phone' }}</div>
                                            <div class="text-muted small">{{ $leadUser['email'] ?: 'No email' }}</div>
                                        </td>
                                        <td data-label="City">{{ $leadUser['city'] ?: '—' }}</td>
                                        <td data-label="Lead Categories">
                                            @if(!empty($leadUser['has_neutering']))
                                                <span class="badge text-bg-warning">Neutering</span>
                                            @endif
                                            @if(!empty($leadUser['has_video_follow_up']))
                                                <span class="badge text-bg-success">Video Follow-up</span>
                                            @endif
                                        </td>
                                        <td data-label="Neutering Pets">
                                            <div class="fw-semibold">{{ (int) ($leadUser['neutering_pet_count'] ?? 0) }} pets</div>
                                            <div class="text-muted small">
                                                {{ collect($leadUser['neutering_pet_names'] ?? [])->take(3)->implode(', ') ?: '—' }}
                                            </div>
                                        </td>
                                        <td data-label="Video Follow-up">
                                            <div class="fw-semibold">{{ (int) ($leadUser['video_follow_up_count'] ?? 0) }} follow-ups</div>
                                            <div class="text-muted small">Next: {{ $nextFollowUpDate ? $formatDate($nextFollowUpDate) : '—' }}</div>
                                            <span class="lead-chip {{ $followUpChipClass }} mt-1">{{ $followUpChipLabel }}</span>
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
