@extends('layouts.admin-panel')

@section('page-title', 'Vet Registration Report')

@if (!empty($isPublic))
    @section('hide-sidebar', 'true')
@endif

@php
    $bootstrap = [
        'months' => $months,
        'defaultMonth' => $defaultMonth,
        'apiUrl' => $apiUrl,
    ];
@endphp

@section('content')
<section id="vet-report-root" data-bootstrap='@json($bootstrap)'>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h5 mb-1">Vet registrations by month</h2>
            <p class="text-muted mb-0">
                Backed by the <code>vet_registerations_temp</code> table with a dynamic API for month-wise activations.
                @if (!empty($isPublic))
                    <span class="badge text-bg-warning ms-2">Public view</span>
                @endif
            </p>
        </div>
        <span class="badge text-bg-dark">API powered</span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                <div>
                    <h3 class="h6 mb-1">Registrations</h3>
                    <p class="text-muted mb-0">Listing all vet registrations for the selected month.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label fw-semibold mb-0">Select month</label>
                    <select class="form-select" data-role="month-select" style="min-width: 160px;"></select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Clinic</th>
                            <th>Status</th>
                            <th>Owner</th>
                            <th>Created</th>
                            <th>Location</th>
                            <th class="text-end">Price</th>
                        </tr>
                    </thead>
                    <tbody data-role="all-table-body"></tbody>
                </table>
            </div>
            <div class="small text-muted mt-3" data-role="status-text"></div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('vet-report-root');
    if (!root) return;

    const bootstrap = JSON.parse(root.dataset.bootstrap || '{}');
    let months = bootstrap.months || [];
    let currentMonth = bootstrap.defaultMonth || null; // null = all data

    const monthSelect = root.querySelector('[data-role="month-select"]');
    const allTableBody = root.querySelector('[data-role="all-table-body"]');
    const statusText = root.querySelector('[data-role="status-text"]');

    const formatNumber = (value) => Number(value || 0).toLocaleString('en-IN');

    const fillMonthOptions = () => {
        monthSelect.innerHTML = '';
        const allOption = document.createElement('option');
        allOption.value = '';
        allOption.textContent = 'All (date-wise)';
        allOption.selected = currentMonth === null || currentMonth === '';
        monthSelect.appendChild(allOption);

        months.forEach(({ month }) => {
            const option = document.createElement('option');
            option.value = month;
            option.textContent = month;
            if (month === currentMonth) {
                option.selected = true;
            }
            monthSelect.appendChild(option);
        });
        if (!months.length) {
            const option = document.createElement('option');
            option.textContent = 'No data';
            option.disabled = true;
            monthSelect.appendChild(option);
        }
    };

    const renderAllTable = (rows) => {
        if (!rows || !rows.length) {
            allTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No registrations for this month.</td></tr>';
            return;
        }

        allTableBody.innerHTML = rows.map((row) => {
            const location = [row.city, row.pincode].filter(Boolean).join(', ') || '—';
            const owner = row.owner_user_id ? `User #${row.owner_user_id}` : 'Free';
            return `
                <tr>
                    <td>
                        <div class="fw-semibold">${row.name || 'Unnamed clinic'}</div>
                        <div class="small text-muted">ID: ${row.id}</div>
                    </td>
                    <td><span class="badge text-bg-light text-uppercase">${row.status || '—'}</span></td>
                    <td>${owner}</td>
                    <td>${row.created_at || '—'}</td>
                    <td>${location}</td>
                    <td class="text-end">${row.chat_price !== null && row.chat_price !== undefined ? '₹' + Number(row.chat_price).toFixed(2) : '—'}</td>
                </tr>
            `;
        }).join('');
    };

    const fetchAndRender = async (month) => {
        statusText.textContent = 'Loading report...';
        try {
            const url = new URL(bootstrap.apiUrl, window.location.origin);
            if (month) {
                url.searchParams.set('month', month);
            }
            const response = await fetch(url.toString());
            const payload = await response.json();
            const data = payload.data || {};

            months = data.months || months;
            currentMonth = data.selected_month || currentMonth;

            fillMonthOptions();
            renderAllTable(data.all);
            statusText.textContent = '';
        } catch (error) {
            console.error('Failed to load vet registration report', error);
            statusText.textContent = 'Could not load data. Please retry.';
        }
    };

    fillMonthOptions();
    renderAllTable([]);
    fetchAndRender(currentMonth);

    monthSelect.addEventListener('change', (event) => {
        const month = event.target.value;
        currentMonth = month || null;
        fetchAndRender(month || null);
    });
});
</script>
@endpush
