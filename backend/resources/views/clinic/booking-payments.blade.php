{{-- resources/views/clinic/booking-payments.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Booking Payments')
@section('page_title','Booking Payments')

@section('content')
@php
  $sessionRole = session('role')
      ?? data_get(session('auth_full'), 'role')
      ?? data_get(session('user'), 'role');

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
@endphp

<div class="max-w-6xl mx-auto">
  <div class="bg-white rounded-xl shadow p-4 mb-4">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Clinic</label>
        <input type="text" class="mt-1 w-full rounded border-gray-300 bg-gray-50" value="{{ $resolvedClinicId ? '#'.$resolvedClinicId : 'Not detected' }}" disabled>
        <div class="text-xs text-gray-500 mt-1">Loaded from session. Filters bookings of this clinic.</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Since</label>
        <input id="since" type="date" value="{{ date('Y-m-d', strtotime('-30 days')) }}" class="mt-1 w-full rounded border-gray-300">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Service</label>
        <select id="svc" class="mt-1 w-full rounded border-gray-300">
          <option value="">All</option>
          <option value="video">Video</option>
          <option value="in_clinic" selected>In Clinic</option>
          <option value="home_visit">Home Visit</option>
        </select>
      </div>
      <div class="flex items-end">
        <button id="btnLoad" class="px-4 py-2 rounded bg-indigo-600 text-white">Load Payments</button>
      </div>
    </div>
  </div>

  <div id="summary" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6"></div>

  <div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
      <div class="font-semibold">Paid Bookings</div>
      <div id="count" class="text-sm text-gray-500">0</div>
    </div>
    <div class="overflow-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-700">
          <tr class="text-left">
            <th class="px-4 py-2">Date</th>
            <th class="px-4 py-2">Booking #</th>
            <th class="px-4 py-2">Doctor</th>
            <th class="px-4 py-2">Service</th>
            <th class="px-4 py-2">Amount</th>
            <th class="px-4 py-2">Status</th>
            <th class="px-4 py-2">Method</th>
            <th class="px-4 py-2">Payment ID</th>
            <th class="px-4 py-2">Verified</th>
            <th class="px-4 py-2">Action</th>
          </tr>
        </thead>
        <tbody id="rows" class="divide-y"></tbody>
      </table>
      <div id="empty" class="hidden p-6 text-center text-sm text-gray-500">No paid bookings found for the selected range.</div>
    </div>
  </div>

  <script>
    const ORIGIN   = window.location.origin;
    const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
    const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
    const CLINIC_ID = Number(@json($resolvedClinicId ?? null)) || null;

    const $ = s => document.querySelector(s);
    const esc = s => (''+(s??'')).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
    const fmtDate = dt => { if(!dt) return '-'; const d = new Date((dt||'').replace(' ','T')); return isNaN(d)? (dt||'-') : d.toLocaleDateString(); };
    const fmtDateTime = dt => { if(!dt) return '-'; const d = new Date((dt||'').replace(' ','T')); return isNaN(d)? (dt||'-') : d.toLocaleString(); };

    async function getJSON(url){
      const res = await fetch(url, { headers: { 'Accept':'application/json' } });
      const text = await res.text();
      let json=null; try{ json = JSON.parse(text.replace(/^\uFEFF/,'')); }catch{}
      if(!res.ok) throw new Error(json?.error || json?.message || text || `HTTP ${res.status}`);
      return json;
    }

    async function load(){
      if(!CLINIC_ID){ $('#rows').innerHTML = '<tr><td class="px-4 py-4 text-rose-600" colspan="10">Clinic not detected.</td></tr>'; return; }
      $('#rows').innerHTML = '<tr><td class="px-4 py-4 text-gray-500" colspan="10">Loading…</td></tr>';
      $('#empty').classList.add('hidden');
      $('#summary').innerHTML='';

      const since = $('#since').value || new Date().toISOString().slice(0,10);
      const svc   = $('#svc').value || '';

      try{
        // 1) Doctors for this clinic
        const dres = await getJSON(`${apiBase}/clinics/${CLINIC_ID}/doctors`);
        const doctors = Array.isArray(dres?.doctors) ? dres.doctors : [];

        // 2) Gather bookings per doctor
        const all = [];
        await Promise.all(doctors.map(async (doc)=>{
          try{
            const url = `${apiBase}/doctors/${doc.id}/bookings?since=${encodeURIComponent(since)}`;
            const j = await getJSON(url);
            const b = Array.isArray(j?.bookings) ? j.bookings : [];
            b.forEach(x => { x.__doctor_id = doc.id; x.__doctor_name = doc.name || doc.doctor_name || `Doctor #${doc.id}`; });
            all.push(...b);
          }catch(_){ /* ignore */ }
        }));

        // 3) Filter to paid bookings (and optional service type)
        let rows = all.filter(x => (x.payment_status||'').toLowerCase() === 'paid');
        if(svc){ rows = rows.filter(x => (x.service_type||'') === svc); }

        // 4) Sort by payment_verified_at or scheduled/created
        rows.sort((a,b)=>{
          const ta = Date.parse((a.payment_verified_at || a.scheduled_for || a.booking_created_at || '').replace(' ','T')) || 0;
          const tb = Date.parse((b.payment_verified_at || b.scheduled_for || b.booking_created_at || '').replace(' ','T')) || 0;
          return tb - ta;
        });

        // 5) Summary cards
        const totalInr = rows.reduce((acc,r)=> acc + (Number(r.final_price||0)||0), 0);
        const methods  = new Map(); rows.forEach(r=>{ const m=(r.payment_method||'-').toUpperCase(); methods.set(m,(methods.get(m)||0)+1); });
        const cards = [];
        cards.push(`
          <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Total Collected</div>
            <div class="text-2xl font-bold">₹${totalInr.toFixed(2)}</div>
          </div>`);
        cards.push(`
          <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Paid Bookings</div>
            <div class="text-2xl font-bold">${rows.length}</div>
          </div>`);
        cards.push(`
          <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Methods</div>
            <div class="text-sm">${Array.from(methods.entries()).map(([k,v])=>`${esc(k)}: ${v}`).join(', ') || '-'}</div>
          </div>`);
        $('#summary').innerHTML = cards.join('');

        // 6) Table
        const tbody = $('#rows'); tbody.innerHTML='';
        if(!rows.length){ $('#empty').classList.remove('hidden'); $('#count').textContent = '0'; return; }
        $('#count').textContent = `${rows.length}`;
        rows.forEach(r => {
          const tr = document.createElement('tr');
          const amt = Number(r.final_price||0)||0;
          tr.innerHTML = `
            <td class="px-4 py-2">${fmtDate(r.payment_verified_at || r.scheduled_for || r.booking_created_at)}</td>
            <td class="px-4 py-2 font-medium">#${r.id}</td>
            <td class="px-4 py-2">${esc(r.__doctor_name || '')}</td>
            <td class="px-4 py-2">${esc(r.service_type || '')}</td>
            <td class="px-4 py-2">₹${amt.toFixed(2)}</td>
            <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700">PAID</span></td>
            <td class="px-4 py-2">${esc((r.payment_method||'-').toUpperCase())}</td>
            <td class="px-4 py-2 font-mono">${esc(r.payment_id || '-')}</td>
            <td class="px-4 py-2">${fmtDateTime(r.payment_verified_at)}</td>
            <td class="px-4 py-2">
              <a href="${location.origin}${location.pathname.startsWith('/backend')?'/backend':''}/doctor/booking/${r.id}" class="text-indigo-600 hover:underline">Details</a>
            </td>`;
          tbody.appendChild(tr);
        });
      }catch(e){
        $('#rows').innerHTML = `<tr><td class="px-4 py-4 text-rose-600" colspan="10">${esc(e.message||e)}</td></tr>`;
      }
    }

    document.getElementById('btnLoad').addEventListener('click', load);
    document.addEventListener('DOMContentLoaded', load);
  </script>
</div>
@endsection
