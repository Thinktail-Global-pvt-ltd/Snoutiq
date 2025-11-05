<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sales CRM • SnoutIQ</title>
    <style>
        :root {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            background: #f1f5f9;
        }
        body { margin: 0; padding: 0; }
        header { padding: 1.5rem 2.25rem; background: linear-gradient(90deg,#1d4ed8,#0ea5e9); color: #fff; }
        header h1 { margin: 0; font-size: 2rem; }
        header p { margin: .45rem 0 0; opacity: .88; max-width: 640px; }
        nav { margin-top: 1.25rem; display: flex; gap: .75rem; flex-wrap: wrap; }
        nav a {
            color: #1d4ed8;
            background: rgba(248,250,252,.95);
            padding: .55rem 1.1rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: .95rem;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        nav a:hover { transform: translateY(-1px); box-shadow: 0 8px 18px -12px rgba(15,23,42,.45); }
        main { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }
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
        @media (max-width: 720px) {
            header { padding: 1.5rem 1.25rem; }
            main { padding: 2rem 1.25rem 3rem; }
        }
    </style>
</head>
<body>
<header>
    <h1>Sales CRM</h1>
    <p>Manage legacy QR redirects. Map each QR code to the correct clinic name.</p>
    <nav>
        <a href="#legacy-qr">Legacy QR Redirects</a>
    </nav>
</header>
<main>
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
                                                $fileContents = $publicDisk->get($redirect->qr_image_path);
                                                $qrDisk->put($redirect->qr_image_path, $fileContents);
                                                $publicDisk->delete($redirect->qr_image_path);
                                            }

                                            if ($qrDisk->exists($redirect->qr_image_path)) {
                                                $mime = $qrDisk->mimeType($redirect->qr_image_path) ?: 'image/png';
                                                $contents = base64_encode($qrDisk->get($redirect->qr_image_path));
                                                $qrDataUri = 'data:'.$mime.';base64,'.$contents;
                                            }
                                        }
                                    @endphp
                                    @if($qrDataUri)
                                        <div class="qr-preview" style="margin-top:.8rem;">
                                            <span style="display:block;font-size:.78rem;color:#64748b;margin-bottom:.35rem;">QR image</span>
                                            <div class="qr-thumbnail">
                                                <img src="{{ $qrDataUri }}" alt="QR image for {{ $redirect->code }}">
                                            </div>
                                            <div class="qr-actions">
                                                <a href="{{ $qrDataUri }}" download="{{ $qrDownloadName }}">Download</a>
                                                <a href="{{ $qrDataUri }}" target="_blank" rel="noopener noreferrer">Open</a>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ optional($redirect->clinic)->name ?? '—' }}</div>
                                    @if(optional($redirect->clinic)->slug)
                                        <a class="qr-preview" href="{{ url('/vets/'.optional($redirect->clinic)->slug) }}" target="_blank" rel="noopener noreferrer">
                                            View draft site
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    <form class="inline" method="POST" action="{{ route($legacyDestroyRouteName, $redirect) }}" onsubmit="return confirm('Remove this mapping?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:#ef4444">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align:center;color:#64748b;padding:1.5rem 0">No mappings yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:1rem">
                {{ $redirects->withQueryString()->links() }}
            </div>
        </div>
    </section>
</main>

</body>
</html>
