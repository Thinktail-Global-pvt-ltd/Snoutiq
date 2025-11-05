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
        th, td { padding: .65rem .8rem; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: .95rem; }
        th { background: #f8fafc; font-weight: 700; }
        form.inline { display: inline; }
        .qr-preview { display: block; margin-top: .5rem; color: #475569; }
        .table-wrapper { overflow-x: auto; }
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
        <form method="POST" action="{{ $legacyStoreRoute }}">
            @csrf
            <div class="grid">
                <div>
                    <label for="code">QR Code Identifier</label>
                    <input id="code" name="code" value="{{ old('code') }}" placeholder="e.g. QR001 (auto-generated if blank)">
                    @error('code')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="clinic_id">Clinic Name</label>
                    <select id="clinic_id" name="clinic_id">
                        <option value="">— pick clinic —</option>
                        @foreach($clinics as $clinic)
                            <option value="{{ $clinic->id }}" @selected(old('clinic_id') == $clinic->id)>
                                {{ $clinic->name ?? ('Clinic #'.$clinic->id) }} ({{ $clinic->public_id }})
                            </option>
                        @endforeach
                    </select>
                    @error('clinic_id')<div class="error">{{ $message }}</div>@enderror
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
                            <th>Clinic Name</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($redirects as $redirect)
                            <tr>
                                <td>{{ $redirect->code }}</td>
                                <td>{{ optional($redirect->clinic)->name ?? '—' }}</td>
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
