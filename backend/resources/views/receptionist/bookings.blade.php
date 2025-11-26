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
  @media (max-width: 640px) {
    .table-card table {
      min-width: 720px;
    }
  }
</style>
@endsection

@php
  $sessionRole = session('role')
      ?? data_get(session('auth_full'), 'role')
      ?? data_get(session('user'), 'role');

  $sessionClinicId = session('clinic_id')
      ?? session('vet_registerations_temp_id')
      ?? session('vet_registeration_id')
      ?? session('vet_id')
      ?? data_get(session('user'), 'clinic_id')
      ?? data_get(session('auth_full'), 'clinic_id')
      ?? data_get(session('auth_full'), 'user.clinic_id')
      ?? null;

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

  <div class="table-card overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <input id="booking-search" type="text" placeholder="Search by patient, pet, or doctor..." class="w-full md:w-80 bg-slate-50 border border-transparent rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
      <div class="text-xs text-slate-500">Showing latest bookings for your clinic</div>
    </div>
    <div id="booking-loading" class="p-6 text-center text-sm text-slate-500">Loading bookings...</div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm hidden" id="booking-table">
        <thead class="text-xs uppercase text-slate-600">
          <tr>
            <th class="px-4 py-3 text-left">Patient</th>
            <th class="px-4 py-3 text-left">Pet</th>
            <th class="px-4 py-3 text-left">Doctor</th>
            <th class="px-4 py-3 text-left">Service</th>
            <th class="px-4 py-3 text-left">Scheduled</th>
            <th class="px-4 py-3 text-left">Status</th>
          </tr>
        </thead>
        <tbody id="booking-rows" class="divide-y divide-slate-100 bg-white"></tbody>
      </table>
    </div>
    <div id="booking-empty" class="hidden p-10 text-center text-slate-500 text-sm">
      No bookings yet. Start by creating a new appointment for a patient.
    </div>
  </div>
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
          <label class="block text-sm font-semibold mb-1">Time</label>
          <input name="scheduled_time" type="time" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
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
  const SESSION_ROLE = @json($sessionRole);
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
    const raw = @json(auth()->id() ?? session('user_id') ?? data_get(session('user'),'id'));
    if (raw === null || raw === undefined) return null;
    const num = Number(raw);
    return Number.isFinite(num) && num > 0 ? num : null;
  })();
  const INITIAL_DOCTORS = @json($doctorList);

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
      if (CURRENT_USER_ID) {
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
    if (CURRENT_USER_ID) {
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
    if (CURRENT_USER_ID) {
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

  const API = {
    bookings: () => `${CONFIG.API_BASE}/receptionist/bookings${targetQuery()}`,
    createBooking: `${CONFIG.API_BASE}/receptionist/bookings`,
    patients: (query = '') => `${CONFIG.API_BASE}/receptionist/patients${targetQuery(query ? { q: query } : {})}`,
    createPatient: `${CONFIG.API_BASE}/receptionist/patients`,
    patientPets: (userId) => `${CONFIG.API_BASE}/receptionist/patients/${userId}/pets${targetQuery()}`,
  };

  const bookingRows = document.getElementById('booking-rows');
  const bookingTable = document.getElementById('booking-table');
  const bookingEmpty = document.getElementById('booking-empty');
  const bookingLoading = document.getElementById('booking-loading');
  const addBookingBtn = document.getElementById('btn-open-booking');
  const modal = document.getElementById('booking-modal');
  const bookingForm = document.getElementById('booking-form');
  const patientSelect = document.getElementById('patient-select');
  const patientSearchInput = document.getElementById('patient-search');
  const petSelect = document.getElementById('pet-select');
  const doctorSelect = document.getElementById('doctor-select');
  const modeButtons = document.querySelectorAll('[data-patient-mode]');
  const existingSection = document.getElementById('existing-patient-section');
  const newSection = document.getElementById('new-patient-section');

  let BOOKINGS = [];
  let PATIENTS = [];
  let PATIENT_MODE = 'existing';

  function openModal() {
    modal.classList.add('active');
    modal.style.display = 'flex';
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
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
  addBookingBtn?.addEventListener('click', () => {
    if (!CURRENT_USER_ID && !CLINIC_SLUG) {
      Swal.fire({ icon:'warning', title:'Missing clinic context', text:'Open this page from the dashboard or add ?userId=clinicId to the URL.' });
      return;
    }
    openModal();
  });

  function renderBookings(list) {
    bookingRows.innerHTML = '';
    if (!list.length) {
      bookingTable.classList.add('hidden');
      bookingEmpty.classList.remove('hidden');
      return;
    }
    bookingTable.classList.remove('hidden');
    bookingEmpty.classList.add('hidden');
    list.forEach(row => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="px-4 py-3">
          <div class="font-semibold text-slate-900">${row.patient_name || 'Unknown'}</div>
          <div class="text-xs text-slate-500">${row.patient_phone || ''}</div>
        </td>
        <td class="px-4 py-3">
          <div class="font-semibold">${row.pet_name || '—'}</div>
          <div class="text-xs text-slate-500">${row.pet_breed || row.pet_type || ''}</div>
        </td>
        <td class="px-4 py-3">
          <div class="text-slate-900">${row.doctor_name || '—'}</div>
          <div class="text-xs text-slate-500">${row.assigned_doctor_id ? 'Doctor #'+row.assigned_doctor_id : ''}</div>
        </td>
        <td class="px-4 py-3 capitalize">${(row.service_type || '').replace('_',' ')}</td>
        <td class="px-4 py-3 text-sm text-slate-700">${formatDate(row.scheduled_for || row.booking_created_at)}</td>
        <td class="px-4 py-3">
          <span class="status-pill ${statusClass(row.status)}">${row.status || 'pending'}</span>
        </td>
      `;
      bookingRows.appendChild(tr);
    });
  }

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

  async function fetchBookings() {
    if (!CURRENT_USER_ID && !CLINIC_SLUG) {
      bookingLoading.textContent = 'Clinic context missing.';
      return;
    }
    bookingLoading.classList.remove('hidden');
    bookingTable.classList.add('hidden');
    bookingEmpty.classList.add('hidden');
    try {
      await Auth.bootstrap();
      const res = await apiFetch(API.bookings(), { headers: Auth.headers() });
      BOOKINGS = res?.data || [];
      renderBookings(BOOKINGS);
    } catch (error) {
      Swal.fire({ icon:'error', title:'Failed to load bookings', text:error.message || 'Unknown error' });
    } finally {
      bookingLoading.classList.add('hidden');
    }
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
    patientSelect.innerHTML = '';
    PATIENTS.forEach(patient => {
      const option = document.createElement('option');
      option.value = patient.id;
      option.textContent = `${patient.name || 'Patient'} • ${patient.phone || patient.email || ''}`;
      patientSelect.appendChild(option);
    });
    if (PATIENTS.length) {
      patientSelect.value = PATIENTS[0].id;
      handlePatientChange();
    } else {
      petSelect.innerHTML = '';
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
      petSelect.appendChild(opt);
    });
  }

  function renderDoctors(doctors) {
    doctorSelect.innerHTML = '<option value="">Any available doctor</option>';
    doctors.forEach(doc => {
      const opt = document.createElement('option');
      opt.value = doc.id;
      opt.textContent = doc.doctor_name || `Doctor #${doc.id}`;
      doctorSelect.appendChild(opt);
    });
  }

  function handlePatientChange() {
    const patientId = patientSelect.value;
    if (!patientId) {
      petSelect.innerHTML = '';
      return;
    }
    fetchPatientPets(patientId);
  }

  patientSelect.addEventListener('change', handlePatientChange);

  let searchDebounce;
  patientSearchInput.addEventListener('input', (event) => {
    const query = event.target.value.trim();
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => fetchPatients(query), 350);
  });

  document.getElementById('booking-search').addEventListener('input', (event) => {
    const query = event.target.value.toLowerCase();
    if (!query) {
      renderBookings(BOOKINGS);
      return;
    }
    const filtered = BOOKINGS.filter(row => {
      const fields = [
        row.patient_name,
        row.patient_phone,
        row.pet_name,
        row.doctor_name,
        row.service_type,
      ].map(v => (v || '').toLowerCase());
      return fields.some(field => field.includes(query));
    });
    renderBookings(filtered);
  });

  bookingForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!CURRENT_USER_ID && !CLINIC_SLUG) {
      Swal.fire({ icon:'warning', title:'Missing clinic context', text:'Add ?userId=clinicId to the URL or reload from dashboard.' });
      return;
    }
    try {
      await Auth.bootstrap();
      let patientId = bookingForm.elements['patient_id']?.value || null;
      let petId = bookingForm.elements['pet_id']?.value || null;
      let inlinePet = null;

      if (PATIENT_MODE === 'new') {
        const payload = new FormData();
        const name = bookingForm.elements['new_patient_name'].value.trim();
        const phone = bookingForm.elements['new_patient_phone'].value.trim();
        const email = bookingForm.elements['new_patient_email'].value.trim();
        const petName = bookingForm.elements['new_pet_name'].value.trim();
        if (!name || (!phone && !email)) {
          Swal.fire({ icon:'warning', title:'Patient details required', text:'Provide name and either phone or email.' });
          return;
        }
        if (!petName) {
          Swal.fire({ icon:'warning', title:'Pet required', text:'Please provide pet details so we can attach the booking.' });
          return;
        }
        payload.append('name', name);
        if (phone) payload.append('phone', phone);
        if (email) payload.append('email', email);
        payload.append('pet_name', petName);
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
        petId = patientRes?.data?.pet?.id || null;
        if (!patientId) throw new Error('Patient creation failed');
      } else {
        inlinePet = {
          name: bookingForm.elements['inline_pet_name'].value.trim(),
          type: bookingForm.elements['inline_pet_type'].value.trim(),
          breed: bookingForm.elements['inline_pet_breed'].value.trim(),
          gender: bookingForm.elements['inline_pet_gender'].value.trim(),
        };
      }

      if (!patientId) {
        Swal.fire({ icon:'warning', title:'Select a patient' });
        return;
      }

      const payload = new FormData();
      payload.append('patient_id', patientId);
      if (petId) {
        payload.append('pet_id', petId);
      } else if (inlinePet && inlinePet.name) {
        payload.append('pet_name', inlinePet.name);
        if (inlinePet.type) payload.append('pet_type', inlinePet.type);
        if (inlinePet.breed) payload.append('pet_breed', inlinePet.breed);
        if (inlinePet.gender) payload.append('pet_gender', inlinePet.gender);
      }
      const doctorId = bookingForm.elements['doctor_id'].value;
      if (doctorId) payload.append('doctor_id', doctorId);
      payload.append('service_type', bookingForm.elements['service_type'].value);
      if (bookingForm.elements['scheduled_date'].value) payload.append('scheduled_date', bookingForm.elements['scheduled_date'].value);
      if (bookingForm.elements['scheduled_time'].value) payload.append('scheduled_time', bookingForm.elements['scheduled_time'].value);
      if (bookingForm.elements['notes'].value.trim()) payload.append('notes', bookingForm.elements['notes'].value.trim());
      appendTarget(payload);

      await apiFetch(API.createBooking, {
        method: 'POST',
        headers: Auth.headers(),
        body: payload,
      });
      Swal.fire({ icon:'success', title:'Booking saved', timer:1400, showConfirmButton:false });
      closeModal();
      fetchBookings();
      fetchPatients();
    } catch (error) {
      Swal.fire({ icon:'error', title:'Unable to save booking', text:error.message || 'Unknown error' });
    }
  });

  function init() {
    renderDoctors(INITIAL_DOCTORS || []);
    fetchBookings();
    fetchPatients();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
@endsection
