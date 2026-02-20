@extends('layouts.admin-panel')

@section('page-title', 'Excel Export Transactions')

@push('styles')
<style>
    .excel-export-table td,
    .excel-export-table th {
        vertical-align: top;
    }
    .excel-export-table textarea {
        min-height: 76px;
        resize: vertical;
    }
    .excel-export-table .text-muted.small {
        line-height: 1.35;
    }
    @media (max-width: 991.98px) {
        .excel-export-summary {
            flex-direction: column;
            align-items: flex-start !important;
        }
        .excel-export-badges {
            width: 100%;
            flex-wrap: wrap;
        }
        .excel-export-badges .badge {
            width: auto;
            max-width: 100%;
            white-space: normal;
            text-align: left;
        }
        .excel-export-table thead {
            display: none;
        }
        .excel-export-table,
        .excel-export-table tbody,
        .excel-export-table tr,
        .excel-export-table td {
            display: block;
            width: 100%;
        }
        .excel-export-table tr {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            margin-bottom: 0.9rem;
            box-shadow: 0 8px 20px rgba(2, 6, 23, 0.06);
            overflow: hidden;
        }
        .excel-export-table td {
            border: 0;
            border-bottom: 1px dashed #e5e7eb;
            padding: 0.7rem 0.8rem;
            position: relative;
            padding-left: 42%;
            min-height: 2.65rem;
            overflow-wrap: anywhere;
        }
        .excel-export-table td:last-child {
            border-bottom: 0;
        }
        .excel-export-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 0.8rem;
            top: 0.72rem;
            width: calc(42% - 1rem);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748b;
            line-height: 1.25;
        }
        .excel-export-table td[data-label="Manual WhatsApp"] textarea {
            min-height: 90px;
        }
        .excel-export-table td[data-label="Manual WhatsApp"] .d-flex {
            justify-content: flex-start !important;
        }
        .excel-export-table td[data-label="Details"] .btn {
            width: 100%;
        }
    }
    @media (max-width: 575.98px) {
        .excel-export-table td {
            padding-left: 0.72rem;
            padding-top: 2.05rem;
        }
        .excel-export-table td::before {
            position: static;
            display: block;
            width: 100%;
            margin-bottom: 0.33rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $totalPaise = $transactions->sum('amount_paise');
    $formatInr = fn ($paise) => number_format(($paise ?? 0) / 100, 2);
    $formatPetDob = static function ($value) {
        if (empty($value)) {
            return 'N/A';
        }
        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
@endphp
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3 excel-export-summary">
                    <div>
                        <h2 class="h5 mb-1">Excel Export Campaign Transactions</h2>
                        <p class="text-muted mb-0">All payments where <code>type</code> or <code>metadata.order_type</code> equals <strong>excell_export_campaign</strong>.</p>
                    </div>
                    <div class="d-flex gap-2 excel-export-badges align-items-center">
                        <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($transactions->count()) }} records</span>
                        <span class="badge text-bg-success-subtle text-success-emphasis px-3 py-2">₹{{ $formatInr($totalPaise) }} collected</span>
                        <a href="{{ route('admin.transactions.excell-export', ['export' => 'csv']) }}" class="btn btn-sm btn-outline-dark text-nowrap">
                            Export CSV
                        </a>
                    </div>
                </div>

                @if($transactions->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-receipt-cutoff display-6 d-block mb-2"></i>
                        <p class="mb-0">No transactions found for this campaign yet.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle excel-export-table">
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
                                    <th>Details</th>
                                    <th class="text-nowrap">Manual WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $txn)
                                    @php
                                        $userPets = $txn->user?->pets ?? collect();
                                        $petFromTransaction = $txn->pet;
                                        $fallbackPetWithIssue = $userPets->first(function ($pet) {
                                            return trim((string) ($pet->reported_symptom ?? '')) !== '';
                                        });
                                        $petRecord = $petFromTransaction ?? $fallbackPetWithIssue ?? $userPets->first();
                                        $doctorName = $txn->doctor->doctor_name ?? 'Doctor';
                                        $parentName = $txn->user->name ?? 'Pet Parent';
                                        $parentPhone = $txn->user->phone ?? 'N/A';
                                        $petName = $petRecord->name ?? 'Pet';
                                        $petType = $petRecord->pet_type ?? $petRecord->type ?? $petRecord->breed ?? 'Pet';
                                        $petDob = $formatPetDob($petRecord->pet_dob ?? $petRecord->dob ?? null);
                                        $doctorMobile = $txn->doctor->doctor_mobile ?? 'N/A';
                                        $issue = trim((string) ($petFromTransaction->reported_symptom ?? ''));
                                        if ($issue === '') {
                                            $issue = trim((string) ($fallbackPetWithIssue->reported_symptom ?? $petRecord->reported_symptom ?? ''));
                                        }
                                        if ($issue === '') {
                                            $issue = 'N/A';
                                        }
                                        $responseMinutes = (int) ($txn->metadata['response_time_minutes'] ?? config('app.video_consult_response_minutes', 15));
                                        $amountInr = $formatInr($txn->amount_paise);
                                        $parentMsg = "Hi {$parentName}, your {$petType} {$petName} is booked with {$doctorName}. They'll respond within {$responseMinutes} minutes. Amount paid ₹{$amountInr}. Vet: {$doctorName}. - SnoutIQ";
                                        $vetMsg = "Hi Dr. {$doctorName}, a new consultation is assigned. Pet: {$petName} ({$petType}). Parent: {$parentName} ({$parentPhone}). Issue: {$issue}. Prescription: (add link if any). Please respond within {$responseMinutes} mins. - SnoutIQ";
                                    @endphp
                                    <tr>
                                        <td data-label="ID">#{{ $txn->id }}</td>
                                        <td class="text-nowrap" data-label="Created">{{ optional($txn->created_at)->format('d M Y, H:i') ?? '—' }}</td>
                                        <td data-label="Status">
                                            <span class="badge text-bg-light text-uppercase">{{ $txn->status ?? 'n/a' }}</span>
                                        </td>
                                        <td class="fw-semibold" data-label="Amount (₹)">₹{{ $formatInr($txn->amount_paise) }}</td>
                                        <td data-label="Clinic">
                                            {{ $txn->clinic->name ?? '—' }}
                                            <div class="text-muted small">ID: {{ $txn->clinic_id ?? '—' }}</div>
                                        </td>
                                        <td data-label="Doctor">
                                            {{ $txn->doctor->doctor_name ?? '—' }}
                                            <div class="text-muted small">
                                                @if($txn->doctor)
                                                    <div>Email: {{ $txn->doctor->doctor_email ?? '—' }}</div>
                                                    <div>Mobile: {{ $txn->doctor->doctor_mobile ?? '—' }}</div>
                                                @else
                                                    —
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="User">
                                            {{ $txn->user->name ?? '—' }}
                                            <div class="text-muted small">
                                                <div>Email: {{ $txn->user->email ?? '—' }}</div>
                                                <div>Phone: {{ $txn->user->phone ?? '—' }}</div>
                                            </div>
                                        </td>
                                        <td data-label="Pet">
                                            {{ $petRecord->name ?? '—' }}
                                            @if($petRecord)
                                                <div class="text-muted small">Breed: {{ $petRecord->breed ?? 'n/a' }}</div>
                                                <div class="text-muted small">DOB: {{ $petDob }}</div>
                                            @endif
                                        </td>
                                        <td data-label="Details">
                                            @if(($petRecord?->id || $txn->pet_id) && $txn->user_id)
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-dark"
                                                    data-action="view-details"
                                                    data-pet-id="{{ $petRecord->id ?? $txn->pet_id }}"
                                                    data-user-id="{{ $txn->user_id }}"
                                                    data-pet-name="{{ $petRecord->name ?? 'Pet' }}"
                                                    data-transaction-id="{{ $txn->id }}"
                                                    data-reported-symptom="{{ $issue }}"
                                                    data-pet-dob="{{ $petDob }}"
                                                    data-doctor-mobile="{{ $doctorMobile }}"
                                                    data-user-phone="{{ $txn->user->phone ?? 'N/A' }}"
                                                >
                                                    View Details
                                                </button>
                                            @else
                                                <span class="text-muted small">Unavailable</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap" data-label="Manual WhatsApp">
                                            <div class="mb-2">
                                                <label class="form-label mb-1 small text-muted">Parent msg</label>
                                                <textarea class="form-control form-control-sm" rows="3" readonly>{{ $parentMsg }}</textarea>
                                                <div class="d-flex justify-content-end mt-1">
                                                    <button class="btn btn-sm btn-outline-primary" data-body="{{ $parentMsg }}" onclick="copyTemplate(this)">Copy</button>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="form-label mb-1 small text-muted">Vet msg</label>
                                                <textarea class="form-control form-control-sm" rows="3" readonly>{{ $vetMsg }}</textarea>
                                                <div class="d-flex justify-content-end mt-1">
                                                    <button class="btn btn-sm btn-outline-success" data-body="{{ $vetMsg }}" onclick="copyTemplate(this)">Copy</button>
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

<div class="modal fade" id="petTimelineModal" tabindex="-1" aria-labelledby="petTimelineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="petTimelineModalLabel">Pet Timeline</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="petTimelineMeta" class="small text-muted mb-3"></div>
                <div id="petTimelineDetails" class="small mb-3"></div>
                <div id="petTimelineSymptom" class="small mb-3"></div>
                <div id="petTimelineContent" class="small text-muted">Click "View Details" to load timeline.</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const timelinePathPrefix = (() => {
        const path = window.location.pathname || '';
        if (path.startsWith('/backend/')) return '/backend';
        if (path === '/backend' || path === '/backend/') return '/backend';
        return '';
    })();
    const TIMELINE_API_URL = `${window.location.origin}${timelinePathPrefix}/api/pets/consult-timeline`;
    const timelineModalEl = document.getElementById('petTimelineModal');
    const timelineTitleEl = document.getElementById('petTimelineModalLabel');
    const timelineMetaEl = document.getElementById('petTimelineMeta');
    const timelineDetailsEl = document.getElementById('petTimelineDetails');
    const timelineSymptomEl = document.getElementById('petTimelineSymptom');
    const timelineContentEl = document.getElementById('petTimelineContent');
    const timelineModal = timelineModalEl ? new bootstrap.Modal(timelineModalEl) : null;

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value).replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[char]));
    }

    function formatTimelineDate(value) {
        if (!value) return '—';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '—';
        return date.toLocaleString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function sourceBadgeClass(source) {
        if (source === 'appointments') return 'text-bg-warning';
        if (source === 'transactions') return 'text-bg-info';
        if (source === 'prescriptions') return 'text-bg-success';
        return 'text-bg-secondary';
    }

    function sourceLabel(source) {
        if (source === 'appointments') return 'Appointment';
        if (source === 'transactions') return 'Transaction';
        if (source === 'prescriptions') return 'Prescription';
        return 'Record';
    }

    function summaryHtml(entry) {
        const record = entry?.record || {};
        if (entry?.source === 'transactions') {
            const amountPaise = Number(record.amount_paise || 0);
            const amountInr = Number.isFinite(amountPaise) ? (amountPaise / 100).toFixed(2) : '0.00';
            return `
                <div>Status: <strong>${escapeHtml(record.status || 'n/a')}</strong></div>
                <div>Type: <strong>${escapeHtml(record.type || 'n/a')}</strong></div>
                <div>Amount: <strong>₹${escapeHtml(amountInr)}</strong></div>
            `;
        }
        if (entry?.source === 'appointments') {
            return `
                <div>Status: <strong>${escapeHtml(record.status || 'n/a')}</strong></div>
                <div>Scheduled: <strong>${escapeHtml(record.schedule_time || record.scheduled_for || '—')}</strong></div>
                <div>Doctor ID: <strong>${escapeHtml(record.doctor_id || '—')}</strong></div>
            `;
        }
        if (entry?.source === 'prescriptions') {
            return `
                <div>Doctor ID: <strong>${escapeHtml(record.doctor_id || '—')}</strong></div>
                <div>Prescription ID: <strong>#${escapeHtml(record.id || entry.record_id || '—')}</strong></div>
            `;
        }
        return `<div>Record ID: <strong>#${escapeHtml(entry?.record_id || '—')}</strong></div>`;
    }

    function renderTimeline(data) {
        if (!Array.isArray(data) || data.length === 0) {
            timelineContentEl.innerHTML = '<div class="text-muted">No timeline events found for this pet/user pair.</div>';
            return;
        }

        timelineContentEl.innerHTML = `
            <div class="d-flex flex-column gap-2">
                ${data.map((entry) => `
                    <div class="border rounded p-2">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                            <span class="badge ${sourceBadgeClass(entry?.source)}">${escapeHtml(sourceLabel(entry?.source))}</span>
                            <span class="text-muted">${escapeHtml(formatTimelineDate(entry?.created_at))}</span>
                        </div>
                        ${summaryHtml(entry)}
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderReportedSymptom(symptom) {
        const text = String(symptom || '').trim();
        timelineSymptomEl.innerHTML = `
            <div class="border rounded p-2 bg-light">
                <div class="text-muted mb-1">Reported Symptom</div>
                <div class="text-dark">${escapeHtml(text || 'N/A')}</div>
            </div>
        `;
    }

    function renderPetDetails(petDob, doctorMobile, userPhone) {
        const dobText = String(petDob || '').trim() || 'N/A';
        const mobileText = String(doctorMobile || '').trim() || 'N/A';
        const userPhoneText = String(userPhone || '').trim() || 'N/A';
        timelineDetailsEl.innerHTML = `
            <div class="border rounded p-2 bg-light">
                <div class="d-flex flex-column gap-1">
                    <div><span class="text-muted">Pet DOB:</span> <strong>${escapeHtml(dobText)}</strong></div>
                    <div><span class="text-muted">Doctor Mobile:</span> <strong>${escapeHtml(mobileText)}</strong></div>
                    <div><span class="text-muted">User Phone:</span> <strong>${escapeHtml(userPhoneText)}</strong></div>
                </div>
            </div>
        `;
    }

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

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-action="view-details"]');
        if (!btn) return;

        const petId = btn.getAttribute('data-pet-id');
        const userId = btn.getAttribute('data-user-id');
        const petName = btn.getAttribute('data-pet-name') || 'Pet';
        const transactionId = btn.getAttribute('data-transaction-id') || '—';
        const reportedSymptom = btn.getAttribute('data-reported-symptom') || '';
        const petDob = btn.getAttribute('data-pet-dob') || '';
        const doctorMobile = btn.getAttribute('data-doctor-mobile') || '';
        const userPhone = btn.getAttribute('data-user-phone') || '';

        if (!petId || !userId || !timelineModal) {
            alert('Pet details unavailable for this row.');
            return;
        }

        timelineTitleEl.textContent = `${petName} Timeline`;
        timelineMetaEl.textContent = `Transaction #${transactionId} | Pet ID: ${petId} | User ID: ${userId}`;
        renderPetDetails(petDob, doctorMobile, userPhone);
        renderReportedSymptom(reportedSymptom);
        timelineContentEl.innerHTML = '<div class="text-muted">Loading timeline...</div>';
        timelineModal.show();

        btn.disabled = true;
        try {
            const query = new URLSearchParams({
                pet_id: String(petId),
                user_id: String(userId),
                transaction_scope: 'all',
                transaction_id: String(transactionId),
            });
            const response = await fetch(`${TIMELINE_API_URL}?${query.toString()}`, {
                credentials: 'include',
            });

            if (!response.ok) {
                throw new Error(`Timeline fetch failed (${response.status})`);
            }

            const payload = await response.json();
            const timeline = payload?.data?.timeline || [];
            const counts = payload?.counts || {};
            timelineMetaEl.textContent = `Transaction #${transactionId} | Pet ID: ${petId} | User ID: ${userId} | Appointments: ${counts.appointments || 0}, Transactions: ${counts.transactions || 0}, Prescriptions: ${counts.prescriptions || 0}`;
            renderTimeline(timeline);
        } catch (error) {
            timelineContentEl.innerHTML = `<div class="text-danger">Unable to load timeline. ${escapeHtml(error?.message || '')}</div>`;
        } finally {
            btn.disabled = false;
        }
    });
</script>
@endpush
