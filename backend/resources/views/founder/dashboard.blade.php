@extends('layouts.admin-panel')

@section('page-title', 'Founder Dashboard Preview')

@push('styles')
<style>
    .founder-dashboard {
        padding: 1rem 0;
    }
    .founder-dashboard h1 {
        margin-bottom: 0.5rem;
        font-size: 1.75rem;
        font-weight: 700;
    }
    .founder-dashboard .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }
    .founder-dashboard .card-lite {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    }
    .founder-dashboard table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }
    .founder-dashboard th,
    .founder-dashboard td {
        padding: 8px 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    .founder-dashboard .alerts {
        list-style: none;
        padding: 0;
        margin: 16px 0 0;
    }
    .founder-dashboard .alert {
        border-left: 4px solid transparent;
        padding: 12px 16px;
        margin-bottom: 12px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05);
    }
    .founder-dashboard .alert.critical { border-color: #dc2626; }
    .founder-dashboard .alert.warning { border-color: #f59e0b; }
    .founder-dashboard .alert.success { border-color: #10b981; }
    .founder-dashboard .alert.info { border-color: #3b82f6; }
    .founder-dashboard .pills {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .founder-dashboard .pill {
        border: 1px solid #d1d5db;
        background: #fff;
        border-radius: 999px;
        padding: 6px 14px;
        cursor: pointer;
        font-size: 0.85rem;
    }
    .founder-dashboard .pill.active {
        background: #111827;
        color: #fff;
        border-color: #111827;
    }
    .founder-dashboard .error {
        color: #dc2626;
        margin-top: 16px;
    }
</style>
@endpush

@section('content')
<div class="founder-dashboard">
    <h1>SnoutIQ Founder Command Center</h1>
    <p id="generated-at" class="text-muted">Loading data…</p>

    <section id="kpi-grid" class="kpi-grid"></section>

    <div class="kpi-grid">
        <div class="card-lite">
            <h2>Revenue By Month</h2>
            <table id="revenue-table">
                <thead>
                <tr>
                    <th>Month</th>
                    <th>Revenue (₹)</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="card-lite">
            <h2>Transactions (Last 30d)</h2>
            <table id="txn-table">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Count</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card-lite">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h2 class="mb-0">Recent Alerts</h2>
            <div class="pills" id="alert-pills">
                <button class="pill active" data-type="all">All</button>
                <button class="pill" data-type="critical">Critical</button>
                <button class="pill" data-type="warning">Warning</button>
                <button class="pill" data-type="success">Success</button>
                <button class="pill" data-type="info">Info</button>
            </div>
        </div>
        <ul class="alerts" id="alerts-list"></ul>
    </div>

    <p id="error" class="error d-none"></p>
</div>
@endsection

@push('scripts')
<script>
    const inBackend = window.location.pathname.startsWith('/backend');
    const apiBase = inBackend ? '/backend/api' : '/api';

    const state = {
        alerts: [],
        alertFilter: 'all',
    };

    document.querySelectorAll('#alert-pills .pill').forEach((pill) => {
        pill.addEventListener('click', () => {
            state.alertFilter = pill.dataset.type;
            document.querySelectorAll('#alert-pills .pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            renderAlerts();
        });
    });

    function formatCurrency(paise) {
        const rupees = (paise || 0) / 100;
        return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 2 }).format(rupees);
    }

    function renderKpis(kpis) {
        const config = [
            { key: 'totalClinics', label: 'Total Clinics' },
            { key: 'activeClinics', label: 'Active Clinics' },
            { key: 'monthlyRevenuePaise', label: 'Monthly Revenue', formatter: formatCurrency },
            { key: 'mtdRevenuePaise', label: 'MTD Revenue', formatter: formatCurrency },
            { key: 'last30dTransactions', label: 'Last 30d Transactions' },
            { key: 'failedTxnRate', label: 'Failed Txn Rate', formatter: (v) => `${(v * 100).toFixed(2)}%` },
        ];
        const grid = document.getElementById('kpi-grid');
        grid.innerHTML = '';
        config.forEach(({ key, label, formatter }) => {
            const value = kpis?.[key] ?? 0;
            const card = document.createElement('div');
            card.className = 'card-lite';
            const formatted = formatter ? formatter(value) : value;
            card.innerHTML = `<h3 class="text-muted small mb-2">${label}</h3><strong class="fs-3">${formatted}</strong>`;
            grid.appendChild(card);
        });
    }

    function renderTableRows(tableId, rows, mapper) {
        const tbody = document.querySelector(`${tableId} tbody`);
        tbody.innerHTML = '';
        rows.forEach((row) => {
            const tr = document.createElement('tr');
            mapper(tr, row);
            tbody.appendChild(tr);
        });
    }

    function renderAlerts() {
        const list = document.getElementById('alerts-list');
        list.innerHTML = '';
        const filtered = state.alertFilter === 'all'
            ? state.alerts
            : state.alerts.filter(alert => alert.type === state.alertFilter);

        if (filtered.length === 0) {
            const li = document.createElement('li');
            li.textContent = 'No alerts in this category.';
            li.className = 'text-muted';
            list.appendChild(li);
            return;
        }

        filtered.forEach((alert) => {
            const li = document.createElement('li');
            li.className = `alert ${alert.type}`;
            li.innerHTML = `
                <strong>${alert.title}</strong>
                <p style="margin:6px 0;color:#475569;">${alert.message}</p>
                <small style="color:#6b7280;">${alert.timestamp || '–'} • ${alert.type.toUpperCase()}</small>
            `;
            list.appendChild(li);
        });
    }

    function showError(message) {
        const el = document.getElementById('error');
        el.textContent = message;
        el.classList.remove('d-none');
    }

    async function bootstrap() {
        try {
            const res = await fetch(`${apiBase}/founder/dashboard`, {
                credentials: 'same-origin',
            });

            if (!res.ok) {
                throw new Error(`Request failed (${res.status})`);
            }

            const payload = await res.json();
            if (!payload.success) {
                throw new Error(payload.error?.message || 'Unable to fetch dashboard');
            }

            const data = payload.data;
            renderKpis(data.kpis);
            renderTableRows('#revenue-table', data.charts.revenueByMonth || [], (tr, row) => {
                tr.innerHTML = `<td>${row.label}</td><td>${formatCurrency(row.revenuePaise)}</td>`;
            });
            renderTableRows('#txn-table', data.charts.transactionsByDay || [], (tr, row) => {
                tr.innerHTML = `<td>${row.date}</td><td>${row.count}</td>`;
            });
            state.alerts = data.alerts?.recent || [];
            renderAlerts();
            document.getElementById('generated-at').textContent = `Generated at ${new Date(data.summary.generatedAt).toLocaleString()}`;
        } catch (error) {
            console.error(error);
            showError(error.message || 'Unexpected error while loading dashboard');
        }
    }

    bootstrap();
</script>
@endpush
