@extends('layouts.admin-panel')

@section('page-title', 'Onboarding Overview')

@php
    use Illuminate\Support\Str;
@endphp

@section('sidebar-secondary')
    <div class="text-uppercase text-white-50 small fw-semibold mb-3">Onboarding Console</div>
    <nav class="admin-secondary-nav" data-panel-nav>
        <a href="#overview" class="active">Overview</a>
        <a href="#users">Users</a>
        <a href="#pets">Pets</a>
        <a href="#doctors">Doctors</a>
        <a href="#clinics">Vet Registrations</a>
    </nav>
    <small class="text-white-50 d-block mt-3">Jump to detailed lists without leaving this overview.</small>
@endsection

@push('styles')
<style>
    .onboarding-search-wrap {
        max-width: 420px;
    }
    .onboarding-search-wrap .form-control {
        border-radius: 999px;
        padding: 0.65rem 1.1rem;
    }
    .onboarding-search-wrap .input-group-text {
        border-radius: 999px;
        background: transparent;
        border-right: none;
    }
    .onboarding-search-wrap .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(15,23,42,0.08);
    }
    .clinic-progress-bar {
        height: 10px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .clinic-progress-bar span {
        display: block;
        height: 100%;
        background: linear-gradient(90deg,#34d399,#1d4ed8);
    }
    .clinic-progress-legend {
        font-size: 0.82rem;
        color: #475569;
    }
    .step-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
    }
    .step-chip {
        border-radius: 999px;
        padding: 0.15rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        border: 1px solid transparent;
    }
    .step-chip.complete {
        background: rgba(34,197,94,0.12);
        color: #0f8a4c;
        border-color: rgba(34,197,94,0.3);
    }
    .step-chip.pending {
        background: rgba(251,191,36,0.15);
        color: #92400e;
        border-color: rgba(251,191,36,0.3);
    }
    .doctor-progress {
        min-width: 120px;
    }
    .doctor-progress small {
        font-size: 0.75rem;
        color: #475569;
    }
    [data-clinic-block].d-none + [data-clinic-block] {
        display: none;
    }
    .search-summary {
        font-size: 0.85rem;
        color: #475569;
    }
</style>
@endpush

@php
    $stepLabels = $stepLabels ?? [
        'services' => 'Services',
        'video' => 'Video Calling',
        'clinic_hours' => 'Clinic Hours',
        'emergency' => 'Emergency Cover',
        'documents' => 'Documents & Compliance',
    ];

    $sections = [
        'services' => [
            'title' => 'Services',
            'description' => 'Doctors who have configured their services or clinic chat pricing.',
            'stats' => $stats['services'],
            'rows' => $services,
            'columns' => [
                ['label' => 'Clinic', 'width' => '22%'],
                ['label' => 'Location', 'width' => '15%'],
                ['label' => 'Doctors', 'class' => 'text-center'],
                ['label' => 'With Services', 'class' => 'text-center'],
                ['label' => 'Status']
            ],
            'doctorKey' => 'services',
            'statusField' => 'services_info_complete',
            'statusLabels' => ['Complete', 'Pending'],
            'statusClasses' => ['badge-soft-success', 'badge-soft-warning']
        ],
        'video' => [
            'title' => 'Video Calling',
            'description' => 'Doctors who have created video calling availability slots.',
            'stats' => $stats['video'],
            'rows' => $video,
            'columns' => [
                ['label' => 'Clinic', 'width' => '22%'],
                ['label' => 'Location', 'width' => '15%'],
                ['label' => 'Doctors', 'class' => 'text-center'],
                ['label' => 'With Video', 'class' => 'text-center'],
                ['label' => 'Status']
            ],
            'doctorKey' => 'video',
            'statusField' => 'has_any_video_config',
            'statusLabels' => ['Configured', 'Missing'],
            'statusClasses' => ['badge-soft-info', 'badge-soft-warning']
        ],
        'clinicHours' => [
            'title' => 'Clinic Hours',
            'description' => 'Regular clinic schedule details completed during onboarding.',
            'stats' => $stats['clinic_hours'],
            'rows' => $clinicHours,
            'columns' => [
                ['label' => 'Clinic', 'width' => '22%'],
                ['label' => 'Location', 'width' => '15%'],
                ['label' => 'Doctors', 'class' => 'text-center'],
                ['label' => 'With Hours', 'class' => 'text-center'],
                ['label' => 'Status']
            ],
            'doctorKey' => 'clinic_hours',
            'statusField' => 'has_any_clinic_hours',
            'statusLabels' => ['Scheduled', 'Missing'],
            'statusClasses' => ['badge-soft-success', 'badge-soft-warning']
        ],
        'emergency' => [
            'title' => 'Emergency Coverage',
            'description' => 'Emergency response hours and doctor participation.',
            'stats' => $stats['emergency'],
            'rows' => $emergency,
            'columns' => [
                ['label' => 'Clinic', 'width' => '22%'],
                ['label' => 'Location', 'width' => '15%'],
                ['label' => 'Doctors', 'class' => 'text-center'],
                ['label' => 'In Program', 'class' => 'text-center'],
                ['label' => 'Status']
            ],
            'doctorKey' => 'emergency',
            'statusField' => 'has_emergency_program',
            'statusLabels' => ['Ready', 'Missing'],
            'statusClasses' => ['badge-soft-danger', 'badge-soft-warning']
        ],
        'documents' => [
            'title' => 'License & Documents',
            'description' => 'Compliance checklist for clinic licenses and doctor credentials.',
            'stats' => $stats['documents'],
            'rows' => $documents,
            'columns' => [
                ['label' => 'Clinic', 'width' => '22%'],
                ['label' => 'Location', 'width' => '15%'],
                ['label' => 'Doctors', 'class' => 'text-center'],
                ['label' => 'Verified Docs', 'class' => 'text-center'],
                ['label' => 'Status']
            ],
            'doctorKey' => 'documents',
            'statusField' => 'documents_complete',
            'statusLabels' => ['Verified', 'Pending'],
            'statusClasses' => ['badge-soft-primary', 'badge-soft-warning']
        ],
    ];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h2 class="h4 mb-1">SnoutIQ Admin Overview</h2>
            <p class="mb-0 text-muted">Review clinic onboarding progress alongside the rest of the admin tools.</p>
        </div>
        <div class="text-end small text-muted">
            Updated {{ now()->format('d M Y') }}
        </div>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="onboarding-search-wrap flex-grow-1 flex-md-grow-0">
            <div class="input-group">
                <span class="input-group-text border-end-0">
                    <i class="bi bi-search"></i>
                </span>
                <input type="search"
                       class="form-control border-start-0"
                       placeholder="Search clinics or doctors"
                       aria-label="Search clinics or doctors"
                       data-onboarding-search>
            </div>
        </div>
        <div class="search-summary" data-search-summary></div>
    </div>

    <section id="overview" class="mb-5">
        <div class="card admin-card mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-chip text-center">
                            <strong>{{ number_format($summary['clinics']) }}</strong>
                            <span>Total Clinics</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-chip text-center">
                            <strong>{{ number_format($summary['doctors']) }}</strong>
                            <span>Total Doctors</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-chip text-center">
                            <strong>{{ number_format($summary['video_ready']) }}</strong>
                            <span>Doctors with Video</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-chip text-center">
                            <strong>{{ number_format($summary['emergency_ready']) }}</strong>
                            <span>Emergency Ready</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-chip text-center">
                            <strong>{{ number_format($summary['documents_ready']) }}</strong>
                            <span>Docs Uploaded</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card admin-card">
                <div class="card-header border-0 pt-4 pb-0">
                    <ul class="nav nav-pills tab-pill mb-0" id="admin-tab" role="tablist">
                        @foreach($sections as $key => $section)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if($loop->first) active @endif" id="tab-{{ $key }}" data-bs-toggle="pill" data-bs-target="#pane-{{ $key }}" type="button" role="tab" aria-controls="pane-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ $section['title'] }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="admin-tabContent">
                        @foreach($sections as $key => $section)
                            <div class="tab-pane fade @if($loop->first) show active @endif" id="pane-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}">
                                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                                    <div>
                                        <h3 class="section-title mb-1">{{ $section['title'] }} <span>overview</span></h3>
                                        <p class="text-muted mb-0">{{ $section['description'] }}</p>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($section['stats'] as $label => $value)
                                            <div class="stat-chip text-center">
                                                <strong>{{ number_format($value) }}</strong>
                                                <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                @foreach($section['columns'] as $column)
                                                    <th @if(isset($column['width'])) style="min-width: {{ $column['width'] }};" @endif class="{{ $column['class'] ?? '' }}">{{ $column['label'] }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($section['rows'] as $clinic)
                                                @php
                                                    $blockId = $key.'-clinic-'.$loop->index;
                                                    $searchTokens = Str::lower(trim(
                                                        ($clinic['clinic_name'] ?? $clinic['clinic'] ?? '') . ' ' .
                                                        ($clinic['slug'] ?? '') . ' ' .
                                                        collect($clinic['doctors'] ?? [])->pluck('doctor_name')->implode(' ')
                                                    ));
                                                @endphp
                                                <tr data-clinic-block="{{ $blockId }}" data-search-text="{{ $searchTokens }}">
                                                    <td>
                                                        <div class="fw-semibold">{{ $clinic['clinic_name'] }}</div>
                                                        <div class="small text-muted">Slug: {{ $clinic['slug'] ?? '—' }}</div>
                                                        <div class="small text-muted">Email: {{ $clinic['email'] ?? '—' }}</div>
                                                    </td>
                                                    <td>
                                                        <div>{{ $clinic['city'] ?? '—' }}</div>
                                                        <div class="small text-muted">Pincode: {{ $clinic['pincode'] ?? '—' }}</div>
                                                    </td>
                                                    <td class="text-center">{{ $clinic['doctor_count'] }}</td>
                                                    <td class="text-center">
                                                        @if($key === 'services')
                                                            {{ $clinic['doctors_with_services'] }}
                                                        @elseif($key === 'video')
                                                            {{ $clinic['doctors_with_video'] }}
                                                        @elseif($key === 'clinicHours')
                                                            {{ $clinic['doctors_with_clinic_hours'] }}
                                                        @elseif($key === 'documents')
                                                            {{ $clinic['doctors_with_documents'] }}
                                                        @else
                                                            {{ $clinic['doctors_in_emergency'] }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $statusField = $section['statusField'];
                                                            $isComplete = data_get($clinic, $statusField, false);
                                                            $badgeClass = $isComplete ? $section['statusClasses'][0] : $section['statusClasses'][1];
                                                            $badgeLabel = $isComplete ? $section['statusLabels'][0] : $section['statusLabels'][1];
                                                        @endphp
                                                        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                                    </td>
                                                </tr>
                                                <tr data-clinic-block="{{ $blockId }}" data-search-text="{{ $searchTokens }}">
                                                    <td colspan="{{ count($section['columns']) }}" class="bg-body-tertiary">
                                                        <div class="row g-3 py-3">
                                                            <div class="col-lg-3 col-sm-6">
                                                                <div class="small text-uppercase text-muted mb-1">Contact</div>
                                                                <div class="fw-medium">{{ $clinic['mobile'] ?? '—' }}</div>
                                                                <div class="small text-muted">{{ $clinic['address'] ?? 'Address unavailable' }}</div>
                                                                @php
                                                                    $consultationPrice = $clinic['consultation_price'] ?? null;
                                                                    $clinicServiceCount = (int) ($clinic['clinic_service_count'] ?? 0);
                                                                    $clinicServiceNames = collect($clinic['clinic_services'] ?? [])->pluck('name')->filter()->values()->all();
                                                                    $clinicProgress = $doctorProgress[$clinic['clinic_id']] ?? [
                                                                        'doctors' => [],
                                                                        'totals' => [
                                                                            'total_doctors' => 0,
                                                                            'all_steps_complete' => 0,
                                                                        ],
                                                                    ];
                                                                    $completedDoctors = data_get($clinicProgress, 'totals.all_steps_complete', 0);
                                                                    $totalDoctors = data_get($clinicProgress, 'totals.total_doctors', $clinic['doctor_count']);
                                                                    $clinicLicenseNo = $clinic['clinic_license_no'] ?? null;
                                                                    $clinicLicenseDoc = $clinic['clinic_license_document'] ?? null;
                                                                    $documentsComplete = $clinic['documents_complete'] ?? false;
                                                                    $stepCompletionPercent = $totalDoctors > 0
                                                                        ? round(($completedDoctors / max($totalDoctors, 1)) * 100)
                                                                        : 0;
                                                                @endphp
                                                                @if($key === 'documents')
                                                                    <div class="small text-muted mt-2">
                                                                        Clinic License:
                                                                        <span class="{{ $clinicLicenseNo ? 'text-success fw-semibold' : 'text-warning' }}">
                                                                            {{ $clinicLicenseNo ?? 'Pending' }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="small text-muted">
                                                                        Compliance File:
                                                                        @if($clinicLicenseDoc)
                                                                            <a href="{{ asset($clinicLicenseDoc) }}" target="_blank" rel="noopener noreferrer">View document</a>
                                                                        @else
                                                                            Pending upload
                                                                        @endif
                                                                    </div>
                                                                    <div class="small text-muted">
                                                                        Doctors Verified: {{ $clinic['doctors_with_documents'] }} / {{ $clinic['doctor_count'] }}
                                                                    </div>
                                                                    <div class="small text-muted">
                                                                        Clinic Status: {{ $documentsComplete ? 'Complete' : 'Pending' }}
                                                                    </div>
                                                                @else
                                                                    @if(!is_null($consultationPrice))
                                                                        <div class="small text-muted mt-2">Emergency Fee: ₹{{ number_format($consultationPrice, 2) }}</div>
                                                                    @endif
                                                                    @if($clinicServiceCount > 0)
                                                                        <div class="small text-muted">Clinic Services: {{ $clinicServiceCount }}</div>
                                                                    @endif
                                                                    @if($totalDoctors > 0)
                                                                        <div class="small text-muted">All Steps Complete: {{ $completedDoctors }} / {{ $totalDoctors }}</div>
                                                                    @endif
                                                                    @if(!empty($clinic['night_slots']))
                                                                        <div class="small text-muted">Night Slots: {{ implode(', ', $clinic['night_slots']) }}</div>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                            <div class="col-lg-9">
                                                                <div class="small text-uppercase text-muted mb-2">Doctor Details</div>
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm align-middle mb-0">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Name</th>
                                                                                <th>Email</th>
                                                                                <th>Mobile</th>
                                                                                <th>License</th>
                                                                                <th class="text-center">Status</th>
                                                                                <th class="text-center">Progress</th>
                                                                                <th>Onboarding Steps</th>
                                                                                <th>Notes</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @forelse($clinic['doctors'] as $doctor)
                                                                                <tr>
                                                                                    <td>{{ $doctor['doctor_name'] }}</td>
                                                                                    <td>{{ $doctor['doctor_email'] ?? '—' }}</td>
                                                                                    <td>{{ $doctor['doctor_mobile'] ?? '—' }}</td>
                                                                                    <td>{{ $doctor['doctor_license'] ?? '—' }}</td>
                                                                                    <td class="text-center">
                                                                                        @php
                                                                                            $doctorStatus = false;
                                                                                            $notes = '';
                                                                                            $doctorProgressEntry = $clinicProgress['doctors'][$doctor['doctor_id']] ?? null;
                                                                                            $doctorSteps = is_array($doctorProgressEntry) ? ($doctorProgressEntry['steps'] ?? []) : [];
                                                                                            $pendingStepLabels = is_array($doctorProgressEntry) ? ($doctorProgressEntry['pending_step_labels'] ?? []) : [];
                                                                                            $allStepsComplete = is_array($doctorProgressEntry) ? ($doctorProgressEntry['all_steps_complete'] ?? false) : false;
                                                                                            if ($key === 'services') {
                                                                                                $clinicPricingConfigured = !is_null($clinic['chat_price'] ?? null);
                                                                                                $doctorStatus = (bool) ($doctorSteps['services'] ?? false);
                                                                                                if ($doctorStatus && !empty($doctor['services'])) {
                                                                                                    $notes = collect($doctor['services'])
                                                                                                        ->map(fn($service) => ucfirst(str_replace('_', ' ', $service['service_type'])) . ' • ' . $service['slot_count'] . ' slots')
                                                                                                        ->implode(', ');
                                                                                                } elseif ($doctorStatus && $clinicServiceCount > 0) {
                                                                                                    $preview = [];
                                                                                                    if (!empty($clinicServiceNames)) {
                                                                                                        $preview = array_slice($clinicServiceNames, 0, 3);
                                                                                                    }
                                                                                                    $notes = 'Clinic-level services configured';
                                                                                                    if ($clinicServiceCount > 0) {
                                                                                                        $notes .= ' (' . $clinicServiceCount . ')';
                                                                                                    }
                                                                                                    if (!empty($preview)) {
                                                                                                        $notes .= ' • ' . implode(', ', $preview);
                                                                                                    }
                                                                                                } elseif ($doctorStatus && $clinicPricingConfigured) {
                                                                                                    $notes = 'Clinic pricing configured (₹' . number_format($clinic['chat_price'], 2) . ')';
                                                                                                } elseif ($clinicPricingConfigured) {
                                                                                                    $notes = 'Consultation pricing set but no services saved';
                                                                                                } else {
                                                                                                    $notes = 'No services configured';
                                                                                                }
                                                                                            } elseif ($key === 'video') {
                                                                                                $doctorStatus = data_get($doctor, 'video.has_data', false);
                                                                                                $notes = $doctorStatus
                                                                                                    ? ($doctor['video']['slot_count'] . ' slots')
                                                                                                    : 'No availability created';
                                                                                            } elseif ($key === 'clinicHours') {
                                                                                                $doctorStatus = data_get($doctor, 'clinic_hours.has_data', false);
                                                                                                $notes = $doctorStatus
                                                                                                    ? ($doctor['clinic_hours']['slot_count'] . ' slots')
                                                                                                    : 'No schedule configured';
                                                                                            } elseif ($key === 'emergency') {
                                                                                                $doctorStatus = data_get($doctor, 'emergency.is_listed', false);
                                                                                                $notes = $doctorStatus
                                                                                                    ? 'Listed for emergency cover'
                                                                                                    : 'Not participating yet';
                                                                                            } else {
                                                                                                $docMeta = $doctor['documents'] ?? [];
                                                                                                $doctorStatus = (bool) data_get($docMeta, 'completed', false);
                                                                                                $hasLicense = (bool) data_get($docMeta, 'has_license', false);
                                                                                                $hasDocument = (bool) data_get($docMeta, 'has_document', false);
                                                                                                if ($doctorStatus) {
                                                                                                    $notes = 'License & credential verified';
                                                                                                } else {
                                                                                                    $missing = [];
                                                                                                    if (!$hasLicense) {
                                                                                                        $missing[] = 'license';
                                                                                                    }
                                                                                                    if (!$hasDocument) {
                                                                                                        $missing[] = 'document';
                                                                                                    }
                                                                                                    $notes = 'Missing ' . implode(' & ', $missing);
                                                                                                }
                                                                                            }
                                                                                            $onboardingNote = empty($pendingStepLabels)
                                                                                                ? 'All onboarding steps completed'
                                                                                                : 'Pending: ' . implode(', ', $pendingStepLabels);
                                                                                            $notes = $notes
                                                                                                ? $onboardingNote . ' • ' . $notes
                                                                                                : $onboardingNote;
                                                                                            $badgeClass = $allStepsComplete ? 'badge-soft-success' : ($doctorStatus ? 'badge-soft-info' : 'badge-soft-warning');
                                                                                            $badgeLabel = $allStepsComplete ? 'All Steps Ready' : ($doctorStatus ? 'Ready' : 'Missing');
                                                                                            $totalStepCount = count($stepLabels);
                                                                                            $completedStepCount = 0;
                                                                                            foreach ($stepLabels as $stepKey => $_label) {
                                                                                                if (!empty($doctorSteps[$stepKey])) {
                                                                                                    $completedStepCount++;
                                                                                                }
                                                                                            }
                                                                                            $doctorPercent = $totalStepCount > 0
                                                                                                ? round(($completedStepCount / $totalStepCount) * 100)
                                                                                                : 0;
                                                                                        @endphp
                                                                                        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                                                                    </td>
                                                                                    <td class="doctor-progress">
                                                                                        <div class="clinic-progress-bar mb-1">
                                                                                            <span style="width: {{ $doctorPercent }}%;"></span>
                                                                                        </div>
                                                                                        <small>{{ $completedStepCount }} / {{ $totalStepCount }} steps</small>
                                                                                    </td>
                                                                                    <td>
                                                                                        <div class="step-chips">
                                                                                            @foreach($stepLabels as $stepKey => $stepLabel)
                                                                                                @php
                                                                                                    $stepDone = (bool) ($doctorSteps[$stepKey] ?? false);
                                                                                                @endphp
                                                                                                <span class="step-chip {{ $stepDone ? 'complete' : 'pending' }}">{{ $stepLabel }}</span>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    </td>
                                                                                    <td>
                                                                                        @php
                                                                                            $notesText = $notes ?? '';
                                                                                            if ($notesText === '' && !empty($pendingStepLabels)) {
                                                                                                $notesText = 'Pending: ' . implode(', ', $pendingStepLabels);
                                                                                            }
                                                                                        @endphp
                                                                                        <span class="small text-muted">{{ $notesText !== '' ? $notesText : '—' }}</span>
                                                                                    </td>
                                                                                </tr>
                                                                            @empty
                                                                                <tr>
                                                                                    <td colspan="7" class="text-center text-muted">No doctors recorded for this clinic.</td>
                                                                                </tr>
                                                                            @endforelse
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ count($section['columns']) }}" class="text-center py-5 text-muted">No clinics found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($key === 'video')
                                    @include('admin.partials.video-slot-matrix', [
                                        'slotMatrixTitle' => 'Video Calling Slot Coverage',
                                        'slotMatrixDescription' => 'Live doctor availability matrix for onboarding clinics.',
                                        'slotMatrixBadge' => 'Select a date to refresh',
                                        'slotMatrixCardClass' => 'card admin-card border-0',
                                        'slotMatrixShowControls' => true,
                                        'slotMatrixRefreshLabel' => 'Refresh Coverage'
                                    ])
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
    </section>


    <section id="users" class="mb-5">
    <div class="card admin-card">
        <div class="card-header border-0 pt-4 pb-0">
            <div>
                <h2 class="h5 mb-1">Users</h2>
                <p class="text-muted small mb-0">Latest 25 sign-ups</p>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="text-muted small text-uppercase">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentUsers as $user)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $user->name ?? '—' }}</div>
                                    <div class="text-muted small">#{{ $user->id }}</div>
                                </td>
                                <td>{{ $user->email ?? '—' }}</td>
                                <td>{{ $user->phone ?? '—' }}</td>
                                <td>{{ $user->created_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No user records available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>


    <section id="pets" class="mb-5">
    <div class="card admin-card">
        <div class="card-header border-0 pt-4 pb-0">
            <div>
                <h2 class="h5 mb-1">Pets</h2>
                <p class="text-muted small mb-0">Latest 25 registered pets</p>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="text-muted small text-uppercase">
                        <tr>
                            <th>Pet</th>
                            <th>Owner</th>
                            <th>Breed</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPets as $pet)
                            @php
                                $petMeta = collect([
                                    $pet->pet_gender ? ucfirst($pet->pet_gender) : null,
                                    $pet->pet_age ? $pet->pet_age . ' yrs' : null,
                                ])->filter()->implode(' • ');
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $pet->name ?? '—' }}</div>
                                    <div class="text-muted small">{{ $petMeta !== '' ? $petMeta : '—' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ optional($pet->owner)->name ?? '—' }}</div>
                                    <div class="text-muted small">{{ optional($pet->owner)->email ?? '—' }}</div>
                                </td>
                                <td>{{ $pet->breed ?? '—' }}</td>
                                <td>{{ $pet->created_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No pet records available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>


    <section id="doctors" class="mb-5">
    <div class="card admin-card">
        <div class="card-header border-0 pt-4 pb-0">
            <div>
                <h2 class="h5 mb-1">Doctors</h2>
                <p class="text-muted small mb-0">Alphabetical snapshot (first 25)</p>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="text-muted small text-uppercase">
                        <tr>
                            <th>Doctor</th>
                            <th>Clinic</th>
                            <th>Phone</th>
                            <th>License</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentDoctors as $doctor)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $doctor->doctor_name ?? '—' }}</div>
                                    <div class="text-muted small">{{ $doctor->doctor_email ?? '—' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ optional($doctor->clinic)->name ?? '—' }}</div>
                                    <div class="text-muted small">{{ optional($doctor->clinic)->city ?? '—' }}</div>
                                </td>
                                <td>{{ $doctor->doctor_mobile ?? '—' }}</td>
                                <td>{{ $doctor->doctor_license ?? '—' }}</td>
                                <td>{{ $doctor->created_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No doctor records available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>


    <section id="clinics" class="mb-5">
    <div class="card admin-card">
        <div class="card-header border-0 pt-4 pb-0">
            <div>
                <h2 class="h5 mb-1">Vet Registrations</h2>
                <p class="text-muted small mb-0">Latest 25 clinics from vet_registerations_temp</p>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="text-muted small text-uppercase">
                        <tr>
                            <th>Clinic</th>
                            <th>Location</th>
                            <th>Email</th>
                            <th class="text-center">Doctors</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentClinics as $clinic)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $clinic->name ?? '—' }}</div>
                                    <div class="text-muted small">#{{ $clinic->id }}</div>
                                </td>
                                <td>
                                    <div>{{ $clinic->city ?? '—' }}</div>
                                    <div class="text-muted small">{{ $clinic->pincode ?? '—' }}</div>
                                </td>
                                <td>{{ $clinic->email ?? '—' }}</td>
                                <td class="text-center">{{ number_format($clinic->doctors_count ?? 0) }}</td>
                                <td>{{ $clinic->created_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No clinic records available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.querySelector('[data-onboarding-search]');
        const searchSummary = document.querySelector('[data-search-summary]');
        const blockMap = new Map();
        document.querySelectorAll('[data-clinic-block]').forEach(row => {
            const blockId = row.getAttribute('data-clinic-block');
            if (!blockId) {
                return;
            }
            if (!blockMap.has(blockId)) {
                blockMap.set(blockId, []);
            }
            blockMap.get(blockId).push(row);
        });

        const applySearch = () => {
            if (!searchInput) {
                return;
            }
            const term = searchInput.value.trim().toLowerCase();
            let visibleBlocks = 0;
            blockMap.forEach(rows => {
                const matches = term === '' || rows.some(row => (row.dataset.searchText || '').includes(term));
                rows.forEach(row => row.classList.toggle('d-none', !matches));
                if (matches) {
                    visibleBlocks++;
                }
            });
            if (searchSummary) {
                searchSummary.textContent = term
                    ? `${visibleBlocks} clinic${visibleBlocks === 1 ? '' : 's'} match "${term}"`
                    : '';
            }
        };

        if (searchInput) {
            searchInput.addEventListener('input', applySearch);
            applySearch();
        }

        const sidebarNav = document.querySelector('[data-panel-nav]');
        const sidebarLinks = sidebarNav ? Array.from(sidebarNav.querySelectorAll('a[href^="#"]')) : [];
        const observedSections = sidebarLinks
            .map(link => document.querySelector(link.getAttribute('href')))
            .filter(Boolean);
        const setActiveLink = (hash) => {
            if (!sidebarLinks.length) {
                return;
            }
            let matched = false;
            sidebarLinks.forEach(link => {
                const isMatch = link.getAttribute('href') === hash;
                link.classList.toggle('active', isMatch);
                if (isMatch) {
                    matched = true;
                }
            });
            if (!matched) {
                const fallback = sidebarLinks.find(link => link.getAttribute('href') === '#overview');
                if (fallback) {
                    sidebarLinks.forEach(link => link.classList.toggle('active', link === fallback));
                }
            }
        };

        const scrollOffset = 90;
        if (sidebarLinks.length) {
            const initialHash = window.location.hash || '#overview';
            setActiveLink(initialHash);

            sidebarLinks.forEach(link => {
                link.addEventListener('click', (event) => {
                    const targetSelector = link.getAttribute('href');
                    if (!targetSelector || !targetSelector.startsWith('#')) {
                        return;
                    }
                    const target = document.querySelector(targetSelector);
                    if (!target) {
                        return;
                    }
                    event.preventDefault();
                    const top = target.getBoundingClientRect().top + window.scrollY - scrollOffset;
                    window.scrollTo({ top, behavior: 'smooth' });
                    if (history.replaceState) {
                        history.replaceState(null, '', targetSelector);
                    } else {
                        window.location.hash = targetSelector;
                    }
                    setActiveLink(targetSelector);
                });
            });

            if (window.IntersectionObserver && observedSections.length) {
                const observer = new IntersectionObserver((entries) => {
                    const visible = entries
                        .filter(entry => entry.isIntersecting)
                        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                    if (!visible) {
                        return;
                    }
                    const id = '#' + visible.target.id;
                    setActiveLink(id);
                }, { rootMargin: '-45% 0px -45% 0px', threshold: [0.2, 0.35, 0.5] });

                observedSections.forEach(section => observer.observe(section));
            }
        }

        const initialTabHash = window.location.hash.replace('#', '');
        if (initialTabHash) {
            const tabTrigger = document.querySelector(`[data-bs-target="#pane-${initialTabHash}"]`);
            if (tabTrigger && window.bootstrap && typeof window.bootstrap.Tab === 'function') {
                new bootstrap.Tab(tabTrigger).show();
            }
        }

        const tabButtons = document.querySelectorAll('#admin-tab [data-bs-toggle="pill"]');
        tabButtons.forEach(button => {
            button.addEventListener('shown.bs.tab', (event) => {
                const targetId = event.target.getAttribute('data-bs-target');
                if (!targetId) {
                    return;
                }
                const hash = targetId.replace('#pane-', '#');
                if (history.replaceState) {
                    history.replaceState(null, '', hash);
                } else {
                    window.location.hash = hash;
                }
                setActiveLink(hash);
            });
        });
    });
</script>
@endpush
