@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Total Clinics</p>
                    <h4 class="mb-0">{{ $stats['total_clinics'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Clinics with Emergency Cover</p>
                    <h4 class="mb-0">{{ $stats['clinics_with_program'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Doctors</p>
                    <h4 class="mb-0">{{ $stats['total_doctors'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Doctors in Program</p>
                    <h4 class="mb-0">{{ $stats['doctors_in_program'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Emergency Readiness</h5>
            <span class="badge badge-pill badge-primary">Updated {{ now()->format('d M Y') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width: 220px;">Clinic</th>
                            <th>Location</th>
                            <th class="text-center">Doctors</th>
                            <th class="text-center">On Emergency Rota</th>
                            <th>Status</th>
                            <th>Emergency Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clinics as $clinic)
                            <tr>
                                <td>
                                    <strong>{{ $clinic['clinic_name'] }}</strong>
                                    <div class="small text-muted">Slug: {{ $clinic['slug'] ?? '—' }}</div>
                                </td>
                                <td>{{ $clinic['city'] ?? '—' }}</td>
                                <td class="text-center">{{ $clinic['doctor_count'] }}</td>
                                <td class="text-center">{{ $clinic['doctors_in_emergency'] }}</td>
                                <td>
                                    @if($clinic['has_emergency_program'])
                                        <span class="badge badge-success">Ready</span>
                                    @else
                                        <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">Consultation price: {{ $clinic['consultation_price'] !== null ? '₹'.number_format($clinic['consultation_price'], 2) : '—' }}</div>
                                    <div class="small">Night slots: {{ is_array($clinic['night_slots']) ? count($clinic['night_slots']) : 0 }}</div>
                                    <div class="small text-muted">Last updated: {{ $clinic['updated_at'] ? \Illuminate\Support\Carbon::parse($clinic['updated_at'])->format('d M Y H:i') : '—' }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6" class="bg-light">
                                    <div class="px-3 py-3">
                                        <h6 class="text-uppercase text-muted small mb-3">Doctors</h6>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="bg-white">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Contact</th>
                                                        <th>License</th>
                                                        <th>Emergency Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($clinic['doctors'] as $doctor)
                                                        <tr>
                                                            <td>{{ $doctor['doctor_name'] ?? '—' }}</td>
                                                            <td>
                                                                <div class="small">Email: {{ $doctor['doctor_email'] ?? '—' }}</div>
                                                                <div class="small">Mobile: {{ $doctor['doctor_mobile'] ?? '—' }}</div>
                                                            </td>
                                                            <td class="small">{{ $doctor['doctor_license'] ?? '—' }}</td>
                                                            <td>
                                                                @if($doctor['emergency']['is_listed'])
                                                                    <span class="badge badge-success">Listed</span>
                                                                @else
                                                                    <span class="badge badge-light">Not Listed</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted">No doctors linked to this clinic.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        <h6 class="text-uppercase text-muted small mb-2">Night Slots</h6>
                                        @if(!empty($clinic['night_slots']))
                                            <ul class="mb-0 pl-3 small">
                                                @foreach($clinic['night_slots'] as $slot)
                                                    @php
                                                        $label = $slot['label'] ?? ($slot['day'] ?? 'Slot');
                                                        $start = $slot['start'] ?? ($slot['start_time'] ?? null);
                                                        $end = $slot['end'] ?? ($slot['end_time'] ?? null);
                                                    @endphp
                                                    <li>
                                                        <strong>{{ $label }}</strong>
                                                        @if($start || $end)
                                                            <span class="text-muted">— {{ $start ?? '—' }} to {{ $end ?? '—' }}</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="badge badge-light">No night slots configured</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No clinic records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
