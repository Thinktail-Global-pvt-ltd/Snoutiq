{{-- resources/views/doctor-dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Doctor Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
</head>
<body class="bg-gray-50 min-h-screen">
@php
  // unified default to 127.0.0.1:4000
  $socketUrl = $socketUrl ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000'));
  $doctorId  = (int) ($doctorId ?? request('doctorId', 501));
@endphp

<div class="max-w-4xl mx-auto p-6">
  <div class="flex items-center gap-3 mb-4">
    <h1 class="text-2xl font-bold">Doctor Dashboard</h1>
    <span id="status-pill" class="px-3 py-1 rounded-full text-xs font-bold">…</span>
  </div>

  <div class="mb-3 text-sm text-gray-700">
    Doctor ID: <strong id="doctor-id">{{ $doctorId }}</strong>
  </div>

  <div class="bg-white rounded-xl shadow p-4 mb-6 text-sm">
    <div>Socket ID: <code id="socket-id">Not connected</code></div>
    <div>Connection Status: <strong id="conn-status">connecting</strong></div>
    <div>Socket Connected: <strong id="socket-connected">No</strong></div>
    <div>Is Online: <strong id="is-online">No</strong></div>
    <div class="mt-3 flex gap-2">
      <button id="btn-rejoin" class="px-3 py-2 rounded bg-blue-600 text-white">🔄 Rejoin Room</button>
      <button id="btn-test" class="px-3 py-2 rounded bg-gray-800 text-white">🧪 Test Server</button>
      <button id="btn-clear" class="px-3 py-2 rounded bg-gray-200 text-gray-900">🗑️ Clear Logs</button>
    </div>
  </div>

  <h2 class="text-xl font-semibold mb-2">Incoming Calls (<span id="calls-count">0</span>)</h2>
  <div id="calls" class="space-y-3">
    <div id="no-calls" class="bg-gray-100 text-gray-600 rounded-lg p-6 text-center">
      <p>No incoming calls at the moment</p>
      <p class="text-xs mt-1" id="no-calls-sub">Connect to receive calls</p>
    </div>
  </div>

  <div class="mt-6 bg-black text-green-400 rounded-lg p-4 text-xs font-mono max-h-64 overflow-y-auto">
    <div class="font-bold mb-2">Debug Logs</div>
    <div id="logs"></div>
  </div>
</div>

<script>
  // ===== server-config
  const SOCKET_URL=@json($socketUrl);
  const DOCTOR_ID=Number(@json($doctorId));

  // ===== state
  let incomingCalls=[]; let isOnline=false; let connectionStatus='connecting'; let debugLogs=[];
  // ===== dom
  const elSocketId=document.getElementById('socket-id');
  const elConnStatus=document.getElementById('conn-status');
  const elSockConnected=document.getElementById('socket-connected');
  const elIsOnline=document.getElementById('is-online');
  const elStatusPill=document.getElementById('status-pill');
  const elCallsWrap=document.getElementById('calls');
  const elCallsCount=document.getElementById('calls-count');
  const elNoCalls=document.getElementById('no-calls');
  const elNoCallsSub=document.getElementById('no-calls-sub');
  const elLogs=document.getElementById('logs');
  const btnRejoin=document.getElementById('btn-rejoin');
  const btnTest=document.getElementById('btn-test');
  const btnClear=document.getElementById('btn-clear');

  function addLog(m){const ts=new Date().toLocaleTimeString();const line=`${ts}: ${m}`;console.log(line);debugLogs=[...debugLogs.slice(-9),line];renderLogs();}
  function statusColor(){if(isOnline)return{bg:'#dcfce7',color:'#16a34a',text:'🟢 ONLINE'};if(['connecting','joining','rejoining','connected'].includes(connectionStatus))return{bg:'#fef9c3',color:'#f59e0b',text:connectionStatus==='connecting'?'🟡 CONNECTING':connectionStatus==='joining'?'🟡 JOINING ROOM':connectionStatus==='rejoining'?'🟡 REJOINING':'🟡 CONNECTED (Not in room)'};return{bg:'#fee2e2',color:'#dc2626',text:'🔴 OFFLINE'};}
  function renderHeader(){elConnStatus.textContent=connectionStatus;elSockConnected.textContent=socket.connected?'Yes':'No';elIsOnline.textContent=isOnline?'Yes':'No';elSocketId.textContent=socket.id||'Not connected';const s=statusColor();elStatusPill.style.background=s.bg;elStatusPill.style.color=s.color;elStatusPill.textContent=s.text;}
  function renderCalls(){elCallsWrap.querySelectorAll('.call-card').forEach(n=>n.remove());elCallsCount.textContent=String(incomingCalls.length);elNoCalls.style.display=incomingCalls.length?'none':'block';elNoCallsSub.textContent=isOnline?"You'll be notified when patients request calls":"Connect to receive calls";for(const call of incomingCalls){const card=document.createElement('div');card.className='call-card bg-amber-100 border-2 border-amber-400 rounded-lg p-4';card.innerHTML=`<div class="font-semibold mb-1">📞 Incoming Call</div><div class="text-sm"><strong>Patient:</strong> ${call.patientId}</div><div class="text-sm"><strong>Channel:</strong> ${call.channel}</div><div class="text-[11px] text-gray-600 mt-1"><strong>Time:</strong> ${new Date(call.timestamp).toLocaleTimeString()}</div><div class="mt-3 flex gap-2"><button data-action="accept" data-id="${call.id}" class="px-3 py-2 rounded bg-green-600 text-white font-semibold">✅ Accept Call</button><button data-action="reject" data-id="${call.id}" class="px-3 py-2 rounded bg-red-600 text-white font-semibold">❌ Reject</button></div>`;elCallsWrap.appendChild(card);}}
  function renderLogs(){elLogs.innerHTML=debugLogs.map(l=>`<div>${l}</div>`).join('');elLogs.parentElement.scrollTop=elLogs.parentElement.scrollHeight;}
  function removeCallById(id){incomingCalls=incomingCalls.filter(c=>c.id!==id);renderCalls();}

  // CHANGED: unified init (websocket first, no credentials, exact path)
  const socket=io(SOCKET_URL,{transports:['websocket','polling'],withCredentials:false,path:'/socket.io'});

  function joinDoctorRoom(){if(isOnline){addLog('⚠️ Already online, skipping join-doctor');return;}addLog(`🏥 Emitting join-doctor for ID: ${DOCTOR_ID}`);connectionStatus='joining';renderHeader();socket.emit('join-doctor',DOCTOR_ID);setTimeout(()=>{if(!isOnline && socket.connected){addLog('⚠️ TIMEOUT: No doctor-online in 3s → retrying');socket.emit('join-doctor',DOCTOR_ID);}},3000);}

  socket.on('connect',()=>{addLog('✅ Socket connected');connectionStatus='connected';renderHeader();joinDoctorRoom();});
  socket.on('disconnect',(r)=>{addLog(`❌ Socket disconnected (${r})`);connectionStatus='disconnected';isOnline=false;renderHeader();});
  socket.on('connect_error',(e)=>{addLog(`❌ connect_error: ${e.message}`);connectionStatus='error';renderHeader();});

  socket.on('doctor-online',(d)=>{addLog(`👨‍⚕️ doctor-online: ${JSON.stringify(d)}`);if(Number(d.doctorId)===DOCTOR_ID){isOnline=true;connectionStatus='online';renderHeader();}});
  socket.on('doctor-offline',(d)=>{addLog(`👨‍⚕️ doctor-offline: ${JSON.stringify(d)}`);if(Number(d.doctorId)===DOCTOR_ID){isOnline=false;connectionStatus='offline';renderHeader();}});
  socket.on('call-requested',(callData)=>{addLog(`📞 call-requested: ${JSON.stringify(callData)}`);const id=callData.callId;if(incomingCalls.some(c=>c.id===id)){addLog(`⚠️ Duplicate call ignored: ${id}`);return;}incomingCalls.push({...callData,id});renderCalls();});
  socket.on('join-error',(err)=>{addLog(`❌ join-error: ${err?.message||'unknown'}`);connectionStatus='error';renderHeader();});
  socket.onAny((evt,...args)=>{if(!['ping','pong'].includes(evt))addLog(`📡 ${evt} ${JSON.stringify(args)}`)});
  socket.emit('get-server-status');
  socket.on('server-status',(s)=>addLog(`📊 server-status: ${JSON.stringify(s)}`));

  elCallsWrap.addEventListener('click', (e)=>{
    const btn=e.target.closest('button[data-action]'); if(!btn) return;
    const action=btn.dataset.action; const id=btn.dataset.id;
    const call=incomingCalls.find(c=>c.id===id); if(!call) return;

    if(action==='accept'){
      addLog(`✅ Accepting call: ${id}`); removeCallById(id);
      socket.emit('call-accepted',{callId:call.id,doctorId:call.doctorId,patientId:call.patientId,channel:call.channel});
      window.location.href=`/call-page/${encodeURIComponent(call.channel)}?uid=${encodeURIComponent(DOCTOR_ID)}&role=host`;
    }
    if(action==='reject'){
      addLog(`❌ Rejecting call: ${id}`); removeCallById(id);
      socket.emit('call-rejected',{callId:call.id,doctorId:call.doctorId,patientId:call.patientId});
    }
  });

  btnRejoin.addEventListener('click',()=>{addLog('🔄 Manual rejoin triggered');isOnline=false;connectionStatus='rejoining';renderHeader();socket.emit('join-doctor',DOCTOR_ID);});
  btnTest.addEventListener('click',()=>{addLog('🧪 Sending ping');socket.emit('ping',{doctorId:DOCTOR_ID,timestamp:Date.now()});socket.once('pong',d=>addLog(`🏓 pong: ${JSON.stringify(d)}`));});
  btnClear.addEventListener('click',()=>{debugLogs=[];renderLogs();});
  window.addEventListener('beforeunload',()=>{if(socket.connected){addLog(`🚪 leave-doctor ${DOCTOR_ID}`);socket.emit('leave-doctor',DOCTOR_ID);}});
  renderHeader(); renderCalls(); renderLogs();
</script>
</body>
</html>
