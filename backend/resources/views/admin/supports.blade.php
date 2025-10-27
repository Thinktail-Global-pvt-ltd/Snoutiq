@extends('layouts.admin-panel')

@section('page-title', 'Support Tickets')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Customer support</h2>
                        <p class="text-muted mb-0">Keep an eye on the latest issues raised by the community.</p>
                    </div>
                    <span class="badge text-bg-warning-subtle text-warning-emphasis px-3 py-2">{{ number_format($supports->count()) }} open items</span>
                </div>

                @if($supports->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-life-preserver display-6 d-block mb-2"></i>
                        <p class="mb-0">No support tickets to review right now.</p>
                    </div>
                @else
                    <div id="support-accordion" class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Ticket</th>
                                    <th scope="col">Pet parent</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Created</th>
                                    <th scope="col">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($supports as $ticket)
                                    @php
                                        $user = optional($ticket->user);
                                        $status = $ticket->status ?? $ticket->ticket_status ?? 'open';
                                        $category = $ticket->category ?? $ticket->type ?? 'General';
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">#{{ $ticket->id }}</span>
                                            <div class="text-muted small">{{ $ticket->subject ?? \Illuminate\Support\Str::limit($ticket->message ?? $ticket->description ?? 'Support ticket', 40) }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name ?? 'Unknown' }}</div>
                                            <div class="text-muted small">{{ $user->email ?? '—' }}</div>
                                        </td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $category)) }}</td>
                                        <td>
                                            <span class="badge rounded-pill text-bg-secondary text-uppercase small">{{ $status }}</span>
                                        </td>
                                        <td>{{ optional($ticket->created_at)->format('d M Y, h:i A') ?? '—' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#ticket-{{ $ticket->id }}" aria-expanded="false" aria-controls="ticket-{{ $ticket->id }}">
                                                View message
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="p-0 border-0">
                                            <div id="ticket-{{ $ticket->id }}" class="collapse" data-bs-parent="#support-accordion">
                                                <div class="bg-light border-top px-4 py-3 small">
                                                    {{ $ticket->message ?? $ticket->description ?? 'No additional details provided.' }}
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
