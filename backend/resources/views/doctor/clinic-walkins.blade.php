{{-- resources/views/doctor/patients.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Clinic Walkins')
@section('page_title','Clinic Walkins')

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

  if (!in_array($sessionRole, ['doctor', 'receptionist'], true)) {
      array_unshift(
          $clinicCandidates,
          session('user_id'),
          data_get(session('user'), 'id')
      );
  }

  $resolvedClinicId = null;
  foreach ($clinicCandidates as $candidate) {
      if ($candidate === null || $candidate === '') continue;
      $num = (int) $candidate;
      if ($num > 0) { $resolvedClinicId = $num; break; }
  }

  $sessionDoctorId = session('doctor_id')
      ?? data_get(session('doctor'), 'id')
      ?? data_get(session('auth_full'), 'doctor_id')
      ?? data_get(session('auth_full'), 'user.doctor_id')
      ?? ($sessionRole === 'doctor' ? (session('user_id') ?? data_get(session('user'), 'id')) : null);

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
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ url('vertical/assets/plugins/select2/css/select2.min.css') }}">
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
    .pm-btn:disabled{opacity:.55;cursor:not-allowed;pointer-events:none}
    .pm-content{display:grid;grid-template-columns:380px 1fr;gap:18px;margin-top:12px;align-items:start}
    .pm-left,.pm-right{background:var(--pm-panel);padding:14px;border-radius:var(--pm-radius);box-shadow:var(--pm-shadow)}
    .pm-left{min-height:640px}
    .pm-right{min-height:680px}
    .pm-searchRow{display:flex;gap:8px;align-items:center;margin-bottom:10px}
    .pm-input{padding:10px;border-radius:10px;border:1.5px solid #000;width:100%;font-size:14px;background:#fff}
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
    .pm-record-media{margin-top:10px;border:1px solid #dbeafe;background:#f8fafc;border-radius:12px;padding:10px}
    .pm-record-media img{max-width:100%;height:auto;border-radius:10px;display:block}
    .pm-record-media iframe{width:100%;height:360px;border:0;border-radius:10px;background:#fff}
    .pm-record-link{color:#1d4ed8;font-weight:700;text-decoration:none}
    .pm-record-file{font-size:12px;color:var(--pm-muted);word-break:break-word;margin-top:6px}
    .pm-record-list{margin:0;padding-left:18px}
    .pm-record-list li{margin:0 0 4px 0}
    .pv-context{border:1px dashed #dbeafe;background:linear-gradient(180deg,#f8fbff,#ffffff);padding:14px;border-radius:16px;box-shadow:0 10px 24px rgba(91,91,229,0.08);margin-bottom:12px}
    .pv-context-head{display:flex;justify-content:space-between;gap:10px;align-items:center}
    .pv-context-title{font-size:14px;font-weight:800;color:#0f172a;display:flex;gap:8px;align-items:center}
    .pv-context-meta{font-size:12px;color:#6b7280}
    .pv-context-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px;margin-top:10px}
    .pv-context-row{padding:10px 12px;border:1px solid #e5e7eb;border-radius:12px;background:#fff}
    .pv-context-label{font-size:11px;font-weight:800;color:#6b7280;text-transform:uppercase;letter-spacing:.02em}
    .pv-context-value{display:block;margin-top:4px;font-size:13px;color:#0b1220;line-height:1.45}
    .pv-context-empty{font-size:13px;color:#6b7280;margin-top:6px}
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
    .record-input{width:100%;padding:10px 11px;border-radius:12px;border:1.5px solid #000;font-size:14px;background:#fff}
    .record-textarea{min-height:70px;resize:vertical}
    .record-upload{border:2px dashed #c7d2fe;border-radius:12px;padding:12px;text-align:center;font-weight:700;color:#4338ca;cursor:pointer;background:#f8f9ff;display:inline-block}
    .record-note{font-size:12px;color:var(--pm-muted);margin-top:4px}
    .meds-shell{border:1px dashed #d7dde6;border-radius:12px;padding:12px;margin-top:8px;background:#f8fafc}
    .meds-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:10px}
    .meds-hint{font-size:12px;color:#64748b;max-width:520px}
    .meds-add{padding:8px 12px;font-weight:700;font-size:13px}
    .meds-list{display:flex;flex-direction:column;gap:12px}
    .meds-card{border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff;box-shadow:0 6px 16px rgba(15,23,42,0.06)}
    .meds-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:10px}
    .meds-field label{font-size:12px;font-weight:700;color:#111827;display:block;margin-bottom:4px}
    .meds-chipRow{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px}
    .meds-chipLabel{font-size:12px;font-weight:700;color:#111827;min-width:120px}
    .meds-chipGroup{display:flex;flex-wrap:wrap;gap:8px}
    .meds-chip{padding:7px 10px;border-radius:999px;border:1px solid #d7dde6;background:#f8fafc;font-weight:700;font-size:12px;color:#475569;cursor:pointer}
    .meds-chip.is-active{background:#0f766e;color:#fff;border-color:#0f766e;box-shadow:0 6px 14px rgba(15,118,110,0.18)}
    .meds-preview{background:#f1f5f9;border-radius:10px;padding:8px 10px;font-size:12px;color:#0f172a;margin-top:6px}
    .meds-remove{color:#b91c1c;border:1px solid #fecdd3;background:#fff1f2;padding:6px 10px;border-radius:10px;font-weight:700;font-size:12px;cursor:pointer;margin-top:8px}
    .meds-empty{font-size:12px;color:#94a3b8;margin-bottom:6px}
    .pv-shell{--pv-primary:#2563eb;--pv-primary-dark:#1e40af;--pv-bg:#f8fafc;--pv-card:#ffffff;--pv-text:#0f172a;--pv-muted:#64748b;--pv-border:#e2e8f0;--pv-radius:12px;--pv-shadow:0 10px 18px rgba(15,23,42,0.06);background:var(--pv-bg);color:var(--pv-text);font-family:'DM Sans','Inter',system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
    .pv-header{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:10px}
    .pv-overline{font-size:13px;font-weight:700;color:var(--pv-primary);letter-spacing:.01em}
    .pv-patientRow{display:flex;align-items:center;gap:6px;font-size:14px;font-weight:700}
    .pv-patient{color:var(--pv-text)}
    .pv-pet{color:var(--pv-muted)}
    .pv-sep{color:var(--pv-muted)}
    .pv-meta{margin-top:6px}
    .pv-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;font-weight:700;font-size:12px}
    .pv-badge-primary{background:#dbeafe;color:var(--pv-primary)}
    .pv-dot{width:8px;height:8px;border-radius:50%;background:var(--pv-primary);display:inline-block}
    .pv-form{display:flex;flex-direction:column;gap:14px}
    .pv-card{background:var(--pv-card);border:1px solid var(--pv-border);border-radius:var(--pv-radius);padding:16px;box-shadow:var(--pv-shadow)}
    .pv-cardTitle{font-size:16px;font-weight:700;margin-bottom:12px;color:var(--pv-text);display:flex;align-items:center;gap:6px}
    .pv-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
    .pv-grid-vitals{grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
    .pv-field{display:flex;flex-direction:column;gap:6px}
    .pv-label{font-size:13px;font-weight:700;color:var(--pv-text);display:flex;align-items:center;gap:6px}
    .pv-required{color:#ef4444}
    .pv-optional{font-size:11px;font-weight:600;color:var(--pv-muted);background:#f1f5f9;padding:2px 6px;border-radius:6px}
    .pv-input{width:100%;padding:10px 12px;border:1.5px solid #000;border-radius:10px;font-size:14px;background:var(--pv-card);transition:border-color .15s,box-shadow .15s}
    .pv-input:focus{outline:none;border-color:#000;box-shadow:0 0 0 2px rgba(0,0,0,0.1)}
    .pv-textarea{min-height:90px;resize:vertical}
    .pv-inputWrap{position:relative}
    .pv-unit{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--pv-muted)}
    .pv-upload{border:1.5px dashed var(--pv-primary);border-radius:10px;padding:12px;display:inline-flex;align-items:center;gap:8px;font-weight:700;color:var(--pv-primary);cursor:pointer;background:#eff6ff}
    .pv-helper{font-size:12px;color:var(--pv-muted);margin-top:4px}
    .pv-actions{display:flex;gap:10px;justify-content:flex-end}
    .pv-btn{padding:12px 18px;border-radius:10px;font-weight:700;font-size:14px;border:1.5px solid transparent;cursor:pointer;transition:transform .12s,box-shadow .12s}
    .pv-btn-primary{background:var(--pv-primary);color:#fff}
    .pv-btn-primary:hover{background:var(--pv-primary-dark);box-shadow:0 10px 18px rgba(37,99,235,0.25);transform:translateY(-1px)}
    .pv-btn-ghost{background:#fff;border-color:var(--pv-border);color:var(--pv-muted)}
    .pv-btn-ghost:hover{background:#f8fafc}
    .pv-critical{background:linear-gradient(180deg,#f8fafc,#ffffff);border:1px solid #e0e7ff}
    .pv-hidden{display:none!important}
    .meds-hidden-text{display:none}
    .record-critical{display:block}
    .record-critical.is-visible{display:block}
    .record-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:12px;position:sticky;bottom:0;background:#fff;padding-top:10px;padding-bottom:6px;border-top:1px solid #e5e7eb;}
    .pm-petList{display:flex;flex-direction:column;gap:10px}
    .pm-petRow{display:flex;align-items:flex-start;gap:10px;padding:10px;border-radius:12px;border:1px solid #eef6ff;background:#f8fafc}
    .pm-petAvatar{width:40px;height:40px;border-radius:10px;background:#e0e7ff;color:#1f2937;font-weight:800;display:flex;align-items:center;justify-content:center}
    .pm-petBody{flex:1}
    .pm-petName{font-weight:800}
    .pm-petMeta{font-size:12px;color:var(--pm-muted);margin-top:2px}
    .pm-petActions{display:flex;align-items:center;gap:8px;margin-left:auto}
    .pm-petDelete{border:1px solid #fecaca;background:#fff1f2;color:#b91c1c;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;cursor:pointer}
    .pm-petDelete:hover{background:#fee2e2}
    .pm-petDelete:disabled{opacity:.5;cursor:not-allowed}
    .pm-pill{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:700;background:#eef2ff;color:#4338ca;border:1px solid #e0e7ff;margin-right:6px}
    .pm-records,.pm-card,.pm-right{overflow-x:visible}
    .pm-record-head{flex-wrap:wrap}
    .pm-record-title,.pm-record-notes{word-break:break-word;white-space:normal}
    .pm-small{font-size:13px;color:var(--pm-muted)}
    @media (max-width:1100px){.pm-content{grid-template-columns:1fr}.pm-left,.pm-right{min-height:unset}.pm-profileHeader{flex-direction:column;align-items:flex-start}.pm-actions{width:100%;justify-content:flex-start}}
    .modal-overlay{position:fixed;inset:0;background:rgba(8,10,14,0.45);display:none;align-items:center;justify-content:center;padding:12px 16px;z-index:70}
    .modal-overlay.active{display:flex}
    .modal-card{width:100%;max-width:100%;height:100vh;max-height:100vh;background:#fff;border-radius:0;box-shadow:0 20px 60px rgba(15,23,42,0.25);padding:24px 28px;overflow-y:auto}
    .modal-card.booking-modal{width:min(640px,100%);max-width:640px;height:auto;max-height:min(90vh,780px);border-radius:16px;padding:14px 16px 12px;display:flex;flex-direction:column;overflow:hidden}
    .booking-modal-head{flex-shrink:0}
    .booking-modal .modal-tabs{margin-top:10px;flex-shrink:0}
    .booking-modal-form{display:flex;flex-direction:column;flex:1;min-height:0;margin-top:8px}
    .booking-modal-scroll{flex:1;min-height:0;overflow-y:auto;padding-right:4px}
    .booking-modal-scroll.space-y-4>*+*{margin-top:0.75rem}
    .booking-modal-footer{flex-shrink:0;display:flex;flex-direction:column;gap:8px;padding-top:10px;margin-top:8px;border-top:1px solid #e2e8f0;background:#fff}
    .booking-modal .patient-results{min-height:220px;max-height:300px}
    .booking-modal #existing-patient-section .patient-results{min-height:240px;max-height:320px}
    .booking-modal select,.booking-modal input,.booking-modal textarea{min-height:38px}
    .booking-modal textarea[name="notes"]{min-height:64px}
    .modal-card select{min-height:44px;padding-top:10px;padding-bottom:10px}
    .booking-modal select{min-height:38px;padding-top:8px;padding-bottom:8px}
    .select2-container{width:100%!important}
    .select2-container--default .select2-selection--single{height:44px;border-radius:0.5rem;border:1.5px solid #000;background:#fff}
    .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:44px;padding-left:0.75rem;padding-right:2.5rem;font-size:0.875rem;color:#0f172a}
    .select2-container--default .select2-selection--single .select2-selection__placeholder{color:#94a3b8}
    .select2-container--default .select2-selection--single .select2-selection__arrow{height:44px;right:10px}
    .select2-container--default.select2-container--focus .select2-selection--single{border-color:#000;box-shadow:0 0 0 2px rgba(0,0,0,0.1)}
    .select2-dropdown{border-radius:12px;border-color:#e2e8f0}
    .select2-results__option--highlighted[aria-selected]{background:#0f766e}
    .select2-container--open{z-index:90}
    .modal-tabs{margin-top:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .tab-button{padding:0.45rem 1.1rem;border-radius:999px;font-size:0.9rem;font-weight:600;border:1px solid #e2e8f0;background:#f8fafc;color:#475569;cursor:pointer;transition:background .2s,color .2s,border-color .2s}
    .tab-button.active{background:#0f766e;color:#fff;border-color:#0f766e}
    .patient-results{display:flex;flex-direction:column;gap:10px;margin-top:10px;max-height:420px;overflow-y:auto;padding-right:4px}
    .pet-type-tabs{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
    .pet-type-tab{padding:0.45rem 0.75rem;border-radius:0.5rem;font-size:0.875rem;font-weight:600;border:1px solid #e2e8f0;background:#f8fafc;color:#475569;cursor:pointer;transition:background .2s,color .2s,border-color .2s;text-transform:capitalize}
    .pet-type-tab.active{background:#0f766e;color:#fff;border-color:#0f766e}
    .patient-result{border:1px solid #e2e8f0;background:#fff;border-radius:16px;padding:12px 14px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;cursor:pointer;transition:border-color .2s,box-shadow .2s}
    .patient-result.is-selected{border-color:#0f766e;box-shadow:0 0 0 3px rgba(15,118,110,0.12)}
    .patient-result-name{font-weight:700;color:#0f172a}
    .patient-result-meta{color:#64748b;font-size:12px;margin-top:2px}
    .patient-result-tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
    .patient-result-tag{font-size:11px;font-weight:700;border-radius:999px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;padding:2px 8px}
    .patient-result-action{font-weight:700;color:#2563eb;background:none;border:none;cursor:pointer}
    .patient-results-empty{font-size:12px;color:#94a3b8;padding:6px 4px}
    .modal-card form .space-y-4>*+*{margin-top:1rem}
    /* Themed overrides */
    .pm-shell{--pm-bg:#f8fafc;--pm-panel:rgba(255,255,255,0.92);--pm-muted:#64748b;--pm-blue:#0f766e;--pm-purple:#38bdf8;--pm-green:#10b981;--pm-orange:#f97316;--pm-radius:20px;--pm-shadow:0 18px 44px rgba(15,23,42,0.1);background:linear-gradient(180deg,#f8fafc 0%,#eef6ff 100%);border-radius:26px;padding:22px;position:relative;overflow:hidden;}
    .pm-shell::before{content:"";position:absolute;top:-120px;right:-80px;width:320px;height:320px;border-radius:999px;background:radial-gradient(circle at top,rgba(56,189,248,0.22),rgba(255,255,255,0));pointer-events:none;}
    .pm-shell::after{content:"";position:absolute;bottom:-140px;left:-120px;width:300px;height:300px;border-radius:999px;background:radial-gradient(circle at top,rgba(15,118,110,0.18),rgba(255,255,255,0));pointer-events:none;}
    .pm-shell>*{position:relative;z-index:1}
    .pm-header{background:var(--pm-panel);padding:18px 20px;border-radius:22px;border:1px solid rgba(148,163,184,0.2);box-shadow:var(--pm-shadow);margin-bottom:16px;}
    .pm-title{color:#0f172a}
    .pm-subtitle{color:var(--pm-muted)}
    .pm-btn.pm-primary{background:linear-gradient(135deg,#0f766e,#38bdf8);box-shadow:0 12px 24px rgba(15,118,110,0.18);}
    .pm-btn.pm-ghost{border:1px solid #e2e8f0;color:#0f172a}
    .pm-chip{background:#ccfbf1;color:#0f766e}
    .pm-left,.pm-right{background:var(--pm-panel);border:1px solid rgba(148,163,184,0.18);border-radius:22px;}
    .pm-input{background:#fff;border:1.5px solid #000}
    .pm-tag.is-active{background:#0f766e;border-color:#0f766e}
    .pm-row.is-active{border-color:#14b8a6;background:#f0fdfa}
    .pm-avatar{background:#ecfeff;color:#0f766e}
    .pm-avatar-large{background:#f8fafc;border-color:#e2e8f0}
    .pm-badge{background:#ecfeff;color:#0f766e;border:1px solid #99f6e4}
    .pm-card{background:linear-gradient(145deg,rgba(15,118,110,0.1),rgba(56,189,248,0.08));border-color:rgba(15,118,110,0.18);box-shadow:0 14px 32px rgba(15,118,110,0.12);}
    .pm-record{background:#ffffff;border-color:rgba(15,118,110,0.2);box-shadow:0 14px 26px rgba(15,118,110,0.12);}
    .pm-record-notes{background:#f8fafc}
    .pm-tag-soft{border-color:rgba(15,118,110,0.3);background:rgba(15,118,110,0.12);color:#0f766e}
    .pm-record-row{border-color:#dbeafe;background:#f8fafc}
    .pm-stat{background:#f8fafc;border-color:#e2e8f0}
    .pm-petRow{background:#ffffff;border-color:#e2e8f0}
    .pm-petAvatar{background:#ccfbf1;color:#0f766e}
    .pm-pill{background:#ecfeff;color:#0f766e;border-color:#99f6e4}
    .record-section{background:#f8fafc;border-color:#e2e8f0}
    .record-upload{border-color:#7dd3fc;color:#0f766e;background:#f0f9ff}
    .record-status{color:#0f766e}
    .pm-header.pm-header-compact{margin-top:24px;padding:16px 20px}
    .pm-header.pm-header-compact .pm-title{font-size:20px;color:#0f172a}
    .pm-header.pm-header-compact .pm-subtitle{font-size:12px;color:#64748b}
    .cw-topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:var(--pm-panel);border:1px solid rgba(148,163,184,0.18);border-radius:22px;padding:14px 18px;box-shadow:var(--pm-shadow)}
    .cw-brand{display:flex;flex-direction:column;gap:2px;min-width:160px}
    .cw-brand-title{font-size:18px;font-weight:700;color:#0f766e}
    .cw-brand-date{font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#94a3b8;font-weight:600}
    .cw-hero{margin-top:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .cw-hero-title{font-size:32px;font-weight:300;color:#94a3b8}
    .cw-hero-title span{font-weight:700;color:#0f172a;font-style:italic}
    .cw-hero-sub{margin-top:6px;color:#64748b;font-size:14px}
    .cw-tiles{margin-top:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
    .cw-tile{background:#fff;border-radius:20px;border:1px solid #e2e8f0;padding:18px;display:flex;gap:14px;align-items:flex-start;text-align:left;transition:transform .2s ease,box-shadow .2s ease;cursor:pointer}
    .cw-tile:hover{transform:translateY(-2px);box-shadow:0 16px 30px rgba(15,23,42,0.08)}
    .cw-tile-icon{width:44px;height:44px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;border:1px solid #e2e8f0;color:#0f766e}
    .cw-tile-icon svg{width:22px;height:22px}
    .cw-tile-title{font-weight:700;color:#0f172a;font-size:16px}
    .cw-tile-sub{color:#64748b;font-size:13px;margin-top:4px}
    .cw-tile.mint{background:#ecfeff;border-color:#99f6e4}
    .cw-tile.mint .cw-tile-icon{border-color:#99f6e4;color:#0f766e}
    .cw-tile.sky{background:#eff6ff;border-color:#bfdbfe}
    .cw-tile.sky .cw-tile-icon{border-color:#bfdbfe;color:#2563eb}
    @keyframes pmFadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
    .pm-row,.pm-record,.pm-petRow{animation:pmFadeUp .4s ease both}
    @media (prefers-reduced-motion:reduce){.pm-row,.pm-record,.pm-petRow{animation:none}}
    /* Booking modal patient search loading state */
    .booking-patients-loading{display:flex;align-items:center;gap:8px;padding:10px 4px;font-size:13px;color:#64748b}
    .booking-patients-loading::before{content:"";width:16px;height:16px;border:2px solid #e2e8f0;border-top-color:#0f766e;border-radius:50%;animation:spin .6s linear infinite;flex-shrink:0}
    @keyframes spin{to{transform:rotate(360deg)}}
    .pm-shell .modal-card :is(input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="button"]):not([type="submit"]), select, textarea),
    .pm-overlay :is(input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="button"]):not([type="submit"]), select, textarea){
      border:1.5px solid #000!important;
      background:#fff;
    }
    .pm-shell .modal-card :is(input, select, textarea):focus,
    .pm-overlay :is(input, select, textarea):focus{
      outline:none;
      border-color:#000!important;
      box-shadow:0 0 0 2px rgba(0,0,0,0.1)!important;
    }
    .pm-id-tag{font-weight:700;color:#475569}
  </style>
@endsection

@section('content')
<div class="pm-shell">
  @if(!$resolvedClinicId)
    <div class="pm-alert">
      We could not detect a clinic ID in your session. Open this page from the doctor or clinic dashboard where a clinic is selected.
    </div>
  @endif

  <div class="cw-hero">
    <div>
      <div class="cw-hero-title">Namaste, <span>{{ $sessionRole === 'doctor' ? 'Dr. ' : '' }}{{ data_get(session('user'), 'name') ?? (auth()->user()?->name ?? 'Doctor') }}</span></div>
      <div class="cw-hero-sub">Here is what is happening at the Indiranagar Clinic today.</div>
    </div>
  </div>

  <div class="cw-tiles">
    <button type="button" class="cw-tile mint" data-role="booking-new">
      <div class="cw-tile-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
        </svg>
      </div>
      <div>
        <div class="cw-tile-title">New Interaction</div>
        <div class="cw-tile-sub">Intake for walk-ins or calls</div>
      </div>
    </button>
    <button type="button" class="cw-tile sky" data-role="booking-existing">
      <div class="cw-tile-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
        </svg>
      </div>
      <div>
        <div class="cw-tile-title">Existing Patient</div>
        <div class="cw-tile-sub">Quick lookup and visit history</div>
      </div>
    </button>
  </div>

  <div class="pm-header pm-header-compact">
    <div>
      <div class="pm-title">Walk-in Patients</div>
      <div class="pm-subtitle">Patient list, pet profiles, and medical documents</div>
    </div>
    <div class="pm-controls"></div>
  </div>

  <div class="pm-content">
    <div class="pm-left">
      <div class="pm-searchRow">
        <input id="pm-search" class="pm-input" type="search" placeholder="Search patient, pet, phone...">
        <select id="pm-sort" class="pm-input pm-select">
          <option value="recent">Last activity (new->old)</option>
          <option value="records">Records (high->low)</option>
          <option value="name">Patient name (A->Z)</option>
        </select>
      </div>
      <div id="pm-list" class="pm-list" role="list">
        <div class="pm-empty" id="pm-loading">Loading patients...</div>
      </div>
      <div class="pm-small" id="pm-list-count"></div>
    </div>

    <div class="pm-right">
      <div class="pm-profileHeader">
        <div class="pm-avatar-large" id="pm-profile-avatar">CW</div>
        <div class="pm-profileMain">
          <div class="pm-profileName" id="pm-profile-name">Select a patient</div>
          <div class="pm-profileSub" id="pm-profile-sub">Patient and pet details will appear here.</div>
          <div class="pm-profileMeta" id="pm-profile-meta"></div>
        </div>
        <div class="pm-actions"></div>
      </div>

      <div class="pm-gridRow">
        <div class="pm-stat">
          <div class="pm-statLabel">Records</div>
          <div class="pm-statValue" id="pm-stat-records">-</div>
          <div class="pm-statHint" id="pm-stat-last-record">Last upload -</div>
        </div>
        <div class="pm-stat">
          <div class="pm-statLabel">Contact</div>
          <div class="pm-statValue" id="pm-stat-contact">-</div>
          <div class="pm-statHint" id="pm-stat-email">-</div>
        </div>
      </div>

      <div class="pm-section">
        <div class="pm-sectionTitle">
          <div>Pets</div>
          <div class="pm-actions">
            <div class="pm-small" id="pm-pet-count">-</div>
            <button class="pm-btn pm-primary" data-role="open-pet">+ Add Pet</button>
            <button class="pm-btn pm-primary" data-role="open-upload" data-followup="1">Post Op Form</button>
            <button type="button" class="pm-btn pm-primary" id="pm-upload-document-btn" data-role="upload-document" disabled>Upload Document</button>
            <input type="file" id="pm-document-upload-input" hidden accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/*">
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
          <div class="pm-small" id="pm-record-count">-</div>
        </div>
        <div class="pm-card">
          <div id="pm-records-empty" class="pm-empty">Select a patient to see uploaded files.</div>
          <div id="pm-records-list" class="pm-records"></div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ==================== BOOKING MODAL ==================== --}}
<div id="booking-modal" class="modal-overlay" hidden aria-hidden="true">
  <div class="modal-card booking-modal">
    <div class="booking-modal-head flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold tracking-wide text-teal-600 uppercase">Front Desk</p>
        <h3 class="text-lg font-semibold text-slate-900">Create Booking</h3>
      </div>
      <button type="button" data-close class="text-slate-500 hover:text-slate-700 text-lg">&times;</button>
    </div>

    <div class="modal-tabs">
      <button type="button" class="tab-button active" data-patient-mode="new">New Patient</button>
      <button type="button" class="tab-button" data-patient-mode="existing">Existing Patient</button>
    </div>

    <form id="booking-form" class="booking-modal-form">
      <div class="booking-modal-scroll space-y-4">
      <div id="booking-notes-block">
        <label class="block text-sm font-semibold mb-1">What happened?</label>
        <textarea name="notes" rows="2" class="w-full bg-white rounded-lg px-3 py-2 text-sm border border-black focus:ring-2 focus:ring-teal-500" placeholder="Share the reason or context for this visit"></textarea>
      </div>

      {{-- EXISTING PATIENT SECTION --}}
      <div id="existing-patient-section" class="space-y-4 hidden">
        <div>
          <label id="patient-picker-label" class="block text-sm font-semibold mb-1">Patient</label>
          <div id="existing-patient-display-name" class="hidden text-sm font-semibold text-slate-800 mb-2"></div>
          <div class="flex flex-col gap-2">
            <div id="patient-search-block" class="flex flex-col gap-2">
              <input id="patient-search" type="text" placeholder="Search by name or mobile number..."
                class="bg-white rounded-lg px-3 py-2 text-sm border border-black focus:ring-2 focus:ring-teal-500">
              <div id="patient-results" class="patient-results"></div>
            </div>
            {{-- Hidden select kept for form value compatibility --}}
            <select id="patient-select" name="patient_id" class="hidden"></select>
          </div>
        </div>
        <div id="existing-patient-details" class="space-y-4 hidden">
          <div>
            <label class="block text-sm font-semibold mb-1">Pet</label>
            <select id="pet-select" name="pet_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500"></select>
          </div>
          {{-- Hidden fields kept for programmatic values / adding a new pet via API if needed later --}}
          <div id="existing-inline-pet-fields" class="space-y-3 hidden" aria-hidden="true">
            <div>
              <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Pet Name</label>
              <input name="inline_pet_name" type="text" placeholder="Pet name" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Pet Type</label>
              <div class="pet-type-tabs" data-pet-type-group="inline" role="tablist" aria-label="Pet type">
                <button type="button" class="pet-type-tab active" data-pet-type="dog">Dog</button>
                <button type="button" class="pet-type-tab" data-pet-type="cat">Cat</button>
                <button type="button" class="pet-type-tab" data-pet-type="exotic">Exotic</button>
              </div>
              <input type="hidden" name="inline_pet_type" value="dog">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div data-breed-wrap="inline-select">
                <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Breed</label>
                <select name="inline_pet_breed" class="breed-select w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
                  <option value="">Select breed</option>
                </select>
              </div>
              <div data-breed-wrap="inline-exotic" class="hidden md:col-span-1">
                <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Which exotic pet?</label>
                <input name="inline_pet_exotic_detail" type="text" placeholder="e.g. Parrot, Rabbit, Turtle" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
              </div>
              <div>
                <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Gender</label>
                <select name="inline_pet_gender" class="w-full bg-white rounded-lg px-3 py-2 text-sm border border-black focus:ring-2 focus:ring-teal-500">
                  <option value="">Select gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div id="booking-submit-fields" class="space-y-4">
        {{-- NEW PATIENT SECTION --}}
        <div id="new-patient-section" class="hidden space-y-4">
          <div>
            <label class="block text-sm font-semibold mb-1">Pet Name</label>
            <input name="new_pet_name" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-semibold mb-1">Parent name</label>
              <input name="new_patient_name" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Phone</label>
              <input name="new_patient_phone" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-semibold mb-1">Email</label>
              <input name="new_patient_email" type="email" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Pet Type</label>
            <div class="pet-type-tabs" data-pet-type-group="new" role="tablist" aria-label="Pet type">
              <button type="button" class="pet-type-tab active" data-pet-type="dog">Dog</button>
              <button type="button" class="pet-type-tab" data-pet-type="cat">Cat</button>
              <button type="button" class="pet-type-tab" data-pet-type="exotic">Exotic</button>
            </div>
            <input type="hidden" name="new_pet_type" value="dog">
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div data-breed-wrap="new-select">
              <label class="block text-sm font-semibold mb-1">Breed</label>
              <select name="new_pet_breed" class="breed-select w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
                <option value="">Select breed</option>
              </select>
            </div>
            <div data-breed-wrap="new-exotic" class="hidden">
              <label class="block text-sm font-semibold mb-1">Which exotic pet?</label>
              <input name="new_pet_exotic_detail" type="text" placeholder="e.g. Parrot, Rabbit, Turtle" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Gender</label>
              <select name="new_pet_gender" class="w-full bg-white rounded-lg px-3 py-2 text-sm border border-black focus:ring-2 focus:ring-teal-500">
                <option value="">Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
          </div>
          <p class="text-xs text-slate-500">Provide at least a phone number or email for the patient, and pet details so we can attach the booking.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold mb-1">Doctor</label>
            <select id="booking-doctor-select" name="doctor_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
              <option value="">Any available doctor</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Service Type</label>
            <select name="service_type" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
              <option value="in_clinic">In Clinic</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
<label class="block text-sm font-semibold mb-1">Date <span class="text-xs font-normal text-slate-400">(optional)</span></label>
<input name="scheduled_date" type="date" class="w-full bg-white rounded-lg px-3 py-2 text-sm border border-black focus:ring-2 focus:ring-teal-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Available Slots</label>
            <select name="scheduled_time" id="slot-select" class="w-full bg-white rounded-lg px-3 py-2 text-sm border border-black focus:ring-2 focus:ring-teal-500">
              <option value="">Select a time slot</option>
            </select>
            <p id="slot-hint" class="text-xs text-slate-500 mt-1">Select a doctor and date first to load available slots.</p>
          </div>
        </div>
      </div>
      </div>

      <div class="booking-modal-footer">
        <div class="flex flex-col sm:flex-row justify-end gap-2">
          <button type="button" data-close class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold">Save Booking</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- ==================== PET MODAL ==================== --}}
<div id="pet-modal" class="pm-overlay">
  <div class="pm-modal" role="dialog" aria-modal="true">
    <div class="record-header">
      <div>
        <div class="record-title">Add Pet</div>
        <div id="pet-modal-patient" class="record-patient">Patient | -</div>
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

{{-- ==================== RECORD MODAL ==================== --}}
<div class="pm-overlay" id="record-modal">
  <div class="pm-modal record-modal pv-shell" role="dialog" aria-modal="true">
    <div class="pv-header">
      <div>
        <div class="pv-overline">Close Consultation</div>
        <div class="pv-patientRow">
          <span id="record-modal-patient" class="pv-patient">Patient | -</span>
          <span class="pv-sep">•</span>
          <span id="record-modal-pet" class="pv-pet"></span>
        </div>
        <div class="pv-meta">
          <span class="pv-badge pv-badge-primary"><span class="pv-dot"></span> Active Case</span>
        </div>
      </div>
      <button type="button" class="pv-btn pv-btn-ghost" data-role="close-record-modal">Close</button>
    </div>

    <form id="record-form" class="pv-form" enctype="multipart/form-data">
      <input type="hidden" name="user_id" id="record-user-id">
      <input type="hidden" name="record_id" id="record-id">

      <div class="pv-card pv-context" id="pv-followup-context" style="display:none">
        <div class="pv-context-head">
          <div class="pv-context-title"><span>Previous consultation snapshot</span></div>
          <div class="pv-context-meta" id="pv-followup-context-meta"></div>
        </div>
        <div class="pv-context-grid" id="pv-followup-context-body"></div>
      </div>

      <div class="pv-card" id="pv-overview-card">
        <div class="pv-cardTitle">Visit Overview</div>
        <div class="pv-grid">
          <div class="pv-field">
            <label class="pv-label" for="visit-category"><span class="pv-required">*</span> Visit Category</label>
            <select id="visit-category" name="visit_category" class="pv-input">
              <option value="vaccination">Vaccination</option>
              <option value="consultation">Consultation</option>
              <option value="video_consultation">Video Consultation</option>
              <option value="followup">Follow-up</option>
              <option value="deworming">Deworming</option>
            </select>
          </div>

          <div class="pv-field vaccination-field" style="display:none">
            <label class="pv-label">Vaccination Certificate</label>
            <label class="pv-upload" for="vaccination-certificate-file" style="margin-top:2px;">
              <span id="vaccination-certificate-status-icon">📎</span> <span id="vaccination-certificate-status-text">Upload certificate (Gemini Auto-Parse)</span>
            </label>
            <input id="vaccination-certificate-file" name="vaccination_certificate_file" type="file" class="pv-input" accept="image/*,application/pdf" style="display:none">
            <div class="pv-helper">PDF, JPG, PNG up to 10 MB.</div>
          </div>
          <div class="pv-field vaccination-field" style="display:none; grid-column: 1 / -1; margin-top: 10px;">
            <label class="pv-label" style="font-weight: 700; margin-bottom: 4px;">Parsed Certificate Data</label>
            <textarea id="vaccination-certificate-json" name="vaccination_certificate_json" style="display:none;"></textarea>
            
            <div id="vaccination-editor-container" style="background:#f8fafc; border:1.5px solid #cbd5e1; border-radius:12px; padding:16px; margin-top:4px; box-shadow:inset 0 2px 4px rgba(0,0,0,0.02);">
              <!-- Empty state text when no vaccines are parsed -->
              <div id="vaccination-editor-empty" style="color:#64748b; font-size:13px; text-align:center; padding:20px 0; border:2px dashed #cbd5e1; border-radius:10px; background:#fff;">
                No parsed vaccine data yet. Upload a certificate above to auto-populate, or click "+ Add Vaccine" below.
              </div>
              
              <!-- Loader showing while analyzing -->
              <div id="vaccination-editor-loading" style="display:none; text-align:center; padding:30px 0; border:2px dashed #2563eb; border-radius:10px; background:#eff6ff;">
                <div style="width: 32px; height: 32px; border: 3px solid #bfdbfe; border-top: 3px solid #2563eb; border-radius: 50%; display: inline-block; animation: spin 1s linear infinite;"></div>
                <div style="margin-top:12px; font-weight:700; font-size:14px; color:#1e40af;">Analyzing certificate with Gemini 2.5 Flash...</div>
                <div style="font-size:12px; color:#60a5fa; margin-top:4px;">Please wait while we extract the vaccination details</div>
              </div>
              
              <!-- Vaccines list container -->
              <div id="vaccination-editor-list" style="display:flex; flex-direction:column; gap:10px; margin-bottom:12px;"></div>
              
              <!-- Add vaccine button -->
              <div style="display:flex; justify-content:flex-end;">
                <button type="button" id="vaccination-editor-add-btn" class="pv-btn pv-btn-ghost" style="padding:6px 12px; font-size:12px; display:inline-flex; align-items:center; gap:6px; font-weight:700; border:1.5px solid #cbd5e1; border-radius:8px; height:auto;">
                  ➕ Add Vaccine
                </button>
              </div>
            </div>
            <div class="pv-helper">Verify and edit the extracted vaccinations above. They will be saved to the pet's vaccination record automatically.</div>
          </div>
          <div class="pv-field deworming-field" style="display:none">
            <label class="pv-label" for="deworming-status"><span class="pv-required">*</span> Deworming</label>
            <select id="deworming-status" name="deworming" class="pv-input">
              <option value="no">No</option>
              <option value="yes">Yes</option>
            </select>
          </div>
          <div class="pv-field deworming-field deworming-date-container" style="display:none">
            <label class="pv-label" for="last-deworming-date"><span class="pv-required">*</span> Last Deworming Date</label>
            <input type="date" id="last-deworming-date" name="last_deworming_date" class="pv-input">
          </div>
          <div class="pv-field">
            <label class="pv-label" for="case-severity"><span class="pv-required">*</span> Case Severity</label>
            <select id="case-severity" name="case_severity" class="pv-input" data-role="case-severity">
              <option value="general">General</option>
              <option value="critical">Critical / Treatment</option>
            </select>
          </div>
          <div class="pv-field">
            <label class="pv-label" for="doctor-select"><span class="pv-required">*</span> Doctor</label>
            <select name="doctor_id" id="doctor-select" class="pv-input">
              <option value="">Select doctor</option>
            </select>
          </div>
          <div class="pv-field">
            <label class="pv-label" for="record-pet"><span class="pv-required">*</span> Pet</label>
            <select name="pet_id" id="record-pet" class="pv-input">
              <option value="">Select pet</option>
            </select>
          </div>
        </div>
      </div>

      <div class="pv-card" id="pv-notes-card">
        <div class="pv-cardTitle">Visit Notes</div>
        <textarea id="record-notes" name="notes" class="pv-input pv-textarea" placeholder="Reason for visit / brief notes"></textarea>
        <div class="pv-field" style="margin-top:10px">
          <label class="pv-label" for="doctor-treatment">Doctor Treatment</label>
          <textarea id="doctor-treatment" name="doctor_treatment" class="pv-input pv-textarea" placeholder="Doctor treatment details"></textarea>
        </div>
      </div>

      <div class="pv-card pv-critical" data-critical id="pv-clinical-card">
        <div class="pv-cardTitle">Clinical Observations</div>
        <div class="pv-grid pv-grid-vitals">
          <div class="pv-field pv-vital">
            <label class="pv-label" for="temperature">Temperature</label>
            <div class="pv-inputWrap">
              <input id="temperature" name="temperature" type="number" step="0.1" class="pv-input" placeholder="38.5">
              <span class="pv-unit">°C</span>
            </div>
          </div>
          <div class="pv-field pv-vital">
            <label class="pv-label" for="weight">Weight</label>
            <div class="pv-inputWrap">
              <input id="weight" name="weight" type="number" step="0.1" class="pv-input" placeholder="4.5">
              <span class="pv-unit">kg</span>
            </div>
          </div>
          <div class="pv-field pv-vital">
            <label class="pv-label" for="heart-rate">Heart Rate <span class="pv-optional">optional</span></label>
            <div class="pv-inputWrap">
              <input id="heart-rate" name="heart_rate" type="number" class="pv-input" placeholder="120">
              <span class="pv-unit">bpm</span>
            </div>
          </div>
        </div>
        <div class="pv-field">
          <label class="pv-label" for="exam-notes">Physical examination notes <span class="pv-optional">optional</span></label>
          <textarea id="exam-notes" name="exam_notes" class="pv-input pv-textarea" placeholder="Physical examination findings..."></textarea>
        </div>
      </div>

      <div class="pv-card pv-critical" data-critical id="pv-diagnosis-card">
        <div class="pv-cardTitle"><span class="pv-required">*</span> Diagnosis &amp; Assessment</div>
        <div class="pv-grid">
          <div class="pv-field">
            <label class="pv-label" for="diagnosis">Diagnosis</label>
            <input id="diagnosis" name="diagnosis" type="text" class="pv-input" placeholder="Diagnosis (e.g. UTI)">
          </div>
          <div class="pv-field">
            <label class="pv-label" for="diagnosis-status">Status</label>
            <select id="diagnosis-status" name="diagnosis_status" class="pv-input">
              <option value="new">New</option>
              <option value="ongoing">Ongoing</option>
              <option value="chronic">Chronic</option>
            </select>
          </div>
        </div>
      </div>

      <div class="pv-card pv-critical" data-critical id="pv-treatment-card">
        <div class="pv-cardTitle">Treatment &amp; Medications</div>
        <div class="pv-field">
          <label class="pv-label" for="treatment-plan">Medication / treatment plan</label>
          <textarea id="treatment-plan" name="treatment_plan" class="pv-input pv-textarea" placeholder="Medication / treatment plan"></textarea>
        </div>
        <div class="pv-field meds-shell">
          <div class="meds-head">
            <div>
              <div class="pv-label" style="margin-bottom:2px">Medicines (structured)</div>
              <div class="meds-hint">Type medicine name → choose frequency &amp; timing → set dosage and duration.</div>
            </div>
            <button class="meds-add pm-btn pm-primary" type="button" data-role="add-medicine">+ Add medicine</button>
          </div>
          <input type="hidden" id="medications-json" name="medications_json">
          <textarea id="medicines-text" name="medicines" class="pv-input pv-textarea meds-hidden-text" style="display:none"></textarea>
          <div id="medications-empty" class="meds-empty">No medicines added yet. Click "Add medicine".</div>
          <div id="medications-list" class="meds-list"></div>
        </div>
        <div class="pv-field">
          <label class="pv-label" for="home-care">Home care / precautions <span class="pv-optional">optional</span></label>
          <textarea id="home-care" name="home_care" class="pv-input pv-textarea" placeholder="Care instructions shown to pet parent"></textarea>
          <div class="pv-helper">Optional — when filled, this text is shared with the pet parent.</div>
        </div>
      </div>

      <div class="pv-card" id="pv-documents-card">
        <div class="pv-cardTitle">Documents <span class="pv-optional">optional</span></div>
        <label class="pv-upload" for="record-file"><span>📎</span> Upload prescription / lab report</label>
        <input id="record-file" name="record_file" type="file" class="pv-input">
        <div class="pv-helper">PDF, JPG, PNG, DOC up to 10 MB.</div>
      </div>

      <div class="pv-card pv-critical" data-critical id="pv-followup-card">
        <div class="pv-cardTitle">Follow-up</div>
        <div class="pv-grid">
          <div class="pv-field">
            <label class="pv-label" for="follow-up-date">Date</label>
            <input id="follow-up-date" name="follow_up_date" type="date" class="pv-input">
          </div>
          <div class="pv-field">
            <label class="pv-label" for="follow-up-type">Visit Type</label>
            <select id="follow-up-type" name="follow_up_type" class="pv-input">
              <option value="clinic">Clinic Visit</option>
              <option value="video">Video Consultation</option>
            </select>
          </div>
        </div>
      </div>

      <div class="pv-actions">
        <button type="button" class="pv-btn pv-btn-ghost" data-role="close-record-modal">Cancel</button>
        <button type="submit" class="pv-btn pv-btn-primary">Close &amp; Share</button>
      </div>
    </form>
  </div>
</div>

<script src="{{ url('vertical/assets/js/jquery.min.js') }}"></script>
<script src="{{ url('vertical/assets/plugins/select2/js/select2.min.js') }}"></script>

{{-- ==================== SHARED PATIENT STORE ==================== --}}
<script>
/**
 * PatientStore — single source of truth for patients loaded from
 * GET /api/clinics/{CLINIC_ID}/patients
 * Both the walk-in list and the booking modal subscribe to this.
 */
window.PatientStore = (() => {
  const ORIGIN  = window.location.origin;
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
  const API_BASE = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
  const CLINIC_ID = Number(@json($resolvedClinicId ?? null)) || null;

  let _patients  = [];
  let _loading   = false;
  let _loaded    = false;
  let _error     = null;
  const _listeners = [];

  function notify() {
    _listeners.forEach(fn => fn({ patients: _patients, loading: _loading, loaded: _loaded, error: _error }));
  }

  async function load(force = false) {
    if (!CLINIC_ID) return;
    if (_loading) return;
    if (_loaded && !force) { notify(); return; }
    _loading = true;
    _error   = null;
    notify();
    try {
      const res  = await fetch(`${API_BASE}/clinics/${CLINIC_ID}/patients`, {
        headers: { Accept: 'application/json' },
      });
      const text = await res.text();
      let data = null;
      try { data = text ? JSON.parse(text) : null; } catch (_) {}
      if (!res.ok) throw new Error(data?.message || data?.error || 'Failed to load patients');
      _patients = Array.isArray(data?.patients) ? data.patients : [];
      _loaded   = true;
    } catch (err) {
      _error    = err.message || 'Unknown error';
      _patients = [];
    } finally {
      _loading = false;
      notify();
    }
  }

  /** Force-reload (e.g. after adding a patient/pet) */
  function reload() { return load(true); }

  /** Subscribe: fn({ patients, loading, loaded, error }) */
  function subscribe(fn) {
    _listeners.push(fn);
    // Immediately deliver current state if already loaded
    if (_loaded || _error) {
      fn({ patients: _patients, loading: _loading, loaded: _loaded, error: _error });
    }
  }

  function getAll()    { return _patients; }
  function getClinicId() { return CLINIC_ID; }
  function getApiBase()  { return API_BASE; }

  /** Match patient/pet by name, phone, email, user id, pet id (e.g. "13" matches #1382). */
  function matchesSearch(patient, query) {
    const q = String(query ?? '').trim().toLowerCase();
    if (!q) return true;
    const qDigits = q.replace(/\D+/g, '');

    const fieldMatches = (value) => {
      if (value === null || value === undefined || value === '') return false;
      const text = String(value).toLowerCase();
      if (text.includes(q)) return true;
      if (qDigits) {
        const digits = text.replace(/\D+/g, '');
        if (digits.includes(qDigits)) return true;
      }
      return false;
    };

    if (
      fieldMatches(patient?.name) ||
      fieldMatches(patient?.email) ||
      fieldMatches(patient?.phone) ||
      fieldMatches(patient?.id)
    ) {
      return true;
    }

    const pets = Array.isArray(patient?.pets) ? patient.pets : [];
    return pets.some(pet =>
      fieldMatches(pet?.name) ||
      fieldMatches(pet?.pet_name) ||
      fieldMatches(pet?.breed) ||
      fieldMatches(pet?.type) ||
      fieldMatches(pet?.pet_type) ||
      fieldMatches(pet?.id) ||
      fieldMatches(pet?.pet_id)
    );
  }

  return { load, reload, subscribe, getAll, getClinicId, getApiBase, matchesSearch };
})();
</script>

{{-- ==================== WALK-IN LIST MODULE ==================== --}}
<script>
(() => {
  const API_BASE   = window.PatientStore.getApiBase();
  const CLINIC_ID  = window.PatientStore.getClinicId();
  const DEFAULT_DOCTOR_ID = Number(@json($sessionDoctorId ?? null)) || null;
  const DOCUMENT_UPLOAD_POST_ENDPOINT = '/documents/upload';
  const DOCUMENT_UPLOAD_POST_URL = `${API_BASE}${DOCUMENT_UPLOAD_POST_ENDPOINT}`;
  const DOCUMENT_UPLOAD_MAX_BYTES = 10 * 1024 * 1024;

  const state = {
    patients: [],
    search: '',
    sort: 'recent',
    selectedId: null,
    records: new Map(),
    lastPostOp: null,
    loadingPatients: false,
    loadingRecords: false,
    editingRecordId: null,
  };

  const els = {
    search:              document.getElementById('pm-search'),
    sort:                document.getElementById('pm-sort'),
    list:                document.getElementById('pm-list'),
    listCount:           document.getElementById('pm-list-count'),
    profileAvatar:       document.getElementById('pm-profile-avatar'),
    profileName:         document.getElementById('pm-profile-name'),
    profileSub:          document.getElementById('pm-profile-sub'),
    profileMeta:         document.getElementById('pm-profile-meta'),
    statRecords:         document.getElementById('pm-stat-records'),
    statLastRecord:      document.getElementById('pm-stat-last-record'),
    statContact:         document.getElementById('pm-stat-contact'),
    statEmail:           document.getElementById('pm-stat-email'),
    recordCount:         document.getElementById('pm-record-count'),
    recordEmpty:         document.getElementById('pm-records-empty'),
    recordList:          document.getElementById('pm-records-list'),
    petCount:            document.getElementById('pm-pet-count'),
    petEmpty:            document.getElementById('pm-pets-empty'),
    petList:             document.getElementById('pm-pets-list'),
    followupContextCard: document.getElementById('pv-followup-context'),
    followupContextBody: document.getElementById('pv-followup-context-body'),
    followupContextMeta: document.getElementById('pv-followup-context-meta'),
    openUploadBtns:      Array.from(document.querySelectorAll('[data-role="open-upload"]')),
    openPetBtns:         Array.from(document.querySelectorAll('[data-role="open-pet"]')),
    documentUploadBtn:   document.getElementById('pm-upload-document-btn'),
    documentUploadInput: document.getElementById('pm-document-upload-input'),
    modal:               document.getElementById('record-modal'),
    modalPatient:        document.getElementById('record-modal-patient'),
    modalPet:            document.getElementById('record-modal-pet'),
    modalUserInput:      document.getElementById('record-user-id'),
    recordForm:          document.getElementById('record-form'),
    doctorSelect:        document.getElementById('doctor-select'),
    recordPet:           document.getElementById('record-pet'),
    recordNotes:         document.getElementById('record-notes'),
    medicationsList:     document.getElementById('medications-list'),
    medicationsEmpty:    document.getElementById('medications-empty'),
    medicationsJson:     document.getElementById('medications-json'),
    medicinesText:       document.getElementById('medicines-text'),
    addMedicineBtn:      document.querySelector('[data-role="add-medicine"]'),
    visitCategory:       document.getElementById('visit-category'),
    clinicalCard:        document.getElementById('pv-clinical-card'),
    diagnosisCard:       document.getElementById('pv-diagnosis-card'),
    treatmentCard:       document.getElementById('pv-treatment-card'),
    followupCard:        document.getElementById('pv-followup-card'),
    caseSeverity:        document.getElementById('case-severity'),
    criticalSections:    Array.from(document.querySelectorAll('[data-critical]')),
    petModal:            document.getElementById('pet-modal'),
    petForm:             document.getElementById('pet-form'),
    petPatient:          document.getElementById('pet-modal-patient'),
    petUserInput:        document.getElementById('pet-user-id'),
  };

  let lastRecordError = null;

  /* ---- MEDICATION helpers (unchanged from original) ---- */
  const MED_FREQUENCIES = [
    { value: 'OD (Once daily)', label: 'OD (Once daily)' },
    { value: 'BD (Twice daily)', label: 'BD (Twice daily)' },
    { value: 'TDS (3 times)',    label: 'TDS (3 times)'    },
    { value: 'QID (4 times)',    label: 'QID (4 times)'    },
  ];
  const MED_TIMINGS = [
    { value: 'Morning',   label: 'Morning'   },
    { value: 'Afternoon', label: 'Afternoon' },
    { value: 'Evening',   label: 'Evening'   },
    { value: 'Night',     label: 'Night'     },
  ];
  const MED_FOOD = [
    { value: 'Before food (AC)', label: 'Before food (AC)' },
    { value: 'After food (PC)',  label: 'After food (PC)'  },
    { value: 'With food',        label: 'With food'        },
    { value: 'Empty stomach',    label: 'Empty stomach'    },
  ];
  let medications = [];

  const cleanTimings = v => Array.isArray(v) ? Array.from(new Set(v.map(t => t && t.toString().trim()).filter(Boolean))) : [];
  const newMedication = (init = {}) => ({ name:'', dose:'', frequency:'', duration:'', route:'', notes:'', timings:[], food_relation:'', ...init });

  function medPreviewLine(med) {
    const parts = [];
    if (med.name) parts.push(med.name);
    if (med.dose) parts.push(med.dose);
    if (med.frequency) parts.push(med.frequency);
    const t = cleanTimings(med.timings);
    if (t.length) parts.push(`Timing: ${t.join(', ')}`);
    if (med.food_relation) parts.push(med.food_relation);
    if (med.duration) parts.push(`Duration: ${med.duration}`);
    if (med.notes) parts.push(`Notes: ${med.notes}`);
    return parts.filter(Boolean).join(' • ') || 'Fill in details to see prescription';
  }

  function normalizeMedicationState(raw) {
    const meds = normalizeMedications(raw);
    return meds.map(med => newMedication({
      name: med.name||med.medicine||med.title||'',
      dose: med.dose||'', frequency: med.frequency||'', duration: med.duration||'',
      route: med.route||'', notes: med.notes||'',
      timings: cleanTimings(med.timings||med.timing||[]),
      food_relation: med.food_relation||med.food||'',
    })).filter(m => medPreviewLine(m).trim() !== 'Fill in details to see prescription');
  }

  function syncMedicationPayload() {
    const payload = medications.map(med => ({ ...med, timings: cleanTimings(med.timings), food_relation: (med.food_relation||'').trim() }))
      .filter(med => Boolean((med.name||'').trim()||(med.dose||'').trim()||(med.frequency||'').trim()||(med.duration||'').trim()||cleanTimings(med.timings).length||(med.food_relation||'').trim()||(med.notes||'').trim()));
    if (els.medicationsJson) els.medicationsJson.value = payload.length ? JSON.stringify(payload) : '';
    if (els.medicinesText)   els.medicinesText.value   = payload.map(m => medPreviewLine(m)).join(';\n');
    if (els.medicationsEmpty) els.medicationsEmpty.style.display = payload.length ? 'none' : 'block';
  }

  function buildChipRow(label, options, { multi=false, med, field, onChange }) {
    const row = document.createElement('div'); row.className = 'meds-chipRow';
    const lab = document.createElement('div'); lab.className = 'meds-chipLabel'; lab.textContent = label;
    const group = document.createElement('div'); group.className = 'meds-chipGroup';
    options.forEach(opt => {
      const btn = document.createElement('button'); btn.type = 'button';
      const isActive = multi ? cleanTimings(med[field]).includes(opt.value) : (med[field]||'') === opt.value;
      btn.className = 'meds-chip' + (isActive ? ' is-active' : '');
      btn.textContent = opt.label;
      btn.addEventListener('click', () => {
        if (multi) {
          const next = new Set(cleanTimings(med[field]));
          next.has(opt.value) ? next.delete(opt.value) : next.add(opt.value);
          med[field] = Array.from(next); btn.classList.toggle('is-active'); onChange(med[field]);
        } else {
          med[field] = opt.value; group.querySelectorAll('.meds-chip').forEach(c => c.classList.remove('is-active')); btn.classList.add('is-active'); onChange(opt.value);
        }
      });
      group.appendChild(btn);
    });
    row.appendChild(lab); row.appendChild(group); return row;
  }

  function buildMedicationCard(med, index) {
    const card = document.createElement('div'); card.className = 'meds-card';
    let preview = null;
    const row = document.createElement('div'); row.className = 'meds-row';
    [{ label:'Medicine Name', field:'name', placeholder:'Start typing medicine name...' },
     { label:'Dosage',        field:'dose', placeholder:'e.g., 1 tab'                   },
     { label:'Duration',      field:'duration', placeholder:'e.g., 5 days'              }].forEach(cfg => {
      const wrap = document.createElement('div'); wrap.className = 'meds-field';
      const lab  = document.createElement('label'); lab.textContent = cfg.label;
      const input = document.createElement('input'); input.type = 'text'; input.value = med[cfg.field]||''; input.placeholder = cfg.placeholder; input.className = 'record-input';
      input.addEventListener('input', ev => { med[cfg.field] = ev.target.value; if (preview) preview.textContent = medPreviewLine(med); syncMedicationPayload(); });
      wrap.appendChild(lab); wrap.appendChild(input); row.appendChild(wrap);
    });
    card.appendChild(row);
    const upd = () => { preview.textContent = medPreviewLine(med); syncMedicationPayload(); };
    card.appendChild(buildChipRow('Frequency',    MED_FREQUENCIES, { med, field:'frequency',    onChange: upd }));
    card.appendChild(buildChipRow('Timing',       MED_TIMINGS,     { med, field:'timings',       multi:true, onChange: upd }));
    card.appendChild(buildChipRow('Food Relation',MED_FOOD,        { med, field:'food_relation', onChange: upd }));
    preview = document.createElement('div'); preview.className = 'meds-preview'; preview.textContent = medPreviewLine(med);
    card.appendChild(preview);
    const removeBtn = document.createElement('button'); removeBtn.type = 'button'; removeBtn.className = 'meds-remove'; removeBtn.textContent = 'Remove';
    removeBtn.addEventListener('click', () => { medications.splice(index,1); renderMedicationCards(); syncMedicationPayload(); });
    card.appendChild(removeBtn);
    return card;
  }

  function renderMedicationCards() {
    if (!els.medicationsList) return;
    els.medicationsList.innerHTML = '';
    medications.forEach((med, idx) => els.medicationsList.appendChild(buildMedicationCard(med, idx)));
    if (els.medicationsEmpty) els.medicationsEmpty.style.display = medications.length ? 'none' : 'block';
  }

  function addMedication(prefill={})  { medications.push(newMedication(prefill)); renderMedicationCards(); syncMedicationPayload(); }
  function resetMedications()         { medications = []; renderMedicationCards(); syncMedicationPayload(); }

  function toggleCriticalSections()   { (els.criticalSections||[]).forEach(s => { s.style.display='block'; s.classList.add('is-visible'); }); }
  function setCardVisible(card, vis)  { if (!card) return; card.classList.toggle('pv-hidden', !vis); }

  function normalizeVisitCategoryValue(v) {
    const raw = String(v??'').trim().toLowerCase();
    if (!raw) return '';
    if (raw==='follow_up'||raw==='followup') return 'followup';
    if (['video_consult','video-consult','video_consultation','video consultation'].includes(raw)) return 'video_consultation';
    return raw;
  }

  function updateDewormingDateUI() {
    const isDeworm = (els.visitCategory?.value === 'deworming');
    const dewormingStatus = document.getElementById('deworming-status');
    const dewormingDateContainer = document.querySelector('.deworming-date-container');
    const lastDewormingDateInput = document.getElementById('last-deworming-date');

    if (isDeworm && dewormingStatus?.value === 'yes') {
      if (dewormingDateContainer) dewormingDateContainer.style.display = 'block';
      if (lastDewormingDateInput) lastDewormingDateInput.setAttribute('required', 'required');
    } else {
      if (dewormingDateContainer) dewormingDateContainer.style.display = 'none';
      if (lastDewormingDateInput) {
        lastDewormingDateInput.removeAttribute('required');
        if (!isDeworm || dewormingStatus?.value !== 'yes') {
          lastDewormingDateInput.value = '';
        }
      }
    }
  }

  function updateVisitCategoryUI(category) {
    const cat = normalizeVisitCategoryValue(category||els.visitCategory?.value||'');
    if (!cat) {
      setCardVisible(els.clinicalCard,true);
      setCardVisible(els.diagnosisCard,true);
      setCardVisible(els.treatmentCard,true);
      setCardVisible(els.followupCard,true);
      document.querySelectorAll('.vaccination-field').forEach(el => el.style.display = 'none');
      document.querySelectorAll('.deworming-field').forEach(el => el.style.display = 'none');
      return;
    }
    const isVacc   = cat === 'vaccination';
    const isVideo  = cat === 'video_consultation';
    const isDeworm = cat === 'deworming';

    setCardVisible(els.clinicalCard,  !isVacc && !isVideo && !isDeworm);
    setCardVisible(els.diagnosisCard, !isVacc && !isVideo && !isDeworm);
    setCardVisible(els.treatmentCard, (!isVacc || isVideo) && !isDeworm);
    setCardVisible(els.followupCard,  !isDeworm);

    document.querySelectorAll('.vaccination-field').forEach(el => el.style.display = isVacc ? 'block' : 'none');
    document.querySelectorAll('.deworming-field').forEach(el => el.style.display = isDeworm ? 'block' : 'none');

    updateDewormingDateUI();
    renderFollowupContext();
  }

  function escapeHtml(v) {
    return String(v??'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
  }

  function formatDate(v, withTime=true) {
    if (!v) return '-';
    const d = new Date(v);
    if (isNaN(d.getTime())) return v;
    return new Intl.DateTimeFormat(undefined, withTime ? { dateStyle:'medium', timeStyle:'short' } : { dateStyle:'medium' }).format(d);
  }

  function normalizeMedications(raw) {
    if (!raw) return [];
    if (Array.isArray(raw)) return raw;
    if (typeof raw === 'string') { try { const p = JSON.parse(raw); return Array.isArray(p) ? p : []; } catch(_) { return []; } }
    return [];
  }

  function formatMedicationList(raw) {
    const meds = normalizeMedications(raw);
    if (!meds.length) return '';
    const items = meds.map(med => {
      if (!med) return '';
      if (typeof med === 'string') { const l = escapeHtml(med); return l ? `<li>${l}</li>` : ''; }
      const parts = [];
      const name = med.name||med.medicine||med.title;
      if (name) parts.push(escapeHtml(name));
      if (med.dose) parts.push(`Dose: ${escapeHtml(med.dose)}`);
      if (med.frequency) parts.push(`Frequency: ${escapeHtml(med.frequency)}`);
      const timings = Array.isArray(med.timings) ? med.timings : (med.timing ? [med.timing] : []);
      if (timings.length) parts.push(`Timing: ${escapeHtml(timings.join(', '))}`);
      if (med.food_relation||med.food) parts.push(`Food: ${escapeHtml(med.food_relation||med.food)}`);
      if (med.duration) parts.push(`Duration: ${escapeHtml(med.duration)}`);
      if (med.route)    parts.push(`Route: ${escapeHtml(med.route)}`);
      if (med.notes)    parts.push(`Notes: ${escapeHtml(med.notes)}`);
      const l = parts.filter(Boolean).join(' | ');
      return l ? `<li>${l}</li>` : '';
    }).filter(Boolean);
    return items.length ? `<ul class="pm-record-list">${items.join('')}</ul>` : '';
  }

  function extractLastPostOp(records=[], petId=null) {
    if (!Array.isArray(records)||!records.length) return null;
    const recent = records.find(r => {
      if (!r||!r.prescription) return false;
      if (petId===null) return true;
      return Number(r.prescription.pet_id??r.pet_id??null) === Number(petId);
    }) || null;
    if (!recent||!recent.prescription) return null;
    const rx = recent.prescription;
    return {
      raw: recent, visit_category: rx.visit_category||'', case_severity: rx.case_severity||'',
      visit_notes: rx.visit_notes||'', diagnosis: rx.diagnosis||'', diagnosis_status: rx.diagnosis_status||'',
      doctor_treatment: rx.doctor_treatment||'', treatment_plan: rx.treatment_plan||'',
      home_care: rx.home_care||'', medications_html: formatMedicationList(rx.medications_json),
      follow_up_date: rx.follow_up_date||'', follow_up_type: rx.follow_up_type||'',
      date: recent.uploaded_at||rx.created_at||null,
    };
  }

  function recomputeLastPostOp() {
    if (!state.selectedId) { state.lastPostOp = null; renderFollowupContext(); return; }
    const records = state.records.get(Number(state.selectedId)) || [];
    state.lastPostOp = extractLastPostOp(records, getSelectedPetId());
    renderFollowupContext();
  }

  function renderFollowupContext() {
    const { followupContextCard: card, followupContextBody: body, followupContextMeta: meta } = els;
    if (!card||!body||!meta) return;
    const cat = els.visitCategory?.value||'';
    if (!['followup','follow_up'].includes(cat) || !state.selectedId) { card.style.display='none'; return; }
    const ctx = state.lastPostOp;
    body.innerHTML = ''; meta.textContent = '';
    if (!ctx) { body.innerHTML = '<div class="pv-context-empty">No previous consultation found.</div>'; card.style.display='block'; return; }
    meta.textContent = ctx.date ? `Last record: ${formatDate(ctx.date,true)}` : 'Last record';
    const rows = [];
    if (ctx.visit_category||ctx.case_severity) rows.push({ label:'Visit', value:`${escapeHtml(ctx.visit_category||'-')}${ctx.case_severity?' • '+escapeHtml(ctx.case_severity):''}` });
    if (ctx.visit_notes)      rows.push({ label:'Visit notes',      value: escapeHtml(ctx.visit_notes)      });
    if (ctx.diagnosis||ctx.diagnosis_status) rows.push({ label:'Diagnosis', value:`${escapeHtml(ctx.diagnosis||'-')}${ctx.diagnosis_status?' ('+escapeHtml(ctx.diagnosis_status)+')':''}` });
    if (ctx.doctor_treatment) rows.push({ label:'Doctor treatment', value: escapeHtml(ctx.doctor_treatment) });
    if (ctx.treatment_plan)   rows.push({ label:'Treatment',        value: escapeHtml(ctx.treatment_plan)   });
    if (ctx.home_care)        rows.push({ label:'Home care',         value: escapeHtml(ctx.home_care)        });
    if (ctx.follow_up_date||ctx.follow_up_type) {
      const bits = []; if (ctx.follow_up_date) bits.push(`Date: ${escapeHtml(ctx.follow_up_date)}`); if (ctx.follow_up_type) bits.push(`Type: ${escapeHtml(ctx.follow_up_type)}`);
      rows.push({ label:'Follow-up', value: bits.join(' | ') });
    }
    if (ctx.medications_html) rows.push({ label:'Medicines', value: ctx.medications_html });
    if (!rows.length) { body.innerHTML='<div class="pv-context-empty">Latest record has no clinical details.</div>'; card.style.display='block'; return; }
    body.innerHTML = rows.map(r => `<div class="pv-context-row"><div class="pv-context-label">${r.label}</div><div class="pv-context-value">${r.value}</div></div>`).join('');
    card.style.display = 'block';
  }

  function buildRecordPreview(rec) {
    if (!rec?.url) return '';
    const url = escapeHtml(rec.url), fn = escapeHtml(rec.file_name||'Document'), mime = String(rec.mime_type||'').toLowerCase();
    if (mime.startsWith('image/'))         return `<div class="pm-record-media"><img src="${url}" alt="${fn}" loading="lazy"></div>`;
    if (mime==='application/pdf')          return `<div class="pm-record-media"><iframe src="${url}" title="${fn}"></iframe></div>`;
    return `<div class="pm-record-media"><a class="pm-record-link" href="${url}" target="_blank" rel="noopener">View document</a><div class="pm-record-file">${fn}</div></div>`;
  }

  async function request(url, options={}) {
    const headers = { Accept:'application/json', ...(options.headers||{}) };
    const res = await fetch(url, { ...options, headers });
    const text = await res.text();
    let data = null; try { data = text ? JSON.parse(text) : null; } catch(_) {}
    if (!res.ok) throw new Error(data?.error||data?.message||text||'Request failed');
    return data;
  }

  const getPrimaryPet    = p => (Array.isArray(p?.pets) && p.pets.length) ? p.pets[0] : null;
  const getPatientPets   = p => (Array.isArray(p?.pets) && p.pets.length) ? p.pets : [];
  const resolvePetId     = pet => { const raw = pet?.id ?? pet?.pet_id; const num = Number(raw); return Number.isFinite(num) && num > 0 ? num : null; };
  const formatPetLabel   = (pet, fallback = 'Pet') => { const name = pet?.name || pet?.pet_name || fallback; const id = resolvePetId(pet); return id ? `${name} #${id}` : name; };

  function getPetLabel(patient, petId) {
    if (!petId) return null;
    const match = getPatientPets(patient).find(pet => String(pet.id??pet.pet_id) === String(petId));
    return match ? formatPetLabel(match) : `Pet #${petId}`;
  }

  function getSelectedPetId() {
    const raw = els.recordPet?.value||''; if (!raw) return null;
    const n = Number(raw); return Number.isFinite(n) ? n : null;
  }

  function getSelectedPet() {
    const pid = getSelectedPetId(); if (!pid||!state.selectedId) return null;
    const patient = state.patients.find(p => Number(p.id)===Number(state.selectedId));
    return getPatientPets(patient).find(pet => Number(pet.id??pet.pet_id)===Number(pid)) || null;
  }

  function autofillVisitNotesFromSelectedPet({ onlyWhenEmpty=true }={}) {
    if (!els.recordNotes) return;
    const symptom  = String(getSelectedPet()?.reported_symptom??'').trim();
    const current  = String(els.recordNotes.value??'').trim();
    const previous = String(els.recordNotes.dataset.autofillSymptom??'').trim();
    if (!symptom) { if (previous && current===previous) { els.recordNotes.value=''; } delete els.recordNotes.dataset.autofillSymptom; return; }
    if (onlyWhenEmpty && current && current!==previous) return;
    els.recordNotes.value = symptom; els.recordNotes.dataset.autofillSymptom = symptom;
  }

  function applyFilters(list) {
    let f = list.slice();
    if (state.search) {
      f = f.filter(p => window.PatientStore.matchesSearch(p, state.search));
    }
    if (state.sort==='records')    f.sort((a,b)=>(b.records_count||0)-(a.records_count||0));
    else if (state.sort==='name')  f.sort((a,b)=>(a.name||'').localeCompare(b.name||''));
    else                            f.sort((a,b)=>new Date(b.last_record_at||b.updated_at||0)-new Date(a.last_record_at||a.updated_at||0));
    return f;
  }

  function renderPatientList() {
    if (!els.list) return;
    els.list.innerHTML = '';
    if (!CLINIC_ID)                { els.list.appendChild(createEmptyRow('Clinic ID missing.')); els.listCount.textContent=''; return; }
    if (state.loadingPatients)     { els.list.appendChild(createEmptyRow('Loading patients...')); els.listCount.textContent=''; return; }
    if (!state.patients.length)    { els.list.appendChild(createEmptyRow('No patients found for this clinic yet.')); els.listCount.textContent='0 patients'; return; }
    const list = applyFilters(state.patients);
    if (!list.length) { els.list.appendChild(createEmptyRow('No patients match your filters.')); els.listCount.textContent='0 patients'; return; }
    list.forEach(patient => {
      const row = document.createElement('div');
      row.className = 'pm-row' + (Number(patient.id)===Number(state.selectedId) ? ' is-active' : '');
      row.onclick = () => selectPatient(patient.id);
      const primaryPet = getPrimaryPet(patient);
      const avatar = document.createElement('div'); avatar.className='pm-avatar'; avatar.textContent=String(primaryPet?.name||patient.name||'?').charAt(0).toUpperCase();
      const info = document.createElement('div'); info.className='pm-info';
      const name  = document.createElement('div'); name.className='pm-name'; name.textContent=`${patient.name||'Patient'}  #${patient.id}`;
      const meta  = document.createElement('div'); meta.className='pm-meta';
      const petMeta = primaryPet ? `${escapeHtml(formatPetLabel(primaryPet))} | ${escapeHtml(primaryPet.breed||'Breed -')}` : 'No pets on file';
      meta.innerHTML = `${petMeta}<br>${escapeHtml(patient.phone||'Phone -')} | ${escapeHtml(patient.email||'Email -')}`;
      const badges = document.createElement('div'); badges.className='pm-badges';
      const recBadge = document.createElement('span'); recBadge.className='pm-badge'; recBadge.textContent=`${patient.records_count||0} file${(patient.records_count||0)===1?'':'s'}`;
      const lastBadge = document.createElement('span'); lastBadge.className='pm-badge'; lastBadge.textContent=patient.last_record_at?`Last: ${formatDate(patient.last_record_at,false)}`:'No uploads';
      badges.appendChild(recBadge); badges.appendChild(lastBadge);
      info.appendChild(name); info.appendChild(meta); info.appendChild(badges);
      row.appendChild(avatar); row.appendChild(info);
      els.list.appendChild(row);
    });
    const lc = document.getElementById('pm-list-count');
    if (lc) lc.textContent = `${list.length} patient${list.length===1?'':'s'}`;
  }

  function createEmptyRow(text) { const d=document.createElement('div'); d.className='pm-empty'; d.textContent=text; return d; }

  function updateDocumentUploadButton() {
    if (!els.documentUploadBtn) return;
    els.documentUploadBtn.disabled = !state.selectedId;
  }

  function resolveUploadPetId(patient) {
    if (!patient) return null;
    const pets = getPatientPets(patient);
    if (!pets.length) return null;
    const primary = getPrimaryPet(patient);
    return resolvePetId(primary) || resolvePetId(pets[0]) || null;
  }

  function triggerDocumentUploadPicker() {
    if (!state.selectedId) {
      Swal.fire({ icon:'info', title:'Select a patient', text:'Pick a patient before uploading a document.' });
      return;
    }
    els.documentUploadInput?.click();
  }

  async function uploadPatientDocument(file) {
    const patient = state.patients.find(p => Number(p.id) === Number(state.selectedId));
    if (!patient || !file) return;

    if (file.size > DOCUMENT_UPLOAD_MAX_BYTES) {
      Swal.fire({ icon:'warning', title:'File too large', text:'Maximum file size is 10 MB.' });
      return;
    }

    const petId = resolveUploadPetId(patient);
    const fd = new FormData();
    fd.append('file', file);
    fd.append('user_id', String(patient.id));
    if (petId) fd.append('pet_id', String(petId));
    fd.append('record_type', 'clinic_walkin');
    fd.append('record_label', (file.name || 'Uploaded document').slice(0, 150));
    fd.append('source', 'clinic_walkins');
    fd.append('file_count', '1');

    const prevLabel = els.documentUploadBtn?.textContent;
    if (els.documentUploadBtn) {
      els.documentUploadBtn.disabled = true;
      els.documentUploadBtn.textContent = 'Uploading...';
    }

    try {
      const res = await fetch(DOCUMENT_UPLOAD_POST_URL, {
        method: 'POST',
        body: fd,
        credentials: 'include',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const text = await res.text();
      let data = null;
      try { data = text ? JSON.parse(text) : null; } catch (_) {}
      if (!res.ok) {
        throw new Error(data?.message || data?.error || text || 'Upload failed');
      }
      Swal.fire({
        icon: 'success',
        title: 'Document uploaded',
        text: data?.data?.file_name || file.name || 'Saved to database',
        timer: 1800,
        showConfirmButton: false,
      });
    } catch (error) {
      Swal.fire({ icon:'error', title:'Upload failed', text: error.message || 'Could not upload document' });
    } finally {
      if (els.documentUploadBtn) {
        els.documentUploadBtn.textContent = prevLabel || 'Upload Document';
        updateDocumentUploadButton();
      }
      if (els.documentUploadInput) els.documentUploadInput.value = '';
    }
  }

  function renderProfile() {
    const patient = state.patients.find(p => Number(p.id)===Number(state.selectedId));
    if (!patient) {
      els.profileAvatar.textContent='CW'; els.profileName.textContent='Select a patient';
      els.profileSub.textContent='Patient and pet details will appear here.'; els.profileMeta.textContent='';
      els.statRecords.textContent='-'; els.statLastRecord.textContent='Last upload -';
      els.statContact.textContent='-'; els.statEmail.textContent='-'; els.recordCount.textContent='-';
      renderPetSelect(null); renderRecords(); renderPets(); updateDocumentUploadButton(); return;
    }
    const primaryPet = getPrimaryPet(patient);
    els.profileAvatar.textContent = String(primaryPet?.name||patient.name||'?').charAt(0).toUpperCase();
    els.profileName.textContent   = `${patient.name||'Patient'} #${patient.id}`;
    if (primaryPet) {
      const petAge = primaryPet.pet_age??primaryPet.age;
      els.profileSub.textContent = `${formatPetLabel(primaryPet)} | ${primaryPet.breed||'Breed -'} | ${primaryPet.gender||primaryPet.pet_gender||'Gender -'} | Age: ${(petAge||petAge===0)?petAge:'-'}`;
    } else { els.profileSub.textContent = 'No pets on file.'; }
    els.profileMeta.textContent = `Phone: ${patient.phone||'-'} | Email: ${patient.email||'-'}`;
    const cached = state.records.get(Number(patient.id));
    const total  = Array.isArray(cached) ? cached.length : (patient.records_count||0);
    els.statRecords.textContent   = `${total}`;
    els.statLastRecord.textContent = (Array.isArray(cached)&&cached.length&&cached[0]?.uploaded_at) ? `Last upload ${formatDate(cached[0].uploaded_at)}` : (patient.last_record_at ? `Last upload ${formatDate(patient.last_record_at)}` : 'No uploads yet');
    els.statContact.textContent   = patient.phone||'-';
    els.statEmail.textContent     = patient.email||'-';
    renderPetSelect(patient, getPrimaryPet(patient)?.id);
    renderPets(patient);
    renderRecords();
    updateDocumentUploadButton();
  }

  function renderPets(patient=null) {
    if (!els.petList||!els.petEmpty) return;
    els.petList.innerHTML = '';
    if (!patient) { els.petEmpty.textContent='Select a patient to see pets.'; els.petEmpty.style.display='block'; if (els.petCount) els.petCount.textContent='-'; return; }
    const pets = Array.isArray(patient.pets) ? patient.pets : [];
    if (!pets.length) { els.petEmpty.textContent='No pets yet for this patient.'; els.petEmpty.style.display='block'; if (els.petCount) els.petCount.textContent='0 pets'; return; }
    els.petEmpty.style.display='none';
    pets.forEach(pet => {
      const wrap=document.createElement('div'); wrap.className='pm-petRow';
      const av=document.createElement('div'); av.className='pm-petAvatar'; av.textContent=String(pet.name||pet.pet_name||'P').charAt(0).toUpperCase();
      const body=document.createElement('div'); body.className='pm-petBody';
      const title=document.createElement('div'); title.className='pm-petName'; title.textContent=formatPetLabel(pet);
      const meta=document.createElement('div'); meta.className='pm-petMeta';
      const mp=[]; if(pet.type) mp.push(pet.type); if(pet.breed) mp.push(pet.breed); const g=pet.gender||pet.pet_gender; if(g) mp.push(`Gender: ${g}`); const a=pet.pet_age??pet.age; if(a||a===0) mp.push(`Age: ${a}`);
      meta.textContent=mp.join(' | ')||'Details not provided';
      body.appendChild(title); body.appendChild(meta); wrap.appendChild(av); wrap.appendChild(body);
      const petId = Number(pet.id??pet.pet_id);
      if (Number.isFinite(petId)) {
        const actions=document.createElement('div'); actions.className='pm-petActions';
        const del=document.createElement('button'); del.type='button'; del.className='pm-petDelete'; del.textContent='Delete'; del.setAttribute('aria-label',`Delete ${pet.name||pet.pet_name||'pet'}`);
        del.addEventListener('click', ev => { ev.stopPropagation(); handlePetDelete({...pet,id:petId},patient); });
        actions.appendChild(del); wrap.appendChild(actions);
      }
      els.petList.appendChild(wrap);
    });
    if (els.petCount) els.petCount.textContent=`${pets.length} pet${pets.length===1?'':'s'}`;
  }

  async function handlePetDelete(pet, patient) {
    if (!pet?.id) return;
    const result = await Swal.fire({ icon:'warning', title:`Delete ${pet.name||pet.pet_name||`Pet #${pet.id}`}?`, text:`This will remove this pet from ${patient?.name||'this patient'}.`, showCancelButton:true, confirmButtonText:'Delete', confirmButtonColor:'#ef4444' });
    if (!result.isConfirmed) return;
    try {
      await request(`${API_BASE}/pets/${pet.id}`, { method:'DELETE' });
      Swal.fire({ icon:'success', title:'Pet deleted', timer:1500, showConfirmButton:false });
      state.selectedId = Number(patient?.id??state.selectedId);
      await window.PatientStore.reload();
    } catch (error) {
      Swal.fire({ icon:'error', title:'Delete failed', text:error.message||'Request failed' });
    }
  }

  function renderRecords() {
    if (!els.recordList||!els.recordEmpty) return;
    els.recordList.innerHTML = '';
    const activePatient = state.patients.find(p => Number(p.id)===Number(state.selectedId));
    if (!state.selectedId)     { els.recordEmpty.textContent='Select a patient to see uploaded files.'; els.recordEmpty.style.display='block'; els.recordEmpty.style.color='var(--pm-muted)'; els.recordCount.textContent='-'; if (els.statRecords) els.statRecords.textContent='-'; return; }
    if (state.loadingRecords)  { els.recordEmpty.textContent='Loading records...'; els.recordEmpty.style.display='block'; els.recordEmpty.style.color='var(--pm-muted)'; els.recordCount.textContent='-'; return; }
    if (lastRecordError)       { els.recordEmpty.textContent=lastRecordError; els.recordEmpty.style.display='block'; els.recordEmpty.style.color='#b91c1c'; els.recordCount.textContent='-'; return; }
    const records = state.records.get(Number(state.selectedId)) || [];
    if (!records.length) { els.recordEmpty.textContent='No medical records uploaded yet.'; els.recordEmpty.style.display='block'; els.recordEmpty.style.color='var(--pm-muted)'; els.recordCount.textContent='0 files'; if (els.statRecords) els.statRecords.textContent='0'; return; }
    els.recordEmpty.style.display='none';
    records.forEach(rec => {
      const wrap = document.createElement('div'); wrap.className = 'pm-record pm-record-card';
      const rx = rec.prescription||{};
      const petId = rec.pet_id??rx.pet_id??null;
      const petLabel = activePatient ? getPetLabel(activePatient,petId) : null;
      const detailPairs = [];
      if (rx.visit_category||rx.case_severity) detailPairs.push({label:'Visit',value:`${escapeHtml(rx.visit_category||'-')} | ${escapeHtml(rx.case_severity||'-')}`});
      if (rx.visit_notes)    detailPairs.push({label:'Visit notes',   value:escapeHtml(rx.visit_notes)});
      if (rx.temperature||rx.weight||rx.heart_rate) {
        const vs=[rx.temperature?`Temp: ${escapeHtml(rx.temperature)}°`:null, rx.weight?`Weight: ${escapeHtml(rx.weight)}kg`:null, rx.heart_rate?`Heart: ${escapeHtml(rx.heart_rate)}`:null].filter(Boolean);
        detailPairs.push({label:'Vitals',value:vs.join(' | ')});
      }
      if (rx.exam_notes)     detailPairs.push({label:'Exam',          value:escapeHtml(rx.exam_notes)});
      if (rx.diagnosis||rx.diagnosis_status) detailPairs.push({label:'Diagnosis',value:`${escapeHtml(rx.diagnosis||'-')} (${escapeHtml(rx.diagnosis_status||'-')})`});
      if (rx.doctor_treatment) detailPairs.push({label:'Doctor treatment',value:escapeHtml(rx.doctor_treatment)});
      if (rx.treatment_plan) detailPairs.push({label:'Treatment',     value:escapeHtml(rx.treatment_plan)});
      if (rx.home_care)      detailPairs.push({label:'Home care',      value:escapeHtml(rx.home_care)});
      const medsHtml = formatMedicationList(rx.medications_json);
      if (medsHtml)          detailPairs.push({label:'Medicines',      value:medsHtml});
      if (rx.follow_up_date||rx.follow_up_type) {
        const fp=[]; if(rx.follow_up_date) fp.push(`Date: ${escapeHtml(rx.follow_up_date)}`); if(rx.follow_up_type) fp.push(`Type: ${escapeHtml(rx.follow_up_type)}`);
        detailPairs.push({label:'Follow-up',value:fp.join(' | ')});
      }
      if (rx.visit_category === 'vaccination') {
        if (rx.vaccination_name) detailPairs.push({label:'Vaccine',      value:escapeHtml(rx.vaccination_name)});
        if (rx.vaccination_date) detailPairs.push({label:'Vacc. date',   value:escapeHtml(rx.vaccination_date)});
        if (rx.batch_number)     detailPairs.push({label:'Batch no.',    value:escapeHtml(rx.batch_number)});
      }
      if (petLabel) detailPairs.unshift({label:'Pet',value:escapeHtml(petLabel)});
      const detailHtml  = detailPairs.length ? `<div class="pm-record-details">${detailPairs.map(p=>`<div class="pm-record-row"><div class="pm-record-label">${p.label}</div><div class="pm-record-value">${p.value}</div></div>`).join('')}</div>` : '';
      const tags = [rx.case_severity?`<span class="pm-tag-soft">${escapeHtml(rx.case_severity)}</span>`:'', rx.visit_category?`<span class="pm-tag-soft">${escapeHtml(rx.visit_category)}</span>`:''].filter(Boolean);
      wrap.innerHTML = `
        <div class="pm-record-head">
          <div>
            <div class="pm-record-title">${escapeHtml(rec.file_name||'Medical file')}</div>
            <div class="pm-record-meta">${formatDate(rec.uploaded_at)}${rec.doctor_id?` | Doctor #${rec.doctor_id}`:''}${petLabel?` | Pet: ${escapeHtml(petLabel)}`:''}</div>
          </div>
          ${tags.length?`<div class="pm-record-tags">${tags.join('')}</div>`:''}
        </div>
        <div class="pm-record-notes">${escapeHtml(rec.notes||'No notes')}</div>
        ${detailHtml}
        ${buildRecordPreview(rec)}
        <div class="pm-record-actions">
          <button type="button" class="pm-btn pm-primary" data-role="edit-record" data-id="${rec.id}" style="padding:6px 12px">Edit</button>
          <a href="${rec.url}" target="_blank" rel="noopener" class="pm-btn pm-ghost" style="padding:6px 10px">Download</a>
        </div>`;
      wrap.querySelector('[data-role="edit-record"]')?.addEventListener('click', () => { selectPatient(rec.user_id); openUploadModal(); fillRecordFormFromRecord(rec); });
      els.recordList.appendChild(wrap);
    });
    els.recordCount.textContent = `${records.length} file${records.length===1?'':'s'}`;
    if (els.statRecords) els.statRecords.textContent = `${records.length}`;
  }

  function renderPetSelect(patient, selectedPetId=null) {
    if (!els.recordPet) return;
    const pets = getPatientPets(patient);
    els.recordPet.innerHTML = '';
    const ph = document.createElement('option'); ph.value=''; ph.textContent=pets.length?'Select pet':'No pets found'; els.recordPet.appendChild(ph);
    pets.forEach(pet => {
      const v = pet.id??pet.pet_id, nv = Number(v), hasId = v!==null&&v!==undefined&&v!==''&&Number.isFinite(nv);
      const opt = document.createElement('option'); opt.value = hasId?v:'';
      opt.textContent = `${formatPetLabel(pet)}${pet.breed?` | ${pet.breed}`:''}${pet.gender?` | ${pet.gender}`:''}`;
      opt.dataset.petName = pet.name||pet.pet_name||''; if (!hasId) { opt.disabled=true; opt.textContent+=' (link pet to use)'; }
      els.recordPet.appendChild(opt);
    });
    if (selectedPetId) { els.recordPet.value = String(selectedPetId); }
    else if (pets.length===1) { const ov=pets[0].id??pets[0].pet_id; if (ov!==null&&ov!==undefined&&ov!==''&&Number.isFinite(Number(ov))) els.recordPet.value=String(ov); }
    else { els.recordPet.value=''; }
  }

  async function loadDoctors() {
    if (!CLINIC_ID||!els.doctorSelect) return;
    try {
      const data = await request(`${API_BASE}/clinics/${CLINIC_ID}/doctors`);
      const doctors = Array.isArray(data?.doctors) ? data.doctors : [];
      els.doctorSelect.innerHTML='<option value="">Select doctor</option>';
      doctors.forEach(doc => { const o=document.createElement('option'); o.value=doc.id; o.textContent=doc.name||doc.doctor_name||`Doctor #${doc.id}`; els.doctorSelect.appendChild(o); });
      if (DEFAULT_DOCTOR_ID) els.doctorSelect.value=DEFAULT_DOCTOR_ID;
    } catch (e) { console.error('loadDoctors',e); }
  }

  async function loadRecords(patientId) {
    if (!CLINIC_ID||!patientId) { renderRecords(); return; }
    state.loadingRecords=true; lastRecordError=null; renderRecords();
    try {
      const data = await request(`${API_BASE}/users/${patientId}/medical-records?clinic_id=${CLINIC_ID}`);
      const records = Array.isArray(data?.data?.records) ? data.data.records : [];
      state.records.set(Number(patientId), records);
      recomputeLastPostOp();
    } catch (error) { lastRecordError=escapeHtml(error.message); state.records.set(Number(patientId),[]); state.lastPostOp=null; }
    finally { state.loadingRecords=false; renderRecords(); renderFollowupContext(); }
  }

  function selectPatient(patientId) {
    state.selectedId=Number(patientId); state.lastPostOp=null; state.loadingRecords=true; lastRecordError=null;
    renderPatientList(); renderProfile(); renderFollowupContext(); loadRecords(patientId);
  }

  function resetRecordForm() {
    state.editingRecordId=null; els.recordForm?.reset();
    if (els.recordNotes) delete els.recordNotes.dataset.autofillSymptom;
    if (els.caseSeverity) els.caseSeverity.value='general';
    if (els.visitCategory) els.visitCategory.value='';
    resetMedications(); toggleCriticalSections(); updateVisitCategoryUI(els.visitCategory?.value||'');
    const ri=document.getElementById('record-id'); if(ri) ri.value='';
    const rf=document.getElementById('record-file'); if(rf) rf.required=false;
    const vn=document.getElementById('vaccination-name'); if(vn) vn.value='';
    const bn=document.getElementById('batch-number'); if(bn) bn.value='';
    const vd=document.getElementById('vaccination-date'); if(vd) vd.value='';
    const ds=document.getElementById('deworming-status'); if(ds) ds.value='no';
    const ld=document.getElementById('last-deworming-date'); if(ld) ld.value='';
    const vcf=document.getElementById('vaccination-certificate-file'); if(vcf) vcf.value='';
    const vcj=document.getElementById('vaccination-certificate-json'); if(vcj) vcj.value='';
    const vct=document.getElementById('vaccination-certificate-status-text'); if(vct) vct.textContent='Upload certificate (Gemini Auto-Parse)';
    const vci=document.getElementById('vaccination-certificate-status-icon'); if(vci) vci.textContent='📎';
    if (typeof loadVaccineList === 'function') loadVaccineList(null);
  }

  function fillRecordFormFromRecord(rec) {
    if (!rec) return;
    const vcf=document.getElementById('vaccination-certificate-file'); if(vcf) vcf.value='';
    const vcj=document.getElementById('vaccination-certificate-json'); if(vcj) vcj.value='';
    const vct=document.getElementById('vaccination-certificate-status-text'); if(vct) vct.textContent='Upload certificate (Gemini Auto-Parse)';
    const vci=document.getElementById('vaccination-certificate-status-icon'); if(vci) vci.textContent='📎';
    if (typeof loadVaccineList === 'function') loadVaccineList(null);
    const rx = rec.prescription||{};
    state.editingRecordId = rec.id;
    const ri=document.getElementById('record-id'); if(ri) ri.value=rec.id;
    const ru=document.getElementById('record-user-id'); if(ru) ru.value=rec.user_id;
    const rf=document.getElementById('record-file'); if(rf) rf.required=false;
    const mv = (id,v) => { const el=document.getElementById(id); if(el) el.value=v??''; };
    mv('record-notes',     rec.notes??rx.visit_notes??'');
    if (els.recordNotes) delete els.recordNotes.dataset.autofillSymptom;
    mv('visit-category',   normalizeVisitCategoryValue(rx.visit_category));
    mv('case-severity',    rx.case_severity??'');
    mv('doctor-select',    rec.doctor_id??rx.doctor_id??DEFAULT_DOCTOR_ID??'');
    mv('temperature',      rx.temperature??''); mv('weight',rx.weight??''); mv('heart-rate',rx.heart_rate??'');
    mv('exam-notes',       rx.exam_notes??''); mv('diagnosis',rx.diagnosis??''); mv('diagnosis-status',rx.diagnosis_status??'');
    mv('doctor-treatment', rx.doctor_treatment??''); mv('treatment-plan',rx.treatment_plan??'');
    mv('home-care',        rx.home_care??''); mv('follow-up-date',rx.follow_up_date??''); mv('follow-up-type',rx.follow_up_type??'');
    mv('record-pet',       rx.pet_id??rec.pet_id??'');
    mv('vaccination-name', rx.vaccination_name??'');
    mv('batch-number',     rx.batch_number??'');
    mv('vaccination-date', rx.vaccination_date??'');
    mv('deworming-status',  rx.deworming??'no');
    mv('last-deworming-date', rx.last_deworming_date??'');
    updateVisitCategoryUI(els.visitCategory?.value||''); recomputeLastPostOp();
    medications = normalizeMedicationState(rx.medications_json||[]); renderMedicationCards(); syncMedicationPayload();
    if (!medications.length) addMedication();
    toggleCriticalSections(); updateVisitCategoryUI(els.visitCategory?.value||'');
  }

  function openUploadModal(forceFollowup=false) {
    if (!state.selectedId) { Swal.fire({icon:'info',title:'Select a patient',text:'Pick a patient from the list before uploading.'}); return; }
    const patient = state.patients.find(p => Number(p.id)===Number(state.selectedId));
    if (patient) {
      els.modalPatient.textContent = `${patient.name||'Patient'} | #${patient.id}`;
      const pp = getPrimaryPet(patient);
      const pa = pp?.pet_age??pp?.age;
      if (els.modalPet) els.modalPet.textContent = pp ? [formatPetLabel(pp),pp.breed,(pa||pa===0)?`Age: ${pa}`:null].filter(Boolean).join(' | ') : '';
      els.modalUserInput.value = patient.id;
    }
    resetRecordForm();
    if (forceFollowup && els.visitCategory) { els.visitCategory.value='followup'; updateVisitCategoryUI('followup'); }
    if (!medications.length) addMedication();
    renderPetSelect(patient); autofillVisitNotesFromSelectedPet({onlyWhenEmpty:true}); recomputeLastPostOp();
    if (els.doctorSelect && DEFAULT_DOCTOR_ID) els.doctorSelect.value=DEFAULT_DOCTOR_ID;
    els.modal.classList.add('is-visible');
  }

  function closeModal() {
    els.modal.classList.remove('is-visible'); resetRecordForm();
    if (els.doctorSelect) els.doctorSelect.value=DEFAULT_DOCTOR_ID||'';
    if (els.modalPet) els.modalPet.textContent='';
  }

  function openPetModal() {
    if (!state.selectedId) { Swal.fire({icon:'info',title:'Select a patient',text:'Pick a patient before adding a pet.'}); return; }
    if (els.petForm) els.petForm.reset();
    const patient = state.patients.find(p => Number(p.id)===Number(state.selectedId));
    if (patient) { if (els.petPatient) els.petPatient.textContent=`${patient.name||'Patient'} | #${patient.id}`; if (els.petUserInput) els.petUserInput.value=patient.id; }
    els.petModal?.classList.add('is-visible');
  }
  function closePetModal() { els.petModal?.classList.remove('is-visible'); if (els.petPatient) els.petPatient.textContent='Patient | -'; if (els.petForm) els.petForm.reset(); }

  // --- VACCINE CERTIFICATE EDITOR MODULE ---
  function serializeVaccineList() {
    const listContainer = document.getElementById('vaccination-editor-list');
    const jsonTextarea = document.getElementById('vaccination-certificate-json');
    if (!listContainer || !jsonTextarea) return;

    const result = {
      vaccination: {}
    };

    const rows = listContainer.querySelectorAll('.vaccine-row');

    rows.forEach(row => {
      const nameInput = row.querySelector('.vaccine-name');
      const dateInput = row.querySelector('.vaccine-date');
      const nextDueInput = row.querySelector('.vaccine-next-due');
      const batchNoInput = row.querySelector('.vaccine-batch-no');

      let name = nameInput.value.trim();
      if (!name) return;

      let standardizedSlug = name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
      if (!standardizedSlug) return;

      const dateVal = dateInput.value;
      const nextDueVal = nextDueInput.value;
      const batchNoVal = batchNoInput ? batchNoInput.value.trim() : '';

      const vaccineData = {
        date: dateVal || new Date().toISOString().split('T')[0]
      };

      if (nextDueVal) {
        vaccineData.next_due = nextDueVal;
      }
      if (batchNoVal) {
        vaccineData.batch_no = batchNoVal;
      }

      if (!result.vaccination[standardizedSlug]) {
        result.vaccination[standardizedSlug] = [];
      }
      result.vaccination[standardizedSlug].push(vaccineData);
    });

    jsonTextarea.value = rows.length > 0 ? JSON.stringify(result, null, 2) : '';
  }

  function renderVaccineRow(slug = '', date = '', nextDue = '', batchNo = '') {
    const listContainer = document.getElementById('vaccination-editor-list');
    const emptyState = document.getElementById('vaccination-editor-empty');
    if (!listContainer) return;

    if (emptyState) emptyState.style.display = 'none';

    const row = document.createElement('div');
    row.className = 'vaccine-row';
    row.style.cssText = 'display:grid; grid-template-columns:1.2fr 1fr 1fr 1fr auto; gap:12px; align-items:flex-end; background:#fff; padding:14px; border:1.5px solid #cbd5e1; border-radius:12px; box-shadow:0 2px 4px rgba(0,0,0,0.02); margin-bottom:10px;';

    let displayName = slug;
    const lowerSlug = slug.toLowerCase();
    if (lowerSlug === 'dhppil') displayName = 'DHPPiL';
    else if (lowerSlug === 'rabies') displayName = 'Rabies';
    else if (lowerSlug === 'canine_coronavirus') displayName = 'Canine Coronavirus';
    else if (lowerSlug === 'nobivac_kc') displayName = 'Nobivac KC';
    else if (lowerSlug === 'dhppi') displayName = 'DHPPi';
    else if (lowerSlug === 'leptospirosis') displayName = 'Leptospirosis';
    else if (lowerSlug === 'fvrcp') displayName = 'FVRCP';
    else if (lowerSlug === 'felv') displayName = 'FeLV';

    row.innerHTML = `
      <div class="pv-field" style="margin:0;">
        <label class="pv-label" style="font-size:12px; font-weight:700; color:#475569;">Vaccination Name</label>
        <input type="text" class="vaccine-name pv-input" value="${displayName}" placeholder="e.g. DHPPiL" style="border:1.5px solid #000; border-radius:10px; padding:8px 10px; font-size:13px; width:100%; height:auto;">
      </div>
      <div class="pv-field" style="margin:0;">
        <label class="pv-label" style="font-size:12px; font-weight:700; color:#475569;">Date Administered</label>
        <input type="date" class="vaccine-date pv-input" value="${date}" style="border:1.5px solid #000; border-radius:10px; padding:8px 10px; font-size:13px; width:100%; height:auto;">
      </div>
      <div class="pv-field" style="margin:0;">
        <label class="pv-label" style="font-size:12px; font-weight:700; color:#475569;">Next Due Date</label>
        <input type="date" class="vaccine-next-due pv-input" value="${nextDue}" style="border:1.5px solid #000; border-radius:10px; padding:8px 10px; font-size:13px; width:100%; height:auto;">
      </div>
      <div class="pv-field" style="margin:0;">
        <label class="pv-label" style="font-size:12px; font-weight:700; color:#475569;">Batch Number</label>
        <input type="text" class="vaccine-batch-no pv-input" value="${batchNo}" placeholder="e.g. VAC123" style="border:1.5px solid #000; border-radius:10px; padding:8px 10px; font-size:13px; width:100%; height:auto;">
      </div>
      <button type="button" class="vaccine-delete-btn" style="border:none; background:none; cursor:pointer; font-size:18px; padding:8px; color:#ef4444; border-radius:10px; transition:background 0.12s; height:38px; width:38px; display:flex; align-items:center; justify-content:center; margin-bottom:2px;" title="Delete vaccine entry">
        🗑️
      </button>
    `;

    const nameInput = row.querySelector('.vaccine-name');
    const dateInput = row.querySelector('.vaccine-date');
    const nextDueInput = row.querySelector('.vaccine-next-due');
    const batchNoInput = row.querySelector('.vaccine-batch-no');
    const deleteBtn = row.querySelector('.vaccine-delete-btn');

    nameInput.addEventListener('input', serializeVaccineList);
    dateInput.addEventListener('change', serializeVaccineList);
    nextDueInput.addEventListener('change', serializeVaccineList);
    batchNoInput.addEventListener('input', serializeVaccineList);

    deleteBtn.addEventListener('click', () => {
      row.remove();
      if (listContainer.children.length === 0) {
        if (emptyState) emptyState.style.display = 'block';
      }
      serializeVaccineList();
    });

    deleteBtn.addEventListener('mouseenter', () => deleteBtn.style.background = '#fee2e2');
    deleteBtn.addEventListener('mouseleave', () => deleteBtn.style.background = 'none');

    listContainer.appendChild(row);
  }

  function loadVaccineList(data) {
    const listContainer = document.getElementById('vaccination-editor-list');
    const emptyState = document.getElementById('vaccination-editor-empty');
    if (!listContainer) return;

    listContainer.innerHTML = '';

    let parsedData = null;
    if (typeof data === 'string') {
      try {
        parsedData = JSON.parse(data);
      } catch (_) {}
    } else if (data && typeof data === 'object') {
      parsedData = data;
    }

    const vaccinations = parsedData?.vaccination || {};
    const keys = Object.keys(vaccinations);

    if (keys.length === 0) {
      if (emptyState) emptyState.style.display = 'block';
    } else {
      if (emptyState) emptyState.style.display = 'none';
      keys.forEach(slug => {
        const item = vaccinations[slug];
        if (Array.isArray(item)) {
          item.forEach(subItem => {
            renderVaccineRow(slug, subItem?.date || '', subItem?.next_due || '', subItem?.batch_no || subItem?.batch_number || '');
          });
        } else if (item && typeof item === 'object') {
          renderVaccineRow(slug, item.date || '', item.next_due || '', item.batch_no || item.batch_number || '');
        }
      });
    }

    serializeVaccineList();
  }

  function initVaccineEditor() {
    const addBtn = document.getElementById('vaccination-editor-add-btn');
    if (addBtn) {
      addBtn.addEventListener('click', () => {
        renderVaccineRow('', '', '');
        serializeVaccineList();
      });
    }
  }

  function wireEvents() {
    els.search?.addEventListener('input', e => { state.search=e.target.value||''; renderPatientList(); });
    els.sort?.addEventListener('change', e => { state.sort=e.target.value; renderPatientList(); });
    els.openUploadBtns.forEach(btn => btn.addEventListener('click', ev => openUploadModal(ev.currentTarget?.dataset?.followup==='1')));
    els.openPetBtns.forEach(btn => btn.addEventListener('click', openPetModal));
    els.documentUploadBtn?.addEventListener('click', triggerDocumentUploadPicker);
    els.documentUploadInput?.addEventListener('change', ev => {
      const file = ev.target?.files?.[0];
      if (file) uploadPatientDocument(file);
    });
    document.querySelectorAll('[data-role="close-record-modal"]').forEach(btn => btn.addEventListener('click', closeModal));
    document.querySelectorAll('[data-role="close-pet-modal"]').forEach(btn => btn.addEventListener('click', closePetModal));
    els.caseSeverity?.addEventListener('change', () => toggleCriticalSections());
    els.addMedicineBtn?.addEventListener('click', () => addMedication());
    els.visitCategory?.addEventListener('change', e => updateVisitCategoryUI(e.target.value));
    document.getElementById('deworming-status')?.addEventListener('change', () => updateDewormingDateUI());
    els.recordPet?.addEventListener('change', () => { recomputeLastPostOp(); autofillVisitNotesFromSelectedPet({onlyWhenEmpty:true}); });
    els.recordNotes?.addEventListener('input', () => {
      const auto = String(els.recordNotes.dataset.autofillSymptom??'').trim();
      if (auto && String(els.recordNotes.value??'').trim()!==auto) delete els.recordNotes.dataset.autofillSymptom;
    });

    const certInput = document.getElementById('vaccination-certificate-file');
    certInput?.addEventListener('change', async ev => {
      const file = ev.target?.files?.[0];
      if (!file) return;

      const statusIcon = document.getElementById('vaccination-certificate-status-icon');
      const statusText = document.getElementById('vaccination-certificate-status-text');
      const jsonTextarea = document.getElementById('vaccination-certificate-json');

      if (statusIcon) statusIcon.textContent = '⏳';
      if (statusText) statusText.textContent = 'Analyzing with Gemini 2.5 Flash...';

      const emptyState = document.getElementById('vaccination-editor-empty');
      const loadingState = document.getElementById('vaccination-editor-loading');
      const listContainer = document.getElementById('vaccination-editor-list');
      const addBtn = document.getElementById('vaccination-editor-add-btn');

      if (emptyState) emptyState.style.display = 'none';
      if (listContainer) listContainer.style.display = 'none';
      if (loadingState) loadingState.style.display = 'block';
      if (addBtn) addBtn.disabled = true;

      const fd = new FormData();
      fd.append('document', file);

      try {
        const res = await request(`${API_BASE}/medical-records/parse-vaccination-certificate`, {
          method: 'POST',
          body: fd
        });

        if (res && res.success && res.data) {
          if (jsonTextarea) {
            jsonTextarea.value = JSON.stringify(res.data, null, 2);
          }
          loadVaccineList(res.data);
          if (statusIcon) statusIcon.textContent = '✅';
          if (statusText) statusText.textContent = 'Parsed successfully!';
          Swal.fire({
            icon: 'success',
            title: 'Certificate Parsed',
            text: 'Vaccination details extracted successfully. Please verify the data below.',
            timer: 2000,
            showConfirmButton: false
          });
        } else {
          throw new Error(res?.error || 'Failed to parse certificate.');
        }
      } catch (err) {
        if (statusIcon) statusIcon.textContent = '❌';
        if (statusText) statusText.textContent = 'Upload failed. Try again.';
        Swal.fire({
          icon: 'error',
          title: 'Parsing Failed',
          text: err.message || 'Could not parse vaccination certificate.'
        });
      } finally {
        if (loadingState) loadingState.style.display = 'none';
        if (listContainer) listContainer.style.display = 'flex';
        if (addBtn) addBtn.disabled = false;
        if (listContainer && listContainer.children.length === 0) {
          if (emptyState) emptyState.style.display = 'block';
        }
      }
    });

    els.recordForm?.addEventListener('submit', async ev => {
      ev.preventDefault();
      if (!CLINIC_ID) { Swal.fire({icon:'error',title:'Clinic missing',text:'Clinic ID not detected. Reload dashboard.'}); return; }
      const patientId = els.modalUserInput.value;
      if (!patientId) { Swal.fire({icon:'error',title:'Patient missing',text:'Select a patient before uploading.'}); return; }
      if (els.visitCategory?.value === 'deworming') {
        const dStatus = document.getElementById('deworming-status')?.value;
        const dDate = document.getElementById('last-deworming-date')?.value;
        if (dStatus === 'yes' && !dDate) {
          Swal.fire({icon:'warning',title:'Missing Date',text:'Please enter the last deworming date.'});
          return;
        }
      }
      syncMedicationPayload();
      const fd = new FormData(els.recordForm);
      const vcf = fd.get('vaccination_certificate_file');
      if (!(vcf instanceof File) || !vcf.size) {
        fd.delete('vaccination_certificate_file');
      }
      fd.append('clinic_id', CLINIC_ID);
      if (!fd.get('doctor_id')) fd.delete('doctor_id');
      if (!fd.get('pet_id'))    fd.delete('pet_id');
      const homeCare = (fd.get('home_care') || '').toString().trim();
      if (!homeCare) fd.delete('home_care');
      else fd.set('home_care', homeCare);
      let url = `${API_BASE}/medical-records`;
      if (state.editingRecordId || fd.get('record_id')) {
        const recId = state.editingRecordId||fd.get('record_id'); fd.append('_method','PUT'); url=`${API_BASE}/medical-records/${recId}`;
        const f=fd.get('record_file'); if(!(f instanceof File)||!f?.size) fd.delete('record_file');
      }
      try {
        await request(url, { method:'POST', body:fd });
        Swal.fire({icon:'success',title:state.editingRecordId?'Updated':'Uploaded',timer:1500,showConfirmButton:false});
        closeModal();
        await window.PatientStore.reload();
        if (state.selectedId===Number(patientId)) await loadRecords(patientId);
      } catch (error) { Swal.fire({icon:'error',title:'Upload failed',text:error.message||'Could not upload file'}); }
    });

    els.petForm?.addEventListener('submit', async ev => {
      ev.preventDefault();
      const patientId = state.selectedId||els.petUserInput?.value;
      if (!patientId) { Swal.fire({icon:'info',title:'Select a patient'}); return; }
      const fd = new FormData(els.petForm);
      const name=( fd.get('name')||'').trim(), breed=(fd.get('breed')||'').trim(), gender=(fd.get('pet_gender')||'').trim(), ageRaw=fd.get('pet_age'), age=Number(ageRaw);
      if (!name||!breed||!gender||ageRaw===null||ageRaw===undefined||ageRaw==='') { Swal.fire({icon:'warning',title:'Missing details',text:'Name, breed, age and gender are required.'}); return; }
      if (isNaN(age)||age<0) { Swal.fire({icon:'warning',title:'Check age',text:'Please enter a valid age (0 or higher).'}); return; }
      fd.set('name',name); fd.set('breed',breed); fd.set('pet_gender',gender); fd.set('pet_age',String(age)); fd.set('user_id',patientId);
      ['microchip_number','mcd_registration_number'].forEach(k => { const v=(fd.get(k)||'').toString().trim(); if(v) fd.set(k,v); else fd.delete(k); });
      try {
        await request(`${API_BASE}/users/${patientId}/pets`, { method:'POST', body:fd });
        Swal.fire({icon:'success',title:'Pet added',timer:1500,showConfirmButton:false});
        closePetModal();
        await window.PatientStore.reload();
        selectPatient(patientId);
      } catch (error) { Swal.fire({icon:'error',title:'Could not add pet',text:error.message||'Request failed'}); }
    });

    initVaccineEditor();
  }

  // Subscribe to PatientStore — keeps walk-in list in sync
  window.PatientStore.subscribe(({ patients, loading, error }) => {
    state.patients       = patients;
    state.loadingPatients = loading;
    const exists = state.selectedId && patients.some(p => Number(p.id)===Number(state.selectedId));
    if (!exists) state.selectedId = null;
    renderPatientList();
    renderProfile();
  });

  renderPatientList(); renderProfile(); renderMedicationCards(); syncMedicationPayload();
  wireEvents(); toggleCriticalSections();
  window.PatientStore.load();
  loadDoctors();
})();
</script>

{{-- ==================== BOOKING MODAL MODULE ==================== --}}
<script>
(() => {
  const CLINIC_ID      = window.PatientStore.getClinicId();
  const API_BASE       = window.PatientStore.getApiBase();
  const CURRENT_USER_ID= Number(@json(auth()->id() ?? session('user_id') ?? data_get(session('user'),'id') ?? null)) || null;
  const PATH_PREFIX    = window.location.pathname.startsWith('/backend') ? '/backend' : '';
  const CONFIG = {
    CSRF_URL: `${window.location.origin}${PATH_PREFIX}/sanctum/csrf-cookie`,
  };

  function getCookie(name) { return document.cookie.split('; ').find(r=>r.startsWith(name+'='))?.split('=')[1]??''; }

  const Auth = {
    mode: 'unknown',
    async bootstrap() {
      const token = localStorage.getItem('token')||sessionStorage.getItem('token');
      if (token) { this.mode='bearer'; return; }
      try {
        await fetch(CONFIG.CSRF_URL, { credentials:'include' });
        if (getCookie('XSRF-TOKEN')) { this.mode='cookie'; return; }
      } catch(_) {}
      this.mode='none';
    },
    headers(base={}) {
      const h = { Accept:'application/json', ...base };
      if (CLINIC_ID) { h['X-Clinic-Id']=String(CLINIC_ID); h['X-User-Id']=String(CLINIC_ID); }
      else if (CURRENT_USER_ID) { h['X-User-Id']=String(CURRENT_USER_ID); }
      if (this.mode==='bearer') { const t=localStorage.getItem('token')||sessionStorage.getItem('token'); if(t) h['Authorization']='Bearer '+t; }
      else if (this.mode==='cookie') { h['X-Requested-With']='XMLHttpRequest'; const x=decodeURIComponent(getCookie('XSRF-TOKEN')||''); if(x) h['X-XSRF-TOKEN']=x; }
      return h;
    },
  };

  function appendTarget(fd) {
    if (CLINIC_ID) { if(!fd.has('clinic_id')) fd.append('clinic_id',String(CLINIC_ID)); }
  }

  async function apiFetch(url, opts={}) {
    const res = await fetch(url, { credentials:'include', ...opts });
    const ct  = res.headers.get('content-type')||'';
    const data = ct.includes('application/json') ? await res.json() : await res.text();
    if (!res.ok) throw new Error(typeof data==='string' ? data : data?.message||'Request failed');
    return data;
  }

  const resolvePetId = pet => { const raw = pet?.id ?? pet?.pet_id; const num = Number(raw); return Number.isFinite(num) && num > 0 ? num : null; };
  const formatPetLabel = (pet, fallback = 'Pet') => { const name = pet?.name || pet?.pet_name || fallback; const id = resolvePetId(pet); return id ? `${name} #${id}` : name; };

  const DOG_BREEDS_API_URL = `${API_BASE}/dog-breeds/all`;
  const DOG_BREEDS_CDN     = 'https://snoutiq.com/backend/api/dog-breeds/all';
  const CAT_BREEDS_API_URL = `${API_BASE}/cat-breeds/with-indian`;
  const CAT_BREEDS_CDN     = 'https://snoutiq.com/backend/api/cat-breeds/with-indian';
  const SELECT2_ASSETS = {
    jquery:    @json(url('vertical/assets/js/jquery.min.js')),
    jqueryCdn: 'https://code.jquery.com/jquery-3.7.1.min.js',
    select2:   @json(url('vertical/assets/plugins/select2/js/select2.min.js')),
    select2Cdn:'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
  };

  const bookingModal      = document.getElementById('booking-modal');
  const bookingForm       = document.getElementById('booking-form');
  const patientSearchInput= document.getElementById('patient-search');
  const patientResults    = document.getElementById('patient-results');
  const patientSelect     = document.getElementById('patient-select'); // hidden, holds value
  const patientSearchBlock= document.getElementById('patient-search-block');
  const petSelect         = document.getElementById('pet-select');
  const doctorSelect      = document.getElementById('booking-doctor-select');
  const slotSelect        = document.getElementById('slot-select');
  const slotHint          = document.getElementById('slot-hint');
  const existingSection   = document.getElementById('existing-patient-section');
  const newSection        = document.getElementById('new-patient-section');
  const existingDetails   = document.getElementById('existing-patient-details');
  const bookingNotesBlock = document.getElementById('booking-notes-block');
  const existingInlinePetFields = document.getElementById('existing-inline-pet-fields');
  const patientPickerLabel = document.getElementById('patient-picker-label');
  const existingPatientDisplay = document.getElementById('existing-patient-display-name');
  const bookingSubmitFields=document.getElementById('booking-submit-fields');
  const modeButtons       = Array.from(document.querySelectorAll('[data-patient-mode]'));
  const bookingNew        = document.querySelector('[data-role="booking-new"]');
  const bookingExisting   = document.querySelector('[data-role="booking-existing"]');
  const inlineBreedSelect = bookingForm?.elements['inline_pet_breed'];
  const newBreedSelect    = bookingForm?.elements['new_pet_breed'];
  const breedSelects      = [inlineBreedSelect, newBreedSelect].filter(Boolean);

  let ALL_PATIENTS   = [];   // populated from PatientStore (same API)
  let FILTERED_PATIENTS = [];
  let CURRENT_PATIENT= null;
  let PATIENT_MODE   = 'new';
  let DOG_BREED_LIST = [];
  let CAT_BREED_LIST = null;
  let select2Loader  = null;
  const PET_TYPE_GROUPS = {
    new:    { hidden: 'new_pet_type',    breedSelect: () => newBreedSelect,    selectWrap: '[data-breed-wrap="new-select"]',    exoticWrap: '[data-breed-wrap="new-exotic"]',    exoticInput: 'new_pet_exotic_detail' },
    inline: { hidden: 'inline_pet_type', breedSelect: () => inlineBreedSelect, selectWrap: '[data-breed-wrap="inline-select"]', exoticWrap: '[data-breed-wrap="inline-exotic"]', exoticInput: 'inline_pet_exotic_detail' },
  };

  // ---- Subscribe to the shared PatientStore ----
  window.PatientStore.subscribe(({ patients }) => {
    ALL_PATIENTS      = patients;
    FILTERED_PATIENTS = patients;
    // Re-render results only when modal is open
    if (bookingModal?.classList.contains('active')) {
      filterAndRenderPatients(patientSearchInput?.value||'');
    }
  });

  function filterPatients(query='') {
    if (!query.trim()) return ALL_PATIENTS;
    return ALL_PATIENTS.filter(p => window.PatientStore.matchesSearch(p, query));
  }

  function filterAndRenderPatients(query='') {
    FILTERED_PATIENTS = filterPatients(query);
    renderPatientResults(FILTERED_PATIENTS);
  }

  function renderPatientResults(list) {
    if (!patientResults) return;
    patientResults.innerHTML = '';

    if (window.PatientStore.getAll().length === 0 && !CLINIC_ID) {
      patientResults.innerHTML = '<div class="patient-results-empty">No clinic detected.</div>'; return;
    }
    if (!list || !list.length) {
      patientResults.innerHTML = '<div class="patient-results-empty">No patients found.</div>'; return;
    }

    list.forEach(patient => {
      const card = document.createElement('div');
      const isSelected = String(patientSelect?.value||'') === String(patient.id);
      card.className = `patient-result${isSelected?' is-selected':''}`;

      const info = document.createElement('div');
      const name = document.createElement('div'); name.className='patient-result-name'; name.textContent=patient.name||'Patient';
      const meta = document.createElement('div'); meta.className='patient-result-meta'; meta.textContent=patient.phone||patient.email||'';
      const tagsDiv = document.createElement('div'); tagsDiv.className='patient-result-tags';
      const pets = Array.isArray(patient.pets) ? patient.pets : [];
      pets.slice(0,2).forEach(pet => {
        const tag = document.createElement('span'); tag.className='patient-result-tag';
        tag.textContent=`${formatPetLabel(pet)} (${pet.species||pet.pet_type||pet.type||'Pet'})`;
        tagsDiv.appendChild(tag);
      });
      info.appendChild(name); info.appendChild(meta); if (tagsDiv.childElementCount) info.appendChild(tagsDiv);

      const action = document.createElement('button'); action.type='button'; action.className='patient-result-action'; action.textContent='Select ->';
      action.addEventListener('click', ev => { ev.stopPropagation(); selectPatientInModal(patient); });
      card.appendChild(info); card.appendChild(action);
      card.addEventListener('click', () => selectPatientInModal(patient));
      patientResults.appendChild(card);
    });
  }

  function selectPatientInModal(patient) {
    CURRENT_PATIENT = patient;
    if (patientSelect) { patientSelect.innerHTML = `<option value="${patient.id}" selected>${patient.name}</option>`; patientSelect.value = patient.id; }
    // Load pets from the already-fetched patient object (it has `pets` array from PatientStore)
    renderPetOptions(Array.isArray(patient.pets) ? patient.pets : []);
    updateBookingSections();
    renderPatientResults(FILTERED_PATIENTS); // re-render to highlight selected
    if (existingPatientDisplay) {
      existingPatientDisplay.textContent = patient.name || 'Unknown';
      existingPatientDisplay.classList.remove('hidden');
    }
    patientPickerLabel?.classList.add('hidden');
  }

  function normalizeInlinePetType(raw) {
    const t = String(raw ?? '').trim().toLowerCase();
    if (!t) return 'dog';
    if (t === 'dog' || t === 'dogs' || t === 'canine' || t === 'puppy') return 'dog';
    if (t === 'cat' || t === 'cats' || t === 'feline' || t === 'kitten') return 'cat';
    if (t === 'exotic' || t === 'bird' || t === 'rabbit' || t === 'parrot' || t === 'turtle' || t === 'hamster' || t === 'reptile') return 'exotic';
    if (['dog', 'cat', 'exotic'].includes(t)) return t;
    if (/\bdog\b/.test(t)) return 'dog';
    if (/\bcat\b/.test(t)) return 'cat';
    return 'dog';
  }

  function normalizeInlinePetGender(raw) {
    const g = String(raw ?? '').trim().toLowerCase();
    if (g === 'm' || g === 'male' || g === 'boy') return 'male';
    if (g === 'f' || g === 'female' || g === 'girl') return 'female';
    return ['male', 'female'].includes(g) ? g : '';
  }

  function matchBreedOption(breed, breeds) {
    const needle = String(breed ?? '').trim();
    if (!needle || !Array.isArray(breeds) || !breeds.length) return needle;
    const exact = breeds.find(b => b === needle);
    if (exact) return exact;
    const lower = needle.toLowerCase();
    return breeds.find(b => String(b).toLowerCase() === lower) || needle;
  }

  function findPatientPet(petId) {
    if (!petId || !CURRENT_PATIENT) return null;
    const pets = Array.isArray(CURRENT_PATIENT.pets) ? CURRENT_PATIENT.pets : [];
    return pets.find(p => String(p.id ?? p.pet_id) === String(petId)) || null;
  }

  async function fillInlinePetFieldsFromPet(pet) {
    if (!pet) return;

    const petNameInput = bookingForm?.elements['inline_pet_name'];
    if (petNameInput) petNameInput.value = pet.pet_name || pet.name || '';

    const petType = normalizeInlinePetType(pet.pet_type || pet.species || pet.type);
    const hiddenType = bookingForm?.elements['inline_pet_type'];
    if (hiddenType) hiddenType.value = petType;

    const tabContainer = bookingForm?.querySelector('.pet-type-tabs[data-pet-type-group="inline"]');
    tabContainer?.querySelectorAll('.pet-type-tab').forEach(tab => {
      tab.classList.toggle('active', tab.dataset.petType === petType);
    });
    toggleBreedFields('inline', petType);

    if (petType === 'exotic') {
      const exoticInput = bookingForm?.elements['inline_pet_exotic_detail'];
      if (exoticInput) exoticInput.value = pet.breed || pet.exotic_detail || '';
      const breedSelect = bookingForm?.elements['inline_pet_breed'];
      if (breedSelect) {
        populateBreedSelect(breedSelect, []);
        destroyBreedSelect2(breedSelect);
      }
    } else {
      const breedSelect = bookingForm?.elements['inline_pet_breed'];
      if (breedSelect) {
        try {
          const breeds = petType === 'cat' ? await ensureCatBreeds() : await ensureDogBreeds();
          populateBreedSelect(breedSelect, breeds);
          const matchedBreed = matchBreedOption(pet.breed, breeds);
          if (matchedBreed) breedSelect.value = matchedBreed;
          initBreedSelect2();
        } catch (e) {
          populateBreedSelect(breedSelect, []);
          if (pet.breed) breedSelect.value = pet.breed;
          initBreedSelect2();
        }
      }
      const exoticInput = bookingForm?.elements['inline_pet_exotic_detail'];
      if (exoticInput) exoticInput.value = '';
    }

    const genderSelect = bookingForm?.elements['inline_pet_gender'];
    if (genderSelect) {
      genderSelect.value = normalizeInlinePetGender(pet.gender || pet.pet_gender);
    }
  }

  async function syncInlinePetFromSelection() {
    const petId = petSelect?.value;
    if (!petId) return;
    await fillInlinePetFieldsFromPet(findPatientPet(petId));
  }

  function renderPetOptions(pets) {
    if (!petSelect) return;
    petSelect.innerHTML = '';
    if (!pets.length) {
      petSelect.innerHTML = '<option value="">No pets found</option>';
      return;
    }
    pets.forEach(pet => {
      const opt = document.createElement('option');
      opt.value = pet.id ?? pet.pet_id;
      opt.textContent = `${formatPetLabel(pet)} | ${pet.pet_type || pet.species || pet.type || ''}`;
      opt.dataset.petName = pet.pet_name || pet.name || '';
      petSelect.appendChild(opt);
    });
    const firstId = pets[0]?.id ?? pets[0]?.pet_id;
    if (firstId != null && firstId !== '') {
      petSelect.value = String(firstId);
      void syncInlinePetFromSelection();
    }
  }

  petSelect?.addEventListener('change', () => { void syncInlinePetFromSelection(); });

  function openBooking() {
    if (!bookingModal) return;
    if (!CLINIC_ID && !CURRENT_USER_ID) { Swal.fire({icon:'warning',title:'Clinic missing',text:'Open this page from the clinic dashboard.'}); return; }
    bookingModal.classList.add('active'); bookingModal.removeAttribute('hidden'); bookingModal.setAttribute('aria-hidden','false');
    // const dateField = bookingForm?.elements['scheduled_date'];
    // if (dateField && !dateField.value) dateField.value = new Date().toISOString().split('T')[0];
    initBreedSelect2();
    // PatientStore already loaded; if not yet, trigger load
    window.PatientStore.load();
    filterAndRenderPatients(patientSearchInput?.value||'');
    if (!doctorSelect?.options?.length || doctorSelect.options.length <= 1) fetchDoctors();
    if (existingPatientDisplay) {
      existingPatientDisplay.textContent = '';
      existingPatientDisplay.classList.add('hidden');
    }
    patientPickerLabel?.classList.remove('hidden');
  }

  function closeBooking() {
    if (!bookingModal) return;
    bookingModal.classList.remove('active'); bookingModal.setAttribute('hidden','hidden'); bookingModal.setAttribute('aria-hidden','true');
    bookingForm?.reset(); CURRENT_PATIENT=null;
    if (patientSelect) patientSelect.innerHTML='';
    if (petSelect)     petSelect.innerHTML='';
    if (slotSelect)    slotSelect.innerHTML='<option value="">Select a time slot</option>';
    if (slotHint)      slotHint.textContent='Select a doctor and date first to load available slots.';
    if (patientSearchInput) patientSearchInput.value='';
    if (patientResults) patientResults.innerHTML='';
    setPatientMode('new');
    resetPetTypeTabs();
    if (existingPatientDisplay) {
      existingPatientDisplay.textContent = '';
      existingPatientDisplay.classList.add('hidden');
    }
    patientPickerLabel?.classList.remove('hidden');
  }

  function setPatientMode(mode) {
    PATIENT_MODE=mode;
    modeButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.patientMode===mode));
    existingSection?.classList.toggle('hidden', mode!=='existing');
    newSection?.classList.toggle('hidden', mode!=='new');
    updateBookingSections();
    if (mode==='existing') filterAndRenderPatients(patientSearchInput?.value||'');
  }

  function updateBookingSections() {
    const isExisting = PATIENT_MODE === 'existing';
    const hasPatient = Boolean(patientSelect?.value);
    existingDetails?.classList.toggle('hidden', !isExisting || !hasPatient);
    bookingSubmitFields?.classList.toggle('hidden', isExisting && !hasPatient);
    patientSearchBlock?.classList.toggle('hidden', isExisting && hasPatient);
    bookingNotesBlock?.classList.toggle('hidden', isExisting);
    existingInlinePetFields?.classList.add('hidden');
    if (isExisting && hasPatient) {
      patientPickerLabel?.classList.add('hidden');
      existingPatientDisplay?.classList.remove('hidden');
    } else if (isExisting) {
      patientPickerLabel?.classList.remove('hidden');
      existingPatientDisplay?.classList.add('hidden');
    }
  }

  modeButtons.forEach(btn => btn.addEventListener('click', () => setPatientMode(btn.dataset.patientMode)));
  Array.from(bookingModal?.querySelectorAll('[data-close]')||[]).forEach(btn => btn.addEventListener('click', closeBooking));
  bookingNew?.addEventListener('click', () => { openBooking(); setPatientMode('new'); });
  bookingExisting?.addEventListener('click', () => { openBooking(); setPatientMode('existing'); });

  // Live search filters the already-loaded PatientStore data (no extra API call)
  let searchTimer;
  patientSearchInput?.addEventListener('input', e => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => filterAndRenderPatients(e.target.value), 200);
  });

  /* ---- Doctors & slots (unchanged) ---- */
  async function fetchDoctors() {
    if (!doctorSelect) return;
    try {
      await Auth.bootstrap();
      const res  = await apiFetch(`${API_BASE}/receptionist/doctors?clinic_id=${CLINIC_ID||CURRENT_USER_ID||''}`, { headers: Auth.headers() });
      const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
      doctorSelect.innerHTML='<option value="">Any available doctor</option>';
      list.forEach(doc => { const o=document.createElement('option'); o.value=doc.id; o.textContent=doc.doctor_name||doc.name||`Doctor ${doc.id}`; doctorSelect.appendChild(o); });
    } catch(e) { console.error('fetchDoctors',e); }
  }

  async function fetchDoctorSlots(doctorId) {
    if (!slotSelect) return;
    if (!doctorId) { slotSelect.innerHTML='<option value="">Select a time slot</option>'; slotHint.textContent='Select a doctor and date first.'; return; }
    const date = bookingForm.elements['scheduled_date'].value;
    if (!date) { slotSelect.innerHTML='<option value="">Select a time slot</option>'; slotHint.textContent='Select a doctor and date first.'; return; }
    try {
      await Auth.bootstrap();
      const url = `${API_BASE}/doctors/${doctorId}/slots/summary?clinic_id=${CLINIC_ID||''}&user_id=${CLINIC_ID||CURRENT_USER_ID||''}&date=${date}&service_type=${bookingForm.elements['service_type'].value||'in_clinic'}`;
      const res  = await apiFetch(url, { headers: Auth.headers() });
      const slots = res?.free_slots||[];
      slotSelect.innerHTML='<option value="">Select a time slot</option>';
      slots.forEach(slot => { const t=typeof slot==='string'?slot:(slot.time||slot.time_slot||slot.slot||''); const s=typeof slot==='string'?'free':(slot.status||'free'); const o=document.createElement('option'); o.value=t; o.textContent=`${t} (${s})`; slotSelect.appendChild(o); });
      slotHint.textContent = slots.length ? `${slots.length} slots available` : 'No slots available for this date';
    } catch(e) { console.error('fetchDoctorSlots',e); slotSelect.innerHTML='<option value="">Select a time slot</option>'; slotHint.textContent='Failed to load slots'; }
  }

  doctorSelect?.addEventListener('change', () => fetchDoctorSlots(doctorSelect.value));
  bookingForm?.elements['scheduled_date']?.addEventListener('change', () => fetchDoctorSlots(doctorSelect?.value));

  /* ---- Pet type tabs & breeds ---- */
  function toTitleCase(v) { return String(v||'').split(/[\s_-]+/).filter(Boolean).map(w=>w.charAt(0).toUpperCase()+w.slice(1)).join(' '); }
  function buildDogBreedList(data) {
    const s=new Set();
    Object.entries(data||{}).forEach(([breed,subs]) => { const bn=toTitleCase(breed); Array.isArray(subs)&&subs.length ? subs.forEach(sub=>s.add(`${toTitleCase(sub)} ${bn}`.trim())) : s.add(bn); });
    return Array.from(s).sort((a,b)=>a.localeCompare(b));
  }
  function buildCatBreedList(payload) {
    return Array.from(new Set([
      ...(Array.isArray(payload?.data) ? payload.data : []).map(item => String(item?.name || item?.id || '').trim()),
      'Indian Cat',
      'Mixed / Other',
    ].filter(Boolean))).sort((a,b)=>a.localeCompare(b));
  }
  function populateBreedSelect(sel, breeds, placeholder='Select breed') {
    if (!sel) return;
    const prev=sel.value; sel.innerHTML='';
    const ph=document.createElement('option'); ph.value=''; ph.textContent=placeholder; sel.appendChild(ph);
    breeds.forEach(b => { const o=document.createElement('option'); o.value=b; o.textContent=b; sel.appendChild(o); });
    if (prev&&breeds.includes(prev)) sel.value=prev;
  }
  const tryFetchJson = async url => { const r=await fetch(url,{headers:{Accept:'application/json'}}); if(!r.ok) throw new Error(); return r.json(); };
  async function ensureDogBreeds() {
    if (DOG_BREED_LIST.length) return DOG_BREED_LIST;
    const data = await tryFetchJson(DOG_BREEDS_API_URL).catch(()=>tryFetchJson(DOG_BREEDS_CDN));
    DOG_BREED_LIST = buildDogBreedList(data?.breeds||{});
    return DOG_BREED_LIST;
  }
  async function ensureCatBreeds() {
    if (CAT_BREED_LIST) return CAT_BREED_LIST;
    const data = await tryFetchJson(CAT_BREEDS_API_URL).catch(()=>tryFetchJson(CAT_BREEDS_CDN));
    CAT_BREED_LIST = buildCatBreedList(data);
    return CAT_BREED_LIST;
  }
  function getPetTypeValue(group) {
    return (bookingForm?.elements[PET_TYPE_GROUPS[group]?.hidden]?.value || 'dog').toLowerCase();
  }
  function getPetBreedValue(group) {
    const type = getPetTypeValue(group);
    if (type === 'exotic') {
      const exoticName = PET_TYPE_GROUPS[group]?.exoticInput;
      return (bookingForm?.elements[exoticName]?.value || '').trim() || 'Unknown';
    }
    const sel = PET_TYPE_GROUPS[group]?.breedSelect?.();
    return (sel?.value || '').trim() || 'Unknown';
  }
  function toggleBreedFields(group, type) {
    const cfg = PET_TYPE_GROUPS[group];
    if (!cfg || !bookingForm) return;
    const selectWrap = bookingForm.querySelector(cfg.selectWrap);
    const exoticWrap = bookingForm.querySelector(cfg.exoticWrap);
    const isExotic = type === 'exotic';
    selectWrap?.classList.toggle('hidden', isExotic);
    exoticWrap?.classList.toggle('hidden', !isExotic);
  }
  async function applyPetType(group, type, { resetBreed = true } = {}) {
    const cfg = PET_TYPE_GROUPS[group];
    if (!cfg || !bookingForm) return;
    const hidden = bookingForm.elements[cfg.hidden];
    if (hidden) hidden.value = type;
    const tabList = bookingForm.querySelector(`[data-pet-type-group="${group}"]`);
    tabList?.querySelectorAll('.pet-type-tab').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.petType === type);
    });
    toggleBreedFields(group, type);
    const sel = cfg.breedSelect();
    if (!sel || type === 'exotic') {
      destroyBreedSelect2(sel);
      if (resetBreed && bookingForm.elements[cfg.exoticInput]) bookingForm.elements[cfg.exoticInput].value = '';
      return;
    }
    if (resetBreed) sel.value = '';
    populateBreedSelect(sel, [], 'Loading breeds...');
    initBreedSelect2();
    try {
      const breeds = type === 'cat' ? await ensureCatBreeds() : await ensureDogBreeds();
      populateBreedSelect(sel, breeds);
      initBreedSelect2();
    } catch(e) {
      populateBreedSelect(sel, [], 'Breeds unavailable');
      initBreedSelect2();
    }
  }
  function resetPetTypeTabs() {
    ['new', 'inline'].forEach(group => applyPetType(group, 'dog', { resetBreed: true }));
  }
  function initPetTypeTabs() {
    bookingForm?.querySelectorAll('.pet-type-tabs').forEach(tabList => {
      const group = tabList.dataset.petTypeGroup;
      if (!group) return;
      tabList.querySelectorAll('.pet-type-tab').forEach(btn => {
        btn.addEventListener('click', () => applyPetType(group, btn.dataset.petType || 'dog', { resetBreed: true }));
      });
    });
    resetPetTypeTabs();
  }
  function destroyBreedSelect2(sel) {
    if (!sel || !window.jQuery?.fn?.select2) return;
    const $sel = window.jQuery(sel);
    if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
  }

  function loadScriptOnce(src,id) {
    return new Promise((res,rej)=>{ if(id&&document.getElementById(id)){res();return;} const s=document.createElement('script'); if(id)s.id=id; s.src=src; s.async=true; s.onload=()=>res(); s.onerror=()=>rej(); document.head.appendChild(s); });
  }
  function ensureSelect2Ready() {
    if (window.jQuery?.fn?.select2) return Promise.resolve(true);
    if (select2Loader) return select2Loader;
    select2Loader = (async()=>{
      try {
        if (!window.jQuery?.fn) await loadScriptOnce(SELECT2_ASSETS.jquery,'snoutiq-jquery').catch(()=>loadScriptOnce(SELECT2_ASSETS.jqueryCdn,'snoutiq-jquery-cdn'));
        if (!window.jQuery?.fn?.select2) await loadScriptOnce(SELECT2_ASSETS.select2,'snoutiq-select2').catch(()=>loadScriptOnce(SELECT2_ASSETS.select2Cdn,'snoutiq-select2-cdn'));
        return Boolean(window.jQuery?.fn?.select2);
      } catch(e){ return false; }
    })();
    return select2Loader;
  }
  function initBreedSelect2() {
    ensureSelect2Ready().then(ready => {
      if (!ready||!window.jQuery?.fn?.select2) return;
      const dp = bookingModal ? window.jQuery(bookingModal) : window.jQuery(document.body);
      breedSelects.forEach(sel => {
        if (!sel) return;
        const $sel=window.jQuery(sel), prev=sel.value;
        if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
        $sel.select2({ width:'100%', placeholder:'Select breed', allowClear:true, minimumResultsForSearch:0, dropdownParent:dp });
        if (prev) $sel.val(prev).trigger('change.select2');
      });
    });
  }

  /* ---- Form submit ---- */
  function normalizePhone(...candidates) {
    for (const v of candidates) {
      if (typeof v!=='string') continue;
      const t=v.trim(); if(!t||t.includes('@')) continue;
      const d=t.replace(/\D+/g,''); if(!d) continue;
      if (d.startsWith('91')&&d.length>=12) return d.slice(0,12);
      if (d.length===10) return `91${d}`;
      return d;
    }
    return null;
  }

  bookingForm?.addEventListener('submit', async ev => {
    ev.preventDefault();
    if (!CLINIC_ID&&!CURRENT_USER_ID) { Swal.fire({icon:'warning',title:'Clinic missing',text:'Reload from clinic dashboard.'}); return; }
    // const doctorId = bookingForm.elements['doctor_id'].value;
    // const date     = bookingForm.elements['scheduled_date'].value;
    // const timeSlot = bookingForm.elements['scheduled_time'].value;
    // if (!doctorId||!date||!timeSlot) { Swal.fire({icon:'warning',title:'Required fields missing',text:'Doctor, date and slot are mandatory.'}); return; }
    const doctorId = bookingForm.elements['doctor_id'].value;
const date     = bookingForm.elements['scheduled_date'].value;
const timeSlot = bookingForm.elements['scheduled_time'].value;
if (!doctorId) { Swal.fire({icon:'warning',title:'Required fields missing',text:'Please select a doctor.'}); return; }
if (date && !timeSlot) { Swal.fire({icon:'warning',title:'Time slot required',text:'Please select a time slot for the chosen date.'}); return; }
    try {
      await Auth.bootstrap();
      let patientId=patientSelect?.value||null, patientName=CURRENT_PATIENT?.name||'', patientPhone=null, petName=null;

      if (PATIENT_MODE==='new') {
        const name=bookingForm.elements['new_patient_name'].value.trim();
        const phone=normalizePhone(bookingForm.elements['new_patient_phone'].value);
        const email=bookingForm.elements['new_patient_email'].value.trim();
        const newPetName=bookingForm.elements['new_pet_name'].value.trim();
        if (!name||(!phone&&!email)) { Swal.fire({icon:'warning',title:'Patient details required',text:'Provide name and phone or email.'}); return; }
        if (!newPetName) { Swal.fire({icon:'warning',title:'Pet name required'}); return; }
        const newPetType = getPetTypeValue('new');
        if (newPetType === 'exotic' && !(bookingForm.elements['new_pet_exotic_detail']?.value||'').trim()) {
          Swal.fire({icon:'warning',title:'Exotic pet required',text:'Please specify the exotic pet type (e.g. Parrot, Rabbit).'}); return;
        }
        if (newPetType !== 'exotic' && !(bookingForm.elements['new_pet_breed']?.value||'').trim()) {
          Swal.fire({icon:'warning',title:'Breed required',text:'Please select a breed for your pet.'}); return;
        }
        const payload=new FormData();
        payload.append('name',name); if(phone) payload.append('phone',phone); if(email) payload.append('email',email);
        payload.append('pet_name',newPetName);
        payload.append('pet_type',getPetTypeValue('new')||'dog');
        payload.append('pet_breed',getPetBreedValue('new'));
        payload.append('pet_gender',bookingForm.elements['new_pet_gender'].value.trim()||'unknown');
        if(CLINIC_ID) payload.append('clinic_id',String(CLINIC_ID));
        const res=await apiFetch(`${API_BASE}/receptionist/patients`,{method:'POST',headers:Auth.headers(),body:payload});
        patientId=res?.data?.user?.id; patientName=res?.data?.user?.name||name;
        patientPhone=normalizePhone(res?.data?.user?.phone,phone,res?.data?.user?.email,email);
        petName=res?.data?.pet?.name||newPetName;
        if (!patientId) { Swal.fire({icon:'error',title:'Could not create patient',text:'Patient registration failed. Please try again.'}); return; }
        // Refresh PatientStore so new patient shows everywhere
        await window.PatientStore.reload();
      } else {
        if (!patientId) { Swal.fire({icon:'warning',title:'Select a patient'}); return; }
        patientPhone = normalizePhone(CURRENT_PATIENT?.phone,CURRENT_PATIENT?.email);
        const sel = petSelect?.options[petSelect.selectedIndex];
        petName = sel?.dataset?.petName || (sel?.textContent ? sel.textContent.split('|')[0].trim() : null);
      }
      const payload=new FormData();
      if(patientId)    payload.append('user_id',patientId);
      if(CLINIC_ID)    payload.append('clinic_id',String(CLINIC_ID));
      payload.append('doctor_id',doctorId); payload.append('patient_name',patientName);
      if(patientPhone) payload.append('patient_phone',patientPhone);
      if(petName)      payload.append('pet_name',petName);
      if (date)     payload.append('date', date);
if (timeSlot) payload.append('time_slot', timeSlot);

      const notes=bookingForm.elements['notes'].value.trim(); if(notes) payload.append('notes',notes);
      appendTarget(payload);
      await apiFetch(`${API_BASE}/appointments/submit`,{method:'POST',headers:Auth.headers(),body:payload});
      Swal.fire({icon:'success',title:'Appointment saved',timer:1500,showConfirmButton:false});
      closeBooking(); fetchDoctorSlots(doctorId);
    } catch(error) { Swal.fire({icon:'error',title:'Unable to save appointment',text:error.message||'Unknown error'}); }
  });

  document.addEventListener('DOMContentLoaded', () => { fetchDoctors(); initPetTypeTabs(); });
})();
</script>

@endsection