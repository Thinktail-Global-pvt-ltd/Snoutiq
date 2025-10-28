@extends('layouts.admin-panel')

@section('page-title', 'Pets')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Registered pets</h2>
                        <p class="text-muted mb-0">Every pet profile created by pet parents inside SnoutIQ.</p>
                    </div>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($pets->count()) }} total</span>
                </div>

                @if($pets->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-bag-heart display-6 d-block mb-2"></i>
                        <p class="mb-0">No pets have been added yet. New registrations will appear here automatically.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Pet</th>
                                    <th scope="col">Owner</th>
                                    <th scope="col">Breed</th>
                                    <th scope="col">Age</th>
                                    <th scope="col">Gender</th>
                                    <th scope="col" class="text-end">Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pets as $pet)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $pet->name ?? 'Unnamed pet' }}</span>
                                            @if($pet->pet_doc1 || $pet->pet_doc2)
                                                <div class="small text-muted">Documents uploaded</div>
                                            @endif
                                        </td>
                                        <td>
                                            @php $owner = $pet->owner; @endphp
                                            <span class="fw-semibold">{{ $owner->name ?? '—' }}</span>
                                            <div class="small text-muted">{{ $owner->email ?? 'No email on file' }}</div>
                                        </td>
                                        <td>{{ $pet->breed ?? '—' }}</td>
                                        <td>{{ $pet->pet_age ?? '—' }}</td>
                                        <td class="text-capitalize">{{ $pet->pet_gender ?? '—' }}</td>
                                        <td class="text-end text-muted small">{{ optional($pet->created_at)->format('d M Y') ?? '—' }}</td>
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
