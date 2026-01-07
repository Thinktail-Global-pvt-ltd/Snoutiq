@extends('layouts.admin-panel')

@section('page-title', 'Vet/User Connections')

@if (!empty($isPublic))
    @section('hide-sidebar', 'true')
@endif

@section('content')
    <section class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h5 mb-1">Users linked to vets</h2>
            <p class="text-muted mb-0">
                Showing one-to-one connections where <code>users.last_vet_id = vet_registerations_temp.id</code>.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if (!empty($isPublic))
                <span class="badge text-bg-warning">Public view</span>
            @endif
            <span class="badge text-bg-dark">Live data</span>
        </div>
    </section>

    @if (!$hasUsersLastVet || !$hasVetTable)
        <div class="alert alert-danger">
            The report cannot run because
            @if (!$hasUsersLastVet && !$hasVetTable)
                the <code>users.last_vet_id</code> column and the <code>vet_registerations_temp</code> table are missing.
            @elseif (!$hasUsersLastVet)
                the <code>users.last_vet_id</code> column is missing.
            @else
                the <code>vet_registerations_temp</code> table is missing.
            @endif
        </div>
    @else
        <section class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-chip h-100">
                    <span>Total connections</span>
                    <strong>{{ number_format($metrics['total_connections']) }}</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-chip h-100">
                    <span>Unique users</span>
                    <strong>{{ number_format($metrics['unique_users']) }}</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-chip h-100">
                    <span>Unique vets</span>
                    <strong>{{ number_format($metrics['unique_vets']) }}</strong>
                </div>
            </div>
        </section>

        <section class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h3 class="h6 mb-1">Latest connections</h3>
                        <p class="text-muted mb-0">
                            Sorted by <code>users.updated_at</code>. Limited to {{ number_format($maxRows) }} rows for quick loading.
                        </p>
                    </div>
                    <span class="badge text-bg-light">Rows: {{ number_format($connections->count()) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Vet</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th class="text-end">Last updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($connections as $row)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">User #{{ $row->user_id }}</div>
                                        <div class="small text-muted">{{ $row->user_name ?: '—' }}</div>
                                    </td>
                                    <td class="small">
                                        <div>{{ $row->user_phone ?: '—' }}</div>
                                        <div class="text-muted">{{ $row->user_email ?: '—' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $row->clinic_name ?: 'Unnamed clinic' }}</div>
                                        <div class="small text-muted">Vet ID: {{ $row->last_vet_id }}</div>
                                    </td>
                                    <td class="small">
                                        {{ trim(($row->clinic_city ?? '').($row->clinic_city && $row->clinic_pincode ? ', ' : '').($row->clinic_pincode ?? '')) ?: '—' }}
                                    </td>
                                    <td>
                                        <span class="badge text-bg-light text-uppercase">{{ $row->clinic_status ?: '—' }}</span>
                                    </td>
                                    <td class="text-end small text-muted">
                                        {{ $row->user_updated_at ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No connections found between users and vets.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif
@endsection
