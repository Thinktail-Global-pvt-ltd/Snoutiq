@extends('layouts.admin-panel')

@section('page-title', 'Video Slot Overview')

@section('sidebar-secondary')
    <div class="text-uppercase text-white-50 small fw-semibold mb-3">Video Coverage</div>
    <nav class="admin-secondary-nav" data-panel-nav>
        <a href="#overview" class="active">Overview</a>
        <a href="#clinics">Clinics</a>
        <a href="#slot-matrix">Slot Matrix</a>
    </nav>
    <small class="text-white-50 d-block mt-3">Jump between summary cards, clinic details, and the coverage grid.</small>
@endsection

@section('content')
<div class="d-flex flex-column gap-5">
    <section id="overview" class="d-flex flex-column gap-4">
        <div class="card admin-card border-0">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-end gap-4">
                    <div class="flex-grow-1">
                        <h2 class="h4 mb-1">Daily Video Slot Coverage</h2>
                        <p class="text-muted small mb-0">Monitor configured clinics and scheduled doctor availability for live video consultations.</p>
                    </div>
                    <div class="w-100 w-lg-auto" style="max-width: 240px;">
                        <label for="slotDate" class="form-label">Date (IST)</label>
                        <input type="date" id="slotDate" class="form-control">
                    </div>
                    <div class="w-100 w-lg-auto">
                        <label class="form-label opacity-0">Refresh</label>
                        <button id="btnSlotRefresh" class="btn btn-primary w-100">Refresh Grid</button>
                    </div>
                </div>
                <div class="mt-3 text-muted small">
                    Each cell shows the number of doctors scheduled in that hour for the selected pincode.
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <div class="card admin-card border-0 h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-1">Total Clinics</p>
                        <h3 class="fw-bold mb-0">{{ number_format($stats['total_clinics']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card admin-card border-0 h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-1">Clinics with Video</p>
                        <h3 class="fw-bold mb-0">{{ number_format($stats['clinics_with_config']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card admin-card border-0 h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-1">Doctors</p>
                        <h3 class="fw-bold mb-0">{{ number_format($stats['total_doctors']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card admin-card border-0 h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-1">Doctors Ready with Video</p>
                        <h3 class="fw-bold mb-0">{{ number_format($stats['doctors_with_config']) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="clinics">
        <div class="card admin-card border-0">
            <div class="card-header bg-transparent border-bottom-0 pb-0">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
                    <div>
                        <h5 class="mb-1">Video Calling overview</h5>
                        <p class="text-muted small mb-0">Doctors who have video calling availability slots.</p>
                    </div>
                    <span class="badge text-bg-dark align-self-start">Updated {{ now()->format('d M Y') }}</span>
                </div>
            </div>
            <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 240px;">Clinic</th>
                            <th>Location</th>
                            <th class="text-center">Doctors</th>
                            <th class="text-center">With Video</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clinics as $clinic)
                            <tr>
                                <td>
                                    <strong>{{ $clinic['clinic_name'] }}</strong>
                                    <div class="small text-muted">Slug: {{ $clinic['slug'] ?? '—' }}</div>
                                    <div class="small text-muted">Last updated: {{ $clinic['last_updated_at'] ? \Illuminate\Support\Carbon::parse($clinic['last_updated_at'])->format('d M Y H:i') : '—' }}</div>
                                </td>
                                <td>{{ $clinic['city'] ?? '—' }}</td>
                                <td class="text-center">{{ number_format($clinic['doctor_count']) }}</td>
                                <td class="text-center">{{ number_format($clinic['doctors_with_video']) }}</td>
                                <td>
                                    @if($clinic['has_any_video_config'])
                                        <span class="badge bg-success-subtle text-success-emphasis">Configured</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger-emphasis">Not Configured</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="bg-body-tertiary">
                                <td colspan="5" class="p-0">
                                    <div class="px-3 py-3">
                                        <h6 class="text-uppercase text-muted small mb-3">Doctor Details</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Contact</th>
                                                        <th>License</th>
                                                        <th>Video Slots</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($clinic['doctors'] as $doctor)
                                                        <tr>
                                                            <td>{{ $doctor['doctor_name'] ?? '—' }}</td>
                                                            <td class="small">
                                                                <div>Email: {{ $doctor['doctor_email'] ?? '—' }}</div>
                                                                <div>Mobile: {{ $doctor['doctor_mobile'] ?? '—' }}</div>
                                                            </td>
                                                            <td class="small">{{ $doctor['doctor_license'] ?? '—' }}</td>
                                                            <td>
                                                                @if(data_get($doctor, 'video.has_data'))
                                                                    <span class="badge bg-success-subtle text-success-emphasis">Configured</span>
                                                                    <span class="small text-muted d-block mt-1">Slots: {{ number_format(data_get($doctor, 'video.slot_count', 0)) }}</span>
                                                                    @if(data_get($doctor, 'video.last_updated_at'))
                                                                        <span class="small text-muted d-block">Updated: {{ \Illuminate\Support\Carbon::parse(data_get($doctor, 'video.last_updated_at'))->format('d M Y H:i') }}</span>
                                                                    @endif
                                                                @else
                                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Pending</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted">No doctors linked to this clinic.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No clinic records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </section>

    <section id="slot-matrix">
        @include('admin.partials.video-slot-matrix', [
            'slotMatrixCardClass' => 'card admin-card border-0'
        ])
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarNav = document.querySelector('[data-panel-nav]');
        const sidebarLinks = sidebarNav ? Array.from(sidebarNav.querySelectorAll('a[href^="#"]')) : [];
        const sections = sidebarLinks.map(link => document.querySelector(link.getAttribute('href'))).filter(Boolean);
        const scrollOffset = 90;

        function setActive(hash) {
            let matched = false;
            sidebarLinks.forEach(link => {
                const isActive = link.getAttribute('href') === hash;
                link.classList.toggle('active', isActive);
                if (isActive) {
                    matched = true;
                }
            });
            if (!matched && sidebarLinks.length) {
                sidebarLinks.forEach((link, index) => link.classList.toggle('active', index === 0));
            }
        }

        if (sidebarLinks.length) {
            const initial = window.location.hash || '#overview';
            setActive(initial);

            sidebarLinks.forEach(link => {
                link.addEventListener('click', (event) => {
                    const targetSelector = link.getAttribute('href');
                    if (!targetSelector || !targetSelector.startsWith('#')) {
                        return;
                    }
                    const target = document.querySelector(targetSelector);
                    if (!target) {
                        return;
                    }
                    event.preventDefault();
                    const top = target.getBoundingClientRect().top + window.scrollY - scrollOffset;
                    window.scrollTo({ top, behavior: 'smooth' });
                    if (history.replaceState) {
                        history.replaceState(null, '', targetSelector);
                    } else {
                        window.location.hash = targetSelector;
                    }
                    setActive(targetSelector);
                });
            });

            if (window.IntersectionObserver && sections.length) {
                const observer = new IntersectionObserver((entries) => {
                    const visible = entries
                        .filter(entry => entry.isIntersecting)
                        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                    if (!visible) {
                        return;
                    }
                    const hash = `#${visible.target.id}`;
                    setActive(hash);
                }, { rootMargin: '-45% 0px -45% 0px', threshold: [0.25, 0.4, 0.6] });

                sections.forEach(section => observer.observe(section));
            }
        }
    });
</script>
@endpush
