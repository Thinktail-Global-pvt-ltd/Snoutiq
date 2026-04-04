@extends('layouts.admin-panel')

@section('page-title', 'Users Data Hub')

@push('styles')
<style>
    .hub-table td,
    .hub-table th {
        vertical-align: middle;
    }
    .hub-count {
        font-size: 0.75rem;
        border-radius: 999px;
        padding: 0.3rem 0.6rem;
        font-weight: 600;
        background: #eef2ff;
        color: #3730a3;
    }
    .hub-section-title {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #334155;
        margin-bottom: 0.65rem;
    }
    .hub-mini-card {
        border: 1px solid #e2e8f0;
        border-radius: 0.9rem;
        background: #fff;
        padding: 0.85rem;
        height: 100%;
    }
    .hub-mini-card ul {
        margin-bottom: 0;
        padding-left: 1rem;
    }
    .hub-mini-card li {
        margin-bottom: 0.45rem;
        color: #334155;
        font-size: 0.86rem;
    }
    .hub-mini-card li:last-child {
        margin-bottom: 0;
    }
    .hub-note {
        font-size: 0.78rem;
        color: #64748b;
    }
    .hub-muted {
        font-size: 0.86rem;
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
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">All Users With Related Data</h2>
                        <p class="text-muted mb-0">Each user includes pets, transactions, prescriptions, appointments, and video consult records.</p>
                    </div>
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label for="per_page" class="form-label mb-0 text-muted small">Per page</label>
                        <select id="per_page" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach([10, 20, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($currentPerPage === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
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
                                        $collapseId = 'user-data-'.$userId;
                                    @endphp

                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name ?: 'Unnamed User' }}</div>
                                            <div class="small text-muted">ID: {{ $user->id }} · {{ $user->email ?: 'no-email' }}</div>
                                            <div class="small text-muted">{{ $user->phone ?: 'no-phone' }} · {{ str_replace('_', ' ', $user->role ?: 'n/a') }}</div>
                                            <div class="small text-muted">Joined {{ $formatDateTime($user->created_at) }}</div>
                                        </td>
                                        <td><span class="hub-count">{{ $supports['pets'] ? $pets->count() : 'N/A' }}</span></td>
                                        <td><span class="hub-count">{{ $supports['transactions'] ? $transactions->count() : 'N/A' }}</span></td>
                                        <td><span class="hub-count">{{ $supports['prescriptions'] ? $prescriptions->count() : 'N/A' }}</span></td>
                                        <td><span class="hub-count">{{ $supports['appointments'] ? $appointments->count() : 'N/A' }}</span></td>
                                        <td><span class="hub-count">{{ $supports['video_apointment'] ? $videoConsults->count() : 'N/A' }}</span></td>
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
                                                <div class="col-12 col-lg-6">
                                                    <div class="hub-mini-card">
                                                        <div class="hub-section-title">Pets ({{ $supports['pets'] ? $pets->count() : 'N/A' }})</div>
                                                        @if(!$supports['pets'])
                                                            <p class="hub-muted">`pets` table not available.</p>
                                                        @elseif($pets->isEmpty())
                                                            <p class="hub-muted">No pets found for this user.</p>
                                                        @else
                                                            <ul>
                                                                @foreach($pets->take($detailLimit) as $pet)
                                                                    @php
                                                                        $petType = $pet->pet_type ?? $pet->type ?? 'n/a';
                                                                        $petGender = $pet->pet_gender ?? $pet->gender ?? 'n/a';
                                                                    @endphp
                                                                    <li>
                                                                        <strong>{{ $pet->name ?: 'Unnamed Pet' }}</strong>
                                                                        · {{ $petType }} · {{ $petGender }}
                                                                        · {{ $pet->breed ?: 'no-breed' }}
                                                                        <span class="text-muted">(#{{ $pet->id }})</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                            @if($pets->count() > $detailLimit)
                                                                <div class="hub-note mt-2">Showing latest {{ $detailLimit }} of {{ $pets->count() }} pets.</div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-12 col-lg-6">
                                                    <div class="hub-mini-card">
                                                        <div class="hub-section-title">Transactions ({{ $supports['transactions'] ? $transactions->count() : 'N/A' }})</div>
                                                        @if(!$supports['transactions'])
                                                            <p class="hub-muted">`transactions` table not available.</p>
                                                        @elseif($transactions->isEmpty())
                                                            <p class="hub-muted">No transactions found for this user.</p>
                                                        @else
                                                            <ul>
                                                                @foreach($transactions->take($detailLimit) as $transaction)
                                                                    @php
                                                                        $txType = $transaction->type ?: data_get($transaction->metadata, 'order_type', 'n/a');
                                                                    @endphp
                                                                    <li>
                                                                        <strong>#{{ $transaction->id }}</strong>
                                                                        · {{ $txType }} · {{ strtoupper($transaction->status ?: 'n/a') }}
                                                                        · {{ $formatInrFromPaise($transaction->amount_paise) }}
                                                                        <span class="text-muted">({{ $formatDateTime($transaction->created_at) }})</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                            @if($transactions->count() > $detailLimit)
                                                                <div class="hub-note mt-2">Showing latest {{ $detailLimit }} of {{ $transactions->count() }} transactions.</div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-12 col-lg-6">
                                                    <div class="hub-mini-card">
                                                        <div class="hub-section-title">Prescriptions ({{ $supports['prescriptions'] ? $prescriptions->count() : 'N/A' }})</div>
                                                        @if(!$supports['prescriptions'])
                                                            <p class="hub-muted">`prescriptions` table not available.</p>
                                                        @elseif($prescriptions->isEmpty())
                                                            <p class="hub-muted">No prescriptions found for this user.</p>
                                                        @else
                                                            <ul>
                                                                @foreach($prescriptions->take($detailLimit) as $prescription)
                                                                    @php
                                                                        $petName = optional($petsById->get((int) ($prescription->pet_id ?? 0)))->name ?: 'Unknown Pet';
                                                                    @endphp
                                                                    <li>
                                                                        <strong>#{{ $prescription->id }}</strong>
                                                                        · {{ $petName }}
                                                                        · {{ $prescription->diagnosis ?: 'no-diagnosis' }}
                                                                        <span class="text-muted">({{ $formatDateTime($prescription->created_at) }})</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                            @if($prescriptions->count() > $detailLimit)
                                                                <div class="hub-note mt-2">Showing latest {{ $detailLimit }} of {{ $prescriptions->count() }} prescriptions.</div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-12 col-lg-6">
                                                    <div class="hub-mini-card">
                                                        <div class="hub-section-title">Appointments ({{ $supports['appointments'] ? $appointments->count() : 'N/A' }})</div>
                                                        @if(!$supports['appointments'])
                                                            <p class="hub-muted">`appointments` table not available.</p>
                                                        @elseif($appointments->isEmpty())
                                                            <p class="hub-muted">No appointments found for this user’s pets.</p>
                                                        @else
                                                            <ul>
                                                                @foreach($appointments->take($detailLimit) as $appointment)
                                                                    @php
                                                                        $appointmentPetName = optional($petsById->get((int) ($appointment->pet_id ?? 0)))->name ?: 'Unknown Pet';
                                                                    @endphp
                                                                    <li>
                                                                        <strong>#{{ $appointment->id }}</strong>
                                                                        · {{ $appointmentPetName }}
                                                                        · {{ $appointment->appointment_date ?: 'no-date' }} {{ $appointment->appointment_time ?: '' }}
                                                                        · {{ strtoupper($appointment->status ?: 'n/a') }}
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                            @if($appointments->count() > $detailLimit)
                                                                <div class="hub-note mt-2">Showing latest {{ $detailLimit }} of {{ $appointments->count() }} appointments.</div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="hub-mini-card">
                                                        <div class="hub-section-title">Video Consults ({{ $supports['video_apointment'] ? $videoConsults->count() : 'N/A' }})</div>
                                                        @if(!$supports['video_apointment'])
                                                            <p class="hub-muted">`video_apointment` table not available.</p>
                                                        @elseif($videoConsults->isEmpty())
                                                            <p class="hub-muted">No video consults found for this user.</p>
                                                        @else
                                                            <ul>
                                                                @foreach($videoConsults->take($detailLimit) as $videoConsult)
                                                                    @php
                                                                        $videoPetName = optional($petsById->get((int) ($videoConsult->pet_id ?? 0)))->name ?: 'Unknown Pet';
                                                                    @endphp
                                                                    <li>
                                                                        <strong>#{{ $videoConsult->id }}</strong>
                                                                        · {{ $videoPetName }}
                                                                        · order {{ $videoConsult->order_id ?: 'n/a' }}
                                                                        · call {{ $videoConsult->call_session ?: 'n/a' }}
                                                                        · {{ $videoConsult->is_completed ? 'COMPLETED' : 'PENDING' }}
                                                                        <span class="text-muted">({{ $formatDateTime($videoConsult->created_at) }})</span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                            @if($videoConsults->count() > $detailLimit)
                                                                <div class="hub-note mt-2">Showing latest {{ $detailLimit }} of {{ $videoConsults->count() }} video consults.</div>
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
