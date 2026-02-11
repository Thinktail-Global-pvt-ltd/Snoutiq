@extends('layouts.admin-panel')

@section('page-title', 'Excel Export Transactions')

@section('content')
@php
    $totalPaise = $transactions->sum('amount_paise');
    $formatInr = fn ($paise) => number_format(($paise ?? 0) / 100, 2);
@endphp
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Excel Export Campaign Transactions</h2>
                        <p class="text-muted mb-0">All payments where <code>type</code> or <code>metadata.order_type</code> equals <strong>excell_export_campaign</strong>.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($transactions->count()) }} records</span>
                        <span class="badge text-bg-success-subtle text-success-emphasis px-3 py-2">₹{{ $formatInr($totalPaise) }} collected</span>
                    </div>
                </div>

                @if($transactions->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-receipt-cutoff display-6 d-block mb-2"></i>
                        <p class="mb-0">No transactions found for this campaign yet.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th class="text-nowrap">Created</th>
                                    <th>Status</th>
                                    <th class="text-nowrap">Amount (₹)</th>
                                    <th>Clinic</th>
                                    <th>Doctor</th>
                                    <th>User</th>
                                    <th>Pet</th>
                                    <th>Payment Method</th>
                                    <th>Reference</th>
                                    <th class="text-nowrap">Manual WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $txn)
                                    @php
                                        $doctorName = $txn->doctor->doctor_name ?? 'Doctor';
                                        $parentName = $txn->user->name ?? 'Pet Parent';
                                        $parentPhone = $txn->user->phone ?? 'N/A';
                                        $petName = $txn->pet->name ?? 'Pet';
                                        $petType = $txn->pet->pet_type ?? $txn->pet->type ?? $txn->pet->breed ?? 'Pet';
                                        $issue = $txn->metadata['issue'] ?? $txn->metadata['concern'] ?? $txn->pet->reported_symptom ?? 'N/A';
                                        $responseMinutes = (int) ($txn->metadata['response_time_minutes'] ?? config('app.video_consult_response_minutes', 15));
                                        $amountInr = $formatInr($txn->amount_paise);
                                        $parentMsg = "Hi {$parentName}, your {$petType} {$petName} is booked with {$doctorName}. They'll respond within {$responseMinutes} minutes. Amount paid ₹{$amountInr}. Vet: {$doctorName}. - SnoutIQ";
                                        $vetMsg = "Hi Dr. {$doctorName}, a new consultation is assigned. Pet: {$petName} ({$petType}). Parent: {$parentName} ({$parentPhone}). Issue: {$issue}. Prescription: (add link if any). Please respond within {$responseMinutes} mins. - SnoutIQ";
                                    @endphp
                                    <tr>
                                        <td>#{{ $txn->id }}</td>
                                        <td class="text-nowrap">{{ optional($txn->created_at)->format('d M Y, H:i') ?? '—' }}</td>
                                        <td>
                                            <span class="badge text-bg-light text-uppercase">{{ $txn->status ?? 'n/a' }}</span>
                                        </td>
                                        <td class="fw-semibold">₹{{ $formatInr($txn->amount_paise) }}</td>
                                        <td>
                                            {{ $txn->clinic->name ?? '—' }}
                                            <div class="text-muted small">ID: {{ $txn->clinic_id ?? '—' }}</div>
                                        </td>
                                        <td>
                                            {{ $txn->doctor->doctor_name ?? '—' }}
                                            <div class="text-muted small">
                                                @if($txn->doctor)
                                                    {{ $txn->doctor->doctor_email ?? $txn->doctor->doctor_mobile ?? '—' }}
                                                @else
                                                    —
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            {{ $txn->user->name ?? '—' }}
                                            <div class="text-muted small">{{ $txn->user->email ?? $txn->user->phone ?? '—' }}</div>
                                        </td>
                                        <td>
                                            {{ $txn->pet->name ?? '—' }}
                                            @if($txn->pet)
                                                <div class="text-muted small">Breed: {{ $txn->pet->breed ?? 'n/a' }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $txn->payment_method ?? '—' }}</td>
                                        <td class="text-break" style="min-width: 140px;">{{ $txn->reference ?? '—' }}</td>
                                        <td class="text-nowrap">
                                            <div class="mb-2">
                                                <label class="form-label mb-1 small text-muted">Parent msg</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" value="{{ $parentMsg }}" readonly>
                                                    <button class="btn btn-outline-primary" data-body="{{ $parentMsg }}" onclick="copyTemplate(this)">Copy</button>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form-label mb-1 small text-muted">Vet msg</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" value="{{ $vetMsg }}" readonly>
                                                    <button class="btn btn-outline-success" data-body="{{ $vetMsg }}" onclick="copyTemplate(this)">Copy</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
            const orig = btn.innerText;
            btn.innerText = 'Copied!';
            btn.classList.remove('btn-outline-primary','btn-outline-success');
            btn.classList.add('btn-secondary','text-white');
            setTimeout(() => {
                btn.innerText = orig;
                btn.classList.add(orig.includes('parent') ? 'btn-outline-primary' : 'btn-outline-success');
                btn.classList.remove('btn-secondary','text-white');
            }, 1200);
        }).catch(() => alert('Copy failed, please copy manually.'));
    }
</script>
@endpush
