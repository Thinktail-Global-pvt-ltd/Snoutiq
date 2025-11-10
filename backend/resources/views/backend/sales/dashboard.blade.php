@php
    use Illuminate\Support\Carbon;
@endphp

@extends('layouts.sales')

@section('page-title', 'Sales Dashboard')
@section('hero-title', 'Sales Performance Dashboard')
@section('hero-description', 'Track QR scanner adoption, clinic onboarding progress, and downstream transactions.')

@push('sales-styles')
<style>
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
    section.data-block {
        background: #fff;
        border-radius: 1.2rem;
        padding: 2rem;
        margin-bottom: 2.5rem;
        box-shadow: 0 24px 48px -32px rgba(15,23,42,.3);
        border: 1px solid rgba(148,163,184,.14);
    }
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
        text-decoration: none;
        padding: .75rem 1.5rem;
        border-radius: .85rem;
        font-weight: 600;
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
    .badge.draft { background: rgba(248,250,252,.7); color: #475569; border: 1px solid rgba(148,163,184,.4); }
    .badge.claimed { background: rgba(250,204,21,.18); color: #854d0e; border: 1px solid rgba(250,204,21,.4); }
    .badge.published { background: rgba(59,130,246,.15); color: #1d4ed8; border: 1px solid rgba(59,130,246,.35); }
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
    footer { text-align: center; padding: 2.5rem 1rem 1.5rem; color: #64748b; font-size: .85rem; }
    .quick-link-pill {
        display:inline-flex;
        align-items:center;
        gap:0.4rem;
        background:#fff;
        color:#0f172a;
        border-radius:999px;
        padding:0.6rem 1.2rem;
        text-decoration:none;
        font-weight:600;
        box-shadow:0 8px 24px -14px rgba(15,23,42,.55);
    }
    .quick-link-pill span:first-child {
        font-size:1.1rem;
    }
    .sales-content { padding-bottom: 3rem; }
    @media (max-width: 720px) {
        form.filters { flex-direction: column; align-items: stretch; }
        table { font-size: .9rem; }
        th, td { padding: .7rem .75rem; }
    }
</style>
@endpush

@section('content')
    <div style="margin-bottom:1.5rem;display:flex;flex-wrap:wrap;gap:0.75rem;">
        <a class="quick-link-pill" href="{{ route('admin.onboarding.panel') }}" target="_blank" rel="noopener">
            <span>↗</span>
            <span>Open Admin Onboarding</span>
        </a>
    </div>

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

    <section class="data-block">
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
            </tr>
            </thead>
            <tbody>
            @foreach($scannerPaginator as $scanner)
                @php
                    $scannerCode = data_get($scanner, 'code');
                    $createdAt = data_get($scanner, 'created_at');
                    $createdAt = $createdAt ? Carbon::parse($createdAt) : null;
                    $clinicName = data_get($scanner, 'clinic.name');
                    $scanCount = (int) data_get($scanner, 'scan_count', 0);
                    $petParents = (int) data_get($scanner, 'pet_parents', 0);
                    $transactions = (int) data_get($scanner, 'transactions', 0);
                    $status = data_get($scanner, 'status', 'inactive');
                    $lastRegistration = data_get($scanner, 'last_registration_at');
                    $lastRegistration = $lastRegistration ? Carbon::parse($lastRegistration) : null;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $scannerCode ?? '—' }}</strong><br>
                        <span class="muted">{{ optional($createdAt)->format('d M Y') }}</span>
                    </td>
                    <td>{{ $clinicName ?? '—' }}</td>
                    <td>{{ number_format($scanCount) }}</td>
                    <td>{{ number_format($petParents) }}</td>
                    <td>{{ number_format($transactions) }}</td>
                    <td>
                        <span class="badge {{ $status === 'active' ? 'active' : 'inactive' }}">
                            {{ ucfirst($status) }}
                        </span>
                    </td>
                    <td>{{ optional($lastRegistration)->diffForHumans() ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="pagination">
            {{ $scannerPaginator->withQueryString()->links() }}
        </div>
    </section>

    <section class="data-block">
        <h2>Clinic Onboarding Pipeline</h2>
        <p class="lead">Monitor clinic drafts, active listings, and downstream impact.</p>
        <form class="filters" method="GET">
            <input type="hidden" name="scanner_status" value="{{ $scannerFilters['status'] }}">
            <input type="hidden" name="scanner_search" value="{{ $scannerFilters['search'] }}">
            <label>
                <span class="muted">Status</span><br>
                <select name="clinic_status">
                    @foreach([
                        'all' => 'All statuses',
                        'draft' => 'Draft',
                        'claimed' => 'Claimed',
                        'active' => 'Active',
                        'published' => 'Published',
                    ] as $value => $label)
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

        <table>
            <thead>
            <tr>
                <th>Clinic</th>
                <th>Status</th>
                <th>City</th>
                <th>Doctors</th>
                <th>QR Scanners</th>
                <th>Pet Parents via QR</th>
                <th>Transactions</th>
                <th>Created</th>
                <th>Claimed</th>
            </tr>
            </thead>
            <tbody>
            @foreach($clinicPaginator as $clinic)
                @php
                    $status = strtolower((string) data_get($clinic, 'status', 'draft'));
                    $badgeClass = in_array($status, ['active','draft','claimed','published']) ? $status : 'inactive';
                    $cityParts = array_filter([data_get($clinic, 'city'), data_get($clinic, 'pincode')]);
                    $cityLabel = empty($cityParts) ? '—' : implode(', ', $cityParts);
                    $createdAt = data_get($clinic, 'created_at');
                    $createdAt = $createdAt ? Carbon::parse($createdAt) : null;
                    $claimedAt = data_get($clinic, 'claimed_at');
                    $claimedAt = $claimedAt ? Carbon::parse($claimedAt) : null;
                @endphp
                <tr>
                    <td>
                        <strong>{{ data_get($clinic, 'name', '—') }}</strong><br>
                        <span class="muted">{{ data_get($clinic, 'public_id', '—') }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $badgeClass }}">
                            {{ ucfirst($status) }}
                        </span>
                    </td>
                    <td>{{ $cityLabel }}</td>
                    <td>{{ number_format((int) data_get($clinic, 'doctors_count', 0)) }}</td>
                    <td>{{ number_format((int) data_get($clinic, 'scanner_count', 0)) }}</td>
                    <td>{{ number_format((int) data_get($clinic, 'pet_parents_count', 0)) }}</td>
                    <td>{{ number_format((int) data_get($clinic, 'transactions_count', 0)) }}</td>
                    <td>{{ optional($createdAt)->format('d M Y') ?? '—' }}</td>
                    <td>{{ optional($claimedAt)->format('d M Y') ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

    </section>

    <footer>
        SnoutIQ Sales Console • Data refreshed {{ now()->format('d M Y, h:i A') }}
    </footer>
@endsection
