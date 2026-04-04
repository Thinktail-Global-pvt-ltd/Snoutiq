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
        min-width: 2rem;
        height: 1.5rem;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0 0.45rem;
    }
    .hub-preview-line {
        margin-top: 0.35rem;
        font-size: 0.78rem;
        line-height: 1.3;
        color: #475569;
    }
    .hub-sticky-top th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #f8fafc;
    }
    .hub-modal-section {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #fff;
        padding: 0.85rem;
        margin-bottom: 0.85rem;
    }
    .hub-modal-section:last-child {
        margin-bottom: 0;
    }
    .hub-modal-section h6 {
        font-size: 0.83rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        color: #334155;
        margin-bottom: 0.7rem;
    }
    .hub-modal-table th {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #475569;
        white-space: nowrap;
    }
    .hub-modal-table td {
        font-size: 0.79rem;
        color: #1e293b;
        white-space: nowrap;
    }
    .hub-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #f1f5f9;
        color: #334155;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.3rem 0.55rem;
    }
    .hub-pagination .pagination {
        margin-bottom: 0;
    }
    .hub-pagination .page-link {
        font-size: 0.85rem;
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
    $currentPage = (int) request()->query('page', 1);
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">All Users With Related Data</h2>
                        <p class="text-muted mb-0">Compact list with quick preview. Open modal to view complete records.</p>
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
                        <div class="col-12 col-lg-3 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Apply</button>
                            <a
                                href="{{ route('admin.users.data-hub.export-csv', array_filter(['q' => $searchQuery], fn ($value) => $value !== null && $value !== '')) }}"
                                class="btn btn-sm btn-success"
                            >
                                <i class="bi bi-download me-1"></i>Export CSV
                            </a>
                            @if($searchQuery !== '')
                                <a href="{{ route('admin.users.data-hub', ['per_page' => $currentPerPage]) }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                            @endif
                        </div>
                    </form>
                </div>

                @if(session('status'))
                    <div class="alert alert-success py-2 px-3 mb-3">{{ session('status') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger py-2 px-3 mb-3">{{ session('error') }}</div>
                @endif

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

                                        $modalId = 'user-data-modal-'.$userId;
                                        $modalLabelId = 'user-data-modal-label-'.$userId;
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
                                                    {{ \Illuminate\Support\Str::limit(($latestPet->name ?: 'Unnamed Pet') . ' · ' . ($latestPet->breed ?: ($latestPet->pet_type ?? $latestPet->type ?? 'n/a')), 42) }}
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
                                                    {{ strtoupper($latestTransaction->status ?: 'n/a') }} · {{ $formatInrFromPaise($latestTransaction->amount_paise) }} · {{ \Illuminate\Support\Str::limit($latestTxType, 24) }}
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
                                                    {{ \Illuminate\Support\Str::limit($latestPrescription->diagnosis ?: 'No diagnosis', 42) }}
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
                                            <div class="d-flex justify-content-end gap-2">
                                                <button
                                                    class="btn btn-sm btn-outline-primary"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#{{ $modalId }}"
                                                >
                                                    View
                                                </button>
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.users.data-hub.delete', $user) }}"
                                                    onsubmit="return confirm('Delete user #{{ $user->id }} and all related data? This cannot be undone.');"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="q" value="{{ $searchQuery }}">
                                                    <input type="hidden" name="per_page" value="{{ $currentPerPage }}">
                                                    <input type="hidden" name="page" value="{{ $currentPage }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 hub-pagination d-flex justify-content-end">
                        {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>

                    @foreach($users as $user)
                        @php
                            $userId = (int) $user->id;
                            $pets = $petsByUser->get($userId, collect());
                            $transactions = $transactionsByUser->get($userId, collect());
                            $prescriptions = $prescriptionsByUser->get($userId, collect());
                            $appointments = $appointmentsByUser->get($userId, collect());
                            $videoConsults = $videoConsultsByUser->get($userId, collect());

                            $modalId = 'user-data-modal-'.$userId;
                            $modalLabelId = 'user-data-modal-label-'.$userId;
                        @endphp

                        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalLabelId }}" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div>
                                            <h5 class="modal-title" id="{{ $modalLabelId }}">{{ $user->name ?: 'Unnamed User' }} · Full Details</h5>
                                            <div class="small text-muted">ID: {{ $user->id }} · {{ $user->email ?: 'no-email' }} · {{ $user->phone ?: 'no-phone' }}</div>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <span class="hub-chip">Joined {{ $formatDateTime($user->created_at) }}</span>
                                            <span class="hub-chip">Role: {{ str_replace('_', ' ', $user->role ?: 'n/a') }}</span>
                                            <span class="hub-chip">Pets: {{ $supports['pets'] ? $pets->count() : 'N/A' }}</span>
                                            <span class="hub-chip">Transactions: {{ $supports['transactions'] ? $transactions->count() : 'N/A' }}</span>
                                            <span class="hub-chip">Prescriptions: {{ $supports['prescriptions'] ? $prescriptions->count() : 'N/A' }}</span>
                                            <span class="hub-chip">Appointments: {{ $supports['appointments'] ? $appointments->count() : 'N/A' }}</span>
                                            <span class="hub-chip">Video Consults: {{ $supports['video_apointment'] ? $videoConsults->count() : 'N/A' }}</span>
                                        </div>

                                        <div class="hub-modal-section">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">Pets</h6>
                                                <span class="hub-count">{{ $supports['pets'] ? $pets->count() : 'N/A' }}</span>
                                            </div>
                                            @if(!$supports['pets'])
                                                <p class="text-muted mb-0 mt-2">`pets` table not available.</p>
                                            @elseif($pets->isEmpty())
                                                <p class="text-muted mb-0 mt-2">No pets found for this user.</p>
                                            @else
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm hub-modal-table mb-0">
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
                                            @endif
                                        </div>

                                        <div class="hub-modal-section">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">Transactions</h6>
                                                <span class="hub-count">{{ $supports['transactions'] ? $transactions->count() : 'N/A' }}</span>
                                            </div>
                                            @if(!$supports['transactions'])
                                                <p class="text-muted mb-0 mt-2">`transactions` table not available.</p>
                                            @elseif($transactions->isEmpty())
                                                <p class="text-muted mb-0 mt-2">No transactions found for this user.</p>
                                            @else
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm hub-modal-table mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Type</th>
                                                                <th>Status</th>
                                                                <th>Amount</th>
                                                                <th>Pet</th>
                                                                <th>Reference</th>
                                                                <th>Doctor</th>
                                                                <th>Clinic</th>
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
                                                                    <td>{{ $txPetName }} ({{ $transaction->pet_id ?: 'n/a' }})</td>
                                                                    <td>{{ $transaction->reference ?: 'n/a' }}</td>
                                                                    <td>{{ $transaction->doctor_id ?: 'n/a' }}</td>
                                                                    <td>{{ $transaction->clinic_id ?: 'n/a' }}</td>
                                                                    <td>{{ $formatDateTime($transaction->created_at) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="hub-modal-section">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">Prescriptions</h6>
                                                <span class="hub-count">{{ $supports['prescriptions'] ? $prescriptions->count() : 'N/A' }}</span>
                                            </div>
                                            @if(!$supports['prescriptions'])
                                                <p class="text-muted mb-0 mt-2">`prescriptions` table not available.</p>
                                            @elseif($prescriptions->isEmpty())
                                                <p class="text-muted mb-0 mt-2">No prescriptions found for this user.</p>
                                            @else
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm hub-modal-table mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Pet</th>
                                                                <th>Diagnosis</th>
                                                                <th>Follow Up</th>
                                                                <th>Mode</th>
                                                                <th>Doctor</th>
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
                                                                    <td>{{ $prescriptionPetName }} ({{ $prescription->pet_id ?: 'n/a' }})</td>
                                                                    <td>{{ $prescription->diagnosis ?: 'n/a' }}</td>
                                                                    <td>{{ $prescription->follow_up_date ?: 'n/a' }}</td>
                                                                    <td>{{ $prescription->video_inclinic ?: 'n/a' }}</td>
                                                                    <td>{{ $prescription->doctor_id ?: 'n/a' }}</td>
                                                                    <td>{{ $formatDateTime($prescription->created_at) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="hub-modal-section">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">Appointments</h6>
                                                <span class="hub-count">{{ $supports['appointments'] ? $appointments->count() : 'N/A' }}</span>
                                            </div>
                                            @if(!$supports['appointments'])
                                                <p class="text-muted mb-0 mt-2">`appointments` table not available.</p>
                                            @elseif($appointments->isEmpty())
                                                <p class="text-muted mb-0 mt-2">No appointments found for this user's pets.</p>
                                            @else
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm hub-modal-table mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Pet</th>
                                                                <th>Date</th>
                                                                <th>Time</th>
                                                                <th>Status</th>
                                                                <th>Doctor</th>
                                                                <th>Vet Registration</th>
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
                                                                    <td>{{ $appointmentPetName }} ({{ $appointment->pet_id ?: 'n/a' }})</td>
                                                                    <td>{{ $appointment->appointment_date ?: 'n/a' }}</td>
                                                                    <td>{{ $appointment->appointment_time ?: 'n/a' }}</td>
                                                                    <td>{{ strtoupper($appointment->status ?: 'n/a') }}</td>
                                                                    <td>{{ $appointment->doctor_id ?: 'n/a' }}</td>
                                                                    <td>{{ $appointment->vet_registeration_id ?: 'n/a' }}</td>
                                                                    <td>{{ $formatDateTime($appointment->created_at) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="hub-modal-section">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">Video Consults</h6>
                                                <span class="hub-count">{{ $supports['video_apointment'] ? $videoConsults->count() : 'N/A' }}</span>
                                            </div>
                                            @if(!$supports['video_apointment'])
                                                <p class="text-muted mb-0 mt-2">`video_apointment` table not available.</p>
                                            @elseif($videoConsults->isEmpty())
                                                <p class="text-muted mb-0 mt-2">No video consults found for this user.</p>
                                            @else
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm hub-modal-table mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Pet</th>
                                                                <th>Order ID</th>
                                                                <th>Call Session</th>
                                                                <th>Status</th>
                                                                <th>Doctor</th>
                                                                <th>Clinic</th>
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
                                                                    <td>{{ $videoPetName }} ({{ $videoConsult->pet_id ?: 'n/a' }})</td>
                                                                    <td>{{ $videoConsult->order_id ?: 'n/a' }}</td>
                                                                    <td>{{ $videoConsult->call_session ?: 'n/a' }}</td>
                                                                    <td>{{ $videoConsult->is_completed ? 'COMPLETED' : 'PENDING' }}</td>
                                                                    <td>{{ $videoConsult->doctor_id ?: 'n/a' }}</td>
                                                                    <td>{{ $videoConsult->clinic_id ?: 'n/a' }}</td>
                                                                    <td>{{ $formatDateTime($videoConsult->created_at) }}</td>
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
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
