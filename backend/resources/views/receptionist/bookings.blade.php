@extends('layouts.snoutiq-dashboard')

@section('title','Receptionist Bookings')
@section('page_title','Receptionist Bookings')

@section('head')
<style>
  .table-card {
    background: #fff;
    border-radius: 1.25rem;
    border: 1px solid rgba(15,23,42,0.08);
    box-shadow: 0 25px 60px rgba(15,23,42,0.08);
  }
  .table-card thead {
    background: linear-gradient(120deg, rgba(99,102,241,0.08), rgba(79,70,229,0.05));
  }
  .status-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.55rem;
    font-size: 0.7rem;
    border-radius: 999px;
    font-weight: 600;
  }
  .status-scheduled { background: rgba(14,165,233,0.1); color: #0369a1; }
  .status-pending { background: rgba(234,179,8,0.15); color: #92400e; }
  .status-completed { background: rgba(34,197,94,0.12); color: #0f766e; }
  .status-cancelled { background: rgba(248,113,113,0.15); color: #b91c1c; }

  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,0.65);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    z-index: 80;
  }
  .modal-overlay.active { display: flex; }
  .modal-card {
    width: 100%;
    max-width: 640px;
  }
  .tab-button {
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: background .2s;
  }
  .tab-button.active {
    background: #4f46e5;
    color: #fff;
  }
  .schedule-section-row td {
    background: #f8fafc;
    color: #475569;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  @media (max-width: 640px) {
    .table-card table {
      min-width: 720px;
    }
  }
</style>
@endsection

@php
  $bookingContext = \App\Services\ReceptionistBookingContext::resolve($viewMode ?? 'create');
  extract($bookingContext);
@endphp

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
  @if($viewMode === 'create')
  <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
      <p class="text-sm font-semibold text-indigo-600">Clinic Front Desk</p>
      <h2 class="text-2xl font-semibold text-slate-900 mt-1">Manage appointments without leaving the desk</h2>
      <p class="text-sm text-slate-600 mt-2">
        View every booking for your clinic, schedule new in-clinic visits, and create patient records directly from this panel.
      </p>
    </div>
    <button id="btn-open-booking" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow">
      + New Booking
    </button>
  </div>
  @endif

  @if($viewMode === 'schedule')
  <div class="table-card overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <div class="text-sm font-semibold text-slate-800">Doctor Schedule</div>
        <p class="text-xs text-slate-500">Pulled from /api/appointments/by-doctor</p>
      </div>
      <div class="text-xs text-slate-500">Select a doctor below to update this table</div>
    </div>
    <div class="px-4 pt-3 pb-2 border-b border-slate-100">
      <label class="text-xs uppercase tracking-wide text-slate-500">Doctor</label>
      <select id="doctor-card-select" class="mt-1 w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
        <option value="">Select doctor</option>
      </select>
    </div>
    <div id="doctor-loading" class="p-6 text-center text-sm text-slate-500">Pick a doctor to load appointments…</div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm hidden" id="doctor-table">
        <thead class="text-xs uppercase text-slate-600">
          <tr>
            <th class="px-4 py-3 text-left">Slot</th>
            <th class="px-4 py-3 text-left">Patient</th>
            <th class="px-4 py-3 text-left">Pet</th>
            <th class="px-4 py-3 text-left">Status</th>
          </tr>
        </thead>
        <tbody id="doctor-rows" class="divide-y divide-slate-100 bg-white"></tbody>
      </table>
    </div>
    <div id="doctor-empty" class="hidden p-10 text-center text-slate-500 text-sm">
      No appointments scheduled for the selected doctor.
    </div>
  </div>
  @endif

  @if($viewMode === 'history')
  <div class="table-card overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <div class="text-sm font-semibold text-slate-800">Patient History</div>
        <p class="text-xs text-slate-500">Powered by /api/appointments/by-doctor & /api/receptionist/bookings</p>
      </div>
      <div class="text-xs text-slate-500">Showing recent bookings for this clinic</div>
    </div>
    <div id="patient-loading" class="p-6 text-center text-sm text-slate-500">Loading history…</div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm hidden" id="patient-table">
        <thead class="text-xs uppercase text-slate-600">
          <tr>
            <th class="px-4 py-3 text-left">Date</th>
            <th class="px-4 py-3 text-left">Patient</th>
            <th class="px-4 py-3 text-left">Doctor</th>
            <th class="px-4 py-3 text-left">Clinic</th>
            <th class="px-4 py-3 text-left">Status</th>
          </tr>
        </thead>
        <tbody id="patient-rows" class="divide-y divide-slate-100 bg-white"></tbody>
      </table>
    </div>
    <div id="patient-empty" class="hidden p-10 text-center text-slate-500 text-sm">
      No appointments found yet.
    </div>
  </div>
  @endif
</div>

@include('receptionist.partials.booking-modal')
@endsection

@section('scripts')
@include('receptionist.partials.booking-scripts')
@endsection
