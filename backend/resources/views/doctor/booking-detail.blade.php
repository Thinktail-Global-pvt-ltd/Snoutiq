@extends('layouts.snoutiq-dashboard')

@section('title','Booking Details')
@section('page_title','Booking Details')
@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-xl shadow p-5">
  <div class="flex items-center justify-between mb-4">
    <a href="{{ route('doctor.bookings') }}" class="text-sm text-indigo-700 hover:underline">&larr; Back to Calendar</a>
    <div id="statusBadge" class="text-xs px-2.5 py-1 rounded-full border">Loading…</div>
  </div>

  <h2 id="title" class="text-lg font-semibold mb-1">Booking #{{ $bookingId }}</h2>
  <div id="subtitle" class="text-sm text-gray-600 mb-4">Loading details…</div>

  <div id="details" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>

  <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div>
      <div class="text-sm text-gray-500 mb-1">Pet Parent Snapshot</div>
      <div id="userPanel" class="bg-gray-50 border rounded p-3 text-sm">Loading user profile…</div>
    </div>
    <div>
      <div class="text-sm text-gray-500 mb-1">Primary Pet</div>
      <div id="petPanel" class="bg-gray-50 border rounded p-3 text-sm"></div>
    </div>
  </div>

  <div class="mt-6">
    <div class="text-sm text-gray-500 mb-1">Medical Documents</div>
    <div id="documentsPanel" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"></div>
  </div>

  <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div>
      <div class="text-sm text-gray-500 mb-1">User Booking History</div>
      <div id="historyPanel" class="bg-gray-50 border rounded p-3 text-sm"></div>
    </div>
    <div>
      <div class="text-sm text-gray-500 mb-1">All Pets (User)</div>
      <div id="petsPanel" class="bg-gray-50 border rounded p-3 text-sm"></div>
    </div>
  </div>

  <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div>
      <div class="text-sm text-gray-500 mb-1">Symptoms</div>
      <pre id="symptoms" class="whitespace-pre-wrap bg-gray-50 border rounded p-3 text-sm"></pre>
    </div>
    <div>
      <div class="text-sm text-gray-500 mb-1">AI Summary (Booking)</div>
      <pre id="summary" class="whitespace-pre-wrap bg-gray-50 border rounded p-3 text-sm"></pre>
    </div>
    <div>
      <div class="text-sm text-gray-500 mb-1">AI Summary from Documents</div>
      <pre id="docSummary" class="whitespace-pre-wrap bg-gray-50 border rounded p-3 text-sm"></pre>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const BOOKING_ID = Number(@json($bookingId));
  // Smart base detection for API (handles /backend prefix in production)
  const ORIGIN          = window.location.origin;
  const IS_LOCAL        = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const apiBase         = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
  async function api(url){
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    const t = await res.text();
    let j=null; try{ j = JSON.parse(t.replace(/^\uFEFF/, '')); }catch{}
    return { ok: res.ok, status: res.status, json: j, raw: t };
  }
  const esc = (v) => String(v ?? '').replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
  function pretty(v){ if(v==null) return ''; if(typeof v==='string'){ try{ const j=JSON.parse(v); return JSON.stringify(j,null,2) }catch{} return v } return JSON.stringify(v,null,2); }
  function renderKV(obj){
    if (!obj) return '<div class="text-xs text-gray-500">No data</div>';
    const keys = Object.keys(obj);
    if (!keys.length) return '<div class="text-xs text-gray-500">No data</div>';
    return '<div class="grid grid-cols-1 md:grid-cols-2 gap-2">' + keys.map(k => {
      const v = obj[k];
      const val = typeof v === 'object' ? pretty(v) : esc(v);
      return `<div class="p-2 bg-white rounded border"><div class="text-[11px] text-gray-500">${esc(k)}</div><div class="text-sm">${val}</div></div>`;
    }).join('') + '</div>';
  }
  function formatTimeline(value){
    if(!value) return '—';
    const normalized = value.includes('T') ? value : value.replace(' ', 'T');
    const dt = new Date(normalized);
    if(Number.isNaN(dt.getTime())) return value;
    return dt.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
  }
  function renderUserSnapshot(user, booking){
    const idLabel = user?.id ?? booking?.user_id ?? '—';
    const contactBits = [
      user?.phone ? `<span class="font-mono">${esc(user.phone)}</span>` : '',
      user?.email ? `<span>${esc(user.email)}</span>` : '',
    ].filter(Boolean).join(' · ');
    const locationParts = [user?.address, user?.city, user?.state].filter(Boolean).join(', ');
    const coords = [user?.latitude, user?.longitude].every(v => v !== null && v !== undefined && v !== '') ? `${user.latitude}, ${user.longitude}` : '';
    return `
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm font-semibold text-gray-800">${esc(user?.name ?? 'Pet Parent')}</div>
            ${user?.pet_name ? `<div class="text-xs text-gray-500">Primary pet · ${esc(user.pet_name)}</div>` : ''}
          </div>
          <span class="text-[11px] uppercase tracking-wide text-gray-400">User #${esc(idLabel)}</span>
        </div>
        ${contactBits ? `<div class="text-sm text-gray-600 flex flex-col gap-1">${contactBits}</div>` : ''}
        ${locationParts ? `<div class="text-sm text-gray-600">${esc(locationParts)}</div>` : ''}
        ${coords ? `<div class="text-xs text-gray-500">Coords · ${esc(coords)}</div>` : ''}
      </div>`;
  }
  function renderDocuments(docs){
    if(!Array.isArray(docs) || !docs.length){
      return '<div class="text-sm text-gray-500 border border-dashed rounded-lg p-4 bg-white">No medical documents uploaded yet.</div>';
    }
    return docs.map(doc => `
      <div class="bg-white border rounded-xl p-3 flex items-center gap-3 shadow-sm">
        <div class="w-14 h-14 rounded-lg overflow-hidden bg-slate-100 flex items-center justify-center">
          ${doc.is_image ? `<img src="${esc(doc.url)}" alt="${esc(doc.label)}" class="object-cover w-full h-full">` : `<span class="text-xs font-semibold text-slate-500">${esc((doc.extension || 'file').toUpperCase())}</span>`}
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-sm font-semibold text-slate-800">${esc(doc.label)}</div>
          <div class="text-xs text-slate-500">${doc.category === 'pet' ? 'Pet' : 'User'} • ${(doc.extension || 'file').toUpperCase()}</div>
          <a class="text-xs text-indigo-600 hover:underline" href="${esc(doc.url)}" target="_blank" rel="noopener">Open</a>
        </div>
      </div>
    `).join('');
  }
  function renderHistoryPanel(items){
    if(!Array.isArray(items) || !items.length){
      return '<div class="text-sm text-gray-500">No previous bookings for this user.</div>';
    }
    return `
      <div class="space-y-3">
        ${items.map(entry => `
          <div class="border rounded-lg px-3 py-2 ${entry.is_current ? 'bg-indigo-50 border-indigo-200' : 'bg-white'}">
            <div class="flex items-center justify-between text-[11px] text-gray-500 mb-1">
              <span>${esc(formatTimeline(entry.timeline_label))}</span>
              <span>#${esc(entry.id)}</span>
            </div>
            <div class="text-sm font-semibold text-gray-800">${esc((entry.service_type || '').replace(/_/g,' ') || 'Booking')}</div>
            <div class="text-xs text-gray-600 flex flex-wrap gap-2 mt-1">
              <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">${esc((entry.status || 'pending').replace(/_/g,' '))}</span>
              ${entry.pet_id ? `<span>Pet #${esc(entry.pet_id)}</span>` : ''}
              ${entry.urgency ? `<span class="uppercase tracking-wide text-[10px] text-orange-600">Urgency · ${esc(entry.urgency)}</span>` : ''}
            </div>
          </div>
        `).join('')}
      </div>
    `;
  }

  async function load(){
    const res = await api(`${apiBase}/bookings/details/${BOOKING_ID}`);
    console.log('[booking-detail] GET /api/bookings/details/'+BOOKING_ID+' response:', res);
    if(!res.ok || !res.json?.booking){
      document.getElementById('subtitle').textContent = 'Failed to load booking';
      return;
    }
    const b = res.json.booking;
    console.log('[booking-detail] booking:', b);
    document.getElementById('title').textContent = `Booking #${b.id}`;
    document.getElementById('subtitle').textContent = `${(b.service_type||'').replace('_',' ')} • ${(b.scheduled_for||b.booking_created_at||'').replace('T',' ')}`;
    const badge = document.getElementById('statusBadge');
    badge.textContent = b.status || 'pending';
    badge.className = 'text-xs px-2.5 py-1 rounded-full border ' + (b.status==='completed' ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-indigo-50 border-indigo-300 text-indigo-800');

    const details = document.getElementById('details');
    const user = res.json?.user || null;
    const petParentName = user?.name || `User #${esc(b.user_id)}`;
    const doctorName = (b.doctor_name && b.doctor_name.trim()) ? b.doctor_name : (b.assigned_doctor_id ? `Doctor #${b.assigned_doctor_id}` : '—');
    details.innerHTML = `
      <div class="p-3 border rounded">
        <div class="text-xs text-gray-500">User / Pet</div>
        <div class="text-sm">#${esc(b.user_id)} / #${esc(b.pet_id)}</div>
      </div>
      <div class="p-3 border rounded">
        <div class="text-xs text-gray-500">Urgency</div>
        <div class="text-sm">${esc(b.urgency)}</div>
      </div>
      <div class="p-3 border rounded">
        <div class="text-xs text-gray-500">Doctor</div>
        <div class="text-sm">${esc(doctorName)}</div>
      </div>
      <div class="p-3 border rounded">
        <div class="text-xs text-gray-500">Pet Parent</div>
        <div class="text-sm">${esc(petParentName)}</div>
      </div>
      <div class="p-3 border rounded">
        <div class="text-xs text-gray-500">Location</div>
        <div class="text-sm">${esc(b.user_address ?? '')}</div>
      </div>
      <div class="p-3 border rounded">
        <div class="text-xs text-gray-500">Coordinates</div>
        <div class="text-sm">${esc(b.user_latitude ?? '')}, ${esc(b.user_longitude ?? '')}</div>
      </div>`;

    const userPanel = document.getElementById('userPanel');
    if(userPanel){
      userPanel.innerHTML = user ? renderUserSnapshot(user, b) : '<div class="text-sm text-gray-500">No user profile found for this booking.</div>';
    }

    document.getElementById('symptoms').textContent = pretty(b.symptoms);
    document.getElementById('summary').textContent = pretty(b.ai_summary);
    const docSummary = document.getElementById('docSummary');
    if(docSummary){
      const docText = res.json?.user_summary ? pretty(res.json.user_summary) : 'No AI summary generated from pet documents yet.';
      docSummary.textContent = docText;
    }

    // Pet sections
    const petPanel = document.getElementById('petPanel');
    const petsPanel = document.getElementById('petsPanel');
    const pet  = res.json?.pet || null;
    const pets = Array.isArray(res.json?.pets) ? res.json.pets : [];
    if (petPanel) petPanel.innerHTML = pet ? renderKV(pet) : '<div class="text-xs text-gray-500">No pet linked to booking.</div>';
    if (petsPanel) {
      if (pets.length) {
        const list = pets.map(p => `<li class="py-1 flex flex-col">
            <span class="font-mono text-xs text-gray-500">#${esc(p.id)}</span>
            <span class="text-sm text-gray-800">${esc(p.name ?? '')} ${p.breed? '('+esc(p.breed)+')':''}</span>
          </li>`).join('');
        petsPanel.innerHTML = `<ul class="list-disc pl-5 space-y-1">${list}</ul>`;
      } else {
        petsPanel.innerHTML = '<div class="text-xs text-gray-500">No pets found for user.</div>';
      }
    }

    const documentsPanel = document.getElementById('documentsPanel');
    if(documentsPanel){
      documentsPanel.innerHTML = renderDocuments(res.json?.documents || []);
    }

    const historyPanel = document.getElementById('historyPanel');
    if(historyPanel){
      historyPanel.innerHTML = renderHistoryPanel(res.json?.user_history || []);
    }
  }
  document.addEventListener('DOMContentLoaded', load);
</script>
@endsection
