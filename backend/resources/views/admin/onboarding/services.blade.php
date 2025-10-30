@extends('layouts.admin')

@section('content')
<style>
    /* Custom Responsive Styles */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 1.5rem;
        color: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
    }
    
    .stat-card.variant-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-card.variant-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-card.variant-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .stat-card.variant-4 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
    
    .stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.9;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        line-height: 1;
    }
    
    .main-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    
    .card-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .header-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }
    
    .badge-custom {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(10px);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .custom-table {
        width: 100%;
        margin: 0;
        font-size: 0.9rem;
    }
    
    .custom-table thead th {
        background: #f8f9fa;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1rem;
        border: none;
        white-space: nowrap;
    }
    
    .custom-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
    }
    
    .custom-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .clinic-name {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.25rem;
    }
    
    .clinic-detail {
        font-size: 0.8rem;
        color: #718096;
        margin: 0.125rem 0;
    }
    
    .doctor-card {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .doctor-card:last-child {
        margin-bottom: 0;
    }
    
    .doctor-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .doctor-name {
        font-weight: 600;
        color: #2d3748;
        font-size: 1rem;
    }
    
    .doctor-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .info-item {
        font-size: 0.85rem;
    }
    
    .info-label {
        color: #718096;
        font-weight: 500;
    }
    
    .info-value {
        color: #2d3748;
    }
    
    .service-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .service-item {
        background: #f8f9fa;
        border-left: 3px solid #667eea;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        border-radius: 4px;
    }
    
    .service-item:last-child {
        margin-bottom: 0;
    }
    
    .service-type {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.25rem;
    }
    
    .service-meta {
        font-size: 0.8rem;
        color: #718096;
    }
    
    .badge-status {
        padding: 0.375rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge-light {
        background: #e9ecef;
        color: #6c757d;
    }
    
    .expandable-row {
        background: #f8f9fa;
    }
    
    .expandable-content {
        padding: 1.5rem;
    }
    
    .section-title {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #718096;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e9ecef;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #718096;
    }
    
    .empty-state svg {
        width: 64px;
        height: 64px;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    /* Responsive Mobile Adjustments */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .stat-card {
            padding: 1.25rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
        }
        
        .card-header-custom {
            padding: 1rem;
        }
        
        .header-title {
            font-size: 1.1rem;
        }
        
        .custom-table {
            font-size: 0.85rem;
        }
        
        .custom-table thead th,
        .custom-table tbody td {
            padding: 0.75rem 0.5rem;
        }
        
        .doctor-info {
            grid-template-columns: 1fr;
        }
        
        .expandable-content {
            padding: 1rem;
        }
    }
    
    @media (max-width: 576px) {
        .container-fluid {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        
        .stat-card {
            padding: 1rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
        }
        
        /* Stack table cells vertically on mobile */
        .custom-table thead {
            display: none;
        }
        
        .custom-table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .custom-table tbody td {
            display: block;
            text-align: left;
            padding: 0.75rem;
            border: none;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .custom-table tbody td:last-child {
            border-bottom: none;
        }
        
        .custom-table tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #718096;
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        
        .expandable-row td {
            padding: 0 !important;
        }
    }
</style>

<div class="container-fluid py-4">
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card variant-1">
            <div class="stat-label">Total Clinics</div>
            <h2 class="stat-value">{{ $stats['total_clinics'] }}</h2>
        </div>
        <div class="stat-card variant-2">
            <div class="stat-label">Clinics with Service Info</div>
            <h2 class="stat-value">{{ $stats['clinics_with_info'] }}</h2>
        </div>
        <div class="stat-card variant-3">
            <div class="stat-label">Doctors</div>
            <h2 class="stat-value">{{ $stats['total_doctors'] }}</h2>
        </div>
        <div class="stat-card variant-4">
            <div class="stat-label">Doctors with Services</div>
            <h2 class="stat-value">{{ $stats['doctors_with_info'] }}</h2>
        </div>
    </div>

    <!-- Main Card -->
    <div class="main-card">
        <div class="card-header-custom">
            <h5 class="header-title">Clinic Service Onboarding</h5>
            <span class="badge-custom">Updated {{ now()->format('d M Y') }}</span>
        </div>
        <div class="table-wrapper">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Clinic</th>
                        <th>Location</th>
                        <th class="text-center">Doctors</th>
                        <th class="text-center">With Services</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clinics as $clinic)
                        <tr>
                            <td data-label="Clinic">
                                <div class="clinic-name">{{ $clinic['clinic_name'] }}</div>
                                <div class="clinic-detail">Slug: {{ $clinic['slug'] ?? '—' }}</div>
                                <div class="clinic-detail">{{ $clinic['email'] ?? '—' }}</div>
                            </td>
                            <td data-label="Location">
                                <div style="font-weight: 500;">{{ $clinic['city'] ?? '—' }}</div>
                                <div class="clinic-detail">Pincode: {{ $clinic['pincode'] ?? '—' }}</div>
                            </td>
                            <td data-label="Doctors" class="text-center">
                                <strong>{{ $clinic['doctor_count'] }}</strong>
                            </td>
                            <td data-label="With Services" class="text-center">
                                <strong>{{ $clinic['doctors_with_services'] }}</strong>
                            </td>
                            <td data-label="Status">
                                @if($clinic['services_info_complete'])
                                    <span class="badge-status badge-success">Complete</span>
                                @else
                                    <span class="badge-status badge-warning">Pending</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="expandable-row">
                            <td colspan="5">
                                <div class="expandable-content">
                                    <div class="section-title">Associated Doctors</div>
                                    @forelse($clinic['doctors'] as $doctor)
                                        <div class="doctor-card">
                                            <div class="doctor-header">
                                                <div class="doctor-name">{{ $doctor['doctor_name'] ?? '—' }}</div>
                                            </div>
                                            <div class="doctor-info">
                                                <div class="info-item">
                                                    <span class="info-label">Email:</span>
                                                    <span class="info-value">{{ $doctor['doctor_email'] ?? '—' }}</span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">Mobile:</span>
                                                    <span class="info-value">{{ $doctor['doctor_mobile'] ?? '—' }}</span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">License:</span>
                                                    <span class="info-value">{{ $doctor['doctor_license'] ?? '—' }}</span>
                                                </div>
                                            </div>
                                            @if(!empty($doctor['services']))
                                                <div style="margin-top: 1rem;">
                                                    <div style="font-weight: 600; margin-bottom: 0.5rem; color: #2d3748;">Services Configured:</div>
                                                    <ul class="service-list">
                                                        @foreach($doctor['services'] as $service)
                                                            <li class="service-item">
                                                                <div class="service-type">{{ ucwords(str_replace('_', ' ', $service['service_type'])) }}</div>
                                                                <div class="service-meta">
                                                                    {{ $service['slot_count'] }} slots available
                                                                    @if(!empty($service['last_created_at']))
                                                                        • Last updated: {{ \Illuminate\Support\Carbon::parse($service['last_created_at'])->format('d M Y H:i') }}
                                                                    @endif
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @else
                                                <div style="margin-top: 1rem;">
                                                    <span class="badge-status badge-light">No services configured</span>
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="empty-state">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-3-3h-2m-8-4a4 4 0 11-8 0 4 4 0 018 0zm-8 8h14v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2z"/>
                                            </svg>
                                            <div>No doctors linked to this clinic</div>
                                        </div>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <div>No clinic records found</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection