{{-- resources/views/clinic/staff-services.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Services & Staff')
@section('page_title','Clinic Operations & Staff')

@section('head')
<style>
  :root {
    --ops-bg: #f7f8fb;
    --ops-surface: #ffffff;
    --ops-muted: #6b7280;
    --ops-line: #e5e7eb;
    --ops-blue: #2563eb;
    --ops-green: #10b981;
    --ops-red: #ef4444;
    --ops-radius: 12px;
    --ops-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
  }

  .ops-shell {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }

  .ops-hero {
    background: #ffffff;
    color: #0f172a;
    border-radius: 14px;
    padding: 16px 18px;
    border: 1px solid var(--ops-line);
    box-shadow: var(--ops-shadow);
  }

  .ops-hero-body {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    align-items: center;
  }

  .ops-hero-title {
    font-size: 24px;
    font-weight: 800;
    color: #111827;
  }

  .ops-hero-sub {
    color: var(--ops-muted);
    max-width: 520px;
    margin-top: 6px;
    line-height: 1.45;
  }

  .ops-eyebrow {
    text-transform: uppercase;
    letter-spacing: .08em;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
  }

  .ops-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
  }

  .pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border-radius: 999px;
    border: 1px solid var(--ops-line);
    background: #f8fafc;
    font-weight: 700;
    font-size: 12px;
    color: #111827;
  }

  .pill-success {
    background: #ecfdf3;
    border-color: #d1fae5;
    color: #047857;
  }

  .pill-danger {
    background: #fef2f2;
    border-color: #fecdd3;
    color: #b91c1c;
  }

  .pill-soft {
    background: #f8fafc;
    color: #334155;
  }

  .ops-hero-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    min-width: 220px;
  }

  .ops-meta-card {
    background: #f8fafc;
    border: 1px solid var(--ops-line);
    border-radius: 12px;
    padding: 10px 12px;
    color: #0f172a;
  }

  .meta-label {
    font-size: 11px;
    color: #6b7280;
    letter-spacing: .06em;
  }

  .meta-value {
    font-weight: 800;
    font-size: 16px;
    margin-top: 4px;
  }

  .ops-panel {
    background: var(--ops-surface);
    border-radius: 14px;
    border: 1px solid var(--ops-line);
    box-shadow: var(--ops-shadow);
  }

  .ops-panel-head {
    padding: 18px 18px 8px;
    display: flex;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
  }

  .ops-panel-title {
    font-size: 18px;
    font-weight: 800;
    color: #111827;
  }

  .ops-panel-sub {
    color: var(--ops-muted);
    font-size: 14px;
  }

  .ops-head-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }

  .ops-filters {
    padding: 0 18px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
  }

  .search-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f3f6ff;
    border: 1px solid #e4e8f4;
    border-radius: 12px;
    padding: 10px 12px;
    min-width: 260px;
    flex: 1;
  }

  .search-wrap svg {
    width: 18px;
    height: 18px;
    color: #6b7280;
  }

  .search-input {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 14px;
    color: #0f172a;
  }

  .ops-filters-right {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .ops-btn-primary {
    background: var(--ops-blue);
    color: #fff;
    padding: 10px 16px;
    border-radius: 12px;
    font-weight: 800;
    box-shadow: none;
  }

  .ops-btn-primary:hover {
    filter: brightness(0.97);
  }

  .ops-btn-secondary {
    background: #fff;
    border: 1px solid #d9dce6;
    color: #374151;
    padding: 10px 14px;
    border-radius: 12px;
    font-weight: 700;
  }

  .table-card {
    padding: 0 18px 18px;
  }

  .services-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    background: transparent;
    border: 1px solid #edf0f8;
    border-radius: 12px;
    overflow: hidden;
  }

  .services-table thead {
    background: #f8fafc;
    color: #0f172a;
  }

  .services-table th {
    padding: 12px 14px;
    text-align: left;
    font-size: 13px;
    font-weight: 700;
    border-bottom: 1px solid #eaecf5;
  }

  .services-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f2f7;
    font-size: 14px;
  }

  .services-table tr:last-child td {
    border-bottom: none;
  }

  .services-table tbody tr:hover {
    background: #f9fafb;
  }

  .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    background: #ecfdf3;
    color: #047857;
  }

  .status-pill.status-inactive {
    background: #f3f4f6;
    color: #4b5563;
  }

  .status-pill .status-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: currentColor;
  }

  .empty-state {
    border: 1px dashed #d7ddea;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    background: #fafbff;
    color: #6b7280;
    margin: 16px 18px 0;
    font-weight: 600;
  }

  .mobile-card {
    border: 1px solid #e6e8f3;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 12px 28px rgba(17, 24, 39, 0.05);
  }

  /* ===============================
     Global / Utility Styles
  =============================== */
  #client-logger {
    font-family: ui-monospace, Menlo, Consolas, monospace;
  }

  /* ===============================
     Responsive Table Wrapper
  =============================== */
  .table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  /* ===============================
     Modal Overlay & Content
  =============================== */
  .modal-overlay {
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
  }

  .modal-content {
    max-height: 95vh;
    overflow-y: auto;
    width: 100%;
    margin: 0 auto;
  }

  @media (min-width: 640px) {
    .modal-content {
      max-width: 42rem; /* ≈672px, comfortable for forms */
    }
  }

  @media (max-width: 640px) {
    .modal-content {
      margin: 1rem;
      padding: 1rem !important;
      border-radius: 1rem;
    }

    .modal-content .grid {
      grid-template-columns: 1fr !important;
    }


    .search-input {
      width: 100% !important;
    }
  }

  /* ===============================
     Mobile Table Card Layout
  =============================== */
  @media (max-width: 768px) {
    .table-container table {
      min-width: 700px;
    }

    .mobile-card {
      display: block;
      margin-bottom: 1rem;
      padding: 1rem;
    }

    .mobile-card .card-row {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 0;
      border-bottom: 1px solid #e5e7eb;
    }

    .mobile-card .card-row:last-child {
      border-bottom: none;
    }

    .mobile-card .card-label {
      font-weight: 600;
      color: #4b5563;
      min-width: 100px;
    }

    .mobile-card .card-value {
      flex: 1;
      text-align: right;
      color: #374151;
    }
  }

  /* ===============================
     Modal Scrollbar Styling
  =============================== */
  .modal-content::-webkit-scrollbar {
    width: 6px;
  }

  .modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  .modal-content::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
  }

  .modal-content::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
  }

  /* ===============================
     Staff section styles
  =============================== */
  #staff-section .role-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
  }

  #staff-section .role-pill.doctor {
    background: #eef2ff;
    color: #3730a3;
  }

  #staff-section .role-pill.receptionist {
    background: #ecfdf5;
    color: #065f46;
  }

  #staff-section .role-pill.clinic {
    background: #fee2e2;
    color: #991b1b;
  }

  .staff-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    z-index: 80;
  }

  .staff-modal-content {
    width: 100%;
    max-width: 480px;
  }
</style>
@endsection

@php
  $isOnboarding = request()->get('onboarding') === '1';
  $onboardingDefaults = [
    'duration' => 30,
    'petType' => 'all',
    'main_service' => 'vet',
    'status' => 'Active',
  ];

  $sessionRole = session('role')
      ?? data_get(session('auth_full'), 'role')
      ?? data_get(session('user'), 'role');

  $doctorId = null;
  if ($sessionRole === 'doctor') {
      $doctorId = session('doctor_id')
          ?? data_get(session('auth_full'), 'doctor_id')
          ?? data_get(session('auth_full'), 'user.doctor_id')
          ?? session('user_id')
          ?? data_get(session('user'), 'id');
  }

  $sessionClinicId = session('clinic_id')
      ?? session('vet_registerations_temp_id')
      ?? session('vet_registeration_id')
      ?? session('vet_id')
      ?? data_get(session('user'), 'clinic_id')
      ?? data_get(session('auth_full'), 'clinic_id')
      ?? data_get(session('auth_full'), 'user.clinic_id')
      ?? null;

  $doctorRecord = null;
  if ($sessionRole === 'doctor' && $doctorId) {
      $doctorRecord = \App\Models\Doctor::query()
          ->select('id', 'doctor_name', 'vet_registeration_id')
          ->find($doctorId);
  }

  $stepStatus = $stepStatus ?? [];
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-4 lg:px-0 ops-shell">
  @if($isOnboarding)
    <div>
      @include('layouts.partials.onboarding-steps', [
        'active' => (int) (request()->get('step', 1)),
        'stepStatus' => $stepStatus,
      ])
    </div>
  @endif

  @if($sessionRole === 'doctor')
    <div class="bg-gradient-to-r from-indigo-50 to-white border border-indigo-100 rounded-xl px-4 py-3 text-sm text-indigo-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 shadow-sm">
      <div>
        <div class="font-semibold text-indigo-950">Logged in as Doctor</div>
        <div>{{ $doctorRecord?->doctor_name ?? 'Doctor' }}</div>
      </div>
      <div class="flex items-center gap-3 text-xs text-indigo-700">
        <span class="px-2 py-1 rounded-lg bg-white border border-indigo-200 font-mono">Doctor ID: {{ $doctorRecord?->id ?? ($doctorId ?? '—') }}</span>
        @if($doctorRecord?->vet_registeration_id)
          <span class="px-2 py-1 rounded-lg bg-white border border-indigo-200 font-mono">Clinic ID: {{ $doctorRecord->vet_registeration_id }}</span>
        @endif
      </div>
    </div>
  @endif

  <div class="flex flex-wrap items-center justify-end gap-2">
    <a href="#services-section" class="ops-btn-secondary text-sm font-semibold">Jump to Services</a>
    <a href="#staff-section" class="ops-btn-primary text-sm font-semibold">Jump to Staff</a>
  </div>

  <div class="ops-hero" id="services-section">
    <div class="ops-hero-body">
      <div>
        <div class="ops-eyebrow">Clinic Operations</div>
        <div class="ops-hero-title">Services</div>
        <p class="ops-hero-sub">Manage services, pricing, durations and visibility for your clinic.</p>
        <div class="ops-chip-row">
          <span class="pill pill-success">Visible</span>
          <span class="pill pill-soft">Sync healthy</span>
        </div>
      </div>
      <div class="ops-hero-meta">
        <div class="ops-meta-card">
          <div class="meta-label">Role</div>
          <div class="meta-value">{{ ucfirst($sessionRole ?? 'Clinic admin') }}</div>
        </div>
        <div class="ops-meta-card">
          <div class="meta-label">Clinic ID</div>
          <div class="meta-value">{{ $sessionClinicId ?? '—' }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="ops-panel">
    <div class="ops-panel-head">
      <div>
        <p class="ops-eyebrow text-slate-500">Service Library</p>
        <div class="ops-panel-title">Services Management</div>
        <p class="ops-panel-sub">Organize services into categories. Edit price, duration and status.</p>
      </div>
      <div class="ops-head-actions">
        <span class="pill pill-soft">Prices in ₹</span>
      </div>
    </div>

    <div class="ops-filters">
      <div class="search-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
        <input id="search" type="text" placeholder="Search service or code..." class="search-input">
      </div>
      <div class="ops-filters-right">
        <button id="btn-open-create" class="ops-btn-primary">
          + Add Service
        </button>
      </div>
    </div>

    <div class="table-card">
      <div class="table-container hidden md:block">
        <table class="min-w-full text-sm services-table">
          <thead>
            <tr>
              <th class="text-left">Name</th>
              <th class="text-left">Pet</th>
              <th class="text-left">Price (₹)</th>
              <th class="text-left">Duration (m)</th>
              <th class="text-left">Category</th>
              <th class="text-left">Status</th>
              <th class="text-left">Actions</th>
            </tr>
          </thead>
          <tbody id="rows"></tbody>
        </table>
      </div>

      <div id="mobile-rows" class="md:hidden p-4 space-y-4"></div>
      <div id="empty" class="hidden empty-state">No services found. Add a service to get started.</div>
    </div>
  </div>

  <section id="staff-section" class="space-y-6">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
      <div class="p-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <p class="text-sm font-semibold text-indigo-600">Staff Directory</p>
          <h2 class="text-2xl font-semibold text-gray-900 mt-1">Keep your clinic team organised</h2>
          <p class="text-sm text-gray-600 mt-2">
            View clinic admins, doctors, and receptionists in one place, update their roles, and onboard new front-desk teammates quickly.
          </p>
        </div>
        <div class="flex flex-col gap-2 items-start">
          <button id="btn-open-staff-modal" class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow">
            + Add Staff
          </button>
          <div class="text-xs text-gray-500">
            Doctors are saved to the doctors table; receptionists go to the receptionist table.
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
      <div class="p-4 border-b flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <input id="staff-search" type="text" placeholder="Search by name or email…" class="w-full md:w-80 bg-gray-100 rounded-lg px-3 py-2 text-sm border border-transparent focus:ring-2 focus:ring-blue-500 focus:bg-white">
        <div class="text-xs text-gray-500 flex items-center gap-2">
          <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
          Role changes are allowed only for doctors and receptionists
        </div>
      </div>
      <div id="staff-loading" class="px-4 py-8 text-center text-sm text-gray-500">Loading staff…</div>
      <div class="table-container hidden" id="staff-table-wrapper">
        <table class="w-full min-w-[720px] text-sm">
          <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
            <tr>
              <th class="text-left px-4 py-3">Name</th>
              <th class="text-left px-4 py-3">Contact</th>
              <th class="text-left px-4 py-3">Role</th>
              <th class="text-left px-4 py-3 w-48">Actions</th>
            </tr>
          </thead>
          <tbody id="staff-rows" class="divide-y divide-gray-100 bg-white"></tbody>
        </table>
      </div>
      <div id="staff-empty" class="hidden px-6 py-10 text-center text-gray-500 text-sm">
        No staff members found yet. Add your first receptionist using the button above.
      </div>
    </div>
  </section>
</div>

<!-- Staff Modal -->
<div id="staff-modal" class="staff-modal-overlay hidden" hidden aria-hidden="true" style="display:none;">
  <div class="staff-modal-content bg-white rounded-2xl shadow-2xl p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <p class="text-xs font-semibold tracking-wide text-indigo-600 uppercase">Add Staff Member</p>
        <h3 class="text-lg font-semibold text-gray-900">Create a receptionist profile</h3>
      </div>
      <button type="button" data-close class="text-gray-500 hover:text-gray-700 text-lg">&times;</button>
    </div>
    <form id="staff-form" class="space-y-4">
      <div>
        <label class="block text-sm font-semibold mb-1">Full name</label>
        <input name="name" type="text" required class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500" placeholder="Receptionist name">
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Email</label>
          <input name="email" type="email" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500" placeholder="optional@clinic.com">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Phone</label>
          <input name="phone" type="text" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500" placeholder="+91 99999 00000">
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Role</label>
        <select name="role" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          <option value="receptionist">Receptionist</option>
          <option value="doctor">Doctor</option>
        </select>
      </div>
      <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
        <button type="button" data-close class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold w-full sm:w-auto">Cancel</button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold w-full sm:w-auto">Save Staff</button>
      </div>
    </form>
  </div>
</div>

<!-- Create Modal -->
<div id="create-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 scroll-m-0">
  <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-3xl p-4 sm:p-6 relative">
    <button type="button" aria-label="Close" class="btn-close absolute top-3 right-3 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
        <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 011.06 0L12 10.94l5.47-5.47a.75.75 0 111.06 1.06L13.06 12l5.47 5.47a.75.75 0 11-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 01-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 010-1.06z" clip-rule="evenodd"/>
      </svg>
    </button>
    <h3 class="text-xl font-semibold text-gray-800 mb-1">Add New Service</h3>
    <p class="text-sm text-gray-500 mb-4">Fill details to create service</p>

    <form id="create-form" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Service Name</label>
          <input name="serviceName" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Price (₹)</label>
          <input name="price" type="number" min="0" step="0.01" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        @unless($isOnboarding)
          <div>
            <label class="block text-sm font-semibold mb-1">Duration (mins)</label>
            <input name="duration" type="number" min="1" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Pet Type</label>
            <select name="petType" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
              <option value="">Select Pet</option>
              <option value="dog">Dog</option>
              <option value="cat">Cat</option>
              <option value="bird">Bird</option>
              <option value="rabbit">Rabbit</option>
              <option value="hamster">Hamster</option>
              <option value="all">All</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Service Category</label>
            <select name="main_service" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
              <option value="">Select</option>
              <option value="grooming">Grooming</option>
              <option value="video_call">Video Call</option>
              <option value="vet">Vet Service</option>
              <option value="pet_walking">Pet Walking</option>
              <option value="sitter">Sitter</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Status</label>
            <select name="status" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        @endunless
      </div>
      @if($isOnboarding)
        <input type="hidden" name="duration" value="{{ $onboardingDefaults['duration'] }}">
        <input type="hidden" name="petType" value="{{ $onboardingDefaults['petType'] }}">
        <input type="hidden" name="main_service" value="{{ $onboardingDefaults['main_service'] }}">
        <input type="hidden" name="status" value="{{ $onboardingDefaults['status'] }}">
      @endif
      <div>
        <label class="block text-sm font-semibold mb-1">Description (optional)</label>
        <textarea name="description" rows="3" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm"></textarea>
      </div>

      <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
        <button type="button" class="btn-close ops-btn-secondary w-full sm:w-auto text-sm font-semibold">Cancel</button>
        <button type="submit" class="ops-btn-primary w-full sm:w-auto text-sm font-semibold">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-3xl p-4 sm:p-6 relative">
    <button type="button" aria-label="Close" class="btn-close absolute top-3 right-3 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
        <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 011.06 0L12 10.94l5.47-5.47a.75.75 0 111.06 1.06L13.06 12l5.47 5.47a.75.75 0 11-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 01-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 010-1.06z" clip-rule="evenodd"/>
      </svg>
    </button>
    <h3 class="text-xl font-semibold text-gray-800 mb-1">Edit Service</h3>
    <p class="text-sm text-gray-500 mb-4">Update details</p>

    <form id="edit-form" class="space-y-4">
      <input type="hidden" name="id">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Service Name</label>
          <input name="serviceName" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Price (₹)</label>
          <input name="price" type="number" min="0" step="0.01" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Duration (mins)</label>
          <input name="duration" type="number" min="1" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Pet Type</label>
          <select name="petType" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="">Select Pet</option>
            <option value="dog">Dog</option>
            <option value="cat">Cat</option>
            <option value="bird">Bird</option>
            <option value="rabbit">Rabbit</option>
            <option value="hamster">Hamster</option>
            <option value="all">All</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Service Category</label>
          <select name="main_service" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="grooming">Grooming</option>
            <option value="video_call">Video Call</option>
            <option value="vet">Vet Service</option>
            <option value="pet_walking">Pet Walking</option>
            <option value="sitter">Sitter</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Status</label>
          <select name="status" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Notes (optional)</label>
        <textarea name="description" rows="3" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm"></textarea>
      </div>

      <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
        <button type="button" class="btn-close ops-btn-secondary w-full sm:w-auto text-sm font-semibold">Cancel</button>
        <button type="submit" class="ops-btn-primary w-full sm:w-auto text-sm font-semibold">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== Frontend Logger + Auth Panel ===== -->
<div id="client-logger" class="hidden fixed bottom-20 right-4 z-[100] w-[90vw] sm:w-[460px] max-h-[72vh] bg-white border border-gray-200 rounded-xl shadow-2xl">
  <div class="flex flex-col sm:flex-row items-center justify-between px-3 py-2 border-b gap-2">
    <div class="text-xs font-bold text-gray-700">Frontend Logger</div>
    <div class="flex items-center gap-2 flex-wrap justify-center">
      <input id="log-token" placeholder="paste Bearer token…" class="px-2 py-1 rounded bg-gray-100 text-xs w-32 sm:w-44">
      <button id="log-token-save" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs">Save</button>
      <button id="log-dump" class="px-2 py-1 rounded bg-gray-100 text-xs hover:bg-gray-200">Download</button>
      <button id="log-clear" class="px-2 py-1 rounded bg-gray-100 text-xs hover:bg-gray-200">Clear</button>
      <button id="log-close" class="px-2 py-1 rounded bg-gray-100 text-xs hover:bg-gray-200">✕</button>
    </div>
  </div>
  <div id="log-body" class="text-[11px] leading-4 text-gray-800 px-3 py-2 overflow-y-auto whitespace-pre-wrap"></div>
</div>
<button id="log-toggle" class="fixed bottom-4 right-4 z-[90] px-3 py-2 rounded-full bg-black text-white text-xs shadow-lg">
  🪶 Logs (<span id="log-count">0</span>)
</button>

@endsection

@section('scripts')
<script>
(() => {
  const SESSION_LOGIN_URL = @json(url('/api/session/login'));
  const SESSION_ROLE = @json($sessionRole);
  const SESSION_DOCTOR_ID = @json($doctorRecord?->id ?? ($doctorId ?? null));
  const SESSION_CLINIC_ID = (() => {
    const raw = @json($sessionClinicId);
    if (raw === null || raw === undefined) return null;
    const num = Number(raw);
    return Number.isFinite(num) && num > 0 ? num : null;
  })();
  const SERVER_USER_ID = (() => {
    if (['doctor','receptionist'].includes(SESSION_ROLE) && SESSION_CLINIC_ID) {
      return SESSION_CLINIC_ID;
    }
    const raw = @json(auth()->id() ?? session('user_id') ?? data_get(session('user'), 'id'));
    if (raw === null || raw === undefined) return null;
    const num = Number(raw);
    return Number.isFinite(num) && num > 0 ? num : null;
  })();

  const AUTH_FULL = (() => {
    try {
      const raw = localStorage.getItem('auth_full') || sessionStorage.getItem('auth_full');
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (_) {
      return null;
    }
  })();

  function pickFirstString(candidates){
    for (const value of candidates){
      if (typeof value === 'string'){
        const trimmed = value.trim();
        if (trimmed) return trimmed;
      }
    }
    return null;
  }

  // ===== CURRENT_USER_ID strictly from frontend =====
  const CURRENT_USER_ID = (() => {
    try {
      const url = new URL(location.href);
      const qRaw = url.searchParams.get('userId') ?? url.searchParams.get('doctorId');
      const qid = Number(qRaw);
      const stg = Number(localStorage.getItem('user_id') || sessionStorage.getItem('user_id'));
      const fromAuth = Number(AUTH_FULL?.user?.id ?? AUTH_FULL?.user_id);
      const candidates = [qid, SERVER_USER_ID, fromAuth, stg];
      for (const value of candidates){
        if (Number.isFinite(value) && value > 0){
          return Number(value);
        }
      }
      return null;
    } catch (_) { return null; }
  })();
  console.log('[services] CURRENT_USER_ID:', CURRENT_USER_ID);
  console.log('[services] SESSION_ROLE:', SESSION_ROLE);
  console.log('[services] SESSION_DOCTOR_ID:', SESSION_DOCTOR_ID);
  console.log('[services] SESSION_CLINIC_ID:', SESSION_CLINIC_ID);

  const CLINIC_SLUG = (() => {
    try {
      const url = new URL(location.href);
      const qSlug = url.searchParams.get('vet_slug') || url.searchParams.get('clinic_slug');
      if (qSlug && qSlug.trim()) return qSlug.trim();
    } catch (_) { /* noop */ }

    const fromStorage = pickFirstString([
      localStorage.getItem('vet_slug'),
      sessionStorage.getItem('vet_slug'),
      localStorage.getItem('clinic_slug'),
      sessionStorage.getItem('clinic_slug'),
    ]);
    if (fromStorage) return fromStorage;

    const fromAuth = pickFirstString([
      AUTH_FULL?.clinic?.slug,
      AUTH_FULL?.clinic?.vet_slug,
      AUTH_FULL?.clinic_slug,
      AUTH_FULL?.vet?.slug,
      AUTH_FULL?.vet_slug,
      AUTH_FULL?.user?.clinic?.slug,
      AUTH_FULL?.user?.clinic_slug,
      AUTH_FULL?.profile?.clinic_slug,
      AUTH_FULL?.profile?.slug,
      AUTH_FULL?.slug,
    ]);
    if (fromAuth) return fromAuth;

    return null;
  })();
  console.log('[services] CLINIC_SLUG:', CLINIC_SLUG);

  // ===== CONFIG (backend endpoints) =====
  const CONFIG = {
    API_BASE: @json(url('/api')),
    CSRF_URL: @json(url('/sanctum/csrf-cookie')),
    LOGIN_API: @json(url('/api/login')),
    SESSION_LOGIN: SESSION_LOGIN_URL,
  };
  function targetQuery(extra={}){
    const params = new URLSearchParams();
    if (CURRENT_USER_ID){
      params.set('user_id', String(CURRENT_USER_ID));
    } else if (CLINIC_SLUG){
      params.set('vet_slug', CLINIC_SLUG);
    }
    Object.entries(extra).forEach(([key,value])=>{
      if (value === undefined || value === null || value === '') return;
      params.set(key, String(value));
    });
    const qs = params.toString();
    return qs ? `?${qs}` : '';
  }

  const API = {
    list:   () => `${CONFIG.API_BASE}/groomer/services${targetQuery()}`,
    create: `${CONFIG.API_BASE}/groomer/service`,
    show:   (id) => `${CONFIG.API_BASE}/groomer/service/${id}${targetQuery()}`,
    update: (id) => `${CONFIG.API_BASE}/groomer/service/${id}/update${targetQuery()}`,
    delete: (id) => `${CONFIG.API_BASE}/groomer/service/${id}${targetQuery()}`,
  };

  function hasTarget(){
    return Boolean(CURRENT_USER_ID || CLINIC_SLUG);
  }

  function appendTarget(formData){
    if (CURRENT_USER_ID){
      formData.append('user_id', String(CURRENT_USER_ID));
    } else if (CLINIC_SLUG){
      formData.append('vet_slug', CLINIC_SLUG);
    }
  }

  function alertMissingTarget(){
    Swal.fire({
      icon: 'warning',
      title: 'user_id missing',
      text: 'Add ?userId=... or ?vet_slug=... to the URL, or log in through the dashboard.',
    });
  }

  // ===== Logger =====
  (function(){
    const ui={panel:document.getElementById('client-logger'),body:document.getElementById('log-body'),toggle:document.getElementById('log-toggle'),count:document.getElementById('log-count'),close:document.getElementById('log-close'),clear:document.getElementById('log-clear'),dump:document.getElementById('log-dump'),tokenI:document.getElementById('log-token'),tokenS:document.getElementById('log-token-save')};
    const MAX=600,buf=[];
    const trunc=(s,n)=>{ if(typeof s!=='string'){ try{s=JSON.stringify(s)}catch(_){s=String(s)} } return s.length>n?s.slice(0,n)+'…':s; };
    const stamp=()=>new Date().toISOString();
    function push(level,msg,meta){ const row={t:stamp(),level,msg,meta}; buf.push(row); if(buf.length>MAX) buf.shift(); const div=document.createElement('div'); div.textContent=`[${row.t}] ${level.toUpperCase()} ${msg}${meta?' '+trunc(meta,1800):''}`; ui.body.appendChild(div); ui.body.scrollTop=ui.body.scrollHeight; ui.count.textContent=String(buf.length); }
    const Log={info:(m,d)=>push('info',m,d? (typeof d==='string'?d:JSON.stringify(d)):''),warn:(m,d)=>push('warn',m,d? (typeof d==='string'?d:JSON.stringify(d)):''),error:(m,d)=>push('error',m,d? (typeof d==='string'?d:JSON.stringify(d)):''),open:()=>ui.panel.classList.remove('hidden'),close:()=>ui.panel.classList.add('hidden'),clear:()=>{ui.body.innerHTML='';buf.length=0;ui.count.textContent='0'},dump:()=>({env:{api:API,login_api:CONFIG.LOGIN_API,token_present:!!(localStorage.getItem('token')||sessionStorage.getItem('token'))},logs:buf})};
    window.ClientLog=Log;

    ui.toggle.addEventListener('click',Log.open);
    ui.close.addEventListener('click',Log.close);
    ui.clear.addEventListener('click',Log.clear);
    ui.dump.addEventListener('click',()=>{ const blob=new Blob([JSON.stringify(Log.dump(),null,2)],{type:'application/json'}); const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='frontend-logs.json'; a.click(); URL.revokeObjectURL(a.href); });
    ui.tokenS.addEventListener('click',()=>{ const t=ui.tokenI.value.trim(); if(!t) return; localStorage.setItem('token',t); sessionStorage.setItem('token',t); Swal.fire({icon:'success',title:'Token saved',timer:1200,showConfirmButton:false}); });

    // instrument fetch
    const origFetch=window.fetch.bind(window);
    window.fetch=async function(input,init={}){
      const url=(typeof input==='string')?input:input.url;
      const method=(init?.method||(typeof input==='object'&&input.method)||'GET').toUpperCase();
      const start=performance.now();
      Log.info('NET:REQUEST', JSON.stringify({method,url,headers:init?.headers||{},cred:init?.credentials||'default'}));
      try{
        const res=await origFetch(input,init);
        const ct=res.headers.get('content-type')||'';
        const ms=Math.round(performance.now()-start);
        Log.info('NET:RESPONSE', JSON.stringify({method,url,status:res.status,ok:res.ok,duration_ms:ms,content_type:ct}));
        return res;
      }catch(err){
        Log.error('NET:FAILED', JSON.stringify({method,url,error:err?.message||String(err)}));
        throw err;
      }
    };
  })();

  // ===== Auth helper (Bearer OR Sanctum cookie) =====
  const Auth = {
    mode: 'unknown', // 'bearer' | 'cookie' | 'none'
    async bootstrap(){
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      if (token){ this.mode='bearer'; return {mode:'bearer'}; }
      try{
        await fetch(CONFIG.CSRF_URL, {credentials:'include'});
        const xsrf = getCookie('XSRF-TOKEN');
        if (xsrf){ this.mode='cookie'; return {mode:'cookie', xsrf}; }
        this.mode='none'; return {mode:'none'};
      }catch{ this.mode='none'; return {mode:'none'}; }
    },
    headers(base={}){
      const h={ 'Accept':'application/json', ...base };
      if (CURRENT_USER_ID){
        h['X-User-Id'] = String(CURRENT_USER_ID);
      } else if (CLINIC_SLUG){
        h['X-Vet-Slug'] = CLINIC_SLUG;
      }
      if (this.mode==='bearer'){
        const token = localStorage.getItem('token') || sessionStorage.getItem('token');
        if (token) h['Authorization']='Bearer '+token;
      } else if (this.mode==='cookie'){
        h['X-Requested-With']='XMLHttpRequest';
        const xsrf = decodeURIComponent(getCookie('XSRF-TOKEN')||'');
        if (xsrf) h['X-XSRF-TOKEN']=xsrf;
      }
      return h;
    },
  };

  function getCookie(name){
    return document.cookie.split('; ').find(r=>r.startsWith(name+'='))?.split('=')[1] || '';
  }

  async function apiFetch(url, opts={}, expectJSON=true){
    const res = await fetch(url, { credentials:'include', ...opts });
    const ct  = res.headers.get('content-type')||'';
    let body;

    if (expectJSON && ct.includes('application/json')){
      const text = await res.text();
      const cleaned = text.replace(/^\uFEFF/, '');
      try {
        body = JSON.parse(cleaned || 'null');
      } catch (parseErr) {
        const err = new Error(`Invalid JSON response: ${parseErr?.message || parseErr}`);
        err.status = res.status;
        err.body = cleaned;
        err.cause = parseErr;
        throw err;
      }
    } else {
      body = await res.text();
    }

    if (!res.ok){
      const msg = (body && body.message) ? body.message : `HTTP ${res.status}`;
      const err = new Error(msg); err.status=res.status; err.body=body; throw err;
    }
    return body;
  }

  // ===== UI helpers =====
  const $ = (s, r=document)=> r.querySelector(s);
  const $$ = (s, r=document)=> Array.from(r.querySelectorAll(s));
  const rows = $('#rows');
  const mobileRows = $('#mobile-rows');
  const empty = $('#empty');
  const search = $('#search');
  const createModal = $('#create-modal');
  const createForm  = document.getElementById('create-form');
  const editModal   = $('#edit-modal');
  const editForm    = document.getElementById('edit-form');
  const open = el => el.classList.remove('hidden');
  const close = el => el.classList.add('hidden');
  const resetCreateForm = () => {
    if (!createForm) return;
    createForm.reset();
    // Force selects to blank/default so data doesn't stick between opens
    ['petType','main_service','status'].forEach(name=>{
      const field = createForm.elements[name];
      if (field && field.tagName === 'SELECT') field.value = '';
    });
  };
  const openCreate = () => { resetCreateForm(); open(createModal); };
  const closeCreate = () => { resetCreateForm(); close(createModal); };
  function esc(s){ return (''+(s??'')).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

  // ===== List + Render =====
  let ALL = [];
  async function fetchServices(){
    if (!CURRENT_USER_ID && !CLINIC_SLUG){
      const helpUrl = `${CONFIG.SESSION_LOGIN}?user_id=YOUR_ID`;
      const extra = 'If you are using a clinic slug, append ?vet_slug=YOUR-SLUG to the page URL.';
      rows.innerHTML = `<tr><td class="px-4 py-6 text-center text-rose-600" colspan="7">user_id missing (add ?userId=... in URL or visit <a class="text-blue-600 underline" target="_blank" rel="noreferrer" href="${esc(helpUrl)}">${esc(CONFIG.SESSION_LOGIN)}?user_id=YOUR_ID</a> then reload). ${esc(extra)}</td></tr>`;
      mobileRows.innerHTML = `<div class="text-center text-rose-600 p-4">user_id missing. Add ?userId=... in URL or visit the login page.</div>`;
      return;
    }
    rows.innerHTML = `<tr><td class="px-4 py-6 text-center text-gray-500" colspan="7">Loading…</td></tr>`;
    mobileRows.innerHTML = `<div class="text-center text-gray-500 p-4">Loading…</div>`;
    try{
      await Auth.bootstrap();
      const res = await apiFetch(API.list(), {
        headers: Auth.headers()
      });
      const items = Array.isArray(res) ? res : Array.isArray(res?.data) ? res.data : [];
      ALL = items;
      render(ALL);
    }catch(e){
      rows.innerHTML = `<tr><td class="px-4 py-6 text-center text-rose-600" colspan="7">Failed to load (${esc(e.message||e)})</td></tr>`;
      mobileRows.innerHTML = `<div class="text-center text-rose-600 p-4">Failed to load (${esc(e.message||e)})</div>`;
      ClientLog?.error('services.load.failed', e.message||String(e));
      ClientLog?.open();
    }
  }

  function render(list){
    rows.innerHTML = '';
    mobileRows.innerHTML = '';
    
    if(!list.length){ 
      empty.classList.remove('hidden'); 
      return; 
    }
    empty.classList.add('hidden');

    // Desktop table view
    for(const it of list){
      const tr = document.createElement('tr');
      tr.className = 'border-t';
      tr.innerHTML = `
        <td class="px-4 py-3 font-medium">${esc(it.name)}</td>
        <td class="px-4 py-3">${esc(it.pet_type || it.petType || '')}</td>
        <td class="px-4 py-3">${Number(it.price).toFixed(2)}</td>
        <td class="px-4 py-3">${it.duration}</td>
        <td class="px-4 py-3">${esc(it.main_service || '')}</td>
        <td class="px-4 py-3">
          <span class="status-pill ${it.status==='Active'?'':'status-inactive'}">
            <span class="status-dot"></span>
            ${esc(it.status || '')}
          </span>
        </td>
        <td class="px-4 py-3">
          <button class="mr-2 text-blue-600 hover:underline" data-act="edit" data-id="${it.id}">Edit</button>
          <button class="text-rose-600 hover:underline" data-act="delete" data-id="${it.id}">Delete</button>
        </td>
      `;
      rows.appendChild(tr);
    }

    // Mobile card view
    for(const it of list){
      const card = document.createElement('div');
      card.className = 'mobile-card bg-white border border-gray-200 rounded-lg p-4 shadow-sm';
      card.innerHTML = `
        <div class="card-row">
          <span class="card-label">Name:</span>
          <span class="card-value font-medium">${esc(it.name)}</span>
        </div>
        <div class="card-row">
          <span class="card-label">Pet:</span>
          <span class="card-value">${esc(it.pet_type || it.petType || '')}</span>
        </div>
        <div class="card-row">
          <span class="card-label">Price:</span>
          <span class="card-value">₹${Number(it.price).toFixed(2)}</span>
        </div>
        <div class="card-row">
          <span class="card-label">Duration:</span>
          <span class="card-value">${it.duration} mins</span>
        </div>
        <div class="card-row">
          <span class="card-label">Category:</span>
          <span class="card-value">${esc(it.main_service || '')}</span>
        </div>
        <div class="card-row">
          <span class="card-label">Status:</span>
          <span class="card-value">
            <span class="status-pill ${it.status==='Active'?'':'status-inactive'}">
              <span class="status-dot"></span>
              ${esc(it.status || '')}
            </span>
          </span>
        </div>
        <div class="card-row">
          <span class="card-label">Actions:</span>
          <span class="card-value">
            <button class="text-blue-600 hover:underline mr-3" data-act="edit" data-id="${it.id}">Edit</button>
            <button class="text-rose-600 hover:underline" data-act="delete" data-id="${it.id}">Delete</button>
          </span>
        </div>
      `;
      mobileRows.appendChild(card);
    }
  }

  // Search
  search.addEventListener('input', e=>{
    const q = e.target.value.toLowerCase().trim();
    const filtered = !q ? ALL : ALL.filter(x => (x.name||'').toLowerCase().includes(q));
    render(filtered);
  });

  // ===== Create =====
  document.getElementById('btn-open-create').addEventListener('click', openCreate);
  $$('.btn-close', createModal).forEach(b=> b.addEventListener('click', closeCreate));

  document.getElementById('create-form').addEventListener('submit', async (e)=>{
    e.preventDefault();
    if (!hasTarget()){ alertMissingTarget(); return; }

    const fd = new FormData(e.target);
    const payload = new FormData();
    payload.append('serviceName',  fd.get('serviceName'));
    payload.append('description',  fd.get('description') || '');
    payload.append('petType',      fd.get('petType'));
    payload.append('price',        fd.get('price'));
    payload.append('duration',     fd.get('duration'));
    payload.append('main_service', fd.get('main_service'));
    payload.append('status',       fd.get('status'));
    // ✅ send user_id from frontend (or vet_slug when available)
    appendTarget(payload);

    try{
      await Auth.bootstrap();
      const res = await apiFetch(API.create, {
        method:'POST',
        headers: Auth.headers(),
        body: payload
      });
      Swal.fire({icon:'success', title:'Service Created', text:'Service was created successfully', timer:1500, showConfirmButton:false});
      closeCreate();
      await fetchServices();
      ClientLog?.info('service.create.success', JSON.stringify(res).slice(0,800));
      // If onboarding is active, move to Step 2 (Video Calling Schedule)
      try{
        const url = new URL(location.href);
        if ((url.searchParams.get('onboarding')||'') === '1'){
          const PATH_PREFIX = location.pathname.startsWith('/backend') ? '/backend' : '';
          setTimeout(()=>{
            window.location.href = `${window.location.origin}${PATH_PREFIX}/doctor/video-calling-schedule/manage?onboarding=1&step=2`;
          }, 600);
        }
      }catch(_){ }
    }catch(err){
      Swal.fire({icon:'error', title:'Create failed', text: err.message || 'Error'});
      ClientLog?.error('service.create.failed', err.message||String(err));
      ClientLog?.open();
    }
  });

  // ===== Edit/Delete actions =====
  function attachActionListeners(){
    // Desktop table
    rows.addEventListener('click', handleActionClick);
    // Mobile cards
    mobileRows.addEventListener('click', handleActionClick);
  }

  async function handleActionClick(e){
    const btn = e.target.closest('button[data-act]');
    if(!btn) return;
    const {act, id} = btn.dataset;

    if(act==='edit'){
      try{
        await Auth.bootstrap();
        const data = await apiFetch(API.show(id), { headers: Auth.headers() });
        const s = data?.data || data;
        fillEdit(s);
        open(editModal);
      }catch(err){
        Swal.fire({icon:'error', title:'Failed to load service', text: err.message||'Error'});
        ClientLog?.error('service.show.failed', err.message||String(err));
      }
    }

    if(act==='delete'){
      const ok = await Swal.fire({
        icon:'warning',
        title:'Delete this service?',
        text:'This action cannot be undone.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete',
        cancelButtonText:'Cancel'
      });
      if(!ok.isConfirmed) return;

      try{
        await Auth.bootstrap();
        // DELETE with header + query (user_id / vet_slug)
        await apiFetch(API.delete(id), {
          method:'DELETE',
          headers: Auth.headers()
        }, true);
        Swal.fire({icon:'success', title:'Deleted', timer:1200, showConfirmButton:false});
        await fetchServices();
      }catch(err){
        // Fallback: POST override if server blocks DELETE
        try{
          const payload = new FormData();
          appendTarget(payload);
          await apiFetch(API.delete(id), {
            method:'POST',
            headers: Auth.headers({'X-HTTP-Method-Override':'DELETE'}),
            body: payload
          }, true);
          Swal.fire({icon:'success', title:'Deleted', timer:1200, showConfirmButton:false});
          await fetchServices();
        }catch(err2){
          Swal.fire({icon:'error', title:'Delete failed', text: err2.message || 'Error'});
          ClientLog?.error('service.delete.failed', err2.message||String(err2));
          ClientLog?.open();
        }
      }
    }
  }

  function fillEdit(s){
    const f = editForm;
    f.elements['id'].value = s.id;
    f.elements['serviceName'].value = s.name || '';
    f.elements['description'].value = s.description || '';
    f.elements['petType'].value = s.pet_type || s.petType || '';
    f.elements['price'].value = s.price || 0;
    f.elements['duration'].value = s.duration || 0;
    f.elements['main_service'].value = s.main_service || '';
    f.elements['status'].value = s.status || 'Active';
  }

  $$('.btn-close', editModal).forEach(b=> b.addEventListener('click', ()=> close(editModal)));

  document.getElementById('edit-form').addEventListener('submit', async (e)=>{
    e.preventDefault();
    if (!hasTarget()){ alertMissingTarget(); return; }

    const f = e.target;
    const id = f.elements['id'].value;
    const payload = new FormData();
    payload.append('serviceName',  f.elements['serviceName'].value);
    payload.append('description',  f.elements['description'].value || '');
    payload.append('petType',      f.elements['petType'].value);
    payload.append('price',        f.elements['price'].value);
    payload.append('duration',     f.elements['duration'].value);
    payload.append('main_service', f.elements['main_service'].value);
    payload.append('status',       f.elements['status'].value);
    // ✅ send user_id from frontend (or vet_slug when available)
    appendTarget(payload);

    try{
      await Auth.bootstrap();
      const res = await apiFetch(API.update(id), {
        method:'POST',
        headers: Auth.headers(),
        body: payload
      });
      Swal.fire({icon:'success', title:'Updated', timer:1200, showConfirmButton:false});
      close(editModal);
      await fetchServices();
      ClientLog?.info('service.update.success', JSON.stringify(res).slice(0,800));
    }catch(err){
      // Fallback: PUT w/ override
      try{
        const res2 = await apiFetch(`${CONFIG.API_BASE}/groomer/services/${id}${targetQuery()}`, {
          method:'POST',
          headers: Auth.headers({'X-HTTP-Method-Override':'PUT'}),
          body: payload
        });
        Swal.fire({icon:'success', title:'Updated', timer:1200, showConfirmButton:false});
        close(editModal);
        await fetchServices();
        ClientLog?.info('service.update.success(fallback)', JSON.stringify(res2).slice(0,800));
      }catch(err2){
        Swal.fire({icon:'error', title:'Update failed', text: err2.message || err.message || 'Error'});
        ClientLog?.error('service.update.failed', err2.message||String(err2));
        ClientLog?.open();
      }
    }
  });

  // ===== Init =====
  document.addEventListener('DOMContentLoaded', async ()=>{
    await fetchServices();
    attachActionListeners();
  });

  // ===== Logger hotkey =====
  (function(){
    const uiToggle=document.getElementById('log-toggle');
    const uiPanel=document.getElementById('client-logger');
    const uiClose=document.getElementById('log-close');
    uiToggle.addEventListener('click', ()=> uiPanel.classList.remove('hidden'));
    uiClose.addEventListener('click', ()=> uiPanel.classList.add('hidden'));
    window.addEventListener('keydown',e=>{ if((e.ctrlKey||e.metaKey)&&e.key==='`'){ e.preventDefault(); uiPanel.classList.toggle('hidden'); }});
  })();
})(); // end services bundle

(() => {
  const SESSION_LOGIN_URL = @json(url('/api/session/login'));
  const SESSION_ROLE = @json($sessionRole);
  const SESSION_DOCTOR_ID = @json($doctorRecord?->id ?? ($doctorId ?? null));
  const SESSION_CLINIC_ID = (() => {
    const raw = @json($sessionClinicId);
    if (raw === null || raw === undefined) return null;
    const num = Number(raw);
    return Number.isFinite(num) && num > 0 ? num : null;
  })();
  const SERVER_USER_ID = (() => {
    if (['doctor','receptionist'].includes(SESSION_ROLE) && SESSION_CLINIC_ID) {
      return SESSION_CLINIC_ID;
    }
    const raw = @json(auth()->id() ?? session('user_id') ?? data_get(session('user'), 'id'));
    if (raw === null || raw === undefined) return null;
    const num = Number(raw);
    return Number.isFinite(num) && num > 0 ? num : null;
  })();

  const AUTH_FULL = (() => {
    try {
      const raw = localStorage.getItem('auth_full') || sessionStorage.getItem('auth_full');
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (_) {
      return null;
    }
  })();

  function pickFirstString(candidates) {
    for (const value of candidates) {
      if (typeof value === 'string') {
        const trimmed = value.trim();
        if (trimmed) return trimmed;
      }
    }
    return null;
  }

  const CURRENT_USER_ID = (() => {
    try {
      const url = new URL(location.href);
      const qRaw = url.searchParams.get('userId') ?? url.searchParams.get('doctorId');
      const qid = Number(qRaw);
      const stg = Number(localStorage.getItem('user_id') || sessionStorage.getItem('user_id'));
      const fromAuth = Number(AUTH_FULL?.user?.id ?? AUTH_FULL?.user_id);
      const candidates = [qid, SERVER_USER_ID, fromAuth, stg];
      for (const value of candidates) {
        if (Number.isFinite(value) && value > 0) {
          return Number(value);
        }
      }
      return null;
    } catch (_) { return null; }
  })();

  const CLINIC_SLUG = (() => {
    try {
      const url = new URL(location.href);
      const qSlug = url.searchParams.get('vet_slug') || url.searchParams.get('clinic_slug');
      if (qSlug && qSlug.trim()) return qSlug.trim();
    } catch (_) {}

    const fromStorage = pickFirstString([
      localStorage.getItem('vet_slug'),
      sessionStorage.getItem('vet_slug'),
      localStorage.getItem('clinic_slug'),
      sessionStorage.getItem('clinic_slug'),
    ]);
    if (fromStorage) return fromStorage;

    const fromAuth = pickFirstString([
      AUTH_FULL?.clinic?.slug,
      AUTH_FULL?.clinic?.vet_slug,
      AUTH_FULL?.clinic_slug,
      AUTH_FULL?.vet?.slug,
      AUTH_FULL?.vet_slug,
      AUTH_FULL?.user?.clinic?.slug,
      AUTH_FULL?.user?.clinic_slug,
      AUTH_FULL?.profile?.clinic_slug,
      AUTH_FULL?.profile?.slug,
      AUTH_FULL?.slug,
    ]);
    if (fromAuth) return fromAuth;

    return null;
  })();

  const CONFIG = {
    API_BASE: @json(url('/api')),
    CSRF_URL: @json(url('/sanctum/csrf-cookie')),
  };

  function targetQuery(extra = {}) {
    const params = new URLSearchParams();
    if (CURRENT_USER_ID) {
      params.set('user_id', String(CURRENT_USER_ID));
    } else if (CLINIC_SLUG) {
      params.set('vet_slug', CLINIC_SLUG);
    }
    Object.entries(extra).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return;
      params.set(key, String(value));
    });
    const qs = params.toString();
    return qs ? `?${qs}` : '';
  }

  function appendTarget(formData) {
    if (CURRENT_USER_ID) {
      formData.append('user_id', String(CURRENT_USER_ID));
    } else if (CLINIC_SLUG) {
      formData.append('vet_slug', CLINIC_SLUG);
    }
  }

  function hasTarget() {
    return Boolean(CURRENT_USER_ID || CLINIC_SLUG);
  }

  function alertMissingTarget() {
    Swal.fire({
      icon: 'warning',
      title: 'Link a clinic to continue',
      text: 'Add ?userId=<clinicId> or ?vet_slug=<clinic_slug> to the URL, or open this page from the dashboard.',
    });
  }

  function getCookie(name) {
    return document.cookie.split('; ').find(r => r.startsWith(name + '='))?.split('=')[1] ?? '';
  }

  const Auth = {
    mode: 'unknown',
    async bootstrap() {
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      if (token) {
        this.mode = 'bearer';
        return { mode: 'bearer' };
      }
      try {
        await fetch(CONFIG.CSRF_URL, { credentials: 'include' });
        const xsrf = getCookie('XSRF-TOKEN');
        if (xsrf) {
          this.mode = 'cookie';
          return { mode: 'cookie', xsrf };
        }
        this.mode = 'none';
        return { mode: 'none' };
      } catch {
        this.mode = 'none';
        return { mode: 'none' };
      }
    },
    headers(base = {}) {
      const headers = { 'Accept': 'application/json', ...base };
      if (CURRENT_USER_ID) {
        headers['X-User-Id'] = String(CURRENT_USER_ID);
      } else if (CLINIC_SLUG) {
        headers['X-Vet-Slug'] = CLINIC_SLUG;
      }
      if (this.mode === 'bearer') {
        const token = localStorage.getItem('token') || sessionStorage.getItem('token');
        if (token) headers['Authorization'] = 'Bearer ' + token;
      } else if (this.mode === 'cookie') {
        headers['X-Requested-With'] = 'XMLHttpRequest';
        const xsrf = decodeURIComponent(getCookie('XSRF-TOKEN') || '');
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
      }
      return headers;
    },
  };

  async function apiFetch(url, opts = {}) {
    const res = await fetch(url, { credentials: 'include', ...opts });
    if (!res.ok) {
      const text = await res.text();
      throw new Error(text || 'Request failed');
    }
    const ct = res.headers.get('content-type') || '';
    if (ct.includes('application/json')) {
      return await res.json();
    }
    return await res.text();
  }

  const API = {
    list: () => `${CONFIG.API_BASE}/staff${targetQuery()}`,
    addReceptionist: `${CONFIG.API_BASE}/staff/receptionists`,
    updateRole: (type, id) => `${CONFIG.API_BASE}/staff/${type}/${id}/role${targetQuery()}`,
  };

  const staffRows = document.getElementById('staff-rows');
  const staffSearch = document.getElementById('staff-search');
  const staffEmpty = document.getElementById('staff-empty');
  const staffLoading = document.getElementById('staff-loading');
  const tableWrapper = document.getElementById('staff-table-wrapper');
  const addModal = document.getElementById('staff-modal');
  const addForm = document.getElementById('staff-form');
  const addBtn = document.getElementById('btn-open-staff-modal');
  let ALL_STAFF = [];
  let ALLOWED_ROLES = ['doctor', 'receptionist'];

  function formatRole(role) {
    if (role === 'doctor') return 'Doctor';
    if (role === 'receptionist') return 'Receptionist';
    if (role === 'clinic_admin') return 'Clinic Admin';
    return role ? role.replace(/_/g, ' ') : '—';
  }

  function renderStaff(list) {
    staffRows.innerHTML = '';
    if (!list.length) {
      staffEmpty.classList.remove('hidden');
      tableWrapper.classList.add('hidden');
      return;
    }
    staffEmpty.classList.add('hidden');
    tableWrapper.classList.remove('hidden');

    list.forEach((item) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="px-4 py-4 align-top">
          <div class="font-semibold text-gray-900">${item.name || '—'}</div>
          <div class="text-xs text-gray-500 capitalize">${item.type === 'clinic' ? 'Clinic Admin' : item.type}</div>
        </td>
        <td class="px-4 py-4 align-top">
          <div class="text-sm text-gray-900">${item.email || '—'}</div>
          <div class="text-xs text-gray-500">${item.phone || '—'}</div>
        </td>
        <td class="px-4 py-4 align-top">
          <span class="role-pill ${item.type === 'clinic' ? 'clinic' : (item.role || '').toLowerCase()}">${formatRole(item.role)}</span>
        </td>
        <td class="px-4 py-4 align-top">
          ${item.editable ? renderRoleSelect(item) : '<span class="text-xs text-gray-400">Locked</span>'}
        </td>
      `;
      staffRows.appendChild(tr);
    });
  }

  function renderRoleSelect(item) {
    const options = (ALLOWED_ROLES || ['doctor', 'receptionist'])
      .map((role) => {
        const selected = role === item.role ? 'selected' : '';
        const label = role === 'doctor' ? 'Doctor' : 'Receptionist';
        return `<option value="${role}" ${selected}>${label}</option>`;
      })
      .join('');
    return `
      <select data-role-picker data-id="${item.id}" data-type="${item.type}" data-prev="${item.role}" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        ${options}
      </select>
    `;
  }

  function normalizeStaff(data) {
    const rows = [];
    if (data?.clinic_admin) {
      rows.push({
        ...data.clinic_admin,
        editable: false,
      });
    }
    (data?.doctors || []).forEach((doc) => {
      rows.push({
        ...doc,
        editable: true,
      });
    });
    (data?.receptionists || []).forEach((rec) => {
      rows.push({
        ...rec,
        editable: true,
      });
    });
    return rows;
  }

  async function fetchStaff() {
    if (!hasTarget()) {
      alertMissingTarget();
      return;
    }
    staffLoading.classList.remove('hidden');
    tableWrapper.classList.add('hidden');
    staffEmpty.classList.add('hidden');
    try {
      await Auth.bootstrap();
      const res = await apiFetch(API.list(), { headers: Auth.headers() });
      const payload = res?.data ?? res;
      ALLOWED_ROLES = payload?.editable_roles ?? ['doctor', 'receptionist'];
      ALL_STAFF = normalizeStaff(payload);
      renderStaff(ALL_STAFF);
    } catch (err) {
      console.error(err);
      Swal.fire({
        icon: 'error',
        title: 'Could not load staff',
        text: err.message || 'Unknown error',
      });
    } finally {
      staffLoading.classList.add('hidden');
    }
  }

  function openModal() {
    addModal.style.display = 'flex';
    addModal.classList.remove('hidden');
    addModal.removeAttribute('hidden');
    addModal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    addModal.style.display = 'none';
    addModal.classList.add('hidden');
    addModal.setAttribute('hidden', 'hidden');
    addModal.setAttribute('aria-hidden', 'true');
    addForm.reset();
  }

  addBtn.addEventListener('click', () => {
    if (!hasTarget()) {
      alertMissingTarget();
      return;
    }
    openModal();
  });

  addModal.querySelectorAll('[data-close]').forEach((btn) => {
    btn.addEventListener('click', closeModal);
  });

  addModal.addEventListener('click', (event) => {
    if (event.target === addModal) {
      closeModal();
    }
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !addModal.classList.contains('hidden')) {
      closeModal();
    }
  });

  // Ensure modal starts hidden even if cached markup misses the hidden class
  closeModal();

  addForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!hasTarget()) {
      alertMissingTarget();
      return;
    }
    const formData = new FormData(addForm);
    appendTarget(formData);
    try {
      await Auth.bootstrap();
      await apiFetch(API.addReceptionist, {
        method: 'POST',
        headers: Auth.headers(),
        body: formData,
      });
      Swal.fire({
        icon: 'success',
        title: 'Staff added',
        timer: 1500,
        showConfirmButton: false,
      });
      closeModal();
      await fetchStaff();
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Unable to add staff',
        text: err.message || 'Unknown error',
      });
    }
  });

  staffRows.addEventListener('change', async (e) => {
    const select = e.target.closest('select[data-role-picker]');
    if (!select) return;
    const { id, type } = select.dataset;
    const newRole = select.value;
    const prevRole = select.getAttribute('data-prev') || '';
    if (!id || !type) return;
    try {
      await Auth.bootstrap();
      const payload = new FormData();
      payload.append('role', newRole);
      appendTarget(payload);
      try {
        await apiFetch(API.updateRole(type, id), {
          method: 'PATCH',
          headers: Auth.headers(),
          body: payload,
        });
      } catch (err) {
        await apiFetch(API.updateRole(type, id), {
          method: 'POST',
          headers: Auth.headers({ 'X-HTTP-Method-Override': 'PATCH' }),
          body: payload,
        });
      }
      select.setAttribute('data-prev', newRole);
      await fetchStaff();
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Role update failed',
        text: err.message || 'Unknown error',
      });
      if (prevRole) {
        select.value = prevRole;
      }
    }
  });

  staffSearch.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase().trim();
    if (!query) {
      renderStaff(ALL_STAFF);
      return;
    }
    const filtered = ALL_STAFF.filter((item) => {
      return (
        (item.name || '').toLowerCase().includes(query) ||
        (item.email || '').toLowerCase().includes(query)
      );
    });
    renderStaff(filtered);
  });

  document.addEventListener('DOMContentLoaded', fetchStaff);
})();
</script>
@endsection
