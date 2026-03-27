@extends('layouts.admin-panel')

@section('page-title', 'User Profile Completion')

@section('content')
@php
    $basePath = rtrim(request()->getBasePath(), '/');
    $profileCompletionApiPath = ($basePath !== '' ? $basePath : '') . '/api/user/profile/completion';
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Users Profile Completion</h2>
                        <p class="text-muted mb-0">Shows completion percentage and completed/missing profile fields for users with registered app device tokens.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">
                            {{ number_format($users->count()) }} users
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="reloadProfileCompletionBtn">
                            Reload Data
                        </button>
                    </div>
                </div>

                @if(!($deviceTokensTableExists ?? false))
                    <div class="alert alert-warning py-2 mb-3">
                        device_tokens table not found, so app-installed users cannot be detected in this environment.
                    </div>
                @endif

                <div class="row g-2 align-items-center mb-3">
                    <div class="col-12 col-md-7 col-lg-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input
                                type="search"
                                id="nameFilterInput"
                                class="form-control"
                                placeholder="Filter by user name"
                                value="{{ request('name', '') }}"
                                autocomplete="off"
                            >
                            <button type="button" class="btn btn-outline-secondary" id="clearNameFilterBtn">Clear</button>
                        </div>
                    </div>
                    <div class="col-12 col-md-auto">
                        <span class="text-muted small" id="filteredUsersCount">{{ number_format($users->count()) }} shown</span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="border rounded-3 p-3 bg-light-subtle h-100">
                            <div class="text-muted small">Processed</div>
                            <div class="h4 mb-0" id="completionProcessedCount">0 / {{ number_format($users->count()) }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded-3 p-3 bg-light-subtle h-100">
                            <div class="text-muted small">Average completion</div>
                            <div class="h4 mb-0"><span id="averageCompletionPercent">0</span>%</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="border rounded-3 p-3 bg-light-subtle h-100">
                            <div class="text-muted small">Failures</div>
                            <div class="h4 mb-0 text-danger-emphasis" id="completionFailuresCount">0</div>
                        </div>
                    </div>
                </div>

                @if($users->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-people display-6 d-block mb-2"></i>
                        <p class="mb-0">No users with registered app device tokens found.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">User</th>
                                    <th scope="col">Completion</th>
                                    <th scope="col">Completed Fields</th>
                                    <th scope="col">Missing Fields</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr data-user-id="{{ $user->id }}" data-user-name="{{ strtolower((string) ($user->name ?? '')) }}">
                                        <td style="min-width: 220px;">
                                            <div class="fw-semibold">{{ $user->name ?? 'Unnamed user' }}</div>
                                            <div class="small text-muted">ID: {{ $user->id }}</div>
                                            <div class="small text-muted">{{ $user->email ?? 'No email' }}</div>
                                            <div class="small text-muted">{{ $user->phone ?? 'No phone' }}</div>
                                            <div class="small text-muted">App tokens: {{ (int) ($user->app_device_tokens_count ?? 0) }}</div>
                                            @if(($deviceTokensHasLastSeenAt ?? false) && !empty($user->app_last_seen_at))
                                                <div class="small text-muted">
                                                    Last app activity: {{ \Illuminate\Support\Carbon::parse($user->app_last_seen_at)->format('d M Y h:i A') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td style="min-width: 220px;">
                                            <div class="progress mb-2" role="progressbar" aria-label="Profile completion">
                                                <div class="progress-bar" data-completion-bar style="width: 0%">0%</div>
                                            </div>
                                            <div class="small text-muted" data-completion-count>Loading...</div>
                                        </td>
                                        <td style="min-width: 280px;" data-filled-fields>
                                            <span class="text-muted small">Loading...</span>
                                        </td>
                                        <td style="min-width: 280px;" data-missing-fields>
                                            <span class="text-muted small">Loading...</span>
                                        </td>
                                        <td style="min-width: 160px;" data-load-status>
                                            <span class="badge text-bg-secondary">Pending</span>
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

@push('scripts')
<script>
(() => {
    const endpoint = @json($profileCompletionApiPath);
    const rows = Array.from(document.querySelectorAll('tr[data-user-id]'));
    const reloadBtn = document.getElementById('reloadProfileCompletionBtn');
    const nameFilterInput = document.getElementById('nameFilterInput');
    const clearNameFilterBtn = document.getElementById('clearNameFilterBtn');
    const filteredUsersCountEl = document.getElementById('filteredUsersCount');
    const processedCountEl = document.getElementById('completionProcessedCount');
    const averageEl = document.getElementById('averageCompletionPercent');
    const failuresEl = document.getElementById('completionFailuresCount');
    const totalRows = rows.length;
    const workersCount = Math.min(8, Math.max(totalRows, 1));

    const state = {
        processed: 0,
        success: 0,
        failed: 0,
        sumPercent: 0,
    };
    let isLoading = false;

    const updateSummary = () => {
        if (processedCountEl) {
            processedCountEl.textContent = `${state.processed} / ${totalRows}`;
        }
        if (averageEl) {
            const average = state.success > 0 ? Math.round(state.sumPercent / state.success) : 0;
            averageEl.textContent = String(average);
        }
        if (failuresEl) {
            failuresEl.textContent = String(state.failed);
        }
    };

    const setBadges = (container, fields, mode) => {
        if (!container) {
            return;
        }

        container.innerHTML = '';
        if (!Array.isArray(fields) || fields.length === 0) {
            const empty = document.createElement('span');
            empty.className = 'text-muted small';
            empty.textContent = mode === 'missing' ? 'No missing fields' : 'No completed fields';
            container.appendChild(empty);
            return;
        }

        const fragment = document.createDocumentFragment();
        fields.forEach((item) => {
            const label = typeof item?.label === 'string' ? item.label : '';
            if (!label) {
                return;
            }

            const badge = document.createElement('span');
            badge.className = mode === 'missing'
                ? 'badge rounded-pill text-bg-danger-subtle text-danger-emphasis me-1 mb-1'
                : 'badge rounded-pill text-bg-success-subtle text-success-emphasis me-1 mb-1';
            badge.textContent = label;
            fragment.appendChild(badge);
        });

        if (!fragment.childNodes.length) {
            const empty = document.createElement('span');
            empty.className = 'text-muted small';
            empty.textContent = mode === 'missing' ? 'No missing fields' : 'No completed fields';
            container.appendChild(empty);
            return;
        }

        container.appendChild(fragment);
    };

    const applyNameFilter = () => {
        const term = String(nameFilterInput?.value || '').trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach((row) => {
            const rowName = String(row.getAttribute('data-user-name') || '').toLowerCase();
            const matches = term === '' || rowName.includes(term);
            row.classList.toggle('d-none', !matches);
            if (matches) {
                visibleCount += 1;
            }
        });

        if (filteredUsersCountEl) {
            filteredUsersCountEl.textContent = `${visibleCount} shown`;
        }
    };

    const setPendingRowState = (row) => {
        const completionBar = row.querySelector('[data-completion-bar]');
        const completionCount = row.querySelector('[data-completion-count]');
        const filledContainer = row.querySelector('[data-filled-fields]');
        const missingContainer = row.querySelector('[data-missing-fields]');
        const statusContainer = row.querySelector('[data-load-status]');

        if (completionBar) {
            completionBar.style.width = '0%';
            completionBar.textContent = '0%';
            completionBar.classList.remove('bg-success', 'bg-warning', 'bg-danger');
        }
        if (completionCount) {
            completionCount.textContent = 'Loading...';
        }
        if (filledContainer) {
            filledContainer.innerHTML = '<span class="text-muted small">Loading...</span>';
        }
        if (missingContainer) {
            missingContainer.innerHTML = '<span class="text-muted small">Loading...</span>';
        }
        if (statusContainer) {
            statusContainer.innerHTML = '<span class="badge text-bg-secondary">Pending</span>';
        }
    };

    const loadRow = async (row) => {
        const userId = row.getAttribute('data-user-id');
        const completionBar = row.querySelector('[data-completion-bar]');
        const completionCount = row.querySelector('[data-completion-count]');
        const filledContainer = row.querySelector('[data-filled-fields]');
        const missingContainer = row.querySelector('[data-missing-fields]');
        const statusContainer = row.querySelector('[data-load-status]');

        try {
            const response = await fetch(`${endpoint}?user_id=${encodeURIComponent(userId)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const payload = await response.json();
            if (!response.ok || !payload?.success || !payload?.data) {
                const errorMessage = payload?.message || `Request failed (${response.status})`;
                throw new Error(errorMessage);
            }

            const data = payload.data;
            const completionPercent = Number(data.completion_percent || 0);
            const fieldsFilled = Number(data.fields_filled || 0);
            const fieldsTotal = Number(data.fields_total || 0);
            const filledFields = Array.isArray(data.filled_fields) ? data.filled_fields : [];
            const missingFields = Array.isArray(data.missing_fields) ? data.missing_fields : [];

            if (completionBar) {
                completionBar.style.width = `${completionPercent}%`;
                completionBar.textContent = `${completionPercent}%`;
                completionBar.classList.remove('bg-success', 'bg-warning', 'bg-danger');
                if (completionPercent >= 75) {
                    completionBar.classList.add('bg-success');
                } else if (completionPercent >= 40) {
                    completionBar.classList.add('bg-warning');
                } else {
                    completionBar.classList.add('bg-danger');
                }
            }

            if (completionCount) {
                completionCount.textContent = `${fieldsFilled} / ${fieldsTotal} fields completed`;
            }

            setBadges(filledContainer, filledFields, 'filled');
            setBadges(missingContainer, missingFields, 'missing');

            if (statusContainer) {
                statusContainer.innerHTML = '<span class="badge text-bg-success">Loaded</span>';
            }

            state.success += 1;
            state.sumPercent += completionPercent;
        } catch (error) {
            if (completionCount) {
                completionCount.textContent = error instanceof Error ? error.message : 'Unable to load';
            }
            if (filledContainer) {
                filledContainer.innerHTML = '<span class="text-muted small">-</span>';
            }
            if (missingContainer) {
                missingContainer.innerHTML = '<span class="text-muted small">-</span>';
            }
            if (statusContainer) {
                statusContainer.innerHTML = '<span class="badge text-bg-danger">Failed</span>';
            }
            state.failed += 1;
        } finally {
            state.processed += 1;
            updateSummary();
        }
    };

    const startLoading = async () => {
        if (isLoading) {
            return;
        }

        isLoading = true;
        if (reloadBtn) {
            reloadBtn.disabled = true;
        }

        state.processed = 0;
        state.success = 0;
        state.failed = 0;
        state.sumPercent = 0;
        updateSummary();

        rows.forEach((row) => setPendingRowState(row));

        try {
            if (!totalRows) {
                return;
            }

            let cursor = 0;
            const worker = async () => {
                while (cursor < totalRows) {
                    const row = rows[cursor];
                    cursor += 1;
                    await loadRow(row);
                }
            };

            const workers = Array.from({ length: workersCount }, () => worker());
            await Promise.all(workers);
        } finally {
            isLoading = false;
            if (reloadBtn) {
                reloadBtn.disabled = false;
            }
        }
    };

    if (reloadBtn) {
        reloadBtn.addEventListener('click', () => {
            void startLoading();
        });
    }

    if (nameFilterInput) {
        nameFilterInput.addEventListener('input', applyNameFilter);
    }

    if (clearNameFilterBtn) {
        clearNameFilterBtn.addEventListener('click', () => {
            if (nameFilterInput) {
                nameFilterInput.value = '';
                nameFilterInput.focus();
            }
            applyNameFilter();
        });
    }

    applyNameFilter();
    void startLoading();
})();
</script>
@endpush
