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

@if(isset($callMetrics))
    <div class="row g-4 mt-1">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Total Video Sessions</p>
                    <h2 class="fw-bold mb-0">{{ number_format($callMetrics['total_sessions']) }}</h2>
                    <p class="mb-0 text-muted small">All sessions opened by pet parents.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Completed Sessions</p>
                    <h2 class="fw-bold mb-0">{{ number_format($callMetrics['completed_sessions']) }}</h2>
                    <p class="mb-0 text-muted small">Calls that reached an ended state.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Total Video Minutes</p>
                    <h2 class="fw-bold mb-0">{{ $callMetrics['total_duration_human'] }}</h2>
                    <p class="mb-0 text-muted small">Aggregated duration across ended calls.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Fees Collected</p>
                    <h2 class="fw-bold mb-0">₹{{ number_format($callMetrics['total_revenue_rupees'], 2) }}</h2>
                    <p class="mb-0 text-muted small">Based on verified Razorpay payments.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Paid Sessions</p>
                    <h3 class="fw-bold mb-0">{{ number_format($callMetrics['paid_sessions']) }}</h3>
                    <p class="mb-0 text-muted small">Sessions where payment status is marked paid.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Avg. Call Length</p>
                    <h3 class="fw-bold mb-0">{{ $callMetrics['average_duration_human'] }}</h3>
                    <p class="mb-0 text-muted small">Calculated across ended sessions.</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3">Recent Video Consultations</h2>
                    @php
                        $formatDuration = static function ($seconds) {
                            $seconds = (int) $seconds;
                            if ($seconds <= 0) {
                                return '—';
                            }

                            return \Carbon\CarbonInterval::seconds($seconds)->cascade()->forHumans(['short' => true]);
                        };
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                    <th>Duration</th>
                                    <th>Fee</th>
                                    <th class="text-nowrap">Started</th>
                                    <th class="text-nowrap">Ended</th>
                                    <th class="text-nowrap">Links</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentCalls as $call)
                                    <tr>
                                        <td>#{{ $call->id }}</td>
                                        <td>{{ $call->patient?->name ?? '—' }}<div class="text-muted small">ID: {{ $call->patient_id }}</div></td>
                                        <td>{{ $call->doctor?->doctor_name ?? '—' }}<div class="text-muted small">ID: {{ $call->doctor_id ?? '—' }}</div></td>
                                        <td><span class="badge text-bg-light">{{ strtoupper($call->status) }}</span></td>
                                        <td>{{ $formatDuration($call->duration_seconds) }}</td>
                                        <td>
                                            @if ($call->payment_status === 'paid')
                                                ₹{{ number_format(($call->amount_paid ?? 0) / 100, 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ optional($call->started_at)->format('d M, H:i') ?? '—' }}</td>
                                        <td class="text-nowrap">{{ optional($call->ended_at)->format('d M, H:i') ?? '—' }}</td>
                                        <td class="text-nowrap">
                                            @php
                                                $joinUrl = $call->doctor_join_url;
                                                $paymentUrl = $call->patient_payment_url;
                                            @endphp
                                            @if ($joinUrl || $paymentUrl)
                                                <div class="d-flex gap-2">
                                                    @if ($joinUrl)
                                                        <a href="{{ $joinUrl }}" target="_blank" rel="noopener noreferrer" class="badge text-bg-primary-subtle text-primary-emphasis text-decoration-none px-2 py-1">
                                                            Doctor Join
                                                        </a>
                                                    @endif
                                                    @if ($paymentUrl)
                                                        <a href="{{ $paymentUrl }}" target="_blank" rel="noopener noreferrer" class="badge text-bg-success-subtle text-success-emphasis text-decoration-none px-2 py-1">
                                                            Payment
                                                        </a>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No call sessions recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

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
