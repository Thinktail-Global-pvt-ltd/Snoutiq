@extends('layouts.admin-panel')

@section('page-title', 'Service Provider Profile')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Service provider details</h2>
                        <p class="text-muted mb-0">A quick look at the clinic or groomer onboarding information.</p>
                    </div>
                    @if(isset($profile))
                        <span class="badge text-bg-info-subtle text-info-emphasis px-3 py-2">{{ ucfirst($profile->status ?? 'pending') }}</span>
                    @endif
                </div>

                @if(!isset($profile))
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-person-vcard display-6 d-block mb-2"></i>
                        <p class="mb-0">No profile data available for this service provider.</p>
                    </div>
                @else
                    <div class="row g-4">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted">Name</dt>
                                <dd class="col-7 fw-semibold">{{ $profile->name ?? '—' }}</dd>

                                <dt class="col-5 text-muted">Bio</dt>
                                <dd class="col-7">{{ $profile->bio ?? '—' }}</dd>

                                <dt class="col-5 text-muted">Address</dt>
                                <dd class="col-7">{{ $profile->address ?? '—' }}</dd>

                                <dt class="col-5 text-muted">City</dt>
                                <dd class="col-7">{{ $profile->city ?? '—' }}</dd>

                                <dt class="col-5 text-muted">Pincode</dt>
                                <dd class="col-7">{{ $profile->pincode ?? '—' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-5 text-muted">Coordinates</dt>
                                <dd class="col-7">{{ $profile->coordinates ?? '—' }}</dd>

                                <dt class="col-5 text-muted">Working hours</dt>
                                <dd class="col-7">{{ $profile->working_hours ?? '—' }}</dd>

                                <dt class="col-5 text-muted">In-home services</dt>
                                <dd class="col-7">{{ $profile->inhome_grooming_services ? 'Yes' : 'No' }}</dd>

                                <dt class="col-5 text-muted">License No.</dt>
                                <dd class="col-7">{{ $profile->license_no ?? '—' }}</dd>

                                <dt class="col-5 text-muted">Type</dt>
                                <dd class="col-7 text-capitalize">{{ $profile->type ?? '—' }}</dd>
                            </dl>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
