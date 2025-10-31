{{-- resources/views/call-page.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>SnoutIQ – Video Call</title>

  {{-- UI --}}
  <script src="https://cdn.tailwindcss.com"></script>

  {{-- Axios --}}
  <script src="https://cdn.jsdelivr.net/npm/axios@1.7.4/dist/axios.min.js"></script>

  {{-- Razorpay --}}
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

  {{-- Agora NG (browser CDN) --}}
  <script src="https://cdn.jsdelivr.net/npm/agora-rtc-sdk-ng@4.20.2/AgoraRTC_N.min.js"></script>

  <style>
    .btn { @apply px-3 py-2 rounded-lg text-sm font-semibold transition; }
    .btn-primary { @apply bg-indigo-600 text-white hover:bg-indigo-700; }
    .btn-ghost { @apply bg-gray-100 text-gray-800 hover:bg-gray-200; }
    .btn-danger { @apply bg-red-600 text-white hover:bg-red-700; }
    .badge { @apply inline-flex items-center px-2 py-0.5 rounded text-xs font-medium; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">

@php
  // ==== Config ====
  $API_BASE         = config('app.call_api_base') ?? env('CALL_API_BASE', 'https://snoutiq.com/backend');
  $AGORA_APP_ID     = config('services.agora.app_id') ?? env('AGORA_APP_ID', '88a602d093ed47d6b77a29726aa6c35e');
  $RAZORPAY_KEY_ID  = config('services.razorpay.key') ?? env('RAZORPAY_KEY_ID', 'rzp_test_1nhE9190sR3rkP');

  // ==== Params from URL ====
  $channel  = request()->route('channel');            // /call-page/{channel}
  $uid      = request('uid');                         // ?uid=...
  $role     = request('role', 'audience');            // ?role=host|audience
  $callId   = request('callId');                      // ?callId=...
@endphp

<script>
  // ===== Constants (match your React demo) =====
  const API_BASE        = @json($API_BASE);
  const POLL_MS         = 2000;
  const AGORA_APP_ID    = @json($AGORA_APP_ID);
  const RAZORPAY_KEY_ID = @json($RAZORPAY_KEY_ID);

  // Args from URL (when coming from doctor-accept flow)
  const PRESET_CHANNEL  = @json($channel);
  const PRESET_UID      = @json($uid);     // doctor or patient id (display only)
  const PRESET_ROLE     = @json($role);    // 'host' or 'audience'
  const PRESET_CALL_ID  = @json($callId);
</script>

<div class="max-w-6xl mx-auto p-4">
  <header class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
      <h1 class="text-xl font-semibold text-gray-800">SnoutIQ Video Call</h1>
      <span id="status" class="badge bg-yellow-100 text-yellow-800">Idle</span>
    </div>
    <div class="text-right text-sm text-gray-600">
      <div><span class="font-semibold">Channel:</span> <span id="hdr-channel">—</span></div>
      <div><span class="font-semibold">Role:</span> <span id="hdr-role">—</span></div>
      <div><span class="font-semibold">UID:</span> <span id="hdr-uid">—</span></div>
    </div>
  </header>

  {{-- Control Row: test helpers (same as React demo) --}}
  <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4">
    <div class="flex flex-wrap items-center gap-2">
      <button id="btn-create" class="btn btn-ghost">1. Create Call (Patient)</button>
      <button id="btn-accept" class="btn btn-ghost">2. Accept Call (Doctor)</button>

      <button id="btn-start-poll" class="btn btn-ghost hidden">🔁 Start Polling</button>
      <button id="btn-stop-poll" class="btn btn-ghost hidden">⏹️ Stop Polling</button>

      <button id="btn-pay" class="btn btn-primary hidden">💳 Pay Now</button>

      <button id="btn-join" class="btn btn-primary">Join</button>
      <button id="btn-leave" class="btn btn-danger hidden">🚪 Leave</button>
      <button id="btn-pip" class="btn btn-ghost hidden" title="Picture in Picture">📺 PiP</button>

      <div class="ml-auto text-xs text-gray-500">
        <span class="mr-2">CallId: <span id="hdr-callid">—</span></span>
        <span class="mr-2">Preset: <span id="hdr-preset" class="badge bg-indigo-100 text-indigo-700">NO</span></span>
      </div>
    </div>
  </section>

  {{-- Video layout --}}
  <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
      <div class="mb-2 text-sm font-medium text-gray-700">Local</div>
      <div id="local-player" class="w-full aspect-video bg-black rounded-lg"></div>
      <div class="mt-2 flex items-center gap-2">
        <button id="btn-toggle-mic" class="btn btn-ghost">🎙️ Mic: On</button>
        <button id="btn-toggle-cam" class="btn btn-ghost">📷 Cam: On</button>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
      <div class="mb-2 text-sm font-medium text-gray-700">Remotes</div>
      <div id="remote-container" class="flex flex-wrap gap-2"></div>
    </div>
  </section>

  {{-- Logs --}}
  <section class="mt-4 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
    <div class="text-sm font-medium text-gray-700 mb-2">Logs</div>
    <pre id="log"
         class="bg-gray-50 rounded-lg p-3 text-xs max-h-72 overflow-auto whitespace-pre-wrap"></pre>
  </section>
</div>

<script>
  // ========= State =========
  let session     = null;       // { session_id, id, status, payment_status, channel_name, ... }
  let joined      = false;
  let pollingId   = null;
  let wantMic     = true;
  let wantCam     = true;
  let callStartedAt = null;

  // Agora
  const clientRef       = { current: null };
  const localTracksRef  = { current: { mic: null, cam: null } };

  // ========= DOM =========
  const elLog           = document.getElementById('log');
  const elStatus        = document.getElementById('status');
  const elHdrChannel    = document.getElementById('hdr-channel');
  const elHdrRole       = document.getElementById('hdr-role');
  const elHdrUid        = document.getElementById('hdr-uid');
  const elHdrCallId     = document.getElementById('hdr-callid');
  const elHdrPreset     = document.getElementById('hdr-preset');

  const btnCreate       = document.getElementById('btn-create');
  const btnAccept       = document.getElementById('btn-accept');
  const btnStartPoll    = document.getElementById('btn-start-poll');
  const btnStopPoll     = document.getElementById('btn-stop-poll');
  const btnPay          = document.getElementById('btn-pay');
  const btnJoin         = document.getElementById('btn-join');
  const btnLeave        = document.getElementById('btn-leave');
  const btnPiP          = document.getElementById('btn-pip');
  const btnToggleMic    = document.getElementById('btn-toggle-mic');
  const btnToggleCam    = document.getElementById('btn-toggle-cam');

  // ========= Helpers =========
  const log = (msg) => {
    const line = `[${new Date().toLocaleTimeString()}] ${msg}`;
    console.log(line);
    elLog.textContent += (elLog.textContent ? '\n' : '') + line;
    elLog.scrollTop = elLog.scrollHeight;
  };

  const setStatus = (label, tone = 'yellow') => {
    elStatus.className = 'badge';
    if (tone === 'green') elStatus.classList.add('bg-green-100', 'text-green-800');
    if (tone === 'red')   elStatus.classList.add('bg-red-100', 'text-red-800');
    if (tone === 'blue')  elStatus.classList.add('bg-blue-100', 'text-blue-800');
    if (tone === 'yellow')elStatus.classList.add('bg-yellow-100', 'text-yellow-800');
    elStatus.textContent = label;
  };

  function getRemoteDivId(uid){ return `remote-${uid}`; }
  function ensureRemoteVideoContainer(uid){
    if(document.getElementById(getRemoteDivId(uid))) return;
    const wrap = document.getElementById('remote-container');
    const el = document.createElement('div');
    el.id = getRemoteDivId(uid);
    el.style.width = '260px';
    el.style.height = '180px';
    el.style.background = '#000';
    el.style.borderRadius = '8px';
    el.className = 'overflow-hidden';
    wrap.appendChild(el);
  }
  // PiP helper: pick remote if present else local
  function findBestVideoEl(){
    const firstRemote = document.querySelector('#remote-container video');
    if (firstRemote) return firstRemote;
    const local = document.querySelector('#local-player video');
    return local || null;
  }
  function removeRemoteVideoContainer(uid){
    const el = document.getElementById(getRemoteDivId(uid));
    if (el && el.parentNode) el.parentNode.removeChild(el);
  }
  function clearRemoteContainer(){
    const wrap = document.getElementById('remote-container');
    if (wrap) wrap.innerHTML = '';
  }

  // ========= Polling =========
  function startPolling(sessionId){
    stopPolling();
    log(`🔁 Start polling session ${sessionId} every ${POLL_MS}ms`);
    btnStartPoll.classList.add('hidden');
    btnStopPoll.classList.remove('hidden');

    pollingId = setInterval(async ()=>{
      try{
        const res = await axios.get(`${API_BASE}/api/call/${sessionId}`);
        const s   = res.data?.session ?? res.data;
        log(`📥 Status: ${s.status}, Payment: ${s.payment_status}`);

        // expose Pay Now if accepted but unpaid
        if (!joined && s.status === 'accepted' && s.payment_status !== 'paid'){
          btnPay.classList.remove('hidden');
        }

        // auto-join if backend says paid
        if (s.payment_status === 'paid' && !joined){
          await joinAgora(sessionId, s.channel_name);
        }
      }catch(e){
        log('❌ Poll error: ' + e.message);
      }
    }, POLL_MS);
  }
  function stopPolling(){
    if (pollingId){
      clearInterval(pollingId);
      pollingId = null;
      log('⏹️ Polling stopped');
      btnStopPoll.classList.add('hidden');
      if (session) btnStartPoll.classList.remove('hidden');
    }
  }

  // ========= Backend Helpers (React parity) =========
  async function createCall(){
    try{
      const res = await axios.post(`${API_BASE}/api/call/create`, { patient_id: Number(PRESET_UID) || 101 });
      session = res.data;
      elHdrCallId.textContent = session?.session_id ?? '—';
      log('🆕 Patient created call: ' + JSON.stringify(res.data));
      btnStartPoll.classList.remove('hidden');
      startPolling(session.session_id);
    }catch(e){
      log('Create call error: ' + e.message);
    }
  }

  async function acceptCall(){
    if (!session) { alert('Create a call first!'); return; }
    try{
      const res = await axios.post(`${API_BASE}/api/call/${session.session_id}/accept`, { doctor_id:  Number(PRESET_UID) || 501 });
      log('👨‍⚕️ Doctor accepted: ' + JSON.stringify(res.data));
    }catch(e){
      log('Accept call error: ' + e.message);
    }
  }

  async function openRazorpayPayment(sessionId){
    try{
      const orderRes = await axios.post(`${API_BASE}/api/create-order`);
      if (!window.Razorpay){
        log('⚠️ Razorpay SDK missing'); return;
      }
      const options = {
        key: RAZORPAY_KEY_ID,
        amount: orderRes.data.amount,
        currency: orderRes.data.currency,
        order_id: orderRes.data.id,
        name: 'SnoutIQ Consultation',
        description: 'Doctor Video Consultation',
        handler: async (response) => {
          log('✅ Payment success: ' + JSON.stringify(response));
          await axios.post(`${API_BASE}/api/call/${sessionId}/payment-success`, {
            payment_id: response.razorpay_payment_id,
            order_id:    response.razorpay_order_id,
            signature:   response.razorpay_signature,
          });

          let channelName = session?.channel_name;
          if (!channelName){
            const sRes = await axios.get(`${API_BASE}/api/call/${sessionId}`);
            const s = sRes.data?.session ?? sRes.data;
            channelName = s.channel_name;
          }
          await joinAgora(sessionId, channelName);
        },
        prefill: { name: 'SnoutIQ User', email: 'user@example.com', contact: '9999999999' }
      };
      log('🪟 Opening Razorpay checkout…');
      new window.Razorpay(options).open();
    }catch(e){
      log('Payment error: ' + e.message);
    }
  }

  // ========= Agora =========
  async function joinAgora(sessionId, channelName){
    try{
      if (joined){ log('ℹ️ Already joined'); return; }
      if (!AGORA_APP_ID){ log('❌ Missing AGORA_APP_ID'); return; }

      setStatus('Joining…', 'blue');

      // random UID for Agora join (independent of your app uid)
      const rtcUid = Math.floor(Math.random() * 1_000_000);

      // get token
      const tokRes = await axios.post(`${API_BASE}/api/agora/token`, {
        channel: channelName,
        uid: rtcUid,
        role: 'publisher'
      });
      const token = tokRes.data?.token;
      if (!token){ log('❌ No Agora token'); setStatus('Token error', 'red'); return; }

      // client
      const client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
      clientRef.current = client;

      client.on('user-published', async (user, mediaType) => {
        await client.subscribe(user, mediaType);
        log(`📡 Remote published: uid=${user.uid}, media=${mediaType}`);
        if (mediaType === 'video'){
          ensureRemoteVideoContainer(user.uid);
          user.videoTrack?.play(getRemoteDivId(user.uid));
        }
        if (mediaType === 'audio'){
          user.audioTrack?.play();
        }
      });

      client.on('user-unpublished', (user, mediaType) => {
        log(`🧹 Remote unpublished: uid=${user.uid}, media=${mediaType}`);
        if (mediaType === 'video') removeRemoteVideoContainer(user.uid);
      });

      client.on('user-left', (user) => {
        log(`👋 Remote left: uid=${user.uid}`);
        removeRemoteVideoContainer(user.uid);
      });

      // join + local tracks
      await client.join(AGORA_APP_ID, channelName, token, rtcUid);

      const [mic, cam] = await AgoraRTC.createMicrophoneAndCameraTracks();
      localTracksRef.current = { mic, cam };

      cam.play('local-player');
      await client.publish([mic, cam]);

      // reflect mic/cam desired state (if user toggled before join)
      if (!wantMic) await mic.setEnabled(false);
      if (!wantCam) await cam.setEnabled(false);

      joined = true;
      setStatus('Live', 'green');
      elHdrChannel.textContent = channelName;
      log(`🎥 Joined Agora: ch=${channelName}, uid=${rtcUid}`);

      callStartedAt = Date.now();
      try {
        await axios.post(`${API_BASE}/api/call/${sessionId}/start`, {
          started_at: new Date(callStartedAt).toISOString(),
        });
        log('🕑 Marked call session as started');
      } catch (err) {
        log('⚠️ Failed to mark call start: ' + (err?.message || String(err)));
      }

      // UI
      btnJoin.classList.add('hidden');
      btnLeave.classList.remove('hidden');
      btnPiP?.classList.remove('hidden');
    }catch(e){
      log('Agora join error: ' + (e?.message || String(e)));
      setStatus('Join error', 'red');
    }
  }

  async function leaveAgora(){
    try{
      const client = clientRef.current;
      const { mic, cam } = localTracksRef.current;

      if (mic) { mic.stop(); mic.close(); }
      if (cam) { cam.stop(); cam.close(); }
      localTracksRef.current = { mic: null, cam: null };

      if (client){
        await client.unpublish();
        await client.leave();
        client.removeAllListeners();
        clientRef.current = null;
      }
      clearRemoteContainer();
      joined = false;
      setStatus('Left', 'yellow');
      log('🚪 Left Agora');

      const endedAt = Date.now();
      const durationSeconds = callStartedAt ? Math.max(0, Math.round((endedAt - callStartedAt) / 1000)) : null;
      const sessionId = session?.session_id ?? session?.id ?? session?.session?.id;
      let marked = false;
      try {
        const payload = {
          ended_at: new Date(endedAt).toISOString(),
        };
        if (callStartedAt) {
          payload.started_at = new Date(callStartedAt).toISOString();
        }
        if (durationSeconds !== null) {
          payload.duration_seconds = durationSeconds;
        }
        if (sessionId) {
          await axios.post(`${API_BASE}/api/call/${sessionId}/end`, payload);
          marked = true;
        }
      } catch (err) {
        log('⚠️ Failed to mark call end: ' + (err?.message || String(err)));
      } finally {
        callStartedAt = null;
      }

      if (marked) {
        log('🛑 Marked call session as ended');
      }

      btnLeave.classList.add('hidden');
      btnPiP?.classList.add('hidden');
      btnJoin.classList.remove('hidden');
    }catch(e){
      log('Leave error: ' + (e?.message || String(e)));
    }
  }

  // ===== PiP actions =====
  async function tryEnterPiP(){
    const v = findBestVideoEl();
    if (!v) { log('PiP: no video found'); return; }
    try {
      if (document.pictureInPictureElement) await document.exitPictureInPicture();
      await v.requestPictureInPicture();
      log('PiP: entered');
    } catch(e) { log('PiP error: ' + (e?.message || e)); }
  }
  btnPiP?.addEventListener('click', tryEnterPiP);

  // ========= UI Events =========
  btnCreate.addEventListener('click', createCall);
  btnAccept.addEventListener('click', acceptCall);

  btnStartPoll.addEventListener('click', ()=> session && startPolling(session.session_id));
  btnStopPoll .addEventListener('click', stopPolling);

  btnPay  .addEventListener('click', ()=> session && openRazorpayPayment(session.session_id));
  btnJoin .addEventListener('click', async ()=>{
    // If we have a session from test flow, use that channel
    let ch = session?.channel_name || PRESET_CHANNEL;
    if (!ch){
      alert('No channel available. Use Create/Accept flow or open with /call-page/{channel}');
      return;
    }
    await joinAgora(session?.session_id ?? PRESET_CALL_ID, ch);
  });
  btnLeave.addEventListener('click', leaveAgora);

  btnToggleMic.addEventListener('click', async ()=>{
    wantMic = !wantMic;
    btnToggleMic.textContent = wantMic ? '🎙️ Mic: On' : '🎙️ Mic: Off';
    const mic = localTracksRef.current.mic;
    if (mic) await mic.setEnabled(wantMic);
  });
  btnToggleCam.addEventListener('click', async ()=>{
    wantCam = !wantCam;
    btnToggleCam.textContent = wantCam ? '📷 Cam: On' : '📷 Cam: Off';
    const cam = localTracksRef.current.cam;
    if (cam) await cam.setEnabled(wantCam);
  });

  // ========= Boot: reflect preset, auto-fill header, optional auto-join =========
  (function init(){
    elHdrChannel.textContent = PRESET_CHANNEL || '—';
    elHdrRole.textContent    = PRESET_ROLE    || '—';
    elHdrUid.textContent     = PRESET_UID     || '—';
    elHdrCallId.textContent  = PRESET_CALL_ID || '—';
    elHdrPreset.textContent  = PRESET_CHANNEL ? 'YES' : 'NO';

    if (PRESET_CHANNEL){
      log(`Preset channel detected: ${PRESET_CHANNEL}`);
      // We don't auto-call join(). Doctor/patient clicks Join for a clean UX;
      // If you want auto-join, uncomment:
      // joinAgora(PRESET_CALL_ID, PRESET_CHANNEL);
    }
  })();
</script>
</body>
</html>
