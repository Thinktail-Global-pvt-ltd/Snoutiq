@extends('layouts.admin-panel')

@section('page-title', 'Online Doctors')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h4 fw-semibold mb-1">Online Doctors</h2>
                <p class="text-muted small mb-0">
                    {{ $onlineDoctors->count() }} {{ \Illuminate\Support\Str::plural('doctor', $onlineDoctors->count()) }} currently online.
                </p>
            </div>
            <span class="badge text-bg-success-subtle text-success-emphasis">Live availability</span>
        </div>

        @if ($onlineDoctors->isEmpty())
            <div class="text-center text-muted py-5">
                <div class="fw-semibold mb-1">No doctors are online right now.</div>
                <div class="small">Doctors appear here once they toggle their availability on from their dashboard.</div>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach ($onlineDoctors as $doctor)
                    <div class="list-group-item px-0 py-3">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold">{{ $doctor->doctor_name ?? 'Unnamed doctor' }}</div>
                                <div class="small text-muted">
                                    {{ $doctor->clinic->name ?? 'Clinic not assigned' }}
                                    @if (!empty($doctor->clinic?->city))
                                        • {{ $doctor->clinic->city }}
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-3 small text-muted">
                                @if (!empty($doctor->doctor_email))
                                    <span>Email: <a href="mailto:{{ $doctor->doctor_email }}" class="text-decoration-none">{{ $doctor->doctor_email }}</a></span>
                                @endif
                                @if (!empty($doctor->doctor_mobile))
                                    <span>Phone: <a href="tel:{{ $doctor->doctor_mobile }}" class="text-decoration-none">{{ $doctor->doctor_mobile }}</a></span>
                                @endif
                                @if (!empty($doctor->doctor_license))
                                    <span>License: {{ $doctor->doctor_license }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
