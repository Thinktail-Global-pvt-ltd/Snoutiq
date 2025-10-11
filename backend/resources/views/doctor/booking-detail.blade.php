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

  <div class="mt-6 grid grid-cols-1 gap-4">
    <div>
      <div class="text-sm text-gray-500 mb-1">Symptoms</div>
      <pre id="symptoms" class="whitespace-pre-wrap bg-gray-50 border rounded p-3 text-sm"></pre>
    </div>
    <div>
      <div class="text-sm text-gray-500 mb-1">AI Summary</div>
      <pre id="summary" class="whitespace-pre-wrap bg-gray-50 border rounded p-3 text-sm"></pre>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const BOOKING_ID = Number(@json($bookingId));
  async function api(url){
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    const t = await res.text();
    let j=null; try{ j = JSON.parse(t.replace(/^\uFEFF/, '')); }catch{}
    return { ok: res.ok, status: res.status, json: j, raw: t };
  }
  const esc = (v) => String(v ?? '').replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
  function pretty(v){ if(v==null) return ''; if(typeof v==='string'){ try{ const j=JSON.parse(v); return JSON.stringify(j,null,2) }catch{} return v } return JSON.stringify(v,null,2); }

  async function load(){
    const res = await api(`/api/bookings/details/${BOOKING_ID}`);
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
        <div class="text-xs text-gray-500">Location</div>
        <div class="text-sm">${esc(b.user_address ?? '')}</div>
      </div>
      <div class="p-3 border rounded">
        <div class="text-xs text-gray-500">Coordinates</div>
        <div class="text-sm">${esc(b.user_latitude ?? '')}, ${esc(b.user_longitude ?? '')}</div>
      </div>`;

    document.getElementById('symptoms').textContent = pretty(b.symptoms);
    document.getElementById('summary').textContent = pretty(b.ai_summary);
  }
  document.addEventListener('DOMContentLoaded', load);
</script>
@endsection
