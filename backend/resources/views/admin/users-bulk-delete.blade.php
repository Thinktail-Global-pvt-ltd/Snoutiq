@extends('layouts.admin-panel')

@section('page-title', 'User Bulk Delete')

@push('styles')
<style>
    .bulk-delete-table th,
    .bulk-delete-table td {
        vertical-align: middle;
    }
    .bulk-delete-sticky th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8fafc;
    }
    .bulk-delete-checkbox {
        width: 1.1rem;
        height: 1.1rem;
    }
    .bulk-delete-toolbar {
        gap: 0.75rem;
    }
    .bulk-delete-count {
        font-size: 0.9rem;
        color: #475569;
    }
    .bulk-delete-table .small-muted {
        color: #64748b;
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
                    <div>
                        <h2 class="h5 mb-1">Users Table</h2>
                        <p class="text-muted mb-0">Search users and delete multiple users with their related data in one action.</p>
                    </div>
                    <div class="bulk-delete-count">
                        Showing <strong>{{ number_format($users->count()) }}</strong> of <strong>{{ number_format($totalUsers) }}</strong> users
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.users.bulk-delete') }}" class="row g-2 align-items-end mb-4">
                    <div class="col-12 col-lg-9">
                        <label for="q" class="form-label mb-1 text-muted small">Search users</label>
                        <input
                            type="text"
                            id="q"
                            name="q"
                            class="form-control"
                            value="{{ $searchQuery }}"
                            placeholder="Search by ID, name, email, phone, role, city"
                        >
                    </div>
                    <div class="col-12 col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">Search</button>
                        @if($searchQuery !== '')
                            <a href="{{ route('admin.users.bulk-delete') }}" class="btn btn-outline-secondary">Clear</a>
                        @endif
                    </div>
                </form>

                @if(session('status'))
                    <div class="alert alert-success py-2 px-3 mb-3">{{ session('status') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger py-2 px-3 mb-3">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger py-2 px-3 mb-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if($users->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-people display-6 d-block mb-2"></i>
                        <p class="mb-0">No users matched the current search.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.users.bulk-delete.destroy') }}" id="bulkDeleteForm">
                        @csrf
                        <input type="hidden" name="q" value="{{ $searchQuery }}">

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center bulk-delete-toolbar mb-3">
                            <div class="bulk-delete-count">
                                <strong id="selectedUsersCount">0</strong> user(s) selected
                            </div>
                            <button
                                type="submit"
                                class="btn btn-danger"
                                id="bulkDeleteSubmit"
                                disabled
                                onclick="return confirm('Delete the selected users and related data? This action cannot be undone.');"
                            >
                                Delete Selected Users
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle bulk-delete-table">
                                <thead class="table-light bulk-delete-sticky">
                                    <tr>
                                        <th scope="col" style="width: 52px;">
                                            <input
                                                type="checkbox"
                                                class="form-check-input bulk-delete-checkbox"
                                                id="bulkDeleteSelectAll"
                                                aria-label="Select all users"
                                            >
                                        </th>
                                        <th scope="col">ID</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Phone</th>
                                        <th scope="col">Role</th>
                                        <th scope="col">City</th>
                                        <th scope="col">Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                        <tr>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="user_ids[]"
                                                    value="{{ $user->id }}"
                                                    class="form-check-input bulk-delete-checkbox bulk-delete-row"
                                                    aria-label="Select user {{ $user->id }}"
                                                >
                                            </td>
                                            <td>
                                                <span class="fw-semibold">#{{ $user->id }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ $user->name ?: 'Unnamed user' }}</div>
                                                <div class="small-muted">{{ $user->phone ?: 'No phone' }}</div>
                                            </td>
                                            <td>{{ $user->email ?: '—' }}</td>
                                            <td>{{ $user->phone ?: '—' }}</td>
                                            <td>
                                                <span class="badge rounded-pill text-bg-dark text-capitalize">
                                                    {{ str_replace('_', ' ', $user->role ?: 'n/a') }}
                                                </span>
                                            </td>
                                            <td>{{ $user->city ?: '—' }}</td>
                                            <td>{{ optional($user->created_at)->format('d M Y, H:i') ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('bulkDeleteForm');
    if (!form) return;

    const selectAll = document.getElementById('bulkDeleteSelectAll');
    const submitButton = document.getElementById('bulkDeleteSubmit');
    const selectedCount = document.getElementById('selectedUsersCount');
    const rowCheckboxes = Array.from(form.querySelectorAll('.bulk-delete-row'));

    const syncSelectionState = () => {
        const checked = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
        const total = rowCheckboxes.length;

        selectedCount.textContent = String(checked);
        submitButton.disabled = checked === 0;

        if (selectAll) {
            selectAll.checked = total > 0 && checked === total;
            selectAll.indeterminate = checked > 0 && checked < total;
        }
    };

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            syncSelectionState();
        });
    }

    rowCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncSelectionState);
    });

    syncSelectionState();
})();
</script>
@endpush
