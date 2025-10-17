{{-- backend/resources/views/snoutiq/app-video-night-coverage.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@php
  $page_title = $page_title ?? 'Night Video Coverage';
  $readonly   = (bool) ($readonly ?? true);
@endphp

@section('title', $page_title)
@section('page_title', $page_title)

@section('content')
<div class="max-w-5xl mx-auto">

  {{-- ===== Top: Doctor dropdown (commit flow parity) ===== --}}
  <div class="bg-white rounded-xl shadow p-4 mb-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Doctor</label>
        <select id="doctor_id" class="mt-1 w-full rounded border-gray-300 focus:ring-indigo-500 focus:border-indigo-500">
          @if(isset($doctors) && $doctors->count())
            @foreach($doctors as $doc)
              <option value="{{ $doc->id }}">{{ $doc->doctor_name }}</option>
            @endforeach
          @else
            <option value="">No doctors found</option>
          @endif
        </select>
        <p class="text-xs text-gray-500 mt-1">Used when committing a slot from “Find Slots Near Me”.</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Service Type</label>
        <input type="text" value="video" disabled class="mt-1 w-full rounded border-gray-300 bg-gray-50 text-gray-600">
      </div>
    </div>
  </div>

  {{-- =========================================================
       ORIGINAL GRID (unchanged behavior)
       ========================================================= --}}
  <div class="bg-white rounded-xl shadow p-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-base font-semibold text-gray-800">Night Video Coverage (19:00–07:00 IST)</h3>
      <div class="flex items-center gap-2">
        <input type="date" id="night_date" class="rounded border-gray-300 text-sm">
        <label class="inline-flex items-center gap-1 text-xs text-gray-600">
          <input type="checkbox" id="night_autoref" class="rounded border-gray-300">
          Auto-refresh (30s)
        </label>
        <button id="btnNightPublish" class="px-3 py-1.5 rounded bg-indigo-600 text-white text-sm">Publish Tonight</button>
        <button id="btnNightReset" class="px-3 py-1.5 rounded bg-rose-600 text-white text-sm">Reset Data</button>
        <button id="btnNightLoad" class="px-3 py-1.5 rounded bg-gray-800 text-white text-sm">Load Coverage</button>
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-600 mb-2">
      <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">O = Open</span>
      <span class="px-2 py-0.5 rounded bg-green-100 text-green-800">C = Committed</span>
      <span class="px-2 py-0.5 rounded bg-green-100 text-green-800">I = In-progress</span>
      <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800">D = Done</span>
      <span class="px-2 py-0.5 rounded bg-red-100 text-red-800">X = Cancelled</span>
      <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700">– = None</span>
    </div>

    <p class="text-xs text-gray-600">
      Rows = <code>geo_strips</code> (Gurugram). Coverage API: <code>/api/video/coverage</code> (IST→UTC per hour).<br>
      Each row shows all Gurugram pincodes mapped to that strip’s longitude window (empty bands fall back to nearest pincode in tooltip).
    </p>

    <div id="nightMatrixWrap" class="mt-3 overflow-x-auto"></div>
    <div id="nightSummary" class="mt-2 text-xs text-gray-700"></div>

    <div class="mt-6 border-t pt-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
        <div>
          <label class="block text-sm font-medium text-gray-700">Latitude</label>
          <input type="number" step="0.000001" id="route_lat" class="mt-1 w-full rounded border-gray-300" placeholder="28.4601">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Longitude</label>
          <input type="number" step="0.000001" id="route_lon" class="mt-1 w-full rounded border-gray-300" placeholder="77.0305">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Timestamp (IST)</label>
          <input type="text" id="route_ts" class="mt-1 w-full rounded border-gray-300" placeholder="YYYY-MM-DDTHH:MM:SS+05:30">
        </div>
      </div>
      <div class="mt-2 flex items-center gap-2">
        <button id="btnRouteTest" class="px-3 py-1.5 rounded bg-teal-600 text-white text-sm">Try Routing</button>
        <div id="routeOut" class="text-sm text-gray-700"></div>
      </div>
    </div>
  </div>

  {{-- SweetAlert2 (shared) --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- GRID JS (original) --}}
  <script>
    (function(){
      const ORIGIN   = window.location.origin;
      const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
      const apiBase  = window.SN_API_BASE || (IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`);

      // expose once; if already present, reuse
      window.SN_API_BASE = apiBase;
      window.Csrf = window.Csrf || {
        ready:false,
        async ensure(){ if(this.ready) return; await fetch(`${ORIGIN}/sanctum/csrf-cookie`,{credentials:'include'}); this.ready=true; },
        token(){ const m=document.cookie.match(/XSRF-TOKEN=([^;]+)/); return m? decodeURIComponent(m[1]):''; },
        opts(method,body,extra={}){ return { method, credentials:'include', headers:{'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-XSRF-TOKEN':this.token(),...extra}, body: body? JSON.stringify(body):undefined }; }
      };

      const hoursIST = [19,20,21,22,23,0,1,2,3,4,5,6];
      const el=(s)=>document.querySelector(s), h2=(n)=>String(n).padStart(2,'0'), esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
      const toast=(m,ok=true)=>{ if(window.Swal) Swal.fire({toast:true,position:'top',timer:1000,showConfirmButton:false,icon:ok?'success':'error',title:String(m)}); };

      async function fetchGgnPincodes(){
        const r=await fetch(`${apiBase}/geo/pincodes?city=Gurugram&active=1&limit=500`,{credentials:'include'});
        const j=await r.json().catch(()=>({})); const rows=j.pincodes||j.rows||j.data||[];
        return rows.map(x=>({code:String(x.pincode||x.code),label:String(x.label||x.name||''),lon:Number(x.lon??x.lng??x.longitude)})).filter(p=>p.code&&Number.isFinite(p.lon));
      }
      async function fetchStrips(){
        const r=await fetch(`${apiBase}/geo/strips`,{credentials:'include'});
        const j=await r.json().catch(()=>({}));
        return (j.strips||[]).map(s=>({id:Number(s.id),name:String(s.name||`Gurugram-${String(s.id).padStart(2,'0')}`),min:Number(s.min_lon),max:Number(s.max_lon),ctr:(Number(s.min_lon)+Number(s.max_lon))/2})).sort((a,b)=>a.id-b.id);
      }
      function mapPinsToStrips(strips,pins){
        const P=pins.slice().sort((a,b)=>a.lon-b.lon); const out=new Map(); for(const s of strips) out.set(s.id,[]);
        const assigned=new Array(P.length).fill(false), EPS=1e-6;
        // pass1
        for(let i=0;i<P.length;i++){ const p=P[i]; const s=strips.find(st=>p.lon>=st.min-EPS&&p.lon<=st.max+EPS); if(s){ out.get(s.id).push(p); assigned[i]=true; } }
        // pass2 leftover → nearest
        for(let i=0;i<P.length;i++){ if(assigned[i]) continue; const p=P[i]; let best=null;
          for(const st of strips){ let score; if(p.lon<st.min) score=st.min-p.lon; else if(p.lon>st.max) score=p.lon-st.max; else score=Math.abs(p.lon-st.ctr); if(!best||score<best.score) best={strip:st,score}; }
          if(best) out.get(best.strip.id).push({...p,_nearest:true});
        }
        // pass3 ensure non-empty
        if(P.length){ for(const st of strips){ const arr=out.get(st.id); if(!arr.length){ let nearest=null; for(const p of P){ const d=Math.abs(p.lon-st.ctr); if(!nearest||d<nearest.dist) nearest={...p,dist:d,_nearest:true}; } if(nearest) out.set(st.id,[nearest]); } } }
        return out;
      }
      function istToUtcParts(dateIst,hourIst){ let dt=new Date(`${dateIst}T${h2(hourIst)}:00:00+05:30`); if([0,1,2,3,4,5,6].includes(Number(hourIst))) dt=new Date(dt.getTime()+86400000); return {date:dt.toISOString().slice(0,10),hour:dt.getUTCHours()}; }
      const allow=new Set(['committed','in_progress','done']);
      const short=(st)=>({committed:'C',in_progress:'I',done:'D'})[String(st||'').toLowerCase()]??'–';
      const pretty=(st)=>({committed:'committed',in_progress:'in progress',done:'done'})[String(st||'').toLowerCase()]??'none';
      const color=(st)=> st==='committed'||st==='in_progress' ? 'bg-green-100 text-green-800' : (st==='done' ? 'bg-blue-100 text-blue-800' : 'bg-gray-50 text-gray-500');
      const onlyFulfilled=(st)=>{ const s=String(st||'').toLowerCase(); return allow.has(s)? s:null; };

      function renderMatrix(matrix, stripRows){
        if(!stripRows.length){ el('#nightMatrixWrap').innerHTML='<div class="text-xs text-red-600">No strips loaded. Check API.</div>'; el('#nightSummary').textContent=''; return; }
        let totals={committed:0,in_progress:0,done:0};
        let html='<table class="min-w-full text-xs border border-gray-200"><thead><tr class="bg-gray-50">';
        html+='<th class="px-2 py-1 text-left">Strip</th>'; hoursIST.forEach(h=> html+=`<th class="px-2 py-1 text-center">${h2(h)}</th>`); html+='</tr></thead><tbody>';
        stripRows.forEach(r=>{
          const title=r.tooltip? ` title="${esc(r.tooltip)}"`:''; html+=`<tr class="border-t"><td class="px-2 py-1 whitespace-nowrap font-medium"${title}>#${r.id} - ${esc(r.name)}</td>`;
          hoursIST.forEach(h=>{ const c=(matrix[r.id]&&matrix[r.id][h])||{primary:null,bench:null}; const p=onlyFulfilled(c.primary), b=onlyFulfilled(c.bench);
            if(p) totals[p]=(totals[p]||0)+1; if(b) totals[b]=(totals[b]||0)+1;
            html+=`<td class="px-2 py-1 text-center"><div class="inline-flex gap-1">
                      <span class="px-1 rounded ${color(p)}" title="Primary: ${pretty(p)}">P:${short(p)}</span>
                      <span class="px-1 rounded ${color(b)}" title="Bench: ${pretty(b)}">B:${short(b)}</span>
                    </div></td>`;
          }); html+='</tr>';
        });
        html+='</tbody></table>'; el('#nightMatrixWrap').innerHTML=html;

        const now=new Date(); const ist=new Date(now.toLocaleString('en-US',{timeZone:'Asia/Kolkata'})); const pad=n=>String(n).padStart(2,'0');
        const stamp=`${ist.getFullYear()}-${pad(ist.getMonth()+1)}-${pad(ist.getDate())} ${pad(ist.getHours())}:${pad(ist.getMinutes())}:${pad(ist.getSeconds())} IST`;
        el('#nightSummary').innerHTML=`Committed: <b>${totals.committed||0}</b> | In-progress: <b>${totals.in_progress||0}</b> | Done: <b>${totals.done||0}</b> <span class="text-gray-500">(Last updated ${stamp})</span>`;
      }

      async function loadNightCoverage(){
        const d=el('#night_date')?.value; if(!d){ toast('Pick date',false); return; }
        const [strips,pins]=await Promise.all([fetchStrips(),fetchGgnPincodes()]);
        const pinsByStrip=mapPinsToStrips(strips,pins);
        const stripRows=strips.map(s=>{
          const list=pinsByStrip.get(s.id)||[]; const codes=list.map(p=>p.code);
          const tooltip=list.length? list.map(p=>`${p.code} — ${p.label||''}${p._nearest?' (nearest)':''}`).join('\n') : '';
          return {id:s.id, name: `${s.name}${codes.length?' — '+codes.join(', '):''}`, tooltip};
        });

        const matrix={};
        for(const h of hoursIST){
          const {date,hour}=istToUtcParts(d,h);
          const res=await fetch(`${apiBase}/video/coverage?date=${date}&hour=${hour}`,{credentials:'include'}); if(!res.ok) continue;
          const j=await res.json().catch(()=>({})); const rows=(j.coverage||j.rows||[]);
          rows.forEach(row=>{ const sid=Number(row.strip_id??row.id); matrix[sid] ||= {}; matrix[sid][h]={primary:row.primary??null, bench:row.bench??null}; });
        }
        renderMatrix(matrix, stripRows);
      }

      async function publishTonight(){ const d=el('#night_date')?.value; if(!d){ toast('Pick date',false); return; }
        await window.Csrf.ensure(); const r=await fetch(`${apiBase}/video/admin/publish?date=${encodeURIComponent(d)}&tz=IST`, window.Csrf.opts('POST')); if(!r.ok) return toast('Publish failed',false); toast('Published'); loadNightCoverage(); }
      async function resetTonight(){
        const yes=await (window.Swal? Swal.fire({title:'Reset all slots?',text:'This clears video slots and commitments.',icon:'warning',showCancelButton:true,confirmButtonText:'Yes, reset'}): Promise.resolve({isConfirmed:confirm('Reset all slots?')}));
        if(!(yes && (yes.isConfirmed||yes===true))) return;
        await window.Csrf.ensure(); const r=await fetch(`${apiBase}/video/admin/reset`, window.Csrf.opts('POST')); if(!r.ok) return toast('Reset failed',false); toast('Reset done'); loadNightCoverage();
      }
      async function tryRoute(){ const lat=parseFloat(el('#route_lat')?.value||''), lon=parseFloat(el('#route_lon')?.value||''), ts=String(el('#route_ts')?.value||'');
        if(!Number.isFinite(lat)||!Number.isFinite(lon)||!ts){ toast('Enter lat/lon/ts',false); return; }
        await window.Csrf.ensure(); const r=await fetch(`${apiBase}/video/route?tz=IST`, window.Csrf.opts('POST',{lat,lon,ts})); const j=await r.json(); el('#routeOut').textContent=`doctor_id: ${j?.doctor_id ?? 'null'}`;
      }
      let _timer=null; function setAuto(on){ if(_timer){clearInterval(_timer);_timer=null;} if(on){ _timer=setInterval(()=>{ if(document.visibilityState==='visible') loadNightCoverage(); },30000);} }
      window._loadNightCoverage = loadNightCoverage;

      document.addEventListener('DOMContentLoaded', ()=>{
        const t=new Date(); const ist=new Date(t.toLocaleString('en-US',{timeZone:'Asia/Kolkata'})); const pad=n=>String(n).padStart(2,'0');
        el('#night_date').value=`${ist.getFullYear()}-${pad(ist.getMonth()+1)}-${pad(ist.getDate())}`;
        el('#route_ts').value=`${el('#night_date').value}T19:30:00+05:30`;
        el('#btnNightLoad').addEventListener('click', loadNightCoverage);
        el('#btnNightPublish').addEventListener('click', publishTonight);
        el('#btnNightReset').addEventListener('click', resetTonight);
        el('#btnRouteTest').addEventListener('click', tryRoute);
        el('#night_autoref')?.addEventListener('change',e=>setAuto(!!e.target.checked));
      });
    })();
  </script>

  {{-- ===== Find Slots Near Me (session) – same as before ===== --}}
  <div class="max-w-5xl mx-auto mt-6">
    <div class="bg-white rounded-xl shadow p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-base font-semibold text-gray-800">Find Slots Near Me (Session Location)</h3>
        <div class="flex items-center gap-2">
          <input type="date" id="near_date" class="rounded border-gray-300 text-sm">
          <button id="btnNearFind" class="px-3 py-1.5 rounded bg-teal-600 text-white text-sm">Find</button>
          <button id="btnNearFindPin" class="px-3 py-1.5 rounded bg-emerald-600 text-white text-sm" title="Use pincode bands (no geo_strips)">Find (Pincode)</button>
        </div>
      </div>
      <div id="nearMeta" class="text-xs text-gray-600"></div>
      <div id="nearSlots" class="mt-2 text-sm"></div>
    </div>
  </div>

  <script>
    (function(){
      const apiBase = window.SN_API_BASE || `${window.location.origin}/api`;
      const el=(s)=>document.querySelector(s);
      function card(html){ return `<div class="border rounded p-2">${html}</div>`; }
      function renderSlotsPin(list, strip){
        if(!list || !list.length) return '<div class="text-xs text-gray-500">No open slots.</div>';
        let html='<div class="grid sm:grid-cols-2 gap-2">';
        list.forEach(s=>{
          const hh=String((Number(s.hour_24)+6)%24).padStart(2,'0');
          const stripTxt= strip? `Strip: <b>#${strip.id}</b> — ${strip.name}` : '';
          html+=card(`<div class="text-xs text-gray-600">Hour (IST): <b>${hh}:00</b> - ${stripTxt} - Role: ${s.role}</div>
                      <div class="mt-1"><button class="px-2 py-1 rounded bg-indigo-600 text-white text-xs" onclick="window._commitNearSlot(${s.id})">Commit</button></div>`);
        }); html+='</div>'; return html;
      }
      async function findNear(){
        const date=el('#near_date')?.value; if(!date) return;
        const r1=await fetch(`${apiBase}/video/nearest-strip`,{credentials:'include'}); if(!r1.ok){ el('#nearMeta').textContent='Location not found in session/table.'; el('#nearSlots').innerHTML=''; return; }
        const j1=await r1.json(); el('#nearMeta').innerHTML=`Nearest strip: <b>#${j1.strip.id}</b> - ${j1.strip.name}`;
        const r2=await fetch(`${apiBase}/video/slots/nearby?date=${encodeURIComponent(date)}&tz=IST`,{credentials:'include'}); const j2=await r2.json().catch(()=>({}));
        const allowed=new Set([19,20,21,22,23,0,1,2,3,4,5,6]);
        const filtered=(Array.isArray(j2?.slots)? j2.slots:[]).filter(s=> allowed.has((Number(s.hour_24)+6)%24));
        el('#nearSlots').innerHTML=renderSlotsPin(filtered, j1.strip);
        el('#nearMeta').innerHTML=`Nearest strip: <b>#${j1.strip.id}</b> - ${j1.strip.name} · Found: <b>${filtered.length}</b> open slots`;
      }
      async function findNearByPincode(){
        const date=el('#near_date')?.value; if(!date) return;
        const p=await fetch(`${apiBase}/geo/nearest-pincode`,{credentials:'include'}); if(!p.ok){ el('#nearMeta').textContent='Nearest pincode not found (session).'; el('#nearSlots').innerHTML=''; return; }
        const jp=await p.json(); const code=jp?.pincode?.code||jp?.pincode?.pincode||null; const label=jp?.pincode?.name||jp?.pincode?.label||'';
        if(!code){ el('#nearMeta').textContent='Pincode code unavailable.'; el('#nearSlots').innerHTML=''; return; }
        const r=await fetch(`${apiBase}/video/slots/nearby/pincode?date=${encodeURIComponent(date)}&code=${encodeURIComponent(code)}`,{credentials:'include'}); const j=await r.json().catch(()=>({}));
        const allowed=new Set([19,20,21,22,23,0,1,2,3,4,5,6]);
        const filtered=(Array.isArray(j?.slots)? j.slots:[]).filter(s=> allowed.has((Number(s.hour_24)+6)%24));
        el('#nearSlots').innerHTML=renderSlotsPin(filtered, j?.strip || {id:j?.strip_id, name:`Band-${j?.strip_id||''}`});
        el('#nearMeta').innerHTML=`Nearest pincode: <b>${code}</b> ${label? '('+label+')':''} · Band strip: <b>#${j?.strip_id||'?'}</b> · Found: <b>${filtered.length}</b> open slots`;
      }
      window._commitNearSlot = async function(slotId){
        try{
          await window.Csrf.ensure();
          const doctorId=Number(document.querySelector('#doctor_id')?.value||0);
          if(!doctorId){ return Swal?.fire({icon:'error',title:'Select a doctor',timer:1200,showConfirmButton:false}); }
          const r=await fetch(`${apiBase}/video/slots/${slotId}/commit`, window.Csrf.opts('POST',{doctor_id:doctorId}));
          if(r.ok){ Swal?.fire({icon:'success',title:'Committed!',timer:900,showConfirmButton:false}); document.querySelector('#btnNearFind')?.click(); if(typeof window._loadNightCoverage==='function'){ window._loadNightCoverage(); } }
          else{ const t=await r.text(); Swal?.fire({icon:'error',title:'Commit failed',text:t||String(r.status)}); }
        }catch(e){ Swal?.fire({icon:'error',title:'Commit error',text:String(e?.message||e)}); }
      };
      document.addEventListener('DOMContentLoaded', ()=>{
        const t=new Date(); const ist=new Date(t.toLocaleString('en-US',{timeZone:'Asia/Kolkata'})); const pad=n=>String(n).padStart(2,'0');
        el('#near_date').value=`${ist.getFullYear()}-${pad(ist.getMonth()+1)}-${pad(ist.getDate())}`;
        el('#btnNearFind')?.addEventListener('click', findNear);
        el('#btnNearFindPin')?.addEventListener('click', findNearByPincode);
      });
    })();
  </script>

</div>

{{-- =========================================================
     NEW SECTION: Heatmap + Graphs (separate, independent)
     ========================================================= --}}
<div class="max-w-6xl mx-auto mt-10">
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
        <label class="block text-sm font-medium text-gray-700">Heat Weighting</label>
        <select id="hm_weight" class="mt-1 rounded border-gray-300">
          <option value="flat" selected>Flat (C/I/D = 1)</option>
          <option value="ranked">Ranked (I=1.5, C=1.2, D=1.0)</option>
        </select>
      </div>
      <div class="flex items-center gap-3 mt-6">
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
        <button id="hm_refresh" class="px-4 py-2 rounded bg-gray-800 text-white text-sm">Render Heatmap & Graphs</button>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
    <div id="heatmap" style="height: 520px;"></div>
    <div id="hm_legend" class="p-2 text-xs text-gray-600"></div>
  </div>

  <div class="bg-white rounded-xl shadow p-4">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-base font-semibold text-gray-800">Coverage Graphs</h3>
      <span id="graphsSubtitle" class="text-xs text-gray-500"></span>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div>
        <div class="text-sm font-medium text-gray-700 mb-2">Hour-wise Stack (19:00–07:00 IST)</div>
        <canvas id="chartHourly" height="200"></canvas>
      </div>
      <div>
        <div class="flex items-center justify-between mb-2">
          <div class="text-sm font-medium text-gray-700">Per-strip (Selected Hour)</div>
          <div class="text-xs text-gray-500">Click any bar in left chart to change hour</div>
        </div>
        <canvas id="chartPerStrip" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

{{-- Leaflet + Heat + Chart.js --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>

<script>
  (function(){
    const ORIGIN   = window.location.origin;
    const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(location.hostname);
    const apiBase  = window.SN_API_BASE || (IS_LOCAL ? `${ORIGIN}/api` : `${ORIGIN}/backend/api`);
    const el=(s)=>document.querySelector(s), h2=(n)=>String(n).padStart(2,'0');
    const hoursIST=[19,20,21,22,23,0,1,2,3,4,5,6];

    // Map setup
    const map=L.map('heatmap',{zoomControl:true,attributionControl:true});
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);
    const ggnBounds=L.latLngBounds([[28.20,76.70],[28.65,77.25]]);
    map.fitBounds(ggnBounds);
    let heatLayer=null, labelLayer=L.layerGroup().addTo(map);

    // Fetchers
    async function fetchPincodes(){
      const r=await fetch(`${apiBase}/geo/pincodes?city=Gurugram&active=1&limit=500`,{credentials:'include'}); const j=await r.json().catch(()=>({}));
      const rows=j.pincodes||j.rows||j.data||[];
      return rows.map(x=>({code:String(x.pincode||x.code),label:String(x.label||x.name||''),lat:Number(x.lat??x.latitude),lon:Number(x.lon??x.lng??x.longitude)})).filter(p=>p.code&&Number.isFinite(p.lat)&&Number.isFinite(p.lon));
    }
    async function fetchStrips(){
      const r=await fetch(`${apiBase}/geo/strips`,{credentials:'include'}); const j=await r.json().catch(()=>({}));
      return (j.strips||[]).map(s=>({id:Number(s.id),name:String(s.name||`Gurugram-${String(s.id).padStart(2,'0')}`),min:Number(s.min_lon),max:Number(s.max_lon),ctr:(Number(s.min_lon)+Number(s.max_lon))/2})).sort((a,b)=>a.id-b.id);
    }
    function mapPinsToStrips(strips,pins){
      const P=pins.slice().sort((a,b)=>a.lon-b.lon); const out=new Map(); for(const s of strips) out.set(s.id,[]);
      const assigned=new Array(P.length).fill(false), EPS=1e-6;
      for(let i=0;i<P.length;i++){ const p=P[i]; const s=strips.find(st=>p.lon>=st.min-EPS&&p.lon<=st.max+EPS); if(s){ out.get(s.id).push(p); assigned[i]=true; } }
      for(let i=0;i<P.length;i++){ if(assigned[i]) continue; const p=P[i]; let best=null;
        for(const st of strips){ let score; if(p.lon<st.min) score=st.min-p.lon; else if(p.lon>st.max) score=p.lon-st.max; else score=Math.abs(p.lon-st.ctr); if(!best||score<best.score) best={strip:st,score}; }
        if(best) out.get(best.strip.id).push({...p,_nearest:true});
      }
      if(P.length){ for(const st of strips){ const arr=out.get(st.id); if(!arr.length){ let nearest=null; for(const p of P){ const d=Math.abs(p.lon-st.ctr); if(!nearest||d<nearest.dist) nearest={...p,dist:d,_nearest:true}; } if(nearest) out.set(st.id,[nearest]); } } }
      return out;
    }
    function istToUtcParts(dateIst,hourIst){ let dt=new Date(`${dateIst}T${h2(hourIst)}:00:00+05:30`); if([0,1,2,3,4,5,6].includes(Number(hourIst))) dt=new Date(dt.getTime()+86400000); return {date:dt.toISOString().slice(0,10),hour:dt.getUTCHours()}; }
    async function fetchCoverageUTC(dateUtc,hourUtc){ const r=await fetch(`${apiBase}/video/coverage?date=${dateUtc}&hour=${hourUtc}`,{credentials:'include'}); const j=await r.json().catch(()=>({})); return (j.coverage||j.rows||[]); }

    function weightFor(state,scheme){ const s=String(state||'').toLowerCase(); if(!s||s==='none') return 0;
      if(scheme==='ranked'){ if(s==='in_progress') return 1.5; if(s==='committed') return 1.2; if(s==='done') return 1.0; return 0.8; } return 1.0; }
    function renderLabels(list){ labelLayer.clearLayers(); list.forEach(p=>{ L.circleMarker([p.lat,p.lon],{radius:4,weight:1,color:'#111',fillColor:'#fff',fillOpacity:0.9}).bindTooltip(`${p.code}${p._nearest?' (nearest)':''}`,{permanent:false}).addTo(labelLayer); }); }

    let chartHourly=null, chartPerStrip=null, selectedHour=19;
    function normalizeState(s){ s=String(s||'').toLowerCase(); if(!s||s==='none') return 'none';
      if(s.startsWith('open')) return 'open'; if(s==='committed') return 'committed'; if(s==='in_progress'||s==='in-progress') return 'in_progress'; if(s==='done') return 'done'; if(s==='cancelled'||s==='canceled') return 'cancelled'; return 'other'; }
    function buildHourlySeries(rowsByHour){
      const cats=['open','committed','in_progress','done','cancelled','none']; const series={}; cats.forEach(k=> series[k]=hoursIST.map(()=>0));
      hoursIST.forEach((h,idx)=>{ const rows=rowsByHour.get(h)||[]; rows.forEach(r=>{
        const stP=normalizeState(r.primary), stB=normalizeState(r.bench);
        if(series[stP]!=null) series[stP][idx]+=1; if(series[stB]!=null) series[stB][idx]+=1; }); });
      return {cats,series};
    }
    function drawHourlyChart(seriesObj){
      const ctx=document.getElementById('chartHourly'); const labels=hoursIST.map(h=>h2(h)); const {cats,series}=seriesObj;
      const datasets=cats.map(k=>({label:k.replace('_',' ').toUpperCase(), data: series[k]}));
      if(chartHourly) chartHourly.destroy();
      chartHourly=new Chart(ctx,{type:'bar', data:{labels,datasets}, options:{responsive:true, scales:{x:{stacked:true}, y:{stacked:true,beginAtZero:true}}, plugins:{legend:{position:'bottom'}},
        onClick:(evt,els)=>{ if(els && els.length){ const idx=els[0].index; selectedHour=hoursIST[idx]; updatePerStripChart(); } } }});
    }
    function drawPerStripChart(rows){
      const byStrip=new Map(); rows.forEach(r=>{ const sid=Number(r.strip_id??r.id);
        const add=s=> (s==='committed'||s==='in_progress'||s==='done')?1:0;
        const v=(byStrip.get(sid)||0)+add(normalizeState(r.primary))+add(normalizeState(r.bench)); byStrip.set(sid,v); });
      const entries=Array.from(byStrip.entries()).sort((a,b)=>b[1]-a[1]);
      const labels=entries.map(([sid])=>`#${sid}`), data=entries.map(([,v])=>v);
      const ctx=document.getElementById('chartPerStrip'); if(chartPerStrip) chartPerStrip.destroy();
      chartPerStrip=new Chart(ctx,{type:'bar', data:{labels, datasets:[{label:'Fulfilled (C/I/D) count (0–2 per strip)', data}]}, options:{responsive:true, scales:{y:{beginAtZero:true, suggestedMax:2}}, plugins:{legend:{display:true,position:'bottom'}}}});
    }
    async function updatePerStripChart(){ const dateIst=el('#hm_date').value; const {date,hour}=istToUtcParts(dateIst,selectedHour); const rows=await fetchCoverageUTC(date,hour); drawPerStripChart(rows); el('#graphsSubtitle').textContent=`Selected hour: ${h2(selectedHour)}:00 IST`; }

    async function renderHeatmapAndGraphs(){
      const dateIst=el('#hm_date').value; const hourIst=Number(el('#hm_hour').value||19); const scheme=el('#hm_weight').value; if(!dateIst) return;
      const [pins,strips]=await Promise.all([fetchPincodes(),fetchStrips()]); if(pins.length){ map.fitBounds(L.latLngBounds(pins.map(p=>[p.lat,p.lon]))); }
      const pinsByStrip=mapPinsToStrips(strips,pins);
      const rowsByHour=new Map();
      for(const h of hoursIST){ const {date,hour}=istToUtcParts(dateIst,h); const rows=await fetchCoverageUTC(date,hour); rowsByHour.set(h,rows); }
      const {date,hour}=istToUtcParts(dateIst,hourIst); const rowsForHeat=rowsByHour.get(hourIst) || await fetchCoverageUTC(date,hour);
      const stripWeight=new Map(); rowsForHeat.forEach(r=>{ const sid=Number(r.strip_id??r.id); const w= (weightFor(r.primary,scheme) + weightFor(r.bench,scheme)); stripWeight.set(sid,(stripWeight.get(sid)||0)+w); });
      const heatPoints=[], labelPoints=[]; for(const s of strips){ const pinsIn=pinsByStrip.get(s.id)||[]; const w=stripWeight.get(s.id)||0; pinsIn.forEach(p=>{ heatPoints.push([p.lat,p.lon,w||0]); labelPoints.push(p); }); }
      if(heatLayer){ map.removeLayer(heatLayer); heatLayer=null; } heatLayer=L.heatLayer(heatPoints,{radius:30, blur:25, maxZoom:17}).addTo(map);
      if(el('#hm_show_labels').checked) renderLabels(labelPoints); else labelLayer.clearLayers();
      el('#hm_legend').innerHTML=`Points: <b>${heatPoints.length}</b> · Heat @ ${dateIst} ${h2(hourIst)}:00 IST`;
      const hourlySeries=buildHourlySeries(rowsByHour); drawHourlyChart(hourlySeries); selectedHour=hourIst; await updatePerStripChart();
    }

    document.addEventListener('DOMContentLoaded', ()=>{
      const now=new Date(); const ist=new Date(now.toLocaleString('en-US',{timeZone:'Asia/Kolkata'})); const pad=n=>String(n).padStart(2,'0');
      el('#hm_date').value=`${ist.getFullYear()}-${pad(ist.getMonth()+1)}-${pad(ist.getDate())}`; el('#hm_hour').value='19';
      el('#hm_refresh').addEventListener('click', renderHeatmapAndGraphs);
      el('#hm_show_labels').addEventListener('change', renderHeatmapAndGraphs);
      el('#hm_weight').addEventListener('change', renderHeatmapAndGraphs);
      el('#hm_hour').addEventListener('change', renderHeatmapAndGraphs);
      el('#hm_date').addEventListener('change', renderHeatmapAndGraphs);
      let t=null; el('#hm_autoref').addEventListener('change',e=>{ if(t){clearInterval(t); t=null;} if(e.target.checked){ t=setInterval(()=>{ if(document.visibilityState==='visible') renderHeatmapAndGraphs(); },30000); }});
      renderHeatmapAndGraphs();
    });
  })();
</script>
@endsection
