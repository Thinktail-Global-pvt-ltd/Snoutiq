@extends('layouts.snoutiq-dashboard')

@section('title','Appointments')
@section('page_title','Appointments')

@php
  $sessionClinicId = session('clinic_id')
    ?? session('vet_registerations_temp_id')
    ?? session('vet_registeration_id')
    ?? session('vet_id')
    ?? data_get(session('user'), 'clinic_id')
    ?? data_get(session('auth_full'), 'clinic_id')
    ?? data_get(session('auth_full'), 'user.clinic_id')
    ?? data_get(session('doctor'), 'vet_registeration_id')
    ?? optional(auth()->user())->clinic_id
    ?? optional(auth()->user())->vet_registeration_id;
@endphp

@section('head')
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    #appointmentsShell {
      --bg:#f5f7fb; --panel:#ffffff; --muted:#6b7280;
      --blue:#2563eb; --blue-100:#e6f0ff; --purple:#7c3aed; --purple-100:#f2eefe;
      --green:#10b981; --green-100:#e6fff2; --orange:#f97316; --red:#ef4444;
      --radius:12px; --shadow:0 8px 30px rgba(10,20,40,0.06);
      font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background: var(--bg);
      color: #0b1220;
      padding: 8px;
      border-radius: 16px;
    }
    #appointmentsShell .appt-container{max-width:1180px;margin:0 auto;padding:20px;display:flex;flex-direction:column;gap:18px}
    #appointmentsShell .header{display:flex;justify-content:space-between;align-items:center;gap:12px}
    #appointmentsShell .title{font-size:28px;font-weight:700;color:var(--blue)}
    #appointmentsShell .subtitle{color:var(--muted);font-size:13px;margin-top:6px}
    #appointmentsShell .header-right{display:flex;align-items:center;gap:10px}
    #appointmentsShell .doctorChip{background:var(--panel);padding:8px 12px;border-radius:999px;box-shadow:var(--shadow);display:flex;align-items:center;gap:10px;font-weight:700}
    #appointmentsShell .btn{padding:9px 14px;border-radius:10px;border:0;cursor:pointer;font-weight:700}
    #appointmentsShell .btn-ghost{background:var(--panel);border:1px solid #eef6ff;color:var(--muted)}
    #appointmentsShell .btn-primary{background:linear-gradient(90deg,var(--blue),var(--purple));color:white;box-shadow:var(--shadow)}
    #appointmentsShell .small{font-size:13px;color:var(--muted)}
    #appointmentsShell .filterBar{background:var(--panel);padding:12px;border-radius:12px;box-shadow:var(--shadow);overflow:hidden;transition:max-height .3s ease}
    #appointmentsShell .filterToggle{display:flex;gap:8px;align-items:center;cursor:pointer}
    #appointmentsShell .filterRow{display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;align-items:center}
    #appointmentsShell .pill{padding:8px 14px;border-radius:999px;background:#fff;border:1px solid #eef3ff;color:var(--muted);cursor:pointer;font-weight:700}
    #appointmentsShell .pill.active{background:var(--blue);color:#fff;border-color:transparent}
    #appointmentsShell .pill.tele.active{background:var(--purple)}
    #appointmentsShell .select,#appointmentsShell .search{padding:8px 12px;border-radius:10px;border:1px solid #eef6ff}
    #appointmentsShell .search{min-width:240px}
    #appointmentsShell .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
    #appointmentsShell .kpi{background:var(--panel);padding:14px;border-radius:10px;box-shadow:var(--shadow)}
    #appointmentsShell .kpi .num{font-weight:800;font-size:18px}
    #appointmentsShell .kpi .label{color:var(--muted);font-size:13px;margin-top:6px}
    #appointmentsShell .calendar{background:var(--panel);padding:14px;border-radius:12px;box-shadow:var(--shadow)}
    #appointmentsShell .cal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
    #appointmentsShell .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:10px}
    #appointmentsShell .weekday{font-size:12px;color:var(--muted);text-align:center}
    #appointmentsShell .day{height:88px;border-radius:10px;display:flex;flex-direction:column;align-items:center;padding:8px;position:relative;cursor:pointer}
    #appointmentsShell .day .num{font-weight:700}
    #appointmentsShell .day .dots{display:flex;gap:4px;position:absolute;bottom:8px}
    #appointmentsShell .dot{width:8px;height:8px;border-radius:50%}
    #appointmentsShell .dot.in-clinic{background:var(--blue)}
    #appointmentsShell .dot.telemedicine{background:var(--purple)}
    #appointmentsShell .dot.walk-in{background:var(--green)}
    #appointmentsShell .dot.follow-up{background:var(--orange)}
    #appointmentsShell .day.today{background:linear-gradient(180deg,#eef6ff,#fff);box-shadow:0 8px 26px rgba(37,99,235,0.06)}
    #appointmentsShell .day.selected{outline:3px solid rgba(37,99,235,0.08)}
    #appointmentsShell .main{display:flex;flex-direction:column;gap:12px}
    #appointmentsShell .tabs{display:flex;gap:8px;align-items:center;justify-content:space-between}
    #appointmentsShell .tabButtons{display:flex;gap:8px}
    #appointmentsShell .tabBtn{padding:8px 12px;border-radius:999px;border:1px solid #eef6ff;background:#fff;cursor:pointer;font-weight:700}
    #appointmentsShell .tabBtn.active{background:linear-gradient(90deg,var(--blue),var(--purple));color:#fff;box-shadow:var(--shadow)}
    #appointmentsShell .timelineCard{background:var(--panel);padding:14px;border-radius:12px;box-shadow:var(--shadow);min-height:360px;display:flex;flex-direction:column}
    #appointmentsShell .timelineWrap{display:flex;gap:12px;flex:1;min-height:260px;position:relative}
    #appointmentsShell .leftTimes{width:72px;display:flex;flex-direction:column;gap:4px;padding-top:4px}
    #appointmentsShell .timeRow{height:40px;color:var(--muted);text-align:right;padding-right:8px;font-size:13px}
    #appointmentsShell .rightArea{flex:1;position:relative;padding-left:8px;border-left:1px dashed #eef6ff}
    #appointmentsShell .appt{position:absolute;left:8px;right:8px;border-radius:10px;padding:10px;color:#062b4b;font-weight:700;box-shadow:0 10px 26px rgba(2,6,23,0.06);cursor:pointer;overflow:hidden;background:linear-gradient(90deg,var(--blue-100),#fff);border-left:6px solid var(--blue)}
    #appointmentsShell .appt .meta{display:flex;justify-content:space-between;align-items:center}
    #appointmentsShell .appt .sub{font-size:13px;color:#234;opacity:0.95;margin-top:6px;font-weight:600}
    #appointmentsShell .appt.telemedicine{background:linear-gradient(90deg,var(--purple-100),#fff);border-left:6px solid var(--purple)}
    #appointmentsShell .appt.walk-in{background:linear-gradient(90deg,var(--green-100),#fff);border-left:6px solid var(--green)}
    #appointmentsShell .appt.follow,#appointmentsShell .appt.follow-up{background:linear-gradient(90deg,#fff3eb,#fff);border-left:6px solid var(--orange)}
    #appointmentsShell .appt.conflict{box-shadow:0 0 0 4px rgba(239,68,68,0.10);border:1px solid rgba(239,68,68,0.14)}
    #appointmentsShell .joinBtn{padding:6px 10px;border-radius:8px;border:0;background:linear-gradient(90deg,var(--blue),var(--purple));color:white;cursor:pointer;font-size:12px;font-weight:700}
    #appointmentsShell .slotCard{background:var(--panel);padding:12px;border-radius:12px;box-shadow:var(--shadow)}
    #appointmentsShell .docGrid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:10px}
    #appointmentsShell .col{background:#fafcff;border-radius:10px;padding:10px;min-height:300px}
    #appointmentsShell .docName{font-weight:800;margin-bottom:10px}
    #appointmentsShell .slot{height:56px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;margin-bottom:8px;cursor:pointer}
    #appointmentsShell .slot.free{background:#eef6ff;color:var(--blue)}
    #appointmentsShell .slot.booked{background:var(--blue)}
    #appointmentsShell .slot.tele{background:var(--purple)}
    #appointmentsShell .slot.break{background:var(--orange)}
    #appointmentsShell .slot.unavail{background:#f2f4f8;color:var(--muted);font-weight:700}
    #appointmentsShell .overlay{position:fixed;inset:0;background:rgba(10,12,16,0.45);display:none;align-items:center;justify-content:center;z-index:999}
    #appointmentsShell .modal{width:920px;background:var(--panel);border-radius:12px;padding:16px;box-shadow:0 18px 48px rgba(2,6,23,0.12);max-height:88vh;overflow:auto}
    #appointmentsShell .steps{display:flex;gap:8px;margin-bottom:12px}
    #appointmentsShell .step{flex:1;padding:10px;border-radius:8px;background:#f8fbff;text-align:center;font-weight:700}
    #appointmentsShell .formRow{display:flex;gap:12px;align-items:center;margin-bottom:12px}
    #appointmentsShell .input{width:100%;padding:10px;border-radius:8px;border:1px solid #eef6ff}
    #appointmentsShell .tooltip{position:fixed;padding:8px 10px;background:#0b1220;color:white;border-radius:8px;font-size:13px;pointer-events:none;z-index:1600;display:none}
    #appointmentsShell .patientPanel{position:fixed;right:0;top:0;bottom:0;width:420px;background:var(--panel);box-shadow:-10px 0 40px rgba(2,6,23,0.12);transform:translateX(100%);transition:transform .28s;z-index:1200;overflow:auto}
    #appointmentsShell .patientPanel.open{transform:translateX(0)}
    #appointmentsShell .patientHead{padding:16px;background:linear-gradient(90deg,var(--blue),var(--purple));color:white}
    #appointmentsShell .teleModal{position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);background:var(--panel);padding:16px;border-radius:12px;box-shadow:var(--shadow);display:none;z-index:2000}
    @media(max-width:980px){
      #appointmentsShell .docGrid{grid-template-columns:repeat(2,1fr)}
      #appointmentsShell .docGrid .col{min-height:220px}
    }
    @media(max-width:640px){
      #appointmentsShell .kpis{grid-template-columns:repeat(2,1fr)}
      #appointmentsShell .docGrid{grid-template-columns:repeat(1,1fr)}
    }
  </style>
@endsection

@section('content')
<div id="appointmentsShell">
  <div class="appt-container">

    <div class="header">
      <div>
        <div class="title">Appointments</div>
        <div class="subtitle">Manage telemedicine, online bookings & walk-ins — demo (clinic-wide)</div>
      </div>

      <div class="header-right">
        <div class="doctorChip" title="Showing all clinic doctors">
          Doctors: <strong style="margin-left:6px">All doctors ▾</strong>
        </div>
        <button class="btn btn-ghost" id="exportBtn">Export CSV</button>
        <button class="btn btn-ghost" id="printBtn">Print</button>
        <button class="btn btn-primary" id="openCreateBtn">+ Create Appointment</button>
      </div>
    </div>

    <div class="kpis" id="kpiRow">
      <div class="kpi"><div class="num" id="k1">1,248</div><div class="label">Locked-in Pet Parents</div></div>
      <div class="kpi"><div class="num" id="k2">44%</div><div class="label">Repeat Rate (30d)</div></div>
      <div class="kpi"><div class="num" id="k3">₹42,500</div><div class="label">Telemed Revenue (30d)</div></div>
      <div class="kpi"><div class="num" id="k4">72%</div><div class="label">Clinic Utilization Today</div></div>
    </div>

    <div style="display:flex;gap:12px;align-items:center">
      <div class="filterToggle" id="filterToggle" aria-expanded="false" style="cursor:pointer">
        <div class="pill" style="padding:8px 12px">Filters ▾</div>
        <div class="small">Click to expand filters</div>
      </div>
    </div>

    <div class="filterBar" id="filterBar" style="max-height:0;padding-top:0;padding-bottom:0;">
      <div class="filterRow" style="margin-top:10px">
        <div style="display:flex;gap:8px;align-items:center">
          <div class="small">Type</div>
          <div>
            <span class="pill active" data-type="all">All</span>
            <span class="pill" data-type="in-clinic">In-Clinic</span>
            <span class="pill tele" data-type="telemedicine">Telemedicine</span>
            <span class="pill walk" data-type="walk-in">Walk-in</span>
            <span class="pill follow" data-type="follow-up">Follow-up</span>
          </div>
        </div>

        <div style="display:flex;gap:8px;align-items:center">
          <div class="small">Status</div>
          <div>
            <span class="pill" data-status="upcoming">Upcoming</span>
            <span class="pill" data-status="completed">Completed</span>
            <span class="pill" data-status="cancelled">Cancelled</span>
            <span class="pill" data-status="waiting">Waiting</span>
          </div>
        </div>

        <div style="display:flex;gap:8px;align-items:center">
          <div class="small">Doctor</div>
          <select id="doctorFilter" class="select"><option value="">All doctors</option></select>
        </div>

        <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
          <div>
            <button class="pill active" data-date="0">Today</button>
            <button class="pill" data-date="1">Tomorrow</button>
            <button class="pill" data-date="7">This week</button>
          </div>
          <input id="datePicker" class="select" type="date"/>
          <input id="searchInput" class="search" placeholder="Search pet, owner, phone..." />
        </div>
      </div>
    </div>

    <div class="calendar">
      <div class="cal-head">
        <div>
          <div style="font-weight:800" id="monthLabel">December 2025</div>
          <div class="small">Click a day to populate timeline & slot grid</div>
        </div>
        <div>
          <button class="btn btn-ghost" id="prevMonth">◀</button>
          <button class="btn btn-ghost" id="nextMonth">▶</button>
        </div>
      </div>

      <div class="cal-grid">
        <div class="weekday">Sun</div><div class="weekday">Mon</div><div class="weekday">Tue</div><div class="weekday">Wed</div><div class="weekday">Thu</div><div class="weekday">Fri</div><div class="weekday">Sat</div>
      </div>

      <div class="cal-grid" id="calendarDays" style="margin-top:12px"></div>
    </div>

    <div class="main">
      <div class="tabs">
        <div class="tabButtons">
          <button class="tabBtn active" id="tabTimeline">Timeline</button>
          <button class="tabBtn" id="tabGrid">Slot Grid</button>
        </div>
      </div>

      <div class="timelineCard" id="timelineCard">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
          <div style="font-weight:800" id="timelineTitle">Today's Schedule</div>
          <div class="small" id="timelineSub">Click an appointment to view pet details</div>
        </div>

        <div class="timelineWrap" id="timelineWrap">
          <div class="leftTimes" id="leftTimes"></div>
          <div class="rightArea" id="rightArea"></div>
        </div>
      </div>

      <div class="slotCard" id="slotCard" style="display:none">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="font-weight:800">Slot Grid</div>
          <div class="small">Columns show each doctor (click a free tile to book)</div>
        </div>
        <div class="docGrid" id="docGrid"></div>
      </div>
    </div>
  </div>

  <div class="overlay" id="modalOverlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <div><div style="font-weight:800" id="modalTitle">Create Appointment</div><div class="small">Clinic flow — owner → pet → details</div></div>
        <div><button class="btn btn-ghost" id="closeModal">Close</button></div>
      </div>

      <div class="steps">
        <div class="step" id="st1">1 — Owner</div>
        <div class="step" id="st2">2 — Pet</div>
        <div class="step" id="st3">3 — Details</div>
      </div>

      <div id="step1">
        <div class="formRow">
          <div style="flex:1">
            <label class="small">Phone or name</label>
            <input id="ownerSearch" class="input" placeholder="+91 98xxxx or John" />
            <div id="ownerResults" class="small" style="margin-top:8px"></div>
          </div>
          <div style="width:200px">
            <label class="small">Quick</label>
            <button class="btn btn-ghost" id="addOwner">+ Add owner</button>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end"><button class="btn btn-primary" id="toPet">Next</button></div>
      </div>

      <div id="step2" style="display:none">
        <div class="formRow">
          <div style="flex:1">
            <label class="small">Select Pet</label>
            <div id="petCards" style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px"></div>
          </div>
          <div style="width:260px">
            <label class="small">Add quick pet</label>
            <input id="quickName" class="input" placeholder="Pet name" style="margin-bottom:8px"/>
            <input id="quickBreed" class="input" placeholder="Breed" style="margin-bottom:8px"/>
            <button class="btn btn-ghost" id="addPet">Add Pet</button>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between"><button class="btn btn-ghost" id="backTo1">Back</button><button class="btn btn-primary" id="toDetails">Next</button></div>
      </div>

      <div id="step3" style="display:none">
        <div class="formRow">
          <div style="flex:1">
            <label class="small">Type</label>
            <select id="typeSelect" class="input"><option value="in-clinic">In-Clinic</option><option value="telemedicine">Telemedicine</option><option value="walk-in">Walk-in</option><option value="follow-up">Follow-up</option></select>
          </div>
          <div style="flex:1">
            <label class="small">Reason</label>
            <select id="reasonSelect" class="input"><option>Annual Checkup</option><option>Vaccination</option><option>Skin Issue</option><option>Dental</option><option>Emergency</option></select>
          </div>
        </div>

        <div class="formRow">
          <div style="flex:1">
            <label class="small">Doctor</label>
            <select id="modalDoctor" class="input"></select>
            <div style="margin-top:10px">
              <label class="small">Pick a slot</label>
              <div id="modalSlots" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px"></div>
            </div>
          </div>
          <div style="width:320px">
            <div class="small">Fee Estimate</div>
            <div style="margin-top:8px;background:#fbfbff;padding:12px;border-radius:8px;border:1px solid #eef6ff">Consult ₹800<br><strong>Total ₹800</strong></div>
          </div>
        </div>

        <div style="display:flex;justify-content:space-between">
          <button class="btn btn-ghost" id="backTo2">Back</button>
          <button class="btn btn-primary" id="createApptBtn">Create Appointment</button>
        </div>
      </div>
    </div>
  </div>

  <div class="tooltip" id="tooltip"></div>

  <div class="patientPanel" id="patientPanel">
    <div class="patientHead">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div style="display:flex;gap:12px;align-items:center">
          <div style="width:52px;height:52px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:26px">🐶</div>
          <div>
            <div id="pName" style="font-weight:800">Pet</div>
            <div class="small" id="pBrief">Breed • age</div>
          </div>
        </div>
        <div><button class="btn btn-ghost" id="closePatient">Close</button></div>
      </div>
    </div>
    <div style="padding:12px">
      <div><strong>Owner</strong><div class="small" id="pOwner">Owner — phone</div></div>
      <div style="margin-top:12px"><strong>Last visits</strong><div class="small" id="pVisits">—</div></div>
      <div style="margin-top:12px"><strong>Actions</strong><div style="display:flex;gap:8px;margin-top:8px"><button class="btn btn-primary" id="startTele">Start Tele</button><button class="btn btn-ghost" id="reschedBtn">Reschedule</button></div></div>
    </div>
  </div>

  <div class="teleModal" id="teleModal"><div id="teleContent">Tele call simulation</div></div>

  <div class="patientPanel" id="dayPanel">
    <div class="patientHead">
      <div style="display:flex;justify-content:space-between;align-items:center;width:100%">
        <div>
          <div id="dayTitle" style="font-weight:800">Appointments</div>
          <div class="small">Pet parents for the selected day</div>
        </div>
        <div><button class="btn btn-ghost" id="closeDayPanel">Close</button></div>
      </div>
    </div>
    <div style="padding:12px" id="dayList"></div>
  </div>
</div>
@endsection

@section('scripts')
<script>
(() => {
  const query = new URLSearchParams(window.location.search);
  const qsClinic = query.get('clinic_id') || query.get('vet_id') || query.get('clinicId') || query.get('vetId');
  const storedAuth = (()=>{ try { return JSON.parse(localStorage.getItem('auth_full') || sessionStorage.getItem('auth_full') || '{}'); } catch(e){ return {}; }})();
  const storedClinic = storedAuth?.user?.vet_registeration_id || storedAuth?.user?.clinic_id || storedAuth?.clinic_id;
  const CLINIC_ID = Number(qsClinic || @json($sessionClinicId ?? null) || storedClinic || '') || null;
  const clinicConfig = {
    clinicId: CLINIC_ID || "clinic_001",
    timezone: "Asia/Kolkata",
    workingHours: {start:"08:00", end:"20:00"},
    slotDurationMinutes: 30,
    telemedEnabled: true,
    commissionPercent: 25
  };
  const ORIGIN = window.location.origin;
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const apiBase = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
  const endpoints = {
    doctors: (clinicId) => clinicId ? `${apiBase}/doctors?vet_id=${clinicId}` : `${apiBase}/doctors`,
    appointmentsByDoctor: (doctorId) => `${apiBase}/appointments/by-doctor/${doctorId}`,
  };
  const qsDoctor = query.get('doctor_id') || query.get('doctorId');

  const state = {
    selectedDate: new Date(),
    filterTypes: ['all'],
    filterStatus: [],
    filterDoctors: [],
    search: '',
    view: 'timeline',
    modalStep: 1,
    selectedOwner: null,
    selectedPet: null,
    selectedSlot: null,
    selectedDoctor: null,
    doctors: [],
    owners: [],
    pets: [],
    appointments: [],
    loading: false
  };

  const $ = (id) => document.getElementById(id);

  const sameDay = (a,b) => new Date(a).toDateString() === new Date(b).toDateString();
  const formatTime = (d) => new Date(d).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
  const pad = (n) => n.toString().padStart(2,'0');
  const getDoctor = (id) => state.doctors.find(d => String(d.id) === String(id)) || {name:'Doctor'};
  const getOwner = (id) => state.owners.find(o => o.id === id) || {name:'Owner', phone:'—'};
  const getPet = (id) => state.pets.find(p => p.id === id) || {name:'Pet', breed:'—', age:'—'};

  async function loadData(){
    state.loading = true;
    renderAll();
    await loadDoctors();
    await loadAppointmentsForClinic();
    state.loading = false;
    renderAll();
    if(!CLINIC_ID){
      console.warn('No clinic_id detected; fetched doctors without clinic filter.');
    }
  }

  async function loadDoctors(){
    try{
      const res = await fetch(endpoints.doctors(CLINIC_ID), { credentials: 'include' });
      const json = await res.json();
      const list = json?.doctors || json?.data?.doctors || [];
      state.doctors = list.map(doc => ({
        ...doc,
        name: doc.name || doc.doctor_name || `Doctor #${doc.id}`,
        online: doc.toggle_availability !== 0 && doc.toggle_availability !== false,
        breaks: doc.breaks || []
      }));
    }catch(err){
      console.error('Failed to load doctors', err);
      state.doctors = [];
    }
  }

  async function loadAppointmentsForClinic(){
    const doctorIds = (state.doctors || []).map(d => d.id);
    if(qsDoctor && !doctorIds.includes(Number(qsDoctor))) doctorIds.push(Number(qsDoctor));
    const results = await Promise.all(doctorIds.map(id => {
      let doc = state.doctors.find(d => String(d.id)===String(id));
      if(!doc){
        doc = { id, name: `Doctor #${id}`, online: true, breaks: [] };
        state.doctors.push(doc);
      }
      return fetchDocAppointments(doc);
    }));
    state.appointments = results.flat();
  }

  async function fetchDocAppointments(doc){
    try{
      const res = await fetch(endpoints.appointmentsByDoctor(doc.id), { credentials: 'include' });
      const json = await res.json();
      const list = json?.data?.appointments || [];
      return list.map(item => normalizeAppointment(item, doc));
    }catch(err){
      console.error('Failed to load appointments for doctor', doc?.id, err);
      return [];
    }
  }

  function normalizeAppointment(raw, doctor){
    const patient = raw?.patient || {};
    const ownerId = ensureOwner(patient);
    const petName = raw?.pet_name || raw?.pet?.name || patient.name || 'Pet';
    const petId = ensurePet(petName, ownerId);
    const times = deriveTimes(raw?.date, raw?.time_slot);
    const doctorId = String(doctor?.id ?? raw?.doctor?.id ?? raw?.doctor_id ?? raw?.doctorId ?? '');
    const amountValue = normalizeAmount(raw?.amount);
    return {
      id: String(raw?.id ?? `app_${Date.now()}`),
      doctorId,
      petId,
      ownerId,
      start: times.start,
      end: times.end,
      type: (raw?.service_type || raw?.type || 'in-clinic'),
      reason: raw?.reason || 'Appointment',
      status: raw?.status || 'upcoming',
      amount: amountValue,
      currency: raw?.currency || 'INR'
    };
  }

  function normalizeAmount(v){
    if(v === null || v === undefined || v === '') return null;
    const num = Number(v);
    if(Number.isNaN(num)) return null;
    // Treat large values as paise and convert to rupees
    return num > 1000 ? num / 100 : num;
  }

  function deriveTimes(dateStr, slotStr){
    const start = parseDateTime(dateStr, slotStr, 'start') || new Date();
    const end = parseDateTime(dateStr, slotStr, 'end') || new Date(start.getTime() + clinicConfig.slotDurationMinutes*60000);
    return { start: start.toISOString(), end: end.toISOString() };
  }

  function parseDateTime(dateStr, slotStr, which){
    if(!dateStr) return null;
    const base = new Date(dateStr);
    if(Number.isNaN(base.getTime())) return null;
    if(slotStr){
      const parts = slotStr.split(/-|to|–/i);
      const target = which === 'end' && parts[1] ? parts[1] : parts[0];
      const t = (target || '').trim();
      const m = t.match(/(\d{1,2})(?::(\d{2}))?\s*(AM|PM)?/i);
      if(m){
        let h = parseInt(m[1],10);
        const mins = parseInt(m[2] || '0',10);
        const ampm = (m[3] || '').toUpperCase();
        if(ampm === 'PM' && h < 12) h += 12;
        if(ampm === 'AM' && h === 12) h = 0;
        base.setHours(h, mins, 0, 0);
        return base;
      }
    }
    return base;
  }

  function ensureOwner(patient){
    const id = patient?.user_id ? `owner_${patient.user_id}` : (patient?.phone ? `owner_${patient.phone}` : `owner_${patient?.name || 'anon'}`);
    let owner = state.owners.find(o => o.id === id);
    if(!owner){
      owner = {id, name: patient?.name || 'Patient', phone: patient?.phone || '—'};
      state.owners.push(owner);
    }
    return id;
  }

  function ensurePet(name, ownerId){
    const safeName = name || 'Pet';
    const id = `pet_${ownerId}_${safeName.replace(/\s+/g,'_')}`;
    let pet = state.pets.find(p => p.id === id);
    if(!pet){
      pet = {id, name: safeName, ownerId, breed:'', age:''};
      state.pets.push(pet);
    }
    return id;
  }

  function populateDoctorFilter(){
    const select = $('doctorFilter');
    if(!select) return;
    select.innerHTML = '<option value="">All doctors</option>';
    state.doctors.forEach(doc => {
      const opt = document.createElement('option');
      opt.value = doc.id; opt.textContent = doc.name + (doc.online ? ' • Online' : ' • Offline');
      select.appendChild(opt);
    });
  }

  function renderKPIs(){
    const now = new Date();
    const since30 = new Date(now); since30.setDate(now.getDate() - 30);
    const last30 = state.appointments.filter(a => new Date(a.start) >= since30);
    const ownerIds = new Set(last30.map(a => a.ownerId));
    $('k1').textContent = ownerIds.size.toLocaleString(); // Locked-in Pet Parents (last 30d unique owners)

    // Repeat rate = owners with 2+ appts in last 30d / total owners
    const counts = {};
    last30.forEach(a => { counts[a.ownerId] = (counts[a.ownerId] || 0) + 1; });
    const repeatOwners = Object.values(counts).filter(c => c >= 2).length;
    const repeatRate = ownerIds.size ? Math.round((repeatOwners / ownerIds.size) * 100) : 0;
    $('k2').textContent = `${repeatRate}%`;

    // Telemed Revenue (30d) — using appointment amount where type is telemedicine
    const teleRevenue = last30
      .filter(a => String(a.type).toLowerCase() === 'telemedicine' && a.amount != null)
      .reduce((sum, a) => sum + (a.amount || 0), 0);
    $('k3').textContent = formatCurrency(teleRevenue, '₹');

    // Clinic Utilization Today
    const totalSlots = state.doctors.length ? state.doctors.length * ((parseInt(clinicConfig.workingHours.end.split(':')[0]) - parseInt(clinicConfig.workingHours.start.split(':')[0]))*2 || 0) : 0;
    const booked = state.appointments.filter(a=> sameDay(a.start,state.selectedDate) && a.status!=='cancelled').length;
    const utilization = totalSlots > 0 ? Math.round((booked/totalSlots)*100) : 0;
    $('k4').textContent = utilization + '%';
  }

  function formatCurrency(value, symbol='₹'){
    if(value == null) return symbol + '0';
    return symbol + value.toLocaleString(undefined, { maximumFractionDigits: 2 });
  }

  function renderCalendar(){
    const month = new Date(state.selectedDate).getMonth();
    const year = new Date(state.selectedDate).getFullYear();
    $('monthLabel').textContent = new Date(year,month,1).toLocaleString('default',{month:'long',year:'numeric'});
    const first = new Date(year,month,1), start=first.getDay();
    const days = new Date(year,month+1,0).getDate();
    const container = $('calendarDays'); container.innerHTML='';
    for(let i=0;i<start;i++){ container.appendChild(createDay()) }
    for(let d=1; d<=days; d++){
      const date = new Date(year,month,d);
      const dayEl = createDay(date);
      const apps = state.appointments.filter(a=> sameDay(a.start,date) && passesFilters(a));
      apps.forEach(a=>{
        const dot = document.createElement('div'); dot.className='dot ' + a.type;
        dot.title = `${formatTime(a.start)} ${getPet(a.petId).name} (${a.type})`;
        dayEl.querySelector('.dots').appendChild(dot);
      });
      if(sameDay(date,new Date())) dayEl.classList.add('today');
      if(sameDay(date,state.selectedDate)) dayEl.classList.add('selected');
      dayEl.addEventListener('click', ()=>{ state.selectedDate=date; renderAll(); openDayPanel(date); });
      if(apps.length){
        dayEl.addEventListener('mouseenter',(e)=> showTooltip(e, apps.slice(0,4).map(a=>`${formatTime(a.start)} ${getPet(a.petId).name} — ${a.type}`).join('<br>')));
        dayEl.addEventListener('mouseleave', hideTooltip);
      }
      container.appendChild(dayEl);
    }
  }
  function createDay(date){
    const div = document.createElement('div'); div.className='day';
    const num = document.createElement('div'); num.className='num'; num.textContent = date ? date.getDate() : '';
    div.appendChild(num);
    const dots = document.createElement('div'); dots.className='dots'; div.appendChild(dots);
    return div;
  }

  function renderTimeline(){
    $('timelineTitle').textContent = sameDay(state.selectedDate,new Date()) ? "Today's Schedule" : state.selectedDate.toDateString();
    const left = $('leftTimes'); left.innerHTML='';
    for(let h=8; h<=20; h++){
      const t = document.createElement('div'); t.className='timeRow'; t.style.height='40px'; t.textContent = pad(h)+':00'; left.appendChild(t);
    }
    const right = $('rightArea'); right.innerHTML='';
    const dayApps = state.appointments.filter(a=> sameDay(a.start,state.selectedDate) && passesFilters(a));
    const conflicts = findConflicts(dayApps);
    dayApps.forEach(a=>{
      const s = new Date(a.start), e = new Date(a.end);
      const minutesFrom8 = (s.getHours()-8)*60 + s.getMinutes();
      const top = (minutesFrom8/60) * 40;
      const height = Math.max(36, ((e-s)/60000)/60 * 40);
      const ap = document.createElement('div'); ap.className = 'appt '+a.type;
      if(conflicts.includes(a.id)) ap.classList.add('conflict');
      ap.style.top = top + 'px'; ap.style.height = height + 'px';
      ap.innerHTML = `<div class="meta"><div>${formatTime(a.start)} • ${getPet(a.petId).name}</div><div style="font-size:12px;color:#274b6a">${getDoctor(a.doctorId).name}</div></div><div class="sub">${a.reason} <span style="float:right">${a.status}</span></div>`;
      if(a.type==='telemedicine'){
        const btn = document.createElement('button'); btn.className='joinBtn'; btn.textContent='Join'; btn.style.marginTop='6px';
        btn.onclick = (ev)=>{ ev.stopPropagation(); startTeleSimulation(a); };
        ap.appendChild(btn);
      }
      ap.addEventListener('click', ()=> openPatientPanel(getPet(a.petId)));
      ap.addEventListener('mouseenter',(ev)=> showTooltip(ev, `${getPet(a.petId).name} — ${formatTime(a.start)}<br>${getOwner(a.ownerId).name} • ${a.reason}`));
      ap.addEventListener('mouseleave', hideTooltip);
      right.appendChild(ap);
    });
  }

  function renderDocGrid(){
    const grid = $('docGrid'); grid.innerHTML='';
    const filteredDocs = state.doctors.filter(d=> state.filterDoctors.length ? state.filterDoctors.includes(String(d.id)) : true);
    filteredDocs.forEach(doc=>{
      const col = document.createElement('div'); col.className='col';
      const name = document.createElement('div'); name.className='docName'; name.textContent = doc.name + (doc.online ? ' • Online' : ' • Offline');
      col.appendChild(name);
      for(let h=8; h<20; h++){
        for(let m=0;m<60;m+=30){
          const slot = document.createElement('div'); slot.className='slot free';
          const t = new Date(state.selectedDate); t.setHours(h,m,0,0);
          slot.textContent = `${pad(h)}:${pad(m)}`;
          const inBreak = (doc.breaks||[]).some(b=> isBetweenTime(b.start,b.end,t));
          const booked = state.appointments.some(a=> String(a.doctorId)===String(doc.id) && new Date(a.start) <= t && new Date(a.end) > t && a.status!=='cancelled');
          if(booked){ slot.className='slot booked'; slot.textContent=`${pad(h)}:${pad(m)} · Booked`; }
          else if(inBreak){ slot.className='slot break'; slot.textContent=`${pad(h)}:${pad(m)} · Break`; }
          else if(!doc.online){ slot.className='slot unavail'; slot.textContent=`${pad(h)}:${pad(m)} · Unavailable`; }
          slot.addEventListener('click', ()=>{ if(slot.classList.contains('booked')||slot.classList.contains('break')||slot.classList.contains('unavail')) return; openCreateModalWithSlot(t, doc.id); });
          col.appendChild(slot);
        }
      }
      grid.appendChild(col);
    });
  }

  function renderAll(){
    populateDoctorFilter();
    renderKPIs();
    renderCalendar();
    renderTimeline();
    renderDocGrid();
  }

  function passesFilters(a){
    if(!state.filterTypes.includes('all') && !state.filterTypes.includes(a.type)) return false;
    if(state.filterStatus.length && !state.filterStatus.includes(a.status)) return false;
    if(state.filterDoctors.length && !state.filterDoctors.includes(String(a.doctorId))) return false;
    if(state.search){
      const hay = `${getPet(a.petId).name} ${getOwner(a.ownerId).name} ${getOwner(a.ownerId).phone}`.toLowerCase();
      if(!hay.includes(state.search.toLowerCase())) return false;
    }
    return true;
  }
  function isBetweenTime(start,end,dt){
    const [sh,sm]=start.split(':').map(Number); const [eh,em]=end.split(':').map(Number);
    const s = new Date(dt); s.setHours(sh,sm,0,0); const e = new Date(dt); e.setHours(eh,em,0,0);
    return dt>=s && dt<e;
  }
  function findConflicts(dayApps){
    const byDoc = {}; const conflicts=[];
    dayApps.forEach(a=> { byDoc[a.doctorId] = byDoc[a.doctorId] || []; byDoc[a.doctorId].push(a); });
    Object.values(byDoc).forEach(arr=>{
      arr.sort((x,y)=> new Date(x.start) - new Date(y.start));
      for(let i=0;i<arr.length-1;i++){
        if(new Date(arr[i].end) > new Date(arr[i+1].start)){
          conflicts.push(arr[i].id); conflicts.push(arr[i+1].id);
        }
      }
    });
    return conflicts;
  }

  function showTooltip(ev,html){
    const t = $('tooltip'); t.innerHTML = html; t.style.display='block';
    t.style.left = (ev.clientX + 12) + 'px'; t.style.top = (ev.clientY + 12) + 'px';
  }
  function hideTooltip(){ $('tooltip').style.display='none'; }

  function openCreateModalWithSlot(dt, doctorId){
    state.selectedSlot = dt;
    state.selectedDoctor = doctorId ? String(doctorId) : null;
    $('modalOverlay').style.display='flex';
    state.modalStep = 1; renderModal();
  }

  function renderModal(){
    $('step1').style.display = state.modalStep===1 ? 'block' : 'none';
    $('step2').style.display = state.modalStep===2 ? 'block' : 'none';
    $('step3').style.display = state.modalStep===3 ? 'block' : 'none';
    renderOwnerResults();
    renderPetCards();
    const mdoc = $('modalDoctor'); mdoc.innerHTML='';
    state.doctors.forEach(d=>{ const o = document.createElement('option'); o.value=d.id; o.textContent = d.name + (d.online?' • Online':' • Offline'); mdoc.appendChild(o) });
    if(state.selectedDoctor) mdoc.value = state.selectedDoctor;
    const slotArea = $('modalSlots'); slotArea.innerHTML='';
    const selDoc = state.selectedDoctor || (mdoc.options[0] && mdoc.options[0].value) || (state.doctors[0] && String(state.doctors[0].id));
    for(let h=8; h<20; h++){
      for(let m=0;m<60;m+=30){
        const b = document.createElement('button'); b.className='pill'; b.style.margin='6px'; b.textContent=`${pad(h)}:${pad(m)}`;
        const t = new Date(state.selectedDate); t.setHours(h,m,0,0);
        const booked = state.appointments.some(a=> String(a.doctorId)===String(selDoc) && new Date(a.start) <= t && new Date(a.end) > t && a.status!=='cancelled');
        const doc = getDoctor(selDoc);
        const inBreak = (doc.breaks||[]).some(bx=> isBetweenTime(bx.start,bx.end,t));
        if(booked){ b.disabled=true; b.textContent='Booked' } else if(inBreak){ b.disabled=true; b.textContent='Break' } else if(!doc.online){ b.disabled=true; b.textContent='Unavailable' }
        b.onclick = ()=>{ state.selectedSlot = t; highlightModalSlot(b) };
        slotArea.appendChild(b);
      }
    }
    $('modalDoctor').onchange = ()=>{ state.selectedDoctor = $('modalDoctor').value; renderModal(); };
  }

  function highlightModalSlot(btn){
    document.querySelectorAll('#modalSlots button').forEach(b=> b.style.boxShadow='none');
    btn.style.boxShadow='0 8px 20px rgba(37,99,235,0.12)';
  }

  function renderOwnerResults(){
    const q = $('ownerSearch').value.trim().toLowerCase();
    const out = $('ownerResults'); out.innerHTML='';
    if(!q) return;
    const found = state.owners.filter(o=> (o.phone || '').includes(q) || (o.name || '').toLowerCase().includes(q));
    if(found.length===0){ out.textContent = 'No owner found — will create new'; return; }
    found.forEach(o=>{
      const d = document.createElement('div'); d.className='small'; d.style.padding='6px'; d.style.cursor='pointer'; d.textContent = `${o.name} • ${o.phone}`;
      d.onclick = ()=>{ state.selectedOwner = o; $('ownerResults').innerHTML = `Selected: ${o.name}`; };
      out.appendChild(d);
    });
  }

  function renderPetCards(){
    const wrap = $('petCards'); wrap.innerHTML='';
    const ownerId = state.selectedOwner ? state.selectedOwner.id : null;
    const list = ownerId ? state.pets.filter(p=> p.ownerId===ownerId ) : state.pets;
    list.forEach(p=>{
      const card = document.createElement('div'); card.style.padding='10px'; card.style.border='1px solid #eef6ff'; card.style.cursor='pointer';
      card.innerHTML = `<div style="font-weight:800">${p.name}</div><div class="small">${p.breed} • ${p.age}</div>`;
      card.onclick = ()=>{ state.selectedPet = p; document.querySelectorAll('#petCards div').forEach(n=>n.style.outline='none'); card.style.outline='3px solid rgba(37,99,235,0.12)'; };
      wrap.appendChild(card);
    });
  }

  function createApptFromModal(){
    if(!state.selectedOwner){ alert('Select or enter owner'); return; }
    if(!state.selectedPet){ alert('Select pet'); return; }
    if(!state.selectedSlot){ alert('Select slot'); return; }
    const ap = {
      id: 'app_' + Date.now(),
      doctorId: state.selectedDoctor || $('modalDoctor').value || (state.doctors[0] ? String(state.doctors[0].id) : null),
      petId: state.selectedPet.id,
      ownerId: state.selectedOwner.id || ('owner_' + Date.now()),
      start: state.selectedSlot.toISOString(),
      end: new Date(state.selectedSlot.getTime() + clinicConfig.slotDurationMinutes*60000).toISOString(),
      type: $('typeSelect').value,
      reason: $('reasonSelect').value,
      status: 'upcoming'
    };
    const conflict = state.appointments.some(a=> a.doctorId===ap.doctorId && new Date(a.start) < new Date(ap.end) && new Date(a.end) > new Date(ap.start) && a.status!=='cancelled');
    if(conflict && !confirm('Conflict detected for selected doctor. Create anyway?')) return;
    state.appointments.push(ap);
    alert('Appointment created (demo)');
    closeModalAll();
    renderAll();
  }

  function closeModalAll(){ $('modalOverlay').style.display='none'; state.selectedOwner=null; state.selectedPet=null; state.selectedSlot=null; state.selectedDoctor=null; }

  function openPatientPanel(pet){
    $('pName').textContent = pet.name; $('pBrief').textContent = `${pet.breed} • ${pet.age}`;
    const owner = getOwner(pet.ownerId); $('pOwner').textContent = `${owner.name} • ${owner.phone}`; $('pVisits').textContent = 'Last visit: Demo';
    $('patientPanel').classList.add('open');
  }
  function closePatientPanel(){ $('patientPanel').classList.remove('open'); }

  function openDayPanel(date){
    const listWrap = $('dayList');
    const dateLabel = date ? new Date(date).toDateString() : '';
    $('dayTitle').textContent = `Appointments — ${dateLabel}`;
    const items = state.appointments.filter(a=> sameDay(a.start,date) && passesFilters(a));
    if(!items.length){
      listWrap.innerHTML = '<div class="small">No appointments for this day.</div>';
    } else {
      listWrap.innerHTML = items.map(a=>{
        const owner = getOwner(a.ownerId);
        const pet = getPet(a.petId);
        return `
          <div style="padding:10px;border:1px solid #eef6ff;border-radius:10px;margin-bottom:8px">
            <div style="display:flex;justify-content:space-between;align-items:center;font-weight:700">
              <span>${formatTime(a.start)} • ${pet.name}</span>
              <span class="small">${(a.type||'').replace('_',' ')}</span>
            </div>
            <div class="small" style="margin-top:4px">${owner.name} • ${owner.phone || '—'}</div>
            <div class="small" style="margin-top:2px">Doctor: ${getDoctor(a.doctorId).name} • Status: ${a.status}</div>
          </div>
        `;
      }).join('');
    }
    $('dayPanel').classList.add('open');
  }
  function closeDayPanel(){ $('dayPanel').classList.remove('open'); }

  function startTeleSimulation(app){
    $('teleContent').textContent = `Joining tele-call for ${getPet(app.petId).name} with ${getDoctor(app.doctorId).name}...`;
    $('teleModal').style.display='block';
    setTimeout(()=>{ $('teleModal').style.display='none'; alert('Tele call ended (demo)'); }, 3500);
  }

  function exportCSV(){
    const rows=[['id','date','time','pet','owner','type','reason','doctor','status']];
    state.appointments.filter(a=> passesFilters(a)).forEach(a=>{
      const dt=new Date(a.start);
      rows.push([a.id, dt.toLocaleDateString(), formatTime(a.start), getPet(a.petId).name, getOwner(a.ownerId).name, a.type, a.reason, getDoctor(a.doctorId).name, a.status]);
    });
    const csv = rows.map(r=> r.map(c=> '"' + String(c).replace(/"/g,'""') + '"').join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv'}); const url = URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download='appointments.csv'; a.click(); URL.revokeObjectURL(url);
  }

  function findConflictsOnDate(date){
    const dayApps = state.appointments.filter(a=> sameDay(a.start,date));
    return findConflicts(dayApps);
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    populateDoctorFilter();
    renderAll();

    const filterBar = $('filterBar'), toggle = $('filterToggle');
    toggle.addEventListener('click', ()=>{
      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!expanded));
      if(expanded){ filterBar.style.maxHeight = '0'; filterBar.style.paddingTop='0'; filterBar.style.paddingBottom='0'; }
      else { filterBar.style.maxHeight = '260px'; filterBar.style.paddingTop='12px'; filterBar.style.paddingBottom='12px'; }
    });

    document.querySelectorAll('#appointmentsShell .pill').forEach(p=>{
      p.addEventListener('click', ()=>{
        const t = p.dataset.type, s = p.dataset.status, d = p.dataset.date;
        if(t!==undefined){
          if(t==='all'){ state.filterTypes=['all']; document.querySelectorAll('#appointmentsShell [data-type]').forEach(x=>x.classList.remove('active')); p.classList.add('active'); }
          else { p.classList.toggle('active'); const act = Array.from(document.querySelectorAll('#appointmentsShell [data-type].active')).map(n=>n.dataset.type); state.filterTypes = act.length?act:['all']; }
        } else if(s!==undefined){
          p.classList.toggle('active'); state.filterStatus = Array.from(document.querySelectorAll('#appointmentsShell [data-status].active')).map(n=>n.dataset.status);
        } else if(d!==undefined){
          document.querySelectorAll('#appointmentsShell [data-date]').forEach(x=>x.classList.remove('active')); p.classList.add('active');
          const days = parseInt(d); const nd = new Date(); nd.setDate(nd.getDate()+days); state.selectedDate = nd;
        }
        renderAll();
      });
    });

    $('doctorFilter').addEventListener('change', ()=>{ const val=$('doctorFilter').value; state.filterDoctors = val? [val]: []; renderAll(); });

    $('datePicker').addEventListener('change', ()=>{ state.selectedDate = new Date($('datePicker').value); renderAll(); });
    $('searchInput').addEventListener('input', (e)=>{ state.search = e.target.value; renderAll(); });

    $('prevMonth').addEventListener('click', ()=>{ const d=new Date(state.selectedDate); d.setMonth(d.getMonth()-1); state.selectedDate=new Date(d); renderAll(); });
    $('nextMonth').addEventListener('click', ()=>{ const d=new Date(state.selectedDate); d.setMonth(d.getMonth()+1); state.selectedDate=new Date(d); renderAll(); });

    $('tabTimeline').addEventListener('click', ()=>{ state.view='timeline'; $('tabTimeline').classList.add('active'); $('tabGrid').classList.remove('active'); $('timelineCard').style.display='block'; $('slotCard').style.display='none'; renderAll();});
    $('tabGrid').addEventListener('click', ()=>{ state.view='grid'; $('tabGrid').classList.add('active'); $('tabTimeline').classList.remove('active'); $('timelineCard').style.display='none'; $('slotCard').style.display='block'; renderAll();});

    $('openCreateBtn').addEventListener('click', ()=>{ $('modalOverlay').style.display='flex'; state.modalStep=1; renderModal(); });

    $('closeModal').addEventListener('click', ()=> closeModalAll());
    $('toPet').addEventListener('click', ()=>{ const q = $('ownerSearch').value.trim(); if(!q) return alert('Enter phone or owner'); state.selectedOwner = state.owners.find(o=> o.phone===q) || {id: 'owner_tmp_'+Date.now(), name: q, phone: q}; state.modalStep=2; renderModal(); });
    $('backTo1').addEventListener('click', ()=>{ state.modalStep=1; renderModal(); });
    $('toDetails').addEventListener('click', ()=>{ if(!state.selectedPet) return alert('Pick a pet'); state.modalStep=3; renderModal(); });
    $('backTo2').addEventListener('click', ()=>{ state.modalStep=2; renderModal(); });
    $('createApptBtn').addEventListener('click', createApptFromModal);

    $('addPet').addEventListener('click', ()=>{ const n=$('quickName').value.trim(), b=$('quickBreed').value.trim(); if(!n) return alert('Enter name'); const ownerId = state.selectedOwner ? state.selectedOwner.id : null; const p={id:'pet_'+Date.now(),name:n,ownerId:ownerId,breed:b||'Unknown',age:'0y'}; state.pets.push(p); renderPetCards(); });
    $('addOwner').addEventListener('click', ()=>{ const ph = prompt('Owner phone'); if(!ph) return; const o={id:'owner_'+Date.now(),name:'New Owner',phone:ph}; state.owners.push(o); state.selectedOwner=o; renderModal(); });

    $('exportBtn').addEventListener('click', exportCSV);
    $('printBtn').addEventListener('click', ()=> window.print());

    $('closePatient').addEventListener('click', closePatientPanel);
    $('closeDayPanel').addEventListener('click', closeDayPanel);
    $('startTele').addEventListener('click', ()=> alert('Demo: start tele — integrate real WebRTC/SDK in production'));
    $('reschedBtn').addEventListener('click', ()=> alert('Demo: open reschedule flow'));

    window.findConflictsOnDate = findConflictsOnDate;
    loadData();
  });
})();
</script>
@endsection
