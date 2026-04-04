@extends('layouts.admin-panel')

@section('page-title', 'Users Data Hub')

@push('styles')
<style>
    .hub-table td,
    .hub-table th {
        vertical-align: top;
    }
    .hub-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.1rem;
        height: 1.55rem;
        font-size: 0.73rem;
        border-radius: 999px;
        padding: 0 0.5rem;
        font-weight: 700;
        background: #eef2ff;
        color: #3730a3;
    }
    .hub-preview-line {
        margin-top: 0.4rem;
        font-size: 0.78rem;
        color: #475569;
        line-height: 1.3;
    }
    .hub-section-title {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #334155;
    }
    .hub-mini-card {
        border: 1px solid #e2e8f0;
        border-radius: 0.8rem;
        background: #fff;
        padding: 0.85rem;
        height: 100%;
    }
    .hub-muted {
        font-size: 0.84rem;
        color: #64748b;
        margin-bottom: 0;
    }
    .hub-detail-row td {
        background: #f8fafc;
    }
    .hub-sticky-top {
        position: sticky;
        top: 84px;
        z-index: 2;
        background: #f8fafc;
    }
    .hub-section-table th {
        font-size: 0.72rem;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }
    .hub-section-table td {
        font-size: 0.8rem;
        color: #1e293b;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
@php
    $formatDateTime = static function ($value): string {
        if (empty($value)) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y, H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $formatInrFromPaise = static function ($paise): string {
        if (!is_numeric($paise)) {
            return '—';
        }

        return '₹' . number_format(((int) $paise) / 100, 2);
    };

    $currentPerPage = (int) request()->query('per_page', $users->perPage());
    $searchQuery = trim((string) request()->query('q', ''));
    $previewLimit = 2;
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">All Users With Related Data</h2>
                        <p class="text-muted mb-0">Preview + full details for pets, transactions, prescriptions, appointments, and video consult records.</p>
                    </div>

                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-12 col-lg-7">
                            <label for="q" class="form-label mb-1 text-muted small">Search users</label>
                            <input
                                id="q"
                                name="q"
                                type="text"
                                class="form-control form-control-sm"
                                value="{{ $searchQuery }}"
                                placeholder="Search by name, email, phone"
                            >
                        </div>
                        <div class="col-6 col-lg-2">
                            <label for="per_page" class="form-label mb-1 text-muted small">Per page</label>
                            <select id="per_page" name="per_page" class="form-select form-select-sm">
                                @foreach([10, 20, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected($currentPerPage === $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-lg-3 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Apply</button>
                            @if($searchQuery !== '')
                                <a href="{{ route('admin.users.data-hub', ['per_page' => $currentPerPage]) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                            @endif
                        </div>
                    </form>
                </div>

                @if($users->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-database-x d-block fs-2 mb-2"></i>
                        <p class="mb-0">No users found.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table hub-table align-middle">
                            <thead class="table-light hub-sticky-top">
                                <tr>
                                    <th>User</th>
                                    <th>Pets</th>
                                    <th>Transactions</th>
                                    <th>Prescriptions</th>
                                    <th>Appointments</th>
                                    <th>Video Consults</th>
                                    <th class="text-end">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    @php
                                        $userId = (int) $user->id;
                                        $pets = $petsByUser->get($userId, collect());
                                        $transactions = $transactionsByUser->get($userId, collect());
                                        $prescriptions = $prescriptionsByUser->get($userId, collect());
                                        $appointments = $appointmentsByUser->get($userId, collect());
                                        $videoConsults = $videoConsultsByUser->get($userId, collect());

                                        $latestPet = $pets->first();
                                        $latestTransaction = $transactions->first();
                                        $latestPrescription = $prescriptions->first();
                                        $latestAppointment = $appointments->first();
                                        $latestVideo = $videoConsults->first();

                                        $collapseId = 'user-data-'.$userId;
                                        $petsMoreId = 'pets-more-'.$userId;
                                        $transactionsMoreId = 'transactions-more-'.$userId;
                                        $prescriptionsMoreId = 'prescriptions-more-'.$userId;
                                        $appointmentsMoreId = 'appointments-more-'.$userId;
                                        $videosMoreId = 'videos-more-'.$userId;
                                    @endphp

                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name ?: 'Unnamed User' }}</div>
                                            <div class="small text-muted">ID: {{ $user->id }} · {{ $user->email ?: 'no-email' }}</div>
                                            <div class="small text-muted">{{ $user->phone ?: 'no-phone' }} · {{ str_replace('_', ' ', $user->role ?: 'n/a') }}</div>
                                            <div class="small text-muted">Joined {{ $formatDateTime($user->created_at) }}</div>
                                        </td>

                                        <td>
                                            <span class="hub-count">{{ $supports['pets'] ? $pets->count() : 'N/A' }}</span>
                                            <div class="hub-preview-line">
                                                @if(!$supports['pets'])
                                                    pets table missing
                                                @elseif($latestPet)
                                                    {{ \Illuminate\Support\Str::limit(($latestPet->name ?: 'Unnamed Pet') . ' · ' . ($latestPet->breed ?: ($latestPet->pet_type ?? $latestPet->type ?? 'n/a')), 46) }}
                                                @else
                                                    —
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            <span class="hub-count">{{ $supports['transactions'] ? $transactions->count() : 'N/A' }}</span>
                                            <div class="hub-preview-line">
                                                @if(!$supports['transactions'])
                                                    transactions table missing
                                                @elseif($latestTransaction)
                                                    @php
                                                        $latestTxType = $latestTransaction->type ?: data_get($latestTransaction->metadata, 'order_type', 'n/a');
                                                    @endphp
                                                    {{ strtoupper($latestTransaction->status ?: 'n/a') }} · {{ $formatInrFromPaise($latestTransaction->amount_paise) }} · {{ $latestTxType }}
                                                @else
                                                    —
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            <span class="hub-count">{{ $supports['prescriptions'] ? $prescriptions->count() : 'N/A' }}</span>
                                            <div class="hub-preview-line">
                                                @if(!$supports['prescriptions'])
                                                    prescriptions table missing
                                                @elseif($latestPrescription)
                                                    {{ \Illuminate\Support\Str::limit($latestPrescription->diagnosis ?: 'No diagnosis', 46) }}
                                                @else
                                                    —
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            <span class="hub-count">{{ $supports['appointments'] ? $appointments->count() : 'N/A' }}</span>
                                            <div class="hub-preview-line">
                                                @if(!$supports['appointments'])
                                                    appointments table missing
                                                @elseif($latestAppointment)
                                                    {{ ($latestAppointment->appointment_date ?: 'no-date') . ' ' . ($latestAppointment->appointment_time ?: '') }} · {{ strtoupper($latestAppointment->status ?: 'n/a') }}
                                                @else
                                                    —
                                                @endif
                                            </div>
                                        </td>

                                        <td>
                                            <span class="hub-count">{{ $supports['video_apointment'] ? $videoConsults->count() : 'N/A' }}</span>
                                            <div class="hub-preview-line">
                                                @if(!$supports['video_apointment'])
                                                    video_apointment table missing
                                                @elseif($latestVideo)
                                                    order {{ $latestVideo->order_id ?: 'n/a' }} · {{ $latestVideo->is_completed ? 'COMPLETED' : 'PENDING' }}
                                                @else
                                                    —
                                                @endif
                                            </div>
                                        </td>

                                        <td class="text-end">
                                            <button
                                                class="btn btn-sm btn-outline-primary"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false"
                                                aria-controls="{{ $collapseId }}"
                                            >
                                                View
                                            </button>
                                        </td>
                                    </tr>

                                    <tr class="hub-detail-row collapse" id="{{ $collapseId }}">
                                        <td colspan="7">
                                            <div class="row g-3">
                                                <div class="col-12 col-xl-6">
                                                    <div class="hub-mini-card">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div class="hub-section-title mb-0">Pets</div>
                                                            <span class="hub-count">{{ $supports['pets'] ? $pets->count() : 'N/A' }}</span>
                                                        </div>

                                                        @if(!$supports['pets'])
                                                            <p class="hub-muted">`pets` table not available.</p>
                                                        @elseif($pets->isEmpty())
                                                            <p class="hub-muted">No pets found for this user.</p>
                                                        @else
                                                            <div class="table-responsive">
                                                                <table class="table table-sm hub-section-table mb-2">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>ID</th>
                                                                            <th>Name</th>
                                                                            <th>Type</th>
                                                                            <th>Breed</th>
                                                                            <th>Gender</th>
                                                                            <th>Added</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($pets->take($previewLimit) as $pet)
                                                                            @php
                                                                                $petType = $pet->pet_type ?? $pet->type ?? 'n/a';
                                                                                $petGender = $pet->pet_gender ?? $pet->gender ?? 'n/a';
                                                                            @endphp
                                                                            <tr>
                                                                                <td>#{{ $pet->id }}</td>
                                                                                <td>{{ $pet->name ?: 'Unnamed' }}</td>
                                                                                <td>{{ $petType }}</td>
                                                                                <td>{{ $pet->breed ?: 'n/a' }}</td>
                                                                                <td>{{ $petGender }}</td>
                                                                                <td>{{ $formatDateTime($pet->created_at) }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            @if($pets->count() > $previewLimit)
                                                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $petsMoreId }}" aria-expanded="false" aria-controls="{{ $petsMoreId }}">View More</button>
                                                                <div class="collapse mt-2" id="{{ $petsMoreId }}">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm hub-section-table mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>ID</th>
                                                                                    <th>Name</th>
                                                                                    <th>Type</th>
                                                                                    <th>Breed</th>
                                                                                    <th>Gender</th>
                                                                                    <th>Added</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($pets as $pet)
                                                                                    @php
                                                                                        $petType = $pet->pet_type ?? $pet->type ?? 'n/a';
                                                                                        $petGender = $pet->pet_gender ?? $pet->gender ?? 'n/a';
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td>#{{ $pet->id }}</td>
                                                                                        <td>{{ $pet->name ?: 'Unnamed' }}</td>
                                                                                        <td>{{ $petType }}</td>
                                                                                        <td>{{ $pet->breed ?: 'n/a' }}</td>
                                                                                        <td>{{ $petGender }}</td>
                                                                                        <td>{{ $formatDateTime($pet->created_at) }}</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-12 col-xl-6">
                                                    <div class="hub-mini-card">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div class="hub-section-title mb-0">Transactions</div>
                                                            <span class="hub-count">{{ $supports['transactions'] ? $transactions->count() : 'N/A' }}</span>
                                                        </div>

                                                        @if(!$supports['transactions'])
                                                            <p class="hub-muted">`transactions` table not available.</p>
                                                        @elseif($transactions->isEmpty())
                                                            <p class="hub-muted">No transactions found for this user.</p>
                                                        @else
                                                            <div class="table-responsive">
                                                                <table class="table table-sm hub-section-table mb-2">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>ID</th>
                                                                            <th>Type</th>
                                                                            <th>Status</th>
                                                                            <th>Amount</th>
                                                                            <th>Pet</th>
                                                                            <th>Ref</th>
                                                                            <th>Date</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($transactions->take($previewLimit) as $transaction)
                                                                            @php
                                                                                $txType = $transaction->type ?: data_get($transaction->metadata, 'order_type', 'n/a');
                                                                                $txPetName = optional($petsById->get((int) ($transaction->pet_id ?? 0)))->name ?: 'n/a';
                                                                            @endphp
                                                                            <tr>
                                                                                <td>#{{ $transaction->id }}</td>
                                                                                <td>{{ $txType }}</td>
                                                                                <td>{{ strtoupper($transaction->status ?: 'n/a') }}</td>
                                                                                <td>{{ $formatInrFromPaise($transaction->amount_paise) }}</td>
                                                                                <td>{{ $txPetName }}</td>
                                                                                <td>{{ $transaction->reference ?: 'n/a' }}</td>
                                                                                <td>{{ $formatDateTime($transaction->created_at) }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            @if($transactions->count() > $previewLimit)
                                                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $transactionsMoreId }}" aria-expanded="false" aria-controls="{{ $transactionsMoreId }}">View More</button>
                                                                <div class="collapse mt-2" id="{{ $transactionsMoreId }}">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm hub-section-table mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>ID</th>
                                                                                    <th>Type</th>
                                                                                    <th>Status</th>
                                                                                    <th>Amount</th>
                                                                                    <th>Pet</th>
                                                                                    <th>Ref</th>
                                                                                    <th>Date</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($transactions as $transaction)
                                                                                    @php
                                                                                        $txType = $transaction->type ?: data_get($transaction->metadata, 'order_type', 'n/a');
                                                                                        $txPetName = optional($petsById->get((int) ($transaction->pet_id ?? 0)))->name ?: 'n/a';
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td>#{{ $transaction->id }}</td>
                                                                                        <td>{{ $txType }}</td>
                                                                                        <td>{{ strtoupper($transaction->status ?: 'n/a') }}</td>
                                                                                        <td>{{ $formatInrFromPaise($transaction->amount_paise) }}</td>
                                                                                        <td>{{ $txPetName }}</td>
                                                                                        <td>{{ $transaction->reference ?: 'n/a' }}</td>
                                                                                        <td>{{ $formatDateTime($transaction->created_at) }}</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-12 col-xl-6">
                                                    <div class="hub-mini-card">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div class="hub-section-title mb-0">Prescriptions</div>
                                                            <span class="hub-count">{{ $supports['prescriptions'] ? $prescriptions->count() : 'N/A' }}</span>
                                                        </div>

                                                        @if(!$supports['prescriptions'])
                                                            <p class="hub-muted">`prescriptions` table not available.</p>
                                                        @elseif($prescriptions->isEmpty())
                                                            <p class="hub-muted">No prescriptions found for this user.</p>
                                                        @else
                                                            <div class="table-responsive">
                                                                <table class="table table-sm hub-section-table mb-2">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>ID</th>
                                                                            <th>Pet</th>
                                                                            <th>Diagnosis</th>
                                                                            <th>Follow Up</th>
                                                                            <th>Mode</th>
                                                                            <th>Date</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($prescriptions->take($previewLimit) as $prescription)
                                                                            @php
                                                                                $prescriptionPetName = optional($petsById->get((int) ($prescription->pet_id ?? 0)))->name ?: 'Unknown Pet';
                                                                            @endphp
                                                                            <tr>
                                                                                <td>#{{ $prescription->id }}</td>
                                                                                <td>{{ $prescriptionPetName }}</td>
                                                                                <td>{{ \Illuminate\Support\Str::limit($prescription->diagnosis ?: 'n/a', 48) }}</td>
                                                                                <td>{{ $prescription->follow_up_date ?: 'n/a' }}</td>
                                                                                <td>{{ $prescription->video_inclinic ?: 'n/a' }}</td>
                                                                                <td>{{ $formatDateTime($prescription->created_at) }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            @if($prescriptions->count() > $previewLimit)
                                                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $prescriptionsMoreId }}" aria-expanded="false" aria-controls="{{ $prescriptionsMoreId }}">View More</button>
                                                                <div class="collapse mt-2" id="{{ $prescriptionsMoreId }}">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm hub-section-table mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>ID</th>
                                                                                    <th>Pet</th>
                                                                                    <th>Diagnosis</th>
                                                                                    <th>Follow Up</th>
                                                                                    <th>Mode</th>
                                                                                    <th>Date</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($prescriptions as $prescription)
                                                                                    @php
                                                                                        $prescriptionPetName = optional($petsById->get((int) ($prescription->pet_id ?? 0)))->name ?: 'Unknown Pet';
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td>#{{ $prescription->id }}</td>
                                                                                        <td>{{ $prescriptionPetName }}</td>
                                                                                        <td>{{ \Illuminate\Support\Str::limit($prescription->diagnosis ?: 'n/a', 110) }}</td>
                                                                                        <td>{{ $prescription->follow_up_date ?: 'n/a' }}</td>
                                                                                        <td>{{ $prescription->video_inclinic ?: 'n/a' }}</td>
                                                                                        <td>{{ $formatDateTime($prescription->created_at) }}</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-12 col-xl-6">
                                                    <div class="hub-mini-card">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div class="hub-section-title mb-0">Appointments</div>
                                                            <span class="hub-count">{{ $supports['appointments'] ? $appointments->count() : 'N/A' }}</span>
                                                        </div>

                                                        @if(!$supports['appointments'])
                                                            <p class="hub-muted">`appointments` table not available.</p>
                                                        @elseif($appointments->isEmpty())
                                                            <p class="hub-muted">No appointments found for this user's pets.</p>
                                                        @else
                                                            <div class="table-responsive">
                                                                <table class="table table-sm hub-section-table mb-2">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>ID</th>
                                                                            <th>Pet</th>
                                                                            <th>Date</th>
                                                                            <th>Time</th>
                                                                            <th>Status</th>
                                                                            <th>Doctor</th>
                                                                            <th>Created</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($appointments->take($previewLimit) as $appointment)
                                                                            @php
                                                                                $appointmentPetName = optional($petsById->get((int) ($appointment->pet_id ?? 0)))->name ?: 'Unknown Pet';
                                                                            @endphp
                                                                            <tr>
                                                                                <td>#{{ $appointment->id }}</td>
                                                                                <td>{{ $appointmentPetName }}</td>
                                                                                <td>{{ $appointment->appointment_date ?: 'n/a' }}</td>
                                                                                <td>{{ $appointment->appointment_time ?: 'n/a' }}</td>
                                                                                <td>{{ strtoupper($appointment->status ?: 'n/a') }}</td>
                                                                                <td>{{ $appointment->doctor_id ?: ($appointment->vet_registeration_id ?: 'n/a') }}</td>
                                                                                <td>{{ $formatDateTime($appointment->created_at) }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            @if($appointments->count() > $previewLimit)
                                                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $appointmentsMoreId }}" aria-expanded="false" aria-controls="{{ $appointmentsMoreId }}">View More</button>
                                                                <div class="collapse mt-2" id="{{ $appointmentsMoreId }}">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm hub-section-table mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>ID</th>
                                                                                    <th>Pet</th>
                                                                                    <th>Date</th>
                                                                                    <th>Time</th>
                                                                                    <th>Status</th>
                                                                                    <th>Doctor</th>
                                                                                    <th>Created</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($appointments as $appointment)
                                                                                    @php
                                                                                        $appointmentPetName = optional($petsById->get((int) ($appointment->pet_id ?? 0)))->name ?: 'Unknown Pet';
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td>#{{ $appointment->id }}</td>
                                                                                        <td>{{ $appointmentPetName }}</td>
                                                                                        <td>{{ $appointment->appointment_date ?: 'n/a' }}</td>
                                                                                        <td>{{ $appointment->appointment_time ?: 'n/a' }}</td>
                                                                                        <td>{{ strtoupper($appointment->status ?: 'n/a') }}</td>
                                                                                        <td>{{ $appointment->doctor_id ?: ($appointment->vet_registeration_id ?: 'n/a') }}</td>
                                                                                        <td>{{ $formatDateTime($appointment->created_at) }}</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="hub-mini-card">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div class="hub-section-title mb-0">Video Consults</div>
                                                            <span class="hub-count">{{ $supports['video_apointment'] ? $videoConsults->count() : 'N/A' }}</span>
                                                        </div>

                                                        @if(!$supports['video_apointment'])
                                                            <p class="hub-muted">`video_apointment` table not available.</p>
                                                        @elseif($videoConsults->isEmpty())
                                                            <p class="hub-muted">No video consults found for this user.</p>
                                                        @else
                                                            <div class="table-responsive">
                                                                <table class="table table-sm hub-section-table mb-2">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>ID</th>
                                                                            <th>Pet</th>
                                                                            <th>Order</th>
                                                                            <th>Call Session</th>
                                                                            <th>Status</th>
                                                                            <th>Doctor</th>
                                                                            <th>Date</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($videoConsults->take($previewLimit) as $videoConsult)
                                                                            @php
                                                                                $videoPetName = optional($petsById->get((int) ($videoConsult->pet_id ?? 0)))->name ?: 'Unknown Pet';
                                                                            @endphp
                                                                            <tr>
                                                                                <td>#{{ $videoConsult->id }}</td>
                                                                                <td>{{ $videoPetName }}</td>
                                                                                <td>{{ $videoConsult->order_id ?: 'n/a' }}</td>
                                                                                <td>{{ $videoConsult->call_session ?: 'n/a' }}</td>
                                                                                <td>{{ $videoConsult->is_completed ? 'COMPLETED' : 'PENDING' }}</td>
                                                                                <td>{{ $videoConsult->doctor_id ?: 'n/a' }}</td>
                                                                                <td>{{ $formatDateTime($videoConsult->created_at) }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            @if($videoConsults->count() > $previewLimit)
                                                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $videosMoreId }}" aria-expanded="false" aria-controls="{{ $videosMoreId }}">View More</button>
                                                                <div class="collapse mt-2" id="{{ $videosMoreId }}">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm hub-section-table mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>ID</th>
                                                                                    <th>Pet</th>
                                                                                    <th>Order</th>
                                                                                    <th>Call Session</th>
                                                                                    <th>Status</th>
                                                                                    <th>Doctor</th>
                                                                                    <th>Date</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($videoConsults as $videoConsult)
                                                                                    @php
                                                                                        $videoPetName = optional($petsById->get((int) ($videoConsult->pet_id ?? 0)))->name ?: 'Unknown Pet';
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td>#{{ $videoConsult->id }}</td>
                                                                                        <td>{{ $videoPetName }}</td>
                                                                                        <td>{{ $videoConsult->order_id ?: 'n/a' }}</td>
                                                                                        <td>{{ $videoConsult->call_session ?: 'n/a' }}</td>
                                                                                        <td>{{ $videoConsult->is_completed ? 'COMPLETED' : 'PENDING' }}</td>
                                                                                        <td>{{ $videoConsult->doctor_id ?: 'n/a' }}</td>
                                                                                        <td>{{ $formatDateTime($videoConsult->created_at) }}</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
