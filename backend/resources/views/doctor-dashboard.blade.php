{{-- resources/views/doctor-dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Doctor Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
  <style>
    @keyframes ring{0%{transform:rotate(0)}10%{transform:rotate(15deg)}20%{transform:rotate(-15deg)}30%{transform:rotate(10deg)}40%{transform:rotate(-10deg)}50%{transform:rotate(5deg)}60%{transform:rotate(-5deg)}100%{transform:rotate(0)}}
    .ringing{animation:ring 1s infinite}
  </style>
</head>
<body class="h-screen bg-gray-50">

@php
  // ===== App + Socket config =====
  $pathPrefix = rtrim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/');
  $socketUrl  = $socketUrl ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000'));

  // Try to determine doctor id from server-side sources first
  $serverCandidate = session('user_id')
        ?? data_get(session('user'), 'id')
        ?? optional(auth()->user())->id
        ?? request('doctorId');

  $serverDoctorId = $serverCandidate ? (int)$serverCandidate : null;

  // Sidebar links
  $aiChatUrl   = ($pathPrefix ? "/$pathPrefix" : '') . '/pet-dashboard';
  $thisPageUrl = ($pathPrefix ? "/$pathPrefix" : '') . '/doctor' . ($serverDoctorId ? ('?doctorId=' . urlencode($serverDoctorId)) : '');
@endphp

<script>
  // ===== Path prefix for links in JS =====
  const PATH_PREFIX = @json($pathPrefix ? "/$pathPrefix" : "");

  // ===== Socket server URL =====
  const SOCKET_URL = @json($socketUrl);

  // ===== ID resolution (client-side fallback too) =====
  const fromServer   = Number(@json($serverDoctorId ?? null)) || null;

  const fromQuery = (()=> {
    const u = new URL(location.href);
    const v = u.searchParams.get('doctorId');
    return v ? Number(v) : null;
  })();

  function readAuthFull(){
    try{
      const raw = sessionStorage.getItem('auth_full') || localStorage.getItem('auth_full');
      if(!raw) return null;
      return JSON.parse(raw);
    }catch(_){ return null; }
  }
  const af = readAuthFull();
  const fromStorage = (()=> {
    if(!af) return null;
    const id1 = af.user_id;
    const id2 = af.user && af.user.id;
    return Number(id1 || id2) || null;
  })();

  // Final pick order: server -> query -> storage -> 501
  const DOCTOR_ID = fromServer || fromQuery || fromStorage || 501;

  console.log('[doctor-dashboard] ID sources ⇒ session.user.id:', @json(data_get(session('user'), 'id')),
              '| session.user_id:', @json(session('user_id')), '| auth()->id():', @json(optional(auth()->user())->id),
              '| request("doctorId"):', @json(request('doctorId') ?? null),
              '| storage.user_id:', fromStorage);
  console.log('[doctor-dashboard] DOCTOR_ID (final):', DOCTOR_ID);
</script>

<div class="flex h-full">
  {{-- Sidebar --}}
  <aside class="w-64 bg-gradient-to-b from-indigo-700 to-purple-700 text-white">
    <div class="h-16 flex items-center px-6 border-b border-white/10">
      <span class="text-xl font-bold tracking-wide">SnoutIQ</span>
    </div>

    <nav class="px-3 py-4 space-y-1">
      <div class="px-3 text-xs font-semibold tracking-wider text-white/70 uppercase mb-2">Menu</div>

      <a href="{{ $aiChatUrl }}"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v7a2 2 0 01-2 2h-4l-5 4v-4z"/>
        </svg>
        <span class="text-sm font-medium">AI Chat</span>
      </a>

      <a href="{{ $thisPageUrl }}"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <span class="text-sm font-medium">Video Consultation</span>
      </a>
    </nav>
  </aside>

  {{-- Main --}}
  <main class="flex-1 flex flex-col">
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <div class="flex items-center gap-3">
        <h1 class="text-lg font-semibold text-gray-800">Doctor Dashboard</h1>
        <span id="status-dot" class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400" title="Connecting…"></span>
        <span id="status-pill" class="hidden px-3 py-1 rounded-full text-xs font-bold">…</span>
      </div>

      <div class="flex items-center gap-3">
        <button id="toggle-diag"
                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-800">
          Diagnostics
        </button>
        <div class="text-right">
          <div class="text-sm font-medium text-gray-900">{{ auth()->user()->name ?? 'Doctor' }}</div>
          <div class="text-xs text-gray-500">{{ auth()->user()->role ?? 'doctor' }}</div>
        </div>
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold">
          {{ strtoupper(substr(auth()->user()->name ?? 'D',0,1)) }}
        </div>
      </div>
    </header>

    <section class="flex-1 p-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 order-1">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-base font-semibold text-gray-800">
                Incoming Calls (<span id="calls-count">0</span>)
              </h2>
              <div class="text-xs text-gray-500">Keep this page open to receive calls</div>
            </div>

            <div id="calls" class="space-y-3">
              <div id="no-calls" class="bg-gray-50 text-gray-600 rounded-lg p-6 text-center border border-dashed border-gray-200">
                <p>No incoming calls at the moment</p>
                <p class="text-xs mt-1" id="no-calls-sub">Connect to receive calls</p>
              </div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-1 order-2">
          <div id="diagnostics" class="hidden space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
              <div class="text-sm text-gray-700 mb-2">Doctor ID: <strong id="doctor-id">…</strong></div>
              <div class="text-sm space-y-1">
                <div>Socket ID: <code id="socket-id" class="text-gray-600">Not connected</code></div>
                <div>Connection Status: <strong id="conn-status" class="text-gray-800">connecting</strong></div>
                <div>Socket Connected: <strong id="socket-connected" class="text-gray-800">No</strong></div>
                <div>Is Online: <strong id="is-online" class="text-gray-800">No</strong></div>
              </div>
              <div class="mt-4 grid grid-cols-3 gap-2">
                <button id="btn-rejoin" class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">🔄 Rejoin</button>
                <button id="btn-test" class="px-3 py-2 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-black">🧪 Test</button>
                <button id="btn-clear" class="px-3 py-2 rounded-lg bg-gray-200 text-gray-900 text-sm font-medium hover:bg-gray-300">🗑️ Clear</button>
              </div>
            </div>

            <div class="bg-black text-green-400 rounded-xl p-4 text-xs font-mono max-h-64 overflow-y-auto border border-gray-800">
              <div class="font-bold mb-2">Debug Logs</div>
              <div id="logs"></div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</div>

{{-- Incoming Call Modal --}}
<div id="incoming-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-2xl w-[92%] max-w-md p-6">
    <div class="flex items-center gap-3 mb-4">
      <svg class="w-9 h-9 text-rose-600 ringing" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
      </svg>
      <h3 class="text-xl font-semibold text-gray-800">Incoming Call</h3>
    </div>
    <div class="space-y-2 text-gray-700">
      <div><span class="font-semibold">Patient:</span> <span id="m-patient"></span></div>
      <div><span class="font-semibold">Channel:</span> <span id="m-channel" class="break-all"></span></div>
      <div class="text-xs text-gray-500"><span class="font-semibold">Time:</span> <span id="m-time"></span></div>
    </div>
    <div class="mt-6 grid grid-cols-2 gap-3">
      <button id="m-accept" class="py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold shadow">✅ Accept</button>
      <button id="m-reject" class="py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold shadow">❌ Reject</button>
    </div>
  </div>
</div>

<script>
  // ===== state =====
  let incomingCalls=[]; let activeModalCall=null; let isOnline=false; let connectionStatus='connecting'; let debugLogs=[];
  const elSocketId=document.getElementById('socket-id');
  const elConnStatus=document.getElementById('conn-status');
  const elSockConnected=document.getElementById('socket-connected');
  const elIsOnline=document.getElementById('is-online');
  const elStatusPill=document.getElementById('status-pill');
  const elStatusDot=document.getElementById('status-dot');
  const elCallsWrap=document.getElementById('calls');
  const elCallsCount=document.getElementById('calls-count');
  const elNoCalls=document.getElementById('no-calls');
  const elNoCallsSub=document.getElementById('no-calls-sub');
  const elLogs=document.getElementById('logs');
  const btnRejoin=document.getElementById('btn-rejoin');
  const btnTest=document.getElementById('btn-test');
  const btnClear=document.getElementById('btn-clear');
  const btnToggleDiag=document.getElementById('toggle-diag');
  const diagPanel=document.getElementById('diagnostics');
  const modal=document.getElementById('incoming-modal');
  const mPatient=document.getElementById('m-patient');
  const mChannel=document.getElementById('m-channel');
  const mTime=document.getElementById('m-time');
  const mAccept=document.getElementById('m-accept');
  const mReject=document.getElementById('m-reject');
  document.getElementById('doctor-id').textContent = String(DOCTOR_ID);

  btnToggleDiag.addEventListener('click',()=>diagPanel.classList.toggle('hidden'));

  function addLog(m){ const ts=new Date().toLocaleTimeString(); const line=`${ts}: ${m}`; console.log(line); debugLogs=[...debugLogs.slice(-99),line]; renderLogs(); }
  function statusColor(){ if(isOnline) return {bg:'#dcfce7',color:'#16a34a',text:'🟢 ONLINE',dot:'bg-green-500',title:'Online'}; if(['connecting','joining','rejoining','connected'].includes(connectionStatus)) return {bg:'#fef9c3',color:'#f59e0b',text:connectionStatus==='connecting'?'🟡 CONNECTING':connectionStatus==='joining'?'🟡 JOINING ROOM':connectionStatus==='rejoining'?'🟡 REJOINING':'🟡 CONNECTED (Not in room)',dot:'bg-yellow-400',title:'Connecting…'}; return {bg:'#fee2e2',color:'#dc2626',text:'🔴 OFFLINE',dot:'bg-red-500',title:'Offline'}; }
  function renderHeader(){ elConnStatus.textContent=connectionStatus; elSockConnected.textContent=socket.connected?'Yes':'No'; elIsOnline.textContent=isOnline?'Yes':'No'; elSocketId.textContent=socket.id||'Not connected'; const s=statusColor(); elStatusPill.style.background=s.bg; elStatusPill.style.color=s.color; elStatusPill.textContent=s.text; elStatusPill.classList.remove('hidden'); elStatusDot.className=`inline-block w-2.5 h-2.5 rounded-full ${s.dot}`; elStatusDot.title=s.title; }
  function renderCalls(){ elCallsWrap.querySelectorAll('.call-card').forEach(n=>n.remove()); elCallsCount.textContent=String(incomingCalls.length); elNoCalls.style.display=incomingCalls.length?'none':'block'; elNoCallsSub.textContent=isOnline?"You'll be notified when patients request calls":"Connect to receive calls"; for (const call of incomingCalls){ const card=document.createElement('div'); card.className='call-card bg-amber-50 border border-amber-300 rounded-lg p-4'; card.innerHTML=`<div class="font-semibold mb-1">📞 Incoming Call</div><div class="text-sm"><strong>Patient:</strong> ${call.patientId}</div><div class="text-sm"><strong>Channel:</strong> ${call.channel}</div><div class="text-[11px] text-gray-600 mt-1"><strong>Time:</strong> ${new Date(call.timestamp).toLocaleTimeString()}</div><div class="mt-3 flex gap-2"><button data-action="accept" data-id="${call.id}" class="px-3 py-2 rounded bg-green-600 text-white text-sm font-semibold hover:bg-green-700">✅ Accept</button><button data-action="reject" data-id="${call.id}" class="px-3 py-2 rounded bg-red-600 text-white text-sm font-semibold hover:bg-red-700">❌ Reject</button></div>`; elCallsWrap.appendChild(card);} }
  function renderLogs(){ if(!elLogs) return; elLogs.innerHTML=debugLogs.map(l=>`<div>${l}</div>`).join(''); elLogs.parentElement.scrollTop=elLogs.parentElement.scrollHeight; }
  function removeCallById(id){ incomingCalls = incomingCalls.filter(c => c.id !== id); renderCalls(); if (activeModalCall && activeModalCall.callId === id) hideModal(); }
  function showModalFor(call){ activeModalCall = call; mPatient.textContent = call.patientId; mChannel.textContent = call.channel; mTime.textContent = new Date(call.timestamp).toLocaleTimeString(); modal.classList.remove('hidden'); }
  function hideModal(){ modal.classList.add('hidden'); activeModalCall = null; }

  // ===== Socket =====
  const socket=io(SOCKET_URL,{transports:['websocket','polling'],withCredentials:false,path:'/socket.io'});

  function joinDoctorRoom(){
    if(isOnline){addLog('⚠️ Already online, skipping join-doctor');return;}
    addLog(`🏥 Emitting join-doctor for ID: ${DOCTOR_ID}`);
    connectionStatus='joining'; renderHeader();
    socket.emit('join-doctor', DOCTOR_ID);
    setTimeout(()=>{ if(!isOnline && socket.connected){ addLog('⚠️ TIMEOUT: No doctor-online in 3s → retrying'); socket.emit('join-doctor', DOCTOR_ID); }}, 3000);
  }

  socket.on('connect',()=>{ addLog('✅ Socket connected'); connectionStatus='connected'; renderHeader(); joinDoctorRoom(); });
  socket.on('disconnect',(r)=>{ addLog(`❌ Socket disconnected (${r})`); connectionStatus='disconnected'; isOnline=false; renderHeader(); });
  socket.on('connect_error',(e)=>{ addLog(`❌ connect_error: ${e.message}`); connectionStatus='error'; renderHeader(); });

  socket.on('doctor-online',(d)=>{ if(Number(d.doctorId)===DOCTOR_ID){ isOnline=true; connectionStatus='online'; renderHeader(); }});
  socket.on('doctor-offline',(d)=>{ if(Number(d.doctorId)===DOCTOR_ID){ isOnline=false; connectionStatus='offline'; renderHeader(); }});

  socket.on('call-requested',(callData)=>{ addLog(`📞 call-requested: ${JSON.stringify(callData)}`); const id=callData.callId; if (incomingCalls.some(c=>c.id===id)) { addLog(`⚠️ Duplicate call ignored: ${id}`); return; } const enriched = { ...callData, id }; incomingCalls.push(enriched); renderCalls(); showModalFor(enriched); });

  socket.onAny((evt,...args)=>{ if(!['ping','pong'].includes(evt)) addLog(`📡 ${evt} ${JSON.stringify(args)}`) });
  socket.emit('get-server-status');
  socket.on('server-status',(s)=>addLog(`📊 server-status: ${JSON.stringify(s)}`));

  document.getElementById('calls').addEventListener('click',(e)=>{
    const btn=e.target.closest('button[data-action]'); if(!btn) return;
    const action=btn.dataset.action; const id=btn.dataset.id;
    const call=incomingCalls.find(c=>c.id===id); if(!call) return;
    if(action==='accept'){
      addLog(`✅ Accepting call: ${id}`); removeCallById(id);
      socket.emit('call-accepted',{callId:call.id,doctorId:DOCTOR_ID,patientId:call.patientId,channel:call.channel});
      window.location.href = `${PATH_PREFIX}/call-page/${encodeURIComponent(call.channel)}?uid=${encodeURIComponent(DOCTOR_ID)}&role=host&callId=${encodeURIComponent(call.id)}`;
    }
    if(action==='reject'){
      addLog(`❌ Rejecting call: ${id}`); removeCallById(id);
      socket.emit('call-rejected',{callId:call.id,doctorId:DOCTOR_ID,patientId:call.patientId});
    }
  });

  mAccept.addEventListener('click',()=>{
    if(!activeModalCall) return;
    const c=activeModalCall; addLog(`✅ Accepting (modal) call: ${c.callId}`); hideModal(); removeCallById(c.callId);
    socket.emit('call-accepted',{callId:c.callId,doctorId:DOCTOR_ID,patientId:c.patientId,channel:c.channel});
    window.location.href = `${PATH_PREFIX}/call-page/${encodeURIComponent(c.channel)}?uid=${encodeURIComponent(DOCTOR_ID)}&role=host&callId=${encodeURIComponent(c.callId)}`;
  });
  mReject.addEventListener('click',()=>{ if(!activeModalCall) return; const c=activeModalCall; addLog(`❌ Rejecting (modal) call: ${c.callId}`); hideModal(); removeCallById(c.callId); socket.emit('call-rejected',{callId:c.callId,doctorId:DOCTOR_ID,patientId:c.patientId}); });

  const safeBind=(el,evt,fn)=>{ if(el) el.addEventListener(evt, fn); };
  safeBind(btnRejoin,'click',()=>{ addLog('🔄 Manual rejoin triggered'); isOnline=false; connectionStatus='rejoining'; renderHeader(); socket.emit('join-doctor', DOCTOR_ID); });
  safeBind(btnTest,'click',()=>{ addLog('🧪 Sending ping'); socket.emit('ping',{doctorId:DOCTOR_ID,timestamp:Date.now()}); socket.once('pong',d=>addLog(`🏓 pong: ${JSON.stringify(d)}`));});
  safeBind(btnClear,'click',()=>{ debugLogs=[]; renderLogs(); });

  window.addEventListener('beforeunload',()=>{ if(socket.connected){ addLog(`🚪 leave-doctor ${DOCTOR_ID}`); socket.emit('leave-doctor', DOCTOR_ID); }});

  renderHeader(); renderCalls(); renderLogs();
</script>
</body>
</html>
