<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SnoutIQ Chat</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
  <style>
    .modal-mask{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:50}
    .modal-mask.show{display:flex}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
@php
  // unified default to 127.0.0.1:4000
  $socketUrl = $socketUrl ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000'));
  $patientId = $patientId ?? (auth()->id() ?? 101);

  if (!isset($nearbyDoctorsForJs)) {
    $docs = collect($nearbyDoctors ?? []);
    $nearbyDoctorsForJs = $docs->map(fn($d)=>['id'=>$d->id,'name'=>$d->name])->values();
  }
@endphp

<div class="max-w-4xl mx-auto p-4 sm:p-6">
  <h1 class="text-2xl font-bold text-gray-800 mb-2">SnoutIQ Chat</h1>
  <p class="text-blue-700 text-sm mb-4 font-medium">
    Schedule a convenient video consultation with a veterinary professional.
  </p>

  <div id="messages" class="space-y-4"></div>

  <div class="mt-4 space-y-3">
    <button id="start-call-btn"
      class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-semibold py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
      <span>📞 Start Video Consultation</span>
    </button>

    <button id="clinic-btn"
      class="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white text-sm font-semibold py-3 px-4 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      <span>In-Person Clinic Visit</span>
    </button>

    <div id="call-status" class="text-sm font-medium text-gray-700"></div>
  </div>

  <div class="mt-6 flex items-center gap-3">
    <input id="chat-input" type="text" placeholder="Type your message..."
           class="flex-1 px-4 py-3 border rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
    <button id="send-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl shadow-lg">Send</button>
  </div>
</div>

<div id="loading-modal" class="modal-mask">
  <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-sm">
    <p id="loading-text" class="text-gray-700 font-medium">📞 Requesting Call...</p>
  </div>
</div>

<script>
  // ===== server-config
  const SOCKET_URL     = @json($socketUrl);
  const PATIENT_ID     = @json($patientId);
  const NEARBY_DOCTORS = @json($nearbyDoctorsForJs);

  // ===== elements
  const $messages=document.getElementById('messages');
  const $input=document.getElementById('chat-input');
  const $sendBtn=document.getElementById('send-btn');
  const $startCallBtn=document.getElementById('start-call-btn');
  const $clinicBtn=document.getElementById('clinic-btn');
  const $status=document.getElementById('call-status');
  const $loading=document.getElementById('loading-modal');
  const $loadingText=document.getElementById('loading-text');

  // ===== state
  let loading=false;
  let callStatus=null;
  let activeDoctors=[];

  // Always cast IDs to Number
  let selectedDoctors=(NEARBY_DOCTORS||[]).map(d=>Number(d.id));

  // Per-doctor call data + aggregates
  let callDataMap={};            // doctorId -> { callId, channel, state: 'sent'|'failed'|'rejected' }
  let pendingDoctors=new Set();  // doctorIds whose response is still pending
  let accepted=false;            // any doctor accepted?

  // unified socket init
  const socket=io(SOCKET_URL,{transports:['websocket','polling'],withCredentials:false,path:'/socket.io'});

  // bootstrap
  socket.emit('get-active-doctors');
  socket.on('active-doctors',(ids)=>{
    // normalize to Number
    activeDoctors = (Array.isArray(ids)?ids:[]).map(n=>Number(n));
    hideLoading();
  });

  // chat demo
  $sendBtn.addEventListener('click',sendMessage);
  $input.addEventListener('keydown',e=>{if(e.key==='Enter') sendMessage();});
  function sendMessage(){
    const text=($input.value||'').trim();
    if(!text) return;
    addMessage({sender:'user',text});
    socket.emit('chat-message',{patientId:PATIENT_ID,text});
    $input.value='';
  }
  socket.on('chat-message',(msg)=>{
    addMessage({sender:'ai',text:msg?.text||'',emergency_status:(msg?.emergency_status||'').trim()});
  });

  // ===== Start Call (multi-doctor safe)
  $startCallBtn.addEventListener('click', startCall);

  function startCall(){
    if(loading) return;

    // Prefer only online doctors if we have the list
    let targets = selectedDoctors;
    if (activeDoctors.length) {
      targets = selectedDoctors.filter(id => activeDoctors.includes(id));
    }
    // If nothing online, still allow dialing all selected (optional). Here: show message.
    if (!targets.length) {
      updateStatus('❌ No selected doctors are currently online.');
      return;
    }

    // Reset aggregates
    accepted = false;
    callStatus = null;
    callDataMap = {};
    pendingDoctors = new Set(targets);

    loading = true;
    updateStatus();
    showLoading('📞 Requesting Call...');

    targets.forEach((doctorId)=>{
      const channel=`channel_${Date.now()}_${Math.random().toString(36).substring(2,8)}`;
      callDataMap[doctorId]={callId:null, channel, state:'sent'};
      // IMPORTANT: ensure doctorId is number
      socket.emit('call-requested', { doctorId:Number(doctorId), patientId:PATIENT_ID, channel });
    });
  }

  // ===== Server responses
  socket.on('call-sent',(data)=>{
    console.log('call-sent', data);
    callStatus={type:'sent',...data};
    const did = Number(data.doctorId);
    if (callDataMap[did]) {
      callDataMap[did].callId = data.callId;
      callDataMap[did].channel = data.channel;
    }
    // Keep waiting for others
    updateStatus('📤 Call request sent. Waiting for doctors...');
  });

  socket.on('call-failed',(data)=>{
    console.warn('call-failed', data);
    const did = Number(data.doctorId);
    if (pendingDoctors.has(did)) pendingDoctors.delete(did);
    if (callDataMap[did]) callDataMap[did].state='failed';
    // Only show final error if nobody left & not accepted
    maybeFinishIfAllResolved();
  });

  socket.on('call-rejected',(data)=>{
    console.warn('call-rejected', data);
    const did = Number(data.doctorId);
    if (pendingDoctors.has(did)) pendingDoctors.delete(did);
    if (callDataMap[did]) callDataMap[did].state='rejected';
    maybeFinishIfAllResolved();
  });

  socket.on('call-accepted',(data)=>{
    console.log('call-accepted', data);
    if (accepted) return; // already going somewhere
    accepted = true;
    hideLoading();
    callStatus={type:'accepted',...data};

    if (data.requiresPayment){
      updateStatus('✅ Doctor accepted! Redirecting to payment...');
      const url=`/payment/${encodeURIComponent(data.callId)}?doctorId=${encodeURIComponent(data.doctorId)}&channel=${encodeURIComponent(data.channel)}&patientId=${encodeURIComponent(PATIENT_ID)}`;
      window.location.href=url;
    } else {
      updateStatus('✅ Doctor accepted! Connecting...');
      setTimeout(()=>{
        window.location.href=`/call-page/${encodeURIComponent(data.channel)}?uid=${encodeURIComponent(PATIENT_ID)}&role=audience&callId=${encodeURIComponent(data.callId)}`;
      }, 300);
    }
  });

  function maybeFinishIfAllResolved(){
    if (accepted) return; // success path already taken
    if (pendingDoctors.size > 0) {
      // Some doctors still pending → keep spinner/status
      updateStatus('⏳ Waiting for other doctors to respond...');
      return;
    }
    // Reached here means: all failed/rejected and nobody accepted
    hideLoading();
    updateStatus('❌ Doctor not available. Please try another doctor.');
  }

  // ===== UI helpers
  function showLoading(txt){$loadingText.textContent=txt||'Loading…';$loading.classList.add('show');}
  function hideLoading(){$loading.classList.remove('show');loading=false;}
  function updateStatus(text){ if(text){$status.textContent=text;return;}
    if(!callStatus){$status.textContent='';return;}
    switch(callStatus.type){
      case 'sent': $status.textContent='📤 Call request sent. Waiting for doctors...'; break;
      case 'accepted': $status.textContent=callStatus.requiresPayment?'✅ Doctor accepted! Redirecting to payment...':'✅ Doctor accepted! Connecting...'; break;
      case 'rejected': $status.textContent='❌ Doctor is currently unavailable. Please try again later.'; break;
      default: $status.textContent=''; }
  }
  $clinicBtn.addEventListener('click',()=>{window.location.href='/book-clinic-visit';});

  function addMessage({sender,text,isError=false}){
    const wrap=document.createElement('div'); wrap.className=`flex ${sender==='user'?'justify-end':'justify-start'} mb-6`;
    const bubbleClass = sender==='user'?'bg-gradient-to-r from-blue-600 to-indigo-600 text-white ml-auto'
      : (isError?'bg-gradient-to-r from-red-50 to-pink-50 border-2 border-red-200 text-red-800':'bg-white/90 backdrop-blur-sm border border-gray-200 text-gray-800');
    const avatarAI= sender==='ai'?`<div class="flex-shrink-0 mr-3"><div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-lg"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div></div>`:'';
    const avatarUser= sender==='user'?`<div class="flex-shrink-0 ml-3"><div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-full flex items-center justify-center shadow-lg"><svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div></div>`:'';
    const safeText=(text||'').replace(/</g,'&lt;');
    wrap.innerHTML=`<div class="flex max-w-[85%] lg:max-w-[75%]">${avatarAI}
      <div class="rounded-2xl px-4 py-3 shadow-lg relative ${bubbleClass}">
        <div class="whitespace-pre-line leading-relaxed text-sm lg:text-base break-words"><div class="prose prose-sm max-w-full">${safeText}</div></div>
        <div class="flex items-center justify-between mt-3 pt-2"><div class="text-xs ${sender==='user'?'text-blue-200':'text-gray-500'}">${new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</div></div>
      </div>${avatarUser}</div>`;
    $messages.appendChild(wrap); $messages.scrollTop=$messages.scrollHeight;
  }
</script>

</body>
</html>
