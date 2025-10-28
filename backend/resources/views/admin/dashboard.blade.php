@extends('layouts.admin-panel')

@section('page-title', 'Dashboard')

@section('content')
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column gap-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar-sm rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-people-fill fs-4"></i>
                    </span>
                    <div>
                        <p class="text-muted text-uppercase small mb-1">Total Users</p>
                        <h2 class="fw-bold mb-0">{{ number_format($stats['total_users']) }}</h2>
                    </div>
                </div>
                <p class="mb-0 text-muted small">All registered pet parents and providers inside SnoutIQ.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column gap-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar-sm rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-calendar-check fs-4"></i>
                    </span>
                    <div>
                        <p class="text-muted text-uppercase small mb-1">Total Bookings</p>
                        <h2 class="fw-bold mb-0">{{ number_format($stats['total_bookings']) }}</h2>
                    </div>
                </div>
                <p class="mb-0 text-muted small">Confirmed grooming appointments tracked through the platform.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column gap-2">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar-sm rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-life-preserver fs-4"></i>
                    </span>
                    <div>
                        <p class="text-muted text-uppercase small mb-1">Support Tickets</p>
                        <h2 class="fw-bold mb-0">{{ number_format($stats['total_supports']) }}</h2>
                    </div>
                </div>
                <p class="mb-0 text-muted small">Active help requests raised by customers and clinics.</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h2 class="h5 fw-semibold mb-1">Available Clinics</h2>
                        <p class="text-muted small mb-0">
                            {{ $onlineClinics->count() }} {{ \Illuminate\Support\Str::plural('clinic', $onlineClinics->count()) }} currently available.
                        </p>
                    </div>
                    <span class="badge text-bg-success-subtle text-success-emphasis">Live availability</span>
                </div>

                @if ($onlineClinics->isEmpty())
                    <div class="text-center text-muted py-5">
                        <div class="fw-semibold mb-1">No clinics have online doctors right now.</div>
                        <div class="small">Clinics appear here when at least one doctor toggles on and connects to the live console.</div>
                    </div>
                @else
                    <div class="list-group list-group-flush mt-4">
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
    </div>
</div>
@endsection
