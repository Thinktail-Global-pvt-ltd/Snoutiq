@extends('layouts.snoutiq-dashboard')

@section('title','Receptionist Front Desk')
@section('page_title','Receptionist Front Desk')

@php
  $bookingContext = \App\Services\ReceptionistBookingContext::resolve('search');
  extract($bookingContext);
@endphp

@section('head')
<style>
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
</style>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
  <div class="bg-white border border-slate-100 rounded-2xl shadow p-6 space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Front Desk</p>
        <h2 class="text-2xl font-semibold text-slate-900 mt-1">Search & prepare bookings</h2>
        <p class="text-sm text-slate-500 mt-1">Look up patients by name, phone or email before creating a booking.</p>
      </div>
      <button
        id="front-desk-new-patient"
        type="button"
        class="inline-flex items-center justify-center px-5 py-3 rounded-lg bg-emerald-600 text-white text-sm font-semibold shadow hover:bg-emerald-700 focus-visible:outline-emerald-500"
      >New Patient</button>
    </div>
    <form id="receptionist-patient-search" class="space-y-3">
      <label class="text-xs uppercase tracking-wide text-slate-500">Patient lookup</label>
      <div class="flex flex-col gap-3 sm:flex-row">
        <input id="patient-search-field" type="search" class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500" placeholder="Name, email or mobile number">
        <button id="patient-search-button" type="submit" class="inline-flex items-center justify-center px-5 py-3 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Search</button>
      </div>
      <p id="patient-search-status" class="text-sm text-slate-500">Enter a search term to check if a record already exists.</p>
    </form>
  </div>

  <div id="patient-search-result" class="hidden bg-white border border-slate-100 rounded-2xl shadow p-6 space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <p id="patient-search-summary" class="text-xs uppercase tracking-wide text-slate-500"></p>
        <h3 id="patient-search-name" class="text-2xl font-semibold text-slate-900">No result yet</h3>
        <p id="patient-search-meta" class="text-sm text-slate-500"></p>
      </div>
      <span id="patient-search-pets" class="text-xs font-semibold text-emerald-600"></span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <p class="text-xs uppercase tracking-wide text-slate-500">Phone</p>
        <p id="patient-search-phone" class="text-sm text-slate-900">-</p>
      </div>
      <div>
        <p class="text-xs uppercase tracking-wide text-slate-500">Email</p>
        <p id="patient-search-email" class="text-sm text-slate-900">-</p>
      </div>
    </div>
    <p id="patient-search-empty" class="text-sm text-slate-500"></p>
    <div class="flex flex-wrap gap-3">
      <button id="patient-search-existing" type="button" class="inline-flex items-center justify-center px-5 py-3 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed hidden" disabled>Create Booking</button>
      <button id="patient-search-new" type="button" class="inline-flex items-center justify-center px-5 py-3 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-100 hidden">Create New Patient</button>
    </div>
  </div>
</div>

@include('receptionist.partials.booking-modal')
@endsection

@section('scripts')
@include('receptionist.partials.booking-scripts')
<script>
  (function() {
    const searchForm = document.getElementById('receptionist-patient-search');
    const searchInput = document.getElementById('patient-search-field');
    const statusEl = document.getElementById('patient-search-status');
    const resultCard = document.getElementById('patient-search-result');
    const summaryEl = document.getElementById('patient-search-summary');
    const nameEl = document.getElementById('patient-search-name');
    const metaEl = document.getElementById('patient-search-meta');
    const petsEl = document.getElementById('patient-search-pets');
    const phoneEl = document.getElementById('patient-search-phone');
    const emailEl = document.getElementById('patient-search-email');
    const noteEl = document.getElementById('patient-search-empty');
    const existingBtn = document.getElementById('patient-search-existing');
    const newBtn = document.getElementById('patient-search-new');
    const frontDeskNewPatientBtn = document.getElementById('front-desk-new-patient');
    let currentPatient = null;
    let searchTimer;

    function safelySet(el, value) {
      if (el) {
        el.textContent = value;
      }
    }

    function hideResult() {
      if (resultCard) {
        resultCard.classList.add('hidden');
      }
    }

    function updateResult(patient, total) {
      if (!resultCard) {
        return;
      }
      resultCard.classList.remove('hidden');
      if (patient) {
        safelySet(summaryEl, total === 1 ? 'One matching patient found' : `${total} matching patients found`);
        safelySet(nameEl, patient.name || 'Patient');
        const metaParts = [patient.phone, patient.email].filter(Boolean);
        safelySet(metaEl, metaParts.join(' | ') || 'Contact details not provided');
        safelySet(phoneEl, patient.phone || '-');
        safelySet(emailEl, patient.email || '-');
        const pets = (patient.pets || []).map(p => p.name).filter(Boolean);
        safelySet(petsEl, pets.length ? `Pets: ${pets.join(', ')}` : 'No pets recorded yet');
        safelySet(noteEl, '');
        existingBtn?.classList.remove('hidden');
        existingBtn?.removeAttribute('disabled');
        newBtn?.classList.add('hidden');
        setPatientMode('existing');
      } else {
        safelySet(summaryEl, 'No existing patient found');
        safelySet(nameEl, 'Register a new patient');
        safelySet(metaEl, '');
        safelySet(phoneEl, '-');
        safelySet(emailEl, '-');
        safelySet(petsEl, '');
        safelySet(noteEl, 'Create a new patient record to proceed.');
        existingBtn?.setAttribute('disabled', 'disabled');
        existingBtn?.classList.add('hidden');
        newBtn?.classList.remove('hidden');
        setPatientMode('new');
      }
    }

    async function performSearch(query) {
      const trimmed = (query || '').trim();
      if (!trimmed) {
        safelySet(statusEl, 'Enter name, phone, or email to start searching.');
        currentPatient = null;
        hideResult();
        existingBtn?.classList.add('hidden');
        newBtn?.classList.add('hidden');
        return;
      }
      safelySet(statusEl, 'Looking up patient records...');
      try {
        await Auth.bootstrap();
        const response = await apiFetch(API.patients(trimmed), { headers: Auth.headers() });
        const matches = response?.data || [];
        currentPatient = matches[0] || null;
        PATIENTS = matches;
        updateResult(currentPatient, matches.length);
        if (matches.length) {
          safelySet(statusEl, `Found ${matches.length} match${matches.length === 1 ? '' : 'es'}.`);
        } else {
          safelySet(statusEl, 'No matching patient found. You can add a new one below.');
        }
      } catch (error) {
        console.error('Receptionist search failed', error);
        safelySet(statusEl, 'Search failed. Try again.');
        currentPatient = null;
        hideResult();
      }
    }

    existingBtn?.addEventListener('click', () => {
      if (!currentPatient) {
        return;
      }
      PREFERRED_PATIENT_ID = currentPatient.id;
      const existingIndex = PATIENTS.findIndex(p => String(p.id) === String(currentPatient.id));
      if (existingIndex === -1) {
        PATIENTS = [currentPatient, ...PATIENTS];
      }
      populatePatientSelect();
      setPatientMode('existing');
      openModal();
    });

    newBtn?.addEventListener('click', () => {
      PREFERRED_PATIENT_ID = null;
      setPatientMode('new');
      openModal();
    });

    function triggerSearch() {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        performSearch(searchInput?.value || '');
      }, 400);
    }

    searchForm?.addEventListener('submit', (event) => {
      event.preventDefault();
      performSearch(searchInput?.value || '');
    });

    searchInput?.addEventListener('input', triggerSearch);

    frontDeskNewPatientBtn?.addEventListener('click', () => {
      PREFERRED_PATIENT_ID = null;
      setPatientMode('new');
      openModal();
    });

    function initialize() {
      safelySet(statusEl, 'Enter a search term to check patient records.');
      hideResult();
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initialize);
    } else {
      initialize();
    }
  })();
</script>
@endsection
