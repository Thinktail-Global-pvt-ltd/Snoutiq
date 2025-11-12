@extends('layouts.sales')

@section('page-title', 'QR Analytics')
@section('hero-title', 'QR Analytics')
@section('hero-description', 'Track QR scans and clinic performance in real time.')

@push('sales-styles')
<style>
    section.card {
        background: #fff;
        border-radius: 1.2rem;
        box-shadow: 0 20px 48px -30px rgba(15,23,42,.4);
        padding: 2rem;
        margin-bottom: 2.25rem;
    }
    .stats-grid {
        display: grid;
        gap: 1.25rem;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        margin-bottom: 1.5rem;
    }
    .stat-card {
        border: 1px solid rgba(15,23,42,.08);
        border-radius: 1rem;
        padding: 1.1rem;
        background: #f8fafc;
    }
    .stat-label {
        font-size: .9rem;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: .08em;
    }
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #0f172a;
    }
    .stat-note {
        font-size: .85rem;
        color: #475569;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        padding: .75rem .9rem;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }
    th {
        text-transform: uppercase;
        font-size: .8rem;
        letter-spacing: .08em;
        color: #475569;
    }
    .badge {
        display: inline-block;
        padding: .15rem .55rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 600;
    }
    .badge-active {
        background: rgba(16,185,129,.15);
        color: #047857;
    }
    .badge-inactive {
        background: rgba(251,146,60,.15);
        color: #9a3412;
    }
</style>
@endpush

@section('content')
    <section class="card">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total scans</div>
                <div class="stat-value">{{ number_format($stats['total_scans']) }}</div>
                <div class="stat-note">Across all QR codes</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active QR codes</div>
                <div class="stat-value">{{ number_format($stats['active_codes']) }}</div>
                <div class="stat-note">Currently routing traffic</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Inactive / pending</div>
                <div class="stat-value">{{ number_format($stats['inactive_codes']) }}</div>
                <div class="stat-note">Mapped but not yet active</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Last scan seen</div>
                <div class="stat-value" style="font-size:1.2rem;">
                    @if($stats['last_scan_at'])
                        {{ optional($stats['last_scan_at'])->timezone('Asia/Kolkata')->format('d M, h:i A') }}
                    @else
                        —
                    @endif
                </div>
                <div class="stat-note">IST timezone</div>
            </div>
        </div>

        <h3 style="margin:0 0 1.2rem;">Top QR scans</h3>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                <tr>
                    <th>QR Code</th>
                    <th>Clinic</th>
                    <th>Scans</th>
                    <th>Last scanned</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                @forelse($redirects as $redirect)
                    <tr>
                        <td>
                            <strong>{{ $redirect->code }}</strong><br>
                            <a href="{{ $redirect->scan_url }}" target="_blank" rel="noopener">
                                {{ $redirect->scan_url }}
                            </a>
                        </td>
                        <td>
                            {{ $redirect->clinic->name ?? '—' }}<br>
                            @if($redirect->clinic?->slug)
                                <span style="font-size:.9rem;color:#475569;">
                                    /vets/{{ $redirect->clinic->slug }}
                                </span>
                            @endif
                        </td>
                        <td style="font-weight:700;font-size:1.15rem;">
                            {{ number_format($redirect->scan_count ?? 0) }}
                        </td>
                        <td>
                            @if($redirect->last_scanned_at)
                                {{ $redirect->last_scanned_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($redirect->status === 'active')
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge badge-inactive">{{ ucfirst($redirect->status ?? 'inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;color:#64748b;padding:1.5rem 0;">
                            No QR scans recorded yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1.5rem;">
            {{ $redirects->withQueryString()->links() }}
        </div>
    </section>
@endsection
