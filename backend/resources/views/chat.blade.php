{{-- resources/views/chat.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SnoutIQ • Video Consultation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
</head>
<body class="h-screen bg-gray-50">

@php
  /**
   * If your Laravel app runs under /backend in prod, set APP_PATH_PREFIX=/backend in .env
   * This code still auto-forces /backend on snoutiq.com if not set.
   */
  $pathPrefix = rtrim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/');

  // Socket server URL
  $socketUrl = $socketUrl ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000'));

  // ✅ Patient identity: use user_id from SESSION first, then auth()->id(), else 101
  $sessionUserId = session('user_id') ?? data_get(session('user'), 'id');  // supports both shapes
  $authUserId    = optional(auth()->user())->id;
  $patientId     = (int)($sessionUserId ?? $authUserId ?? 101);

  // Sidebar links
  $dashUrl = ($pathPrefix ? '/'.$pathPrefix : '') . '/pet-dashboard';
  $chatUrl = ($pathPrefix ? '/'.$pathPrefix : '') . '/chat';

  // Dump session (debug)
  $sessionDump = session()->all();
@endphp

<script>
  // ===== Prefix utils =====
  const RAW_PATH_PREFIX = @json($pathPrefix);            // may be ""
  const RAW_SOCKET_URL  = @json($socketUrl);

  const resolvePrefix = () => {
    const given = (RAW_PATH_PREFIX || "").trim();
    if (given) return given.startsWith('/') ? given : `/${given}`;
    const pathStartsWithBackend = window.location.pathname.startsWith('/backend');
    const isSnoutiq = location.hostname.endsWith('snoutiq.com');
    if (pathStartsWithBackend) return '/backend';
    if (isSnoutiq) return '/backend'; // force backend on live
    return '';
  };
  const PATH_PREFIX = resolvePrefix();
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const SOCKET_URL = (!IS_LOCAL && /localhost|127\.0\.0\.1/i.test(RAW_SOCKET_URL))
    ? window.location.origin
    : RAW_SOCKET_URL;

  // ===== user_id logging + resolution =====
  const PHP_SESSION_USER_ID = @json($sessionUserId);
  const PHP_AUTH_USER_ID    = @json($authUserId);
  let   PATIENT_ID          = Number(@json($patientId)); // initial from PHP

  console.log('[chat] PHP session user_id:', PHP_SESSION_USER_ID);
  console.log('[chat] PHP auth()->id():',    PHP_AUTH_USER_ID);

  // Try to override from frontend session/localStorage (what you saved on login)
  let STORAGE_USER_ID = null;
  try {
    const raw = sessionStorage.getItem('auth_full') || localStorage.getItem('auth_full');
    if (raw) {
      const obj = JSON.parse(raw);
      STORAGE_USER_ID = Number(obj?.user?.id ?? obj?.user_id ?? NaN);
      console.log('[chat] frontend storage auth_full object:', obj);
      console.log('[chat] frontend storage user_id:', STORAGE_USER_ID);
      if (!Number.isNaN(STORAGE_USER_ID) && STORAGE_USER_ID) {
        PATIENT_ID = STORAGE_USER_ID; // final override
      }
    } else {
      console.log('[chat] no auth_full found in web storage');
    }
  } catch (e) {
    console.log('[chat] storage parse error:', e);
  }

  console.log('[chat] PATIENT_ID (final user_id used):', PATIENT_ID);
</script>

<div class="flex h-full">
  {{-- Sidebar --}}
  <aside class="w-64 bg-gradient-to-b from-indigo-700 to-purple-700 text-white">
    <div class="h-16 flex items-center px-6 border-b border-white/10">
      <span class="text-xl font-bold tracking-wide">SnoutIQ</span>
    </div>

    <nav class="px-3 py-4 space-y-1">
      <div class="px-3 text-xs font-semibold tracking-wider text-white/70 uppercase mb-2">Menu</div>

      <a href="{{ $dashUrl }}"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 transition">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v7a2 2 0 01-2 2h-4l-5 4v-4z"/>
        </svg>
        <span class="text-sm font-medium">AI Chat</span>
      </a>

      <a href="{{ $chatUrl }}"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-white/10 transition">
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
    {{-- Topbar --}}
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <h1 class="text-lg font-semibold text-gray-800">Video Consultation</h1>
      <div class="flex items-center gap-3">
        <div class="text-right">
          <div class="text-sm font-medium text-gray-900">{{ auth()->user()->name ?? 'User' }}</div>
          <div class="text-xs text-gray-500">{{ auth()->user()->role ?? (session('role') ?? 'member') }}</div>
        </div>
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold">
          {{ strtoupper(substr(auth()->user()->name ?? 'U',0,1)) }}
        </div>
      </div>
    </header>

    {{-- Content --}}
    <section class="flex-1 p-6">
      {{-- Session dump (debug) --}}
      <details class="mb-4">
        <summary class="text-sm text-indigo-700 cursor-pointer">Show Session Dump</summary>
        <pre class="mt-2 p-3 bg-white border rounded-lg text-xs overflow-auto max-h-64">{{ json_encode($sessionDump, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
      </details>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <p class="text-sm text-gray-600 mb-4">
          Schedule a convenient video consultation with a veterinary professional.
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
          <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
              <h2 class="text-sm font-semibold text-gray-800">Online Doctors</h2>
              <button id="refresh-btn"
                      class="text-xs px-2.5 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700">
                Refresh
              </button>
            </div>
            <div id="doctors-list" class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
              {{-- populated by JS --}}
            </div>

            <div id="no-docs" class="text-sm text-gray-500 border rounded-lg p-4 hidden">
              No doctors are currently online. Please try again in a moment.
            </div>
          </div>

          <div class="lg:col-span-1">
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
              <div class="flex items-center mb-3">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center mr-2">
                  <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <div class="text-sm font-semibold text-indigo-800">Start Video Consultation</div>
                  <div id="selected-count" class="text-xs text-indigo-700">0 doctor selected</div>
                </div>
              </div>

              <button id="start-btn"
                      class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold py-3 px-4 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                📞 Start Video Consultation
              </button>

              <div id="status" class="mt-3 text-xs text-gray-600"></div>
            </div>
          </div>
        </div>
      </div>

      {{-- Requesting modal --}}
      <div id="modal"
           class="fixed inset-0 bg-black/30 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-5 shadow-xl w-full max-w-sm text-center">
          <div class="w-10 h-10 rounded-full border-4 border-indigo-500 border-t-transparent animate-spin mx-auto mb-3"></div>
          <div class="font-semibold text-gray-800 mb-1">Requesting Call…</div>
          <div class="text-xs text-gray-500">Waiting for doctor to respond</div>
        </div>
      </div>
    </section>
  </main>
</div>

<script>
  // ===== Socket client =====
  const socket = io(SOCKET_URL, {
    transports: ['websocket','polling'],
    withCredentials: false,
    path: '/socket.io/' // keep trailing slash to match server
  });

  // ===== DOM =====
  const elList     = document.getElementById('doctors-list');
  const elNoDocs   = document.getElementById('no-docs');
  const elStart    = document.getElementById('start-btn');
  const elRefresh  = document.getElementById('refresh-btn');
  const elStatus   = document.getElementById('status');
  const elModal    = document.getElementById('modal');
  const elSelCount = document.getElementById('selected-count');

  // ===== State =====
  let activeDoctors   = [];            // [501, 502, ...]
  let selectedDoctors = new Set();     // chosen doctorIds
  let callDataMap     = {};            // { doctorId: {callId, channel, ...} }

  // ===== Helpers =====
  const uid = () => Math.random().toString(36).slice(2,8);
  const mkCallId = () => `call_${Date.now()}_${uid()}`;
  const channelFrom = (callId) => `channel_${callId}`;
  const showModal = (flag) => elModal.classList.toggle('hidden', !flag);
  const setStatus = (html) => { elStatus.innerHTML = html || ''; };

  function renderDoctors() {
    elList.innerHTML = '';
    if (!activeDoctors.length) {
      elNoDocs.classList.remove('hidden');
      elStart.disabled = true;
      elSelCount.textContent = '0 doctor selected';
      return;
    }
    elNoDocs.classList.add('hidden');

    activeDoctors.forEach(id => {
      const checked = selectedDoctors.has(Number(id));
      const card = document.createElement('label');
      card.className =
        'cursor-pointer flex items-center gap-3 p-3 rounded-lg border ' +
        (checked ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-gray-300');
      card.innerHTML = `
        <input type="checkbox" class="peer sr-only" data-id="${id}" ${checked ? 'checked':''}/>
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 text-white flex items-center justify-center text-xs font-bold">D</div>
        <div class="flex-1">
          <div class="text-sm font-medium text-gray-800">Doctor ${id}</div>
          <div class="text-[11px] text-gray-500">Online</div>
        </div>
        <div class="text-indigo-600 text-xs font-semibold">${checked ? 'Selected' : 'Select'}</div>
      `;
      elList.appendChild(card);
    });

    elList.querySelectorAll('input[type=checkbox]').forEach(cb => {
      cb.addEventListener('change', (e) => {
        const id = Number(e.target.dataset.id);
        if (e.target.checked) selectedDoctors.add(id); else selectedDoctors.delete(id);
        elSelCount.textContent = `${selectedDoctors.size} doctor${selectedDoctors.size===1?'':'s'} selected`;
        elStart.disabled = selectedDoctors.size === 0;
        renderDoctors(); // update visual label
      });
    });

    elStart.disabled = selectedDoctors.size === 0;
    elSelCount.textContent = `${selectedDoctors.size} doctor${selectedDoctors.size===1?'':'s'} selected`;
  }

  // ===== Socket events =====
  socket.on('connect', () => {
    setStatus(`<span class="text-green-700">Connected.</span>`);
    socket.emit('get-active-doctors');
  });

  socket.on('disconnect', () => {
    setStatus(`<span class="text-red-600">Disconnected from server.</span>`);
  });

  socket.on('active-doctors', (doctors) => {
    activeDoctors = (doctors || []).map(d => Number(d));
    renderDoctors();
  });

  socket.on('call-sent', (data) => {
    setStatus(`📤 Call request sent to doctor <b>${data.doctorId}</b>.`);
  });

  socket.on('call-accepted', (data) => {
    setStatus(`✅ Doctor <b>${data.doctorId}</b> accepted.`);

    if (data.requiresPayment) {
      const payUrl =
        `${PATH_PREFIX}/payment/${encodeURIComponent(data.callId)}`
        + `?doctorId=${encodeURIComponent(data.doctorId)}`
        + `&channel=${encodeURIComponent(data.channel)}`
        + `&patientId=${encodeURIComponent(PATIENT_ID)}`
        + `&amount=${encodeURIComponent(Number(data.paymentAmount||499))}`;
      window.location.href = payUrl;
    } else {
      const callUrl =
        `${PATH_PREFIX}/call-page/${encodeURIComponent(data.channel)}`
        + `?uid=${encodeURIComponent(PATIENT_ID)}&role=audience`
        + `&callId=${encodeURIComponent(data.callId)}&pip=1`;
      window.location.href = callUrl;
    }
    showModal(false);
  });

  socket.on('call-rejected', (data) => {
    setStatus(`❌ Doctor <b>${data.doctorId}</b> is unavailable. Try another.`);
    showModal(false);
  });

  socket.on('payment-completed', (data) => {
    if (data?.patientId === PATIENT_ID) {
      setStatus('💳 Payment verified. Connecting…');
      const url =
        `${PATH_PREFIX}/call-page/${encodeURIComponent(data.channel)}`
        + `?uid=${encodeURIComponent(PATIENT_ID)}&role=audience`
        + `&callId=${encodeURIComponent(data.callId)}&pip=1`;
      window.location.href = url;
    }
  });

  // ===== Actions =====
  document.getElementById('refresh-btn').addEventListener('click', () => {
    socket.emit('get-active-doctors');
  });

  document.getElementById('start-btn').addEventListener('click', () => {
    if (!selectedDoctors.size) return;
    setStatus('Requesting call…');
    showModal(true);
    callDataMap = {};

    selectedDoctors.forEach((doctorIdNum) => {
      const doctorId = Number(doctorIdNum);
      const callId   = mkCallId();
      const channel  = channelFrom(callId);
      const callData = { doctorId, patientId: PATIENT_ID, channel, callId };
      callDataMap[doctorId] = callData;
      socket.emit('call-requested', callData);
    });
  });

  // initial render
  renderDoctors();
</script>

</body>
</html>
