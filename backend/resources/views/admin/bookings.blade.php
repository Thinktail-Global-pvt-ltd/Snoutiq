@extends('layouts.admin-panel')

@section('page-title', 'Bookings')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Recent grooming bookings</h2>
                        <p class="text-muted mb-0">Track the latest appointments placed by pet parents.</p>
                    </div>
                    <span class="badge text-bg-success-subtle text-success-emphasis px-3 py-2">{{ number_format($bookings->count()) }} total</span>
                </div>

                @if($bookings->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-calendar2-week display-6 d-block mb-2"></i>
                        <p class="mb-0">No bookings have been captured yet.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Booking</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Assigned vet</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bookings as $booking)
                                    @php
                                        $customer = optional($booking->user);
                                        $groomer  = optional($booking->groomerEmployee);
                                        $amount   = $booking->total_amount ?? $booking->amount ?? $booking->price ?? null;
                                        $status   = $booking->status ?? $booking->booking_status ?? 'pending';
                                        $service  = $booking->service_type ?? $booking->service_name ?? $booking->service ?? 'Grooming';
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">#{{ $booking->id }}</span>
                                            <div class="text-muted small">{{ $booking->reference ?? 'Manual entry' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $customer->name ?? 'Unknown' }}</div>
                                            <div class="text-muted small">{{ $customer->email ?? '—' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $groomer->employee_name ?? $groomer->doctor_name ?? 'Unassigned' }}</div>
                                            <div class="text-muted small">{{ $groomer->email ?? $groomer->phone ?? '—' }}</div>
                                        </td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $service)) }}</td>
                                        <td>
                                            @if($amount)
                                                <span class="fw-semibold">₹{{ number_format($amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill text-bg-info text-uppercase small">{{ $status }}</span>
                                        </td>
                                        <td>{{ optional($booking->created_at)->format('d M Y, h:i A') ?? '—' }}</td>
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
