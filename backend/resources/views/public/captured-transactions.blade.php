@extends('layouts.admin-panel')

@section('page-title', $reportTitle ?? 'Captured Transactions')
@section('hide-sidebar', 'true')
@section('access-badge', 'Public view')

@push('styles')
<style>
    .public-transactions-table td,
    .public-transactions-table th {
        vertical-align: top;
    }
    .public-transactions-table tr.gst-included-row > * {
        background-color: #dcfce7;
    }
    .public-transactions-table tr.gst-not-added-row > * {
        background-color: #fee2e2;
    }
    .price-breakdown {
        line-height: 1.35;
    }
    .diagnosis-cell {
        min-width: 220px;
        max-width: 320px;
    }
    .diagnosis-text {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    @media (max-width: 767.98px) {
        .public-transactions-table thead {
            display: none;
        }
        .public-transactions-table,
        .public-transactions-table tbody,
        .public-transactions-table tr,
        .public-transactions-table td {
            display: block;
            width: 100%;
        }
        .public-transactions-table tr {
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            margin-bottom: 0.8rem;
            overflow: hidden;
            background: #fff;
        }
        .public-transactions-table td {
            border: 0;
            border-bottom: 1px dashed #e5e7eb;
            padding: 0.7rem 0.8rem;
        }
        .public-transactions-table td:last-child {
            border-bottom: 0;
        }
        .public-transactions-table td::before {
            content: attr(data-label);
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $formatInr = static fn ($paise) => number_format(((int) ($paise ?? 0)) / 100, 2);
    $priceTolerancePaise = 200;
    $gstIncludedPricePaiseOptions = [49900, 76600, 47100, 58900];
    $gstIncludedBaseOverridesPaise = [
        47100 => 39900,
        58900 => 49900,
    ];
    $gstNotAddedPricePaiseOptions = [64800, 37100, 66600, 48900, 65000, 50000, 40000, 39900, 60000];
    $resolvePriceMatch = static function ($amountPaise) use ($gstIncludedPricePaiseOptions, $gstIncludedBaseOverridesPaise, $gstNotAddedPricePaiseOptions, $priceTolerancePaise) {
        $amountPaise = (int) ($amountPaise ?? 0);
        $bestMatch = null;

        foreach ([
            'included' => $gstIncludedPricePaiseOptions,
            'not_added' => $gstNotAddedPricePaiseOptions,
        ] as $gstMode => $expectedPricePaiseOptions) {
            foreach ($expectedPricePaiseOptions as $expectedPricePaise) {
                $deltaPaise = abs($amountPaise - $expectedPricePaise);

                if ($deltaPaise > $priceTolerancePaise) {
                    continue;
                }

                if ($bestMatch === null || $deltaPaise < $bestMatch['absolute_delta_paise']) {
                    $bestMatch = [
                        'expected_paise' => $expectedPricePaise,
                        'gst_mode' => $gstMode,
                        'base_override_paise' => $gstMode === 'included'
                            ? ($gstIncludedBaseOverridesPaise[$expectedPricePaise] ?? null)
                            : null,
                        'absolute_delta_paise' => $deltaPaise,
                    ];
                }
            }
        }

        return $bestMatch;
    };
    $inclusiveGstPaiseFor = static fn ($amountPaise) => (int) round(((int) $amountPaise) * 18 / 118);
    $additionalGstPaiseFor = static fn ($amountPaise) => (int) round(((int) $amountPaise) * 18 / 100);
    $invoiceNumberFor = static fn ($transaction) => \App\Support\PublicCapturedTransactionInvoices::invoiceNumber($transaction);
    $defaultInvoiceMonth = now('Asia/Kolkata')->format('Y-m');
    $showInvoiceControls = $showInvoiceControls ?? true;
@endphp

<section class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">{{ $reportIntroTitle ?? 'Captured transactions above ₹1' }}</h2>
        <p class="text-muted mb-0">
            {!! $reportDescriptionHtml ?? 'Public report for <code>transactions.status = captured</code>, <code>amount_paise != 100</code>, and users that still exist in <code>users</code>. GST-included rows are marked green. Rows where GST was not added are marked red.' !!}
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge text-bg-warning">Public view</span>
        <span class="badge text-bg-dark">Live data</span>
    </div>
</section>

@if (!$hasRequiredTables || !$hasRequiredColumns)
    <div class="alert alert-danger">
        This report cannot run because the required <code>transactions</code>/<code>users</code> tables or transaction columns are missing.
    </div>
@else
    <section class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-chip h-100">
                <span>Transactions</span>
                <strong>{{ number_format($metrics['total_transactions']) }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-chip h-100">
                <span>Total captured</span>
                <strong>₹{{ $formatInr($metrics['total_amount_paise']) }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-chip h-100">
                <span>Unique users</span>
                <strong>{{ number_format($metrics['unique_users']) }}</strong>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                <div>
                    <h3 class="h6 mb-1">Transaction list</h3>
                    <p class="text-muted mb-0">Sorted newest first.</p>
                </div>
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                    @if($showInvoiceControls)
                    <div class="input-group input-group-sm" style="width: 270px;">
                        <input type="month" id="invoiceMonth" class="form-control" value="{{ $defaultInvoiceMonth }}">
                        <button type="button" id="downloadMonthlyInvoices" class="btn btn-outline-success">
                            Download month
                        </button>
                    </div>
                    <button type="button" id="downloadAllInvoices" class="btn btn-sm btn-success">
                        Download all invoices
                    </button>
                    @endif
                    @if(method_exists($transactions, 'total'))
                        <span class="badge text-bg-light">{{ number_format($transactions->total()) }} records</span>
                    @endif
                </div>
            </div>

            @if($transactions->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-receipt-cutoff display-6 d-block mb-2"></i>
                    <p class="mb-0">No matching captured transactions found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0 public-transactions-table">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Created</th>
                                <th>Amount</th>
                                <th>Expected / GST</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th>Payment Method</th>
                                <th>Diagnosis</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                @php
                                    $priceMatch = $resolvePriceMatch($transaction->amount_paise);
                                    $expectedPricePaise = $priceMatch['expected_paise'] ?? null;
                                    $gstMode = $priceMatch['gst_mode'] ?? null;
                                    $baseOverridePaise = $priceMatch['base_override_paise'] ?? null;
                                    $isExpectedPriceMatch = $priceMatch !== null;
                                    $gstPaise = null;
                                    $taxablePaise = null;
                                    $grossWithGstPaise = null;

                                    if ($gstMode === 'included') {
                                        $taxablePaise = $baseOverridePaise !== null
                                            ? (int) $baseOverridePaise
                                            : $expectedPricePaise - $inclusiveGstPaiseFor($expectedPricePaise);
                                        $gstPaise = $expectedPricePaise - $taxablePaise;
                                        $grossWithGstPaise = $expectedPricePaise;
                                    } elseif ($gstMode === 'not_added') {
                                        $taxablePaise = $expectedPricePaise;
                                        $gstPaise = $additionalGstPaiseFor($expectedPricePaise);
                                        $grossWithGstPaise = $expectedPricePaise + $gstPaise;
                                    }

                                    $deltaPaise = $isExpectedPriceMatch ? (int) $transaction->amount_paise - $expectedPricePaise : null;
                                    $rowClass = match ($gstMode) {
                                        'included' => 'gst-included-row',
                                        'not_added' => 'gst-not-added-row',
                                        default => '',
                                    };
                                    $diagnosisColumn = \Illuminate\Support\Facades\Schema::hasTable('prescriptions') && \Illuminate\Support\Facades\Schema::hasColumn('prescriptions', 'diagnosis')
                                        ? 'diagnosis'
                                        : (\Illuminate\Support\Facades\Schema::hasTable('prescriptions') && \Illuminate\Support\Facades\Schema::hasColumn('prescriptions', 'diagnosys') ? 'diagnosys' : null);
                                    $relatedPrescriptions = $transaction->relationLoaded('prescriptions') ? $transaction->prescriptions : collect();
                                    $prescriptionDiagnoses = $diagnosisColumn
                                        ? $relatedPrescriptions
                                            ->map(fn ($prescription) => trim((string) ($prescription->{$diagnosisColumn} ?? '')))
                                            ->filter()
                                            ->values()
                                        : collect();
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td data-label="ID">
                                        <div>#{{ $transaction->id }}</div>
                                        @if($isExpectedPriceMatch)
                                            <div class="small text-muted">Invoice: {{ $invoiceNumberFor($transaction) }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Created">
                                        {{ optional($transaction->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? 'N/A' }}
                                    </td>
                                    <td data-label="Amount" class="fw-semibold">₹{{ $formatInr($transaction->amount_paise) }}</td>
                                    <td data-label="Expected / GST">
                                        @if($isExpectedPriceMatch)
                                            <div class="price-breakdown">
                                                <span class="badge {{ $gstMode === 'included' ? 'text-bg-success' : 'text-bg-danger' }} mb-1">Matched ₹{{ $formatInr($expectedPricePaise) }}</span>
                                                <div class="small fw-semibold">
                                                    {{ $gstMode === 'included' ? 'GST included' : 'GST not added' }}
                                                </div>
                                                <div class="small text-muted">Tolerance: ±₹2</div>
                                                @if($gstMode === 'included')
                                                    <div class="small">Base: ₹{{ $formatInr($taxablePaise) }}</div>
                                                    <div class="small">GST @ 18%: ₹{{ $formatInr($gstPaise) }}</div>
                                                    <div class="small">Amount with GST: ₹{{ $formatInr($grossWithGstPaise) }}</div>
                                                @endif
                                                <div class="small">Delta: {{ $deltaPaise >= 0 ? '+' : '-' }}₹{{ $formatInr(abs($deltaPaise)) }}</div>
                                            </div>
                                        @else
                                            <span class="text-muted">No configured price match</span>
                                        @endif
                                    </td>
                                    <td data-label="User">
                                        <div class="fw-semibold">
                                            {{ $transaction->user->name ?? 'User #' . $transaction->user_id }}
                                        </div>
                                        <div class="text-muted small">
                                            ID: {{ $transaction->user_id }}
                                            @if(!empty($transaction->user->phone))
                                                <br>Phone: {{ $transaction->user->phone }}
                                            @endif
                                            @if(!empty($transaction->user->email))
                                                <br>Email: {{ $transaction->user->email }}
                                            @endif
                                        </div>
                                    </td>
                                    <td data-label="Type">{{ $transaction->type ?: 'N/A' }}</td>
                                    <td data-label="Reference">{{ $transaction->reference ?: 'N/A' }}</td>
                                    <td data-label="Payment Method">{{ $transaction->payment_method ?: 'N/A' }}</td>
                                    <td data-label="Diagnosis" class="diagnosis-cell">
                                        @if($prescriptionDiagnoses->isNotEmpty())
                                            <div class="diagnosis-text small">
                                                @foreach($prescriptionDiagnoses as $diagnosis)
                                                    <div>{{ $diagnosis }}</div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-muted small">No related prescription diagnosis</div>
                                        @endif
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary mt-2 diagnosis-comparison-btn"
                                            data-url="{{ route('captured-transactions.diagnosis-comparison', $transaction) }}"
                                            data-transaction-id="{{ $transaction->id }}"
                                        >
                                            See diagnosys comparison
                                        </button>
                                    </td>
                                    <td data-label="Invoice">
                                        @if($isExpectedPriceMatch)
                                            <div class="d-flex flex-column gap-1">
                                                <a
                                                    href="{{ route('captured-transactions.invoice', $transaction) }}"
                                                    class="btn btn-sm btn-outline-dark"
                                                    target="_blank"
                                                    rel="noopener"
                                                >
                                                    Preview
                                                </a>
                                                <a
                                                    href="{{ route('captured-transactions.invoice', ['transaction' => $transaction->id, 'download' => 1]) }}"
                                                    class="btn btn-sm btn-dark invoice-download-link"
                                                >
                                                    Download
                                                </a>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                <div class="mt-3">
                    {{ $transactions->links() }}
                </div>
                @endif
            @endif
        </div>
    </section>
@endif

<div class="modal fade" id="diagnosisComparisonModal" tabindex="-1" aria-labelledby="diagnosisComparisonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="diagnosisComparisonModalLabel">Diagnosis comparison</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="diagnosisComparisonBody">
                <div class="text-muted">Loading...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const button = document.getElementById('downloadAllInvoices');
    const monthButton = document.getElementById('downloadMonthlyInvoices');
    const monthInput = document.getElementById('invoiceMonth');
    const delayMs = 900;

    const triggerDownloads = (links, activeButton, originalLabel) => {
        if (links.length === 0) {
            activeButton.textContent = 'No invoices found';
            setTimeout(() => {
                activeButton.disabled = false;
                activeButton.textContent = originalLabel;
            }, 1400);
            return;
        }

        activeButton.disabled = true;

        links.forEach((href, index) => {
            setTimeout(() => {
                const anchor = document.createElement('a');
                anchor.href = href;
                anchor.download = '';
                anchor.style.display = 'none';
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();

                activeButton.textContent = `Downloading ${index + 1}/${links.length}`;

                if (index === links.length - 1) {
                    setTimeout(() => {
                        activeButton.disabled = false;
                        activeButton.textContent = originalLabel;
                    }, delayMs);
                }
            }, index * delayMs);
        });
    };

    if (button) {
        const originalLabel = button.textContent;

        button.addEventListener('click', () => {
            const links = Array.from(document.querySelectorAll('.invoice-download-link'))
                .map((link) => link.href)
                .filter(Boolean);

            triggerDownloads(links, button, originalLabel);
        });
    }

    if (monthButton && monthInput) {
        const originalMonthLabel = monthButton.textContent;

        monthButton.addEventListener('click', async () => {
            const month = monthInput.value;
            if (!month) {
                monthButton.textContent = 'Select month';
                setTimeout(() => {
                    monthButton.textContent = originalMonthLabel;
                }, 1400);
                return;
            }

            monthButton.disabled = true;
            monthButton.textContent = 'Loading...';

            try {
                const response = await fetch(`{{ route('captured-transactions.invoices.month') }}?month=${encodeURIComponent(month)}`, {
                    headers: { Accept: 'application/json' },
                });
                const payload = await response.json();
                const links = Array.isArray(payload.invoices)
                    ? payload.invoices.map((invoice) => invoice.download_url).filter(Boolean)
                    : [];

                triggerDownloads(links, monthButton, originalMonthLabel);
            } catch (error) {
                monthButton.textContent = 'Unable to load';
                setTimeout(() => {
                    monthButton.disabled = false;
                    monthButton.textContent = originalMonthLabel;
                }, 1800);
            }
        });
    }

    const comparisonModalElement = document.getElementById('diagnosisComparisonModal');
    const comparisonBody = document.getElementById('diagnosisComparisonBody');
    const comparisonModal = comparisonModalElement ? new bootstrap.Modal(comparisonModalElement) : null;
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
    const renderComparison = (payload) => {
        const comparison = payload.comparison || {};
        const pet = payload.pet || {};

        return `
            <div class="mb-3">
                <div class="small text-muted">Transaction #${escapeHtml(payload.transaction_id)} • Gemini model: ${escapeHtml(payload.model || 'N/A')}</div>
                <div class="fw-semibold">${escapeHtml(pet.name || `Pet #${pet.id || ''}`)}</div>
                <div class="small text-muted">${escapeHtml([pet.type, pet.breed, pet.age, pet.gender].filter(Boolean).join(' • ') || 'Pet details unavailable')}</div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="small text-muted mb-1">Prescription diagnosis</div>
                        ${renderList(payload.prescription_diagnoses)}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="small text-muted mb-1">AI diagnosis</div>
                        <div class="fw-semibold">${escapeHtml(comparison.ai_diagnosis || 'N/A')}</div>
                        <div class="small text-muted mt-1">Confidence: ${escapeHtml(comparison.confidence || 'N/A')}</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border rounded p-3">
                        <div class="small text-muted mb-1">Reported symptom</div>
                        <div>${escapeHtml(payload.reported_symptom || 'N/A')}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Match status</div>
                        <span class="badge text-bg-dark">${escapeHtml(comparison.match_status || 'N/A')}</span>
                        <p class="mb-0 mt-2">${escapeHtml(comparison.comparison_summary || 'N/A')}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Basis</div>
                        ${renderList(comparison.basis)}
                    </div>
                </div>
                <div class="col-12">
                    <div class="alert alert-warning mb-0">
                        ${escapeHtml(comparison.recommended_review || 'Use as internal comparison only. A veterinarian should review any mismatch.')}
                    </div>
                </div>
            </div>
        `;
    };

    document.querySelectorAll('.diagnosis-comparison-btn').forEach((diagnosisButton) => {
        diagnosisButton.addEventListener('click', async () => {
            if (!comparisonModal || !comparisonBody) return;

            comparisonBody.innerHTML = '<div class="text-muted">Loading diagnosis comparison...</div>';
            comparisonModal.show();

            try {
                const response = await fetch(diagnosisButton.dataset.url, {
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
                    comparisonBody.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(payload.message || 'Unable to generate diagnosis comparison.')}</div>`;
                    return;
                }

                comparisonBody.innerHTML = renderComparison(payload);
            } catch (error) {
                comparisonBody.innerHTML = '<div class="alert alert-danger mb-0">Unable to generate diagnosis comparison.</div>';
            }
        });
    });
})();
</script>
@endpush
