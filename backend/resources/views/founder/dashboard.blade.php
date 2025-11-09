<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SnoutIQ Founder Command Center</title>
    <style>
        :root {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f4f6fb;
            color: #152238;
        }
        body {
            margin: 0;
            padding: 40px;
        }
        h1 {
            margin-bottom: 8px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        }
        .card h3 {
            margin: 0;
            font-size: 0.95rem;
            color: #6b7280;
        }
        .card strong {
            display: block;
            margin-top: 8px;
            font-size: 1.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .alerts {
            list-style: none;
            padding: 0;
            margin: 16px 0 0;
        }
        .alert {
            border-left: 4px solid transparent;
            padding: 12px 16px;
            margin-bottom: 12px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05);
        }
        .alert.critical { border-color: #dc2626; }
        .alert.warning { border-color: #f59e0b; }
        .alert.success { border-color: #10b981; }
        .alert.info { border-color: #3b82f6; }
        .pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .pill {
            border: 1px solid #d1d5db;
            background: #fff;
            border-radius: 999px;
            padding: 6px 14px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .pill.active {
            background: #111827;
            color: #fff;
            border-color: #111827;
        }
        .error {
            color: #dc2626;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <h1>SnoutIQ Founder Command Center</h1>
    <p id="generated-at" style="color:#6b7280;">Loading data…</p>

    <section id="kpi-grid" class="grid"></section>

    <div class="grid">
        <div class="card">
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
        <div class="card">
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

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2 style="margin:0;">Recent Alerts</h2>
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

    <p id="error" class="error" hidden></p>

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
                card.className = 'card';
                const formatted = formatter ? formatter(value) : value;
                card.innerHTML = `<h3>${label}</h3><strong>${formatted}</strong>`;
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

            filtered.forEach((alert) => {
                const li = document.createElement('li');
                li.className = `alert ${alert.type}`;
                li.innerHTML = `
                    <strong>${alert.title}</strong>
                    <p style="margin:4px 0 0;">${alert.message}</p>
                    <small style="color:#6b7280;">${new Date(alert.timestamp).toLocaleString()}</small>
                `;
                list.appendChild(li);
            });

            if (!filtered.length) {
                const empty = document.createElement('li');
                empty.style.color = '#6b7280';
                empty.textContent = 'No alerts';
                list.appendChild(empty);
            }
        }

        async function loadDashboard() {
            try {
                const response = await fetch(`${apiBase}/founder/dashboard`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                const json = await response.json();
                if (!json.success) {
                    throw new Error(json.error?.message || 'Unable to fetch dashboard');
                }
                const data = json.data;
                document.getElementById('generated-at').textContent = `Last refresh: ${new Date(json.timestamp).toLocaleString()}`;
                renderKpis(data.kpis);

                renderTableRows('#revenue-table', data.charts.revenueByMonth || [], (tr, row) => {
                    tr.innerHTML = `<td>${row.label || row.period}</td><td>${formatCurrency(row.revenuePaise)}</td>`;
                });
                renderTableRows('#txn-table', data.charts.transactionsByDay || [], (tr, row) => {
                    tr.innerHTML = `<td>${row.date}</td><td>${row.count}</td>`;
                });

                const recent = data.alerts?.recent ?? [];
                if (recent.length) {
                    state.alerts = recent;
                } else if (data.alerts?.summary) {
                    state.alerts = Object.entries(data.alerts.summary).map(([type, count]) => ({
                        id: `${type}-summary`,
                        type,
                        title: `${type.toUpperCase()} Alerts`,
                        message: `${count} total alerts`,
                        timestamp: new Date().toISOString(),
                    }));
                } else {
                    state.alerts = [];
                }
                renderAlerts();
            } catch (error) {
                const errorBox = document.getElementById('error');
                errorBox.hidden = false;
                errorBox.textContent = error.message;
            }
        }

        loadDashboard();
    </script>
</body>
</html>
