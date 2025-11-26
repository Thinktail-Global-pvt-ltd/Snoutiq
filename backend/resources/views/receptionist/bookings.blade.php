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
  $viewMode = ($viewMode ?? 'create');
  $sessionRole = session('role')
      ?? data_get(session('auth_full'), 'role')
      ?? data_get(session('user'), 'role');

  $sessionClinicId = session('clinic_id')
      ?? session('vet_registerations_temp_id')
      ?? session('vet_registeration_id')
      ?? session('vet_id')
      ?? data_get(session('user'), 'clinic_id')
      ?? data_get(session('user'), 'vet_registeration_id')
      ?? data_get(session('auth_full'), 'clinic_id')
      ?? data_get(session('auth_full'), 'user.clinic_id')
      ?? data_get(session('auth_full'), 'user.vet_registeration_id')
      ?? null;

  $receptionistClinicId = null;
  if ($sessionRole === 'receptionist') {
      $receptionistRecord = \App\Models\Receptionist::find(session('receptionist_id'));
      if ($receptionistRecord?->vet_registeration_id) {
          $receptionistClinicId = (int) $receptionistRecord->vet_registeration_id;
          $sessionClinicId = $sessionClinicId ?: $receptionistClinicId;
      }
  }

  $doctorList = [];
  if ($sessionClinicId) {
      $doctorList = \App\Models\Doctor::where('vet_registeration_id', $sessionClinicId)
          ->orderBy('doctor_name')
          ->get(['id','doctor_name'])
          ->toArray();
  }
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
        <p class="text-xs text-slate-500">Powered by /api/appointments/by-user</p>
      </div>
      <div class="text-xs text-slate-500">Select a patient to view their recent appointments</div>
    </div>
    <div class="px-4 pt-3 pb-2 border-b border-slate-100">
      <label class="text-xs uppercase tracking-wide text-slate-500">Patient</label>
      <select id="patient-card-select" class="mt-1 w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm">
        <option value="">Select patient</option>
      </select>
    </div>
    <div id="patient-loading" class="p-6 text-center text-sm text-slate-500">Select a patient to load history…</div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm hidden" id="patient-table">
        <thead class="text-xs uppercase text-slate-600">
          <tr>
            <th class="px-4 py-3 text-left">Date</th>
            <th class="px-4 py-3 text-left">Doctor</th>
            <th class="px-4 py-3 text-left">Clinic</th>
            <th class="px-4 py-3 text-left">Status</th>
          </tr>
        </thead>
        <tbody id="patient-rows" class="divide-y divide-slate-100 bg-white"></tbody>
      </table>
    </div>
    <div id="patient-empty" class="hidden p-10 text-center text-slate-500 text-sm">
      No appointments found for this patient.
    </div>
  </div>
  @endif
</div>

<div id="booking-modal" class="modal-overlay" hidden aria-hidden="true">
  <div class="modal-card bg-white rounded-2xl shadow-2xl p-6 space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold tracking-wide text-indigo-600 uppercase">Front Desk</p>
        <h3 class="text-xl font-semibold text-slate-900">Create Booking</h3>
      </div>
      <button type="button" data-close class="text-slate-500 hover:text-slate-700 text-lg">&times;</button>
    </div>

    <div class="bg-slate-50 rounded-xl p-2 flex items-center gap-2">
      <button type="button" class="tab-button active" data-patient-mode="existing">Existing Patient</button>
      <button type="button" class="tab-button" data-patient-mode="new">New Patient</button>
    </div>

    <form id="booking-form" class="space-y-5">
      <div id="existing-patient-section" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Patient</label>
          <div class="flex flex-col gap-2">
            <input id="patient-search" type="text" placeholder="Search patient..." class="bg-slate-50 rounded-lg px-3 py-2 text-sm border border-transparent focus:bg-white focus:ring-2 focus:ring-blue-500">
            <select id="patient-select" name="patient_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500"></select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Pet</label>
          <select id="pet-select" name="pet_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500"></select>
          <p class="text-xs text-slate-500 mt-1">Need a new pet? Fill details below and we'll add it automatically.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">New Pet Name</label>
            <input name="inline_pet_name" type="text" placeholder="Pet name" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Pet Type</label>
            <input name="inline_pet_type" type="text" placeholder="Dog, Cat..." class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Breed</label>
            <input name="inline_pet_breed" type="text" placeholder="Breed" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Gender</label>
            <input name="inline_pet_gender" type="text" placeholder="Male/Female" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
      </div>

      <div id="new-patient-section" class="hidden space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold mb-1">Patient Name</label>
            <input name="new_patient_name" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Phone</label>
            <input name="new_patient_phone" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Email</label>
            <input name="new_patient_email" type="email" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold mb-1">Pet Name</label>
            <input name="new_pet_name" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Pet Type</label>
            <input name="new_pet_type" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Breed</label>
            <input name="new_pet_breed" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Gender</label>
            <input name="new_pet_gender" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
        <p class="text-xs text-slate-500">Provide at least a phone number or email for the patient, and pet details so we can attach the booking.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-semibold mb-1">Doctor</label>
          <select id="doctor-select" name="doctor_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
            <option value="">Any available doctor</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Service Type</label>
          <select name="service_type" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
            <option value="in_clinic">In Clinic</option>
            <option value="video">Video</option>
            <option value="home_visit">Home Visit</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-semibold mb-1">Date</label>
          <input name="scheduled_date" type="date" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Available Slots</label>
          <select name="scheduled_time" id="slot-select" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
            <option value="">Select a time slot</option>
          </select>
          <p id="slot-hint" class="text-xs text-slate-500 mt-1">
            Select a doctor and date first to load available slots (via /api/doctors/{doctor}/slots/summary).
          </p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Notes</label>
        <textarea name="notes" rows="3" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500" placeholder="Any reason or context for this visit"></textarea>
      </div>

      <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
        <button type="button" data-close class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">Cancel</button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Save Booking</button>
      </div>
</form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const VIEW_MODE = @json($viewMode);
  const SESSION_ROLE = @json($sessionRole);
  const SESSION_CLINIC_ID = (() => {
    const raw = @json($sessionClinicId);
    if (raw === null || raw === undefined) return null;
    const num = Number(raw);
    return Number.isFinite(num) && num > 0 ? num : null;
  })();
  const RECEPTIONIST_ID = @json(session('receptionist_id') ?? data_get(session('auth_full'),'receptionist_id'));
  const RECEPTIONIST_CLINIC_ID = (() => {
    const raw = @json($receptionistClinicId);
    if (raw === null || raw === undefined) return null;
    const num = Number(raw);
    return Number.isFinite(num) && num > 0 ? num : null;
  })();
  const STORED_AUTH_FULL = (() => {
    try {
      const raw = sessionStorage.getItem('auth_full') || localStorage.getItem('auth_full');
      return raw ? JSON.parse(raw) : null;
    } catch (_e) {
      return null;
    }
  })();
  const SERVER_USER_ID = (() => {
    if (['doctor','receptionist'].includes(SESSION_ROLE) && SESSION_CLINIC_ID) {
      return SESSION_CLINIC_ID;
    }
    const raw = @json(auth()->id() ?? session('user_id') ?? data_get(session('user'),'id'));
    if (raw === null || raw === undefined) return null;
    const num = Number(raw);
    return Number.isFinite(num) && num > 0 ? num : null;
  })();
  const INITIAL_DOCTORS = @json($doctorList);
  const CLINIC_CONTEXT_ID = RECEPTIONIST_CLINIC_ID
    || SESSION_CLINIC_ID
    || STORED_AUTH_FULL?.clinic_id
    || STORED_AUTH_FULL?.user?.clinic_id
    || SERVER_USER_ID;

  const CONFIG = {
    API_BASE: @json(url('/api')),
    CSRF_URL: @json(url('/sanctum/csrf-cookie')),
  };

  function pickFirstString(values) {
    for (const val of values) {
      if (typeof val === 'string' && val.trim()) return val.trim();
    }
    return null;
  }

  const AUTH_FULL = (() => {
    try {
      const raw = localStorage.getItem('auth_full') || sessionStorage.getItem('auth_full');
      return raw ? JSON.parse(raw) : null;
    } catch (_) {
      return null;
    }
  })();

  const CURRENT_USER_ID = (() => {
    try {
      const url = new URL(location.href);
      const fromQuery = Number(url.searchParams.get('userId') ?? url.searchParams.get('doctorId'));
      const fromStorage = Number(localStorage.getItem('user_id') || sessionStorage.getItem('user_id'));
      const fromAuth = Number(AUTH_FULL?.user?.id ?? AUTH_FULL?.user_id);
      const candidates = [fromQuery, SERVER_USER_ID, fromAuth, fromStorage];
      for (const candidate of candidates) {
        if (Number.isFinite(candidate) && candidate > 0) return candidate;
      }
      return null;
    } catch (_) { return null; }
  })();

  const CLINIC_SLUG = (() => {
    try {
      const url = new URL(location.href);
      const q = url.searchParams.get('vet_slug') || url.searchParams.get('clinic_slug');
      if (q && q.trim()) return q.trim();
    } catch (_) {}
    return pickFirstString([
      localStorage.getItem('vet_slug'),
      sessionStorage.getItem('vet_slug'),
      localStorage.getItem('clinic_slug'),
      sessionStorage.getItem('clinic_slug'),
      AUTH_FULL?.clinic?.slug,
      AUTH_FULL?.clinic_slug,
      AUTH_FULL?.vet?.slug,
      AUTH_FULL?.vet_slug,
    ]);
  })();

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
      } catch (_) {}
      this.mode = 'none';
      return { mode: 'none' };
    },
    headers(base = {}) {
      const h = { Accept: 'application/json', ...base };
      if (CLINIC_CONTEXT_ID) {
        h['X-Clinic-Id'] = String(CLINIC_CONTEXT_ID);
        h['X-User-Id'] = String(CLINIC_CONTEXT_ID);
        if (RECEPTIONIST_ID) h['X-Receptionist-Id'] = String(RECEPTIONIST_ID);
      } else if (CURRENT_USER_ID) {
        h['X-User-Id'] = String(CURRENT_USER_ID);
      } else if (CLINIC_SLUG) {
        h['X-Vet-Slug'] = CLINIC_SLUG;
      }
      if (this.mode === 'bearer') {
        const token = localStorage.getItem('token') || sessionStorage.getItem('token');
        if (token) h['Authorization'] = 'Bearer '+token;
      } else if (this.mode === 'cookie') {
        h['X-Requested-With'] = 'XMLHttpRequest';
        const xsrf = getCookie('XSRF-TOKEN');
        if (xsrf) h['X-XSRF-TOKEN'] = decodeURIComponent(xsrf);
      }
      return h;
    },
  };

  function getCookie(name) {
    return document.cookie.split('; ').find(row => row.startsWith(name+'='))?.split('=')[1] ?? '';
  }

  function targetQuery(extra = {}) {
    const params = new URLSearchParams();
    if (CLINIC_CONTEXT_ID) {
      params.set('user_id', String(CLINIC_CONTEXT_ID));
      params.set('clinic_id', String(CLINIC_CONTEXT_ID));
      if (RECEPTIONIST_ID) params.set('receptionist_id', String(RECEPTIONIST_ID));
    } else if (CURRENT_USER_ID) {
      params.set('user_id', String(CURRENT_USER_ID));
    } else if (CLINIC_SLUG) {
      params.set('vet_slug', CLINIC_SLUG);
    }
    Object.entries(extra).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return;
      params.set(key, value);
    });
    const qs = params.toString();
    return qs ? `?${qs}` : '';
  }

  function appendTarget(formData) {
    if (CLINIC_CONTEXT_ID) {
      formData.append('user_id', String(CLINIC_CONTEXT_ID));
      formData.append('clinic_id', String(CLINIC_CONTEXT_ID));
      if (RECEPTIONIST_ID) formData.append('receptionist_id', String(RECEPTIONIST_ID));
    } else if (CURRENT_USER_ID) {
      formData.append('user_id', String(CURRENT_USER_ID));
    } else if (CLINIC_SLUG) {
      formData.append('vet_slug', CLINIC_SLUG);
    }
  }

  async function apiFetch(url, opts = {}) {
    const res = await fetch(url, { credentials: 'include', ...opts });
    const contentType = res.headers.get('content-type') || '';
    const payload = contentType.includes('application/json') ? await res.json() : await res.text();
    if (!res.ok) {
      const message = typeof payload === 'string' ? payload : (payload?.message || 'Request failed');
      throw new Error(message);
    }
    return payload;
  }

  console.log('[receptionist] ID', RECEPTIONIST_ID, 'clinic', CLINIC_CONTEXT_ID, 'stored_vet', STORED_AUTH_FULL?.user?.vet_registeration_id);
  const API = {
    patients: (query = '') => `${CONFIG.API_BASE}/receptionist/patients${targetQuery(query ? { q: query } : {})}`,
    doctors: () => `${CONFIG.API_BASE}/receptionist/doctors${targetQuery()}`,
    createPatient: `${CONFIG.API_BASE}/receptionist/patients`,
    patientPets: (userId) => `${CONFIG.API_BASE}/receptionist/patients/${userId}/pets${targetQuery()}`,
    appointmentsByDoctor: (doctorId) => `${CONFIG.API_BASE}/appointments/by-doctor/${doctorId}`,
    appointmentsByUser: (userId) => `${CONFIG.API_BASE}/appointments/by-user/${userId}`,
    createAppointment: `${CONFIG.API_BASE}/appointments/submit`,
    doctorSlotsSummary: (doctorId, extra = {}) => `${CONFIG.API_BASE}/doctors/${doctorId}/slots/summary${targetQuery(extra)}`,
  };

  const doctorRows = document.getElementById('doctor-rows');
  const doctorTable = document.getElementById('doctor-table');
  const doctorEmpty = document.getElementById('doctor-empty');
  const doctorLoading = document.getElementById('doctor-loading');
  const cardDoctorSelect = document.getElementById('doctor-card-select');
  const patientRows = document.getElementById('patient-rows');
  const patientTable = document.getElementById('patient-table');
  const patientEmpty = document.getElementById('patient-empty');
  const patientLoading = document.getElementById('patient-loading');
  const cardPatientSelect = document.getElementById('patient-card-select');
  const addBookingBtn = document.getElementById('btn-open-booking');
  const modal = document.getElementById('booking-modal');
  const bookingForm = document.getElementById('booking-form');
  const patientSelect = document.getElementById('patient-select');
  const patientSearchInput = document.getElementById('patient-search');
  const petSelect = document.getElementById('pet-select');
  const doctorSelect = document.getElementById('doctor-select');
  const slotSelect = document.getElementById('slot-select');
  const slotHint = document.getElementById('slot-hint');
  const modeButtons = document.querySelectorAll('[data-patient-mode]');
  const existingSection = document.getElementById('existing-patient-section');
  const newSection = document.getElementById('new-patient-section');

  let DOCTOR_APPOINTMENTS = [];
  let PATIENT_APPOINTMENTS = [];
  let PATIENTS = [];
  let CURRENT_PATIENT = null;
  let PATIENT_MODE = 'existing';
  let PREFERRED_PATIENT_ID = null;

  function openModal() {
    modal.classList.add('active');
    modal.style.display = 'flex';
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    if (!PATIENTS.length) {
      fetchPatients();
    } else if (!patientSelect.value && PATIENTS.length) {
      patientSelect.value = PATIENTS[0].id;
      handlePatientChange();
    }
    if (doctorSelect.options.length <= 1) {
      fetchDoctors();
    } else if (!doctorSelect.value && doctorSelect.options.length > 1) {
      doctorSelect.selectedIndex = 1;
      handleDoctorChange();
    }
  }

  function closeModal() {
    modal.classList.remove('active');
    modal.style.display = 'none';
    modal.setAttribute('hidden', 'hidden');
    modal.setAttribute('aria-hidden', 'true');
    bookingForm.reset();
    petSelect.innerHTML = '';
    setPatientMode('existing');
  }

  function setPatientMode(mode) {
    PATIENT_MODE = mode;
    modeButtons.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.patientMode === mode);
    });
    existingSection.classList.toggle('hidden', mode !== 'existing');
    newSection.classList.toggle('hidden', mode !== 'new');
  }

  modeButtons.forEach(btn => {
    btn.addEventListener('click', () => setPatientMode(btn.dataset.patientMode));
  });

  document.querySelectorAll('[data-close]').forEach(btn => btn.addEventListener('click', closeModal));
  bookingForm.elements['scheduled_date'].addEventListener('change', () => fetchDoctorSlots(doctorSelect.value));
  addBookingBtn?.addEventListener('click', () => {
    if (!CURRENT_USER_ID && !CLINIC_SLUG) {
      Swal.fire({ icon:'warning', title:'Missing clinic context', text:'Open this page from the dashboard or add ?userId=clinicId to the URL.' });
      return;
    }
    openModal();
  });

  function statusClass(status) {
    switch ((status || '').toLowerCase()) {
      case 'scheduled':
      case 'confirmed':
        return 'status-scheduled';
      case 'completed':
        return 'status-completed';
      case 'cancelled':
      case 'declined':
        return 'status-cancelled';
      default:
        return 'status-pending';
    }
  }

  function formatDate(value) {
    if (!value) return '—';
    try {
      const date = new Date(value.replace(' ', 'T'));
      return date.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
    } catch (_) {
      return value;
    }
  }

  function tryParseDate(value) {
    if (!value) return null;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
  }

  function parseAppointmentDate(row) {
    if (!row) return null;
    const datePart = row.date || row.scheduled_date || row.slot_date || row.scheduled_at || row.datetime || null;
    if (!datePart) return null;
    const timeRaw = (row.time_slot || row.time || row.slot_time || '').trim();
    const normalizedTime = timeRaw
      ? (timeRaw.length === 5 ? `${timeRaw}:00` : timeRaw)
      : '00:00:00';
    const hasTimeInDate = datePart.includes('T') || datePart.includes(' ');
    const candidates = [];
    if (hasTimeInDate) {
      candidates.push(datePart);
    }
    candidates.push(`${datePart}T${normalizedTime}`);
    candidates.push(`${datePart} ${normalizedTime}`);
    if (!hasTimeInDate) {
      candidates.push(datePart);
    }
    for (const candidate of candidates) {
      const parsed = tryParseDate(candidate);
      if (parsed) return parsed;
    }
    return null;
  }

  async function fetchPatients(query = '') {
    try {
      await Auth.bootstrap();
      const res = await apiFetch(API.patients(query), { headers: Auth.headers() });
      PATIENTS = res?.data || [];
      populatePatientSelect();
    } catch (error) {
      console.error('Failed to fetch patients', error);
    }
  }

  function populatePatientSelect() {
    if (patientSelect) patientSelect.innerHTML = '';
    if (cardPatientSelect) cardPatientSelect.innerHTML = '<option value="">Select patient</option>';
    PATIENTS.forEach(patient => {
      const label = `${patient.name || 'Patient'} • ${patient.phone || patient.email || ''}`;
      if (patientSelect) {
        const option = document.createElement('option');
        option.value = patient.id;
        option.textContent = label;
        patientSelect.appendChild(option);
      }
      if (cardPatientSelect) {
        const optionCard = document.createElement('option');
        optionCard.value = patient.id;
        optionCard.textContent = label;
        cardPatientSelect.appendChild(optionCard);
      }
    });
    const targetId = PREFERRED_PATIENT_ID || (PATIENTS[0]?.id ?? null);
    if (targetId && patientSelect) {
      patientSelect.value = targetId;
      CURRENT_PATIENT = PATIENTS.find(p => String(p.id) === String(targetId)) || null;
      PREFERRED_PATIENT_ID = null;
      handlePatientChange();
    } else if (petSelect) {
      petSelect.innerHTML = '';
      loadPatientAppointments(null);
    }
    if (cardPatientSelect && targetId) {
      cardPatientSelect.value = targetId;
      if (VIEW_MODE === 'history') {
        loadPatientAppointments(targetId);
      }
    }
  }

  async function fetchPatientPets(userId) {
    if (!userId) {
      petSelect.innerHTML = '';
      return;
    }
    try {
      await Auth.bootstrap();
      const res = await apiFetch(API.patientPets(userId), { headers: Auth.headers() });
      const pets = res?.data || [];
      renderPetOptions(pets);
    } catch (error) {
      console.error('Failed to load pets', error);
      petSelect.innerHTML = '';
    }
  }

  function renderPetOptions(pets) {
    petSelect.innerHTML = '';
    if (!pets.length) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = 'No pets on file';
      petSelect.appendChild(opt);
      return;
    }
    pets.forEach(pet => {
      const opt = document.createElement('option');
      opt.value = pet.id;
      opt.textContent = `${pet.name} • ${pet.type || ''}`;
      opt.dataset.petName = pet.name;
      petSelect.appendChild(opt);
    });
  }

  async function fetchDoctors() {
    if (doctorLoading) doctorLoading.textContent = 'Loading doctors…';
    try {
      await Auth.bootstrap();
      console.log('[receptionist] GET doctors', API.doctors());
      const res = await apiFetch(API.doctors(), { headers: Auth.headers() });
      const doctors = res?.data || [];
      console.log('[receptionist] doctors response', doctors);
      renderDoctors(doctors);
    } catch (error) {
      if (doctorLoading) doctorLoading.textContent = 'Failed to load doctors';
      console.error(error);
    }
  }

  function renderDoctors(doctors) {
    if (doctorSelect) {
      doctorSelect.innerHTML = '<option value="">Select doctor</option>';
      doctors.forEach(doc => {
        const opt = document.createElement('option');
        opt.value = doc.id;
        opt.textContent = doc.doctor_name || `Doctor #${doc.id}`;
        doctorSelect.appendChild(opt);
        appendCardOption(doc);
      });
      if (doctors.length && !doctorSelect.value) {
        doctorSelect.value = doctors[0].id;
      }
    } else {
      doctors.forEach(doc => appendCardOption(doc));
    }
    syncCardSelect();
    if (doctors.length) {
      handleDoctorChange();
    } else if (doctorLoading) {
      doctorLoading.textContent = 'No doctors found for this clinic.';
    }
  }

  function appendCardOption(doc) {
    if (!cardDoctorSelect) return;
    const existing = cardDoctorSelect.querySelector(`option[value="${doc.id}"]`);
    if (existing) return;
    const opt = document.createElement('option');
    opt.value = doc.id;
    opt.textContent = doc.doctor_name || `Doctor #${doc.id}`;
    cardDoctorSelect.appendChild(opt);
  }

  function syncCardSelect() {
    if (!cardDoctorSelect) return;
    if (doctorSelect) {
      cardDoctorSelect.innerHTML = doctorSelect.innerHTML;
    }
  }

  async function loadDoctorAppointments(doctorId) {
    if (!doctorRows || !doctorTable || !doctorLoading) return;
    if (!doctorId) {
      doctorRows.innerHTML = '';
      doctorTable.classList.add('hidden');
      doctorEmpty?.classList.add('hidden');
      doctorLoading.classList.remove('hidden');
      doctorLoading.textContent = 'Select a doctor to see appointments…';
      return;
    }
    doctorLoading.textContent = 'Loading appointments…';
    doctorLoading.classList.remove('hidden');
    doctorTable.classList.add('hidden');
    doctorEmpty?.classList.add('hidden');
    try {
      await Auth.bootstrap();
      const res = await apiFetch(API.appointmentsByDoctor(doctorId), { headers: Auth.headers() });
      DOCTOR_APPOINTMENTS = res?.data?.appointments || [];
      renderDoctorAppointments(DOCTOR_APPOINTMENTS);
    } catch (error) {
      doctorLoading.textContent = 'Failed to load doctor appointments';
      console.error(error);
    }
  }

  function createDoctorRow(row) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="px-4 py-3">
        <div class="font-semibold text-slate-900">${formatDate(row.date + ' ' + (row.time_slot || ''))}</div>
        <div class="text-xs text-slate-500">${row.time_slot || ''}</div>
      </td>
      <td class="px-4 py-3">
        <div class="font-semibold text-slate-900">${row.patient?.name || 'Patient'}</div>
        <div class="text-xs text-slate-500">${row.patient?.phone || row.patient?.email || ''}</div>
      </td>
      <td class="px-4 py-3">
        <div class="text-sm">${row.pet_name || '—'}</div>
      </td>
      <td class="px-4 py-3"><span class="status-pill ${statusClass(row.status)}">${row.status || 'pending'}</span></td>
    `;
    return tr;
  }

  function renderDoctorAppointments(rows) {
    if (!doctorRows || !doctorTable || !doctorLoading) return;
    doctorRows.innerHTML = '';
    if (!rows.length) {
      doctorTable.classList.add('hidden');
      doctorEmpty?.classList.remove('hidden');
      doctorLoading.classList.add('hidden');
      return;
    }
    doctorTable.classList.remove('hidden');
    doctorEmpty?.classList.add('hidden');
    doctorLoading.classList.add('hidden');
    const now = new Date();
    const upcoming = [];
    const ended = [];
    rows.forEach(row => {
      const slotDate = parseAppointmentDate(row);
      if (!slotDate || slotDate.getTime() >= now.getTime()) {
        upcoming.push(row);
      } else {
        ended.push(row);
      }
    });
    [
      { label: 'Upcoming Appointments', data: upcoming, emptyText: 'No upcoming appointments' },
      { label: 'Ended Appointments', data: ended, emptyText: 'No ended appointments yet' },
    ].forEach(section => {
      const header = document.createElement('tr');
      header.className = 'schedule-section-row';
      header.innerHTML = `<td class="px-4 py-2" colspan="4">${section.label}</td>`;
      doctorRows.appendChild(header);

      if (!section.data.length) {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `<td colspan="4" class="px-4 py-3 text-sm text-slate-500">${section.emptyText}</td>`;
        doctorRows.appendChild(emptyRow);
        return;
      }

      section.data.forEach(row => {
        doctorRows.appendChild(createDoctorRow(row));
      });
    });
  }

  async function loadPatientAppointments(userId) {
    if (!patientRows || !patientTable || !patientLoading) return;
    if (!userId) {
      patientLoading.classList.remove('hidden');
      patientLoading.textContent = 'Select a patient to view history…';
      patientTable.classList.add('hidden');
      patientEmpty?.classList.add('hidden');
      return;
    }
    patientLoading.classList.remove('hidden');
    patientLoading.textContent = 'Loading history…';
    patientTable.classList.add('hidden');
    patientEmpty?.classList.add('hidden');
    try {
      await Auth.bootstrap();
      const res = await apiFetch(API.appointmentsByUser(userId), { headers: Auth.headers() });
      PATIENT_APPOINTMENTS = res?.data?.appointments || [];
      renderPatientAppointments(PATIENT_APPOINTMENTS);
    } catch (error) {
      patientLoading.textContent = 'Failed to load history';
      console.error(error);
    }
  }

  function renderPatientAppointments(rows) {
    if (!patientRows || !patientTable || !patientLoading) return;
    patientRows.innerHTML = '';
    if (!rows.length) {
      patientTable.classList.add('hidden');
      patientEmpty?.classList.remove('hidden');
      patientLoading.classList.add('hidden');
      return;
    }
    patientTable.classList.remove('hidden');
    patientEmpty?.classList.add('hidden');
    patientLoading.classList.add('hidden');
    rows.forEach(row => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="px-4 py-3">${formatDate(row.date + ' ' + (row.time_slot || ''))}</td>
        <td class="px-4 py-3">${row.doctor?.name || '—'}</td>
        <td class="px-4 py-3">${row.clinic?.name || '—'}</td>
        <td class="px-4 py-3"><span class="status-pill ${statusClass(row.status)}">${row.status || 'pending'}</span></td>
      `;
      patientRows.appendChild(tr);
    });
  }

  async function fetchDoctorSlots(doctorId) {
    const date = bookingForm.elements['scheduled_date'].value;
    const serviceType = bookingForm.elements['service_type'].value || 'in_clinic';
    if (!doctorId || !date) {
      slotSelect.innerHTML = '<option value="">Select a time slot</option>';
      slotHint.textContent = 'Choose a doctor & date to fetch slots';
      return;
    }
    try {
      await Auth.bootstrap();
      const url = API.doctorSlotsSummary(doctorId, {
        date,
        service_type: serviceType,
      });
      const res = await apiFetch(url, { headers: Auth.headers() });
      const slots = res?.free_slots || [];
      slotSelect.innerHTML = '<option value="">Select a time slot</option>';
      slots.forEach(slot => {
        const opt = document.createElement('option');
        const time = typeof slot === 'string' ? slot : (slot.time ?? slot.time_slot ?? slot.slot ?? '');
        const status = typeof slot === 'string' ? 'free' : (slot.status || 'free');
        opt.value = time;
        opt.textContent = `${time} (${status})`;
        slotSelect.appendChild(opt);
      });
      slotHint.textContent = slots.length ? `${slots.length} slots available` : 'No slots available for this date';
    } catch (error) {
      slotHint.textContent = 'Failed to load slots';
      slotSelect.innerHTML = '<option value="">Select a time slot</option>';
      console.error(error);
    }
  }

  function handlePatientChange() {
    const patientId = patientSelect.value;
    CURRENT_PATIENT = PATIENTS.find(p => String(p.id) === String(patientId)) || null;
    if (!patientId) {
      petSelect.innerHTML = '';
      loadPatientAppointments(null);
      return;
    }
    fetchPatientPets(patientId);
    loadPatientAppointments(patientId);
  }

  function handleDoctorChange(source = null) {
    let doctorId = doctorSelect?.value || '';
    if (source === 'card' && cardDoctorSelect) {
      doctorId = cardDoctorSelect.value;
      if (doctorSelect) doctorSelect.value = doctorId;
    } else if (!doctorId && cardDoctorSelect) {
      doctorId = cardDoctorSelect.value;
    }
    if (cardDoctorSelect && source !== 'card') {
      cardDoctorSelect.value = doctorId;
    }
    console.log('[receptionist] doctor change', doctorId);
    if (!doctorId) {
      if (doctorRows && doctorTable && doctorLoading) {
        doctorRows.innerHTML = '';
        doctorTable.classList.add('hidden');
        doctorLoading.classList.remove('hidden');
        doctorLoading.textContent = 'Select a doctor to see appointments…';
      }
      slotSelect.innerHTML = '<option value="">Select a time slot</option>';
      slotHint.textContent = 'Choose a doctor & date to fetch slots';
      return;
    }
    fetchDoctorSlots(doctorId);
    loadDoctorAppointments(doctorId);
  }

  patientSelect.addEventListener('change', handlePatientChange);
  doctorSelect?.addEventListener('change', () => {
    syncCardSelect();
    handleDoctorChange('modal');
  });
  if (cardDoctorSelect) {
    cardDoctorSelect.addEventListener('change', () => handleDoctorChange('card'));
  }

  let searchDebounce;
  patientSearchInput.addEventListener('input', (event) => {
    const query = event.target.value.trim();
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => fetchPatients(query), 350);
  });

  bookingForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const clinicId = CLINIC_CONTEXT_ID;
    if (!clinicId) {
      Swal.fire({ icon:'warning', title:'Missing clinic context', text:'Reload from dashboard or add ?userId=clinicId to the URL.' });
      return;
    }
    const doctorId = bookingForm.elements['doctor_id'].value;
    if (!doctorId) {
      Swal.fire({ icon:'warning', title:'Select a doctor' });
      return;
    }
    const date = bookingForm.elements['scheduled_date'].value;
    const timeSlot = bookingForm.elements['scheduled_time'].value;
    if (!date || !timeSlot) {
      Swal.fire({ icon:'warning', title:'Date & slot required' });
      return;
    }
    try {
      await Auth.bootstrap();
      let patientId = patientSelect.value || null;
      let patientName = CURRENT_PATIENT?.name || '';
      let patientPhone = CURRENT_PATIENT?.phone || STORED_AUTH_FULL?.user?.phone || '';
      let petName = null;

      if (PATIENT_MODE === 'new') {
        const name = bookingForm.elements['new_patient_name'].value.trim();
        const phone = bookingForm.elements['new_patient_phone'].value.trim();
        const email = bookingForm.elements['new_patient_email'].value.trim();
        const newPetName = bookingForm.elements['new_pet_name'].value.trim();
        if (!name || (!phone && !email)) {
          Swal.fire({ icon:'warning', title:'Patient details required', text:'Provide name and either phone or email.' });
          return;
        }
        if (!newPetName) {
          Swal.fire({ icon:'warning', title:'Pet required', text:'Please provide pet details so we can attach the booking.' });
          return;
        }
        const payload = new FormData();
        payload.append('name', name);
        if (phone) payload.append('phone', phone);
        if (email) payload.append('email', email);
        payload.append('pet_name', newPetName);
        payload.append('pet_type', bookingForm.elements['new_pet_type'].value.trim() || 'pet');
        payload.append('pet_breed', bookingForm.elements['new_pet_breed'].value.trim() || 'Unknown');
        payload.append('pet_gender', bookingForm.elements['new_pet_gender'].value.trim() || 'unknown');
        appendTarget(payload);
        const patientRes = await apiFetch(API.createPatient, {
          method: 'POST',
          headers: Auth.headers(),
          body: payload,
        });
        patientId = patientRes?.data?.user?.id;
        patientName = patientRes?.data?.user?.name || name;
        patientPhone = patientRes?.data?.user?.phone || phone;
        petName = patientRes?.data?.pet?.name || newPetName;
        CURRENT_PATIENT = { id: patientId, name: patientName, phone: patientPhone };
        PREFERRED_PATIENT_ID = patientId;
        fetchPatients();
      } else {
        if (!patientId) {
          Swal.fire({ icon:'warning', title:'Select a patient' });
          return;
        }
        const selectedPetOption = petSelect.options[petSelect.selectedIndex];
        petName = selectedPetOption?.dataset?.petName || selectedPetOption?.textContent || null;
        const inlinePetName = bookingForm.elements['inline_pet_name'].value.trim();
        if (inlinePetName) {
          petName = inlinePetName;
        }
      }

      const payload = new FormData();
      payload.append('user_id', patientId);
      payload.append('clinic_id', clinicId);
      payload.append('doctor_id', doctorId);
      payload.append('patient_name', patientName);
      if (patientPhone) payload.append('patient_phone', patientPhone);
      if (petName) payload.append('pet_name', petName);
      payload.append('date', date);
      payload.append('time_slot', timeSlot);
      if (bookingForm.elements['notes'].value.trim()) {
        payload.append('notes', bookingForm.elements['notes'].value.trim());
      }

      await apiFetch(API.createAppointment, {
        method: 'POST',
        headers: Auth.headers(),
        body: payload,
      });
      Swal.fire({ icon:'success', title:'Appointment saved', timer:1500, showConfirmButton:false });
      closeModal();
      handleDoctorChange();
      handlePatientChange();
    } catch (error) {
      Swal.fire({ icon:'error', title:'Unable to save appointment', text:error.message || 'Unknown error' });
    }
  });

  function normalizeDoctorList(list) {
    if (Array.isArray(list)) return list;
    if (list && typeof list === 'object') {
      return Object.values(list);
    }
    return [];
  }

  function init() {
    const initialDoctors = normalizeDoctorList(INITIAL_DOCTORS);
    if (initialDoctors.length) {
      renderDoctors(initialDoctors);
    } else {
      fetchDoctors();
    }
    fetchPatients();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
@endsection
