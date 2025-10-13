{{-- resources/views/clinic/order-history.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Clinic Orders')
@section('page_title','Clinic Orders')

@section('content')
@php
  $resolvedClinicId = session('user_id')
      ?? data_get(session('user'), 'id')
      ?? session('vet_registerations_temp_id')
      ?? session('vet_registeration_id')
      ?? session('vet_id');
@endphp

<div class="max-w-6xl mx-auto">
  <div class="bg-white rounded-xl shadow p-4 mb-4">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 flex-1">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Clinic</label>
          <input type="text" class="mt-1 w-full rounded border-gray-300 bg-gray-50" value="{{ $resolvedClinicId ? '#'.$resolvedClinicId : 'Not detected' }}" disabled>
          <div class="text-xs text-gray-500 mt-1">Loaded from session. Uses doctors belonging to this clinic.</div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Since</label>
          <input id="since" type="date" value="{{ date('Y-m-d', strtotime('-30 days')) }}" class="mt-1 w-full rounded border-gray-300">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Filter Status</label>
          <select id="statusFilter" class="mt-1 w-full rounded border-gray-300">
            <option value="">All</option>
            <option value="completed">Completed</option>
            <option value="accepted">Accepted</option>
            <option value="pending">Pending</option>
            <option value="routing">Routing</option>
            <option value="in_progress">In Progress</option>
            <option value="cancelled">Cancelled</option>
            <option value="failed">Failed</option>
          </select>
        </div>
        <div class="flex items-end">
          <button id="btnLoad" class="px-4 py-2 rounded bg-indigo-600 text-white">Load Orders</button>
        </div>
      </div>
    </div>
  </div>

  <div id="summary" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6"></div>

  <div class="bg-white rounded-xl shadow">
    <div class="p-4 border-b">
      <div class="flex items-center justify-between">
        <div class="text-base font-semibold">Order History</div>
        <div id="count" class="text-sm text-gray-500">0 orders</div>
      </div>
    </div>
    <div class="overflow-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left text-gray-700">
            <th class="px-4 py-2">Order #</th>
            <th class="px-4 py-2">Doctor</th>
            <th class="px-4 py-2">Pet</th>
            <th class="px-4 py-2">Service</th>
            <th class="px-4 py-2">Urgency</th>
            <th class="px-4 py-2">Scheduled</th>
            <th class="px-4 py-2">Status</th>
            <th class="px-4 py-2">Actions</th>
          </tr>
        </thead>
        <tbody id="rows" class="divide-y"></tbody>
      </table>
      <div id="empty" class="hidden p-6 text-center text-sm text-gray-500">No orders found for the selected range.</div>
    </div>
  </div>

  <script>
    const ORIGIN   = window.location.origin;
    const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
    const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
    const CLINIC_ID = Number(@json($resolvedClinicId ?? null)) || null;

    const $ = s => document.querySelector(s);
    const $$ = s => Array.from(document.querySelectorAll(s));

    function esc(s){ return (''+(s??'')).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }
    function fmtDateTime(dt){ if(!dt) return '-'; const d = new Date((dt||'').replace(' ','T')); return isNaN(d)? (dt||'-') : d.toLocaleString(); }

    async function getJSON(url){
      const res = await fetch(url, { headers: { 'Accept':'application/json' } });
      const text = await res.text();
      let json=null; try{ json = JSON.parse(text.replace(/^\uFEFF/,'')); }catch{}
      if(!res.ok) throw new Error(json?.error || json?.message || text || `HTTP ${res.status}`);
      return json;
    }

    async function load(){
      if(!CLINIC_ID){ $('#rows').innerHTML = '<tr><td class="px-4 py-4 text-rose-600" colspan="8">Clinic not detected in session.</td></tr>'; return; }
      $('#rows').innerHTML = '<tr><td class="px-4 py-4 text-gray-500" colspan="8">Loading…</td></tr>';
      $('#empty').classList.add('hidden');
      $('#summary').innerHTML='';

      const since = $('#since').value || new Date().toISOString().slice(0,10);
      const statusFilter = $('#statusFilter').value || '';

      try{
        // 1) Get doctors of this clinic
        const dres = await getJSON(`${apiBase}/clinics/${CLINIC_ID}/doctors`);
        const doctors = Array.isArray(dres?.doctors) ? dres.doctors : [];

        // 2) Pull bookings per doctor (in parallel)
        const all = [];
        await Promise.all(doctors.map(async (doc)=>{
          try{
            const url = `${apiBase}/doctors/${doc.id}/bookings?since=${encodeURIComponent(since)}`;
            const j = await getJSON(url);
            const b = Array.isArray(j?.bookings) ? j.bookings : [];
            b.forEach(x => { x.__doctor_id = doc.id; x.__doctor_name = doc.name || doc.doctor_name || `Doctor #${doc.id}`; });
            all.push(...b);
          }catch(_){ /* ignore one doctor's error */ }
        }));

        // 3) Filter by status if requested
        let rows = all;
        if(statusFilter){ rows = rows.filter(x => (x.status||'').toLowerCase() === statusFilter.toLowerCase()); }

        // 4) Sort desc by scheduled/created
        rows.sort((a,b)=>{
          const ta = Date.parse((a.scheduled_for || a.booking_created_at || '').replace(' ','T')) || 0;
          const tb = Date.parse((b.scheduled_for || b.booking_created_at || '').replace(' ','T')) || 0;
          return tb - ta;
        });

        // 5) Render summary: last booking per doctor
        const byDoc = new Map();
        for(const r of rows){ if(!byDoc.has(r.__doctor_id)) byDoc.set(r.__doctor_id, []); byDoc.get(r.__doctor_id).push(r); }
        const cards = [];
        for(const doc of doctors){
          const list = byDoc.get(doc.id) || [];
          const last = list[0] || null;
          const html = `
            <div class="bg-white border rounded-lg p-4">
              <div class="text-sm text-gray-500">Doctor</div>
              <div class="font-semibold">${esc(doc.name || `Doctor #${doc.id}`)}</div>
              <div class="mt-2 text-xs text-gray-500">Last booking</div>
              ${last ? `
                <div class="mt-1 text-sm">#${last.id} • ${esc(last.service_type||'')} • <span class="uppercase">${esc(last.status||'')}</span></div>
                <div class="text-xs text-gray-500">${fmtDateTime(last.scheduled_for || last.booking_created_at)}</div>
                <div class="mt-2">
                  <a href="${location.origin}${location.pathname.startsWith('/backend')?'/backend':''}/doctor/booking/${last.id}" class="text-indigo-600 hover:underline text-sm">View details</a>
                </div>
              ` : '<div class="mt-1 text-sm text-gray-500">No bookings</div>'}
            </div>`;
          cards.push(html);
        }
        $('#summary').innerHTML = cards.join('');

        // 6) Render table
        const tbody = $('#rows'); tbody.innerHTML='';
        if(!rows.length){ $('#empty').classList.remove('hidden'); $('#count').textContent = '0 orders'; return; }
        $('#count').textContent = `${rows.length} orders`;
        rows.forEach(b => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="px-4 py-2 font-medium">#${b.id}</td>
            <td class="px-4 py-2">${esc(b.__doctor_name || '')}</td>
            <td class="px-4 py-2">${esc(b.pet_name || '')}</td>
            <td class="px-4 py-2">${esc((b.service_type||'').replace('_',' '))}</td>
            <td class="px-4 py-2 capitalize">${esc(b.urgency||'')}</td>
            <td class="px-4 py-2">${fmtDateTime(b.scheduled_for || b.booking_created_at)}</td>
            <td class="px-4 py-2">
              <span class="px-2 py-0.5 rounded-full text-xs ${b.status==='completed'?'bg-emerald-100 text-emerald-700':'bg-gray-200 text-gray-700'}">${esc(b.status||'')}</span>
            </td>
            <td class="px-4 py-2">
              <a href="${location.origin}${location.pathname.startsWith('/backend')?'/backend':''}/doctor/booking/${b.id}" class="text-indigo-600 hover:underline">View</a>
            </td>`;
          tbody.appendChild(tr);
        });
      }catch(e){
        $('#rows').innerHTML = `<tr><td class="px-4 py-4 text-rose-600" colspan="8">${esc(e.message||e)}</td></tr>`;
      }
    }

    document.getElementById('btnLoad').addEventListener('click', load);
    document.addEventListener('DOMContentLoaded', load);
  </script>
</div>
@endsection

