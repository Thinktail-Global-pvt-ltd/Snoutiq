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
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }
    .summary-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
    }
    .summary-box {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.85rem;
        background: #fff;
    }
    .comparison-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .context-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
    }
    .context-box {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.85rem;
        background: #ffffff;
        min-height: 95px;
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
    .source-image {
        width: 100%;
        max-height: 180px;
        object-fit: contain;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
    }
    @media (max-width: 767.98px) {
        .comparison-grid,
        .context-grid,
        .summary-strip {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<section class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">Doctor vs AI diagnosis comparison</h2>
        <p class="text-muted mb-0">
            Shareable comparison report for transactions <code>855</code> and <code>866</code>.
        </p>
    </div>
    <div class="d-flex flex-column flex-sm-row gap-2 align-self-start align-self-md-center">
        <a href="{{ route('captured-transactions.diagnosis-report.pdf') }}" class="btn btn-dark btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
        <span class="badge text-bg-dark align-self-start align-self-sm-center">Gemini 2.5 Flash</span>
    </div>
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
    <section class="summary-strip mb-3">
        <div class="summary-box">
            <span class="comparison-label">Transactions</span>
            <strong>{{ number_format($metrics['total_transactions'] ?? 0) }}</strong>
        </div>
        <div class="summary-box">
            <span class="comparison-label">Unique users</span>
            <strong>{{ number_format($metrics['unique_users'] ?? 0) }}</strong>
        </div>
        <div class="summary-box">
            <span class="comparison-label">Report scope</span>
            <strong>#855, #866</strong>
        </div>
        <div class="summary-box">
            <span class="comparison-label">Output</span>
            <strong>Doctor vs AI</strong>
        </div>
    </section>

    <div class="d-flex flex-column gap-3">
        @foreach($reportRows as $row)
            @php
                $transaction = $row['transaction'];
                $user = $row['user'];
                $doctor = $row['doctor'];
                $pet = $row['pet'];
                $doctorDiagnoses = collect($row['doctor_diagnoses'] ?? []);
                $imageDocuments = $row['image_documents'] ?? [];
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

                <div class="context-grid mb-3">
                    <section class="context-box">
                        <span class="comparison-label">Pet</span>
                        <div class="fw-semibold">{{ $pet->name ?? 'Pet unavailable' }}</div>
                        <div class="small text-muted">
                            {{ collect([$pet?->pet_type ?? $pet?->type ?? null, $pet?->breed ?? null, $pet?->pet_age ? $pet->pet_age . ' yrs' : null, $pet?->pet_gender ?? null])->filter()->join(' · ') ?: 'Details unavailable' }}
                        </div>
                    </section>
                    <section class="context-box">
                        <span class="comparison-label">Doctor</span>
                        <div class="fw-semibold">{{ $doctor->doctor_name ?? 'Doctor unavailable' }}</div>
                        <div class="small text-muted">
                            {{ collect([$doctor?->degree ?? null, $doctor?->doctor_license ?? null])->filter()->join(' · ') ?: 'Details unavailable' }}
                        </div>
                    </section>
                    <section class="context-box">
                        <span class="comparison-label">User</span>
                        <div class="fw-semibold">{{ $user->name ?? 'User #' . $transaction->user_id }}</div>
                        <div class="small text-muted">
                            {{ collect([$user?->phone ?? null, $user?->email ?? null])->filter()->join(' · ') ?: 'Contact unavailable' }}
                        </div>
                    </section>
                    <section class="context-box">
                        <span class="comparison-label">Image used</span>
                        @forelse($imageDocuments as $document)
                            <div class="small fw-semibold">{{ $document['label'] }}</div>
                            <div class="small text-muted">{{ $document['mime_type'] }}</div>
                        @empty
                            <div class="text-muted small">No image/report blob available.</div>
                        @endforelse
                    </section>
                </div>

                @if(!empty($row['reported_symptom']))
                    <section class="border rounded p-3 mb-3 bg-light">
                        <span class="comparison-label">Reported symptom</span>
                        <div class="diagnosis-text">{{ $row['reported_symptom'] }}</div>
                    </section>
                @endif

                @if(!empty($imageDocuments))
                    <div class="row g-3 mb-3">
                        @foreach($imageDocuments as $document)
                            <div class="col-md-6">
                                <div class="border rounded p-2 bg-light h-100">
                                    <div class="small fw-semibold mb-2">{{ $document['label'] }}</div>
                                    @if(!empty($document['data_uri']))
                                        <img src="{{ $document['data_uri'] }}" class="source-image" alt="{{ $document['label'] }}">
                                    @else
                                        <div class="text-muted small">Preview unavailable for {{ $document['mime_type'] }}.</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

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
        if (!Array.isArray(items) || items.length === 0) return '<span class="text-muted">No AI basis returned.</span>';
        return `<ul class="mb-0 ps-3">${items.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
    };
    const usefulText = (value, fallback) => {
        const text = String(value ?? '').trim();
        const lower = text.toLowerCase();
        const looksJson = text.startsWith('{') || text.startsWith('[') || text.startsWith('```');
        return text && !looksJson && !['n/a', 'na', 'n.a.', 'unknown', 'null'].includes(lower) ? text : fallback;
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
                <div class="diagnosis-text fw-semibold">${escapeHtml(usefulText(comparison.ai_diagnosis, 'Limited-evidence AI impression: veterinary consultation recommended for further assessment'))}</div>
                <div class="small text-muted mt-2">Confidence: ${escapeHtml(usefulText(comparison.confidence, 'low'))}</div>
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
                        <p class="mb-0">${escapeHtml(usefulText(comparison.comparison_summary, 'AI generated a limited-evidence impression for doctor review.'))}</p>
                    </div>
                    <span class="badge text-bg-dark align-self-start">${escapeHtml(usefulText(comparison.match_status, 'insufficient_data'))}</span>
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
