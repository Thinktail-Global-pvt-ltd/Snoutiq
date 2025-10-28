@extends('layouts.admin-panel')

@section('page-title', 'Vet Registrations')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Vet registerations temp</h2>
                        <p class="text-muted mb-0">Clinics captured through the <code>vet_registerations_temp</code> table.</p>
                    </div>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($clinics->count()) }} total</span>
                </div>

                @if($clinics->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-hospital display-6 d-block mb-2"></i>
                        <p class="mb-0">No vet registrations found yet. Once clinics sign up they will be listed here.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Clinic</th>
                                    <th scope="col">Contact</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Doctors</th>
                                    <th scope="col" class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clinics as $clinic)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $clinic->name ?? 'Unnamed clinic' }}</span>
                                            <div class="small text-muted">Slug: {{ $clinic->slug ?? '—' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $clinic->email ?? '—' }}</div>
                                            <div class="small text-muted">{{ $clinic->mobile ?? 'No phone on file' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $clinic->city ?? '—' }}</div>
                                            <div class="small text-muted">Pincode: {{ $clinic->pincode ?? '—' }}</div>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-secondary-subtle text-secondary-emphasis">{{ number_format($clinic->doctors_count ?? 0) }} linked</span>
                                        </td>
                                        <td class="text-end">
                                            @php
                                                $status = $clinic->business_status ?? null;
                                                $openNow = $clinic->open_now;
                                            @endphp
                                            <div>
                                                <span class="badge text-bg-dark">{{ $status ? ucwords(strtolower(str_replace('_', ' ', $status))) : 'Status unknown' }}</span>
                                            </div>
                                            <div class="small text-muted mt-1">
                                                @if(is_null($openNow))
                                                    Opening hours unknown
                                                @else
                                                    {{ $openNow ? 'Currently open' : 'Currently closed' }}
                                                @endif
                                            </div>
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
