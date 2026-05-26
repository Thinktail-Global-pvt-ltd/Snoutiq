@extends('layouts.admin-panel')

@section('page-title', 'Diagnosis Comparison')
@section('hide-sidebar', 'true')
@section('access-badge', 'Public view')

@push('styles')
<style>
    .comparison-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
    }
    .comparison-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .comparison-panel {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1rem;
        background: #f8fafc;
        min-height: 160px;
    }
    .comparison-label {
        display: block;
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        margin-bottom: 0.45rem;
        text-transform: uppercase;
    }
    .diagnosis-text {
        overflow-wrap: anywhere;
        white-space: pre-wrap;
    }
    @media (max-width: 767.98px) {
        .comparison-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@php
    $diagnosisColumn = \Illuminate\Support\Facades\Schema::hasTable('prescriptions') && \Illuminate\Support\Facades\Schema::hasColumn('prescriptions', 'diagnosis')
        ? 'diagnosis'
        : (\Illuminate\Support\Facades\Schema::hasTable('prescriptions') && \Illuminate\Support\Facades\Schema::hasColumn('prescriptions', 'diagnosys') ? 'diagnosys' : null);
@endphp

<section class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">Doctor vs AI diagnosis comparison</h2>
        <p class="text-muted mb-0">
            Shareable comparison report for transactions <code>855</code> and <code>866</code>.
        </p>
    </div>
    <span class="badge text-bg-dark align-self-start align-self-md-center">Gemini 2.5 Flash</span>
</section>

@if (!$hasRequiredTables || !$hasRequiredColumns)
    <div class="alert alert-danger">
        This report cannot run because the required <code>transactions</code>/<code>users</code> tables or transaction columns are missing.
    </div>
@elseif($transactions->isEmpty())
    <div class="comparison-card p-5 text-center text-muted">
        No matching transactions found for IDs 855 and 866.
    </div>
@else
    <div class="d-flex flex-column gap-3">
        @foreach($transactions as $transaction)
            @php
                $relatedPrescriptions = $transaction->relationLoaded('prescriptions') ? $transaction->prescriptions : collect();
                $doctorDiagnoses = $diagnosisColumn
                    ? $relatedPrescriptions
                        ->map(fn ($prescription) => trim((string) ($prescription->{$diagnosisColumn} ?? '')))
                        ->filter()
                        ->values()
                    : collect();
            @endphp
            <article class="comparison-card p-3 p-md-4 diagnosis-comparison-item" data-url="{{ route('captured-transactions.diagnosis-comparison', $transaction) }}">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                    <div>
                        <h3 class="h6 mb-1">Transaction #{{ $transaction->id }}</h3>
                        <div class="text-muted small">
                            {{ optional($transaction->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? 'Date unavailable' }}
                            @if(!empty($transaction->user?->name))
                                &middot; {{ $transaction->user->name }}
                            @endif
                        </div>
                    </div>
                    <span class="badge text-bg-light align-self-start">Doctor vs AI</span>
                </div>

                <div class="comparison-grid">
                    <section class="comparison-panel">
                        <span class="comparison-label">Doctor diagnosis</span>
                        @if($doctorDiagnoses->isNotEmpty())
                            <div class="diagnosis-text fw-semibold">
                                @foreach($doctorDiagnoses as $diagnosis)
                                    <div>{{ $diagnosis }}</div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted">No prescription diagnosis found.</div>
                        @endif
                    </section>

                    <section class="comparison-panel ai-panel">
                        <span class="comparison-label">AI diagnosis</span>
                        <div class="text-muted ai-loading">Loading AI diagnosis...</div>
                        <div class="ai-result d-none"></div>
                    </section>
                </div>

                <section class="border rounded p-3 mt-3 bg-light comparison-summary d-none"></section>
            </article>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script>
(() => {
    const csrfToken = '{{ csrf_token() }}';
    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    const renderList = (items) => {
        if (!Array.isArray(items) || items.length === 0) return '<span class="text-muted">N/A</span>';
        return `<ul class="mb-0 ps-3">${items.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
    };

    document.querySelectorAll('.diagnosis-comparison-item').forEach(async (item) => {
        const loading = item.querySelector('.ai-loading');
        const result = item.querySelector('.ai-result');
        const summary = item.querySelector('.comparison-summary');

        try {
            const response = await fetch(item.dataset.url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({}),
            });
            const payload = await response.json();

            if (!response.ok || !payload.success) {
                loading.textContent = payload.message || 'Unable to generate AI diagnosis.';
                return;
            }

            const comparison = payload.comparison || {};
            loading.classList.add('d-none');
            result.classList.remove('d-none');
            result.innerHTML = `
                <div class="diagnosis-text fw-semibold">${escapeHtml(comparison.ai_diagnosis || 'N/A')}</div>
                <div class="small text-muted mt-2">Confidence: ${escapeHtml(comparison.confidence || 'N/A')}</div>
                <div class="mt-3">
                    <span class="comparison-label">AI basis</span>
                    ${renderList(comparison.basis)}
                </div>
            `;

            summary.classList.remove('d-none');
            summary.innerHTML = `
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                    <div>
                        <span class="comparison-label mb-1">Comparison</span>
                        <p class="mb-0">${escapeHtml(comparison.comparison_summary || 'N/A')}</p>
                    </div>
                    <span class="badge text-bg-dark align-self-start">${escapeHtml(comparison.match_status || 'N/A')}</span>
                </div>
                <div class="small text-muted">${escapeHtml(comparison.recommended_review || 'Use as internal comparison only. A veterinarian should review any mismatch.')}</div>
            `;
        } catch (error) {
            loading.textContent = 'Unable to generate AI diagnosis.';
        }
    });
})();
</script>
@endpush
