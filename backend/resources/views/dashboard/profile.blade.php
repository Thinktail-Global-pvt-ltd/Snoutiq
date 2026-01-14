@extends('layouts.snoutiq-dashboard')

@section('title', 'Profile')
@section('page_title', 'Profile')

@php
    $sessionRole = session('role')
        ?? data_get(session('auth_full'), 'role')
        ?? data_get(session('user'), 'role')
        ?? 'clinic_admin';

    $candidates = [
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

    if ($sessionRole !== 'doctor') {
        array_unshift(
            $candidates,
            session('user_id'),
            data_get(session('user'), 'id')
        );
    }

    $resolvedClinicId = null;
    foreach ($candidates as $candidate) {
        if ($candidate === null || $candidate === '') {
            continue;
        }
        $num = (int) $candidate;
        if ($num > 0) {
            $resolvedClinicId = $num;
            break;
        }
    }

    $resolvedDoctorId = session('doctor_id')
        ?? data_get(session('doctor'), 'id')
        ?? data_get(session('auth_full'), 'doctor_id')
        ?? data_get(session('auth_full'), 'user.doctor_id')
        ?? data_get(session('user'), 'doctor_id');
    $resolvedDoctorId = $resolvedDoctorId ? (int) $resolvedDoctorId : null;
@endphp

@section('content')
<div
  id="profile-app"
  class="max-w-6xl mx-auto space-y-6"
  data-role="{{ $sessionRole }}"
  data-clinic-id="{{ $resolvedClinicId ?: '' }}"
  data-doctor-id="{{ $resolvedDoctorId ?: '' }}"
>
  <div class="grid gap-4 lg:grid-cols-3">
    <div class="bg-white rounded-2xl shadow border border-gray-100 p-6 lg:col-span-2">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <p class="text-xs uppercase tracking-wide text-gray-400">Clinic</p>
          <h2 id="clinic-name" class="text-2xl font-semibold text-gray-900 mt-1">—</h2>
          <p class="text-sm text-gray-500 mt-1">ID: <span id="clinic-id-label">{{ $resolvedClinicId ?: '—' }}</span></p>
        </div>
        <div class="text-right">
          <p class="text-xs uppercase tracking-wide text-gray-400">Role</p>
          <p class="text-sm font-semibold text-indigo-600">{{ ucfirst(str_replace('_',' ', $sessionRole)) }}</p>
          <p class="text-xs text-gray-500 mt-1" id="doctor-context"></p>
        </div>
      </div>
      <dl class="grid gap-4 mt-6 text-sm lg:grid-cols-3" id="clinic-summary">
        <div>
          <dt class="text-gray-400 uppercase text-[11px] tracking-wide">Email</dt>
          <dd id="summary-email" class="font-medium text-gray-900">—</dd>
        </div>
        <div>
          <dt class="text-gray-400 uppercase text-[11px] tracking-wide">Phone</dt>
          <dd id="summary-phone" class="font-medium text-gray-900">—</dd>
        </div>
        <div>
          <dt class="text-gray-400 uppercase text-[11px] tracking-wide">Pincode</dt>
          <dd id="summary-pincode" class="font-medium text-gray-900">—</dd>
        </div>
        <div class="lg:col-span-3">
          <dt class="text-gray-400 uppercase text-[11px] tracking-wide">Address</dt>
          <dd id="summary-address" class="font-medium text-gray-900">—</dd>
        </div>
      </dl>
    </div>
    <div class="rounded-2xl p-6 shadow bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex flex-col justify-between">
      <div>
        <p class="text-xs uppercase tracking-wide text-white/70">Quick actions</p>
        <h3 class="text-xl font-semibold mt-1">Stay up-to-date</h3>
        <p class="text-sm text-white/80 mt-2">Keep your clinic information fresh so pet parents can trust your profile.</p>
      </div>
      <div class="mt-6 space-y-3">
        <button id="refresh-profile" type="button" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-sm font-semibold">
          <span>Refresh profile</span>
        </button>
        @if($resolvedDoctorId)
          <button id="open-self-doctor" type="button" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white text-indigo-600 font-semibold text-sm">
            Edit my doctor profile
          </button>
        @endif
      </div>
      <div class="mt-6 rounded-2xl border border-white/20 bg-white/10 p-5 space-y-4 text-white text-left">
        <div class="space-y-1 text-center">
          <p class="text-xs uppercase tracking-wide text-white/70">Registered vet QR</p>
          <p id="clinic-qr-status" class="text-sm text-white/80">Scan to visit your SnoutIQ clinic profile.</p>
        </div>
        <div class="flex justify-center">
          <img
            id="clinic-qr-image"
            src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs="
            alt="Clinic QR code"
            class="h-32 w-32 rounded-xl bg-white/20 object-cover transition-opacity duration-200 opacity-40"
          />
        </div>
        <div class="text-center">
          <p class="text-xs uppercase tracking-wide text-white/70">Referral code</p>
          <p id="clinic-referral-code" class="text-sm font-semibold text-white">—</p>
        </div>
        <div class="space-y-3 text-center">
          <p class="text-xs text-white/70">Download a ready-to-print SnoutIQ card that highlights your clinic name.</p>
          <div class="flex flex-col items-center gap-2">
            <button
              id="clinic-qr-download"
              type="button"
              disabled
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/90 text-indigo-600 px-5 py-2 text-sm font-semibold opacity-60 pointer-events-none transition duration-150"
            >
              Download printable card
            </button>
            <a
              id="clinic-qr-link"
              href="#"
              target="_blank"
              rel="noreferrer noopener"
              class="text-xs font-semibold text-white/90 opacity-50 pointer-events-none transition-opacity duration-200"
            >
              Clinic page not yet available
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow border border-gray-100">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between px-6 py-4 border-b border-gray-100">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Clinic Details</h3>
        <p class="text-sm text-gray-500">These details appear everywhere inside SnoutIQ.</p>
      </div>
      <span id="clinic-edit-pill" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600"></span>
    </div>
    <form id="clinic-form" class="p-6 grid gap-4 md:grid-cols-2">
      <div>
        <label class="text-sm font-medium text-gray-700">Clinic Profile Title</label>
        <input name="clinic_profile" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-200" placeholder="Happy Tails Animal Hospital">
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Registered Name</label>
        <input name="name" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Email</label>
        <input name="email" type="email" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Phone</label>
        <input name="mobile" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">City</label>
        <input name="city" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Pincode</label>
        <input name="pincode" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium text-gray-700">Address</label>
        <textarea name="address" rows="2" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"></textarea>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Consultation Price (₹)</label>
        <input name="chat_price" type="number" min="0" step="1" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
      </div>
      <div class="md:col-span-2">
        <label class="text-sm font-medium text-gray-700">About the Clinic</label>
        <textarea name="bio" rows="3" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"></textarea>
      </div>
      <div class="md:col-span-2 flex justify-end gap-3">
        <button type="reset" class="px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Save changes</button>
      </div>
    </form>
  </div>

  <div class="bg-white rounded-2xl shadow border border-gray-100">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between px-6 py-4 border-b border-gray-100">
      <div>
        <h3 class="text-lg font-semibold text-gray-900">Clinic Doctors</h3>
        <p class="text-sm text-gray-500">Keep doctor credentials accurate for compliance.</p>
      </div>
      <span id="doctor-count" class="text-sm text-gray-500"></span>
    </div>
    <div id="doctor-grid" class="p-6 grid gap-4 md:grid-cols-2"></div>
    <div id="doctor-empty" class="px-6 pb-6 text-sm text-gray-500 hidden">No doctors found for this clinic.</div>
  </div>
</div>

<div id="doctor-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl w-[95%] max-w-lg p-6 relative">
    <button type="button" class="doctor-modal-close absolute top-3 right-3 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-600">×</button>
    <h3 id="doctor-modal-title" class="text-xl font-semibold text-gray-900 mb-2">Edit Doctor</h3>
    <p id="doctor-modal-subtitle" class="text-sm text-gray-500 mb-4">Update doctor credentials</p>
    <form id="doctor-form" class="space-y-4">
      <input type="hidden" name="doctor_id">
      <div>
        <label class="text-sm font-medium text-gray-700">Full Name</label>
        <input name="doctor_name" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" required>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700">Email</label>
        <input name="doctor_email" type="email" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
      </div>
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="text-sm font-medium text-gray-700">Phone</label>
          <input name="doctor_mobile" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700">Consultation Price (₹)</label>
          <input name="doctors_price" type="number" min="0" step="1" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
        </div>
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" class="doctor-modal-close px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Save Doctor</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ORIGIN = window.location.origin;
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
  const API_BASE = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
  const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const appEl = document.getElementById('profile-app');
  const ctx = {
    role: appEl?.dataset?.role || 'clinic_admin',
    clinicId: Number(appEl?.dataset?.clinicId || '') || null,
    doctorId: Number(appEl?.dataset?.doctorId || '') || null,
  };
  const CLINIC_WEB_PAGE_BASE = 'https://snoutiq.com/backend/vets';
  const QR_SERVICE_BASE = 'https://api.qrserver.com/v1/create-qr-code/';
  const QR_PLACEHOLDER_IMAGE = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';

  const els = {
    clinicName: document.getElementById('clinic-name'),
    clinicId: document.getElementById('clinic-id-label'),
    summaryEmail: document.getElementById('summary-email'),
    summaryPhone: document.getElementById('summary-phone'),
    summaryPincode: document.getElementById('summary-pincode'),
    summaryAddress: document.getElementById('summary-address'),
    doctorContext: document.getElementById('doctor-context'),
    clinicForm: document.getElementById('clinic-form'),
    clinicEditPill: document.getElementById('clinic-edit-pill'),
    refreshBtn: document.getElementById('refresh-profile'),
    clinicQrImage: document.getElementById('clinic-qr-image'),
    clinicQrLink: document.getElementById('clinic-qr-link'),
    clinicQrStatus: document.getElementById('clinic-qr-status'),
    clinicQrDownload: document.getElementById('clinic-qr-download'),
    clinicReferralCode: document.getElementById('clinic-referral-code'),
    doctorGrid: document.getElementById('doctor-grid'),
    doctorEmpty: document.getElementById('doctor-empty'),
    doctorCount: document.getElementById('doctor-count'),
    doctorModal: document.getElementById('doctor-modal'),
    doctorForm: document.getElementById('doctor-form'),
    doctorModalTitle: document.getElementById('doctor-modal-title'),
    doctorModalSubtitle: document.getElementById('doctor-modal-subtitle'),
    openSelfDoctor: document.getElementById('open-self-doctor'),
  };

  let profilePayload = null;
  let clinicEditable = false;
  let editableDoctorIds = [];
  let currentClinicPageUrl = '';
  let currentClinicName = 'SnoutIQ Clinic';
  const DOWNLOAD_READY_LABEL = 'Download printable card';
  const DOWNLOAD_LOADING_LABEL = 'Preparing template...';
  const DOWNLOAD_DISABLED_LABEL = 'QR coming soon';

  function formatValue(value, fallback = '—') {
    if (value === null || value === undefined) return fallback;
    const str = String(value).trim();
    return str === '' ? fallback : str;
  }

  function formatCurrency(value) {
    if (value === null || value === undefined || value === '') {
      return '—';
    }
    const num = Number(value);
    if (Number.isNaN(num)) return formatValue(value);
    return `₹${num.toLocaleString('en-IN')}`;
  }

  function sanitizeFileName(value) {
    return String(value ?? 'clinic')
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'clinic';
  }

  function setDownloadButtonState(isEnabled, label) {
    if (!els.clinicQrDownload) return;
    const text = label ?? (isEnabled ? DOWNLOAD_READY_LABEL : DOWNLOAD_DISABLED_LABEL);
    els.clinicQrDownload.disabled = !isEnabled;
    els.clinicQrDownload.textContent = text;
    els.clinicQrDownload.classList.toggle('opacity-60', !isEnabled);
    els.clinicQrDownload.classList.toggle('pointer-events-none', !isEnabled);
  }

  function loadImageFromBlob(blob) {
    return new Promise((resolve, reject) => {
      const image = new Image();
      image.decoding = 'async';
      image.onload = () => {
        URL.revokeObjectURL(image.src);
        resolve(image);
      };
      image.onerror = reject;
      image.src = URL.createObjectURL(blob);
    });
  }

  function drawRoundedRectangle(ctx, x, y, width, height, radius) {
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.lineTo(x + width - radius, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
    ctx.lineTo(x + width, y + height - radius);
    ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
    ctx.lineTo(x + radius, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
    ctx.lineTo(x, y + radius);
    ctx.quadraticCurveTo(x, y, x + radius, y);
    ctx.closePath();
    ctx.fill();
  }

  function buildClinicPageUrl(slug) {
    if (!slug) return '';
    const trimmed = String(slug).trim();
    if (!trimmed) return '';
    return `${CLINIC_WEB_PAGE_BASE}/${encodeURIComponent(trimmed)}`;
  }

  function buildQrImageUrl(pageUrl) {
    if (!pageUrl) return '';
    return `${QR_SERVICE_BASE}?size=220x220&margin=12&data=${encodeURIComponent(pageUrl)}`;
  }

  async function buildDownloadableTemplate(pageUrl, clinicName) {
    const qrUrl = buildQrImageUrl(pageUrl);
    if (!qrUrl) {
      throw new Error('QR code is not ready yet.');
    }
    const response = await fetch(qrUrl);
    if (!response.ok) {
      throw new Error('Could not download the QR code.');
    }
    const qrBlob = await response.blob();
    const qrImage = await loadImageFromBlob(qrBlob);
    const width = 760;
    const height = 1100;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx2d = canvas.getContext('2d');
    const bgGradient = ctx2d.createLinearGradient(0, 0, width, height);
    bgGradient.addColorStop(0, '#0f172a');
    bgGradient.addColorStop(1, '#4338ca');
    ctx2d.fillStyle = bgGradient;
    ctx2d.fillRect(0, 0, width, height);

    ctx2d.fillStyle = 'rgba(255,255,255,0.08)';
    drawRoundedRectangle(ctx2d, 40, 40, width - 80, 260, 34);

    ctx2d.fillStyle = '#e0e7ff';
    ctx2d.font = '700 32px Inter, system-ui, sans-serif';
    ctx2d.fillText('SnoutIQ Clinic', 60, 110);

    ctx2d.fillStyle = '#ffffff';
    ctx2d.font = '600 44px Inter, system-ui, sans-serif';
    ctx2d.fillText(clinicName, 60, 170);

    ctx2d.font = '500 18px Inter, system-ui, sans-serif';
    ctx2d.fillStyle = 'rgba(255,255,255,0.75)';
    ctx2d.fillText('Scan to visit your clinic profile on SnoutIQ', 60, 212);

    ctx2d.strokeStyle = 'rgba(255,255,255,0.35)';
    ctx2d.lineWidth = 1;
    ctx2d.beginPath();
    ctx2d.moveTo(60, 228);
    ctx2d.lineTo(width - 60, 228);
    ctx2d.stroke();

    const qrSize = 360;
    const qrX = (width - qrSize) / 2;
    const qrY = 320;

    ctx2d.fillStyle = '#ffffff';
    drawRoundedRectangle(ctx2d, qrX - 18, qrY - 18, qrSize + 36, qrSize + 36, 32);
    ctx2d.drawImage(qrImage, qrX, qrY, qrSize, qrSize);

    ctx2d.fillStyle = 'rgba(255,255,255,0.85)';
    ctx2d.font = '600 20px Inter, system-ui, sans-serif';
    ctx2d.textAlign = 'center';
    ctx2d.fillText('Scan this code', width / 2, qrY + qrSize + 58);

    ctx2d.font = '400 16px Inter, system-ui, sans-serif';
    ctx2d.fillText('Powered by SnoutIQ', width / 2, qrY + qrSize + 82);
    ctx2d.textAlign = 'start';

    return canvas;
  }

  function updateClinicQr(slug, clinicName) {
    const pageUrl = buildClinicPageUrl(slug);
    const hasPageUrl = Boolean(pageUrl);
    currentClinicPageUrl = pageUrl;
    const candidateName = formatValue(clinicName, '');
    currentClinicName = candidateName || 'SnoutIQ Clinic';
    if (els.clinicQrStatus) {
      els.clinicQrStatus.textContent = hasPageUrl
        ? 'Scan to visit your clinic on SnoutIQ.'
        : 'QR becomes available once your clinic page is published.';
    }
    if (els.clinicQrLink) {
      if (hasPageUrl) {
        els.clinicQrLink.href = pageUrl;
        els.clinicQrLink.textContent = 'View clinic page';
      } else {
        els.clinicQrLink.removeAttribute('href');
        els.clinicQrLink.textContent = 'Clinic page not yet available';
      }
      els.clinicQrLink.classList.toggle('opacity-50', !hasPageUrl);
      els.clinicQrLink.classList.toggle('pointer-events-none', !hasPageUrl);
    }
    if (els.clinicQrImage) {
      els.clinicQrImage.src = hasPageUrl ? buildQrImageUrl(pageUrl) : QR_PLACEHOLDER_IMAGE;
      els.clinicQrImage.classList.toggle('opacity-40', !hasPageUrl);
    }
    setDownloadButtonState(hasPageUrl);
  }

  updateClinicQr(null);

  function toggleClinicForm(disabled) {
    const inputs = els.clinicForm?.querySelectorAll('input, textarea, button[type="submit"]') || [];
    inputs.forEach((node) => {
      if (node.type === 'submit') {
        node.disabled = disabled;
        node.classList.toggle('opacity-60', disabled);
      } else {
        node.disabled = disabled;
      }
    });
    if (els.clinicEditPill) {
      if (disabled) {
        els.clinicEditPill.textContent = 'View only';
        els.clinicEditPill.classList.remove('bg-green-100','text-green-700');
        els.clinicEditPill.classList.add('bg-slate-100','text-slate-600');
      } else {
        els.clinicEditPill.textContent = 'Editable';
        els.clinicEditPill.classList.remove('bg-slate-100','text-slate-600');
        els.clinicEditPill.classList.add('bg-green-100','text-green-700');
      }
    }
  }

  function fillClinicSummary(clinic) {
    const fallbackName = clinic?.clinic_profile || clinic?.name || 'Clinic';
    if (els.clinicName) els.clinicName.textContent = formatValue(fallbackName);
    if (els.summaryEmail) els.summaryEmail.textContent = formatValue(clinic?.email);
    if (els.summaryPhone) els.summaryPhone.textContent = formatValue(clinic?.mobile);
    if (els.summaryPincode) els.summaryPincode.textContent = formatValue(clinic?.pincode);
    if (els.summaryAddress) {
      const parts = [clinic?.address, clinic?.city].filter(Boolean);
      els.summaryAddress.textContent = parts.length ? parts.join(', ') : '—';
    }
  }

  function fillClinicForm(clinic) {
    if (!els.clinicForm) return;
    const setVal = (field, value) => {
      const input = els.clinicForm.elements[field];
      if (input) input.value = value ?? '';
    };
    setVal('clinic_profile', clinic?.clinic_profile ?? clinic?.name ?? '');
    setVal('name', clinic?.name ?? '');
    setVal('email', clinic?.email ?? '');
    setVal('mobile', clinic?.mobile ?? '');
    setVal('city', clinic?.city ?? '');
    setVal('pincode', clinic?.pincode ?? '');
    setVal('address', clinic?.address ?? '');
    setVal('chat_price', clinic?.chat_price ?? '');
    setVal('bio', clinic?.bio ?? '');
  }

  function renderDoctors(doctors) {
    if (!els.doctorGrid) return;
    const list = Array.isArray(doctors) ? doctors : [];
    els.doctorGrid.innerHTML = '';
    if (els.doctorCount) {
      els.doctorCount.textContent = list.length ? `${list.length} doctor${list.length > 1 ? 's' : ''}` : '0 doctors';
    }
    if (!list.length) {
      els.doctorEmpty?.classList.remove('hidden');
      return;
    }
    els.doctorEmpty?.classList.add('hidden');
    list.forEach((doctor) => {
      const card = document.createElement('div');
      card.className = 'border border-gray-100 rounded-2xl p-4 shadow-sm flex flex-col gap-3';
      const canEdit = editableDoctorIds.includes(Number(doctor.id));
      card.innerHTML = `
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-sm font-semibold text-gray-900">${formatValue(doctor.doctor_name)}</p>
            <p class="text-xs text-gray-500">${formatValue(doctor.doctor_email)}</p>
          </div>
          <span class="text-[11px] px-2 py-1 rounded-full ${canEdit ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}">
            ${canEdit ? 'Editable' : 'Read only'}
          </span>
        </div>
        <div class="text-xs text-gray-600 space-y-1">
          <p><span class="font-semibold text-gray-700">Phone:</span> ${formatValue(doctor.doctor_mobile)}</p>
          <p><span class="font-semibold text-gray-700">Consultation:</span> ${formatCurrency(doctor.doctors_price)}</p>
        </div>
        ${canEdit ? '<button class="doctor-edit-btn mt-2 inline-flex justify-center px-3 py-2 rounded-xl bg-indigo-50 text-indigo-700 text-sm font-semibold hover:bg-indigo-100" data-doctor="' + doctor.id + '">Edit Doctor</button>' : ''}
      `;
      els.doctorGrid.appendChild(card);
    });
  }

  function openDoctorModal(doctor) {
    if (!doctor || !els.doctorModal || !els.doctorForm) return;
    els.doctorForm.elements['doctor_id'].value = doctor.id || '';
    els.doctorForm.elements['doctor_name'].value = doctor.doctor_name || '';
    els.doctorForm.elements['doctor_email'].value = doctor.doctor_email || '';
    els.doctorForm.elements['doctor_mobile'].value = doctor.doctor_mobile || '';
    els.doctorForm.elements['doctors_price'].value = doctor.doctors_price ?? '';
    if (els.doctorModalTitle) {
      els.doctorModalTitle.textContent = doctor.id === ctx.doctorId
        ? 'Edit My Profile'
        : `Edit ${doctor.doctor_name || 'Doctor'}`;
    }
    if (els.doctorModalSubtitle) {
      els.doctorModalSubtitle.textContent = `Doctor ID: ${doctor.id ?? '—'}`;
    }
    els.doctorModal.classList.remove('hidden');
  }

  function closeDoctorModal() {
    els.doctorModal?.classList.add('hidden');
  }

  async function loadProfile() {
    try {
      const res = await fetch(`${API_BASE}/dashboard/profile`, {
        credentials: 'include',
      });
      const data = await res.json();
      if (!res.ok || !data?.success) {
        throw new Error(data?.error || data?.message || 'Unable to load profile');
      }
      profilePayload = data;
      clinicEditable = Boolean(data?.editable?.clinic);
      editableDoctorIds = Array.isArray(data?.editable?.doctor_ids) ? data.editable.doctor_ids.map(Number) : [];
      toggleClinicForm(!clinicEditable);
      fillClinicSummary(data.clinic || null);
      const clinicNameForTemplate = data.clinic?.clinic_profile || data.clinic?.name;
      updateClinicQr(data.clinic?.slug, clinicNameForTemplate);
      if (els.clinicReferralCode) {
        els.clinicReferralCode.textContent = formatValue(data.clinic?.referral_code);
      }
      fillClinicForm(data.clinic || null);
      if (els.clinicId && data.clinic_id) {
        els.clinicId.textContent = data.clinic_id;
      }
      if (ctx.role === 'doctor' && data.doctor) {
        els.doctorContext.textContent = data.doctor.doctor_name
          ? `Doctor · ${data.doctor.doctor_name}`
          : 'Doctor';
      } else if (els.doctorContext) {
        els.doctorContext.textContent = '';
      }
      renderDoctors(data.doctors || []);
    } catch (error) {
      console.error(error);
      Swal.fire({
        icon: 'error',
        title: 'Profile unavailable',
        text: error.message || 'Something went wrong while loading profile details.',
      });
    }
  }

  if (els.refreshBtn) {
    els.refreshBtn.addEventListener('click', () => loadProfile());
  }

  if (els.clinicForm) {
    els.clinicForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!clinicEditable) return;
      const formData = new FormData(els.clinicForm);
      const payload = Object.fromEntries(formData.entries());
      Object.keys(payload).forEach((key) => {
        if (payload[key] === '') payload[key] = null;
      });
      try {
        const res = await fetch(`${API_BASE}/dashboard/profile/clinic`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'include',
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok || !data?.success) {
          throw new Error(data?.error || data?.message || 'Unable to update clinic');
        }
        Swal.fire({ icon: 'success', title: 'Clinic updated', timer: 1400, showConfirmButton: false });
        fillClinicSummary(data.clinic || null);
        fillClinicForm(data.clinic || null);
      } catch (error) {
        Swal.fire({ icon: 'error', title: 'Update failed', text: error.message || 'Unable to update clinic details.' });
      }
    });
    els.clinicForm.addEventListener('reset', (event) => {
      event.preventDefault();
      fillClinicForm(profilePayload?.clinic || null);
    });
  }

  if (els.clinicQrDownload) {
    els.clinicQrDownload.addEventListener('click', async () => {
      if (!currentClinicPageUrl) return;
      setDownloadButtonState(false, DOWNLOAD_LOADING_LABEL);
      try {
        const canvas = await buildDownloadableTemplate(currentClinicPageUrl, currentClinicName);
        await new Promise((resolve, reject) => {
          canvas.toBlob((blob) => {
            if (!blob) {
              reject(new Error('Template generation failed.'));
              return;
            }
            const objectUrl = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = objectUrl;
            anchor.download = `SnoutIQ-${sanitizeFileName(currentClinicName)}.png`;
            document.body.appendChild(anchor);
            anchor.click();
            document.body.removeChild(anchor);
            URL.revokeObjectURL(objectUrl);
            resolve();
          }, 'image/png');
        });
        Swal.fire({ icon: 'success', title: 'Template ready', timer: 1200, showConfirmButton: false });
      } catch (error) {
        console.error(error);
        Swal.fire({ icon: 'error', title: 'Download failed', text: error.message || 'Unable to prepare the QR template.' });
      } finally {
        setDownloadButtonState(Boolean(currentClinicPageUrl));
      }
    });
  }

  document.querySelectorAll('.doctor-modal-close').forEach((btn) => {
    btn.addEventListener('click', closeDoctorModal);
  });
  els.doctorModal?.addEventListener('click', (event) => {
    if (event.target === els.doctorModal) closeDoctorModal();
  });

  if (els.doctorGrid) {
    els.doctorGrid.addEventListener('click', (event) => {
      const button = event.target.closest('.doctor-edit-btn');
      if (!button) return;
      const doctorId = Number(button.dataset.doctor);
      if (!Number.isFinite(doctorId)) return;
      const doctor = (profilePayload?.doctors || []).find((doc) => Number(doc.id) === doctorId);
      if (!doctor) return;
      openDoctorModal(doctor);
    });
  }

  if (els.openSelfDoctor) {
    els.openSelfDoctor.addEventListener('click', () => {
      const doctor = (profilePayload?.doctors || []).find((doc) => Number(doc.id) === ctx.doctorId);
      if (doctor) {
        openDoctorModal(doctor);
      } else {
        Swal.fire({ icon: 'info', title: 'Profile not found', text: 'We could not find your doctor record yet.' });
      }
    });
  }

  if (els.doctorForm) {
    els.doctorForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(els.doctorForm);
      const doctorId = Number(formData.get('doctor_id'));
      if (!Number.isFinite(doctorId) || doctorId <= 0) {
        Swal.fire({ icon: 'error', title: 'Missing doctor', text: 'Select a doctor to update.' });
        return;
      }
      if (!editableDoctorIds.includes(doctorId)) {
        Swal.fire({ icon: 'error', title: 'Not allowed', text: 'You cannot edit this doctor profile.' });
        return;
      }
      const payload = Object.fromEntries(formData.entries());
      delete payload.doctor_id;
      Object.keys(payload).forEach((key) => {
        if (payload[key] === '') payload[key] = null;
      });
      try {
        const res = await fetch(`${API_BASE}/dashboard/profile/doctor/${doctorId}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'include',
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok || !data?.success) {
          throw new Error(data?.error || data?.message || 'Unable to update doctor');
        }
        Swal.fire({ icon: 'success', title: 'Doctor saved', timer: 1400, showConfirmButton: false });
        closeDoctorModal();
        await loadProfile();
      } catch (error) {
        Swal.fire({ icon: 'error', title: 'Update failed', text: error.message || 'Unable to update doctor details.' });
      }
    });
  }

  loadProfile();
});
</script>
@endsection
