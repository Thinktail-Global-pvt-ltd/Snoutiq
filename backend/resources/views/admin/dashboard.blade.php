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
                <h2 class="h5 fw-semibold mb-3">Active Doctors (live socket feed)</h2>
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
        </div>
    </div>
</div>
@endsection
