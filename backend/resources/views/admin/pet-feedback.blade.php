@extends('layouts.admin-panel')

@section('page-title', 'Pet Feedback')

@section('content')
@php
    $avgRating = $summary['avg_rating'] ?? null;
    $cards = [
        ['label' => 'Feedback rows', 'value' => number_format($summary['total'] ?? 0), 'icon' => 'bi-chat-square-heart', 'theme' => 'primary'],
        ['label' => 'Average rating', 'value' => $avgRating !== null ? number_format((float) $avgRating, 1) . '/5' : '—', 'icon' => 'bi-star-half', 'theme' => 'warning'],
        ['label' => 'Unique users', 'value' => number_format($summary['unique_users'] ?? 0), 'icon' => 'bi-people', 'theme' => 'success'],
        ['label' => 'Unique pets', 'value' => number_format($summary['unique_pets'] ?? 0), 'icon' => 'bi-heart-pulse', 'theme' => 'danger'],
        ['label' => 'With comments', 'value' => number_format($summary['with_comments'] ?? 0), 'icon' => 'bi-card-text', 'theme' => 'info'],
    ];
@endphp

@if (!$hasTable)
    <div class="alert alert-warning border-0 shadow-sm">
        <code>pet_feedback</code> table is missing. Run the pet feedback migration before using this report.
    </div>
@endif

<div class="d-flex flex-column gap-4">
    <div class="card admin-card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.pet-feedback') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="days" class="form-label small text-muted text-uppercase">Window</label>
                    <select id="days" name="days" class="form-select">
                        @foreach ([7, 14, 30, 60, 90, 180, 365] as $option)
                            <option value="{{ $option }}" @selected((int) $days === $option)>Last {{ $option }} days</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label for="rating" class="form-label small text-muted text-uppercase">Rating</label>
                    <select id="rating" name="rating" class="form-select">
                        <option value="">All ratings</option>
                        @foreach ([5, 4, 3, 2, 1] as $option)
                            <option value="{{ $option }}" @selected((int) $rating === $option)>{{ $option }} star</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label for="q" class="form-label small text-muted text-uppercase">Search</label>
                    <input id="q" name="q" value="{{ $search }}" class="form-control" placeholder="User, phone, pet, doctor, channel, comment">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-funnel me-1"></i> Apply
                    </button>
                    <a href="{{ route('admin.pet-feedback') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        @foreach ($cards as $card)
            <div class="col-12 col-md-6 col-xl">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="avatar-sm rounded-circle bg-{{ $card['theme'] }}-subtle text-{{ $card['theme'] }}-emphasis d-inline-flex align-items-center justify-content-center">
                            <i class="bi {{ $card['icon'] }} fs-4"></i>
                        </span>
                        <div>
                            <p class="text-muted text-uppercase small mb-1">{{ $card['label'] }}</p>
                            <h2 class="fw-bold mb-0">{{ $card['value'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 fw-semibold mb-3">Rating Breakdown</h2>
                    <div class="d-flex flex-column gap-3">
                        @forelse ($ratingBreakdown as $row)
                            @php
                                $total = max(1, (int) ($summary['total'] ?? 0));
                                $percent = ((int) $row->total / $total) * 100;
                            @endphp
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-semibold">{{ $row->rating }} star</span>
                                    <span class="text-muted">{{ number_format($row->total) }}</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" style="width: {{ min(100, $percent) }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No ratings available for this filter.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                        <div>
                            <h2 class="h5 fw-semibold mb-1">Latest Pet Feedback</h2>
                            <p class="text-muted small mb-0">Rows are read directly from <code>pet_feedback</code>.</p>
                        </div>
                        @if (method_exists($feedbackRows, 'total'))
                            <span class="badge text-bg-light align-self-start">{{ number_format($feedbackRows->total()) }} total</span>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Pet</th>
                                    <th>User</th>
                                    <th>Doctor</th>
                                    <th>Rating</th>
                                    <th>Feedback</th>
                                    <th>Channel</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($feedbackRows as $row)
                                    <tr>
                                        <td class="text-muted">#{{ $row->id }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $row->pet_name ?: 'Pet #' . $row->pet_id }}</div>
                                            <div class="text-muted small">ID {{ $row->pet_id }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $row->user_name ?: 'User #' . ($row->user_id ?: '—') }}</div>
                                            <div class="text-muted small">{{ $row->user_phone ?: '—' }}</div>
                                        </td>
                                        <td>{{ $row->doctor_name ?: ($row->vet_id ? 'Doctor #' . $row->vet_id : '—') }}</td>
                                        <td>
                                            @if ($row->rating)
                                                <span class="badge text-bg-warning">{{ $row->rating }}/5</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td style="min-width: 240px;">
                                            <div class="text-wrap">{{ $row->feedback ?: '—' }}</div>
                                            @if ($row->source)
                                                <div class="text-muted small mt-1">Source: {{ $row->source }}</div>
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ $row->channel_name ?: '—' }}</td>
                                        <td class="text-muted small">{{ $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at)->format('d M Y, H:i') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No pet feedback found for this filter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (method_exists($feedbackRows, 'links'))
                        <div class="mt-3">
                            {{ $feedbackRows->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
