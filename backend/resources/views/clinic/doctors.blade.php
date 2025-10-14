{{-- resources/views/clinic/doctors.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Clinic Doctors')
@section('page_title','Clinic Doctors')

@php
  $resolvedClinicId = session('user_id')
      ?? data_get(session('user'), 'id')
      ?? session('vet_registerations_temp_id')
      ?? session('vet_registeration_id')
      ?? session('vet_id');
@endphp

@section('content')
<div class="max-w-6xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <div>
      <div class="text-base font-semibold">Manage Doctors</div>
      <div class="text-xs text-gray-500">Clinic ID: {{ $resolvedClinicId ?: 'not detected' }}</div>
    </div>
    <button id="btn-open-create" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">+ Add Doctor</button>
  </div>

  <div class="bg-white rounded-xl shadow border border-gray-200">
    <div class="p-4 border-b">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700">All Doctors in this Clinic</div>
        <div id="count" class="text-sm text-gray-500">0</div>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-700">
          <tr>
            <th class="px-4 py-2">Name</th>
            <th class="px-4 py-2">Email</th>
            <th class="px-4 py-2">Phone</th>
            <th class="px-4 py-2">License</th>
          </tr>
        </thead>
        <tbody id="rows" class="divide-y"></tbody>
      </table>
      <div id="empty" class="hidden p-6 text-center text-sm text-gray-500">No doctors found</div>
    </div>
  </div>

  <!-- Create Modal -->
  <div id="create-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-[96%] max-w-md p-6 relative">
      <button type="button" class="btn-close absolute top-3 right-3 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700">×</button>
      <h3 class="text-xl font-semibold text-gray-800 mb-1">Add Doctor</h3>
      <p class="text-sm text-gray-500 mb-4">Create a new doctor under this clinic</p>
      <form id="create-form" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Full Name</label>
          <input name="doctor_name" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm" required>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Email</label>
          <input name="doctor_email" type="email" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Mobile</label>
          <input name="doctor_mobile" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">License No.</label>
          <input name="doctor_license" class="w-full bg-gray-100 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="btn-close px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Create</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    const ORIGIN   = window.location.origin;
    const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
    const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;
    const CLINIC_ID = Number(@json($resolvedClinicId ?? null)) || null;

    const $ = s => document.querySelector(s);
    const $$ = (s,root=document)=> Array.from(root.querySelectorAll(s));

    function esc(s){ return (''+(s??'')).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

    async function getJSON(url){
      const res = await fetch(url, { headers: { 'Accept':'application/json' } });
      const text = await res.text();
      let json=null; try{ json = JSON.parse(text.replace(/^\uFEFF/,'')); }catch{}
      if(!res.ok) throw new Error(json?.error || json?.message || text || `HTTP ${res.status}`);
      return json;
    }
    async function postJSON(url, body){
      const res = await fetch(url, { method:'POST', headers: { 'Accept':'application/json','Content-Type':'application/json' }, body: JSON.stringify(body) });
      const text = await res.text(); let json=null; try{ json=JSON.parse(text); }catch{}
      if(!res.ok) throw new Error(json?.error || json?.message || text || `HTTP ${res.status}`);
      return json;
    }

    function open(el){ el?.classList.remove('hidden'); }
    function close(el){ el?.classList.add('hidden'); }

    async function load(){
      const tbody = $('#rows');
      if(!CLINIC_ID){ tbody.innerHTML = '<tr><td class="px-4 py-4 text-rose-600" colspan="4">Clinic not detected in session.</td></tr>'; return; }
      tbody.innerHTML = '<tr><td class="px-4 py-4 text-gray-500" colspan="4">Loading…</td></tr>';
      $('#empty').classList.add('hidden');
      try{
        const j = await getJSON(`${apiBase}/clinics/${CLINIC_ID}/doctors`);
        const docs = Array.isArray(j?.doctors) ? j.doctors : [];
        $('#count').textContent = `${docs.length}`;
        if (!docs.length){ $('#empty').classList.remove('hidden'); tbody.innerHTML=''; return; }
        tbody.innerHTML='';
        docs.forEach(d => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="px-4 py-2 font-medium">${esc(d.name || d.doctor_name || '—')}</td>
            <td class="px-4 py-2">${esc(d.email || d.doctor_email || '—')}</td>
            <td class="px-4 py-2">${esc(d.phone || d.doctor_mobile || '—')}</td>
            <td class="px-4 py-2">${esc(d.doctor_license || d.license || '—')}</td>`;
          tbody.appendChild(tr);
        });
      }catch(e){
        tbody.innerHTML = `<tr><td class=\"px-4 py-4 text-rose-600\" colspan=\"4\">${esc(e.message||e)}</td></tr>`;
      }
    }

    // Modal wiring
    const modal = $('#create-modal');
    $('#btn-open-create').addEventListener('click', ()=> open(modal));
    $$('.btn-close', modal).forEach(b => b.addEventListener('click', ()=> close(modal)));
    $('#create-form').addEventListener('submit', async (e)=>{
      e.preventDefault();
      if(!CLINIC_ID){ Swal.fire({icon:'error', title:'Clinic not detected'}); return; }
      const f = e.target;
      const payload = {
        doctor_name:   f.elements['doctor_name'].value,
        doctor_email:  f.elements['doctor_email'].value,
        doctor_mobile: f.elements['doctor_mobile'].value,
        doctor_license:f.elements['doctor_license'].value,
      };
      try{
        await postJSON(`${apiBase}/clinics/${CLINIC_ID}/doctors`, payload);
        Swal.fire({icon:'success', title:'Doctor added', timer:1200, showConfirmButton:false});
        close(modal);
        await load();
      }catch(err){
        Swal.fire({icon:'error', title:'Failed to add', text: err.message || 'Error'});
      }
    });

    document.addEventListener('DOMContentLoaded', load);
  </script>
</div>
@endsection

