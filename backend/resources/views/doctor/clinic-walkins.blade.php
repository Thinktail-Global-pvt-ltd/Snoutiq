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
    .pm-record-media{margin-top:10px;border:1px solid #dbeafe;background:#f8fafc;border-radius:12px;padding:10px}
    .pm-record-media img{max-width:100%;height:auto;border-radius:10px;display:block}
    .pm-record-media iframe{width:100%;height:360px;border:0;border-radius:10px;background:#fff}
    .pm-record-link{color:#1d4ed8;font-weight:700;text-decoration:none}
    .pm-record-file{font-size:12px;color:var(--pm-muted);word-break:break-word;margin-top:6px}
    .pm-record-list{margin:0;padding-left:18px}
    .pm-record-list li{margin:0 0 4px 0}
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
    .meds-hidden-text{display:none}
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
    .pm-petActions{display:flex;align-items:center;gap:8px;margin-left:auto}
    .pm-petDelete{border:1px solid #fecaca;background:#fff1f2;color:#b91c1c;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;cursor:pointer}
    .pm-petDelete:hover{background:#fee2e2}
    .pm-petDelete:disabled{opacity:.5;cursor:not-allowed}
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
    .modal-overlay{position:fixed;inset:0;background:rgba(8,10,14,0.45);display:none;align-items:center;justify-content:center;padding:0;z-index:70}
    .modal-overlay.active{display:flex}
    .modal-card{width:100%;max-width:100%;height:100vh;max-height:100vh;background:#fff;border-radius:0;box-shadow:0 20px 60px rgba(15,23,42,0.25);padding:24px 28px;overflow-y:auto}
    .modal-card select{min-height:44px;padding-top:10px;padding-bottom:10px}
    .select2-container{width:100%!important}
    .select2-container--default .select2-selection--single{height:44px;border-radius:0.5rem;border:1px solid transparent;background:#f8fafc}
    .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:44px;padding-left:0.75rem;padding-right:2.5rem;font-size:0.875rem;color:#0f172a}
    .select2-container--default .select2-selection--single .select2-selection__placeholder{color:#94a3b8}
    .select2-container--default .select2-selection--single .select2-selection__arrow{height:44px;right:10px}
    .select2-container--default.select2-container--focus .select2-selection--single{border-color:#14b8a6;box-shadow:0 0 0 2px rgba(20,184,166,0.2)}
    .select2-dropdown{border-radius:12px;border-color:#e2e8f0}
    .select2-results__option--highlighted[aria-selected]{background:#0f766e}
    .select2-container--open{z-index:90}
    .modal-tabs{margin-top:16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .tab-button{padding:0.45rem 1.1rem;border-radius:999px;font-size:0.9rem;font-weight:600;border:1px solid #e2e8f0;background:#f8fafc;color:#475569;cursor:pointer;transition:background .2s,color .2s,border-color .2s}
    .tab-button.active{background:#0f766e;color:#fff;border-color:#0f766e}
    .patient-results{display:flex;flex-direction:column;gap:10px;margin-top:10px;max-height:220px;overflow-y:auto;padding-right:4px}
    .patient-result{border:1px solid #e2e8f0;background:#fff;border-radius:16px;padding:12px 14px;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;cursor:pointer;transition:border-color .2s,box-shadow .2s}
    .patient-result.is-selected{border-color:#0f766e;box-shadow:0 0 0 3px rgba(15,118,110,0.12)}
    .patient-result-name{font-weight:700;color:#0f172a}
    .patient-result-meta{color:#64748b;font-size:12px;margin-top:2px}
    .patient-result-tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
    .patient-result-tag{font-size:11px;font-weight:700;border-radius:999px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;padding:2px 8px}
    .patient-result-action{font-weight:700;color:#2563eb;background:none;border:none;cursor:pointer}
    .patient-results-empty{font-size:12px;color:#94a3b8;padding:6px 4px}
    .modal-card form .space-y-4>* + *{margin-top:1rem}
    .pm-shell{
      --pm-bg:#f8fafc;
      --pm-panel:rgba(255,255,255,0.92);
      --pm-muted:#64748b;
      --pm-blue:#0f766e;
      --pm-purple:#38bdf8;
      --pm-green:#10b981;
      --pm-orange:#f97316;
      --pm-radius:20px;
      --pm-shadow:0 18px 44px rgba(15,23,42,0.1);
      background:linear-gradient(180deg,#f8fafc 0%, #eef6ff 100%);
      border-radius:26px;
      padding:22px;
      position:relative;
      overflow:hidden;
    }
    .pm-shell::before{
      content:"";
      position:absolute;
      top:-120px;
      right:-80px;
      width:320px;
      height:320px;
      border-radius:999px;
      background:radial-gradient(circle at top, rgba(56,189,248,0.22), rgba(255,255,255,0));
      pointer-events:none;
    }
    .pm-shell::after{
      content:"";
      position:absolute;
      bottom:-140px;
      left:-120px;
      width:300px;
      height:300px;
      border-radius:999px;
      background:radial-gradient(circle at top, rgba(15,118,110,0.18), rgba(255,255,255,0));
      pointer-events:none;
    }
    .pm-shell > *{position:relative;z-index:1}
    .pm-header{
      background:var(--pm-panel);
      padding:18px 20px;
      border-radius:22px;
      border:1px solid rgba(148,163,184,0.2);
      box-shadow:var(--pm-shadow);
      margin-bottom:16px;
    }
    .pm-title{color:#0f172a}
    .pm-subtitle{color:var(--pm-muted)}
    .pm-btn.pm-primary{
      background:linear-gradient(135deg,#0f766e,#38bdf8);
      box-shadow:0 12px 24px rgba(15,118,110,0.18);
    }
    .pm-btn.pm-ghost{border:1px solid #e2e8f0;color:#0f172a}
    .pm-chip{background:#ccfbf1;color:#0f766e}
    .pm-left,.pm-right{
      background:var(--pm-panel);
      border:1px solid rgba(148,163,184,0.18);
      border-radius:22px;
    }
    .pm-input{background:#f8fafc;border:1px solid #e2e8f0}
    .pm-tag.is-active{background:#0f766e;border-color:#0f766e}
    .pm-row.is-active{border-color:#14b8a6;background:#f0fdfa}
    .pm-avatar{background:#ecfeff;color:#0f766e}
    .pm-avatar-large{background:#f8fafc;border-color:#e2e8f0}
    .pm-badge{background:#ecfeff;color:#0f766e;border:1px solid #99f6e4}
    .pm-card{
      background:linear-gradient(145deg,rgba(15,118,110,0.1),rgba(56,189,248,0.08));
      border-color:rgba(15,118,110,0.18);
      box-shadow:0 14px 32px rgba(15,118,110,0.12);
    }
    .pm-record{
      background:#ffffff;
      border-color:rgba(15,118,110,0.2);
      box-shadow:0 14px 26px rgba(15,118,110,0.12);
    }
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
    .cw-search{position:relative;flex:1;min-width:220px;max-width:520px}
    .cw-search-input{width:100%;border-radius:999px;border:1px solid #e2e8f0;background:#f8fafc;padding:10px 14px 10px 40px;font-size:14px;color:#0f172a;outline:none}
    .cw-search-input:focus{border-color:#5eead4;box-shadow:0 0 0 3px rgba(45,212,191,0.25)}
    .cw-search-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94a3b8}
    .cw-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .cw-primary-btn{display:inline-flex;align-items:center;gap:10px;padding:10px 16px;border-radius:999px;border:none;background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff;font-weight:700;cursor:pointer;box-shadow:0 12px 24px rgba(15,118,110,0.18)}
    .cw-primary-btn:active{transform:scale(0.98)}
    .cw-plus{font-size:18px;line-height:1}
    .cw-icon-btn{width:40px;height:40px;border-radius:999px;border:1px solid #e2e8f0;background:#fff;display:flex;align-items:center;justify-content:center;position:relative;color:#475569}
    .cw-dot{position:absolute;top:10px;right:10px;width:8px;height:8px;border-radius:999px;background:#f87171;border:2px solid #fff}
    .cw-divider{width:1px;height:28px;background:#e2e8f0}
    .cw-avatar{width:36px;height:36px;border-radius:999px;background:#ccfbf1;color:#0f766e;font-weight:700;display:flex;align-items:center;justify-content:center;font-size:12px}
    .cw-hero{margin-top:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .cw-hero-title{font-size:32px;font-weight:300;color:#94a3b8}
    .cw-hero-title span{font-weight:700;color:#0f172a;font-style:italic}
    .cw-hero-sub{margin-top:6px;color:#64748b;font-size:14px}
    .cw-tiles{margin-top:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
    .cw-tile{background:#fff;border-radius:20px;border:1px solid #e2e8f0;padding:18px;display:flex;gap:14px;align-items:flex-start;text-align:left;transition:transform .2s ease, box-shadow .2s ease;cursor:pointer}
    .cw-tile:hover{transform:translateY(-2px);box-shadow:0 16px 30px rgba(15,23,42,0.08)}
    .cw-tile-icon{width:44px;height:44px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;border:1px solid #e2e8f0;color:#0f766e}
    .cw-tile-icon svg{width:22px;height:22px}
    .cw-tile-title{font-weight:700;color:#0f172a;font-size:16px}
    .cw-tile-sub{color:#64748b;font-size:13px;margin-top:4px}
    .cw-tile.mint{background:#ecfeff;border-color:#99f6e4}
    .cw-tile.mint .cw-tile-icon{border-color:#99f6e4;color:#0f766e}
    .cw-tile.sky{background:#eff6ff;border-color:#bfdbfe}
    .cw-tile.sky .cw-tile-icon{border-color:#bfdbfe;color:#2563eb}
    .cw-tile.sun{background:#fff7ed;border-color:#fed7aa}
    .cw-tile.sun .cw-tile-icon{border-color:#fed7aa;color:#f97316}
    .cw-tile.lilac{background:#f5f3ff;border-color:#ddd6fe}
    .cw-tile.lilac .cw-tile-icon{border-color:#ddd6fe;color:#7c3aed}
    .cw-slots{margin-top:20px;background:var(--pm-panel);border:1px solid #e2e8f0;border-radius:22px;padding:18px;box-shadow:var(--pm-shadow)}
    .cw-slots-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .cw-slots-title{font-size:18px;font-weight:700;color:#0f172a}
    .cw-slots-sub{font-size:12px;color:#64748b;margin-top:4px}
    .cw-slots-stats{display:flex;align-items:center;gap:14px;font-size:13px;color:#475569;font-weight:600;flex-wrap:wrap}
    .cw-stat-dot{width:10px;height:10px;border-radius:999px;display:inline-block;margin-right:6px}
    .cw-stat-dot.available{background:#22c55e}
    .cw-stat-dot.booked{background:#3b82f6}
    .cw-stat-dot.inclinic{background:#ef4444}
    .cw-secondary-btn{padding:8px 14px;border-radius:999px;border:1px solid #e2e8f0;background:#f8fafc;font-weight:700;color:#475569}
    .cw-slots-bar{margin-top:14px;display:grid;grid-template-columns:repeat(16,1fr);gap:6px}
    .cw-slot{height:8px;border-radius:999px;background:#d1fae5}
    .cw-slot.booked{background:#93c5fd}
    .cw-slot.inclinic{background:#fca5a5}
    .cw-queue{margin-top:20px;background:var(--pm-panel);border:1px solid #e2e8f0;border-radius:22px;padding:18px;box-shadow:var(--pm-shadow)}
    .cw-queue-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .cw-queue-title{font-size:18px;font-weight:700;color:#0f172a}
    .cw-queue-pill{padding:6px 12px;border-radius:999px;background:#f1f5f9;font-size:12px;font-weight:700;color:#475569}
    .cw-queue-table{margin-top:12px}
    .cw-queue-row{display:grid;grid-template-columns:2fr 2fr 1fr 1fr;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid #e2e8f0;font-size:14px}
    .cw-queue-row.header{font-size:11px;text-transform:uppercase;letter-spacing:0.12em;color:#94a3b8;font-weight:700}
    .cw-queue-name{font-weight:700;color:#0f172a}
    .cw-queue-sub{font-size:12px;color:#94a3b8}
    .cw-status{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700}
    .cw-status.waiting{background:#fef3c7;color:#b45309}
    .cw-status.doctor{background:#e0f2fe;color:#0369a1}
    .cw-queue-action{background:none;border:none;color:#0f766e;font-weight:700;cursor:pointer}
    @media (max-width:900px){
      .cw-queue-row{grid-template-columns:1.4fr 1.4fr 1fr;grid-auto-rows:auto}
      .cw-queue-row div:last-child{grid-column:1 / -1}
    }
    @keyframes pmFadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
    .pm-row,.pm-record,.pm-petRow{animation:pmFadeUp .4s ease both}
    @media (prefers-reduced-motion: reduce){
      .pm-row,.pm-record,.pm-petRow{animation:none}
    }
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
    <div class="pm-controls">
    </div>
  </div>

  <div class="pm-content">
    </div>
    <div class="pm-left">
      <div class="pm-searchRow">
        <input id="pm-search" class="pm-input" type="search" placeholder="Search patient, pet, phone...">
        <select id="pm-sort" class="pm-input pm-select">
          <option value="recent">Last activity (new->old)</option>
          <option value="records">Records (high->low)</option>
          <option value="name">Patient name (A->Z)</option>
        </select>
      </div>
      <div class="pm-tagsRow">
        <div class="pm-small">Quick filters:</div>
        <div id="pm-tag-filters" class="pm-tagWrap"></div>
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
            <button class="pm-btn pm-primary" data-role="open-upload">Post Op Form</button>
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
</div>

<div id="booking-modal" class="modal-overlay" hidden aria-hidden="true">
  <div class="modal-card">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold tracking-wide text-teal-600 uppercase">Front Desk</p>
        <h3 class="text-xl font-semibold text-slate-900">Create Booking</h3>
      </div>
      <button type="button" data-close class="text-slate-500 hover:text-slate-700 text-lg">&times;</button>
    </div>

    <div class="modal-tabs">
      <button type="button" class="tab-button active" data-patient-mode="new">New Patient</button>
      <button type="button" class="tab-button" data-patient-mode="existing">Existing Patient</button>
    </div>

    <form id="booking-form" class="space-y-5 mt-3">
      <div>
        <label class="block text-sm font-semibold mb-1">What happened?</label>
        <textarea name="notes" rows="3" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500" placeholder="Share the reason or context for this visit"></textarea>
      </div>
      <div id="existing-patient-section" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Patient</label>
          <div class="flex flex-col gap-2">
            <div id="patient-search-block" class="flex flex-col gap-2">
              <input id="patient-search" type="text" placeholder="Search by name or mobile number..." class="bg-slate-50 rounded-lg px-3 py-2 text-sm border border-transparent focus:bg-white focus:ring-2 focus:ring-teal-500">
              <div id="patient-results" class="patient-results"></div>
            </div>
            <select id="patient-select" name="patient_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500"></select>
          </div>
        </div>
        <div id="existing-patient-details" class="space-y-4 hidden">
          <div>
            <label class="block text-sm font-semibold mb-1">Pet</label>
            <select id="pet-select" name="pet_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500"></select>
            <p class="text-xs text-slate-500 mt-1">Need a new pet? Fill details below and we'll add it automatically.</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">New Pet Name</label>
              <input name="inline_pet_name" type="text" placeholder="Pet name" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Pet Type</label>
              <input name="inline_pet_type" type="text" placeholder="Dog, Cat..." class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Breed</label>
              <select name="inline_pet_breed" class="breed-select w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
                <option value="">Select breed</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Gender</label>
              <input name="inline_pet_gender" type="text" placeholder="Male/Female" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
          </div>
        </div>
      </div>

      <div id="booking-submit-fields" class="space-y-4">
        <div id="new-patient-section" class="hidden space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-semibold mb-1">Patient Name</label>
              <input name="new_patient_name" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Phone</label>
              <input name="new_patient_phone" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Email</label>
              <input name="new_patient_email" type="email" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-semibold mb-1">Pet Name</label>
              <input name="new_pet_name" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Pet Type</label>
              <input name="new_pet_type" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Breed</label>
              <select name="new_pet_breed" class="breed-select w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
                <option value="">Select breed</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Gender</label>
              <input name="new_pet_gender" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
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
            <label class="block text-sm font-semibold mb-1">Date</label>
            <input name="scheduled_date" type="date" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Available Slots</label>
            <select name="scheduled_time" id="slot-select" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-teal-500">
              <option value="">Select a time slot</option>
            </select>
            <p id="slot-hint" class="text-xs text-slate-500 mt-1">
              Select a doctor and date first to load available slots.
            </p>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
          <button type="button" data-close class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold">Save Booking</button>
        </div>
      </div>
    </form>
  </div>
</div>

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

<div class="pm-overlay" id="record-modal">
  <div class="pm-modal record-modal" role="dialog" aria-modal="true">
    <div class="record-header">
      <div>
        <div class="record-title">Close Consultation</div>
        <div id="record-modal-patient" class="record-patient">Patient | -</div>
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
            <label class="record-label" for="temperature">Temperature (C)</label>
            <input id="temperature" name="temperature" type="text" class="record-input" placeholder="Temperature (C)">
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
        <div class="record-field meds-shell">
          <div class="meds-head">
            <div>
              <div class="record-label" style="margin-bottom:2px">Medicines (structured)</div>
              <div class="meds-hint">Type medicine name → choose frequency & timing → set dosage and duration. Data is saved for each medicine.</div>
            </div>
            <button class="meds-add pm-btn pm-primary" type="button" data-role="add-medicine">+ Add medicine</button>
          </div>
          <input type="hidden" id="medications-json" name="medications_json">
          <textarea id="medicines-text" name="medicines" class="record-input record-textarea meds-hidden-text" style="display:none"></textarea>
          <div id="medications-empty" class="meds-empty">No medicines added yet. Click “Add medicine”.</div>
          <div id="medications-list" class="meds-list"></div>
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
      </div>

      <div class="record-actions">
        <button type="button" class="pm-btn pm-ghost" data-role="close-record-modal">Cancel</button>
        <button type="submit" class="pm-btn pm-primary">Close &amp; Share</button>
      </div>
    </form>
  </div>
</div>

<script src="{{ url('vertical/assets/js/jquery.min.js') }}"></script>
<script src="{{ url('vertical/assets/plugins/select2/js/select2.min.js') }}"></script>
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
    medicationsList: document.getElementById('medications-list'),
    medicationsEmpty: document.getElementById('medications-empty'),
    medicationsJson: document.getElementById('medications-json'),
    medicinesText: document.getElementById('medicines-text'),
    addMedicineBtn: document.querySelector('[data-role="add-medicine"]'),
    caseSeverity: document.getElementById('case-severity'),
    criticalSections: Array.from(document.querySelectorAll('[data-critical]')),
    petModal: document.getElementById('pet-modal'),
    petForm: document.getElementById('pet-form'),
    petPatient: document.getElementById('pet-modal-patient'),
    petUserInput: document.getElementById('pet-user-id'),
  };

  let lastRecordError = null;

  const MED_FREQUENCIES = [
    { value: 'OD (Once daily)', label: 'OD (Once daily)' },
    { value: 'BD (Twice daily)', label: 'BD (Twice daily)' },
    { value: 'TDS (3 times)', label: 'TDS (3 times)' },
    { value: 'QID (4 times)', label: 'QID (4 times)' },
  ];
  const MED_TIMINGS = [
    { value: 'Morning', label: 'Morning' },
    { value: 'Afternoon', label: 'Afternoon' },
    { value: 'Evening', label: 'Evening' },
    { value: 'Night', label: 'Night' },
  ];
  const MED_FOOD = [
    { value: 'Before food (AC)', label: 'Before food (AC)' },
    { value: 'After food (PC)', label: 'After food (PC)' },
    { value: 'With food', label: 'With food' },
    { value: 'Empty stomach', label: 'Empty stomach' },
  ];
  let medications = [];

  const cleanTimings = (value) => Array.isArray(value)
    ? Array.from(new Set(value.map((t) => t && t.toString().trim()).filter(Boolean)))
    : [];

  function newMedication(initial = {}) {
    return {
      name: '',
      dose: '',
      frequency: '',
      duration: '',
      route: '',
      notes: '',
      timings: [],
      food_relation: '',
      ...initial,
    };
  }

  function medPreviewLine(med) {
    const parts = [];
    if (med.name) parts.push(med.name);
    if (med.dose) parts.push(med.dose);
    if (med.frequency) parts.push(med.frequency);
    const timings = cleanTimings(med.timings);
    if (timings.length) parts.push(`Timing: ${timings.join(', ')}`);
    if (med.food_relation) parts.push(med.food_relation);
    if (med.duration) parts.push(`Duration: ${med.duration}`);
    if (med.notes) parts.push(`Notes: ${med.notes}`);
    return parts.filter(Boolean).join(' • ') || 'Fill in details to see prescription';
  }

  function normalizeMedicationState(raw) {
    const meds = normalizeMedications(raw);
    if (!meds.length) return [];
    return meds.map((med) => newMedication({
      name: med.name || med.medicine || med.title || '',
      dose: med.dose || '',
      frequency: med.frequency || '',
      duration: med.duration || '',
      route: med.route || '',
      notes: med.notes || '',
      timings: cleanTimings(med.timings || med.timing || []),
      food_relation: med.food_relation || med.food || '',
    })).filter((m) => medPreviewLine(m).trim() !== 'Fill in details to see prescription');
  }

  function syncMedicationPayload() {
    const payload = medications
      .map((med) => ({
        ...med,
        timings: cleanTimings(med.timings),
        food_relation: (med.food_relation || '').trim(),
      }))
      .filter((med) => {
        return Boolean(
          (med.name || '').trim()
          || (med.dose || '').trim()
          || (med.frequency || '').trim()
          || (med.duration || '').trim()
          || cleanTimings(med.timings).length
          || (med.food_relation || '').trim()
          || (med.notes || '').trim()
        );
      });

    if (els.medicationsJson) {
      els.medicationsJson.value = payload.length ? JSON.stringify(payload) : '';
    }

    if (els.medicinesText) {
      const fallbackLines = payload.map((med) => medPreviewLine(med));
      els.medicinesText.value = fallbackLines.join(';\n');
    }

    if (els.medicationsEmpty) {
      els.medicationsEmpty.style.display = payload.length ? 'none' : 'block';
    }
  }

  function buildChipRow(label, options, { multi = false, med, field, onChange }) {
    const row = document.createElement('div');
    row.className = 'meds-chipRow';
    const lab = document.createElement('div');
    lab.className = 'meds-chipLabel';
    lab.textContent = label;
    const group = document.createElement('div');
    group.className = 'meds-chipGroup';
    options.forEach((opt) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      const isActive = multi
        ? cleanTimings(med[field]).includes(opt.value)
        : (med[field] || '') === opt.value;
      btn.className = 'meds-chip' + (isActive ? ' is-active' : '');
      btn.textContent = opt.label;
      btn.addEventListener('click', () => {
        if (multi) {
          const next = new Set(cleanTimings(med[field]));
          if (next.has(opt.value)) {
            next.delete(opt.value);
          } else {
            next.add(opt.value);
          }
          const arr = Array.from(next);
          med[field] = arr;
          btn.classList.toggle('is-active');
          onChange(arr);
        } else {
          med[field] = opt.value;
          group.querySelectorAll('.meds-chip').forEach((chip) => chip.classList.remove('is-active'));
          btn.classList.add('is-active');
          onChange(opt.value);
        }
      });
      group.appendChild(btn);
    });
    row.appendChild(lab);
    row.appendChild(group);
    return row;
  }

  function buildMedicationCard(med, index) {
    const card = document.createElement('div');
    card.className = 'meds-card';
    let preview = null;

    const row = document.createElement('div');
    row.className = 'meds-row';

    const fields = [
      { label: 'Medicine Name', field: 'name', placeholder: 'Start typing medicine name...' },
      { label: 'Dosage', field: 'dose', placeholder: 'e.g., 1 tab' },
      { label: 'Duration', field: 'duration', placeholder: 'e.g., 5 days' },
    ];

    fields.forEach((cfg) => {
      const wrap = document.createElement('div');
      wrap.className = 'meds-field';
      const lab = document.createElement('label');
      lab.textContent = cfg.label;
      const input = document.createElement('input');
      input.type = 'text';
      input.value = med[cfg.field] || '';
      input.placeholder = cfg.placeholder;
      input.className = 'record-input';
      input.addEventListener('input', (event) => {
        med[cfg.field] = event.target.value;
        if (preview) preview.textContent = medPreviewLine(med);
        syncMedicationPayload();
      });
      wrap.appendChild(lab);
      wrap.appendChild(input);
      row.appendChild(wrap);
    });

    card.appendChild(row);

    const freqRow = buildChipRow('Frequency', MED_FREQUENCIES, {
      med,
      field: 'frequency',
      onChange: () => { preview.textContent = medPreviewLine(med); syncMedicationPayload(); },
    });
    const timeRow = buildChipRow('Timing', MED_TIMINGS, {
      med,
      field: 'timings',
      multi: true,
      onChange: () => { preview.textContent = medPreviewLine(med); syncMedicationPayload(); },
    });
    const foodRow = buildChipRow('Food Relation', MED_FOOD, {
      med,
      field: 'food_relation',
      onChange: () => { preview.textContent = medPreviewLine(med); syncMedicationPayload(); },
    });

    card.appendChild(freqRow);
    card.appendChild(timeRow);
    card.appendChild(foodRow);

    preview = document.createElement('div');
    preview.className = 'meds-preview';
    preview.textContent = medPreviewLine(med);
    card.appendChild(preview);

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'meds-remove';
    removeBtn.textContent = 'Remove';
    removeBtn.addEventListener('click', () => {
      medications.splice(index, 1);
      renderMedicationCards();
      syncMedicationPayload();
    });
    card.appendChild(removeBtn);

    return card;
  }

  function renderMedicationCards() {
    if (!els.medicationsList) return;
    els.medicationsList.innerHTML = '';
    medications.forEach((med, idx) => {
      els.medicationsList.appendChild(buildMedicationCard(med, idx));
    });
    if (els.medicationsEmpty) {
      els.medicationsEmpty.style.display = medications.length ? 'none' : 'block';
    }
  }

  function addMedication(prefill = {}) {
    medications.push(newMedication(prefill));
    renderMedicationCards();
    syncMedicationPayload();
  }

  function resetMedications() {
    medications = [];
    renderMedicationCards();
    syncMedicationPayload();
  }

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
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    const opts = withTime ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' };
    return new Intl.DateTimeFormat(undefined, opts).format(date);
  }

  function normalizeMedications(raw) {
    if (!raw) return [];
    if (Array.isArray(raw)) return raw;
    if (typeof raw === 'string') {
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch (_) {
        return [];
      }
    }
    return [];
  }

  function formatMedicationList(raw) {
    const meds = normalizeMedications(raw);
    if (!meds.length) return '';
    const items = meds.map((med) => {
      if (!med) return '';
      if (typeof med === 'string') {
        const line = escapeHtml(med);
        return line ? `<li>${line}</li>` : '';
      }
      const parts = [];
      const name = med.name || med.medicine || med.title;
      if (name) parts.push(escapeHtml(name));
      if (med.dose) parts.push(`Dose: ${escapeHtml(med.dose)}`);
      if (med.frequency) parts.push(`Frequency: ${escapeHtml(med.frequency)}`);
      const timings = Array.isArray(med.timings) ? med.timings : (med.timing ? [med.timing] : []);
      if (timings.length) parts.push(`Timing: ${escapeHtml(timings.join(', '))}`);
      if (med.food_relation || med.food) parts.push(`Food: ${escapeHtml(med.food_relation || med.food)}`);
      if (med.duration) parts.push(`Duration: ${escapeHtml(med.duration)}`);
      if (med.route) parts.push(`Route: ${escapeHtml(med.route)}`);
      if (med.notes) parts.push(`Notes: ${escapeHtml(med.notes)}`);
      const line = parts.filter(Boolean).join(' | ');
      return line ? `<li>${line}</li>` : '';
    }).filter(Boolean);
    if (!items.length) return '';
    return `<ul class="pm-record-list">${items.join('')}</ul>`;
  }

  function buildRecordPreview(rec) {
    if (!rec?.url) return '';
    const url = escapeHtml(rec.url);
    const fileName = escapeHtml(rec.file_name || 'Document');
    const mime = String(rec.mime_type || '').toLowerCase();
    if (mime.startsWith('image/')) {
      return `<div class="pm-record-media"><img src="${url}" alt="${fileName}" loading="lazy"></div>`;
    }
    if (mime === 'application/pdf') {
      return `<div class="pm-record-media"><iframe src="${url}" title="${fileName}"></iframe></div>`;
    }
    return `<div class="pm-record-media"><a class="pm-record-link" href="${url}" target="_blank" rel="noopener">View document</a><div class="pm-record-file">${fileName}</div></div>`;
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
    return null;
  }

  function getPatientPets(patient) {
    if (!patient) return [];
    if (Array.isArray(patient.pets) && patient.pets.length) {
      return patient.pets;
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
      els.list.appendChild(createEmptyRow('Loading patients...'));
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

      const primaryPet = getPrimaryPet(patient);

      const avatar = document.createElement('div');
      avatar.className = 'pm-avatar';
      avatar.textContent = String(primaryPet?.name || patient.name || '?').charAt(0).toUpperCase();

      const info = document.createElement('div');
      info.className = 'pm-info';
      const name = document.createElement('div');
      name.className = 'pm-name';
      name.textContent = `${patient.name || 'Patient'}  #${patient.id}`;
      const meta = document.createElement('div');
      meta.className = 'pm-meta';
      const petMeta = primaryPet
        ? `${escapeHtml(primaryPet.name || 'Pet')} | ${escapeHtml(primaryPet.breed || 'Breed -')}`
        : 'No pets on file';
      meta.innerHTML = `${petMeta}<br>${escapeHtml(patient.phone || 'Phone -')} | ${escapeHtml(patient.email || 'Email -')}`;
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
      els.profileAvatar.textContent = 'CW';
      els.profileName.textContent = 'Select a patient';
      els.profileSub.textContent = 'Patient and pet details will appear here.';
      els.profileMeta.textContent = '';
      els.statRecords.textContent = '-';
      els.statLastRecord.textContent = 'Last upload -';
      els.statContact.textContent = '-';
      els.statEmail.textContent = '-';
      els.recordCount.textContent = '-';
      renderPetSelect(null);
      renderRecords();
      renderPets();
      return;
    }

    const primaryPet = getPrimaryPet(patient);

    els.profileAvatar.textContent = String(primaryPet?.name || patient.name || '?').charAt(0).toUpperCase();
    els.profileName.textContent = patient.name || 'Patient';
    if (primaryPet) {
      const petName = primaryPet.name || 'Pet';
      const petBreed = primaryPet.breed || 'Breed -';
      const petGender = primaryPet.gender || primaryPet.pet_gender || 'Gender -';
      const petAge = primaryPet.pet_age ?? primaryPet.age;
      const petAgeLabel = (petAge || petAge === 0) ? petAge : '-';
      els.profileSub.textContent = `${petName} | ${petBreed} | ${petGender} | Age: ${petAgeLabel}`;
    } else {
      els.profileSub.textContent = 'No pets on file.';
    }
    els.profileMeta.textContent = `Phone: ${patient.phone || '-'} | Email: ${patient.email || '-'}`;
    const cachedRecords = state.records.get(Number(patient.id));
    const recordTotal = Array.isArray(cachedRecords) ? cachedRecords.length : (patient.records_count || 0);
    els.statRecords.textContent = `${recordTotal}`;
    const latestRecord = Array.isArray(cachedRecords) && cachedRecords.length ? cachedRecords[0]?.uploaded_at : patient.last_record_at;
    els.statLastRecord.textContent = latestRecord ? `Last upload ${formatDate(latestRecord)}` : 'No uploads yet';
    els.statContact.textContent = patient.phone || '-';
    els.statEmail.textContent = patient.email || '-';
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
      if (els.petCount) els.petCount.textContent = '-';
      return;
    }

    const pets = Array.isArray(patient.pets) ? patient.pets : [];

    if (!pets.length) {
      els.petEmpty.textContent = 'No pets yet for this patient.';
      els.petEmpty.style.display = 'block';
      if (els.petCount) els.petCount.textContent = '0 pets';
      return;
    }

    els.petEmpty.style.display = 'none';
    pets.forEach((pet) => {
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
      meta.textContent = metaParts.join(' | ') || 'Details not provided';
      body.appendChild(title);
      body.appendChild(meta);
      wrap.appendChild(avatar);
      wrap.appendChild(body);
      const petId = Number(pet.id ?? pet.pet_id);
      if (Number.isFinite(petId)) {
        const actions = document.createElement('div');
        actions.className = 'pm-petActions';
        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'pm-petDelete';
        deleteBtn.textContent = 'Delete';
        deleteBtn.setAttribute('aria-label', `Delete ${pet.name || pet.pet_name || 'pet'}`);
        deleteBtn.addEventListener('click', (event) => {
          event.stopPropagation();
          handlePetDelete({ ...pet, id: petId }, patient);
        });
        actions.appendChild(deleteBtn);
        wrap.appendChild(actions);
      }
      els.petList.appendChild(wrap);
    });
    if (els.petCount) {
      els.petCount.textContent = `${pets.length} pet${pets.length === 1 ? '' : 's'}`;
    }
  }

  async function handlePetDelete(pet, patient) {
    if (!pet?.id) return;
    const petName = pet.name || pet.pet_name || `Pet #${pet.id}`;
    const patientName = patient?.name || 'this patient';
    const result = await Swal.fire({
      icon: 'warning',
      title: `Delete ${petName}?`,
      text: `This will remove ${petName} from ${patientName}.`,
      showCancelButton: true,
      confirmButtonText: 'Delete',
      confirmButtonColor: '#ef4444',
    });
    if (!result.isConfirmed) return;
    try {
      await request(`${API_BASE}/pets/${pet.id}`, { method: 'DELETE' });
      Swal.fire({ icon: 'success', title: 'Pet deleted', timer: 1500, showConfirmButton: false });
      if (patient?.id || state.selectedId) {
        state.selectedId = Number(patient?.id ?? state.selectedId);
      }
      await loadPatients();
    } catch (error) {
      Swal.fire({ icon: 'error', title: 'Delete failed', text: error.message || 'Request failed' });
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
      els.recordCount.textContent = '-';
      if (els.statRecords) {
        els.statRecords.textContent = '-';
      }
      return;
    }

    if (state.loadingRecords) {
      els.recordEmpty.textContent = 'Loading records...';
      els.recordEmpty.style.display = 'block';
      els.recordEmpty.style.color = 'var(--pm-muted)';
      els.recordCount.textContent = '-';
      if (els.statRecords) {
        els.statRecords.textContent = '-';
      }
      return;
    }

    if (lastRecordError) {
      els.recordEmpty.textContent = lastRecordError;
      els.recordEmpty.style.display = 'block';
      els.recordEmpty.style.color = '#b91c1c';
      els.recordCount.textContent = '-';
      if (els.statRecords) {
        els.statRecords.textContent = '-';
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
        detailPairs.push({ label: 'Visit', value: `${escapeHtml(prescription.visit_category || '-')} | ${escapeHtml(prescription.case_severity || '-')}` });
      }
      if (prescription.visit_notes) {
        detailPairs.push({ label: 'Visit notes', value: escapeHtml(prescription.visit_notes) });
      }
      if (prescription.temperature || prescription.weight || prescription.heart_rate) {
        const temp = prescription.temperature ? `Temp: ${escapeHtml(prescription.temperature)}${prescription.temperature_unit ? escapeHtml(prescription.temperature_unit) : ''}` : null;
        const wt = prescription.weight ? `Weight: ${escapeHtml(prescription.weight)}kg` : null;
        const hr = prescription.heart_rate ? `Heart: ${escapeHtml(prescription.heart_rate)}` : null;
        detailPairs.push({ label: 'Vitals', value: [temp, wt, hr].filter(Boolean).join(' | ') });
      }
      if (prescription.exam_notes) {
        detailPairs.push({ label: 'Exam', value: escapeHtml(prescription.exam_notes) });
      }
      if (prescription.diagnosis || prescription.diagnosis_status) {
        detailPairs.push({ label: 'Diagnosis', value: `${escapeHtml(prescription.diagnosis || '-')} (${escapeHtml(prescription.diagnosis_status || '-')})` });
      }
      if (prescription.treatment_plan) {
        detailPairs.push({ label: 'Treatment', value: escapeHtml(prescription.treatment_plan) });
      }
      if (prescription.home_care) {
        detailPairs.push({ label: 'Home care', value: escapeHtml(prescription.home_care) });
      }
      const medsHtml = formatMedicationList(prescription.medications_json);
      if (medsHtml) {
        detailPairs.push({ label: 'Medicines', value: medsHtml });
      }
      if (prescription.follow_up_date || prescription.follow_up_type) {
        const fuParts = [];
        if (prescription.follow_up_date) fuParts.push(`Date: ${escapeHtml(prescription.follow_up_date)}`);
        if (prescription.follow_up_type) fuParts.push(`Type: ${escapeHtml(prescription.follow_up_type)}`);
        detailPairs.push({ label: 'Follow-up', value: fuParts.join(' | ') });
      }
      if (petLabel) {
        detailPairs.unshift({ label: 'Pet', value: escapeHtml(petLabel) });
      }
      const detailHtml = detailPairs.length
        ? `<div class="pm-record-details">${detailPairs.map(pair => `<div class="pm-record-row"><div class="pm-record-label">${pair.label}</div><div class="pm-record-value">${pair.value}</div></div>`).join('')}</div>`
        : '';
      const previewHtml = buildRecordPreview(rec);
      const tags = [];
      if (prescription.case_severity) tags.push(`<span class="pm-tag-soft">${escapeHtml(prescription.case_severity)}</span>`);
      if (prescription.visit_category) tags.push(`<span class="pm-tag-soft">${escapeHtml(prescription.visit_category)}</span>`);

      wrap.innerHTML = `
        <div class="pm-record-head">
          <div>
            <div class="pm-record-title">${escapeHtml(rec.file_name || 'Medical file')}</div>
            <div class="pm-record-meta">${formatDate(rec.uploaded_at)}${rec.doctor_id ? ` | Doctor #${rec.doctor_id}` : ''}${petLabel ? ` | Pet: ${escapeHtml(petLabel)}` : ''}</div>
          </div>
          ${tags.length ? `<div class="pm-record-tags">${tags.join('')}</div>` : ''}
        </div>
        <div class="pm-record-notes">${escapeHtml(rec.notes || 'No notes')}</div>
        ${detailHtml}
        ${previewHtml}
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
      opt.textContent = `${pet.name || pet.pet_name || 'Pet'}${pet.breed ? ` | ${pet.breed}` : ''}`;
      if (pet.gender) opt.textContent += ` | ${pet.gender}`;
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
    resetMedications();
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
    mapValue('record-pet', prescription.pet_id ?? rec.pet_id ?? '');
    medications = normalizeMedicationState(prescription.medications_json || []);
    renderMedicationCards();
    syncMedicationPayload();
    if (!medications.length) {
      addMedication();
    }
    toggleCriticalSections(els.caseSeverity?.value || 'general');
  }

  function openUploadModal() {
    if (!state.selectedId) {
      Swal.fire({ icon: 'info', title: 'Select a patient', text: 'Pick a patient from the list before uploading.' });
      return;
    }
    const patient = state.patients.find((p) => Number(p.id) === Number(state.selectedId));
    if (patient) {
      els.modalPatient.textContent = `${patient.name || 'Patient'} | #${patient.id}`;
      const primaryPet = getPrimaryPet(patient);
      const petAge = primaryPet?.pet_age ?? primaryPet?.age;
      const petLine = primaryPet
        ? [primaryPet.name || 'Pet', primaryPet.breed, (petAge || petAge === 0) ? `Age: ${petAge}` : null]
          .filter(Boolean)
          .join(' | ')
        : '';
      if (els.modalPet) {
        els.modalPet.textContent = petLine || '';
      }
      els.modalUserInput.value = patient.id;
    }
    resetRecordForm();
    if (!medications.length) {
      addMedication();
    }
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
        els.petPatient.textContent = `${patient.name || 'Patient'} | #${patient.id}`;
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
      els.petPatient.textContent = 'Patient | -';
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
    els.addMedicineBtn?.addEventListener('click', () => addMedication());

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
      syncMedicationPayload();
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
  renderMedicationCards();
  syncMedicationPayload();
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
  const PATH_PREFIX = window.location.pathname.startsWith('/backend') ? '/backend' : '';
  const CONFIG = {
    API_BASE: `${window.location.origin}${PATH_PREFIX}/api`,
    CSRF_URL: `${window.location.origin}${PATH_PREFIX}/sanctum/csrf-cookie`,
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
  const BREEDS_API_URL_PRIMARY = `${CONFIG.API_BASE}/dog-breeds/all`;
  const BREEDS_API_URL_FALLBACK = 'https://snoutiq.com/backend/api/dog-breeds/all';
  const SELECT2_ASSETS = {
    jquery: @json(url('vertical/assets/js/jquery.min.js')),
    jqueryCdn: 'https://code.jquery.com/jquery-3.7.1.min.js',
    select2: @json(url('vertical/assets/plugins/select2/js/select2.min.js')),
    select2Cdn: 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
  };

  const bookingModal = document.getElementById('booking-modal');
  const bookingForm = document.getElementById('booking-form');
  const patientSelect = document.getElementById('patient-select');
  const patientSearchInput = document.getElementById('patient-search');
  const patientSearchBlock = document.getElementById('patient-search-block');
  const patientResults = document.getElementById('patient-results');
  const petSelect = document.getElementById('pet-select');
  const doctorSelect = document.getElementById('booking-doctor-select');
  const slotSelect = document.getElementById('slot-select');
  const slotHint = document.getElementById('slot-hint');
  const inlineBreedSelect = bookingForm?.elements['inline_pet_breed'];
  const newBreedSelect = bookingForm?.elements['new_pet_breed'];
  const breedSelects = [inlineBreedSelect, newBreedSelect].filter(Boolean);
  const modeButtons = Array.from(document.querySelectorAll('[data-patient-mode]'));
  const existingSection = document.getElementById('existing-patient-section');
  const newSection = document.getElementById('new-patient-section');
  const existingDetails = document.getElementById('existing-patient-details');
  const bookingSubmitFields = document.getElementById('booking-submit-fields');
  const bookingNew = document.querySelector('[data-role="booking-new"]');
  const bookingExisting = document.querySelector('[data-role="booking-existing"]');

  const STORED_FULL = STORED_AUTH_FULL || {};

  let PATIENTS = [];
  let CURRENT_PATIENT = null;
  let PATIENT_MODE = 'new';
  let PREFERRED_PATIENT_ID = null;
  let BREED_LIST = [];
  let select2Loader = null;

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
    const dateField = bookingForm?.elements['scheduled_date'];
    if (dateField && !dateField.value) {
      dateField.value = new Date().toISOString().split('T')[0];
    }
    initBreedSelect2();
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
    setPatientMode('new');
  }

  function setPatientMode(mode) {
    PATIENT_MODE = mode;
    modeButtons.forEach((btn) => btn.classList.toggle('active', btn.dataset.patientMode === mode));
    existingSection?.classList.toggle('hidden', mode !== 'existing');
    newSection?.classList.toggle('hidden', mode !== 'new');
    updateBookingSections();
  }

  function updateBookingSections() {
    const isExisting = PATIENT_MODE === 'existing';
    const hasPatient = Boolean(patientSelect?.value);
    const hideSearch = isExisting && hasPatient;
    existingDetails?.classList.toggle('hidden', !isExisting || !hasPatient);
    bookingSubmitFields?.classList.toggle('hidden', isExisting && !hasPatient);
    patientSearchBlock?.classList.toggle('hidden', hideSearch);
  }

  const openBookingWithMode = (mode) => {
    openBooking();
    setPatientMode(mode);
  };

  modeButtons.forEach((btn) => btn.addEventListener('click', () => setPatientMode(btn.dataset.patientMode)));
  closeButtons.forEach((btn) => btn.addEventListener('click', closeBooking));
  bookingNew?.addEventListener('click', () => openBookingWithMode('new'));
  bookingExisting?.addEventListener('click', () => openBookingWithMode('existing'));

  function normalizePhone(...candidates) {
    for (const value of candidates) {
      if (typeof value !== 'string') continue;
      const trimmed = value.trim();
      if (!trimmed || trimmed.includes('@')) continue;
      const digits = trimmed.replace(/\D+/g, '');
      if (!digits) continue;
      if (digits.startsWith('91') && digits.length >= 12) {
        return digits.slice(0, 12);
      }
      if (digits.length === 10) {
        return `91${digits}`;
      }
      return digits;
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
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select patient';
    patientSelect.appendChild(placeholder);
    PATIENTS.forEach((patient) => {
      const option = document.createElement('option');
      option.value = patient.id;
      option.textContent = `${patient.name || 'Patient'} | ${patient.phone || patient.email || ''}`;
      patientSelect.appendChild(option);
    });
    const targetId = PREFERRED_PATIENT_ID && PATIENTS.some((p) => String(p.id) === String(PREFERRED_PATIENT_ID))
      ? PREFERRED_PATIENT_ID
      : '';
    PREFERRED_PATIENT_ID = null;
    patientSelect.value = targetId || '';
    handlePatientChange();
  }

  function selectPatientId(patientId) {
    if (!patientSelect) return;
    patientSelect.value = patientId;
    CURRENT_PATIENT = PATIENTS.find((p) => String(p.id) === String(patientId)) || null;
    PREFERRED_PATIENT_ID = patientId;
    handlePatientChange();
  }

  function renderPatientResults() {
    if (!patientResults) return;
    patientResults.innerHTML = '';

    if (!PATIENTS.length) {
      patientResults.innerHTML = '<div class="patient-results-empty">No patients found.</div>';
      return;
    }

    PATIENTS.forEach((patient) => {
      const card = document.createElement('div');
      const isSelected = String(patientSelect?.value || '') === String(patient.id);
      card.className = `patient-result${isSelected ? ' is-selected' : ''}`;

      const info = document.createElement('div');
      const name = document.createElement('div');
      name.className = 'patient-result-name';
      name.textContent = patient.name || 'Patient';

      const meta = document.createElement('div');
      meta.className = 'patient-result-meta';
      meta.textContent = patient.phone || patient.email || '';

      const tags = document.createElement('div');
      tags.className = 'patient-result-tags';
      const pets = Array.isArray(patient.pets) ? patient.pets : [];
      if (pets.length) {
        pets.slice(0, 2).forEach((pet) => {
          const tag = document.createElement('span');
          tag.className = 'patient-result-tag';
          tag.textContent = `${pet.name || pet.pet_name || 'Pet'} (${pet.species || pet.pet_type || pet.type || 'Pet'})`;
          tags.appendChild(tag);
        });
      }

      info.appendChild(name);
      info.appendChild(meta);
      if (tags.childElementCount) info.appendChild(tags);

      const action = document.createElement('button');
      action.type = 'button';
      action.className = 'patient-result-action';
      action.textContent = 'Select ->';
      action.addEventListener('click', (event) => {
        event.stopPropagation();
        selectPatientId(patient.id);
      });

      card.appendChild(info);
      card.appendChild(action);
      card.addEventListener('click', () => selectPatientId(patient.id));

      patientResults.appendChild(card);
    });
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
      option.textContent = `${pet.pet_name || pet.name || 'Pet'} | ${pet.pet_type || pet.species || ''}`;
      option.dataset.petName = pet.pet_name || pet.name || '';
      petSelect.appendChild(option);
    });
  }

  function toTitleCase(value) {
    return String(value || '')
      .split(/[\s_-]+/)
      .filter(Boolean)
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  }

  function buildBreedList(data) {
    const list = new Set();
    Object.entries(data || {}).forEach(([breed, subBreeds]) => {
      const breedName = toTitleCase(breed);
      if (Array.isArray(subBreeds) && subBreeds.length) {
        subBreeds.forEach((sub) => list.add(`${toTitleCase(sub)} ${breedName}`.trim()));
      } else {
        list.add(breedName);
      }
    });
    return Array.from(list).sort((a, b) => a.localeCompare(b));
  }

  function populateBreedSelect(select, breeds, placeholderText = 'Select breed') {
    if (!select) return;
    const previous = select.value;
    select.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = placeholderText;
    select.appendChild(placeholder);
    breeds.forEach((breed) => {
      const opt = document.createElement('option');
      opt.value = breed;
      opt.textContent = breed;
      select.appendChild(opt);
    });
    if (previous && breeds.includes(previous)) {
      select.value = previous;
    }
  }

  function loadScriptOnce(src, id) {
    return new Promise((resolve, reject) => {
      if (id && document.getElementById(id)) {
        resolve();
        return;
      }
      const script = document.createElement('script');
      if (id) script.id = id;
      script.src = src;
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => reject(new Error(`Failed to load ${src}`));
      document.head.appendChild(script);
    });
  }

  function ensureSelect2Ready() {
    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
      return Promise.resolve(true);
    }
    if (select2Loader) return select2Loader;
    select2Loader = (async () => {
      try {
        if (!window.jQuery || !window.jQuery.fn) {
          try {
            await loadScriptOnce(SELECT2_ASSETS.jquery, 'snoutiq-jquery');
          } catch (_) {
            await loadScriptOnce(SELECT2_ASSETS.jqueryCdn, 'snoutiq-jquery-cdn');
          }
        }
        if (!window.jQuery || !window.jQuery.fn) return false;
        if (!window.jQuery.fn.select2) {
          try {
            await loadScriptOnce(SELECT2_ASSETS.select2, 'snoutiq-select2');
          } catch (_) {
            await loadScriptOnce(SELECT2_ASSETS.select2Cdn, 'snoutiq-select2-cdn');
          }
        }
        return Boolean(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2);
      } catch (error) {
        console.error('Select2 load failed', error);
        return false;
      }
    })();
    return select2Loader;
  }

  function initBreedSelect2() {
    ensureSelect2Ready().then((ready) => {
      if (!ready || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
      const dropdownParent = bookingModal ? window.jQuery(bookingModal) : window.jQuery(document.body);
      breedSelects.forEach((select) => {
        if (!select) return;
        const $select = window.jQuery(select);
        const currentValue = select.value;
        if ($select.hasClass('select2-hidden-accessible')) {
          $select.select2('destroy');
        }
        $select.select2({
          width: '100%',
          placeholder: 'Select breed',
          allowClear: true,
          minimumResultsForSearch: 0,
          dropdownParent,
        });
        if (currentValue) {
          $select.val(currentValue).trigger('change.select2');
        }
      });
    });
  }

  async function fetchDogBreeds() {
    if (!inlineBreedSelect && !newBreedSelect) return;
    populateBreedSelect(inlineBreedSelect, [], 'Loading breeds...');
    populateBreedSelect(newBreedSelect, [], 'Loading breeds...');
    try {
      const res = await fetch(BREEDS_API_URL_PRIMARY, { headers: { Accept: 'application/json' } });
      if (!res.ok) throw new Error(`Failed to fetch breeds (${res.status})`);
      const data = await res.json();
      const breeds = buildBreedList(data?.breeds || {});
      if (!breeds.length && BREEDS_API_URL_FALLBACK) {
        throw new Error('Empty breeds list');
      }
      BREED_LIST = breeds;
      populateBreedSelect(inlineBreedSelect, BREED_LIST);
      populateBreedSelect(newBreedSelect, BREED_LIST);
      initBreedSelect2();
    } catch (error) {
      console.error('Failed to load dog breeds from primary', error);
      if (BREEDS_API_URL_FALLBACK && BREEDS_API_URL_FALLBACK !== BREEDS_API_URL_PRIMARY) {
        try {
          const res = await fetch(BREEDS_API_URL_FALLBACK, { headers: { Accept: 'application/json' } });
          if (!res.ok) throw new Error(`Failed to fetch breeds (${res.status})`);
          const data = await res.json();
          const breeds = buildBreedList(data?.breeds || {});
          BREED_LIST = breeds;
          populateBreedSelect(inlineBreedSelect, BREED_LIST);
          populateBreedSelect(newBreedSelect, BREED_LIST);
          initBreedSelect2();
          return;
        } catch (fallbackErr) {
          console.error('Fallback breed fetch failed', fallbackErr);
        }
      }
      populateBreedSelect(inlineBreedSelect, [], 'Breeds unavailable');
      populateBreedSelect(newBreedSelect, [], 'Breeds unavailable');
      initBreedSelect2();
    }
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
    renderPatientResults();
    updateBookingSections();
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
        const phone = normalizePhone(bookingForm.elements['new_patient_phone'].value);
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
    fetchDogBreeds();
  });
})();
</script>

@endsection
