@extends('layouts.admin-panel')

@section('page-title', 'Clinic Data Hub')

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="h5 mb-1">Clinic snapshot</h2>
            <p class="text-muted mb-0">Every clinic, its doctors, schedules, emergency coverage, and payments—together on one export-friendly page.</p>
        </div>
        <a href="{{ route('admin.clinic-report.export') }}" class="btn btn-primary btn-lg">
            <i class="bi bi-download me-2"></i>Export Excel
        </a>
    </div>

    <div class="row g-4">
        @forelse($reportRows as $row)
            <div class="col-12">
                <div class="card admin-card p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1">{{ $row['clinic']->name }}</h4>
                            <p class="mb-0 text-muted">{{ $row['clinic']->city ?? 'Location not set' }} • ID {{ $row['clinic']->id }}</p>
                        </div>
                        <div class="text-md-end">
                            <div class="text-muted small">Last updated</div>
                            <div class="fw-semibold">{{ $row['clinic']->updated_at ? $row['clinic']->updated_at->timezone('Asia/Kolkata')->format('d M Y') : '—' }}</div>
                        </div>
                    </div>

                    <div class="row row-cols-2 row-cols-md-4 mt-4 gx-3 gy-2">
                        <div class="col">
                            <div class="text-muted small mb-1">Doctors</div>
                            <div class="fs-4 fw-semibold">{{ $row['doctors_count'] }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small mb-1">Payments</div>
                            <div class="fs-4 fw-semibold">{{ $row['payment_total_display'] }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small mb-1">Transactions</div>
                            <div class="fs-4 fw-semibold">{{ $row['transaction_count'] }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small mb-1">Business hours</div>
                            <div class="text-nowrap text-truncate">{{ $row['business_hours_summary'] }}</div>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush mt-4">
                        <li class="list-group-item px-0 py-3">
                            <strong>In-clinic availability</strong>
                            <p class="mb-0 text-muted">{{ $row['in_clinic_summary'] }}</p>
                        </li>
                        <li class="list-group-item px-0 py-3">
                            <strong>Video availability</strong>
                            <p class="mb-0 text-muted">{{ $row['video_summary'] }}</p>
                        </li>
                        <li class="list-group-item px-0 py-3">
                            <strong>Emergency coverage</strong>
                            <p class="mb-0 text-muted">{{ $row['emergency_summary'] }}</p>
                        </li>
                    </ul>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card admin-card p-4 text-center text-muted">
                    No clinic data available yet.
                </div>
            </div>
        @endforelse
    </div>
@endsection

@push('styles')
    <style>
        .row-cols-2 .text-truncate {
            max-width: 230px;
        }

        @media (max-width: 576px) {
            .card.admin-card {
                border-radius: 1.5rem;
            }
        }
    </style>
@endpush
