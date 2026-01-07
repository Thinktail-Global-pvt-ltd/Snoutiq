@extends('layouts.admin-panel')

@section('page-title', 'Pet Parents')

@push('styles')
<style>
  .timeline { position: relative; list-style: none; padding-left: 1.4rem; margin-bottom: 0; }
  .timeline::before { content: ''; position: absolute; left: 6px; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
  .timeline-item { position: relative; padding-bottom: 1rem; }
  .timeline-item:last-child { padding-bottom: 0; }
  .timeline-dot { position: absolute; left: -2px; top: 2px; width: 12px; height: 12px; border-radius: 999px; background: #6366f1; border: 2px solid #fff; box-shadow: 0 0 0 4px rgba(99,102,241,0.12); }
  .timeline-dot.pet { background: #16a34a; box-shadow: 0 0 0 4px rgba(22,163,74,0.12); }
  .timeline-dot.payment { background: #0ea5e9; box-shadow: 0 0 0 4px rgba(14,165,233,0.12); }
  .timeline-dot.booking { background: #f59e0b; box-shadow: 0 0 0 4px rgba(245,158,11,0.12); }
  .timeline-dot.consultation { background: #a855f7; box-shadow: 0 0 0 4px rgba(168,85,247,0.12); }
  .timeline-dot.health { background: #dc2626; box-shadow: 0 0 0 4px rgba(220,38,38,0.12); }
  .timeline-dot.engagement { background: #14b8a6; box-shadow: 0 0 0 4px rgba(20,184,166,0.12); }
  .metric-pill { border-radius: 999px; padding: .4rem .75rem; font-weight: 700; background: #f1f5f9; color: #0f172a; }
  .metric-pill span { display: block; font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; color: #64748b; }
  .pet-card { transition: all .15s ease; border: 1px solid #e2e8f0; background: #fff; height: 100%; }
  .pet-card:hover { box-shadow: 0 10px 24px rgba(15,23,42,0.08); border-color: #d0d7e2; }
  .pet-summary { background: #f8fafc; border-radius: 10px; padding: .6rem .75rem; font-size: .85rem; color: #0f172a; }
  .pagination { gap: .25rem; flex-wrap: wrap; }
  .pagination .page-link { border-radius: 10px; padding: .35rem .6rem; }
</style>
@endpush

@section('content')
@php
    $selected = $selectedPetParent ?? null;
    $profile = $selected['user'] ?? null;
    $clinicReferralCode = $selected['clinicReferralCode'] ?? null;
    $lifeEvents = $selected['lifecycle'] ?? [];
    $metrics = $selected['metrics'] ?? [];
    $formatDate = static fn ($value) => $value ? \Carbon\Carbon::parse($value)->timezone('Asia/Kolkata')->format('d M Y, H:i') : '—';
    $formatMoney = static fn ($value) => '₹' . number_format((float) $value, 2);
    $formatDuration = static fn ($seconds) => $seconds ? \Carbon\CarbonInterval::seconds((int) $seconds)->cascade()->forHumans(['short' => true]) : '—';
@endphp
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h2 class="h5 mb-1">Pet parents</h2>
                        <p class="text-muted small mb-0">Select a pet parent to see their full lifecycle and records.</p>
                    </div>
                    <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2">{{ number_format($petParents->total()) }} total</span>
                </div>
                <form method="GET" action="{{ route('admin.pet-parents') }}" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Search by name, email, phone" value="{{ $search }}">
                        @if($selected && $profile)
                            <input type="hidden" name="user_id" value="{{ $profile->id }}">
                        @endif
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>
                <div class="list-group list-group-flush border rounded overflow-auto" style="max-height: 70vh;">
                    @forelse($petParents as $parent)
                        @php $isActive = $profile && $profile->id === $parent->id; @endphp
                        <a href="{{ route('admin.pet-parents', ['user_id' => $parent->id] + ($search ? ['q' => $search] : [])) }}"
                           class="list-group-item list-group-item-action {{ $isActive ? 'active' : '' }}">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $parent->name ?? 'Unnamed user' }}</div>
                                    <div class="small {{ $isActive ? 'text-white-50' : 'text-muted' }}">
                                        {{ $parent->phone ?? $parent->email ?? '—' }}
                                    </div>
                                </div>
                                <div class="text-end small {{ $isActive ? 'text-white-50' : 'text-muted' }}">
                                    <div>#{{ $parent->id }}</div>
                                    <div>{{ optional($parent->created_at)->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center text-muted py-4">No pet parents found.</div>
                    @endforelse
                </div>
                <div class="mt-3">
                    {{ $petParents->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        @if(!$selected || !$profile)
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    Select a pet parent on the left to load their lifecycle.
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <h2 class="h5 mb-1">{{ $profile->name ?? 'Unnamed pet parent' }}</h2>
                            <div class="text-muted small">
                                ID #{{ $profile->id }} · Joined {{ optional($profile->created_at)->format('d M Y, H:i') ?? '—' }} · Role: {{ $profile->role ?? 'pet_parent' }}
                            </div>
                            <div class="text-muted small mt-1">
                                {{ $profile->email ?? '—' }} · {{ $profile->phone ?? '—' }}
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="metric-pill text-center">
                                <div class="fw-bold">{{ number_format(data_get($metrics, 'pets_count', 0)) }}</div>
                                <span>Pets</span>
                            </div>
                            <div class="metric-pill text-center">
                                <div class="fw-bold">{{ number_format(data_get($metrics, 'call_sessions', 0)) }}</div>
                                <span>Video Calls</span>
                            </div>
                            <div class="metric-pill text-center">
                                <div class="fw-bold">{{ number_format(data_get($metrics, 'consultations', 0)) }}</div>
                                <span>Consults</span>
                            </div>
                            <div class="metric-pill text-center">
                                <div class="fw-bold">{{ $formatMoney(data_get($metrics, 'transactions.value_rupees', 0)) }}</div>
                                <span>Payments</span>
                            </div>
                            <div class="metric-pill text-center">
                                <div class="fw-bold">{{ data_get($metrics, 'bookings', 0) + data_get($metrics, 'groomer_bookings', 0) }}</div>
                                <span>Bookings</span>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <dl class="row mb-0 small">
                                <dt class="col-5 text-muted">Location</dt>
                                <dd class="col-7">{{ ($profile->latitude && $profile->longitude) ? ($profile->latitude.', '.$profile->longitude) : '—' }}</dd>
                                <dt class="col-5 text-muted">Referral code</dt>
                                <dd class="col-7">{{ $clinicReferralCode ?? $profile->referral_code ?? '—' }}</dd>
                                <dt class="col-5 text-muted">Source QR</dt>
                                <dd class="col-7">
                                    @if($profile->qrScanner)
                                        {{ $profile->qrScanner->code ?? 'QR' }}<div class="text-muted">{{ $profile->qrScanner->public_id ?? '' }}</div>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0 small">
                                <dt class="col-5 text-muted">Last vet</dt>
                                <dd class="col-7">{{ $profile->last_vet_slug ?? ('#'.$profile->last_vet_id ?? '—') }}</dd>
                                <dt class="col-5 text-muted">Phone verified</dt>
                                <dd class="col-7">{{ $profile->phone_verified_at ? 'Yes ('.$formatDate($profile->phone_verified_at).')' : 'No / pending' }}</dd>
                                <dt class="col-5 text-muted">Last active</dt>
                                <dd class="col-7">{{ $metrics['last_activity'] ? $formatDate($metrics['last_activity']) : '—' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="h6 mb-0">Lifecycle timeline</h3>
                        <span class="badge text-bg-light">{{ count($lifeEvents) }} events</span>
                    </div>
                    <ul class="timeline mt-3">
                        @forelse($lifeEvents as $event)
                            @php $type = $event['type'] ?? 'call'; @endphp
                            <li class="timeline-item">
                                <span class="timeline-dot {{ $type }}"></span>
                                <div class="ms-3">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold">{{ $event['title'] }}</div>
                                            <div class="text-muted small">{{ $event['description'] ?? '' }}</div>
                                        </div>
                                        <div class="text-muted small text-nowrap">{{ $event['at'] ? $event['at']->timezone('Asia/Kolkata')->format('d M Y, H:i') : '—' }}</div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="timeline-item">
                                <span class="timeline-dot"></span>
                                <div class="ms-3 text-muted">No lifecycle events recorded.</div>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 mb-0">Pets on file</h3>
                        <span class="text-muted small">{{ data_get($metrics, 'pets_count', 0) }} total</span>
                    </div>
                    <div class="row g-3">
                        @forelse($selected['userPets'] as $pet)
                            <div class="col-md-6">
                                <div class="p-3 pet-card rounded">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $pet->name }}</strong>
                                        <span class="badge text-bg-light">Legacy</span>
                                    </div>
                                    <div class="text-muted small">{{ $pet->type ?? 'Pet' }} · {{ $pet->breed ?? 'Breed n/a' }}</div>
                                    <div class="small mt-1">Gender: {{ $pet->gender ?? 'n/a' }}</div>
                                    <div class="small text-muted">State: {{ $pet->health_state ?? '—' }}</div>
                                    @if(!empty($pet->ai_summary))
                                        <div class="pet-summary mt-2">
                                            <div class="text-uppercase text-muted" style="font-size: .7rem;">AI Summary</div>
                                            <div>{{ Str::limit($pet->ai_summary, 160) }}</div>
                                        </div>
                                    @endif
                                    <div class="small text-muted">Added {{ optional($pet->created_at)->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                        @empty
                        @endforelse

                        @forelse($selected['pets'] as $pet)
                            <div class="col-md-6">
                                <div class="p-3 pet-card rounded">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $pet->name ?? 'Pet' }}</strong>
                                        <span class="badge text-bg-primary-subtle text-primary-emphasis">Current</span>
                                    </div>
                                    <div class="text-muted small">{{ $pet->breed ?? 'Breed n/a' }} · {{ $pet->pet_age ? $pet->pet_age.'y' : 'Age n/a' }}</div>
                                    <div class="small">Gender: {{ $pet->pet_gender ?? 'n/a' }}</div>
                                    <div class="small text-muted">State: {{ $pet->health_state ?? '—' }}</div>
                                    @if(!empty($pet->ai_summary))
                                        <div class="pet-summary mt-2">
                                            <div class="text-uppercase text-muted" style="font-size: .7rem;">AI Summary</div>
                                            <div>{{ Str::limit($pet->ai_summary, 160) }}</div>
                                        </div>
                                    @endif
                                    <div class="small text-muted">Added {{ optional($pet->created_at)->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-muted">No pets recorded.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h3 class="h6 mb-0">Video call sessions</h3>
                                <span class="text-muted small">{{ count($selected['callSessions']) }} records</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Status</th>
                                            <th>Doctor</th>
                                            <th>Payment</th>
                                            <th>Started</th>
                                            <th>Ended</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($selected['callSessions'] as $session)
                                            <tr>
                                                <td>#{{ $session->id }}</td>
                                                <td><span class="badge text-bg-light">{{ strtoupper($session->status ?? '—') }}</span></td>
                                                <td>{{ $session->doctor?->doctor_name ?? '—' }}</td>
                                                <td>
                                                    @if(($session->payment_status ?? '') === 'paid')
                                                        {{ $formatMoney(($session->amount_paid ?? 0) / 100) }}
                                                    @else
                                                        <span class="text-muted">Unpaid</span>
                                                    @endif
                                                </td>
                                                <td>{{ $formatDate($session->started_at) }}</td>
                                                <td>{{ $formatDate($session->ended_at) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-muted text-center">No call sessions.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h3 class="h6 mb-0">Consultations</h3>
                                <span class="text-muted small">{{ count($selected['consultations']) }} records</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Status</th>
                                            <th>Doctor</th>
                                            <th>Pet</th>
                                            <th>Start</th>
                                            <th>End</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($selected['consultations'] as $consult)
                                            <tr>
                                                <td>#{{ $consult->id }}</td>
                                                <td><span class="badge text-bg-light">{{ strtoupper($consult->status ?? '—') }}</span></td>
                                                <td>{{ $consult->doctor?->doctor_name ?? '—' }}</td>
                                                <td>{{ $consult->pet?->name ?? ($consult->pet_id ? '#'.$consult->pet_id : '—') }}</td>
                                                <td>{{ $formatDate($consult->start_time) }}</td>
                                                <td>{{ $formatDate($consult->end_time) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-muted text-center">No consultations logged.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h3 class="h6 mb-0">Payments & transactions</h3>
                                <span class="text-muted small">{{ count($selected['transactions']) }} records</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Type</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($selected['transactions'] as $txn)
                                            <tr>
                                                <td>#{{ $txn->id }}</td>
                                                <td>{{ $formatMoney(($txn->amount_paise ?? 0) / 100) }}</td>
                                                <td><span class="badge text-bg-light">{{ strtoupper($txn->status ?? '—') }}</span></td>
                                                <td>{{ $txn->type ?? '—' }}</td>
                                                <td>{{ $formatDate($txn->created_at) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-muted text-center">No transactions found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <h3 class="h6 mb-2">Health notes</h3>
                            <ul class="list-unstyled mb-0 small">
                                @forelse($selected['observations'] as $obs)
                                    <li class="mb-2">
                                        <div class="fw-semibold">{{ $formatDate($obs->observed_at ?? $obs->created_at) }}</div>
                                        <div class="text-muted">{{ $obs->notes ?? 'Observation logged' }}</div>
                                    </li>
                                @empty
                                    <li class="text-muted">No observations.</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h3 class="h6 mb-2">Records & prescriptions</h3>
                            <p class="small mb-1 text-muted">{{ count($selected['medicalRecords']) }} medical records</p>
                            <p class="small mb-3 text-muted">{{ count($selected['prescriptions']) }} prescriptions</p>
                            @if(count($selected['medicalRecords']))
                                <div class="small">
                                    Last record: {{ $formatDate(optional($selected['medicalRecords']->first())->created_at) }}
                                </div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <h3 class="h6 mb-2">Engagement</h3>
                            <p class="small mb-1 text-muted">{{ count($selected['aiChats']) }} AI chats ({{ $selected['aiChatMessages'] }} messages)</p>
                            <p class="small mb-1 text-muted">{{ count($selected['supportTickets']) }} support tickets</p>
                            @if(count($selected['supportTickets']))
                                <div class="small">Last ticket: {{ $formatDate(optional($selected['supportTickets']->first())->created_at) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
