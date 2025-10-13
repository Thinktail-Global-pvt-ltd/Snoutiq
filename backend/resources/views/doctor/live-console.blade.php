{{-- resources/views/doctor/live-console.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Doctor Live Console</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
</head>
<body class="min-h-screen bg-gray-50">

@php
  $pathPrefix = rtrim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/');
  $socketUrl  = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000');
  $sessionUserId = session('user_id') ?? data_get(session('user'), 'id');
  $authUserId    = optional(auth()->user())->id;
  $defaultDoctor = (int)($sessionUserId ?? $authUserId ?? 501);
@endphp

<script>
  const PATH_PREFIX = @json($pathPrefix ? '/'.$pathPrefix : '');
  const SOCKET_URL  = @json($socketUrl);
  const DEFAULT_DOCTOR_ID = Number(@json($defaultDoctor));
</script>

<header class="bg-white border-b border-gray-200">
  <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
    <h1 class="text-lg font-semibold text-gray-800">Doctor Live Console</h1>
    <div class="flex items-center gap-2 text-sm">
      <span class="text-gray-600">Status:</span>
      <span id="status-chip" class="px-2 py-0.5 rounded bg-gray-200 text-gray-800">Offline</span>
      <button id="btn-toggle" class="ml-3 px-3 py-2 rounded bg-gray-600 text-white">Go Live</button>
      <button id="btn-rejoin" class="px-3 py-2 rounded bg-blue-600 text-white">Rejoin</button>
      <button id="btn-test" class="px-3 py-2 rounded bg-indigo-600 text-white">Test Server</button>
    </div>
  </div>
</header>

<main class="max-w-6xl mx-auto p-4">
  <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="bg-white border rounded-xl p-4">
      <div class="text-sm text-gray-700">Doctor ID</div>
      <div class="text-lg font-semibold" id="doctor-id">—</div>
    </div>
    <div class="bg-white border rounded-xl p-4">
      <div class="text-sm text-gray-700">Socket</div>
      <div class="text-xs text-gray-600">ID: <code id="socket-id">Not connected</code></div>
      <div class="text-xs text-gray-600">Conn: <span id="conn">disconnected</span></div>
    </div>
    <div class="bg-white border rounded-xl p-4">
      <div class="text-sm text-gray-700">Calls</div>
      <div class="text-lg font-semibold"><span id="calls-count">0</span></div>
    </div>
  </section>

  <section class="bg-white border rounded-xl p-4">
    <div class="text-sm font-semibold text-gray-800 mb-2">Debug Logs</div>
    <pre id="logs" class="text-xs text-green-700 bg-black/90 text-green-300 p-3 rounded h-48 overflow-auto"></pre>
  </section>

  <section class="mt-4 bg-white border rounded-xl p-4">
    <div class="text-sm font-semibold text-gray-800 mb-2">Incoming Calls</div>
    <div id="calls" class="text-sm text-gray-700">No calls</div>
  </section>
</main>

<!-- Incoming call modal -->
<div id="incoming-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
  <div class="bg-white rounded-xl p-6 w-full max-w-sm">
    <div class="text-lg font-semibold mb-2">Incoming Call</div>
    <div class="text-sm text-gray-700 space-y-1 mb-4">
      <div>Patient: <span id="m-patient"></span></div>
      <div>Channel: <span id="m-channel" class="break-all"></span></div>
      <div>Time: <span id="m-time"></span></div>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <button id="m-accept" class="py-2 rounded bg-green-600 text-white font-semibold">Accept</button>
      <button id="m-reject" class="py-2 rounded bg-red-600 text-white font-semibold">Reject</button>
    </div>
  </div>
 </div>

<script>
  const $ = (s)=>document.querySelector(s);
  const elChip   = $('#status-chip');
  const elToggle = $('#btn-toggle');
  const elRejoin = $('#btn-rejoin');
  const elTest   = $('#btn-test');
  const elDid    = $('#doctor-id');
  const elSid    = $('#socket-id');
  const elConn   = $('#conn');
  const elLogs   = $('#logs');
  const elCnt    = $('#calls-count');
  const elCalls  = $('#calls');
  const modal    = $('#incoming-modal');
  const elMP     = $('#m-patient');
  const elMC     = $('#m-channel');
  const elMT     = $('#m-time');

  function addLog(msg){
    const t = new Date().toLocaleTimeString();
    elLogs.textContent += `[${t}] ${msg}\n`;
    elLogs.scrollTop = elLogs.scrollHeight;
  }

  // Resolve doctorId from query or default
  const Q = new URL(location.href).searchParams;
  const DOCTOR_ID = Number(Q.get('doctorId') || DEFAULT_DOCTOR_ID || 0) || 0;
  if (elDid) elDid.textContent = String(DOCTOR_ID || '—');

  let isLive   = false;
  let isOnline = false;
  let calls    = [];
  let lastCall = null;

  const socket = io(SOCKET_URL, { transports:['websocket','polling'], withCredentials:false, path:'/socket.io/' });

  function setConn(connected){ elConn.textContent = connected ? 'connected' : 'disconnected'; }
  function setChip(state){
    if (state === 'online'){ elChip.textContent='Online'; elChip.className='px-2 py-0.5 rounded bg-green-100 text-green-800'; }
    else if (state === 'connecting'){ elChip.textContent='Connecting'; elChip.className='px-2 py-0.5 rounded bg-yellow-100 text-yellow-800'; }
    else { elChip.textContent='Offline'; elChip.className='px-2 py-0.5 rounded bg-gray-200 text-gray-800'; }
  }

  function renderCalls(){
    elCnt.textContent = String(calls.length);
    if (!calls.length) { elCalls.textContent = 'No calls'; return; }
    elCalls.innerHTML = calls.map(c=>`<div class="py-1"><strong>${c.id}</strong> • patient ${c.patientId} • ${c.channel}</div>`).join('');
  }

  function goLive(){
    isLive = true; setChip('connecting'); addLog('Going live...');
    if (!socket.connected) socket.connect();
    if (DOCTOR_ID) socket.emit('join-doctor', DOCTOR_ID);
    elToggle.textContent = 'Go Offline';
  }
  function goOffline(){
    isLive = false; isOnline = false; setChip('offline'); addLog('Going offline...');
    socket.emit('leave-doctor', DOCTOR_ID);
    try{ socket.disconnect(); }catch{}
    elToggle.textContent = 'Go Live';
  }

  elToggle.addEventListener('click', ()=> isLive ? goOffline() : goLive());
  elRejoin.addEventListener('click', ()=>{ if (socket.connected && DOCTOR_ID) socket.emit('join-doctor', DOCTOR_ID); });
  elTest.addEventListener('click', ()=>{ socket.emit('get-server-status'); });

  socket.on('connect', ()=>{ elSid.textContent = socket.id; setConn(true); addLog('Socket connected'); if (isLive && DOCTOR_ID) socket.emit('join-doctor', DOCTOR_ID); });
  socket.on('connect_error', (e)=>{ setConn(false); addLog('connect_error: ' + (e?.message || e)); });
  socket.on('disconnect', ()=>{ setConn(false); addLog('Socket disconnected'); isOnline=false; setChip('offline'); });

  socket.on('doctor-online', (data)=>{
    if (Number(data?.doctorId) === Number(DOCTOR_ID)) { isOnline = true; setChip('online'); addLog('Doctor online ack'); }
  });
  socket.on('doctor-offline', (data)=>{
    if (Number(data?.doctorId) === Number(DOCTOR_ID)) { isOnline = false; setChip('offline'); addLog('Doctor offline'); }
  });

  socket.on('server-status', (s)=>{ addLog('server-status: ' + JSON.stringify(s)); });

  socket.on('call-requested', (payload)=>{
    lastCall = payload || null;
    const call = { id: payload?.callId, patientId: payload?.patientId, channel: payload?.channel };
    if (!calls.find(c=>c.id===call.id)) calls.push(call);
    renderCalls();
    // Show popup
    try { elMP.textContent = String(call.patientId||''); elMC.textContent = String(call.channel||''); elMT.textContent = new Date().toLocaleString(); modal.classList.remove('hidden'); } catch {}
    addLog('Incoming call: ' + JSON.stringify(call));
  });

  $('#m-accept')?.addEventListener('click', ()=>{
    try {
      modal.classList.add('hidden');
      const ch = (lastCall?.channel || elMC?.textContent || '').trim();
      const callId = (lastCall?.callId || lastCall?.id || '').trim();
      if (callId) socket.emit('call-accepted', { callId, doctorId: DOCTOR_ID, patientId: lastCall?.patientId, channel: ch });
      const next = (PATH_PREFIX||'') + '/call-page/' + encodeURIComponent(ch) + '?uid=' + encodeURIComponent(DOCTOR_ID) + '&role=host' + (callId ? ('&callId='+encodeURIComponent(callId)) : '');
      location.href = next;
    } catch (e) { addLog('accept failed: '+ e?.message); }
  });
  $('#m-reject')?.addEventListener('click', ()=>{
    modal.classList.add('hidden');
    const callId = (lastCall?.callId || lastCall?.id || '').trim?.() || '';
    if (callId) socket.emit('call-rejected', { callId, reason: 'rejected' });
  });

  // Auto-start live if query ?live=1
  if (new URL(location.href).searchParams.get('live') === '1') goLive();
</script>

</body>
</html>

