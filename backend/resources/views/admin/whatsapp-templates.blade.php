@extends('layouts.admin-panel')

@section('page-title', 'WhatsApp Templates')

@push('styles')
<style>
    .template-card { border-radius: 18px; }
    .placeholder-badge { font-size: 0.82rem; }
</style>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm template-card">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Manual WhatsApp templates</h2>
                        <p class="text-muted mb-0">Copy-ready bodies for both pet parent (<code>pp_booking_confirmed</code>) and vet (<code>vet_new_consultation_assigned</code>) on the same row.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">2 templates</span>
                        <span class="badge text-bg-dark px-3 py-2 text-light">Manual send helper</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Template</th>
                                <th>Audience</th>
                                <th>Description</th>
                                <th>Placeholders</th>
                                <th>Copy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                                <tr>
                                    <td class="fw-semibold text-nowrap">{{ $template['key'] }}</td>
                                    <td class="text-nowrap">{{ $template['audience'] }}</td>
                                    <td class="text-muted small">{{ $template['description'] }}</td>
                                    <td style="min-width:240px;">
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($template['placeholders'] as $num => $label)
                                                <span class="badge text-bg-light placeholder-badge">#{{ $num }} {{ $label }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary" data-body="{{ $template['body'] }}" onclick="copyTemplate(this)">
                                            <i class="bi bi-clipboard"></i> Copy body
                                        </button>
                                    </td>
                                </tr>
                                <tr class="table-borderless">
                                    <td colspan="5" class="pb-4">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong class="small text-uppercase text-muted">Preview</strong>
                                                <button class="btn btn-sm btn-outline-secondary" data-body="{{ $template['body'] }}" onclick="copyTemplate(this)">
                                                    <i class="bi bi-clipboard"></i> Copy again
                                                </button>
                                            </div>
                                            <pre class="mb-0 small" style="white-space: pre-wrap;">{{ $template['body'] }}</pre>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function copyTemplate(btn) {
        const text = btn.getAttribute('data-body') || '';
        navigator.clipboard.writeText(text).then(() => {
            btn.innerText = 'Copied!';
            btn.classList.remove('btn-outline-primary','btn-outline-secondary');
            btn.classList.add('btn-success','text-white');
            setTimeout(() => {
                btn.innerText = 'Copy body';
                btn.classList.add('btn-outline-primary');
                btn.classList.remove('btn-success','text-white');
            }, 1200);
        }).catch(() => {
            alert('Unable to copy. Please copy manually.');
        });
    }
</script>
@endpush
