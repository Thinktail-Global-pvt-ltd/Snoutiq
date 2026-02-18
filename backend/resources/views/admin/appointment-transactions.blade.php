@extends('layouts.admin-panel')

@section('page-title', 'Appointment Transactions')

@push('styles')
<style>
    .appointment-table td,
    .appointment-table th {
        vertical-align: top;
    }
    .doctor-update-form .form-select {
        min-width: 260px;
    }
    .transfer-badge {
        font-weight: 700;
        padding: 0.08rem 0.4rem;
        border-radius: 0.3rem;
        display: inline-block;
    }
    .transfer-badge.is-transferred {
        color: #b91c1c;
        background: #fee2e2;
        border: 1px solid #fecaca;
    }
    .transfer-badge.not-transferred {
        color: #64748b;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }
    @media (max-width: 991.98px) {
        .appointment-table thead {
            display: none;
        }
        .appointment-table,
        .appointment-table tbody,
        .appointment-table tr,
        .appointment-table td {
            display: block;
            width: 100%;
        }
        .appointment-table tr {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.85rem;
            margin-bottom: 0.9rem;
            box-shadow: 0 8px 20px rgba(2, 6, 23, 0.06);
            overflow: hidden;
        }
        .appointment-table td {
            border: 0;
            border-bottom: 1px dashed #e5e7eb;
            padding: 0.7rem 0.8rem;
            position: relative;
            padding-left: 42%;
            min-height: 2.65rem;
            overflow-wrap: anywhere;
        }
        .appointment-table td:last-child {
            border-bottom: 0;
        }
        .appointment-table td::before {
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
        .doctor-update-form .form-select {
            min-width: 100%;
        }
    }
    @media (max-width: 575.98px) {
        .appointment-table td {
            padding-left: 0.72rem;
            padding-top: 2.05rem;
        }
        .appointment-table td::before {
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
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Video Appointment Transactions</h2>
                        <p class="text-muted mb-0">Appointment payments from <code>transactions</code> where <code>type</code> or <code>metadata.order_type</code> is <strong>video_consult</strong> or <strong>excell_export_campaign</strong>.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($transactions->count()) }} records</span>
                        <span class="badge text-bg-success-subtle text-success-emphasis px-3 py-2">₹{{ $formatInr($totalPaise) }} total</span>
                    </div>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($transactions->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-calendar2-x display-6 d-block mb-2"></i>
                        <p class="mb-0">No appointment transactions found.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle appointment-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th class="text-nowrap">Created</th>
                                    <th>Status</th>
                                    <th class="text-nowrap">Amount (₹)</th>
                                    <th>Clinic</th>
                                    <th>Current Doctor</th>
                                    <th>User</th>
                                    <th>Pet</th>
                                    <th class="text-nowrap">Change Doctor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $txn)
                                    @php
                                        $userPets = $txn->user?->pets ?? collect();
                                        $petFromTransaction = $txn->pet;
                                        $petRecord = $petFromTransaction ?? $userPets->first();
                                        $petDob = $formatPetDob($petRecord->pet_dob ?? $petRecord->dob ?? null);
                                        $doctorOptions = $allDoctors;
                                        $currentDoctorId = (int) ($txn->doctor_id ?? 0);
                                        $transactionType = $txn->type ?? data_get($txn->metadata, 'order_type', 'n/a');
                                        $metadata = is_array($txn->metadata) ? $txn->metadata : [];
                                        $latestDbLog = $latestAssignmentLogs->get($txn->id);
                                        $lastAssignment = data_get($metadata, 'last_doctor_assignment');
                                        if (!is_array($lastAssignment) && $latestDbLog) {
                                            $lastAssignment = [
                                                'previous_doctor_id' => $latestDbLog->previous_doctor_id ?? null,
                                                'new_doctor_id' => $latestDbLog->new_doctor_id ?? null,
                                                'changed_at' => $latestDbLog->created_at ?? null,
                                            ];
                                        }
                                        $assignmentHistory = data_get($metadata, 'doctor_assignment_logs', []);
                                        if (!is_array($assignmentHistory)) {
                                            $assignmentHistory = [];
                                        }
                                        $hasTransfer = (is_array($lastAssignment) && !empty($lastAssignment['previous_doctor_id']))
                                            || !empty($assignmentHistory)
                                            || (bool) $latestDbLog;
                                        $lastDoctorId = is_array($lastAssignment)
                                            ? ($lastAssignment['previous_doctor_id'] ?? null)
                                            : null;
                                        if ((!$lastDoctorId || !is_numeric($lastDoctorId)) && !empty($assignmentHistory)) {
                                            $lastHistoryEntry = collect($assignmentHistory)->last();
                                            if (is_array($lastHistoryEntry) && !empty($lastHistoryEntry['previous_doctor_id'])) {
                                                $lastDoctorId = $lastHistoryEntry['previous_doctor_id'];
                                            }
                                        }
                                        if ((!$lastDoctorId || !is_numeric($lastDoctorId)) && $latestDbLog && !empty($latestDbLog->previous_doctor_id)) {
                                            $lastDoctorId = $latestDbLog->previous_doctor_id;
                                        }
                                        $lastDoctorId = (is_numeric($lastDoctorId) && (int) $lastDoctorId > 0) ? (int) $lastDoctorId : null;
                                        $lastDoctorName = $lastDoctorId ? ($doctorNameLookup[$lastDoctorId] ?? null) : null;
                                    @endphp
                                    <tr>
                                        <td data-label="ID">#{{ $txn->id }}</td>
                                        <td class="text-nowrap" data-label="Created">{{ optional($txn->created_at)->format('d M Y, H:i') ?? '—' }}</td>
                                        <td data-label="Status">
                                            <span class="badge text-bg-light text-uppercase">{{ $txn->status ?? 'n/a' }}</span>
                                            <div class="small text-muted mt-1">Type: {{ $transactionType }}</div>
                                        </td>
                                        <td class="fw-semibold" data-label="Amount (₹)">₹{{ $formatInr($txn->amount_paise) }}</td>
                                        <td data-label="Clinic">
                                            {{ $txn->clinic->name ?? '—' }}
                                            <div class="text-muted small">ID: {{ $txn->clinic_id ?? '—' }}</div>
                                        </td>
                                        <td data-label="Current Doctor">
                                            {{ $txn->doctor->doctor_name ?? '—' }}
                                            <div class="text-muted small">
                                                <div>ID: {{ $txn->doctor_id ?? '—' }}</div>
                                                <div>Phone: {{ $txn->doctor->doctor_mobile ?? '—' }}</div>
                                                <div>Last Doctor (Before Transfer):
                                                    @if($lastDoctorId)
                                                        {{ $lastDoctorName ?? 'Doctor' }} (ID: {{ $lastDoctorId }})
                                                    @else
                                                        N/A
                                                    @endif
                                                </div>
                                                <div>Transfer Available:
                                                    <span class="transfer-badge {{ $hasTransfer ? 'is-transferred' : 'not-transferred' }}">
                                                        {{ $hasTransfer ? 'Yes' : 'No' }}
                                                    </span>
                                                </div>
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
                                        <td class="text-nowrap" data-label="Change Doctor">
                                            <form action="{{ route('admin.transactions.appointments.doctor', $txn) }}" method="POST" class="doctor-update-form d-flex flex-column gap-2">
                                                @csrf
                                                <select name="doctor_id" class="form-select form-select-sm" required>
                                                    <option value="">Select doctor</option>
                                                    @forelse($doctorOptions as $doctor)
                                                        <option value="{{ $doctor->id }}" @selected($currentDoctorId === (int) $doctor->id)>
                                                            {{ $doctor->doctor_name ?? 'Unnamed Doctor' }} (ID: {{ $doctor->id }})
                                                        </option>
                                                    @empty
                                                        <option value="" disabled>No doctors available</option>
                                                    @endforelse
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary" @disabled($doctorOptions->isEmpty())>
                                                    Update Doctor
                                                </button>
                                            </form>
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
