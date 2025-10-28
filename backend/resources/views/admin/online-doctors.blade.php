@extends('layouts.admin-panel')

@section('page-title', 'Available Clinics')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h4 fw-semibold mb-1">Available Clinics</h2>
                <p class="text-muted small mb-0">
                    {{ $onlineClinics->count() }} {{ \Illuminate\Support\Str::plural('clinic', $onlineClinics->count()) }} currently available.
                </p>
            </div>
            <span class="badge text-bg-success-subtle text-success-emphasis">Live availability</span>
        </div>

        <div class="mb-4">
            <h2 class="h6 fw-semibold mb-2">Active Doctor IDs</h2>
            @if ($activeDoctors->isEmpty())
                <div class="text-muted small">No doctors are connected to the socket server.</div>
            @else
                @php
                    $formattedActiveDoctors = $activeDoctors->map(static fn ($label) => "'{$label}'")->implode(', ');
                @endphp
                <div class="bg-light rounded border border-light-subtle px-3 py-2">
                    <code>Active Doctor IDs: [ {{ $formattedActiveDoctors }} ]</code>
                </div>
                <div class="small text-muted mt-2">Names resolved from <code>vet_registerations_temp</code>.</div>
            @endif
        </div>

        @if ($onlineClinics->isEmpty())
            <div class="text-center text-muted py-5">
                <div class="fw-semibold mb-1">No clinics are live right now.</div>
                <div class="small">Clinics appear here when at least one doctor toggles on and connects to the console.</div>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach ($onlineClinics as $clinic)
                    <div class="list-group-item px-0 py-3">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold">{{ $clinic->name ?? 'Clinic name unavailable' }}</div>
                                <div class="small text-muted">
                                    @if (!empty($clinic->city))
                                        {{ $clinic->city }}
                                    @endif
                                    @if (!empty($clinic->available_doctors_count))
                                        @if (!empty($clinic->city))
                                            •
                                        @endif
                                        {{ $clinic->available_doctors_count }} {{ \Illuminate\Support\Str::plural('doctor', $clinic->available_doctors_count) }} online
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-3 small text-muted">
                                @if (!empty($clinic->email))
                                    <span>Email: <a href="mailto:{{ $clinic->email }}" class="text-decoration-none">{{ $clinic->email }}</a></span>
                                @endif
                                @if (!empty($clinic->mobile))
                                    <span>Phone: <a href="tel:{{ $clinic->mobile }}" class="text-decoration-none">{{ $clinic->mobile }}</a></span>
                                @endif
                                @if (!empty($clinic->address))
                                    <span>Address: {{ $clinic->address }}</span>
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
