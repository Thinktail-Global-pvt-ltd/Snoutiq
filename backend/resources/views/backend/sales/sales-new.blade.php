@extends('layouts.sales')

@section('page-title', 'Clinic QR Directory')
@section('hero-title', 'Clinic QR Directory')
@section('hero-description', 'Instant QR codes for every vet slug in the system.')

@push('sales-styles')
<style>
    section.card {
        background: #fff;
        border-radius: 1.2rem;
        box-shadow: 0 20px 48px -30px rgba(15,23,42,.4);
        padding: 2rem;
        margin-bottom: 2.25rem;
    }
    h2 { margin: 0 0 0.4rem; font-size: 1.8rem; }
    .lead { margin: 0 0 1.5rem; color: #475569; }
    .qr-grid {
        display: grid;
        gap: 1.4rem;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }
    .qr-card {
        border: 1px solid rgba(15,23,42,0.08);
        border-radius: 1rem;
        padding: 1.1rem;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }
    .qr-card img {
        width: 100%;
        height: auto;
        border-radius: 0.6rem;
        background: #fff;
        padding: 0.6rem;
        box-shadow: inset 0 0 0 1px rgba(15,23,42,0.05);
    }
    .qr-card .title {
        font-weight: 700;
        font-size: 1.05rem;
        margin: 0;
    }
    .qr-card .slug {
        font-size: 0.9rem;
        color: #475569;
        word-break: break-all;
    }
    .qr-card .slug strong {
        color: #0f172a;
    }
    .qr-card .meta {
        font-size: 0.82rem;
        color: #475569;
    }
.qr-card .referral {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1d4ed8;
    letter-spacing: 0.08em;
}
    .qr-card .actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }
    .qr-card .actions a {
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
        color: #1d4ed8;
        background: rgba(59,130,246,0.12);
        padding: 0.4rem 0.75rem;
        border-radius: 0.75rem;
    }
    .qr-card .actions a:hover {
        background: rgba(59,130,246,0.18);
    }
</style>
@endpush

@section('content')
    <section class="card">
        <h2>All Clinic QR Codes</h2>
        <p class="lead">
            Generated at {{ $generatedAt }} · {{ $clinicQrs->count() }} clinics detected.
            Scan or download any QR below to land on <code>https://snoutiq.com/backend/vets/{slug}</code>.
        </p>

        @if($clinicQrs->isEmpty())
            <p style="color:#475569;">No clinics found in <code>vet_registerations_temp</code>.</p>
        @else
            <div class="qr-grid">
                @foreach($clinicQrs as $entry)
                    <article class="qr-card">
                        <img src="{{ $entry['qr_data_uri'] }}" alt="QR for {{ $entry['slug'] }}">
                        <div>
                            <p class="title">{{ $entry['name'] }}</p>
                            <p class="slug">
                                <strong>Tracker:</strong> {{ $entry['target_url'] }}<br>
                                <strong>Landing:</strong> {{ $entry['landing_url'] }}
                            </p>
                            <p class="referral">Referral: {{ $entry['referral_code'] }}</p>
                            <p class="meta">
                                ID #{{ $entry['id'] }}
                                @if($entry['city']) · {{ $entry['city'] }} @endif
                                @if($entry['status']) · status: {{ ucfirst($entry['status']) }} @endif
                                @if(!is_null($entry['scan_count']))
                                    · scans: {{ number_format($entry['scan_count']) }}
                                @endif
                            </p>
                        </div>
                        <div class="actions">
                            <a href="{{ $entry['landing_url'] }}" target="_blank" rel="noopener">Open page</a>
                            <a href="{{ $entry['qr_data_uri'] }}" download="{{ $entry['slug'] }}-qr.png">Download PNG</a>
                            <a href="{{ route('sales.clinic-card', $entry['id']) }}" target="_blank" rel="noopener">Booking card</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
