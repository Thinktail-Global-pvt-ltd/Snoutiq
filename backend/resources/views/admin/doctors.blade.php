@extends('layouts.admin-panel')

@section('page-title', 'Doctors')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Doctors list</h2>
                        <p class="text-muted mb-0">All doctors linked to a vet registration during onboarding.</p>
                    </div>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($doctors->count()) }} total</span>
                </div>

                @if($doctors->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-clipboard2-pulse display-6 d-block mb-2"></i>
                        <p class="mb-0">No doctors are currently listed. They will appear here once linked to a clinic.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Doctor</th>
                                    <th scope="col">Clinic</th>
                                    <th scope="col">Contact</th>
                                    <th scope="col">License</th>
                                    <th scope="col" class="text-end">Availability</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($doctors as $doctor)
                                    @php $clinic = $doctor->clinic; @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $doctor->doctor_name ?? 'Unnamed doctor' }}</span>
                                            <div class="small text-muted">Added {{ optional($doctor->created_at)->format('d M Y') ?? '—' }}</div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $clinic->name ?? 'Unassigned clinic' }}</span>
                                            <div class="small text-muted">{{ $clinic->city ?? '—' }} @if(!empty($clinic?->pincode)) • {{ $clinic->pincode }} @endif</div>
                                        </td>
                                        <td>
                                            <div>{{ $doctor->doctor_email ?? '—' }}</div>
                                            <div class="small text-muted">{{ $doctor->doctor_mobile ?? 'No phone on file' }}</div>
                                        </td>
                                        <td>{{ $doctor->doctor_license ?? '—' }}</td>
                                        <td class="text-end">
                                            @if((int) $doctor->toggle_availability === 1)
                                                <span class="badge text-bg-success-subtle text-success-emphasis">Available</span>
                                            @else
                                                <span class="badge text-bg-warning-subtle text-warning-emphasis">Unavailable</span>
                                            @endif
                                        </td>
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
