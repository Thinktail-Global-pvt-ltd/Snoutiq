{{-- backend/resources/views/snoutiq/admin-video-slot-overview.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@php
  $page_title = $page_title ?? 'Video Slots Overview';
@endphp

@section('title', $page_title)
@section('page_title', $page_title)

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
  <div class="bg-white rounded-xl shadow p-4 flex flex-wrap items-end gap-4">
    <div>
      <label for="slotDate" class="block text-sm font-medium text-gray-700">Date (IST)</label>
      <input type="date" id="slotDate" class="mt-1 rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
    </div>
    <div class="flex items-center gap-2 mt-6">
      <button id="btnSlotRefresh" class="px-4 py-2 rounded bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Refresh Grid</button>
    </div>
    <div class="flex-1 min-w-[200px] mt-6 text-xs text-gray-500">
      Grid shows doctor availability per pincode. Each cell = number of doctors scheduled in that hour.
    </div>
  </div>

  <div class="bg-white rounded-xl shadow p-4">
    <div id="slotSummary" class="text-sm text-gray-700 mb-3"></div>
    <div id="slotMatrixWrap" class="overflow-x-auto text-xs"></div>
  </div>
</div>

<script>
  (function(){
    const pathnamePrefix = (() => {
      const path = window.location.pathname || '';
      if (path.startsWith('/backend/')) return '/backend';
      if (path === '/backend' || path === '/backend/') return '/backend';
      return '';
    })();
    const ADMIN_API_BASE = `${window.location.origin}${pathnamePrefix}/api/admin`;
    const el = (selector) => document.querySelector(selector);
    const hours = Array.from({length: 24}, (_, i) => i);

    function fmtInt(v){ return Number(v || 0).toLocaleString('en-IN'); }

    function renderEmpty(message){
      const wrap = el('#slotMatrixWrap');
      if(wrap){
        wrap.innerHTML = `<div class="text-sm text-gray-500">${message}</div>`;
      }
    }

    function renderSummary(summary, dateStr){
      const target = el('#slotSummary');
      if(!target) return;
      if(!summary){
        target.textContent = '';
        return;
      }
      target.innerHTML = `
        <div class="flex flex-wrap gap-4 items-center">
          <span class="text-gray-600 text-sm">Date: <strong>${dateStr}</strong></span>
          <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-xs">Doctor hours: ${fmtInt(summary.doctor_hours)}</span>
          <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs">Unique doctors scheduled: ${fmtInt(summary.unique_doctors)}</span>
        </div>
      `;
    }

    function renderMatrix(rows){
      const wrap = el('#slotMatrixWrap');
      if(!wrap) return;
      if(!rows || !rows.length){
        renderEmpty('No availability or bookings found for this date.');
        return;
      }

      let html = '<table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">';
      html += '<thead class="bg-gray-50"><tr>';
      html += '<th class="px-3 py-2 text-left font-semibold text-gray-700">Pincode</th>';
      html += '<th class="px-3 py-2 text-left font-semibold text-gray-700">Clinics</th>';
      html += '<th class="px-3 py-2 text-center font-semibold text-gray-700">Totals</th>';
      hours.forEach(h=>{
        const label = String(h).padStart(2,'0') + ':00';
        html += `<th class="px-2 py-2 text-center font-semibold text-gray-700">${label}</th>`;
      });
      html += '</tr></thead><tbody>';

      rows.forEach(row=>{
        const clinics = (row.clinics || []).join(', ') || '—';
        html += '<tr class="border-t">';
        html += `<td class="px-3 py-2 font-semibold text-gray-900 whitespace-nowrap">${row.pincode ?? '—'}</td>`;
        html += `<td class="px-3 py-2 text-gray-600 min-w-[160px]">${clinics}</td>`;
        const totalDoctors = row.totals?.unique_doctors ?? 0;
        const docHours = row.totals?.doctor_hours ?? 0;
        html += `<td class="px-3 py-2 text-center text-gray-700">
                    <div class="font-semibold">${fmtInt(totalDoctors)} doctor(s)</div>
                    <div class="text-[11px] text-gray-500">Doctor-hours: ${fmtInt(docHours)}</div>
                 </td>`;

        hours.forEach(h=>{
          const cell = row.hours ? (row.hours[h] ?? row.hours[String(h)]) : null;
          const count = cell?.count ?? 0;
          if(!count){
            html += '<td class="px-2 py-1 text-center text-gray-400">—</td>';
            return;
          }
          const badgeClass = 'bg-emerald-500';
          html += `<td class="px-2 py-1 text-center">
            <span class="inline-block px-2 py-0.5 rounded text-white text-[11px] font-semibold ${badgeClass}">${count} doc${count === 1 ? '' : 's'}</span>
          </td>`;
        });
        html += '</tr>';
      });

      html += '</tbody></table>';
      wrap.innerHTML = html;
    }

    async function fetchMatrix(){
      const dateInput = el('#slotDate');
      const date = dateInput?.value;
      if(!date){
        renderEmpty('Select a date to load data.');
        return;
      }
      renderEmpty('Loading coverage …');
      try{
        const res = await fetch(`${ADMIN_API_BASE}/video/pincode-slots?date=${encodeURIComponent(date)}`, { credentials: 'include' });
        if(!res.ok){
          throw new Error(`Request failed (${res.status})`);
        }
        const data = await res.json();
        renderSummary(data?.summary ?? null, data?.date ?? date);
        renderMatrix(data?.rows ?? []);
      }catch(err){
        console.error(err);
        renderEmpty('Failed to load slot data. Please try again.');
        renderSummary(null, '');
      }
    }

    document.addEventListener('DOMContentLoaded', ()=>{
      const today = new Date();
      const pad = (n)=> String(n).padStart(2,'0');
      const dateStr = `${today.getFullYear()}-${pad(today.getMonth()+1)}-${pad(today.getDate())}`;
      const dateInput = el('#slotDate');
      if(dateInput){
        dateInput.value = dateStr;
        dateInput.addEventListener('change', fetchMatrix);
      }
      el('#btnSlotRefresh')?.addEventListener('click', fetchMatrix);
      fetchMatrix();
    });
  })();
</script>
@endsection
