<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legacy QR Redirects</title>
    <style>
        :root { font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #0f172a; background: #f1f5f9; }
        body { margin: 0; padding: 0; }
        header { padding: 1.2rem 2rem; background: linear-gradient(90deg,#2563eb,#0ea5e9); color: #fff; }
        main { max-width: 960px; margin: 2rem auto; padding: 0 1.5rem 4rem; }
        h1 { margin: 0; font-size: 1.9rem; }
        .card { background: #fff; border-radius: 1rem; box-shadow: 0 18px 36px -24px rgba(15,23,42,.35); padding: 1.5rem 1.75rem; margin-bottom: 2rem; }
        label { display: block; font-weight: 600; margin-bottom: .35rem; }
        input, select, textarea { width: 100%; border-radius: .75rem; border: 1px solid #cbd5f5; padding: .75rem 1rem; font-size: 1rem; background: #f8fafc; }
        textarea { min-height: 80px; resize: vertical; }
        .grid { display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        button { background: linear-gradient(90deg,#2563eb,#0ea5e9); color: #fff; border: none; border-radius: .75rem; padding: .85rem 1.6rem; font-weight: 700; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .65rem .8rem; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: .95rem; }
        th { background: #f8fafc; font-weight: 700; }
        .status { margin-bottom: 1rem; padding: .75rem 1rem; border-radius: .75rem; background: #ecfdf5; color: #047857; font-weight: 600; }
        .error { color: #dc2626; font-size: .9rem; margin-top: .3rem; }
        form.inline { display: inline; }
    </style>
</head>
<body>
<header>
    <h1>Legacy QR Redirects</h1>
    <p style="margin:.4rem 0 0;opacity:.85">Map old QR codes to the new clinic short links.</p>
</header>
<main>
    @if(session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <div class="card">
        <h2 style="margin-top:0">Create Mapping</h2>
        <form method="POST" action="{{ route('admin.legacy-qr.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid" style="margin-top:1rem">
                <div>
                    <label for="code">Legacy QR Code Identifier</label>
                    <input id="code" name="code" value="{{ old('code') }}" placeholder="e.g. QR001 (auto-generated if blank)">
                    @error('code')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="legacy_url">Original QR URL (optional)</label>
                    <input id="legacy_url" name="legacy_url" value="{{ old('legacy_url') }}" placeholder="https://promo.example.com/qr/QR001">
                    @error('legacy_url')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="target_url">Override Target URL (optional)</label>
                    <input id="target_url" name="target_url" value="{{ old('target_url') }}" placeholder="https://external.example.com/landing">
                    @error('target_url')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="public_id">Clinic Public ID</label>
                    <input id="public_id" name="public_id" value="{{ old('public_id') }}" placeholder="01HFA...">
                    @error('public_id')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="clinic_id">Clinic (optional select)</label>
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
                <div>
                    <label for="slug">Clinic slug (optional)</label>
                    <input id="slug" name="slug" value="{{ old('slug') }}" placeholder="vets/my-clinic">
                    @error('slug')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="qr_image">Upload QR image (optional)</label>
                    <input id="qr_image" type="file" name="qr_image" accept="image/png,image/jpeg,image/webp">
                    <span class="error" style="display:block;color:#475569">If provided, we decode the QR automatically.</span>
                    @error('qr_image')<div class="error" style="color:#dc2626">{{ $message }}</div>@enderror
                </div>
            </div>
            <div style="margin-top:1rem">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" placeholder="Any internal context">{{ old('notes') }}</textarea>
                @error('notes')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div style="margin-top:1.5rem">
                <button type="submit">Save Mapping</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Existing Mappings</h2>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Public ID</th>
                        <th>Target</th>
                        <th>Legacy URL</th>
                        <th>QR Image</th>
                        <th>Notes</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($redirects as $redirect)
                        <tr>
                            <td>{{ $redirect->code }}</td>
                            <td>{{ $redirect->public_id }}</td>
                            <td>
                                @if($redirect->target_url)
                                    <a href="{{ $redirect->target_url }}" target="_blank" rel="noopener">custom</a>
                                @else
                                    <a href="{{ url('c/'.$redirect->public_id) }}" target="_blank" rel="noopener">/c/{{ $redirect->public_id }}</a>
                                @endif
                            </td>
                            <td>
                                @if($redirect->legacy_url)
                                    <a href="{{ $redirect->legacy_url }}" target="_blank" rel="noopener">legacy</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($redirect->qr_image_path)
                                    <a href="{{ asset('storage/'.$redirect->qr_image_path) }}" target="_blank" rel="noopener">view</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $redirect->notes ?? '—' }}</td>
                            <td>{{ $redirect->created_at?->format('d M Y H:i') }}</td>
                            <td>
                                <form class="inline" method="POST" action="{{ route('admin.legacy-qr.destroy', $redirect) }}" onsubmit="return confirm('Remove this mapping?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background:#ef4444">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;color:#64748b;padding:1.5rem 0">No mappings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem">
            {{ $redirects->withQueryString()->links() }}
        </div>
    </div>
</main>
</body>
</html>
