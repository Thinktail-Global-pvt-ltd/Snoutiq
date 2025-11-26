@extends('layouts.snoutiq-dashboard')

@section('title','Staff')
@section('page_title','Staff Management')

@section('head')
<style>
  .table-container {
    overflow-x: auto;
  }

  .role-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
  }

  .role-pill.doctor {
    background: #eef2ff;
    color: #3730a3;
  }

  .role-pill.receptionist {
    background: #ecfdf5;
    color: #065f46;
  }

  .role-pill.clinic {
    background: #fee2e2;
    color: #991b1b;
  }

  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    z-index: 80;
  }

  .modal-content {
    width: 100%;
    max-width: 480px;
  }
</style>
@endsection

@php
  $isOnboarding = request()->get('onboarding') === '1';
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
@endphp

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
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
          Newly added staff are stored in the receptionist table.
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
</div>

<div id="staff-modal" class="modal-overlay hidden" hidden aria-hidden="true" style="display:none;">
  <div class="modal-content bg-white rounded-2xl shadow-2xl p-6">
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
@endsection

@section('scripts')
<script>
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
</script>
@endsection
