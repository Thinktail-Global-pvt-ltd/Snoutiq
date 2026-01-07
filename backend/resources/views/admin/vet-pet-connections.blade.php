@extends('layouts.admin-panel')

@section('page-title', 'Vet Pet Connections')

@if (!empty($isPublic))
    @section('hide-sidebar', 'true')
@endif

@section('content')
    <section class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h5 mb-1">Pets grouped by vet</h2>
            <p class="text-muted mb-0">
                Grouping pets via <code>users.last_vet_id = vet_registerations_temp.id</code>. Click a clinic to view its pets and owners.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if (!empty($isPublic))
                <span class="badge text-bg-warning">Public view</span>
            @endif
            <span class="badge text-bg-dark">Live data</span>
        </div>
    </section>

    @if (!$hasAllTables)
        <div class="alert alert-danger">
            Missing required tables/columns (<code>pets</code>, <code>users</code> with <code>last_vet_id</code>, or <code>vet_registerations_temp</code>). Cannot generate report.
        </div>
    @else
        <section id="vet-pet-root"
            data-details-url-base="{{ $detailsUrlBase }}"
            data-default-limit="{{ $defaultLimit }}">
            <section class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-chip h-100">
                        <span>Total clinics with pets</span>
                        <strong>{{ number_format($summary->count()) }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-chip h-100">
                        <span>Total pets (linked)</span>
                        <strong>{{ number_format($totalPets) }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-chip h-100">
                        <span>Total unique users</span>
                        <strong>{{ number_format($summary->sum('user_count')) }}</strong>
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <h3 class="h6 mb-1">Pet counts by clinic</h3>
                            <p class="text-muted mb-0">Ordered by pet count. Click “View pets/users” to drill down.</p>
                        </div>
                        <span class="badge text-bg-light">Rows: {{ number_format($summary->count()) }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Clinic</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th class="text-end">Pets</th>
                                    <th class="text-end">Users</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody data-role="summary-body">
                                @forelse ($summary as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $row->name ?? 'Unnamed clinic' }}</div>
                                            <div class="small text-muted">ID: {{ $row->id }}</div>
                                        </td>
                                        <td class="small">
                                            {{ trim(($row->city ?? '').($row->city && $row->pincode ? ', ' : '').($row->pincode ?? '')) ?: '—' }}
                                        </td>
                                        <td>
                                            <span class="badge text-bg-light text-uppercase">{{ $row->status ?: '—' }}</span>
                                        </td>
                                        <td class="text-end fw-semibold">{{ number_format($row->pet_count) }}</td>
                                        <td class="text-end text-muted">{{ number_format($row->user_count) }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary"
                                                data-role="load-details"
                                                data-vet-id="{{ $row->id }}"
                                                data-vet-name="{{ $row->name ?? 'Clinic #'.$row->id }}"
                                                data-pet-count="{{ $row->pet_count }}">
                                                View pets/users
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No linked pets found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm" data-role="details-panel">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <h3 class="h6 mb-1" data-role="details-title">Select a clinic to view pets</h3>
                            <p class="text-muted mb-0" data-role="details-subtitle">Click “View pets/users” in the table above.</p>
                        </div>
                        <span class="badge text-bg-light" data-role="details-count"></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pet</th>
                                    <th>User</th>
                                    <th>Contact</th>
                                    <th>Meta</th>
                                </tr>
                            </thead>
                            <tbody data-role="details-body">
                                <tr>
                                    <td colspan="4" class="text-muted text-center py-4">
                                        No clinic selected.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </section>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('vet-pet-root');
    if (!root) return;

    const summaryBody = root.querySelector('[data-role="summary-body"]');
    const detailsBody = root.querySelector('[data-role="details-body"]');
    const detailsTitle = root.querySelector('[data-role="details-title"]');
    const detailsSubtitle = root.querySelector('[data-role="details-subtitle"]');
    const detailsCount = root.querySelector('[data-role="details-count"]');

    const detailsUrlBase = root.dataset.detailsUrlBase || '';
    const defaultLimit = Number(root.dataset.defaultLimit || 300);

    const escapeHtml = (value) => {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    const formatMeta = (row) => {
        const age = row.pet_age ? `Age: ${row.pet_age}` : '';
        const gender = row.pet_gender ? `Gender: ${row.pet_gender}` : '';
        return [age, gender].filter(Boolean).join(' • ') || '—';
    };

    const buildDetailsUrl = (vetId) => {
        if (!detailsUrlBase) return '';
        return `${detailsUrlBase.replace(/0$/, vetId)}?limit=${defaultLimit}`;
    };

    const renderDetails = (vetId, vetName, rows) => {
        detailsTitle.textContent = `${vetName} (ID: ${vetId})`;
        detailsSubtitle.textContent = rows.length
            ? 'Showing linked pets and their owners.'
            : 'No pets linked to this clinic.';
        detailsCount.textContent = rows.length ? `Rows: ${rows.length}` : '';

        if (!rows.length) {
            detailsBody.innerHTML = '<tr><td colspan="4" class="text-muted text-center py-4">No pets found for this clinic.</td></tr>';
            return;
        }

        const html = rows.map((row) => {
            const petLine = escapeHtml(row.pet_name || 'Unnamed pet');
            const petId = `Pet #${row.pet_id}`;
            const userLine = escapeHtml(row.user_name || `User #${row.user_id}`);
            const contact = [
                row.user_phone ? escapeHtml(row.user_phone) : null,
                row.user_email ? escapeHtml(row.user_email) : null,
            ].filter(Boolean).join('<br>');

            const meta = escapeHtml(formatMeta(row));
            const created = row.pet_created_at ? escapeHtml(row.pet_created_at) : '—';

            return `
                <tr>
                    <td>
                        <div class="fw-semibold">${petLine}</div>
                        <div class="small text-muted">${petId}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">${userLine}</div>
                        <div class="small text-muted">User #${row.user_id}</div>
                    </td>
                    <td class="small">${contact || '—'}</td>
                    <td class="small">
                        <div>${meta}</div>
                        <div class="text-muted">Created: ${created}</div>
                    </td>
                </tr>
            `;
        }).join('');

        detailsBody.innerHTML = html;
    };

    const setLoadingState = (label) => {
        detailsBody.innerHTML = `<tr><td colspan="4" class="text-center py-4">${escapeHtml(label)}</td></tr>`;
    };

    const loadDetails = async (button) => {
        const vetId = button.dataset.vetId;
        const vetName = button.dataset.vetName || `Clinic #${vetId}`;
        setLoadingState('Loading pets...');
        detailsCount.textContent = '';
        detailsTitle.textContent = `${vetName} (ID: ${vetId})`;
        detailsSubtitle.textContent = 'Fetching linked pets and users.';

        const url = buildDetailsUrl(vetId);
        if (!url) {
            setLoadingState('Could not build details URL.');
            return;
        }

        button.disabled = true;
        button.classList.add('disabled');
        try {
            const response = await fetch(url);
            const payload = await response.json();

            if (!payload.success) {
                throw new Error(payload.message || 'Request failed');
            }

            renderDetails(vetId, vetName, payload.data.rows || []);
        } catch (error) {
            console.error('Failed to load details', error);
            setLoadingState('Failed to load details. Try again.');
            detailsSubtitle.textContent = 'Could not load data. Please retry.';
        } finally {
            button.disabled = false;
            button.classList.remove('disabled');
        }
    };

    summaryBody.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-role="load-details"]');
        if (!btn) return;
        loadDetails(btn);
    });
});
</script>
@endpush
