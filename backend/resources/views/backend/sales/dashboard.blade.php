@php
    use Illuminate\Support\Carbon;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard • SnoutIQ</title>
    <style>
        :root {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            background: #f1f5f9;
        }
        body { margin: 0; padding: 0; }
        header { padding: 2.5rem 3rem 1.5rem; background: linear-gradient(90deg,#1d4ed8,#0ea5e9); color: #fff; }
        header h1 { margin: 0; font-size: 2.4rem; letter-spacing: -.02em; }
        header p { margin: .65rem 0 0; opacity: .9; max-width: 680px; }
        main { max-width: 1200px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }
        .cards { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 2.5rem; }
        .card {
            background: #fff;
            border-radius: 1.2rem;
            padding: 1.75rem;
            box-shadow: 0 22px 45px -28px rgba(15,23,42,.35);
            border: 1px solid rgba(148,163,184,.12);
        }
        .card h3 { margin: 0 0 .6rem; font-size: 1rem; font-weight: 600; color: #334155; text-transform: uppercase; letter-spacing: .08em; }
        .card strong { display: block; font-size: 2.3rem; margin-bottom: .4rem; }
        .card span { color: #64748b; font-size: .9rem; }
        section { background: #fff; border-radius: 1.2rem; padding: 2rem; margin-bottom: 2.5rem; box-shadow: 0 24px 48px -32px rgba(15,23,42,.3); border: 1px solid rgba(148,163,184,.14); }
        section h2 { margin: 0 0 .4rem; font-size: 1.6rem; }
        section p.lead { margin: 0 0 1.5rem; color: #475569; font-size: .95rem; }
        form.filters { display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; margin-bottom: 1.5rem; }
        form.filters select, form.filters input {
            border-radius: .85rem;
            border: 1px solid #cbd5f5;
            padding: .7rem .9rem;
            font-size: .96rem;
            background: #f8fafc;
        }
        form.filters button {
            padding: .75rem 1.5rem;
            border-radius: .85rem;
            border: none;
            background: linear-gradient(90deg,#2563eb,#0ea5e9);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 12px 24px -18px rgba(37,99,235,.6);
        }
        form.filters .link {
            background: rgba(148,163,184,.18);
            color: #334155;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { padding: .85rem .9rem; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: .96rem; }
        th { background: #f8fafc; font-size: .78rem; letter-spacing: .08em; text-transform: uppercase; color: #475569; }
        tbody tr:hover { background: rgba(15,118,110,.04); }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border-radius: 999px;
            padding: .3rem .85rem;
            font-size: .75rem;
            font-weight: 600;
        }
        .badge.active { background: rgba(16,185,129,.12); color: #047857; border: 1px solid rgba(16,185,129,.45); }
        .badge.inactive { background: rgba(148,163,184,.16); color: #475569; border: 1px solid rgba(148,163,184,.35); }
        .pagination { display: flex; gap: .75rem; align-items: center; margin-top: 1.5rem; font-size: .88rem; color: #475569; flex-wrap: wrap; }
        .pagination a, .pagination span.button {
            padding: .55rem 1.2rem;
            border-radius: .75rem;
            border: 1px solid #cbd5f5;
            text-decoration: none;
            color: #1d4ed8;
            font-weight: 600;
            transition: background .15s ease, transform .15s ease;
        }
        .pagination a:hover { background: rgba(37,99,235,.1); transform: translateY(-1px); }
        .pagination .disabled { opacity: .45; pointer-events: none; }
        .muted { color: #94a3b8; font-size: .85rem; }
        footer { text-align: center; padding: 2.5rem 1rem 4rem; color: #64748b; font-size: .85rem; }
        @media (max-width: 720px) {
            header { padding: 2rem 1.5rem 1.2rem; }
            main { padding: 2rem 1.25rem 3.5rem; }
            form.filters { flex-direction: column; align-items: stretch; }
            table { font-size: .9rem; }
            th, td { padding: .7rem .75rem; }
        }
    </style>
</head>
<body>
    <header>
        <h1>Sales Performance Dashboard</h1>
        <p>Track QR scanner adoption, clinic onboarding progress, and downstream transactions across SnoutIQ.</p>
    </header>
    <main>
        <section class="cards">
            <article class="card">
                <h3>QR Scanners</h3>
                <strong>{{ number_format($summary['scanners']['total']) }}</strong>
                <span>{{ number_format($summary['scanners']['active']) }} active • {{ number_format($summary['scanners']['inactive']) }} inactive</span>
            </article>
            <article class="card">
                <h3>QR Scans</h3>
                <strong>{{ number_format($summary['scanner_scans']['total']) }}</strong>
                <span>{{ number_format($summary['scanner_scans']['recent_codes']) }} codes scanned in past {{ $summary['recent_days_window'] }} days</span>
            </article>
            <article class="card">
                <h3>Clinics</h3>
                <strong>{{ number_format($summary['clinics']['total']) }}</strong>
                <span>{{ number_format($summary['clinics']['active']) }} active • {{ number_format($summary['clinics']['recent']) }} new in {{ $summary['recent_days_window'] }}d</span>
            </article>
            <article class="card">
                <h3>Pet Parents (via QR)</h3>
                <strong>{{ number_format($summary['pet_parents']['total']) }}</strong>
                <span>{{ number_format($summary['pet_parents']['recent']) }} onboarded in past {{ $summary['recent_days_window'] }} days</span>
            </article>
            <article class="card">
                <h3>Transactions</h3>
                <strong>{{ number_format($summary['transactions']['total']) }}</strong>
                <span>{{ number_format($summary['transactions']['recent']) }} in past {{ $summary['recent_days_window'] }} days</span>
            </article>
        </section>

        <section>
            <h2>QR Scanner Activity</h2>
            <p class="lead">Understand which scanners are generating registrations and consults. Filter by status or search by code/clinic.</p>
            <form class="filters" method="GET">
                <input type="hidden" name="clinic_status" value="{{ $clinicFilters['status'] }}">
                <input type="hidden" name="clinic_search" value="{{ $clinicFilters['search'] }}">
                <label>
                    <span class="muted">Status</span><br>
                    <select name="scanner_status">
                        @foreach(['all' => 'All statuses', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                            <option value="{{ $value }}" @selected($scannerFilters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="flex:1">
                    <span class="muted">Search</span><br>
                    <input type="search" name="scanner_search" value="{{ $scannerFilters['search'] }}" placeholder="Code or clinic name">
                </label>
                <button type="submit">Apply filters</button>
                <a class="link" href="{{ route('sales.dashboard') }}">Clear</a>
            </form>
            <div class="muted">Showing {{ number_format($scannerPaginator->firstItem() ?? 0) }} – {{ number_format($scannerPaginator->lastItem() ?? 0) }} of {{ number_format($scannerPaginator->total()) }} scanners</div>
            <table>
                <thead>
                    <tr>
                        <th>QR Code</th>
                        <th>Clinic</th>
                        <th>Scans</th>
                        <th>Pet Parents</th>
                        <th>Transactions</th>
                        <th>Status</th>
                        <th>Last Registration</th>
                        <th>Last Transaction</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scannerPaginator as $scanner)
                        <tr>
                            <td>
                                <strong>{{ $scanner['code'] }}</strong><br>
                                <span class="muted">{{ $scanner['public_id'] ?? '—' }}</span>
                            </td>
                            <td>
                                <div>{{ $scanner['clinic']['name'] ?? '—' }}</div>
                                <div class="muted">
                                    {{ $scanner['clinic']['city'] ?? '' }}
                                    @if(!empty($scanner['clinic']['pincode']))
                                        • {{ $scanner['clinic']['pincode'] }}
                                    @endif
                                </div>
                            </td>
                            <td>{{ number_format($scanner['scan_count']) }}</td>
                            <td>
                                {{ number_format($scanner['pet_parent_count']) }}<br>
                                <span class="muted">{{ number_format($scanner['pet_parent_recent_count']) }} in {{ $scannerRecentWindow }}d</span>
                            </td>
                            <td>
                                {{ number_format($scanner['transactions_count']) }}<br>
                                <span class="muted">Direct {{ number_format($scanner['direct_transactions_count']) }} • Clinic {{ number_format($scanner['clinic_transactions_count']) }}</span>
                            </td>
                            <td>
                                @php $statusKey = $scanner['status'] === 'active' ? 'active' : 'inactive'; @endphp
                                <span class="badge {{ $statusKey }}">{{ ucfirst($scanner['status'] ?? 'unknown') }}</span>
                            </td>
                            <td>
                                {{ $scanner['last_registration_at'] ? Carbon::parse($scanner['last_registration_at'])->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td>
                                {{ $scanner['last_transaction_at'] ? Carbon::parse($scanner['last_transaction_at'])->format('d M Y, h:i A') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:2rem 0; color:#64748b;">No scanners match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination">
                @php
                    $scannerQuery = request()->except(['page', 'scanner_page']);
                @endphp
                <span>Page {{ number_format($scannerPaginator->currentPage()) }} of {{ number_format($scannerPaginator->lastPage()) }}</span>
                @if($scannerPaginator->onFirstPage())
                    <span class="button disabled">Previous</span>
                @else
                    <a href="{{ route('sales.dashboard', array_merge($scannerQuery, ['scanner_page' => $scannerPaginator->currentPage() - 1])) }}">Previous</a>
                @endif
                @if($scannerPaginator->hasMorePages())
                    <a href="{{ route('sales.dashboard', array_merge($scannerQuery, ['scanner_page' => $scannerPaginator->currentPage() + 1])) }}">Next</a>
                @else
                    <span class="button disabled">Next</span>
                @endif
            </div>
        </section>

        <section>
            <h2>Vet Registrations</h2>
            <p class="lead">Monitor how clinics progress from draft to active, and how many QR scanners and pet parents they drive.</p>
            <form class="filters" method="GET">
                <input type="hidden" name="scanner_status" value="{{ $scannerFilters['status'] }}">
                <input type="hidden" name="scanner_search" value="{{ $scannerFilters['search'] }}">
                <label>
                    <span class="muted">Status</span><br>
                    <select name="clinic_status">
                        @foreach(['all' => 'All statuses', 'active' => 'Active', 'draft' => 'Draft', 'pending' => 'Pending'] as $value => $label)
                            <option value="{{ $value }}" @selected($clinicFilters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="flex:1">
                    <span class="muted">Search</span><br>
                    <input type="search" name="clinic_search" value="{{ $clinicFilters['search'] }}" placeholder="Clinic name, city, or public ID">
                </label>
                <button type="submit">Apply filters</button>
                <a class="link" href="{{ route('sales.dashboard') }}">Clear</a>
            </form>
            <div class="muted">Showing {{ number_format($clinicPaginator->firstItem() ?? 0) }} – {{ number_format($clinicPaginator->lastItem() ?? 0) }} of {{ number_format($clinicPaginator->total()) }} clinics</div>
            <table>
                <thead>
                    <tr>
                        <th>Clinic</th>
                        <th>Status</th>
                        <th>Scanners</th>
                        <th>Pet Parents</th>
                        <th>Transactions</th>
                        <th>Doctors</th>
                        <th>Created</th>
                        <th>Claimed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clinicPaginator as $clinic)
                        <tr>
                            <td>
                                <div><strong>{{ $clinic['name'] ?? '—' }}</strong></div>
                                <div class="muted">
                                    {{ $clinic['city'] ?? '' }}
                                    @if(!empty($clinic['pincode']))
                                        • {{ $clinic['pincode'] }}
                                    @endif
                                </div>
                                <div class="muted">{{ $clinic['public_id'] ?? '—' }}</div>
                            </td>
                            <td>
                                @php $statusKey = $clinic['status'] === 'active' ? 'active' : 'inactive'; @endphp
                                <span class="badge {{ $statusKey }}">{{ ucfirst($clinic['status'] ?? 'unknown') }}</span>
                            </td>
                            <td>{{ number_format($clinic['scanner_count']) }}</td>
                            <td>{{ number_format($clinic['pet_parents_count']) }}</td>
                            <td>{{ number_format($clinic['transactions_count']) }}</td>
                            <td>{{ number_format($clinic['doctors_count']) }}</td>
                            <td>{{ $clinic['created_at'] ? Carbon::parse($clinic['created_at'])->format('d M Y') : '—' }}</td>
                            <td>{{ $clinic['claimed_at'] ? Carbon::parse($clinic['claimed_at'])->format('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:2rem 0; color:#64748b;">No clinics found for these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="pagination">
                @php
                    $clinicQuery = request()->except(['page', 'clinic_page']);
                @endphp
                <span>Page {{ number_format($clinicPaginator->currentPage()) }} of {{ number_format($clinicPaginator->lastPage()) }}</span>
                @if($clinicPaginator->onFirstPage())
                    <span class="button disabled">Previous</span>
                @else
                    <a href="{{ route('sales.dashboard', array_merge($clinicQuery, ['clinic_page' => $clinicPaginator->currentPage() - 1])) }}">Previous</a>
                @endif
                @if($clinicPaginator->hasMorePages())
                    <a href="{{ route('sales.dashboard', array_merge($clinicQuery, ['clinic_page' => $clinicPaginator->currentPage() + 1])) }}">Next</a>
                @else
                    <span class="button disabled">Next</span>
                @endif
            </div>
        </section>
    </main>
    <footer>
        SnoutIQ Sales Console • Data refreshed {{ now()->format('d M Y, h:i A') }}
    </footer>
</body>
</html>
