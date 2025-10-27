@extends('layouts.admin-panel')

@section('page-title', 'Users')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Registered users</h2>
                        <p class="text-muted mb-0">A quick overview of everyone who has created a SnoutIQ account.</p>
                    </div>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($users->count()) }} total</span>
                </div>

                @if($users->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-people display-6 d-block mb-2"></i>
                        <p class="mb-0">No users found just yet. New registrations will appear here automatically.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Phone</th>
                                    <th scope="col">Role</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $user->name ?? 'Unnamed user' }}</span>
                                            <div class="text-muted small">Joined {{ optional($user->created_at)->format('d M Y') ?? '—' }}</div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->phone ?? '—' }}</td>
                                        <td>
                                            <span class="badge rounded-pill text-bg-dark text-capitalize">{{ str_replace('_', ' ', $user->role ?? 'n/a') }}</span>
                                        </td>
                                        <td class="text-end">
                                            @if(($user->role ?? '') !== 'pet_owner')
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.sp.profile', $user) }}">View profile</a>
                                            @else
                                                <span class="text-muted small">No actions</span>
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
