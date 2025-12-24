
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
      if (!formData.has('clinic_id')) formData.append('clinic_id', String(CLINIC_CONTEXT_ID));
      if (!formData.has('user_id')) formData.append('user_id', String(CLINIC_CONTEXT_ID));
      if (RECEPTIONIST_ID) formData.append('receptionist_id', String(RECEPTIONIST_ID));
    } else if (CURRENT_USER_ID) {
      if (!formData.has('user_id')) formData.append('user_id', String(CURRENT_USER_ID));
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
    receptionistBookings: () => `${CONFIG.API_BASE}/receptionist/bookings${targetQuery()}`,
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
    if (VIEW_MODE === 'history') {
      return loadClinicHistory();
    }
    if (!patientRows || !patientTable || !patientLoading) return;
    if (!userId) {
      patientLoading.classList.remove('hidden');
      patientLoading.textContent = 'Loading history…';
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
      const [apptRes, bookingsRes] = await Promise.all([
        apiFetch(API.appointmentsByUser(userId), { headers: Auth.headers() }).catch(() => ({ data: { appointments: [] } })),
        apiFetch(API.receptionistBookings(), { headers: Auth.headers() }).catch(() => ({ data: [] })),
      ]);

      const appointmentRows = apptRes?.data?.appointments || [];
      const bookingRows = (bookingsRes?.data || []).filter(b => String(b.user_id) === String(userId));

      const normalizedBookings = bookingRows.map((b) => {
        const dateTimeRaw = b.scheduled_for || b.booking_created_at || null;
        let date = '';
        let time_slot = '';
        if (dateTimeRaw) {
          const parsed = new Date(dateTimeRaw);
          if (!Number.isNaN(parsed.getTime())) {
            date = parsed.toISOString().slice(0, 10);
            time_slot = parsed.toTimeString().slice(0, 8);
          }
        }
        return {
          date,
          time_slot,
          status: b.status || 'scheduled',
          doctor: { name: b.doctor_name || '' },
          clinic: { name: b.clinic_name || (b.clinic_id ? `Clinic #${b.clinic_id}` : '') },
        };
      });

      PATIENT_APPOINTMENTS = [...appointmentRows, ...normalizedBookings];
      renderPatientAppointments(PATIENT_APPOINTMENTS);
    } catch (error) {
      patientLoading.textContent = 'Failed to load history';
      console.error(error);
    }
  }

  async function loadClinicHistory() {
    if (!patientRows || !patientTable || !patientLoading) return;
    patientLoading.classList.remove('hidden');
    patientLoading.textContent = 'Loading history…';
    patientTable.classList.add('hidden');
    patientEmpty?.classList.add('hidden');
    try {
      await Auth.bootstrap();
      const doctorList = normalizeDoctorList(INITIAL_DOCTORS);
      const doctors = doctorList.length ? doctorList : ((await apiFetch(API.doctors(), { headers: Auth.headers() }))?.data || []);
      const bookingsPromise = apiFetch(API.receptionistBookings(), { headers: Auth.headers() }).catch(() => ({ data: [] }));
      const appointmentPromises = doctors.map(doc => apiFetch(API.appointmentsByDoctor(doc.id), { headers: Auth.headers() }).catch(() => null));
      const [bookingsRes, ...appointmentsRes] = await Promise.all([bookingsPromise, ...appointmentPromises]);

      const appointmentRows = appointmentsRes.flatMap(res => res?.data?.appointments || []);
      const normalizedAppointments = appointmentRows.map(row => ({
        date: row.date || row.slot_date || row.scheduled_date || row.datetime || '',
        time_slot: row.time_slot || row.time || row.slot_time || '',
        status: row.status || 'scheduled',
        doctor: row.doctor || { name: row.doctor_name || (row.doctor_id ? `Doctor #${row.doctor_id}` : '') },
        clinic: row.clinic || { name: row.clinic_name || (row.clinic_id ? `Clinic #${row.clinic_id}` : '') },
        patient: row.patient || {
          name: row.patient_name || row.name || 'Patient',
          email: row.patient_email || '',
          phone: row.patient_phone || '',
        },
      }));

      const bookingsData = bookingsRes?.data || [];
      const normalizedBookings = bookingsData.map(b => {
        const slot = b.scheduled_for || b.booking_created_at || '';
        let date = '';
        let time_slot = '';
        if (slot) {
          const parsed = new Date(slot);
          if (!Number.isNaN(parsed.getTime())) {
            date = parsed.toISOString().slice(0, 10);
            time_slot = parsed.toTimeString().slice(0, 8);
          }
        }
        return {
          date,
          time_slot,
          status: b.status || 'scheduled',
          doctor: { name: b.doctor_name || (b.assigned_doctor_id ? `Doctor #${b.assigned_doctor_id}` : '') },
          clinic: { name: b.clinic_name || (b.clinic_id ? `Clinic #${b.clinic_id}` : '') },
          patient: {
            name: b.patient_name || b.patient_email || b.patient_phone || 'Patient',
            email: b.patient_email || '',
            phone: b.patient_phone || '',
          },
        };
      });

      const combined = [...normalizedAppointments, ...normalizedBookings];
      combined.sort((a, b) => {
        const timeB = parseAppointmentDate(b)?.getTime() ?? 0;
        const timeA = parseAppointmentDate(a)?.getTime() ?? 0;
        return timeB - timeA;
      });

      PATIENT_APPOINTMENTS = combined;
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
      const patientName = row.patient?.name
        || row.patient_name
        || row.patient?.email
        || row.patient_email
        || 'Patient';
      const patientMeta = row.patient?.email
        || row.patient_email
        || row.patient?.phone
        || row.patient_phone
        || '';
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="px-4 py-3">${formatDate(row.date + ' ' + (row.time_slot || ''))}</td>
        <td class="px-4 py-3">
          <div class="font-semibold text-slate-900">${patientName}</div>
          <div class="text-xs text-slate-500">${patientMeta}</div>
        </td>
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
      let patientPhone = normalizePhone(
        CURRENT_PATIENT?.phone,
        STORED_AUTH_FULL?.user?.phone,
        CURRENT_PATIENT?.email,
        STORED_AUTH_FULL?.user?.email
      );
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
        patientPhone = normalizePhone(
          patientRes?.data?.user?.phone,
          phone,
          patientRes?.data?.user?.email,
          email
        );
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

  function normalizePhone(...candidates) {
    for (const value of candidates) {
      if (typeof value !== 'string') continue;
      const cleaned = value.replace(/\s+/g, '').trim();
      if (cleaned) return cleaned.slice(0, 20);
    }
    return '0000000000';
  }

  function init() {
    const initialDoctors = normalizeDoctorList(INITIAL_DOCTORS);
    if (initialDoctors.length) {
      renderDoctors(initialDoctors);
    } else {
      fetchDoctors();
    }
    fetchPatients();
    if (VIEW_MODE === 'history') {
      loadClinicHistory();
    }
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
