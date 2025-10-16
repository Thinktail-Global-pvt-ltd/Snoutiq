{{-- resources/views/snoutiq/api-test-doctor-slots.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@php
  $page_title = 'API Test: /api/video/slots/doctor';
  $today = date('Y-m-d');
@endphp

@section('title', $page_title)
@section('page_title', $page_title)

@section('content')
<div class="max-w-5xl mx-auto">
  <div class="bg-white rounded-xl shadow p-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Doctor</label>
        <select id="doctor_id" class="mt-1 w-full rounded border-gray-300">
          @foreach(($doctors ?? []) as $doc)
            <option value="{{ $doc->id }}">{{ $doc->doctor_name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Date (IST)</label>
        <input type="date" id="date_ist" class="mt-1 w-full rounded border-gray-300" value="{{ $today }}">
      </div>
      <div class="flex items-end">
        <button id="btnRun" class="w-full md:w-auto px-4 py-2 rounded bg-gray-800 text-white">Run</button>
      </div>
    </div>

    <div class="mt-3 text-xs text-gray-600" id="meta"></div>

    <div class="mt-3">
      <div class="text-sm font-medium text-gray-800">Request</div>
      <pre id="req" class="mt-1 p-2 bg-gray-50 border rounded text-xs overflow-auto"></pre>
    </div>

    <div class="mt-3">
      <div class="text-sm font-medium text-gray-800">Response</div>
      <pre id="out" class="mt-1 p-2 bg-gray-50 border rounded text-xs overflow-auto" style="max-height: 420px"></pre>
    </div>
  </div>
</div>

<script>
  (function(){
    const ORIGIN = window.location.origin;
    const api = `${ORIGIN}/api/video/slots/doctor`;
    const el = (s)=>document.querySelector(s);

    function setReq(url){ el('#req').textContent = `GET ${url}\n\n$ curl -s "${url}" | jq`; }
    function setOut(obj){ el('#out').textContent = JSON.stringify(obj, null, 2); }
    function setMeta(msg){ el('#meta').textContent = msg; }

    async function run(){
      const doctorId = Number(el('#doctor_id')?.value || 0);
      const date = String(el('#date_ist')?.value || '');
      if(!doctorId){ return alert('Pick a doctor'); }
      if(!date){ return alert('Pick a date'); }
      const url = `${api}?doctor_id=${doctorId}&date=${encodeURIComponent(date)}&tz=IST`;
      setReq(url);
      setMeta('Loading...');
      try{
        const r = await fetch(url, { credentials:'include', headers:{Accept:'application/json'} });
        const t = await r.text(); let j=null; try{ j=JSON.parse(t);}catch{ j={raw:t}; }
        setOut(j);
        const count = Array.isArray(j?.slots) ? j.slots.length : 0;
        setMeta(`HTTP ${r.status} • Slots: ${count}`);
      }catch(e){ setOut({error: String(e)}); setMeta('Network error'); }
    }

    document.addEventListener('DOMContentLoaded', ()=>{
      document.querySelector('#btnRun')?.addEventListener('click', run);
      // auto-run once
      run();
    });
  })();
</script>
@endsection

