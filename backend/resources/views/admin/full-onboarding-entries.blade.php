@extends('layouts.admin-panel')

@section('page-title', 'Full Onboarding Entries')

@php
    $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    $fmtMoney = static fn ($value) => $value === null ? '—' : '₹'.number_format((float) $value, 2);
    $fmtPaise = static fn ($value) => '₹'.number_format(((int) ($value ?? 0)) / 100, 2);
    $fmtTime = static fn ($value) => $value ? substr((string) $value, 0, 5) : '—';
    $normalizeTransactionKind = static function ($value): ?string {
        $value = strtolower(trim((string) $value));
        return match ($value) {
            'video_consult', 'video_consultation', 'video_call' => 'video_consult',
            'appointment', 'appointments' => 'appointment',
            'excell_export_campaign', 'excel_export_campaign' => 'excell_export_campaign',
            default => null,
        };
    };
    $transactionKind = static function ($transaction) use ($normalizeTransactionKind): ?string {
        return $normalizeTransactionKind($transaction->status ?? null)
            ?? $normalizeTransactionKind($transaction->type ?? null)
            ?? $normalizeTransactionKind(data_get($transaction->metadata ?? [], 'order_type'));
    };
    $firstFilled = static function (array $values): ?string {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    };
    $transactionUserName = static fn ($transaction): string => $firstFilled([
        $transaction->user->name ?? null,
        data_get($transaction->metadata ?? [], 'user_name'),
        data_get($transaction->metadata ?? [], 'customer_name'),
        data_get($transaction->metadata ?? [], 'parent_name'),
        data_get($transaction->metadata ?? [], 'pet_parent_name'),
        data_get($transaction->metadata ?? [], 'notes.user_name'),
        data_get($transaction->metadata ?? [], 'notes.customer_name'),
        data_get($transaction->metadata ?? [], 'notes.parent_name'),
        data_get($transaction->metadata ?? [], 'notes.pet_parent_name'),
    ]) ?? '—';
    $transactionUserContact = static fn ($transaction): string => $firstFilled([
        $transaction->user->phone ?? null,
        $transaction->user->email ?? null,
        data_get($transaction->metadata ?? [], 'phone'),
        data_get($transaction->metadata ?? [], 'user_phone'),
        data_get($transaction->metadata ?? [], 'customer_phone'),
        data_get($transaction->metadata ?? [], 'email'),
        data_get($transaction->metadata ?? [], 'user_email'),
        data_get($transaction->metadata ?? [], 'notes.phone'),
        data_get($transaction->metadata ?? [], 'notes.user_phone'),
        data_get($transaction->metadata ?? [], 'notes.customer_phone'),
        data_get($transaction->metadata ?? [], 'notes.email'),
        data_get($transaction->metadata ?? [], 'notes.user_email'),
    ]) ?? '—';
    $transactionDoctorName = static fn ($transaction): string => $firstFilled([
        $transaction->doctor->doctor_name ?? null,
        data_get($transaction->metadata ?? [], 'doctor_name'),
        data_get($transaction->metadata ?? [], 'doctor.name'),
        data_get($transaction->metadata ?? [], 'notes.doctor_name'),
        data_get($transaction->metadata ?? [], 'notes.doctor.name'),
    ]) ?? ((int) ($transaction->doctor_id ?? 0) > 0 ? 'Doctor #'.$transaction->doctor_id : '—');
    $transactionLabels = static fn (?string $kind): array => match ($kind) {
        'video_consult' => ['In app call', 'Video consult'],
        'appointment' => ['In clinic'],
        'excell_export_campaign' => ['Web', 'Video call'],
        default => ['—'],
    };
    $fmtDateTime = static function ($value): string {
        if (!$value) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
@endphp

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="h5 mb-1">Full onboarding data</h2>
                        <p class="text-muted mb-0">
                            Clinic registrations{{ $fromDate ? ' from '.\Illuminate\Support\Carbon::parse($fromDate)->format('d M Y') : '' }} with doctors, services, packages, clinic hours, video hours, and vet-at-home settings. Recent clinics appear first.
                        </p>
                    </div>
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                        <form method="GET" action="{{ route('admin.full-onboarding') }}" class="d-flex gap-2">
                            <select name="date_filter" class="form-select form-select-sm" onchange="this.form.querySelector('[name=from_date]').disabled = this.value === 'all'">
                                <option value="from_date" @selected($dateFilter !== 'all')>From date</option>
                                <option value="all" @selected($dateFilter === 'all')>All</option>
                            </select>
                            <input type="date" name="from_date" value="{{ $fromDate ?? '2026-05-10' }}" class="form-control form-control-sm" @disabled($dateFilter === 'all')>
                            <button type="submit" class="btn btn-sm btn-dark">Filter</button>
                        </form>
                        <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($clinics->count()) }} clinics</span>
                    </div>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if($clinics->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-clipboard2-pulse display-6 d-block mb-2"></i>
                        <p class="mb-0">No full onboarding entries found.</p>
                    </div>
                @else
                    <div class="accordion" id="fullOnboardingAccordion">
                        @foreach($clinics as $clinic)
                            @php
                                $clinicServices = $servicesByClinic->get($clinic->id, collect());
                                $packages = $packagesByClinic->get($clinic->id, collect());
                                $homeServices = $vetAtHomeByClinic->get($clinic->id, collect());
                                $clinicTransactions = $transactionsByClinic->get($clinic->id, collect());
                                $doctorNameById = $clinic->doctors->keyBy('id');
                            @endphp
                            <div class="accordion-item border-0 shadow-sm mb-3 rounded overflow-hidden">
                                <h2 class="accordion-header" id="clinicHeading{{ $clinic->id }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#clinicCollapse{{ $clinic->id }}" aria-expanded="false" aria-controls="clinicCollapse{{ $clinic->id }}">
                                        <span class="w-100 d-flex flex-column flex-md-row gap-2 justify-content-between pe-3">
                                            <span>
                                                <span class="fw-semibold">{{ $clinic->name ?? 'Unnamed clinic' }}</span>
                                                <span class="text-muted small ms-2">#{{ $clinic->id }}</span>
                                            </span>
                                            <span class="small text-muted">
                                                {{ $clinic->city ?? '—' }} • {{ number_format($clinic->doctors->count()) }} doctors • {{ number_format($clinicServices->count()) }} services
                                            </span>
                                        </span>
                                    </button>
                                </h2>
                                <div id="clinicCollapse{{ $clinic->id }}" class="accordion-collapse collapse" aria-labelledby="clinicHeading{{ $clinic->id }}" data-bs-parent="#fullOnboardingAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-4">
                                                <div class="p-3 bg-light rounded h-100">
                                                    <div class="text-uppercase small text-muted fw-semibold mb-1">Clinic</div>
                                                    <div class="fw-semibold">{{ $clinic->name ?? '—' }}</div>
                                                    <div class="small text-muted">{{ $clinic->mobile ?? 'No mobile' }} · {{ $clinic->email ?? 'No email' }}</div>
                                                    <div class="small text-muted">{{ $clinic->city ?? '—' }} {{ $clinic->pincode ? '· '.$clinic->pincode : '' }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-3 bg-light rounded h-100">
                                                    <div class="text-uppercase small text-muted fw-semibold mb-2">Clinic Media</div>
                                                    @if(!empty($clinic->clinic_image))
                                                        <img
                                                            src="{{ route('clinics.media.image', $clinic->id) }}"
                                                            alt="{{ $clinic->name ?? 'Clinic' }} image"
                                                            class="img-fluid rounded border mb-2"
                                                            style="max-height: 140px; object-fit: cover;"
                                                        >
                                                    @else
                                                        <div class="small text-muted mb-2">No clinic image blob.</div>
                                                    @endif

                                                    @if(!empty($clinic->clinic_video))
                                                        <video class="w-100 rounded border" style="max-height: 180px;" controls preload="metadata">
                                                            <source src="{{ route('clinics.media.video', $clinic->id) }}" type="video/mp4">
                                                        </video>
                                                    @else
                                                        <div class="small text-muted">No clinic video blob.</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-3 bg-light rounded h-100">
                                                    <div class="text-uppercase small text-muted fw-semibold mb-1">Services</div>
                                                    @forelse($clinicServices as $service)
                                                        <div class="d-flex justify-content-between gap-2 small">
                                                            <span>{{ $service->name ?? '—' }}</span>
                                                            <span class="text-muted">{{ ($service->price_after_service ?? false) ? 'After service' : $fmtMoney($service->price ?? null) }}</span>
                                                        </div>
                                                    @empty
                                                        <span class="text-muted small">No services saved.</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="p-3 bg-light rounded h-100">
                                                    <div class="text-uppercase small text-muted fw-semibold mb-1">Vet At Home</div>
                                                    @forelse($homeServices as $home)
                                                        <div class="small">
                                                            <span class="badge {{ $home->is_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $home->is_enabled ? 'Enabled' : 'Disabled' }}</span>
                                                            <span class="ms-1">{{ $home->protocol_label ?? 'Doorstep Protocol' }}</span>
                                                        </div>
                                                        <div class="small text-muted">Hours: {{ $home->service_hours ?? '—' }}</div>
                                                        <div class="small text-muted">Response: {{ $home->response_time ?? '—' }} · Payout: {{ $fmtMoney($home->base_payout ?? null) }}</div>
                                                    @empty
                                                        <span class="text-muted small">No vet-at-home settings.</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Doctor</th>
                                                        <th>Clinic Hours</th>
                                                        <th>Video Hours</th>
                                                        <th>Packages</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($clinic->doctors as $doctor)
                                                        @php
                                                            $clinicHours = $clinicAvailabilityByDoctor->get($doctor->id, collect());
                                                            $videoHours = $videoAvailabilityByDoctor->get($doctor->id, collect());
                                                            $doctorPackages = $packages->where('doctor_id', $doctor->id);
                                                        @endphp
                                                        <tr>
                                                            <td style="min-width: 180px;">
                                                                <div class="fw-semibold">{{ $doctor->doctor_name ?? 'Doctor #'.$doctor->id }}</div>
                                                                <div class="small text-muted">{{ $doctor->doctor_mobile ?? '—' }}</div>
                                                            </td>
                                                            <td>
                                                                @forelse($clinicHours as $row)
                                                                    <div class="small">
                                                                        <span class="badge text-bg-light">{{ $row->service_type }}</span>
                                                                        {{ $dayNames[(int) $row->day_of_week] ?? $row->day_of_week }}
                                                                        {{ $fmtTime($row->start_time) }}-{{ $fmtTime($row->end_time) }}
                                                                    </div>
                                                                @empty
                                                                    <span class="text-muted small">—</span>
                                                                @endforelse
                                                            </td>
                                                            <td>
                                                                @forelse($videoHours as $row)
                                                                    <div class="small">
                                                                        {{ $dayNames[(int) $row->day_of_week] ?? $row->day_of_week }}
                                                                        {{ $fmtTime($row->start_time) }}-{{ $fmtTime($row->end_time) }}
                                                                    </div>
                                                                @empty
                                                                    <span class="text-muted small">—</span>
                                                                @endforelse
                                                            </td>
                                                            <td style="min-width: 220px;">
                                                                @forelse($doctorPackages as $package)
                                                                    <div class="small">Dog vaccine: {{ $fmtMoney($package->dog_vaccination_package_price) }}</div>
                                                                    <div class="small">Cat vaccine: {{ $fmtMoney($package->cat_vaccination_package_price) }}</div>
                                                                    <div class="small">Dog neuter: {{ $fmtMoney($package->dog_neutering_price) }}</div>
                                                                    <div class="small">Cat neuter: {{ $fmtMoney($package->cat_neutering_price) }}</div>
                                                                @empty
                                                                    <span class="text-muted small">—</span>
                                                                @endforelse
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="p-3 bg-light rounded mb-3">
                                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                                <div>
                                                    <div class="text-uppercase small text-muted fw-semibold">Transactions</div>
                                                    <div class="small text-muted">
                                                        Captured rows matched by clinic id or doctor id for video consult, appointment, and Excel export campaign categories.
                                                    </div>
                                                </div>
                                                <div class="small fw-semibold text-muted">
                                                    {{ number_format($clinicTransactions->count()) }} records
                                                </div>
                                            </div>

                                            @if($clinicTransactions->isEmpty())
                                                <span class="text-muted small">No matching transactions found.</span>
                                            @else
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Date</th>
                                                                <th>Customer</th>
                                                                <th>Doctor</th>
                                                                <th>Shown As</th>
                                                                <th>Status / Type</th>
                                                                <th>Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($clinicTransactions as $transaction)
                                                                @php
                                                                    $kind = $transactionKind($transaction);
                                                                    $labels = $transactionLabels($kind);
                                                                    $userName = $transactionUserName($transaction);
                                                                    $userContact = $transactionUserContact($transaction);
                                                                    $doctorName = $transactionDoctorName($transaction);
                                                                @endphp
                                                                <tr>
                                                                    <td class="fw-semibold">#{{ $transaction->id }}</td>
                                                                    <td class="small text-muted">{{ $fmtDateTime($transaction->created_at ?? null) }}</td>
                                                                    <td>
                                                                        <div class="small fw-semibold">{{ $userName }}</div>
                                                                        <div class="small text-muted">{{ $userContact }}</div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="small fw-semibold">{{ $doctorName }}</div>
                                                                        <div class="small text-muted">#{{ $transaction->doctor_id ?? '—' }}</div>
                                                                    </td>
                                                                    <td>
                                                                        @foreach($labels as $label)
                                                                            <span class="badge text-bg-primary-subtle text-primary-emphasis me-1">{{ $label }}</span>
                                                                        @endforeach
                                                                    </td>
                                                                    <td class="small">
                                                                        <div>Status: <span class="text-muted">{{ $transaction->status ?? '—' }}</span></div>
                                                                        <div>Type: <span class="text-muted">{{ $transaction->type ?? data_get($transaction->metadata ?? [], 'order_type', '—') }}</span></div>
                                                                    </td>
                                                                    <td class="small fw-semibold">{{ $fmtPaise($transaction->amount_paise ?? 0) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="small text-muted">
                                            <div class="d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
                                                <div>
                                                    Full API:
                                                    <code>{{ url('/api/vet-registerations/'.$clinic->id.'/full') }}</code>
                                                </div>
                                                <form method="POST" action="{{ route('admin.full-onboarding.delete', ['clinic' => $clinic->id, 'date_filter' => $dateFilter, 'from_date' => $fromDate]) }}" onsubmit="return confirm('Delete this full onboarding entry and related data?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
