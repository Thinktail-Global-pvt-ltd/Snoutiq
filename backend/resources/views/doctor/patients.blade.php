{{-- resources/views/doctor/patients.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Patient Management')
@section('page_title','Patient Management')

@php
  $sessionRole = session('role')
      ?? data_get(session('auth_full'), 'role')
      ?? data_get(session('user'), 'role');

  $clinicCandidates = [
      session('clinic_id'),
      session('vet_registerations_temp_id'),
      session('vet_registeration_id'),
      session('vet_id'),
      data_get(session('user'), 'clinic_id'),
      data_get(session('user'), 'vet_registeration_id'),
      data_get(session('auth_full'), 'clinic_id'),
      data_get(session('auth_full'), 'user.clinic_id'),
      data_get(session('auth_full'), 'user.vet_registeration_id'),
  ];

  // For receptionists, keep clinic context (avoid overriding with their own user_id)
  if (!in_array($sessionRole, ['doctor', 'receptionist'], true)) {
      array_unshift(
          $clinicCandidates,
          session('user_id'),
          data_get(session('user'), 'id')
      );
  }

  $resolvedClinicId = null;
  foreach ($clinicCandidates as $candidate) {
      if ($candidate === null || $candidate === '') {
          continue;
      }
      $num = (int) $candidate;
      if ($num > 0) {
          $resolvedClinicId = $num;
          break;
      }
  }

  $sessionDoctorId = session('doctor_id')
      ?? data_get(session('doctor'), 'id')
      ?? data_get(session('auth_full'), 'doctor_id')
      ?? data_get(session('auth_full'), 'user.doctor_id')
      ?? ($sessionRole === 'doctor' ? (session('user_id') ?? data_get(session('user'), 'id')) : null);

  // Receptionist: fallback to clinic from receptionist record when session misses it
  if (!$resolvedClinicId && $sessionRole === 'receptionist') {
      $receptionistId = session('receptionist_id')
          ?? data_get(session('auth_full'), 'receptionist_id')
          ?? data_get(session('auth_full'), 'user_id')
          ?? session('user_id');
      if ($receptionistId) {
          $rec = \App\Models\Receptionist::find((int) $receptionistId);
          if ($rec && (int) $rec->vet_registeration_id > 0) {
              $resolvedClinicId = (int) $rec->vet_registeration_id;
          }
      }
  }
@endphp

@section('head')
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    .pm-shell { --pm-bg:#f5f7fb; --pm-panel:#ffffff; --pm-muted:#6b7280; --pm-blue:#5b5be5; --pm-purple:#9f7aea; --pm-green:#10b981; --pm-orange:#f97316; --pm-radius:12px; --pm-shadow:0 8px 28px rgba(10,20,40,0.06); background:var(--pm-bg); border-radius:16px; padding:18px; font-family:'Inter',system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial; color:#0b1220; }
    .pm-header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .pm-title{font-size:24px;font-weight:700;color:var(--pm-blue)}
    .pm-subtitle{color:var(--pm-muted);font-size:13px;margin-top:4px}
    .pm-controls{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .pm-chip{background:#eef2ff;color:#4338ca;padding:8px 10px;border-radius:999px;font-weight:700;font-size:13px}
    .pm-btn{padding:8px 12px;border-radius:10px;border:0;cursor:pointer;font-weight:700;font-size:13px}
    .pm-btn.pm-primary{background:linear-gradient(90deg,var(--pm-blue),var(--pm-purple));color:white}
    .pm-btn.pm-ghost{background:#fff;border:1px solid #eef6ff;color:#1f2937}
    .pm-content{display:grid;grid-template-columns:380px 1fr;gap:18px;margin-top:12px;align-items:start}
    .pm-left,.pm-right{background:var(--pm-panel);padding:14px;border-radius:var(--pm-radius);box-shadow:var(--pm-shadow)}
    .pm-left{min-height:640px}
    .pm-right{min-height:680px}
    .pm-searchRow{display:flex;gap:8px;align-items:center;margin-bottom:10px}
    .pm-input{padding:10px;border-radius:10px;border:1px solid #eef6ff;width:100%;font-size:14px;background:#fff}
    .pm-select{max-width:190px}
    .pm-tagsRow{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px}
    .pm-tagWrap{display:flex;gap:6px;flex-wrap:wrap}
    .pm-tag{padding:6px 8px;border-radius:999px;background:#f1f5f9;color:var(--pm-muted);font-weight:700;font-size:12px;cursor:pointer;border:1px solid transparent}
    .pm-tag.is-active{background:var(--pm-blue);color:#fff;border-color:var(--pm-blue)}
    .pm-list{margin-top:4px;display:flex;flex-direction:column;gap:8px;max-height:640px;overflow:auto}
    .pm-row{display:flex;gap:10px;align-items:flex-start;padding:10px;border-radius:10px;cursor:pointer;border:1px solid transparent;transition:all .15s ease}
    .pm-row:hover{background:#fcfdff;border-color:#eef6ff}
    .pm-row.is-active{border-color:var(--pm-blue);background:#f8fbff}
    .pm-avatar{width:56px;height:56px;border-radius:10px;background:#f8fafc;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#1f2937}
    .pm-info{flex:1}
    .pm-name{font-weight:800}
    .pm-meta{font-size:13px;color:var(--pm-muted);margin-top:2px;line-height:1.4}
    .pm-badges{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
    .pm-badge{font-size:11px;background:#eef2ff;color:#4338ca;padding:4px 8px;border-radius:999px;font-weight:700}
    .pm-profileHeader{display:flex;gap:14px;align-items:center;border-bottom:1px dashed #eef6ff;padding-bottom:12px;margin-bottom:12px}
    .pm-avatar-large{width:120px;height:120px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;font-size:40px;font-weight:900;border:1px solid #eef6ff}
    .pm-profileName{font-size:20px;font-weight:800}
    .pm-profileSub{color:var(--pm-muted);margin-top:4px}
    .pm-profileMeta{margin-top:6px;font-size:13px;color:var(--pm-muted)}
    .pm-actions{display:flex;gap:8px;margin-left:auto;flex-wrap:wrap}
    .pm-gridRow{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-bottom:12px}
    .pm-stat{background:#f8fafc;border:1px solid #eef6ff;border-radius:10px;padding:10px}
    .pm-statLabel{font-size:12px;color:var(--pm-muted);text-transform:uppercase;letter-spacing:.04em}
    .pm-statValue{font-size:18px;font-weight:800;margin-top:4px}
    .pm-statHint{font-size:12px;color:var(--pm-muted);margin-top:2px}
    .pm-section{margin-top:12px}
    .pm-sectionTitle{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;font-weight:800}
    .pm-card{background:linear-gradient(145deg,rgba(91,91,229,0.12),rgba(159,122,234,0.1));padding:12px;border-radius:14px;border:1px solid rgba(91,91,229,0.18);box-shadow:0 12px 24px rgba(91,91,229,0.12)}
    .pm-records{display:flex;flex-direction:column;gap:10px}
    .pm-record{padding:12px;border:1px solid rgba(91,91,229,0.2);border-radius:12px;background:linear-gradient(160deg,rgba(91,91,229,0.13),rgba(159,122,234,0.1));box-shadow:0 10px 22px rgba(91,91,229,0.14)}
    .pm-record-title{font-weight:800;color:#0b1220}
    .pm-record-meta{font-size:12px;color:var(--pm-muted);margin-top:4px}
    .pm-record-notes{font-size:14px;margin-top:8px;color:#0f172a;background:rgba(255,255,255,0.7);padding:8px 10px;border-radius:10px}
    .pm-record-detail{margin-top:8px;font-size:13px;line-height:1.5;color:#111827}
    .pm-record-detail dt{font-weight:700;color:#0b1220}
    .pm-record-detail dd{margin:0 0 6px 0}
    .pm-record-card{padding:16px;border:1px solid rgba(91,91,229,0.22);border-radius:16px;background:linear-gradient(180deg,rgba(91,91,229,0.06),rgba(255,255,255,0.95));box-shadow:0 12px 28px rgba(91,91,229,0.16)}
    .pm-record-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
    .pm-record-tags{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px}
    .pm-tag-soft{padding:5px 10px;border-radius:999px;font-size:12px;font-weight:800;border:1px solid rgba(91,91,229,0.26);background:rgba(91,91,229,0.12);color:#2d2f83;box-shadow:0 4px 10px rgba(91,91,229,0.12)}
    .pm-record-details{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:12px}
    .pm-record-row{padding:12px;border:1px solid #dfe4ff;border-radius:12px;background:rgba(255,255,255,0.9);box-shadow:0 6px 18px rgba(15,23,42,0.06)}
    .pm-record-label{font-size:12px;font-weight:800;color:#111827;margin-bottom:6px;letter-spacing:.01em;text-transform:uppercase}
    .pm-record-value{font-size:13px;color:#0b1220;line-height:1.45}
    .pm-record-actions{display:flex;justify-content:flex-end;margin-top:10px;gap:8px;flex-wrap:wrap}
    .pm-empty{color:var(--pm-muted);font-size:13px;padding:8px 0}
    .pm-alert{background:#fff1f2;border:1px solid #fecdd3;color:#b91c1c;padding:12px;border-radius:12px;margin:10px 0;font-size:14px}
    .pm-overlay{position:fixed;inset:0;background:rgba(8,10,14,0.45);display:none;align-items:flex-start;justify-content:center;z-index:50;padding:12px;overflow-y:auto}
    .pm-overlay.is-visible{display:flex}
    .pm-modal{width:760px;max-width:100%;background:#fff!important;border-radius:12px;padding:16px;box-shadow:0 18px 48px rgba(2,6,23,0.12);max-height:none;overflow:visible;opacity:1}
    .pm-formRow{display:flex;gap:12px;flex-wrap:wrap;margin-top:10px}
    .pm-formField{flex:1;min-width:220px;display:flex;flex-direction:column;gap:6px}
    .pm-input.textarea{height:120px}
    .pm-modalActions{display:flex;justify-content:flex-end;gap:8px;margin-top:12px}
    .record-modal{width:1240px;max-width:98vw;padding:18px 20px;max-height:none;overflow:visible}
    .record-header{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:8px}
    .record-title{font-weight:800;font-size:20px;margin-bottom:2px}
    .record-patient{font-size:14px;color:#0f172a}
    .record-patientSub{font-size:13px;color:var(--pm-muted);margin-top:2px}
    .record-status{font-size:13px;color:#4f46e5;margin-top:6px}
    .record-section{background:#f6f8fb;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-top:12px}
    .record-sectionTitle{font-weight:700;margin-bottom:6px;font-size:14px}
    .record-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px}
    .record-grid.narrow{grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}
    .record-field{display:flex;flex-direction:column;gap:4px}
    .record-label{font-size:12px;font-weight:700;color:#111827}
    .record-input{width:100%;padding:10px 11px;border-radius:12px;border:1px solid #d7dde6;font-size:14px;background:#fff}
    .record-textarea{min-height:70px;resize:vertical}
    .record-upload{border:2px dashed #c7d2fe;border-radius:12px;padding:12px;text-align:center;font-weight:700;color:#4338ca;cursor:pointer;background:#f8f9ff;display:inline-block}
    .record-note{font-size:12px;color:var(--pm-muted);margin-top:4px}
    .record-critical{display:block}
    .record-critical.is-visible{display:block}
    .record-actions{
      display:flex;
      justify-content:flex-end;
      gap:10px;
      margin-top:12px;
      position:sticky;
      bottom:0;
      background:#fff;
      padding-top:10px;
      padding-bottom:6px;
      border-top:1px solid #e5e7eb;
    }
    .pm-petList{display:flex;flex-direction:column;gap:10px}
    .pm-petRow{display:flex;align-items:flex-start;gap:10px;padding:10px;border-radius:12px;border:1px solid #eef6ff;background:#f8fafc}
    .pm-petAvatar{width:40px;height:40px;border-radius:10px;background:#e0e7ff;color:#1f2937;font-weight:800;display:flex;align-items:center;justify-content:center}
    .pm-petBody{flex:1}
    .pm-petName{font-weight:800}
    .pm-petMeta{font-size:12px;color:var(--pm-muted);margin-top:2px}
    .pm-pill{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;background:#eef2ff;color:#4338ca;border:1px solid #e0e7ff;margin-right:6px}
    /* Make long filenames/notes wrap so content fits without horizontal scroll */
    .pm-records,
    .pm-card,
    .pm-right{
      overflow-x: visible;
    }
    .pm-record-head{flex-wrap:wrap}
    .pm-record-title,
    .pm-record-notes{
      word-break: break-word;
      white-space: normal;
    }
    .pm-small{font-size:13px;color:var(--pm-muted)}
    @media (max-width:1100px){.pm-content{grid-template-columns:1fr}.pm-left,.pm-right{min-height:unset}.pm-profileHeader{flex-direction:column;align-items:flex-start}.pm-actions{width:100%;justify-content:flex-start}}
    .modal-overlay{position:fixed;inset:0;background:rgba(8,10,14,0.45);display:none;align-items:center;justify-content:center;padding:20px;z-index:70}
    .modal-overlay.active{display:flex}
    .modal-card{width:100%;max-width:640px;background:#fff;border-radius:24px;box-shadow:0 20px 60px rgba(15,23,42,0.25);padding:30px 32px}
    .tab-button{padding:0.35rem 0.85rem;border-radius:999px;font-size:0.85rem;font-weight:600;border:1px solid transparent;background:#f8fafc;color:#475569;cursor:pointer;transition:background .2s}
    .tab-button.active{background:#4f46e5;color:#fff}
    .tab-button + .tab-button{margin-left:0.5rem}
    .modal-card form .space-y-4>* + *{margin-top:1rem}
  </style>
@endsection

@section('content')
<div class="pm-shell">
  <div class="pm-header">
    <div>
      <div class="pm-title">Patient Management</div>
      <div class="pm-subtitle">Patient list, pet profile, medical records & documents</div>
    </div>
    <div class="pm-controls">
      <button class="pm-btn pm-primary" type="button" id="pm-booking-open">+ New Booking</button>
      <button class="pm-btn pm-ghost" id="pm-refresh">Refresh</button>
      <button class="pm-btn pm-primary" data-role="open-upload">+ Upload record</button>
    </div>
  </div>

  @if(!$resolvedClinicId)
    <div class="pm-alert">
      We could not detect a clinic ID in your session. Open this page from the doctor or clinic dashboard where a clinic is selected.
    </div>
  @endif

  <div class="pm-content">
    <div class="pm-left">
      <div class="pm-searchRow">
        <input id="pm-search" class="pm-input" type="search" placeholder="Search patient, pet, phone…">
        <select id="pm-sort" class="pm-input pm-select">
          <option value="recent">Last activity (new→old)</option>
          <option value="records">Records (high→low)</option>
          <option value="name">Patient name (A→Z)</option>
        </select>
      </div>
      <div class="pm-tagsRow">
        <div class="pm-small">Quick filters:</div>
        <div id="pm-tag-filters" class="pm-tagWrap"></div>
      </div>
      <div id="pm-list" class="pm-list" role="list">
        <div class="pm-empty" id="pm-loading">Loading patients…</div>
      </div>
      <div class="pm-small" id="pm-list-count"></div>
    </div>

    <div class="pm-right">
      <div class="pm-profileHeader">
        <div class="pm-avatar-large" id="pm-profile-avatar">🐾</div>
        <div class="pm-profileMain">
          <div class="pm-profileName" id="pm-profile-name">Select a patient</div>
          <div class="pm-profileSub" id="pm-profile-sub">Patient and pet details will appear here.</div>
          <div class="pm-profileMeta" id="pm-profile-meta"></div>
        </div>
        <div class="pm-actions">
          <button class="pm-btn pm-ghost" id="pm-refresh-profile">Reload</button>
          <button class="pm-btn pm-primary" data-role="open-pet">+ Add Pet</button>
          <button class="pm-btn pm-primary" data-role="open-upload">Upload</button>
        </div>
      </div>

      <div class="pm-gridRow">
        <div class="pm-stat">
          <div class="pm-statLabel">Records</div>
          <div class="pm-statValue" id="pm-stat-records">—</div>
          <div class="pm-statHint" id="pm-stat-last-record">Last upload —</div>
        </div>
        <div class="pm-stat">
          <div class="pm-statLabel">Contact</div>
          <div class="pm-statValue" id="pm-stat-contact">—</div>
          <div class="pm-statHint" id="pm-stat-email">—</div>
        </div>
      </div>

      <div class="pm-section">
        <div class="pm-sectionTitle">
          <div>Pets</div>
          <div class="pm-actions">
            <div class="pm-small" id="pm-pet-count">—</div>
            <button class="pm-btn pm-primary" data-role="open-pet">+ Add Pet</button>
          </div>
        </div>
        <div class="pm-card">
          <div id="pm-pets-empty" class="pm-empty">Select a patient to see pets.</div>
          <div id="pm-pets-list" class="pm-petList"></div>
        </div>
      </div>

      <div class="pm-section">
        <div class="pm-sectionTitle">
          <div>Medical Records</div>
          <div class="pm-small" id="pm-record-count">—</div>
        </div>
        <div class="pm-card">
          <div id="pm-records-empty" class="pm-empty">Select a patient to see uploaded files.</div>
          <div id="pm-records-list" class="pm-records"></div>
        </div>
      </div>
    </div>
  </div>
  </div>
</div>

<div id="booking-modal" class="modal-overlay" hidden aria-hidden="true">
  <div class="modal-card">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold tracking-wide text-indigo-600 uppercase">Front Desk</p>
        <h3 class="text-xl font-semibold text-slate-900">Create Booking</h3>
      </div>
      <button type="button" data-close class="text-slate-500 hover:text-slate-700 text-lg">&times;</button>
    </div>

    <div class="bg-slate-50 rounded-xl p-2 flex items-center gap-2 mt-4">
      <button type="button" class="tab-button active" data-patient-mode="existing">Existing Patient</button>
      <button type="button" class="tab-button" data-patient-mode="new">New Patient</button>
    </div>

    <form id="booking-form" class="space-y-5 mt-3">
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
          <select id="booking-doctor-select" name="doctor_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
            <option value="">Any available doctor</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Service Type</label>
          <select name="service_type" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
            <option value="in_clinic">In Clinic</option>
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
            Select a doctor and date first to load available slots.
          </p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Notes</label>
        <textarea name="notes" rows="3" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500" placeholder="Any reason or context for this visit"></textarea>
      </div>

      <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
        <button type="button" data-close class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">Cancel</button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save Booking</button>
      </div>
    </form>
  </div>
</div>

<div id="pet-modal" class="pm-overlay">
  <div class="pm-modal" role="dialog" aria-modal="true">
    <div class="record-header">
      <div>
        <div class="record-title">Add Pet</div>
        <div id="pet-modal-patient" class="record-patient">Patient • —</div>
      </div>
      <button type="button" class="pm-btn pm-ghost" data-role="close-pet-modal">Close</button>
    </div>
    <form id="pet-form" class="space-y-2">
      <input type="hidden" id="pet-user-id" name="user_id">
      <div class="pm-formRow">
        <div class="pm-formField">
          <label class="record-label" for="pet-name">Pet Name</label>
          <input id="pet-name" name="name" type="text" class="pm-input" placeholder="E.g. Max" required>
        </div>
        <div class="pm-formField">
          <label class="record-label" for="pet-breed">Breed</label>
          <input id="pet-breed" name="breed" type="text" class="pm-input" placeholder="Breed" required>
        </div>
      </div>
      <div class="pm-formRow">
        <div class="pm-formField">
          <label class="record-label" for="pet-age">Age (years)</label>
          <input id="pet-age" name="pet_age" type="number" min="0" class="pm-input" placeholder="Age in years" required>
        </div>
        <div class="pm-formField">
          <label class="record-label" for="pet-gender">Gender</label>
          <input id="pet-gender" name="pet_gender" type="text" class="pm-input" placeholder="Male / Female" required>
        </div>
      </div>
      <div class="pm-formRow">
        <div class="pm-formField">
          <label class="record-label" for="pet-microchip">Microchip # (optional)</label>
          <input id="pet-microchip" name="microchip_number" type="text" class="pm-input" placeholder="Microchip number">
        </div>
        <div class="pm-formField">
          <label class="record-label" for="pet-mcd">MCD Registration (optional)</label>
          <input id="pet-mcd" name="mcd_registration_number" type="text" class="pm-input" placeholder="Registration number">
        </div>
      </div>
      <div class="pm-modalActions">
        <button type="button" class="pm-btn pm-ghost" data-role="close-pet-modal">Cancel</button>
        <button type="submit" class="pm-btn pm-primary">Save Pet</button>
      </div>
    </form>
  </div>
</div>

<div class="pm-overlay" id="record-modal">
  <div class="pm-modal record-modal" role="dialog" aria-modal="true">
    <div class="record-header">
      <div>
        <div class="record-title">Close Consultation</div>
        <div id="record-modal-patient" class="record-patient">Patient • —</div>
        <div id="record-modal-pet" class="record-patientSub"></div>
        <div class="record-status">Active Case</div>
      </div>
      <button type="button" class="pm-btn pm-ghost" data-role="close-record-modal">Close</button>
    </div>
    <form id="record-form" class="space-y-2" enctype="multipart/form-data">
      <input type="hidden" name="user_id" id="record-user-id">
      <input type="hidden" name="record_id" id="record-id">

      <div class="record-section">
        <div class="record-sectionTitle">Visit Overview</div>
        <div class="record-grid">
          <div class="record-field">
            <label class="record-label" for="visit-category">Visit Category</label>
            <select id="visit-category" name="visit_category" class="record-input">
              <option value="vaccination">Vaccination</option>
              <option value="routine">Routine Checkup</option>
              <option value="minor">Minor Issue</option>
              <option value="follow_up">Follow-up</option>
              <option value="illness">Illness / Treatment</option>
            </select>
          </div>
          <div class="record-field">
            <label class="record-label" for="case-severity">Case Severity</label>
            <select id="case-severity" name="case_severity" class="record-input" data-role="case-severity">
              <option value="general">General</option>
              <option value="critical">Critical / Treatment</option>
            </select>
          </div>
          <div class="record-field">
            <label class="record-label" for="doctor-select">Doctor</label>
            <select name="doctor_id" id="doctor-select" class="record-input">
              <option value="">Select doctor</option>
            </select>
          </div>
          <div class="record-field">
            <label class="record-label" for="record-pet">Pet</label>
            <select name="pet_id" id="record-pet" class="record-input">
              <option value="">Select pet</option>
            </select>
          </div>
        </div>
      </div>

      <div class="record-section">
        <div class="record-sectionTitle">Visit Notes</div>
        <textarea id="record-notes" name="notes" class="record-input record-textarea" placeholder="Reason for visit / brief notes"></textarea>
      </div>

      <div class="record-section record-critical" data-critical>
        <div class="record-sectionTitle">Clinical Observations</div>
        <div class="record-grid narrow">
          <div class="record-field">
            <label class="record-label" for="temperature">Temperature (°C)</label>
            <input id="temperature" name="temperature" type="text" class="record-input" placeholder="Temperature (°C)">
          </div>
          <div class="record-field">
            <label class="record-label" for="weight">Weight (kg)</label>
            <input id="weight" name="weight" type="text" class="record-input" placeholder="Weight (kg)">
          </div>
          <div class="record-field">
            <label class="record-label" for="heart-rate">Heart Rate (optional)</label>
            <input id="heart-rate" name="heart_rate" type="text" class="record-input" placeholder="Heart Rate (optional)">
          </div>
        </div>
        <div class="record-field" style="margin-top:8px">
          <label class="record-label" for="exam-notes">Physical examination notes</label>
          <textarea id="exam-notes" name="exam_notes" class="record-input record-textarea" placeholder="Physical examination notes"></textarea>
        </div>
      </div>

      <div class="record-section record-critical" data-critical>
        <div class="record-sectionTitle">Diagnosis</div>
        <div class="record-grid">
          <div class="record-field">
            <label class="record-label" for="diagnosis">Diagnosis</label>
            <input id="diagnosis" name="diagnosis" type="text" class="record-input" placeholder="Diagnosis (e.g. UTI)">
          </div>
          <div class="record-field">
            <label class="record-label" for="diagnosis-status">Status</label>
            <select id="diagnosis-status" name="diagnosis_status" class="record-input">
              <option value="new">New</option>
              <option value="ongoing">Ongoing</option>
              <option value="chronic">Chronic</option>
            </select>
          </div>
        </div>
      </div>

      <div class="record-section record-critical" data-critical>
        <div class="record-sectionTitle">Treatment &amp; Precautions</div>
        <div class="record-field">
          <label class="record-label" for="treatment-plan">Medication / treatment plan</label>
          <textarea id="treatment-plan" name="treatment_plan" class="record-input record-textarea" placeholder="Medication / treatment plan"></textarea>
        </div>
        <div class="record-field">
          <label class="record-label" for="home-care">Home care / precautions</label>
          <textarea id="home-care" name="home_care" class="record-input record-textarea" placeholder="Home care / precautions"></textarea>
        </div>
      </div>

      <div class="record-section">
        <div class="record-sectionTitle">Upload Documents</div>
        <label class="record-upload" for="record-file">+ Upload prescription / lab report</label>
        <input id="record-file" name="record_file" type="file" class="record-input" required>
        <div class="record-note">PDF, JPG, PNG, DOC up to 10 MB.</div>
      </div>

      <div class="record-section record-critical" data-critical>
        <div class="record-sectionTitle">Follow-up</div>
        <div class="record-grid">
          <div class="record-field">
            <label class="record-label" for="follow-up-date">Date</label>
            <input id="follow-up-date" name="follow_up_date" type="date" class="record-input">
          </div>
          <div class="record-field">
            <label class="record-label" for="follow-up-type">Visit Type</label>
            <select id="follow-up-type" name="follow_up_type" class="record-input">
              <option value="clinic">Clinic Visit</option>
              <option value="video">Video Consultation</option>
            </select>
          </div>
        </div>
        <div class="record-field" style="margin-top:8px">
          <label class="record-label" for="follow-up-notes">Follow-up notes</label>
          <textarea id="follow-up-notes" name="follow_up_notes" class="record-input record-textarea" placeholder="Follow-up notes"></textarea>
        </div>
      </div>

      <div class="record-actions">
        <button type="button" class="pm-btn pm-ghost" data-role="close-record-modal">Cancel</button>
        <button type="submit" class="pm-btn pm-primary">Close &amp; Share</button>
      </div>
    </form>
  </div>
</div>

<script>
(() => {
  const ORIGIN = window.location.origin;
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
  const API_BASE = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
  const CLINIC_ID = Number(@json($resolvedClinicId ?? null)) || null;
  const DEFAULT_DOCTOR_ID = Number(@json($sessionDoctorId ?? null)) || null;

  const state = {
    patients: [],
    search: '',
    sort: 'recent',
    tagFilters: [],
    selectedId: null,
    records: new Map(),
    loadingPatients: false,
    loadingRecords: false,
    editingRecordId: null,
  };

  const TAGS = [
    { id: 'hasRecords', label: 'Has records' },
    { id: 'noRecords', label: 'No records' },
    { id: 'recent', label: 'Updated recently' },
  ];

  const els = {
    search: document.getElementById('pm-search'),
    sort: document.getElementById('pm-sort'),
    tagFilters: document.getElementById('pm-tag-filters'),
    list: document.getElementById('pm-list'),
    listCount: document.getElementById('pm-list-count'),
    loading: document.getElementById('pm-loading'),
    profileAvatar: document.getElementById('pm-profile-avatar'),
    profileName: document.getElementById('pm-profile-name'),
    profileSub: document.getElementById('pm-profile-sub'),
    profileMeta: document.getElementById('pm-profile-meta'),
    statRecords: document.getElementById('pm-stat-records'),
    statLastRecord: document.getElementById('pm-stat-last-record'),
    statContact: document.getElementById('pm-stat-contact'),
    statEmail: document.getElementById('pm-stat-email'),
    recordCount: document.getElementById('pm-record-count'),
    recordEmpty: document.getElementById('pm-records-empty'),
    recordList: document.getElementById('pm-records-list'),
    petCount: document.getElementById('pm-pet-count'),
    petEmpty: document.getElementById('pm-pets-empty'),
    petList: document.getElementById('pm-pets-list'),
    refreshBtn: document.getElementById('pm-refresh'),
    refreshProfile: document.getElementById('pm-refresh-profile'),
    openUploadBtns: Array.from(document.querySelectorAll('[data-role="open-upload"]')),
    openPetBtns: Array.from(document.querySelectorAll('[data-role="open-pet"]')),
    modal: document.getElementById('record-modal'),
    modalPatient: document.getElementById('record-modal-patient'),
    modalPet: document.getElementById('record-modal-pet'),
    modalUserInput: document.getElementById('record-user-id'),
    recordForm: document.getElementById('record-form'),
    doctorSelect: document.getElementById('doctor-select'),
    recordPet: document.getElementById('record-pet'),
    caseSeverity: document.getElementById('case-severity'),
    criticalSections: Array.from(document.querySelectorAll('[data-critical]')),
    petModal: document.getElementById('pet-modal'),
    petForm: document.getElementById('pet-form'),
    petPatient: document.getElementById('pet-modal-patient'),
    petUserInput: document.getElementById('pet-user-id'),
  };

  let lastRecordError = null;

  function toggleCriticalSections(value) {
    (els.criticalSections || []).forEach((section) => {
      section.style.display = 'block';
      section.classList.add('is-visible');
    });
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    })[char]);
  }

  function formatDate(value, withTime = true) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    const opts = withTime ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' };
    return new Intl.DateTimeFormat(undefined, opts).format(date);
  }

  async function request(url, options = {}) {
    const headers = Object.assign({ 'Accept': 'application/json' }, options.headers || {});
    const res = await fetch(url, { ...options, headers });
    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : null; } catch (err) { data = null; }
    if (!res.ok) {
      const message = data?.error || data?.message || text || 'Request failed';
      throw new Error(message);
    }
    return data;
  }

  function getPrimaryPet(patient) {
    if (!patient) return null;
    if (Array.isArray(patient.pets) && patient.pets.length) {
      return patient.pets[0];
    }
    if (patient.pet_name || patient.breed || patient.pet_gender || patient.pet_age) {
      return {
        name: patient.pet_name,
        breed: patient.breed,
        gender: patient.pet_gender,
        pet_age: patient.pet_age,
      };
    }
    return null;
  }

  function getPatientPets(patient) {
    if (!patient) return [];
    if (Array.isArray(patient.pets) && patient.pets.length) {
      return patient.pets;
    }
    if (patient.pet_name || patient.breed || patient.pet_gender || patient.pet_age) {
      return [{
        id: 'legacy',
        name: patient.pet_name,
        breed: patient.breed,
        gender: patient.pet_gender,
        pet_age: patient.pet_age,
      }];
    }
    return [];
  }

  function getPetLabel(patient, petId) {
    if (!petId) return null;
    const pets = getPatientPets(patient);
    const match = pets.find((pet) => String(pet.id ?? pet.pet_id) === String(petId));
    if (match) {
      return match.name || match.pet_name || `Pet #${petId}`;
    }
    return `Pet #${petId}`;
  }

  function renderTagFilters() {
    if (!els.tagFilters) return;
    els.tagFilters.innerHTML = '';
    TAGS.forEach((tag) => {
      const el = document.createElement('div');
      el.className = 'pm-tag' + (state.tagFilters.includes(tag.id) ? ' is-active' : '');
      el.textContent = tag.label;
      el.onclick = () => {
        const idx = state.tagFilters.indexOf(tag.id);
        if (idx > -1) state.tagFilters.splice(idx, 1); else state.tagFilters.push(tag.id);
        renderPatientList();
      };
      els.tagFilters.appendChild(el);
    });
  }

  function applyFilters(list) {
    let filtered = list.slice();
    if (state.search) {
      const q = state.search.toLowerCase();
      filtered = filtered.filter((p) => {
        return (p.name || '').toLowerCase().includes(q)
          || (p.email || '').toLowerCase().includes(q)
          || (p.phone || '').toLowerCase().includes(q)
          || (p.pet_name || '').toLowerCase().includes(q)
          || (p.breed || '').toLowerCase().includes(q)
          || (Array.isArray(p.pets) && p.pets.some((pet) => {
            const petName = (pet.name || pet.pet_name || '').toLowerCase();
            const petBreed = (pet.breed || '').toLowerCase();
            return petName.includes(q) || petBreed.includes(q);
          }));
      });
    }

    if (state.tagFilters.includes('hasRecords')) {
      filtered = filtered.filter((p) => (p.records_count || 0) > 0);
    }
    if (state.tagFilters.includes('noRecords')) {
      filtered = filtered.filter((p) => (p.records_count || 0) === 0);
    }
    if (state.tagFilters.includes('recent')) {
      const cutoff = Date.now() - (30 * 24 * 60 * 60 * 1000);
      filtered = filtered.filter((p) => {
        const ts = new Date(p.updated_at || p.last_record_at || p.created_at || '').getTime();
        return !Number.isNaN(ts) && ts >= cutoff;
      });
    }

    if (state.sort === 'records') {
      filtered.sort((a, b) => (b.records_count || 0) - (a.records_count || 0));
    } else if (state.sort === 'name') {
      filtered.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
    } else {
      filtered.sort((a, b) => new Date(b.last_record_at || b.updated_at || 0) - new Date(a.last_record_at || a.updated_at || 0));
    }
    return filtered;
  }

  function renderPatientList() {
    if (!els.list) return;
    els.list.innerHTML = '';

    if (!CLINIC_ID) {
      els.list.innerHTML = '<div class="pm-empty">Clinic ID missing. Cannot load patients.</div>';
      els.listCount.textContent = '';
      return;
    }

    if (state.loadingPatients) {
      els.list.appendChild(createEmptyRow('Loading patients…'));
      els.listCount.textContent = '';
      return;
    }

    if (!state.patients.length) {
      els.list.appendChild(createEmptyRow('No patients found for this clinic yet.'));
      els.listCount.textContent = '0 patients';
      return;
    }

    const list = applyFilters(state.patients);
    if (!list.length) {
      els.list.appendChild(createEmptyRow('No patients match your filters.'));
      els.listCount.textContent = '0 patients';
      return;
    }

    list.forEach((patient) => {
      const row = document.createElement('div');
      row.className = 'pm-row' + (Number(patient.id) === Number(state.selectedId) ? ' is-active' : '');
      row.onclick = () => selectPatient(patient.id);

      const primaryPet = getPrimaryPet(patient) || {};

      const avatar = document.createElement('div');
      avatar.className = 'pm-avatar';
      avatar.textContent = String(primaryPet.name || patient.pet_name || patient.name || '?').charAt(0).toUpperCase();

      const info = document.createElement('div');
      info.className = 'pm-info';
      const name = document.createElement('div');
      name.className = 'pm-name';
      name.textContent = `${patient.name || 'Patient'}  #${patient.id}`;
      const meta = document.createElement('div');
      meta.className = 'pm-meta';
      const petName = primaryPet.name || patient.pet_name || 'Pet —';
      const petBreed = primaryPet.breed || patient.breed || 'Breed —';
      meta.innerHTML = `${escapeHtml(petName)} • ${escapeHtml(petBreed)}<br>${escapeHtml(patient.phone || 'Phone —')} • ${escapeHtml(patient.email || 'Email —')}`;
      const badges = document.createElement('div');
      badges.className = 'pm-badges';
      const recBadge = document.createElement('span');
      recBadge.className = 'pm-badge';
      recBadge.textContent = `${patient.records_count || 0} file${(patient.records_count || 0) === 1 ? '' : 's'}`;
      const lastBadge = document.createElement('span');
      lastBadge.className = 'pm-badge';
      lastBadge.textContent = patient.last_record_at ? `Last: ${formatDate(patient.last_record_at, false)}` : 'No uploads';
      badges.appendChild(recBadge);
      badges.appendChild(lastBadge);

      info.appendChild(name);
      info.appendChild(meta);
      info.appendChild(badges);

      row.appendChild(avatar);
      row.appendChild(info);
      els.list.appendChild(row);
    });

    els.listCount.textContent = `${list.length} patient${list.length === 1 ? '' : 's'}`;
  }

  function createEmptyRow(text) {
    const div = document.createElement('div');
    div.className = 'pm-empty';
    div.textContent = text;
    return div;
  }

  function renderProfile() {
    if (!els.profileName) return;
    const patient = state.patients.find((p) => Number(p.id) === Number(state.selectedId));
    if (!patient) {
      els.profileAvatar.textContent = '🐾';
      els.profileName.textContent = 'Select a patient';
      els.profileSub.textContent = 'Patient and pet details will appear here.';
      els.profileMeta.textContent = '';
      els.statRecords.textContent = '—';
      els.statLastRecord.textContent = 'Last upload —';
      els.statContact.textContent = '—';
      els.statEmail.textContent = '—';
      els.recordCount.textContent = '—';
      renderPetSelect(null);
      renderRecords();
      renderPets();
      return;
    }

    const primaryPet = getPrimaryPet(patient) || {};

    els.profileAvatar.textContent = String(primaryPet.name || patient.pet_name || patient.name || '?').charAt(0).toUpperCase();
    els.profileName.textContent = patient.name || 'Patient';
    const petName = primaryPet.name || patient.pet_name || 'Pet —';
    const petBreed = primaryPet.breed || patient.breed || 'Breed —';
    const petGender = primaryPet.gender || patient.pet_gender || 'Gender —';
    const petAge = primaryPet.pet_age ?? primaryPet.age ?? patient.pet_age;
    const petAgeLabel = (petAge || petAge === 0) ? petAge : '—';
    els.profileSub.textContent = `${petName} • ${petBreed} • ${petGender} • Age: ${petAgeLabel}`;
    els.profileMeta.textContent = `Phone: ${patient.phone || '—'} • Email: ${patient.email || '—'}`;
    const cachedRecords = state.records.get(Number(patient.id));
    const recordTotal = Array.isArray(cachedRecords) ? cachedRecords.length : (patient.records_count || 0);
    els.statRecords.textContent = `${recordTotal}`;
    const latestRecord = Array.isArray(cachedRecords) && cachedRecords.length ? cachedRecords[0]?.uploaded_at : patient.last_record_at;
    els.statLastRecord.textContent = latestRecord ? `Last upload ${formatDate(latestRecord)}` : 'No uploads yet';
    els.statContact.textContent = patient.phone || '—';
    els.statEmail.textContent = patient.email || '—';
    renderPetSelect(patient, primaryPet?.id);
    renderPets(patient);
    renderRecords();
  }

  function renderPets(patient = null) {
    if (!els.petList || !els.petEmpty) return;
    els.petList.innerHTML = '';

    if (!patient) {
      els.petEmpty.textContent = 'Select a patient to see pets.';
      els.petEmpty.style.display = 'block';
      if (els.petCount) els.petCount.textContent = '—';
      return;
    }

    const pets = Array.isArray(patient.pets) ? patient.pets : [];
    const hasLegacyPet = patient.pet_name || patient.breed || patient.pet_gender || patient.pet_age;
    const combinedPets = pets.length ? pets : (hasLegacyPet ? [{
      id: 'legacy',
      name: patient.pet_name,
      breed: patient.breed,
      gender: patient.pet_gender,
      pet_age: patient.pet_age,
    }] : []);

    if (!combinedPets.length) {
      els.petEmpty.textContent = 'No pets yet for this patient.';
      els.petEmpty.style.display = 'block';
      if (els.petCount) els.petCount.textContent = '0 pets';
      return;
    }

    els.petEmpty.style.display = 'none';
    combinedPets.forEach((pet) => {
      const wrap = document.createElement('div');
      wrap.className = 'pm-petRow';
      const avatar = document.createElement('div');
      avatar.className = 'pm-petAvatar';
      avatar.textContent = String(pet.name || pet.pet_name || 'P').charAt(0).toUpperCase();
      const body = document.createElement('div');
      body.className = 'pm-petBody';
      const title = document.createElement('div');
      title.className = 'pm-petName';
      title.textContent = pet.name || pet.pet_name || 'Pet';
      const meta = document.createElement('div');
      meta.className = 'pm-petMeta';
      const metaParts = [];
      if (pet.type) metaParts.push(pet.type);
      if (pet.breed) metaParts.push(pet.breed);
      const gender = pet.gender || pet.pet_gender;
      if (gender) metaParts.push(`Gender: ${gender}`);
      const age = pet.pet_age ?? pet.age;
      if (age || age === 0) metaParts.push(`Age: ${age}`);
      meta.textContent = metaParts.join(' • ') || 'Details not provided';
      body.appendChild(title);
      body.appendChild(meta);
      wrap.appendChild(avatar);
      wrap.appendChild(body);
      els.petList.appendChild(wrap);
    });
    if (els.petCount) {
      els.petCount.textContent = `${combinedPets.length} pet${combinedPets.length === 1 ? '' : 's'}`;
    }
  }

  function renderRecords() {
    if (!els.recordList || !els.recordEmpty) return;
    els.recordList.innerHTML = '';
    const activePatient = state.patients.find((p) => Number(p.id) === Number(state.selectedId));

    if (!state.selectedId) {
      els.recordEmpty.textContent = 'Select a patient to see uploaded files.';
      els.recordEmpty.style.display = 'block';
      els.recordEmpty.style.color = 'var(--pm-muted)';
      els.recordCount.textContent = '—';
      if (els.statRecords) {
        els.statRecords.textContent = '—';
      }
      return;
    }

    if (state.loadingRecords) {
      els.recordEmpty.textContent = 'Loading records…';
      els.recordEmpty.style.display = 'block';
      els.recordEmpty.style.color = 'var(--pm-muted)';
      els.recordCount.textContent = '—';
      if (els.statRecords) {
        els.statRecords.textContent = '—';
      }
      return;
    }

    if (lastRecordError) {
      els.recordEmpty.textContent = lastRecordError;
      els.recordEmpty.style.display = 'block';
      els.recordEmpty.style.color = '#b91c1c';
      els.recordCount.textContent = '—';
      if (els.statRecords) {
        els.statRecords.textContent = '—';
      }
      return;
    }

    const records = state.records.get(Number(state.selectedId)) || [];
    if (!records.length) {
      els.recordEmpty.textContent = 'No medical records uploaded yet.';
      els.recordEmpty.style.display = 'block';
      els.recordEmpty.style.color = 'var(--pm-muted)';
      els.recordCount.textContent = '0 files';
      if (els.statRecords) {
        els.statRecords.textContent = '0';
      }
      return;
    }

    els.recordEmpty.style.display = 'none';
    records.forEach((rec) => {
      const wrap = document.createElement('div');
      wrap.className = 'pm-record pm-record-card';
      const prescription = rec.prescription || {};
      const petId = rec.pet_id ?? prescription.pet_id ?? null;
      const petLabel = activePatient ? getPetLabel(activePatient, petId) : null;
      const detailPairs = [];
      if (prescription.visit_category || prescription.case_severity) {
        detailPairs.push({ label: 'Visit', value: `${escapeHtml(prescription.visit_category || '—')} • ${escapeHtml(prescription.case_severity || '—')}` });
      }
      if (prescription.visit_notes) {
        detailPairs.push({ label: 'Visit notes', value: escapeHtml(prescription.visit_notes) });
      }
      if (prescription.temperature || prescription.weight || prescription.heart_rate) {
        const temp = prescription.temperature ? `Temp: ${escapeHtml(prescription.temperature)}°${prescription.temperature_unit ? escapeHtml(prescription.temperature_unit) : ''}` : null;
        const wt = prescription.weight ? `Weight: ${escapeHtml(prescription.weight)}kg` : null;
        const hr = prescription.heart_rate ? `Heart: ${escapeHtml(prescription.heart_rate)}` : null;
        detailPairs.push({ label: 'Vitals', value: [temp, wt, hr].filter(Boolean).join(' • ') });
      }
      if (prescription.exam_notes) {
        detailPairs.push({ label: 'Exam', value: escapeHtml(prescription.exam_notes) });
      }
      if (prescription.diagnosis || prescription.diagnosis_status) {
        detailPairs.push({ label: 'Diagnosis', value: `${escapeHtml(prescription.diagnosis || '—')} (${escapeHtml(prescription.diagnosis_status || '—')})` });
      }
      if (prescription.treatment_plan) {
        detailPairs.push({ label: 'Treatment', value: escapeHtml(prescription.treatment_plan) });
      }
      if (prescription.home_care) {
        detailPairs.push({ label: 'Home care', value: escapeHtml(prescription.home_care) });
      }
      if (prescription.follow_up_date || prescription.follow_up_type || prescription.follow_up_notes) {
        const fuParts = [];
        if (prescription.follow_up_date) fuParts.push(`Date: ${escapeHtml(prescription.follow_up_date)}`);
        if (prescription.follow_up_type) fuParts.push(`Type: ${escapeHtml(prescription.follow_up_type)}`);
        if (prescription.follow_up_notes) fuParts.push(`Notes: ${escapeHtml(prescription.follow_up_notes)}`);
        detailPairs.push({ label: 'Follow-up', value: fuParts.join(' • ') });
      }
      if (petLabel) {
        detailPairs.unshift({ label: 'Pet', value: escapeHtml(petLabel) });
      }
      const detailHtml = detailPairs.length
        ? `<div class="pm-record-details">${detailPairs.map(pair => `<div class="pm-record-row"><div class="pm-record-label">${pair.label}</div><div class="pm-record-value">${pair.value}</div></div>`).join('')}</div>`
        : '';
      const tags = [];
      if (prescription.case_severity) tags.push(`<span class="pm-tag-soft">${escapeHtml(prescription.case_severity)}</span>`);
      if (prescription.visit_category) tags.push(`<span class="pm-tag-soft">${escapeHtml(prescription.visit_category)}</span>`);

      wrap.innerHTML = `
        <div class="pm-record-head">
          <div>
            <div class="pm-record-title">${escapeHtml(rec.file_name || 'Medical file')}</div>
            <div class="pm-record-meta">${formatDate(rec.uploaded_at)}${rec.doctor_id ? ` • Doctor #${rec.doctor_id}` : ''}${petLabel ? ` • Pet: ${escapeHtml(petLabel)}` : ''}</div>
          </div>
          ${tags.length ? `<div class="pm-record-tags">${tags.join('')}</div>` : ''}
        </div>
        <div class="pm-record-notes">${escapeHtml(rec.notes || 'No notes')}</div>
        ${detailHtml}
        <div class="pm-record-actions">
          <button type="button" class="pm-btn pm-primary" data-role="edit-record" data-id="${rec.id}" style="padding:6px 12px">Edit</button>
          <a href="${rec.url}" target="_blank" rel="noopener" class="pm-btn pm-ghost" style="padding:6px 10px">Download</a>
        </div>
      `;
      const editBtn = wrap.querySelector('[data-role="edit-record"]');
      if (editBtn) {
        editBtn.addEventListener('click', () => {
          selectPatient(rec.user_id);
          openUploadModal();
          fillRecordFormFromRecord(rec);
        });
      }
      els.recordList.appendChild(wrap);
    });
    els.recordCount.textContent = `${records.length} file${records.length === 1 ? '' : 's'}`;
    if (els.statRecords) {
      els.statRecords.textContent = `${records.length}`;
    }
  }

  function renderPetSelect(patient, selectedPetId = null) {
    if (!els.recordPet) return;
    const pets = getPatientPets(patient);
    els.recordPet.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = pets.length ? 'Select pet' : 'No pets found';
    els.recordPet.appendChild(placeholder);
    pets.forEach((pet) => {
      const petValue = pet.id ?? pet.pet_id;
      const numericValue = Number(petValue);
      const hasId = petValue !== null && petValue !== undefined && petValue !== '' && Number.isFinite(numericValue);
      const opt = document.createElement('option');
      opt.value = hasId ? petValue : '';
      opt.textContent = `${pet.name || pet.pet_name || 'Pet'}${pet.breed ? ` • ${pet.breed}` : ''}`;
      if (pet.gender) opt.textContent += ` • ${pet.gender}`;
      opt.dataset.petName = pet.name || pet.pet_name || '';
      if (!hasId) {
        opt.disabled = true;
        opt.textContent += ' (link pet to use)';
      }
      els.recordPet.appendChild(opt);
    });
    if (selectedPetId) {
      els.recordPet.value = String(selectedPetId);
    } else if (pets.length === 1) {
      const onlyValue = pets[0].id ?? pets[0].pet_id;
      const onlyNumeric = Number(onlyValue);
      if (onlyValue !== null && onlyValue !== undefined && onlyValue !== '' && Number.isFinite(onlyNumeric)) {
        els.recordPet.value = String(onlyValue);
      }
    } else {
      els.recordPet.value = '';
    }
  }

  async function loadPatients() {
    if (!CLINIC_ID) {
      renderPatientList();
      return;
    }
    state.loadingPatients = true;
    renderPatientList();
    try {
      const data = await request(`${API_BASE}/clinics/${CLINIC_ID}/patients`);
      state.patients = Array.isArray(data?.patients) ? data.patients : [];
      const exists = state.selectedId && state.patients.some((p) => Number(p.id) === Number(state.selectedId));
      if (!exists) {
        state.selectedId = null;
      }
    } catch (error) {
      state.patients = [];
      els.list.innerHTML = '';
      els.list.appendChild(createEmptyRow(escapeHtml(error.message)));
      els.listCount.textContent = '0 patients';
    } finally {
      state.loadingPatients = false;
      renderTagFilters();
      renderPatientList();
      renderProfile();
    }
  }

  async function loadDoctors() {
    if (!CLINIC_ID || !els.doctorSelect) return;
    try {
      const data = await request(`${API_BASE}/clinics/${CLINIC_ID}/doctors`);
      const doctors = Array.isArray(data?.doctors) ? data.doctors : [];
      els.doctorSelect.innerHTML = '<option value="">Select doctor</option>';
      doctors.forEach((doc) => {
        const option = document.createElement('option');
        option.value = doc.id;
        option.textContent = doc.name || doc.doctor_name || `Doctor #${doc.id}`;
        els.doctorSelect.appendChild(option);
      });
      if (DEFAULT_DOCTOR_ID) {
        els.doctorSelect.value = DEFAULT_DOCTOR_ID;
      }
    } catch (error) {
      console.error('Failed to load doctors', error);
    }
  }

  async function loadRecords(patientId) {
    if (!CLINIC_ID || !patientId) {
      renderRecords();
      return;
    }
    state.loadingRecords = true;
    lastRecordError = null;
    renderRecords();
    try {
      const data = await request(`${API_BASE}/users/${patientId}/medical-records?clinic_id=${CLINIC_ID}`);
      const records = Array.isArray(data?.data?.records) ? data.data.records : [];
      state.records.set(Number(patientId), records);
    } catch (error) {
      lastRecordError = escapeHtml(error.message);
      state.records.set(Number(patientId), []);
    } finally {
      state.loadingRecords = false;
      renderRecords();
    }
  }

  function selectPatient(patientId) {
    state.selectedId = Number(patientId);
    state.loadingRecords = true;
    lastRecordError = null;
    renderPatientList();
    renderProfile();
    loadRecords(patientId);
  }

  function resetRecordForm() {
    state.editingRecordId = null;
    els.recordForm?.reset();
    if (els.caseSeverity) {
      els.caseSeverity.value = 'general';
    }
    toggleCriticalSections(els.caseSeverity?.value || 'general');
    const recordIdInput = document.getElementById('record-id');
    if (recordIdInput) recordIdInput.value = '';
    const recordFile = document.getElementById('record-file');
    if (recordFile) recordFile.required = true;
  }

  function resetPetForm() {
    if (els.petForm) {
      els.petForm.reset();
    }
    if (els.petUserInput) {
      els.petUserInput.value = state.selectedId || '';
    }
  }

  function fillRecordFormFromRecord(rec) {
    if (!rec) return;
    const prescription = rec.prescription || {};
    state.editingRecordId = rec.id;
    const recordIdInput = document.getElementById('record-id');
    if (recordIdInput) recordIdInput.value = rec.id;
    const recordUserInput = document.getElementById('record-user-id');
    if (recordUserInput) recordUserInput.value = rec.user_id;
    const recordFile = document.getElementById('record-file');
    if (recordFile) recordFile.required = false;
    const mapValue = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.value = value ?? '';
    };
    mapValue('record-notes', rec.notes ?? prescription.visit_notes ?? '');
    mapValue('visit-category', prescription.visit_category ?? '');
    mapValue('case-severity', prescription.case_severity ?? '');
    mapValue('doctor-select', rec.doctor_id ?? prescription.doctor_id ?? DEFAULT_DOCTOR_ID ?? '');
    mapValue('temperature', prescription.temperature ?? '');
    mapValue('weight', prescription.weight ?? '');
    mapValue('heart-rate', prescription.heart_rate ?? '');
    mapValue('exam-notes', prescription.exam_notes ?? '');
    mapValue('diagnosis', prescription.diagnosis ?? '');
    mapValue('diagnosis-status', prescription.diagnosis_status ?? '');
    mapValue('treatment-plan', prescription.treatment_plan ?? '');
    mapValue('home-care', prescription.home_care ?? '');
    mapValue('follow-up-date', prescription.follow_up_date ?? '');
    mapValue('follow-up-type', prescription.follow_up_type ?? '');
    mapValue('follow-up-notes', prescription.follow_up_notes ?? '');
    mapValue('record-pet', prescription.pet_id ?? rec.pet_id ?? '');
    toggleCriticalSections(els.caseSeverity?.value || 'general');
  }

  function openUploadModal() {
    if (!state.selectedId) {
      Swal.fire({ icon: 'info', title: 'Select a patient', text: 'Pick a patient from the list before uploading.' });
      return;
    }
    const patient = state.patients.find((p) => Number(p.id) === Number(state.selectedId));
    if (patient) {
      els.modalPatient.textContent = `${patient.name || 'Patient'} • #${patient.id}`;
      const petLine = [patient.pet_name, patient.breed, patient.pet_age ? `Age: ${patient.pet_age}` : null]
        .filter(Boolean)
        .join(' • ');
      if (els.modalPet) {
        els.modalPet.textContent = petLine || '';
      }
      els.modalUserInput.value = patient.id;
    }
    resetRecordForm();
    renderPetSelect(patient);
    if (els.doctorSelect && DEFAULT_DOCTOR_ID) {
      els.doctorSelect.value = DEFAULT_DOCTOR_ID;
    }
    els.modal.classList.add('is-visible');
  }

  function closeModal() {
    els.modal.classList.remove('is-visible');
    resetRecordForm();
    if (els.doctorSelect) {
      els.doctorSelect.value = DEFAULT_DOCTOR_ID || '';
    }
    if (els.modalPet) {
      els.modalPet.textContent = '';
    }
  }

  function openPetModal() {
    if (!state.selectedId) {
      Swal.fire({ icon: 'info', title: 'Select a patient', text: 'Pick a patient before adding a pet.' });
      return;
    }
    resetPetForm();
    const patient = state.patients.find((p) => Number(p.id) === Number(state.selectedId));
    if (patient) {
      if (els.petPatient) {
        els.petPatient.textContent = `${patient.name || 'Patient'} • #${patient.id}`;
      }
      if (els.petUserInput) {
        els.petUserInput.value = patient.id;
      }
    }
    if (els.petModal) {
      els.petModal.classList.add('is-visible');
    }
  }

  function closePetModal() {
    if (els.petModal) {
      els.petModal.classList.remove('is-visible');
    }
    if (els.petPatient) {
      els.petPatient.textContent = 'Patient • —';
    }
    resetPetForm();
  }

  function wireEvents() {
    els.search?.addEventListener('input', (e) => { state.search = e.target.value || ''; renderPatientList(); });
    els.sort?.addEventListener('change', (e) => { state.sort = e.target.value; renderPatientList(); });
    els.refreshBtn?.addEventListener('click', () => loadPatients());
    els.refreshProfile?.addEventListener('click', () => {
      if (state.selectedId) {
        loadRecords(state.selectedId);
      }
      loadPatients();
    });
    els.openUploadBtns.forEach((btn) => btn.addEventListener('click', openUploadModal));
    els.openPetBtns.forEach((btn) => btn.addEventListener('click', openPetModal));
    document.querySelectorAll('[data-role="close-record-modal"]').forEach((btn) => btn.addEventListener('click', closeModal));
    document.querySelectorAll('[data-role="close-pet-modal"]').forEach((btn) => btn.addEventListener('click', closePetModal));
    els.caseSeverity?.addEventListener('change', (event) => {
      toggleCriticalSections(event.target.value || 'general');
    });

    els.recordForm?.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!CLINIC_ID) {
        Swal.fire({ icon: 'error', title: 'Clinic missing', text: 'Clinic ID not detected. Reload dashboard.' });
        return;
      }
      const patientId = els.modalUserInput.value;
      if (!patientId) {
        Swal.fire({ icon: 'error', title: 'Patient missing', text: 'Select a patient before uploading.' });
        return;
      }
      const formData = new FormData(els.recordForm);
      formData.append('clinic_id', CLINIC_ID);
      if (!formData.get('doctor_id')) {
        formData.delete('doctor_id');
      }
      if (!formData.get('pet_id')) {
        formData.delete('pet_id');
      }
      let url = `${API_BASE}/medical-records`;
      if (state.editingRecordId || formData.get('record_id')) {
        const recId = state.editingRecordId || formData.get('record_id');
        formData.append('_method', 'PUT');
        url = `${API_BASE}/medical-records/${recId}`;
        const file = formData.get('record_file');
        if (!(file instanceof File) || !file?.size) {
          formData.delete('record_file');
        }
      }
      try {
        await request(url, { method: 'POST', body: formData });
        Swal.fire({ icon: 'success', title: state.editingRecordId ? 'Updated' : 'Uploaded', timer: 1500, showConfirmButton: false });
        closeModal();
        await loadPatients();
        if (state.selectedId === Number(patientId)) {
          await loadRecords(patientId);
        }
      } catch (error) {
        Swal.fire({ icon: 'error', title: 'Upload failed', text: error.message || 'Could not upload file' });
      }
    });

    els.petForm?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const patientId = state.selectedId || els.petUserInput?.value;
      if (!patientId) {
        Swal.fire({ icon: 'info', title: 'Select a patient', text: 'Choose a patient before adding a pet.' });
        return;
      }
      const formData = new FormData(els.petForm);
      const name = (formData.get('name') || '').trim();
      const breed = (formData.get('breed') || '').trim();
      const gender = (formData.get('pet_gender') || '').trim();
      const ageRaw = formData.get('pet_age');
      const age = Number(ageRaw);
      if (!name || !breed || !gender || ageRaw === null || ageRaw === undefined || ageRaw === '') {
        Swal.fire({ icon: 'warning', title: 'Missing details', text: 'Name, breed, age and gender are required.' });
        return;
      }
      if (Number.isNaN(age) || age < 0) {
        Swal.fire({ icon: 'warning', title: 'Check age', text: 'Please enter a valid age (0 or higher).' });
        return;
      }
      formData.set('name', name);
      formData.set('breed', breed);
      formData.set('pet_gender', gender);
      formData.set('pet_age', String(age));
      formData.set('user_id', patientId);
      ['microchip_number', 'mcd_registration_number'].forEach((key) => {
        const val = (formData.get(key) || '').toString().trim();
        if (val) {
          formData.set(key, val);
        } else {
          formData.delete(key);
        }
      });
      try {
        await request(`${API_BASE}/users/${patientId}/pets`, { method: 'POST', body: formData });
        Swal.fire({ icon: 'success', title: 'Pet added', timer: 1500, showConfirmButton: false });
        closePetModal();
        await loadPatients();
        selectPatient(patientId);
      } catch (error) {
        Swal.fire({ icon: 'error', title: 'Could not add pet', text: error.message || 'Request failed' });
      }
    });
  }

  renderTagFilters();
  renderPatientList();
  renderProfile();
  wireEvents();
  toggleCriticalSections(els.caseSeverity?.value || 'general');
  loadPatients();
  loadDoctors();
})();
</script>

<script>
(() => {
  const CLINIC_ID = Number(@json($resolvedClinicId ?? null)) || null;
  const CURRENT_USER_ID = Number(@json(auth()->id() ?? session('user_id') ?? data_get(session('user'),'id') ?? null)) || null;
  const CONFIG = {
    API_BASE: @json(url('/api')),
    CSRF_URL: @json(url('/sanctum/csrf-cookie')),
  };

  const STORED_AUTH_FULL = (() => {
    try {
      const raw = sessionStorage.getItem('auth_full') || localStorage.getItem('auth_full');
      return raw ? JSON.parse(raw) : null;
    } catch (_) {
      return null;
    }
  })();

  function getCookie(name) {
    return document.cookie.split('; ').find(row => row.startsWith(name + '='))?.split('=')[1] ?? '';
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
      } catch (_) {}
      this.mode = 'none';
      return { mode: 'none' };
    },
    headers(base = {}) {
      const headers = { Accept: 'application/json', ...base };
      if (CLINIC_ID) {
        headers['X-Clinic-Id'] = String(CLINIC_ID);
        headers['X-User-Id'] = String(CLINIC_ID);
      } else if (CURRENT_USER_ID) {
        headers['X-User-Id'] = String(CURRENT_USER_ID);
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

  function targetQuery(extra = {}) {
    const params = new URLSearchParams();
    if (CLINIC_ID) {
      params.set('clinic_id', String(CLINIC_ID));
      params.set('user_id', String(CLINIC_ID));
    } else if (CURRENT_USER_ID) {
      params.set('user_id', String(CURRENT_USER_ID));
    }
    Object.entries(extra).forEach(([key, value]) => {
      if (value === undefined || value === null || value === '') return;
      params.set(key, String(value));
    });
    const qs = params.toString();
    return qs ? `?${qs}` : '';
  }

  function appendTarget(formData) {
    if (CLINIC_ID) {
      if (!formData.has('clinic_id')) formData.append('clinic_id', String(CLINIC_ID));
      if (!formData.has('user_id')) formData.append('user_id', String(CLINIC_ID));
    } else if (CURRENT_USER_ID) {
      if (!formData.has('user_id')) formData.append('user_id', String(CURRENT_USER_ID));
    }
  }

  async function apiFetch(url, opts = {}) {
    const res = await fetch(url, { credentials: 'include', ...opts });
    const contentType = res.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await res.json() : await res.text();
    if (!res.ok) {
      const message = typeof data === 'string' ? data : data?.message || 'Request failed';
      throw new Error(message);
    }
    return data;
  }

  const API = {
    patients: (query = '') => `${CONFIG.API_BASE}/receptionist/patients${targetQuery(query ? { q: query } : {})}`,
    patientPets: (userId) => `${CONFIG.API_BASE}/receptionist/patients/${userId}/pets${targetQuery()}`,
    doctors: () => `${CONFIG.API_BASE}/receptionist/doctors${targetQuery()}`,
    doctorSlotsSummary: (doctorId, extra = {}) => `${CONFIG.API_BASE}/doctors/${doctorId}/slots/summary${targetQuery(extra)}`,
    createPatient: `${CONFIG.API_BASE}/receptionist/patients`,
    createAppointment: `${CONFIG.API_BASE}/appointments/submit`,
  };

  const bookingModal = document.getElementById('booking-modal');
  const bookingForm = document.getElementById('booking-form');
  const patientSelect = document.getElementById('patient-select');
  const patientSearchInput = document.getElementById('patient-search');
  const petSelect = document.getElementById('pet-select');
  const doctorSelect = document.getElementById('booking-doctor-select');
  const slotSelect = document.getElementById('slot-select');
  const slotHint = document.getElementById('slot-hint');
  const modeButtons = Array.from(document.querySelectorAll('[data-patient-mode]'));
  const existingSection = document.getElementById('existing-patient-section');
  const newSection = document.getElementById('new-patient-section');
  const bookingOpen = document.getElementById('pm-booking-open');

  const STORED_FULL = STORED_AUTH_FULL || {};

  let PATIENTS = [];
  let CURRENT_PATIENT = null;
  let PATIENT_MODE = 'existing';
  let PREFERRED_PATIENT_ID = null;

  const closeButtons = bookingModal ? Array.from(bookingModal.querySelectorAll('[data-close]')) : [];

  function openBooking() {
    if (!bookingModal) return;
    if (!CLINIC_ID && !CURRENT_USER_ID) {
      Swal.fire({ icon: 'warning', title: 'Clinic missing', text: 'Open this page from the clinic dashboard.' });
      return;
    }
    bookingModal.classList.add('active');
    bookingModal.removeAttribute('hidden');
    bookingModal.setAttribute('aria-hidden', 'false');
    if (!PATIENTS.length) fetchPatients();
    if (!doctorSelect?.value) fetchDoctors();
  }

  function closeBooking() {
    if (!bookingModal) return;
    bookingModal.classList.remove('active');
    bookingModal.setAttribute('hidden', 'hidden');
    bookingModal.setAttribute('aria-hidden', 'true');
    bookingForm?.reset();
    petSelect.innerHTML = '';
    slotSelect.innerHTML = '<option value="">Select a time slot</option>';
    slotHint.textContent = 'Select a doctor and date first to load available slots.';
    setPatientMode('existing');
  }

  function setPatientMode(mode) {
    PATIENT_MODE = mode;
    modeButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.patientMode === mode));
    existingSection?.classList.toggle('hidden', mode !== 'existing');
    newSection?.classList.toggle('hidden', mode !== 'new');
  }

  modeButtons.forEach((btn) => btn.addEventListener('click', () => setPatientMode(btn.dataset.patientMode)));
  closeButtons.forEach((btn) => btn.addEventListener('click', closeBooking));
  bookingOpen?.addEventListener('click', openBooking);

  function normalizePhone(...candidates) {
    for (const value of candidates) {
      if (typeof value !== 'string') continue;
      const cleaned = value.replace(/\s+/g, '').trim();
      if (cleaned) return cleaned.slice(0, 20);
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
      console.error('Failed to load patients', error);
    }
  }

  function populatePatientSelect() {
    if (!patientSelect) return;
    patientSelect.innerHTML = '';
    PATIENTS.forEach((patient) => {
      const option = document.createElement('option');
      option.value = patient.id;
      option.textContent = `${patient.name || 'Patient'} • ${patient.phone || patient.email || ''}`;
      patientSelect.appendChild(option);
    });
    const targetId = PREFERRED_PATIENT_ID || (PATIENTS[0]?.id ?? null);
    if (targetId) {
      patientSelect.value = targetId;
      CURRENT_PATIENT = PATIENTS.find((p) => String(p.id) === String(targetId)) || null;
      PREFERRED_PATIENT_ID = null;
      handlePatientChange();
    }
  }

  async function fetchPatientPets(userId) {
    if (!userId || !petSelect) {
      petSelect.innerHTML = '';
      return;
    }
    try {
      await Auth.bootstrap();
      const res = await apiFetch(API.patientPets(userId), { headers: Auth.headers() });
      renderPetOptions(res?.data || []);
    } catch (error) {
      console.error('Failed to load pets', error);
      petSelect.innerHTML = '';
    }
  }

  function renderPetOptions(pets) {
    petSelect.innerHTML = '';
    if (!pets.length) {
      petSelect.innerHTML = '<option value="">No pets found</option>';
      return;
    }
    pets.forEach((pet) => {
      const option = document.createElement('option');
      option.value = pet.id;
      option.textContent = `${pet.pet_name || pet.name || 'Pet'} • ${pet.pet_type || pet.species || ''}`;
      option.dataset.petName = pet.pet_name || pet.name || '';
      petSelect.appendChild(option);
    });
  }

  async function fetchDoctors() {
    if (!doctorSelect) return;
    try {
      await Auth.bootstrap();
      const res = await apiFetch(API.doctors(), { headers: Auth.headers() });
      const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
      renderDoctorOptions(list);
    } catch (error) {
      console.error('Failed to load doctors', error);
    }
  }

  function renderDoctorOptions(list) {
    if (!doctorSelect) return;
    doctorSelect.innerHTML = '<option value="">Any available doctor</option>';
    list.forEach((doc) => {
      const option = document.createElement('option');
      option.value = doc.id;
      option.textContent = doc.doctor_name || doc.name || `Doctor ${doc.id}`;
      doctorSelect.appendChild(option);
    });
  }

  async function fetchDoctorSlots(doctorId) {
    if (!slotSelect) return;
    if (!doctorId) {
      slotSelect.innerHTML = '<option value="">Select a time slot</option>';
      slotHint.textContent = 'Select a doctor and date first to load available slots.';
      return;
    }
    const date = bookingForm.elements['scheduled_date'].value;
    if (!date) {
      slotSelect.innerHTML = '<option value="">Select a time slot</option>';
      slotHint.textContent = 'Select a doctor and date first to load available slots.';
      return;
    }
    try {
      await Auth.bootstrap();
      const res = await apiFetch(API.doctorSlotsSummary(doctorId, { date, service_type: bookingForm.elements['service_type'].value || 'in_clinic' }), { headers: Auth.headers() });
      const slots = res?.free_slots || [];
      slotSelect.innerHTML = '<option value="">Select a time slot</option>';
      slots.forEach((slot) => {
        const opt = document.createElement('option');
        const time = typeof slot === 'string' ? slot : (slot.time || slot.time_slot || slot.slot || '');
        const status = typeof slot === 'string' ? 'free' : (slot.status || 'free');
        opt.value = time;
        opt.textContent = `${time} (${status})`;
        slotSelect.appendChild(opt);
      });
      slotHint.textContent = slots.length ? `${slots.length} slots available` : 'No slots available for this date';
    } catch (error) {
      console.error('Failed to load slots', error);
      slotSelect.innerHTML = '<option value="">Select a time slot</option>';
      slotHint.textContent = 'Failed to load slots';
    }
  }

  function handleDoctorChange() {
    const doctorId = doctorSelect?.value;
    fetchDoctorSlots(doctorId);
  }

  function handlePatientChange() {
    const patientId = patientSelect?.value;
    CURRENT_PATIENT = PATIENTS.find((p) => String(p.id) === String(patientId)) || null;
    fetchPatientPets(patientId);
  }

  patientSelect?.addEventListener('change', handlePatientChange);
  doctorSelect?.addEventListener('change', handleDoctorChange);
  bookingForm?.elements['scheduled_date']?.addEventListener('change', () => handleDoctorChange());

  let searchTimer;
  patientSearchInput?.addEventListener('input', (event) => {
    const query = event.target.value.trim();
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => fetchPatients(query), 350);
  });

  bookingForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!CLINIC_ID && !CURRENT_USER_ID) {
      Swal.fire({ icon: 'warning', title: 'Clinic missing', text: 'Reload from clinic dashboard.' });
      return;
    }
    const doctorId = bookingForm.elements['doctor_id'].value;
    const date = bookingForm.elements['scheduled_date'].value;
    const timeSlot = bookingForm.elements['scheduled_time'].value;
    if (!doctorId || !date || !timeSlot) {
      Swal.fire({ icon: 'warning', title: 'Required fields missing', text: 'Doctor, date and slot are mandatory.' });
      return;
    }
    try {
      await Auth.bootstrap();
      let patientId = patientSelect?.value || null;
      let patientName = CURRENT_PATIENT?.name || '';
      let patientPhone = normalizePhone(
        CURRENT_PATIENT?.phone,
        CURRENT_PATIENT?.email,
        STORED_FULL?.user?.phone,
        STORED_FULL?.user?.email
      );
      let petName = null;
      if (!patientPhone) {
        patientPhone = normalizePhone(STORED_FULL?.user?.phone, STORED_FULL?.user?.email);
      }
      if (PATIENT_MODE === 'new') {
        const name = bookingForm.elements['new_patient_name'].value.trim();
        const phone = bookingForm.elements['new_patient_phone'].value.trim();
        const email = bookingForm.elements['new_patient_email'].value.trim();
        const newPetName = bookingForm.elements['new_pet_name'].value.trim();
        if (!name || (!phone && !email)) {
          Swal.fire({ icon: 'warning', title: 'Patient details required', text: 'Provide name and phone or email.' });
          return;
        }
        if (!newPetName) {
          Swal.fire({ icon: 'warning', title: 'Pet name required' });
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
        patientPhone = normalizePhone(patientRes?.data?.user?.phone, phone, patientRes?.data?.user?.email, email);
        petName = patientRes?.data?.pet?.name || newPetName;
        CURRENT_PATIENT = { id: patientId, name: patientName, phone: patientPhone };
        PREFERRED_PATIENT_ID = patientId;
        fetchPatients();
      } else {
        if (!patientId) {
          Swal.fire({ icon: 'warning', title: 'Select a patient' });
          return;
        }
        const selectedPetOption = petSelect?.options[petSelect.selectedIndex];
        petName = selectedPetOption?.dataset?.petName || selectedPetOption?.textContent || null;
        const inlinePetName = bookingForm.elements['inline_pet_name']?.value.trim();
        if (inlinePetName) petName = inlinePetName;
      }
      const payload = new FormData();
      if (patientId) payload.append('user_id', patientId);
      if (CLINIC_ID) payload.append('clinic_id', String(CLINIC_ID));
      payload.append('doctor_id', doctorId);
      payload.append('patient_name', patientName);
      if (patientPhone) payload.append('patient_phone', patientPhone);
      if (petName) payload.append('pet_name', petName);
      payload.append('date', date);
      payload.append('time_slot', timeSlot);
      if (bookingForm.elements['notes'].value.trim()) {
        payload.append('notes', bookingForm.elements['notes'].value.trim());
      }
      appendTarget(payload);
      await apiFetch(API.createAppointment, {
        method: 'POST',
        headers: Auth.headers(),
        body: payload,
      });
      Swal.fire({ icon: 'success', title: 'Appointment saved', timer: 1500, showConfirmButton: false });
      closeBooking();
      handleDoctorChange();
      handlePatientChange();
    } catch (error) {
      Swal.fire({ icon: 'error', title: 'Unable to save appointment', text: error.message || 'Unknown error' });
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    fetchDoctors();
    fetchPatients();
  });
})();
</script>

@endsection
