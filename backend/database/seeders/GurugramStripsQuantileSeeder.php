{{-- backend/resources/views/snoutiq/app-video-heatmap.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@php
  $page_title = $page_title ?? 'Night Coverage Heatmap (Gurugram)';
@endphp

@section('title', $page_title)
@section('page_title', $page_title)

@section('content')
<div class="max-w-6xl mx-auto">
  <div class="bg-white rounded-xl shadow p-4 mb-4">
    <div class="flex flex-wrap items-end gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700">Date (IST)</label>
        <input type="date" id="hm_date" class="mt-1 rounded border-gray-300">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Hour (IST)</label>
        <select id="hm_hour" class="mt-1 rounded border-gray-300">
          @php $hrs=[19,20,21,22,23,0,1,2,3,4,5,6]; @endphp
          @foreach($hrs as $h)
            <option value="{{ $h }}">{{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Weighting</label>
        <select id="hm_weight" class="mt-1 rounded border-gray-300">
          <option value="flat" selected>Flat (C/I/D = 1)</option>
          <option value="ranked">Ranked (I=1.5, C=1.2, D=1.0)</option>
        </select>
      </div>

      <div class="flex items-center gap-2 mt-6">
        <label class="inline-flex items-center gap-1 text-xs text-gray-700">
          <input type="checkbox" id="hm_show_labels" class="rounded border-gray-300">
          Show pincode labels
        </label>
        <label class="inline-flex items-center gap-1 text-xs text-gray-700">
          <input type="checkbox" id="hm_autoref" class="rounded border-gray-300">
          Auto-refresh (30s)
        </label>
      </div>

      <div class="ml-auto">
        <button id="hm_refresh" class="px-4 py-2 rounded bg-gray-800 text-white text-sm">Render Heatmap</button>
      </div>
    </div>

    <p class="text-xs text-gray-600 mt-2">
      Heat intensity = per-strip coverage mapped to that strip’s pincodes.
      States considered: <b>Committed / In-progress / Done</b> (Primary & Bench).
    </p>
  </div>

  <div class="bg-white rounded-xl shadow overflow-hidden">
    <div id="heatmap" style="height: 560px;"></div>
    <div id="hm_legend" class="p-2 text-xs text-gray-600"></div>
  </div>
</div>

{{-- Leaflet + Heat plugin --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>

<script>
(function(){
  // ===== API base =====
  const ORIGIN   = window.location.origin;
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
  const apiBase  = IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`;

  // ===== Controls =====
  const el = (s)=>document.querySelector(s);
  const h2 = (n)=>String(n).padStart(2,'0');

  // ===== Map bootstrap =====
  const map = L.map('heatmap', { zoomControl:true, attributionControl:true });
  const OSM = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  // Gurugram rough bounds; auto-fit on pins after load as well
  const ggnBounds = L.latLngBounds([[28.20, 76.70],[28.65, 77.25]]);
  map.fitBounds(ggnBounds);

  let heatLayer = null;
  let labelLayer = L.layerGroup().addTo(map);

  // ===== Data fetchers =====
  async function fetchPincodes(){
    const r = await fetch(`${apiBase}/geo/pincodes?city=Gurugram&active=1&limit=500`, {credentials:'include'});
    const j = await r.json().catch(()=>({}));
    const rows = j.pincodes || j.rows || j.data || [];
    return rows.map(x => ({
      code:  String(x.pincode || x.code),
      label: String(x.label || x.name || ''),
      lat:   Number(x.lat ?? x.latitude),
      lon:   Number(x.lon ?? x.lng ?? x.longitude)
    })).filter(p => p.code && Number.isFinite(p.lat) && Number.isFinite(p.lon));
  }

  async function fetchStrips(){
    const r = await fetch(`${apiBase}/geo/strips`, {credentials:'include'});
    const j = await r.json().catch(()=>({}));
    return (j.strips || []).map(s => ({
      id: Number(s.id),
      name: String(s.name || `Gurugram-${String(s.id).padStart(2,'0')}`),
      min: Number(s.min_lon),
      max: Number(s.max_lon),
      ctr: (Number(s.min_lon)+Number(s.max_lon))/2
    })).sort((a,b)=>a.id-b.id);
  }

  // 3-pass mapping — every pin appears; empty strips get nearest fallback
  function mapPinsToStrips(strips, pins){
    const P = pins.slice().sort((a,b)=>a.lon-b.lon);
    const out = new Map();
    for (const s of strips) out.set(s.id, []);
    const assigned = new Array(P.length).fill(false);
    const EPS = 1e-6;

    // pass 1: in-window
    for (let i=0;i<P.length;i++){
      const p = P[i];
      const s = strips.find(st => p.lon >= st.min - EPS && p.lon <= st.max + EPS);
      if (s){ out.get(s.id).push(p); assigned[i]=true; }
    }

    // pass 2: leftover -> nearest strip
    for (let i=0;i<P.length;i++){
      if (assigned[i]) continue;
      const p = P[i];
      let best=null;
      for (const st of strips){
        let score;
        if (p.lon < st.min) score = st.min - p.lon;
        else if (p.lon > st.max) score = p.lon - st.max;
        else score = Math.abs(p.lon - st.ctr);
        if (!best || score < best.score) best = {strip:st, score};
      }
      if (best) out.get(best.strip.id).push({...p, _nearest:true});
    }

    // pass 3: ensure no strip empty
    if (P.length){
      for (const st of strips){
        const arr = out.get(st.id);
        if (!arr.length){
          let nearest=null;
          for (const p of P){
            const d = Math.abs(p.lon - st.ctr);
            if (!nearest || d<nearest.dist) nearest = {...p, dist:d, _nearest:true};
          }
          if (nearest) out.set(st.id, [nearest]);
        }
      }
    }
    return out;
  }

  // IST -> UTC date/hour mapping (00–06 IST -> next day)
  function istToUtcParts(dateIst, hourIst){
    let dt = new Date(`${dateIst}T${h2(hourIst)}:00:00+05:30`);
    if ([0,1,2,3,4,5,6].includes(Number(hourIst))) dt = new Date(dt.getTime()+24*60*60*1000);
    return { date: dt.toISOString().slice(0,10), hour: dt.getUTCHours() };
  }

  // coverage fetch
  async function fetchCoverageUTC(dateUtc, hourUtc){
    const r = await fetch(`${apiBase}/video/coverage?date=${dateUtc}&hour=${hourUtc}`, {credentials:'include'});
    const j = await r.json().catch(()=>({}));
    return (j.coverage || j.rows || []);
  }

  // weight calculators
  function weightFor(state, scheme){
    const s = String(state||'').toLowerCase();
    if (!s || s==='none') return 0;
    if (scheme==='ranked'){
      if (s==='in_progress') return 1.5;
      if (s==='committed')   return 1.2;
      if (s==='done')        return 1.0;
      return 0.8; // any other non-null
    }
    // flat
    return 1.0;
  }

  function buildLegend(pointsCount, scheme, whenText){
    const wdesc = scheme==='ranked' ? 'I=1.5, C=1.2, D=1.0' : 'C/I/D = 1';
    el('#hm_legend').innerHTML =
      `Points: <b>${pointsCount}</b> &nbsp;|&nbsp; Weighting: <b>${wdesc}</b> &nbsp;|&nbsp; ${whenText}`;
  }

  function renderLabels(list){
    labelLayer.clearLayers();
    list.forEach(p=>{
      const txt = `${p.code}`;
      L.circleMarker([p.lat, p.lon], {radius:4, weight:1, color:'#111', fillColor:'#fff', fillOpacity:0.9})
        .bindTooltip(txt + (p._nearest?' (nearest)':''), {permanent:false})
        .addTo(labelLayer);
    });
  }

  async function renderHeatmap(){
    const dateIst  = el('#hm_date').value;
    const hourIst  = Number(el('#hm_hour').value || 19);
    const scheme   = el('#hm_weight').value;

    if (!dateIst) return;

    // load base data
    const [pins, strips] = await Promise.all([fetchPincodes(), fetchStrips()]);
    if (pins.length){ map.fitBounds(L.latLngBounds(pins.map(p=>[p.lat,p.lon]))); }
    const pinsByStrip = mapPinsToStrips(strips, pins);

    // coverage at selected IST hour
    const {date, hour} = istToUtcParts(dateIst, hourIst);
    const rows = await fetchCoverageUTC(date, hour);

    // Build weight per strip
    const stripWeight = new Map(); // id -> number
    rows.forEach(r=>{
      const sid = Number(r.strip_id ?? r.id);
      const wP  = weightFor(r.primary, scheme);
      const wB  = weightFor(r.bench,   scheme);
      const w   = wP + wB;
      if (!stripWeight.has(sid)) stripWeight.set(sid, 0);
      stripWeight.set(sid, stripWeight.get(sid) + w);
    });

    // Convert to heat points: every pin in that strip gets that strip weight
    const heatPoints = [];
    const labelPoints = [];
    for (const s of strips){
      const pinsIn = pinsByStrip.get(s.id) || [];
      const w = stripWeight.get(s.id) || 0;
      pinsIn.forEach(p=>{
        // Leaflet.heat expects [lat, lng, intensity]
        heatPoints.push([p.lat, p.lon, w || 0]);
        labelPoints.push(p);
      });
    }

    // draw heat
    if (heatLayer){ map.removeLayer(heatLayer); heatLayer=null; }
    heatLayer = L.heatLayer(heatPoints, {
      // radius in pixels; tweak if needed
      radius: 30, blur: 25, maxZoom: 17
    }).addTo(map);

    // labels toggle
    if (el('#hm_show_labels').checked) renderLabels(labelPoints);
    else labelLayer.clearLayers();

    const whenText = `View: ${dateIst} ${h2(hourIst)}:00 IST (UTC ${date} ${h2(hour)}:00)`;
    buildLegend(heatPoints.length, scheme, whenText);
  }

  // defaults
  document.addEventListener('DOMContentLoaded', ()=>{
    const now = new Date();
    const ist = new Date(now.toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
    const pad=n=>String(n).padStart(2,'0');
    el('#hm_date').value = `${ist.getFullYear()}-${pad(ist.getMonth()+1)}-${pad(ist.getDate())}`;
    el('#hm_hour').value = '19';

    el('#hm_refresh').addEventListener('click', renderHeatmap);
    el('#hm_show_labels').addEventListener('change', renderHeatmap);
    el('#hm_weight').addEventListener('change', renderHeatmap);
    el('#hm_hour').addEventListener('change', renderHeatmap);
    el('#hm_date').addEventListener('change', renderHeatmap);

    // auto-refresh
    let t=null;
    el('#hm_autoref').addEventListener('change', (e)=>{
      if (t){ clearInterval(t); t=null; }
      if (e.target.checked){
        t=setInterval(()=>{ if (document.visibilityState==='visible') renderHeatmap(); }, 30000);
      }
    });

    // first render
    renderHeatmap();
  });
})();
</script>
@endsection
