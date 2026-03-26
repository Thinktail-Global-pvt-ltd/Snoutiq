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
        'video_follow_up' => 'All Follow-up Leads',
        'video_follow_up_video' => 'Video Follow-up Leads',
        'video_follow_up_in_clinic' => 'In-clinic Follow-up Leads',
        'vaccination' => 'Vaccination Reminder Leads',
        'both' => 'Users In Both Categories',
    ];

    $activeFilterLabel = $filterLabels[$leadFilter] ?? $filterLabels['all'];
    $todayDate = \Illuminate\Support\Carbon::today()->toDateString();
    $notificationsByUser = collect($filteredTargetUsers ?? [])
        ->mapWithKeys(function (array $leadUser): array {
            $userId = (string) ((int) ($leadUser['id'] ?? 0));
            $notifications = collect($leadUser['all_notifications'] ?? [])
                ->map(function (array $item): array {
                    $bucket = trim((string) ($item['bucket'] ?? ''));
                    $bucketLabel = match ($bucket) {
                        'neutering' => 'Neutering',
                        'follow_up' => 'Follow-up',
                        'vaccination' => 'Vaccination',
                        'onboarding' => 'Onboarding',
                        'profile_completion' => 'Profile Completion',
                        default => '',
                    };

                    return [
                        'id' => (int) ($item['id'] ?? 0),
                        'notification_title' => trim((string) ($item['notification_title'] ?? '')),
                        'notification_text' => trim((string) ($item['notification_text'] ?? '')),
                        'notification_type' => (string) ($item['notification_type'] ?? 'unknown'),
                        'bucket' => $bucket,
                        'bucket_label' => $bucketLabel !== '' ? $bucketLabel : '—',
                        'timestamp' => (string) ($item['timestamp'] ?? ''),
                    ];
                })
                ->values()
                ->all();

            return [$userId => $notifications];
        })
        ->all();

    $conversionsByUser = collect($filteredTargetUsers ?? [])
        ->mapWithKeys(function (array $leadUser): array {
            $userId = (string) ((int) ($leadUser['id'] ?? 0));
            $bucket = trim((string) ($leadUser['conversion_notification_bucket'] ?? ''));
            $bucketLabel = match ($bucket) {
                'neutering' => 'Neutering',
                'follow_up' => 'Follow-up',
                'vaccination' => 'Vaccination',
                'onboarding' => 'Onboarding',
                'profile_completion' => 'Profile Completion',
                default => 'Lead',
            };

            return [
                $userId => [
                    'captured' => (bool) ($leadUser['conversion_captured'] ?? false),
                    'notification_id' => (int) ($leadUser['conversion_notification_id'] ?? 0),
                    'notification_title' => (string) ($leadUser['conversion_notification_title'] ?? ''),
                    'notification_text' => (string) ($leadUser['conversion_notification_text'] ?? ''),
                    'notification_type' => (string) ($leadUser['conversion_notification_type'] ?? ''),
                    'notification_bucket_label' => $bucketLabel,
                    'notification_at' => (string) ($leadUser['conversion_notification_at'] ?? ''),
                    'transaction_id' => (int) ($leadUser['conversion_transaction_id'] ?? 0),
                    'transaction_type' => (string) ($leadUser['conversion_transaction_type'] ?? ''),
                    'transaction_status' => (string) ($leadUser['conversion_transaction_status'] ?? ''),
                    'transaction_at' => (string) ($leadUser['conversion_transaction_at'] ?? ''),
                    'lag_minutes' => is_numeric($leadUser['conversion_lag_minutes'] ?? null)
                        ? (int) $leadUser['conversion_lag_minutes']
                        : null,
                ],
            ];
        })
        ->all();

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

    $resolveFollowUpChip = static function (?string $date) use ($todayDate): array {
        $chipClass = 'neutral';
        $chipLabel = 'No Date';

        if (!empty($date)) {
            if ($date < $todayDate) {
                $chipClass = 'overdue';
                $chipLabel = 'Overdue';
            } elseif ($date === $todayDate) {
                $chipClass = 'today';
                $chipLabel = 'Due Today';
            } else {
                $chipClass = 'upcoming';
                $chipLabel = 'Upcoming';
            }
        }

        return [
            'class' => $chipClass,
            'label' => $chipLabel,
        ];
    };
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="h5 mb-1">Lead Categories</h2>
                        <p class="text-muted mb-0">Showing only users from Neutering, Video/In-clinic Follow-up, and Vaccination reminder lead buckets.</p>
                    </div>
                    <form class="d-flex align-items-center gap-2 flex-wrap" method="GET" action="{{ route('admin.lead-management') }}">
                        <label for="lead_filter" class="small text-muted text-nowrap mb-0">Category filter</label>
                        <select id="lead_filter" name="lead_filter" class="form-select form-select-sm" style="min-width: 240px;">
                            <option value="all" @selected($leadFilter === 'all')>All targeted users</option>
                            <option value="neutering" @selected($leadFilter === 'neutering')>Neutering package leads</option>
                            <option value="video_follow_up" @selected($leadFilter === 'video_follow_up')>All follow-up leads</option>
                            <option value="video_follow_up_video" @selected($leadFilter === 'video_follow_up_video')>Video follow-up leads</option>
                            <option value="video_follow_up_in_clinic" @selected($leadFilter === 'video_follow_up_in_clinic')>In-clinic follow-up leads</option>
                            <option value="vaccination" @selected($leadFilter === 'vaccination')>Vaccination reminder leads</option>
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
                @if(($leadConfig['supports_video_follow_up'] ?? false) && !($leadConfig['supports_video_follow_up_mode_split'] ?? false))
                    <div class="alert alert-warning py-2 mb-3">Video/In-clinic split is unavailable (missing <code>prescriptions.video_inclinic</code>).</div>
                @endif
                @if(!($leadConfig['supports_neutering_notification_join'] ?? false))
                    <div class="alert alert-warning py-2 mb-3">Neutering notification join is unavailable (missing <code>fcm_notifications.data_payload</code>).</div>
                @endif
                @if(!($leadConfig['supports_follow_up_notification_join'] ?? false))
                    <div class="alert alert-warning py-2 mb-3">Follow-up notification join is unavailable (missing <code>fcm_notifications.call_session</code> or <code>prescriptions.call_session</code>).</div>
                @endif
                @if(!($leadConfig['supports_vaccination_notification_join'] ?? false))
                    <div class="alert alert-warning py-2 mb-3">Vaccination reminder module is unavailable (missing <code>fcm_notifications.notification_type</code> / <code>fcm_notifications.data_payload</code>).</div>
                @endif
                @if(!($leadConfig['supports_conversion_tracking'] ?? false))
                    <div class="alert alert-warning py-2 mb-3">Lead conversion tracking is unavailable (missing <code>transactions.user_id</code> / <code>transactions.created_at</code>).</div>
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
                            <div class="value">{{ number_format($summary['video_follow_up_video_leads'] ?? 0) }}</div>
                            <div class="label">Video Follow-up Leads</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="lead-summary-card">
                            <div class="value">{{ number_format($summary['video_follow_up_in_clinic_leads'] ?? 0) }}</div>
                            <div class="label">In-clinic Follow-up Leads</div>
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
                    <div class="col-6 col-lg-3">
                        <div class="lead-summary-card">
                            <div class="value">{{ number_format($summary['neutering_notified_users'] ?? 0) }}</div>
                            <div class="label">Neutering Notified Users</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="lead-summary-card">
                            <div class="value">{{ number_format($summary['vaccination_notified_users'] ?? 0) }}</div>
                            <div class="label">Vaccination Notified Users</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="lead-summary-card">
                            <div class="value">{{ number_format($summary['converted_users'] ?? 0) }}</div>
                            <div class="label">Converted Users</div>
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
                                    <th>Neutering Notification</th>
                                    <th>Vaccination Reminder</th>
                                    <th>Follow-up (Video/In-clinic)</th>
                                    <th>Conversion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($filteredTargetUsers as $leadUser)
                                    @php
                                        $nextFollowUpDate = $leadUser['next_follow_up_date'] ?? null;
                                        $nextVideoFollowUpDate = $leadUser['next_video_follow_up_date'] ?? null;
                                        $nextInClinicFollowUpDate = $leadUser['next_in_clinic_follow_up_date'] ?? null;
                                        $followUpChip = $resolveFollowUpChip($nextFollowUpDate);
                                        $videoFollowUpChip = $resolveFollowUpChip($nextVideoFollowUpDate);
                                        $inClinicFollowUpChip = $resolveFollowUpChip($nextInClinicFollowUpDate);
                                        $videoFollowUpCount = (int) ($leadUser['video_follow_up_count'] ?? 0);
                                        $videoFollowUpVideoCount = (int) ($leadUser['video_follow_up_video_count'] ?? 0);
                                        $videoFollowUpInClinicCount = (int) ($leadUser['video_follow_up_in_clinic_count'] ?? 0);
                                        $supportsVideoFollowUpModeSplit = (bool) ($leadConfig['supports_video_follow_up_mode_split'] ?? false);

                                        $neuteringNotificationCount = (int) ($leadUser['neutering_notification_count'] ?? 0);
                                        $notifiedNeuteringPetNames = collect($leadUser['notified_neutering_pet_names'] ?? [])->take(3)->implode(', ');
                                        $vaccinationNotificationCount = (int) ($leadUser['vaccination_notification_count'] ?? 0);
                                        $notifiedVaccinationPetNames = collect($leadUser['notified_vaccination_pet_names'] ?? [])->take(3)->implode(', ');
                                        $conversionCaptured = (bool) ($leadUser['conversion_captured'] ?? false);
                                        $conversionNotificationBucket = trim((string) ($leadUser['conversion_notification_bucket'] ?? ''));
                                        $conversionNotificationBucketLabel = match ($conversionNotificationBucket) {
                                            'neutering' => 'Neutering',
                                            'follow_up' => 'Follow-up',
                                            'vaccination' => 'Vaccination',
                                            default => 'Lead',
                                        };
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
                                            @if($supportsVideoFollowUpModeSplit)
                                                @if(!empty($leadUser['has_video_follow_up_video']))
                                                    <span class="badge text-bg-success">Video Follow-up</span>
                                                @endif
                                                @if(!empty($leadUser['has_video_follow_up_in_clinic']))
                                                    <span class="badge text-bg-primary">In-clinic Follow-up</span>
                                                @endif
                                                @if(
                                                    !empty($leadUser['has_video_follow_up'])
                                                    && empty($leadUser['has_video_follow_up_video'])
                                                    && empty($leadUser['has_video_follow_up_in_clinic'])
                                                )
                                                    <span class="badge text-bg-secondary">Follow-up</span>
                                                @endif
                                            @elseif(!empty($leadUser['has_video_follow_up']))
                                                <span class="badge text-bg-success">All Follow-up</span>
                                            @endif
                                            @if(!empty($leadUser['has_vaccination_reminder']))
                                                <span class="badge text-bg-info">Vaccination Reminder</span>
                                            @endif
                                        </td>
                                        <td data-label="Neutering Pets">
                                            <div class="fw-semibold">{{ (int) ($leadUser['neutering_pet_count'] ?? 0) }} pets</div>
                                            <div class="text-muted small">
                                                {{ collect($leadUser['neutering_pet_names'] ?? [])->take(3)->implode(', ') ?: '—' }}
                                            </div>
                                        </td>
                                        <td data-label="Neutering Notification">
                                            @if(!($leadConfig['supports_neutering_notification_join'] ?? false))
                                                <span class="badge text-bg-light">Unavailable</span>
                                            @elseif(empty($leadUser['has_neutering']))
                                                <span class="badge text-bg-light">N/A</span>
                                            @elseif($neuteringNotificationCount > 0)
                                                <span class="badge text-bg-success">Sent</span>
                                                <div class="text-muted small mt-1">Notified pets: {{ $neuteringNotificationCount }}</div>
                                                <div class="text-muted small">{{ $notifiedNeuteringPetNames ?: '—' }}</div>
                                                <div class="text-muted small">Last sent: {{ $formatDateTime($leadUser['last_neutering_notification_at'] ?? null) }}</div>
                                            @else
                                                <span class="badge text-bg-secondary">Not sent</span>
                                            @endif

                                            @php $allNotificationsCount = (int) ($leadUser['all_notifications_count'] ?? 0); @endphp
                                            <div class="mt-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#userNotificationsModal"
                                                    data-user-id="{{ (int) ($leadUser['id'] ?? 0) }}"
                                                    data-user-name="{{ $leadUser['name'] ?: ('User #' . ((int) ($leadUser['id'] ?? 0))) }}"
                                                    @disabled($allNotificationsCount <= 0)
                                                >
                                                    View More
                                                </button>
                                                <div class="text-muted small mt-1">{{ $allNotificationsCount }} total</div>
                                            </div>
                                        </td>
                                        <td data-label="Vaccination Reminder">
                                            @if(!($leadConfig['supports_vaccination_notification_join'] ?? false))
                                                <span class="badge text-bg-light">Unavailable</span>
                                            @elseif($vaccinationNotificationCount > 0)
                                                <span class="badge text-bg-info">Sent</span>
                                                <div class="text-muted small mt-1">Notifications: {{ $vaccinationNotificationCount }}</div>
                                                <div class="text-muted small">{{ $notifiedVaccinationPetNames ?: '—' }}</div>
                                                <div class="text-muted small">Last sent: {{ $formatDateTime($leadUser['last_vaccination_notification_at'] ?? null) }}</div>
                                            @else
                                                <span class="badge text-bg-secondary">Not sent</span>
                                            @endif
                                        </td>
                                        <td data-label="Follow-up (Video/In-clinic)">
                                            @if($supportsVideoFollowUpModeSplit)
                                                <div class="fw-semibold">Video: {{ $videoFollowUpVideoCount }} follow-ups</div>
                                                <div class="text-muted small">Next: {{ $nextVideoFollowUpDate ? $formatDate($nextVideoFollowUpDate) : '—' }}</div>
                                                <span class="lead-chip {{ $videoFollowUpChip['class'] }} mt-1">{{ $videoFollowUpChip['label'] }}</span>
                                                <div class="mt-2 fw-semibold">In-clinic: {{ $videoFollowUpInClinicCount }} follow-ups</div>
                                                <div class="text-muted small">Next: {{ $nextInClinicFollowUpDate ? $formatDate($nextInClinicFollowUpDate) : '—' }}</div>
                                                <span class="lead-chip {{ $inClinicFollowUpChip['class'] }} mt-1">{{ $inClinicFollowUpChip['label'] }}</span>
                                                @if($videoFollowUpCount > ($videoFollowUpVideoCount + $videoFollowUpInClinicCount))
                                                    <div class="text-muted small mt-2">
                                                        Other: {{ $videoFollowUpCount - ($videoFollowUpVideoCount + $videoFollowUpInClinicCount) }} follow-ups
                                                    </div>
                                                @endif
                                            @else
                                                <div class="fw-semibold">{{ $videoFollowUpCount }} follow-ups</div>
                                                <div class="text-muted small">Next: {{ $nextFollowUpDate ? $formatDate($nextFollowUpDate) : '—' }}</div>
                                                <span class="lead-chip {{ $followUpChip['class'] }} mt-1">{{ $followUpChip['label'] }}</span>
                                            @endif
                                        </td>
                                        <td data-label="Conversion">
                                            @if(!($leadConfig['supports_conversion_tracking'] ?? false))
                                                <span class="badge text-bg-light">Unavailable</span>
                                            @elseif($allNotificationsCount <= 0)
                                                <span class="badge text-bg-light">N/A</span>
                                                <div class="text-muted small mt-1">No delivered notifications</div>
                                            @elseif($conversionCaptured)
                                                <span class="badge text-bg-success">Converted</span>
                                                <div class="text-muted small mt-1">
                                                    After: {{ $leadUser['conversion_notification_type'] ?? 'unknown' }} ({{ $conversionNotificationBucketLabel }})
                                                </div>
                                                <div class="text-muted small">Notified at: {{ $formatDateTime($leadUser['conversion_notification_at'] ?? null) }}</div>
                                                <div class="text-muted small">
                                                    Txn: #{{ (int) ($leadUser['conversion_transaction_id'] ?? 0) }}
                                                    • {{ $leadUser['conversion_transaction_type'] ?? 'unknown' }}
                                                </div>
                                                <div class="text-muted small">Txn at: {{ $formatDateTime($leadUser['conversion_transaction_at'] ?? null) }}</div>
                                                @if(!empty($leadUser['conversion_transaction_status']))
                                                    <div class="text-muted small">Status: {{ $leadUser['conversion_transaction_status'] }}</div>
                                                @endif
                                                @if(is_numeric($leadUser['conversion_lag_minutes'] ?? null))
                                                    <div class="text-muted small">Lag: {{ (int) $leadUser['conversion_lag_minutes'] }} min</div>
                                                @endif
                                            @else
                                                <span class="badge text-bg-secondary">Not converted</span>
                                                <div class="text-muted small mt-1">No transaction found after notifications</div>
                                            @endif
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

<div class="modal fade" id="userNotificationsModal" tabindex="-1" aria-labelledby="userNotificationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userNotificationsModalLabel">Notifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-muted small mb-2" id="userNotificationsMeta"></div>
                <div class="border rounded p-3 mb-3 bg-light" id="userConversionMeta"></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Notification Title (Exact)</th>
                                <th>Notification Text</th>
                                <th>Notification Type</th>
                                <th>Lead Bucket</th>
                                <th>Time (Timestamp)</th>
                                <th>Converted</th>
                            </tr>
                        </thead>
                        <tbody id="userNotificationsTableBody">
                            <tr>
                                <td colspan="6" class="text-muted">No notifications found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const notificationsByUser = @json($notificationsByUser);
    const conversionsByUser = @json($conversionsByUser);
    const modal = document.getElementById('userNotificationsModal');
    const meta = document.getElementById('userNotificationsMeta');
    const conversionMeta = document.getElementById('userConversionMeta');
    const body = document.getElementById('userNotificationsTableBody');
    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    const isConvertedTriggerRow = (conversion, row) => {
        if (!conversion?.captured) return false;

        const conversionId = Number(conversion.notification_id || 0);
        const rowId = Number(row.id || 0);
        if (conversionId > 0 && rowId > 0) {
            return conversionId === rowId;
        }

        const conversionTs = String(conversion.notification_at || '').trim();
        const rowTs = String(row.timestamp || '').trim();
        const conversionType = String(conversion.notification_type || '').trim().toLowerCase();
        const rowType = String(row.notification_type || '').trim().toLowerCase();
        return conversionTs !== '' && conversionTs === rowTs && conversionType !== '' && conversionType === rowType;
    };

    if (!modal || !meta || !conversionMeta || !body) return;

    modal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        const userId = trigger?.getAttribute('data-user-id') || '';
        const userName = trigger?.getAttribute('data-user-name') || 'User';
        const rows = notificationsByUser[userId] || [];
        const conversion = conversionsByUser[userId] || null;

        meta.textContent = `${userName} (ID: ${userId}) • ${rows.length} notifications`;

        if (conversion?.captured) {
            const title = escapeHtml(conversion.notification_title || '—');
            const text = escapeHtml(conversion.notification_text || '—');
            const type = escapeHtml(conversion.notification_type || 'unknown');
            const bucket = escapeHtml(conversion.notification_bucket_label || 'Lead');
            const notifiedAt = escapeHtml(conversion.notification_at || '—');
            const txnId = Number(conversion.transaction_id || 0);
            const txnType = escapeHtml(conversion.transaction_type || 'unknown');
            const txnStatus = escapeHtml(conversion.transaction_status || '—');
            const txnAt = escapeHtml(conversion.transaction_at || '—');
            const lagRaw = conversion.lag_minutes;
            const lag = (lagRaw !== null && lagRaw !== undefined && lagRaw !== '' && Number.isFinite(Number(lagRaw)))
                ? `${Number(lagRaw)} min`
                : '—';

            conversionMeta.innerHTML = `
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge text-bg-success">Converted</span>
                    <span class="small text-muted">Notification -> Transaction attribution</span>
                </div>
                <div class="small"><strong>Notification title:</strong> ${title}</div>
                <div class="small"><strong>Notification text:</strong> ${text}</div>
                <div class="small"><strong>Notification type:</strong> ${type} (${bucket})</div>
                <div class="small"><strong>Notified at:</strong> ${notifiedAt}</div>
                <div class="small"><strong>Converted transaction:</strong> #${txnId} • ${txnType} • ${txnStatus}</div>
                <div class="small"><strong>Transaction time:</strong> ${txnAt} • <strong>Lag:</strong> ${lag}</div>
            `;
        } else {
            conversionMeta.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-bg-secondary">Not converted</span>
                    <span class="small text-muted">No transaction mapped after delivered notifications.</span>
                </div>
            `;
        }

        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-muted">No notifications found.</td></tr>';
            return;
        }

        body.innerHTML = rows.map((row) => {
            const isTrigger = isConvertedTriggerRow(conversion, row);
            const rowClass = isTrigger ? 'table-success' : '';
            const title = escapeHtml(row.notification_title || '—');
            const text = escapeHtml(row.notification_text || '—');
            const type = escapeHtml(row.notification_type || 'unknown');
            const bucket = escapeHtml(row.bucket_label || '—');
            const ts = escapeHtml(row.timestamp || '—');
            const converted = isTrigger
                ? '<span class="badge text-bg-success">Converted trigger</span>'
                : '<span class="text-muted">—</span>';

            return `<tr class="${rowClass}"><td>${title}</td><td>${text}</td><td>${type}</td><td>${bucket}</td><td>${ts}</td><td>${converted}</td></tr>`;
        }).join('');
    });
})();
</script>
@endpush
