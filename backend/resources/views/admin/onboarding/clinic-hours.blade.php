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
                    <p class="text-muted text-uppercase small mb-1">Clinics with Hours</p>
                    <h4 class="mb-0">{{ $stats['clinics_with_config'] }}</h4>
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
                    <p class="text-muted text-uppercase small mb-1">Doctors Configured</p>
                    <h4 class="mb-0">{{ $stats['doctors_with_config'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Clinic Hours Configuration</h5>
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
                            <th class="text-center">Configured Doctors</th>
                            <th>Status</th>
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
                                <td class="text-center">{{ $clinic['doctors_with_clinic_hours'] }}</td>
                                <td>
                                    @if($clinic['has_any_clinic_hours'])
                                        <span class="badge badge-success">Configured</span>
                                    @else
                                        <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5" class="bg-light">
                                    <div class="px-3 py-3">
                                        <h6 class="text-uppercase text-muted small mb-3">Doctors</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="bg-white">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Contact</th>
                                                        <th>License</th>
                                                        <th>Clinic Slots</th>
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
                                                                @if($doctor['clinic_hours']['has_data'])
                                                                    <span class="badge badge-success">Configured</span>
                                                                    <span class="small text-muted d-block mt-1">Slots: {{ $doctor['clinic_hours']['slot_count'] }}</span>
                                                                    @if(!empty($doctor['clinic_hours']['last_created_at']))
                                                                        <span class="small text-muted d-block">Created: {{ \Illuminate\Support\Carbon::parse($doctor['clinic_hours']['last_created_at'])->format('d M Y H:i') }}</span>
                                                                    @endif
                                                                @else
                                                                    <span class="badge badge-light">Pending</span>
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
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No clinic records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
