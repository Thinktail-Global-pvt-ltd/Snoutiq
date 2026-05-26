@extends('layouts.admin-panel')

@section('page-title', 'Prescription Diagnosis Users')
@section('hide-sidebar', 'true')
@section('access-badge', 'Public view')

@push('styles')
<style>
    .diagnosis-users-table td,
    .diagnosis-users-table th {
        vertical-align: top;
    }
    .diagnosis-text {
        max-width: 520px;
        overflow-wrap: anywhere;
        white-space: pre-wrap;
    }
    .ai-diagnosis-box {
        max-width: 420px;
        min-width: 260px;
    }
    .pdf-template-preview {
        border: 1px solid #dbe3ef;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }
    .pdf-template-table th {
        color: #475569;
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .pdf-template-table td {
        vertical-align: top;
    }
    @media (max-width: 767.98px) {
        .diagnosis-users-table thead {
            display: none;
        }
        .diagnosis-users-table,
        .diagnosis-users-table tbody,
        .diagnosis-users-table tr,
        .diagnosis-users-table td {
            display: block;
            width: 100%;
        }
        .diagnosis-users-table tr {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 0.85rem;
            overflow: hidden;
            background: #fff;
        }
        .diagnosis-users-table td {
            border: 0;
            border-bottom: 1px dashed #e5e7eb;
            padding: 0.75rem 0.85rem;
        }
        .diagnosis-users-table td:last-child {
            border-bottom: 0;
        }
        .diagnosis-users-table td::before {
            content: attr(data-label);
            display: block;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
        }
    }
</style>
@endpush

@section('content')
<section class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h5 mb-1">Users with prescription diagnosis</h2>
        <p class="text-muted mb-0">
            Prescriptions where <code>prescriptions.{{ $diagnosisColumn ?? 'diagnosis' }}</code> is filled and the related <code>users</code> row still exists.
        </p>
    </div>
    <div class="d-flex flex-column flex-sm-row gap-2 align-self-start align-self-md-center">
        <a href="{{ route('prescription-diagnosis-users.pdf') }}" class="btn btn-dark btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
        <span class="badge text-bg-dark align-self-start align-self-sm-center">Live data</span>
    </div>
</section>

@if (!$hasRequiredTables || !$hasRequiredColumns)
    <div class="alert alert-danger">
        This report cannot run because the required <code>prescriptions</code>/<code>users</code> tables or diagnosis/user columns are missing.
    </div>
@else
    <section class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="stat-chip h-100">
                <span>Prescriptions</span>
                <strong>{{ number_format($metrics['prescriptions']) }}</strong>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-chip h-100">
                <span>Unique users</span>
                <strong>{{ number_format($metrics['unique_users']) }}</strong>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="card-body">
            @if(!$prescriptions->isEmpty())
                <section class="pdf-template-preview p-3 p-md-4 mb-4">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                        <div>
                            <h3 class="h6 mb-1">PDF template preview</h3>
                            <p class="text-muted mb-0">This is the layout used in the downloaded PDF: patient symptom vs doctor diagnosis vs AI diagnosis.</p>
                        </div>
                        <span class="badge text-bg-light align-self-start align-self-md-center">First page preview</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm pdf-template-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 18%;">Patient</th>
                                    <th style="width: 26%;">Actual symptom</th>
                                    <th style="width: 26%;">Doctor diagnosis</th>
                                    <th style="width: 30%;">AI diagnosis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($prescriptions->take(3) as $previewPrescription)
                                    @php
                                        $previewPet = $previewPrescription->pet;
                                        $previewUser = $previewPrescription->user;
                                        $previewDiagnosis = trim((string) ($previewPrescription->{$diagnosisColumn} ?? ''));
                                        $previewSymptom = trim((string) ($previewPet->reported_symptom ?? ''));
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $previewPet->name ?? 'Pet #' . ($previewPet->id ?? 'N/A') }}</div>
                                            <div class="small text-muted">{{ $previewUser->name ?? 'User #' . $previewPrescription->user_id }}</div>
                                        </td>
                                        <td>{{ $previewSymptom !== '' ? $previewSymptom : 'No reported symptom' }}</td>
                                        <td>{{ $previewDiagnosis }}</td>
                                        <td class="text-muted">Generated in PDF from reported symptom + pet_doc2_blob.</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                <div>
                    <h3 class="h6 mb-1">Diagnosis users</h3>
                    <p class="text-muted mb-0">Sorted by newest prescription first.</p>
                </div>
                @if(method_exists($prescriptions, 'total'))
                    <span class="badge text-bg-light align-self-start align-self-md-center">{{ number_format($prescriptions->total()) }} records</span>
                @endif
            </div>

            @if($prescriptions->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-clipboard2-pulse display-6 d-block mb-2"></i>
                    <p class="mb-0">No prescriptions with diagnosis and existing users found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0 diagnosis-users-table">
                        <thead class="table-light">
                            <tr>
                                <th>Prescription</th>
                                <th>User</th>
                                <th>Pet</th>
                                <th>Doctor</th>
                                <th>Diagnosis</th>
                                <th>Reported Symptom</th>
                                <th>AI Diagnosis</th>
                                <th>Call Session</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prescriptions as $prescription)
                                @php
                                    $diagnosis = trim((string) ($prescription->{$diagnosisColumn} ?? ''));
                                    $pet = $prescription->pet;
                                    $doctor = $prescription->doctor;
                                    $user = $prescription->user;
                                    $reportedSymptom = trim((string) ($pet->reported_symptom ?? ''));
                                @endphp
                                <tr>
                                    <td data-label="Prescription">
                                        <div class="fw-semibold">#{{ $prescription->id }}</div>
                                        <div class="small text-muted">
                                            {{ optional($prescription->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? 'Date unavailable' }}
                                        </div>
                                    </td>
                                    <td data-label="User">
                                        <div class="fw-semibold">{{ $user->name ?? 'User #' . $prescription->user_id }}</div>
                                        <div class="small text-muted">
                                            ID: {{ $prescription->user_id }}
                                            @if(!empty($user?->phone))
                                                <br>Phone: {{ $user->phone }}
                                            @endif
                                            @if(!empty($user?->email))
                                                <br>Email: {{ $user->email }}
                                            @endif
                                            @if(!empty($user?->city))
                                                <br>City: {{ $user->city }}
                                            @endif
                                        </div>
                                    </td>
                                    <td data-label="Pet">
                                        @if($pet)
                                            <div class="fw-semibold">{{ $pet->name ?? 'Pet #' . $pet->id }}</div>
                                            <div class="small text-muted">
                                                {{ collect([$pet->pet_type ?? $pet->type ?? null, $pet->breed ?? null, $pet->pet_age ? $pet->pet_age . ' yrs' : null, $pet->pet_gender ?? null])->filter()->join(' · ') ?: 'Details unavailable' }}
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td data-label="Doctor">
                                        @if($doctor)
                                            <div class="fw-semibold">{{ $doctor->doctor_name ?? 'Doctor #' . $doctor->id }}</div>
                                            <div class="small text-muted">
                                                {{ collect([$doctor->degree ?? null, $doctor->doctor_mobile ?? null, $doctor->doctor_email ?? null])->filter()->join(' · ') ?: 'Details unavailable' }}
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td data-label="Diagnosis">
                                        <div class="diagnosis-text fw-semibold">{{ $diagnosis }}</div>
                                        @if(!empty($prescription->disease_name))
                                            <div class="small text-muted mt-1">Disease: {{ $prescription->disease_name }}</div>
                                        @endif
                                        @if(!empty($prescription->diagnosis_status))
                                            <div class="small text-muted">Status: {{ $prescription->diagnosis_status }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Reported Symptom">
                                        @if($reportedSymptom !== '')
                                            <div class="diagnosis-text">{{ $reportedSymptom }}</div>
                                        @else
                                            <span class="text-muted">No reported symptom</span>
                                        @endif
                                    </td>
                                    <td data-label="AI Diagnosis" class="ai-diagnosis-box">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary ai-diagnosis-btn"
                                            data-url="{{ route('prescription-diagnosis-users.ai-analysis', $prescription) }}"
                                        >
                                            Generate AI diagnosis
                                        </button>
                                        <div class="ai-diagnosis-result small mt-2 text-muted"></div>
                                    </td>
                                    <td data-label="Call Session">
                                        {{ $prescription->call_session ?: 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($prescriptions->hasPages())
                    <div class="mt-3">
                        {{ $prescriptions->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>
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
    document.querySelectorAll('.ai-diagnosis-btn').forEach((button) => {
        button.addEventListener('click', async () => {
            const result = button.parentElement.querySelector('.ai-diagnosis-result');
            button.disabled = true;
            button.textContent = 'Diagnosing...';
            result.className = 'ai-diagnosis-result small mt-2 text-muted';
            result.textContent = 'Loading Gemini diagnosis...';

            try {
                const response = await fetch(button.dataset.url, {
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
                    result.className = 'ai-diagnosis-result small mt-2 text-danger';
                    result.textContent = payload.message || 'Unable to generate AI diagnosis.';
                    button.disabled = false;
                    button.textContent = 'Generate AI diagnosis';
                    return;
                }

                const data = payload.data || {};
                result.className = 'ai-diagnosis-result small mt-2';
                result.innerHTML = `
                    <div class="fw-semibold">${escapeHtml(data.ai_diagnosis || 'AI diagnosis generated.')}</div>
                    <div class="text-muted mt-1">Source: reported symptom + pet_doc2_blob</div>
                `;
                button.textContent = 'Regenerate AI diagnosis';
                button.disabled = false;
            } catch (error) {
                result.className = 'ai-diagnosis-result small mt-2 text-danger';
                result.textContent = 'Unable to generate AI diagnosis.';
                button.disabled = false;
                button.textContent = 'Generate AI diagnosis';
            }
        });
    });
})();
</script>
@endpush
