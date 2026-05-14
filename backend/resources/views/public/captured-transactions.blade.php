@extends('layouts.admin-panel')

@section('page-title', 'Captured Transactions')
@section('hide-sidebar', 'true')
@section('access-badge', 'Public view')

@push('styles')
<style>
    .public-transactions-table td,
    .public-transactions-table th {
        vertical-align: top;
    }
    .public-transactions-table tr.price-match-row > * {
        background-color: #dcfce7;
    }
    .price-breakdown {
        line-height: 1.35;
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
    $expectedPricePaiseOptions = [49900, 39900];
    $resolveExpectedPricePaise = static function ($amountPaise) use ($expectedPricePaiseOptions, $priceTolerancePaise) {
        $amountPaise = (int) ($amountPaise ?? 0);

        foreach ($expectedPricePaiseOptions as $expectedPricePaise) {
            if (abs($amountPaise - $expectedPricePaise) <= $priceTolerancePaise) {
                return $expectedPricePaise;
            }
        }

        return null;
    };
    $gstPaiseFor = static fn ($amountPaise) => (int) round(((int) $amountPaise) * 18 / 118);
@endphp

<section class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">Captured transactions above ₹1</h2>
        <p class="text-muted mb-0">
            Public report for <code>transactions.status = captured</code>, <code>amount_paise != 100</code>, and users that still exist in <code>users</code>.
            Rows within ₹2 of ₹499 or ₹399 are marked green, with GST calculated at 18%.
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
                @if(method_exists($transactions, 'total'))
                    <span class="badge text-bg-light">{{ number_format($transactions->total()) }} records</span>
                @endif
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
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                @php
                                    $expectedPricePaise = $resolveExpectedPricePaise($transaction->amount_paise);
                                    $isExpectedPriceMatch = $expectedPricePaise !== null;
                                    $gstPaise = $isExpectedPriceMatch ? $gstPaiseFor($expectedPricePaise) : null;
                                    $taxablePaise = $isExpectedPriceMatch ? $expectedPricePaise - $gstPaise : null;
                                    $deltaPaise = $isExpectedPriceMatch ? (int) $transaction->amount_paise - $expectedPricePaise : null;
                                @endphp
                                <tr class="{{ $isExpectedPriceMatch ? 'price-match-row' : '' }}">
                                    <td data-label="ID">#{{ $transaction->id }}</td>
                                    <td data-label="Created">
                                        {{ optional($transaction->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? 'N/A' }}
                                    </td>
                                    <td data-label="Amount" class="fw-semibold">₹{{ $formatInr($transaction->amount_paise) }}</td>
                                    <td data-label="Expected / GST">
                                        @if($isExpectedPriceMatch)
                                            <div class="price-breakdown">
                                                <span class="badge text-bg-success mb-1">Matched ₹{{ $formatInr($expectedPricePaise) }}</span>
                                                <div class="small text-muted">Tolerance: ±₹2</div>
                                                <div class="small">Base: ₹{{ $formatInr($taxablePaise) }}</div>
                                                <div class="small">GST @ 18%: ₹{{ $formatInr($gstPaise) }}</div>
                                                <div class="small">Delta: {{ $deltaPaise >= 0 ? '+' : '-' }}₹{{ $formatInr(abs($deltaPaise)) }}</div>
                                            </div>
                                        @else
                                            <span class="text-muted">No ₹399/₹499 match</span>
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </section>
@endif
@endsection
