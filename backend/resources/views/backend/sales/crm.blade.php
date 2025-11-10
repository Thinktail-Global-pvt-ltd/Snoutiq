@extends('layouts.sales')

@section('page-title', 'Sales CRM')
@section('hero-title', 'Sales CRM')
@section('hero-description', 'Manage legacy QR redirects. Map each QR code to the correct clinic.')

@push('sales-styles')
<style>
    section.card {
        background: #fff;
        border-radius: 1.2rem;
        box-shadow: 0 20px 48px -30px rgba(15,23,42,.4);
        padding: 2rem;
        margin-bottom: 2.25rem;
    }
    h2 { margin: 0 0 1.25rem; font-size: 1.6rem; }
    .lead { margin: .4rem 0 1.6rem; color: #475569; }
    form { display: grid; gap: 1.25rem; }
    .grid { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    label { font-weight: 600; font-size: .96rem; display: block; margin-bottom: .35rem; }
    input, textarea, select {
        border: 1px solid #cbd5f5;
        border-radius: .85rem;
        padding: .75rem 1rem;
        font-size: 1rem;
        background: #f8fafc;
    }
    textarea { resize: vertical; min-height: 90px; }
    button {
        cursor: pointer;
        border-radius: .9rem;
        border: none;
        font-weight: 700;
        font-size: 1rem;
        padding: .85rem 1.7rem;
        background: linear-gradient(90deg,#2563eb,#0ea5e9);
        color: #fff;
    }
    button[disabled] { opacity: .65; cursor: not-allowed; }
    .actions { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
    .note { font-size: .9rem; color: #475569; }
    .status { margin-bottom: 1.5rem; padding: .9rem 1.1rem; border-radius: .85rem; background: #ecfdf5; color: #047857; font-weight: 600; }
    .error { color: #dc2626; font-size: .85rem; margin-top: .3rem; }
    .error-summary {
        margin-bottom: 1.5rem;
        padding: .9rem 1.1rem;
        border-radius: .85rem;
        background: #fee2e2;
        color: #b91c1c;
        font-weight: 600;
    }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: .65rem .8rem; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: .95rem; vertical-align: middle; }
    th { background: #f8fafc; font-weight: 700; }
    form.inline { display: inline; }
    .qr-preview { display: block; margin-top: .5rem; color: #475569; }
    .table-wrapper { overflow-x: auto; }
    .qr-thumbnail {
        display: inline-block;
        width: 110px;
        height: 110px;
        border-radius: .85rem;
        border: 1px solid rgba(148,163,184,.3);
        padding: .35rem;
        background: #fff;
        box-shadow: 0 10px 28px -22px rgba(15,23,42,.6);
    }
    .qr-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: .45rem;
    }
    .qr-actions {
        display: flex;
        gap: .6rem;
        margin-top: .5rem;
    }
    .qr-actions a {
        font-size: .78rem;
        color: #1d4ed8;
        text-decoration: none;
        padding: .3rem .55rem;
        border-radius: .65rem;
        background: rgba(59,130,246,.12);
        font-weight: 600;
    }
    .qr-actions a:hover {
        background: rgba(59,130,246,.18);
    }
    .sales-content { padding: 2.5rem 2.5rem 4rem; }
    @media (max-width: 720px) {
        .sales-content { padding: 2rem 1.25rem 3rem; }
    }
</style>
@endpush

@section('content')
    @if(session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="error-summary">
            There were validation issues with your last action. Please review the highlighted fields below.
        </div>
    @endif

    <section id="legacy-qr" class="card">
        <h2>Legacy QR Redirects</h2>
        <p class="lead">Save or update a QR code and link it to the clinic it belongs to.</p>
        <form method="POST" action="{{ $legacyStoreRoute }}" enctype="multipart/form-data">
            @csrf
            <div class="grid">
                <div>
                    <label for="code">QR Code Identifier</label>
                    <input id="code" name="code" value="{{ old('code') }}" placeholder="e.g. QR001 (auto-generated if blank)">
                    @error('code')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="clinic_name">Clinic Name</label>
                    <input id="clinic_name" name="clinic_name" value="{{ old('clinic_name') }}" placeholder="e.g. Happy Tails Clinic">
                    <span class="note">We create/update the clinic draft using this name and slug.</span>
                    @error('clinic_name')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="clinic_slug">Clinic Slug</label>
                    <input id="clinic_slug" name="clinic_slug" value="{{ old('clinic_slug') }}" placeholder="happy-tails-clinic">
                    <span class="note">Leave blank to auto-generate from the clinic name.</span>
                    @error('clinic_slug')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="qr_image">Upload QR Image</label>
                    <input id="qr_image" name="qr_image" type="file" accept="image/png,image/jpeg,image/webp">
                    <span class="note">PNG / JPG / WEBP (max 4 MB). We auto-link the draft clinic page.</span>
                    @error('qr_image')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="actions">
                <button type="submit">Save Mapping</button>
            </div>
        </form>

        <div style="margin-top:2rem">
            <h3 style="margin:0 0 1rem;">Existing Mappings</h3>
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>QR Code</th>
                        <th>Clinic</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($redirects as $redirect)
                        <tr>
                            <td>
                                <div>{{ $redirect->code }}</div>
                                @if($redirect->public_id)
                                    <a class="qr-preview" href="{{ $redirect->scan_url }}" target="_blank" rel="noopener noreferrer">
                                        Legacy QR link
                                    </a>
                                @endif
                                @php
                                    $qrDataUri = null;
                                    $qrDownloadName = $redirect->code ? $redirect->code.'.png' : 'legacy-qr.png';
                                    if ($redirect->qr_image_path) {
                                        $qrDisk = Storage::disk('local');
                                        if (! $qrDisk->exists($redirect->qr_image_path) && Storage::disk('public')->exists($redirect->qr_image_path)) {
                                            $publicDisk = Storage::disk('public');
                                            $contents = $publicDisk->get($redirect->qr_image_path);
                                        } elseif ($qrDisk->exists($redirect->qr_image_path)) {
                                            $contents = $qrDisk->get($redirect->qr_image_path);
                                        } else {
                                            $contents = null;
                                        }
                                        if ($contents) {
                                            $qrDataUri = 'data:image/png;base64,'.base64_encode($contents);
                                        }
                                    }
                                @endphp
                                @if($qrDataUri)
                                    <div class="qr-thumbnail">
                                        <img src="{{ $qrDataUri }}" alt="QR for {{ $redirect->code }}">
                                    </div>
                                    <div class="qr-actions">
                                        <a href="{{ $qrDataUri }}" download="{{ $qrDownloadName }}">Download PNG</a>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $redirect->clinic_name ?: '—' }}</td>
                            <td>
                                <form class="inline" method="POST" action="{{ route('sales.legacy-qr.destroy', $redirect) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background:#ef4444;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted">No QR mappings yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
@endsection
