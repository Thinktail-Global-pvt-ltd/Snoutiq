{{-- resources/views/doctor-dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Doctor Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    @keyframes ring{0%{transform:rotate(0)}10%{transform:rotate(15deg)}20%{transform:rotate(-15deg)}30%{transform:rotate(10deg)}40%{transform:rotate(-10deg)}50%{transform:rotate(5deg)}60%{transform:rotate(-5deg)}100%{transform:rotate(0)}}
    .ringing{animation:ring 1s infinite}
    body.demo-overlay-open {
      overflow: hidden;
    }
    body.demo-overlay-open #incoming-modal:not(.hidden) {
      position: fixed;
      inset: auto 2rem 2rem auto;
      background: transparent;
      backdrop-filter: none;
      display: flex;
      align-items: flex-start;
      justify-content: flex-end;
      z-index: 90;
      padding: 0;
    }
    body.demo-overlay-open #incoming-modal .incoming-modal-card {
      width: 360px;
      max-width: calc(100vw - 4rem);
      box-shadow: 0 20px 45px rgba(15,23,42,0.35);
    }
  </style>
</head>
<body class="h-screen bg-gray-50">

@php
  // ===== Env / paths =====
  $pathPrefix = rtrim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/'); // e.g. "backend"
  $socketUrl  = $socketUrl ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000'));

  // ===== Doctor identity (server-side first) =====
  $serverCandidate = session('user_id')
        ?? data_get(session('user'), 'id')
        ?? optional(auth()->user())->id
        ?? request('doctorId');
  // Prefer explicit route-provided $doctorId when present
  $serverDoctorId = $serverCandidate ? (int)$serverCandidate : (isset($doctorId) ? (int)$doctorId : null);

  // For JS (pure session value; helpful but not required)
  $sessionUserId = session('user_id')
        ?? data_get(session('user'), 'id')
        ?? optional(auth()->user())->id
        ?? null;

  // ===== Sidebar links =====
  $aiChatUrl   = ($pathPrefix ? "/$pathPrefix" : '') . '/backend/pet-dashboard';
  $thisPageUrl = ($pathPrefix ? "/$pathPrefix" : '') . '/backend/doctor' . ($serverDoctorId ? ('?doctorId=' . urlencode($serverDoctorId)) : '');
@endphp

<script>
  // ========= runtime env from server =========
  window.DOCTOR_PAGE_HANDLE_CALLS = true;
  const PATH_PREFIX_SERVER = @json($pathPrefix ? "/$pathPrefix" : ""); // "" locally, "/backend" in prod
  const PATH_PREFIX_GUESS = (() => {
    try {
      const path = window.location?.pathname || '';
      const knownPrefixes = ['backend', 'petparent', 'admin'];
      const found = knownPrefixes.find(prefix => path === `/${prefix}` || path.startsWith(`/${prefix}/`));
      return found ? `/${found}` : '';
    } catch (_) {
      return '';
    }
  })();
  const PATH_PREFIX = PATH_PREFIX_SERVER || '';
  const API_PREFIX = PATH_PREFIX_SERVER || PATH_PREFIX_GUESS || '';
  const RAW_SOCKET_URL = @json($socketUrl);
  const API_BASE    = (API_PREFIX || '') + '/api';
  const DEFAULT_DOCTOR_ID = Number(@json($serverDoctorId ?? ($doctorId ?? null))) || null;
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const SOCKET_URL = (!IS_LOCAL && /localhost|127\.0\.0\.1/i.test(RAW_SOCKET_URL))
    ? window.location.origin
    : RAW_SOCKET_URL;

  // Sources
  const SESSION_USER_ID = Number(@json($sessionUserId ?? null)) || null; // server truth (optional)
  const fromServer      = Number(@json($serverDoctorId ?? null)) || null;
  const fromQuery       = (()=>{ const u=new URL(location.href); const v=u.searchParams.get('doctorId'); return v?Number(v):null; })();
  function readAuthFull(){ try{ const raw=sessionStorage.getItem('auth_full')||localStorage.getItem('auth_full'); return raw?JSON.parse(raw):null; }catch(_){ return null; } }
  const af              = readAuthFull();
  const fromStorage     = (()=>{ if(!af) return null; const id1=af?.user_id; const id2=af?.user?.id; return Number(id1||id2)||null; })();

  // â­ FINAL id the frontend will use everywhere:
  let CURRENT_USER_ID = SESSION_USER_ID || fromServer || fromQuery || fromStorage || DEFAULT_DOCTOR_ID || null;

  console.log('[doctor-dashboard] RESOLVED user_id â†’', {
    SESSION_USER_ID, fromServer, fromQuery, fromStorage, CURRENT_USER_ID, PATH_PREFIX, API_BASE
  });
</script>

<div class="flex h-full">
  {{-- Sidebar --}}
  {{-- Shared sidebar --}}
  @include('layouts.partials.sidebar')
  {{-- Main --}}
  <main class="flex-1 flex flex-col">
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <div class="flex items-center gap-3">
        <h1 class="text-lg font-semibold text-gray-800">Dashboard</h1>
        <span id="status-dot" class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400" title="Connectingâ€¦"></span>
        <span id="status-pill" class="hidden px-3 py-1 rounded-full text-xs font-bold">â€¦</span>
      </div>

      <div class="flex items-center gap-3">
        <button id="btn-add-service" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">+ Add Service</button>
        <div id="call-ringing-badge" class="hidden items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 shadow-sm cursor-pointer hover:bg-amber-100 transition">
          <span class="inline-block w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
          <span data-role="ring-text">Phone is ringing</span>
        </div>
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
      <div class="max-w-5xl mx-auto">
        <div class="grid gap-6 lg:grid-cols-3">
          <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
              <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold text-gray-800">Incoming Calls (<span id="calls-count">0</span>)</h2>
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

          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col">
            <div class="flex items-center justify-between mb-4">
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">Pet Owner Preview</p>
                <h3 class="text-base font-semibold text-gray-900 mt-1">Demo Call Sandbox</h3>
              </div>
              <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                Live
              </span>
            </div>
            <p class="text-sm text-gray-600">
              Launch the same React journey that pet parents see. We create a safe dummy call so this dashboard rings instantly while a phone-sized window shows the patient flow.
            </p>
            <ul class="mt-4 space-y-2 text-xs text-gray-600">
              <li class="flex gap-2">
                <span class="text-indigo-500 mt-0.5">•</span>
                <span>Generates a one-time call session mapped to your doctor ID.</span>
              </li>
              <li class="flex gap-2">
                <span class="text-indigo-500 mt-0.5">•</span>
                <span>Opens a mobile frame with the real pet-owner React experience.</span>
              </li>
              <li class="flex gap-2">
                <span class="text-indigo-500 mt-0.5">•</span>
                <span>Use it in demos so vets can hear/see the ring without extra devices.</span>
              </li>
            </ul>
            <div class="mt-5 text-xs text-gray-500">
              Target doctor ID
              <span data-role="demo-doctor-id" class="ml-1 font-semibold text-gray-800">—</span>
            </div>
            <div class="mt-4 flex flex-col gap-2">
              <button id="btn-demo-call" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600" type="button">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.121 2.121 0 0 1 3 3l-1.4 1.4a2 2 0 0 1-2.46.24c-.86-.54-1.86-.9-2.9-1.04a8.06 8.06 0 0 0-5.62 1.64l-.2.16a2 2 0 0 0-.22 3.06l1.23 1.23a2 2 0 0 1 .25 2.49l-1.42 2.37a2 2 0 0 1-2.41.84 12.04 12.04 0 0 1-4.11-2.7A12 12 0 0 1 3 7.5c.03-1.28.24-2.54.62-3.76a2 2 0 0 1 1.97-1.47h.41a2 2 0 0 1 1.78 1.07l.91 1.82" />
                </svg>
                Launch Demo Call
              </button>
              <div id="demo-call-card-status" class="text-xs text-gray-500">
                Ready for a test run.
              </div>
            </div>
            <div id="demo-call-last" class="mt-3 rounded-lg border border-dashed border-gray-200 p-3 text-xs text-gray-500">
              No demo calls triggered yet.
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</div>

{{-- Incoming Call Modal --}}
<div id="incoming-modal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center">
  <div class="incoming-modal-card bg-white rounded-2xl shadow-2xl w-[92%] max-w-md p-6">
    <div class="flex items-center gap-3 mb-4">
      <svg class="w-9 h-9 text-rose-600 ringing" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
      </svg>
      <h3 class="text-xl font-semibold text-gray-800">Incoming Call</h3>
    </div>
    <div class="space-y-2 text-gray-700">
      <div><span class="font-semibold">Patient ID:</span> <span id="m-patient"></span></div>
      <div><span class="font-semibold">Channel:</span> <span id="m-channel" class="break-all"></span></div>
      <div class="text-xs text-gray-500"><span class="font-semibold">Time:</span> <span id="m-time"></span></div>
    </div>
    <div class="mt-4 border-t border-gray-200 pt-4">
      <div class="flex items-center justify-between">
        <span class="text-sm font-semibold text-gray-800">AI Chat Summary</span>
        <span class="text-[11px] uppercase tracking-wide text-gray-400">Last 5 chats</span>
      </div>
      <div class="mt-2 rounded-xl bg-gray-50 border border-gray-200 p-3 max-h-48 overflow-y-auto">
        <p id="m-summary-status" class="text-xs text-gray-500">AI summary will appear here when available.</p>
        <div id="m-summary" class="mt-2 space-y-2 text-sm text-gray-700 leading-relaxed"></div>
      </div>
    </div>
    <div class="mt-6 grid grid-cols-2 gap-3">
      <button id="m-accept" class="py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold shadow"> Accept</button>
      <button id="m-reject" class="py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold shadow">Reject</button>
    </div>
  </div>
</div>

<!-- =============================
     Add Service Modal (hidden by default)
============================= -->
<div id="add-service-modal" class="hidden fixed inset-0 z-[70] bg-slate-900/70 backdrop-blur flex items-center justify-center px-4 py-6">
  <div class="relative w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
    <button type="button" id="svc-close" class="absolute top-4 right-4 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:scale-105 hover:text-slate-900">
      <span class="sr-only">Close</span>
      ✕
    </button>

    <div class="grid gap-0 md:grid-cols-[260px_1fr]">
      <aside class="hidden md:flex flex-col justify-between border-r border-slate-100 bg-gradient-to-br from-indigo-600 via-indigo-500 to-indigo-400 p-8 text-white">
        <div class="space-y-6">
          <div>
            <p class="text-xs uppercase tracking-[0.35em] text-indigo-100/80">SnoutIQ Clinic</p>
            <h3 class="mt-3 text-2xl font-semibold leading-tight">Launch a new in-clinic experience</h3>
          </div>
          <ul class="space-y-4 text-sm text-indigo-50/90">
            <li class="flex items-start gap-3">
              <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/15 text-sm font-semibold">1</span>
              Define a crisp name so pet parents instantly recognise the service.
            </li>
            <li class="flex items-start gap-3">
              <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/15 text-sm font-semibold">2</span>
              Share the duration and pricing to auto-calculate slot availability.
            </li>
            <li class="flex items-start gap-3">
              <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/15 text-sm font-semibold">3</span>
              Add optional notes to highlight inclusions or pre-visit requirements.
            </li>
          </ul>
        </div>
        <div class="rounded-2xl border border-white/20 bg-white/10 p-4 text-xs text-indigo-100/90">
          Pro-tip: keep the description action-oriented (e.g. “Full body grooming with nail trim”) so your reception team can pitch it in seconds.
        </div>
      </aside>

      <div class="p-6 md:p-10">
        <header class="space-y-3">
          <p class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600">In-clinic · Service catalogue</p>
          <div>
            <h3 class="text-2xl font-semibold text-slate-900">Add New Service</h3>
            <p class="text-sm text-slate-500">Craft a polished card that shows up instantly on your clinic listings.</p>
          </div>
        </header>

        <form id="svc-form" class="mt-8 space-y-8">
          <section class="space-y-6">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Core details</h4>
            <div class="grid gap-5 lg:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700" for="svc-name">Service Name</label>
                <input id="svc-name" type="text" placeholder="Eg. Premium Grooming Session" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                <p class="text-xs text-slate-400">Displayed on the service catalogue and booking flow.</p>
              </div>

              <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="space-y-2">
                  <label class="text-sm font-medium text-slate-700" for="svc-duration">Duration (mins)</label>
                  <input id="svc-duration" type="number" min="1" step="1" placeholder="45" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium text-slate-700" for="svc-price">Price (₹)</label>
                  <input id="svc-price" type="number" min="0" step="0.01" placeholder="1299" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                </div>
              </div>
            </div>
          </section>

          <section class="space-y-6">
            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Categorise the visit</h4>
            <div class="grid gap-5 md:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700" for="svc-pet-type">Pet Type</label>
                <select id="svc-pet-type" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                  <option value="" selected disabled>Select pet category</option>
                  <option value="dog">Dog</option>
                  <option value="cat">Cat</option>
                  <option value="bird">Bird</option>
                  <option value="rabbit">Rabbit</option>
                  <option value="hamster">Hamster</option>
                  <option value="all">All Pets</option>
                </select>
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700" for="svc-main">Service Category</label>
                <select id="svc-main" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200" required>
                  <option value="" selected disabled>Select category</option>
                  <option value="grooming">Grooming</option>
                  <option value="video_call">Video Call</option>
                  <option value="vet">Vet Service</option>
                  <option value="pet_walking">Pet Walking</option>
                  <option value="sitter">Sitter</option>
                </select>
              </div>
            </div>
          </section>

          <section class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Optional notes</h4>
              <span class="text-xs font-medium text-indigo-500">Use for add-ons or prep instructions</span>
            </div>
            <textarea id="svc-notes" rows="5" placeholder="Mention what's included, preparation guidelines or recovery notes." class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 shadow-sm transition focus:border-indigo-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200"></textarea>
          </section>

          <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-xs text-slate-500">
              New services go live instantly for your clinic once saved.
            </div>
            <div class="flex items-center gap-3">
              <button type="button" id="svc-cancel" class="inline-flex items-center justify-center rounded-full border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</button>
              <button type="submit" id="svc-submit" class="inline-flex items-center justify-center rounded-full bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-700">Save Service</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Demo Call Overlay -->
<div id="demo-call-overlay" class="hidden fixed inset-0 z-[80] overflow-y-auto bg-slate-900/70 px-4 py-8 backdrop-blur">
  <div class="relative mx-auto max-w-6xl lg:max-w-7xl overflow-hidden rounded-3xl bg-white shadow-2xl">
    <button type="button" data-demo-close class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:scale-105 hover:text-slate-900">
      <span class="sr-only">Close demo</span>
      ✕
    </button>
    <div class="grid gap-0 lg:grid-cols-[500px_1fr]">
              <div class="border-b border-slate-100 bg-gradient-to-b from-slate-900 via-slate-900/95 to-slate-950 p-6 text-white lg:border-b-0 lg:border-r">
                <div class="flex flex-col items-center">
                  <div class="relative w-full max-w-xl">
                    <div class="absolute left-1/2 top-4 z-10 h-4 w-24 -translate-x-1/2 rounded-b-2xl bg-black/60"></div>
                    <div class="rounded-[36px] border-[6px] border-black/80 bg-black/60 p-3 shadow-2xl">
                      <div class="relative aspect-[9/18] overflow-hidden rounded-[24px] bg-slate-950">
                        <div id="demo-call-placeholder" class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-5 text-center text-slate-200">
                          <div class="text-base font-semibold">Pet Owner Preview</div>
                          <p class="text-xs text-slate-300">We will stream the real React payment journey here once the dummy call link is ready.</p>
                          <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[11px] uppercase tracking-wide text-slate-100">
                            React Demo
                          </div>
                        </div>
                        <iframe id="demo-call-frame" title="Pet owner demo" loading="lazy" class="hidden h-full w-full bg-white" referrerpolicy="no-referrer"></iframe>
                        <div id="demo-call-phone-loader" class="pointer-events-none absolute inset-0 hidden items-center justify-center bg-slate-950/70">
                          <div class="flex flex-col items-center gap-2 text-slate-100">
                            <span class="h-10 w-10 animate-spin rounded-full border-2 border-white/20 border-t-white"></span>
                            <span class="text-[11px] font-semibold tracking-wide uppercase">Loading</span>
                          </div>
                        </div>
                        <button id="demo-call-phone-ring" type="button" class="absolute bottom-4 left-1/2 z-10 -translate-x-1/2 rounded-full bg-emerald-500 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-lg shadow-emerald-700/40 hover:bg-emerald-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500">
                          Tap to ring doctor
                        </button>
                      </div>
                    </div>
                  </div>
          <p class="mt-4 text-center text-xs text-slate-300">
            Scroll/tap inside the phone to feel the pet-side flow.
          </p>
        </div>
      </div>
      <div class="p-6 lg:p-10">
        <div class="space-y-5">
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-indigo-600">Sales demo</p>
            <h3 class="mt-1 text-2xl font-semibold text-slate-900">Mobile experience</h3>
            <p class="mt-2 text-sm text-slate-600">
              We spin up a dummy call targeted to
              <span data-role="demo-doctor-id" class="font-semibold text-slate-900">—</span>,
              so this page rings while the phone view loads the React payment flow.
            </p>
          </div>

          <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 text-xs text-slate-500">
            Keep this dashboard visible so you can watch the incoming call banner flip from “No calls” to ringing in realtime.
          </div>

          <div>
            <div class="text-sm font-semibold text-slate-900">Status</div>
            <p id="demo-call-status" class="mt-1 text-sm text-slate-700">Ready to run demo.</p>
            <p id="demo-call-detail" class="mt-1 text-xs text-slate-500">Click start and we will generate a fresh test call.</p>
          </div>

          <div class="flex flex-wrap gap-3">
            <button id="demo-call-trigger" type="button" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
              Start Demo Call
            </button>
            <button id="demo-call-replay" type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" disabled>
              Run Again
            </button>
            <button id="demo-call-open-link" type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" disabled>
              Open Pet View
            </button>
          </div>

          <div class="space-y-3">
            <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-3 transition" data-demo-step="request">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700" data-role="step-icon">1</div>
              <div>
                <div class="text-sm font-semibold text-slate-900">Create dummy call</div>
                <div class="text-xs text-slate-500" data-role="step-desc">Ready</div>
              </div>
            </div>
            <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-3 transition" data-demo-step="ring">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700" data-role="step-icon">2</div>
              <div>
                <div class="text-sm font-semibold text-slate-900">Doctor incoming banner</div>
                <div class="text-xs text-slate-500" data-role="step-desc">Waiting</div>
              </div>
            </div>
            <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-3 transition" data-demo-step="pet">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700" data-role="step-icon">3</div>
              <div>
                <div class="text-sm font-semibold text-slate-900">Pet owner React view</div>
                <div class="text-xs text-slate-500" data-role="step-desc">Loads after link</div>
              </div>
            </div>
          </div>

          <div id="demo-call-info" class="hidden rounded-2xl border border-slate-100 bg-white/70 p-4">
            <div class="text-sm font-semibold text-slate-900">Latest session</div>
            <dl class="mt-3 space-y-2 text-xs text-slate-600">
              <div class="flex items-center justify-between gap-4">
                <dt class="uppercase tracking-wide text-[10px] text-slate-500">Call ID</dt>
                <dd class="font-semibold text-slate-900" data-role="demo-call-id">—</dd>
              </div>
              <div class="flex items-center justify-between gap-4">
                <dt class="uppercase tracking-wide text-[10px] text-slate-500">Channel</dt>
                <dd class="font-mono text-[11px] text-slate-900" data-role="demo-channel">—</dd>
              </div>
              <div class="flex items-center justify-between gap-4">
                <dt class="uppercase tracking-wide text-[10px] text-slate-500">Patient</dt>
                <dd class="font-semibold text-slate-900" data-role="demo-patient">—</dd>
              </div>
              <div class="flex items-center justify-between gap-4">
                <dt class="uppercase tracking-wide text-[10px] text-slate-500">Payment link</dt>
                <dd class="text-right">
                  <a href="#" data-role="demo-payment-link" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-800 text-[11px] font-semibold">Open</a>
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
/* =========================
   Add Service â€” sends user_id = CURRENT_USER_ID
========================= */
(function(){
  const $ = s => document.querySelector(s);

  // Endpoint (prod)
  const API_POST_SVC = 'https://snoutiq.com/backend/api/groomer/service';

  const els = {
    openBtn: $('#btn-add-service'),
    modal:   document.getElementById('add-service-modal'),
    close:   document.getElementById('svc-close'),
    cancel:  document.getElementById('svc-cancel'),
    form:    document.getElementById('svc-form'),
    submit:  document.getElementById('svc-submit'),
    name:    document.getElementById('svc-name'),
    duration:document.getElementById('svc-duration'),
    price:   document.getElementById('svc-price'),
    petType: document.getElementById('svc-pet-type'),
    main:    document.getElementById('svc-main'),
    notes:   document.getElementById('svc-notes'),
  };

  function show(el){ el && el.classList.remove('hidden'); }
  function hide(el){ el && el.classList.add('hidden'); }
  function loading(btn,on){ if(!btn) return; btn.disabled=!!on; btn.classList.toggle('opacity-60',!!on); if(on){btn.dataset.oldText=btn.textContent; btn.textContent='Saving...';} else if(btn.dataset.oldText){ btn.textContent=btn.dataset.oldText; delete btn.dataset.oldText; } }

  // Sanctum helpers
  const CSRF_URL = (PATH_PREFIX || '') + '/sanctum/csrf-cookie';
  function getCookie(n){ return document.cookie.split('; ').find(r=>r.startsWith(n+'='))?.split('=')[1] || ''; }
  function xsrfHeader(){ const raw=getCookie('XSRF-TOKEN'); return raw ? decodeURIComponent(raw) : ''; }

  async function bootstrapAuth(){
    const token = localStorage.getItem('token') || sessionStorage.getItem('token');
    if (token) { window.__authMode='bearer'; return { mode:'bearer' }; }
    try{
      await fetch(CSRF_URL, { credentials:'include' });
      const xsrf = xsrfHeader();
      if (xsrf) { window.__authMode='cookie'; return { mode:'cookie', xsrf }; }
      return { mode:'none' };
    }catch{ return { mode:'none' }; }
  }

  function buildHeaders(auth){
    const h = { 'Accept':'application/json',
                'X-Acting-User':  String(CURRENT_USER_ID ?? ''),
                'X-Session-User': String(SESSION_USER_ID ?? '') };
    if (auth.mode === 'bearer') {
      const token = localStorage.getItem('token') || sessionStorage.getItem('token');
      h['Authorization'] = 'Bearer ' + token;
    } else if (auth.mode === 'cookie') {
      h['X-Requested-With'] = 'XMLHttpRequest';
      const xsrf = xsrfHeader();
      if (xsrf) h['X-XSRF-TOKEN'] = xsrf;
    }
    return h;
  }

  async function fetchJSON(url, opts={}){
    const res = await fetch(url, { credentials:'include', ...opts });
    const ct  = res.headers.get('content-type') || '';
    const isJSON = ct.includes('application/json');
    const body = isJSON ? await res.json() : await res.text();
    if (!isJSON) throw { status: res.status, body, hint: 'Non-JSON response' };
    if (!res.ok) throw { status: res.status, body };
    return body;
  }

  function resetForm(){ els.form?.reset(); if(els.petType) els.petType.value=''; if(els.main) els.main.value=''; }

  async function createService(e){
    e.preventDefault();

    // pull latest possible id from storage (optional)
    try{
      const raw=sessionStorage.getItem('auth_full')||localStorage.getItem('auth_full');
      if(raw){ const obj=JSON.parse(raw); const id = Number(obj?.user?.id ?? obj?.user_id ?? NaN); if(!Number.isNaN(id)&&id) CURRENT_USER_ID=id; }
    }catch{ /* ignore */ }

    const name     = (els.name?.value || '').trim();
    const duration = Number(els.duration?.value || 0);
    const price    = Number(els.price?.value || 0);
    const petType  = els.petType?.value || '';
    const main     = els.main?.value || '';
    const notes    = (els.notes?.value || '').trim();

    if(!name || !duration || !price || !petType || !main){
      Swal.fire({icon:'warning', title:'Missing fields', text:'Please fill all required fields.'});
      return;
    }

    loading(els.submit,true);
    try{
      const auth = await bootstrapAuth();
      if (auth.mode === 'none') {
        Swal.fire({icon:'warning', title:'Not authenticated', text:'Paste a Bearer token or enable Sanctum cookies.'});
        loading(els.submit,false);
        return;
      }

      // Build payload
      const fd = new FormData();
      fd.append('serviceName',  name);
      fd.append('description',  notes);
      fd.append('petType',      petType);
      fd.append('price',        price);
      fd.append('duration',     duration);
      fd.append('main_service', main);
      fd.append('status',       'Active');

      // â­ IMPORTANT: send CURRENT_USER_ID
      fd.append('user_id', String(CURRENT_USER_ID ?? ''));

      const headers = buildHeaders(auth);

      // Debug: see exactly what is going
      console.log('[createService] POST â†’', {
        endpoint: API_POST_SVC,
        CURRENT_USER_ID,
        SESSION_USER_ID,
        headers,
        body: Object.fromEntries([...fd.entries()])
      });

      const data = await fetchJSON(API_POST_SVC, { method:'POST', headers, body: fd });

      Swal.fire({icon:'success', title:'Service Created', text:'Your service has been created successfully.'});
      resetForm();
      console.log('[service.create] success', data);

    }catch(err){
      const msg = err?.body?.message
        || (typeof err?.body==='string' ? err.body : JSON.stringify(err?.body||err))
        || err?.hint
        || 'Error creating service';
      Swal.fire({icon:'error', title:'Create failed', text: msg});
      console.error('[service.create] failed', { err, CURRENT_USER_ID, SESSION_USER_ID });
    }finally{
      loading(els.submit,false);
    }
  }

  // init
  document.addEventListener('DOMContentLoaded', ()=>{
    // open modal via button
    document.getElementById('btn-add-service')?.addEventListener('click', ()=>show(els.modal));
  });

  els.form?.addEventListener('submit', createService);
  els.close?.addEventListener('click', ()=>hide(els.modal));
  els.cancel?.addEventListener('click', ()=>hide(els.modal));
})();
</script>

<script>
(function(){
  const openBtn = document.getElementById('btn-demo-call');
  const overlay = document.getElementById('demo-call-overlay');
  if (!openBtn || !overlay) return;

  const closeBtn = overlay.querySelector('[data-demo-close]');
  const triggerBtn = document.getElementById('demo-call-trigger');
  const replayBtn = document.getElementById('demo-call-replay');
  const openLinkBtn = document.getElementById('demo-call-open-link');
  const phoneRingBtn = document.getElementById('demo-call-phone-ring');
  const statusEl = document.getElementById('demo-call-status');
  const detailEl = document.getElementById('demo-call-detail');
  const frameEl = document.getElementById('demo-call-frame');
  const placeholderEl = document.getElementById('demo-call-placeholder');
  const loaderEl = document.getElementById('demo-call-phone-loader');
  const infoPanel = document.getElementById('demo-call-info');
  const infoCall = infoPanel?.querySelector('[data-role="demo-call-id"]');
  const infoChannel = infoPanel?.querySelector('[data-role="demo-channel"]');
  const infoPatient = infoPanel?.querySelector('[data-role="demo-patient"]');
  const infoPayment = infoPanel?.querySelector('[data-role="demo-payment-link"]');
  const cardStatus = document.getElementById('demo-call-card-status');
  const lastSummary = document.getElementById('demo-call-last');
  const doctorBadges = document.querySelectorAll('[data-role="demo-doctor-id"]');
  const steps = overlay.querySelectorAll('[data-demo-step]');
  const apiBase = (typeof API_BASE !== 'undefined' ? API_BASE : ((typeof PATH_PREFIX !== 'undefined' ? PATH_PREFIX : '') + '/api'));
  const DEMO_ENDPOINT = apiBase + '/call/test';
  const FRONTEND_BASE_FOR_DEMO = detectFrontendBase();
  const DEMO_PET_ENTRY = (() => {
    const base = (FRONTEND_BASE_FOR_DEMO || 'https://snoutiq.com').replace(/\/+$/, '');
    return base ? `${base}/dashboard` : 'https://snoutiq.com/dashboard';
  })();

  let currentSession = null;
  let requesting = false;

  function resolveDoctorId(){
    const socketDoctor = Number(window.snoutiqCall?.doctorId || 0);
    if (socketDoctor) return socketDoctor;
    const current = Number(window.CURRENT_USER_ID || 0);
    if (current) return current;
    const fallback = Number(typeof DEFAULT_DOCTOR_ID !== 'undefined' ? DEFAULT_DOCTOR_ID : 0);
    return fallback || null;
  }

  function updateDoctorBadges(){
    const id = resolveDoctorId();
    doctorBadges.forEach(el => {
      if (!el) return;
      el.textContent = id ? `#${id}` : '—';
    });
  }

  function setStatus(primary, secondary){
    if (statusEl) statusEl.textContent = primary || '';
    if (detailEl) detailEl.textContent = secondary || '';
    if (cardStatus) cardStatus.textContent = primary || '';
  }

  function setFrame(url){
    if (!frameEl || !placeholderEl) return;
    if (url) {
      placeholderEl.classList.add('hidden');
      frameEl.classList.remove('hidden');
      loaderEl?.classList.remove('hidden');
      frameEl.src = url;
    } else {
      placeholderEl.classList.remove('hidden');
      frameEl.classList.add('hidden');
      loaderEl?.classList.add('hidden');
      frameEl.removeAttribute('src');
    }
  }

  function setPhoneButtonState(state){
    if (!phoneRingBtn) return;
    phoneRingBtn.classList.remove('opacity-60','pointer-events-none','hidden');
    phoneRingBtn.disabled = false;
    if (state === 'hidden') {
      phoneRingBtn.classList.add('hidden');
      return;
    }
    if (state === 'busy') {
      phoneRingBtn.disabled = true;
      phoneRingBtn.classList.add('opacity-60','pointer-events-none');
      phoneRingBtn.textContent = 'Connecting…';
      return;
    }
    phoneRingBtn.textContent = 'Tap to ring doctor';
  }

  frameEl?.addEventListener('load', ()=>{
    if (!frameEl.src) return;
    loaderEl?.classList.add('hidden');
    setStepState('pet', 'done', 'Pet view loaded');
  });

  function setStepState(stepId, state, desc){
    const el = overlay.querySelector(`[data-demo-step=\"${stepId}\"]`);
    if (!el) return;
    const descEl = el.querySelector('[data-role="step-desc"]');
    const iconEl = el.querySelector('[data-role="step-icon"]');
    el.classList.remove('border-slate-200','border-indigo-200','border-emerald-200','border-rose-200','bg-white','bg-indigo-50','bg-emerald-50','bg-rose-50');
    if (state === 'active') el.classList.add('border-indigo-200','bg-indigo-50');
    else if (state === 'done') el.classList.add('border-emerald-200','bg-emerald-50');
    else if (state === 'error') el.classList.add('border-rose-200','bg-rose-50');
    else el.classList.add('border-slate-200','bg-white');
    if (descEl) {
      const map = {
        pending: 'Ready',
        active: 'Working…',
        done: 'Complete',
        error: 'Needs attention'
      };
      descEl.textContent = desc || map[state] || map.pending;
      descEl.className = 'text-xs ' + (state === 'active'
        ? 'text-indigo-700'
        : state === 'done'
          ? 'text-emerald-700'
          : state === 'error'
            ? 'text-rose-700'
            : 'text-slate-500');
    }
    if (iconEl) {
      iconEl.classList.remove('bg-slate-100','bg-indigo-600','bg-emerald-600','bg-rose-600','text-slate-700','text-white');
      if (state === 'active') iconEl.classList.add('bg-indigo-600','text-white');
      else if (state === 'done') iconEl.classList.add('bg-emerald-600','text-white');
      else if (state === 'error') iconEl.classList.add('bg-rose-600','text-white');
      else iconEl.classList.add('bg-slate-100','text-slate-700');
    }
  }

  function resetSteps(){
    setStepState('request','pending','Ready');
    setStepState('ring','pending','Waiting');
    setStepState('pet','pending','Loads after link');
  }

  function resetPanel(){
    currentSession = null;
    requesting = false;
    setStatus('Ready to run demo.', 'Click start to trigger the experience.');
    setFrame(null);
    openLinkBtn.disabled = true;
    replayBtn.disabled = true;
    if (infoPanel) infoPanel.classList.add('hidden');
    resetSteps();
    setPhoneButtonState('ready');
  }

  function openOverlay(autoStart){
    overlay.classList.remove('hidden');
    document.body.classList.add('overflow-hidden','demo-overlay-open');
    updateDoctorBadges();
    resetPanel();
    if (autoStart) requestDemoCall();
  }

  function closeOverlay(){
    overlay.classList.add('hidden');
    document.body.classList.remove('overflow-hidden','demo-overlay-open');
  }

  openBtn.addEventListener('click', ()=> openOverlay(false));
  overlay.addEventListener('click', (evt)=> {
    if (evt.target === overlay) closeOverlay();
  });
  document.addEventListener('keydown', (evt)=> {
    if (evt.key === 'Escape' && !overlay.classList.contains('hidden')) closeOverlay();
  });
  closeBtn?.addEventListener('click', closeOverlay);

  phoneRingBtn?.addEventListener('click', ()=> requestDemoCall());
  replayBtn?.addEventListener('click', ()=> requestDemoCall());
  triggerBtn?.addEventListener('click', ()=> requestDemoCall());
  openLinkBtn?.addEventListener('click', ()=>{
    const target = currentSession?.demo_entry_url || currentSession?.patient_payment_url;
    if (target) {
      window.open(target, '_blank', 'noopener');
    }
  });

  function readCookie(name){
    return document.cookie.split('; ').find(r => r.startsWith(name + '='))?.split('=')[1] || '';
  }

  async function requestDemoCall(){
    if (requesting) return;
    const doctorId = resolveDoctorId();
    if (!doctorId) {
      setStatus('Doctor ID missing', 'We could not resolve doctor_id from this session.');
      return;
    }
    currentSession = null;
    if (infoPanel) infoPanel.classList.add('hidden');
    setFrame(null);
    requesting = true;
    setPhoneButtonState('busy');
    const patientId = Math.floor(90000 + Math.random() * 90000);
    const headers = {
      'Content-Type':'application/json',
      'Accept':'application/json'
    };
    const xsrf = readCookie('XSRF-TOKEN');
    if (xsrf) {
      headers['X-Requested-With'] = 'XMLHttpRequest';
      headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrf);
    }
    setStatus('Requesting demo call…', `Sending dummy request with patient #${patientId}`);
    setStepState('request','active','Contacting server…');
    setStepState('ring','pending','Waiting for socket');
    setStepState('pet','pending','Loads after link');

    try{
      const res = await fetch(DEMO_ENDPOINT, {
        method: 'POST',
        credentials: 'include',
        headers,
        body: JSON.stringify({ doctor_id: doctorId, patient_id: patientId })
      });
      const raw = await res.text();
      let payload = {};
      try { payload = raw ? JSON.parse(raw) : {}; } catch (_) { payload = { raw }; }
      if (!res.ok || payload.success === false) {
        const msg = payload?.message || payload?.error || 'Unable to create test session.';
        throw new Error(msg);
      }
      const data = payload.data || payload.session || payload;
      currentSession = {
        call_id: data.call_id || data.callId || data.id || null,
        channel: data.channel || data.channel_name || null,
        doctor_id: data.doctor_id || doctorId,
        patient_id: data.patient_id || patientId,
        session_id: data.session_id || data.id || null,
        patient_payment_url: data.patient_payment_url || '',
        created_at: Date.now()
      };
      currentSession.demo_entry_url = buildDemoEntryUrl(currentSession, doctorId);
      setPhoneButtonState('hidden');
      setStatus('Demo call created', 'Doctor banner should ring now.');
      setStepState('request','done', currentSession.call_id ? `Call ${currentSession.call_id} created` : 'Call created');
      setStepState('ring','active','Listening for incoming event…');
      replayBtn?.removeAttribute('disabled');
      openLinkBtn.disabled = !(currentSession.demo_entry_url || currentSession.patient_payment_url);

      const petTarget = currentSession.demo_entry_url || currentSession.patient_payment_url;
      if (petTarget) {
        setStepState('pet','active','Loading pet dashboard…');
        setFrame(petTarget);
        if (infoPayment) infoPayment.href = currentSession.patient_payment_url;
      } else {
        setFrame(null);
      }

      if (infoPanel) infoPanel.classList.remove('hidden');
      if (infoCall) infoCall.textContent = currentSession.call_id || '—';
      if (infoChannel) infoChannel.textContent = currentSession.channel || '—';
      if (infoPatient) infoPatient.textContent = '#' + (currentSession.patient_id || patientId);
      if (lastSummary) {
        const ts = new Date().toLocaleTimeString();
        lastSummary.textContent = `Call ${currentSession.call_id || '—'} • Channel ${currentSession.channel || '—'} • ${ts}`;
        lastSummary.classList.remove('text-gray-500');
      }
    } catch (err) {
      setStatus('Demo call failed', err?.message || 'Unexpected error while creating demo call.');
      setStepState('request','error', err?.message || 'Server error');
      replayBtn?.removeAttribute('disabled');
      console.warn('[demo-call] request failed', err);
      setPhoneButtonState('ready');
    } finally {
      requesting = false;
    }
  }

  window.addEventListener('snoutiq:call-requested', (evt)=>{
    const payload = evt?.detail || {};
    const callId = payload.callId || payload.call_id || payload.id;
    if (!currentSession || !callId) return;
    if (currentSession.call_id && currentSession.call_id !== callId) return;
    setStepState('ring','done','Doctor alert received');
    setStatus('Incoming call ringing', 'Accept/reject from the banner above when ready.');
  });

  updateDoctorBadges();
  if (!document.body.classList.contains('overflow-hidden')) {
    setStatus('Ready for a test run.', 'Click the demo button to open the mobile preview.');
  }

  function detectFrontendBase(){
    const clean = (value) => value ? String(value).trim().replace(/\/+$/, '') : '';
    const candidates = [];
    const seen = new Set();
    const addCandidate = (value) => {
      const cleaned = clean(value);
      if (!cleaned || seen.has(cleaned)) return;
      candidates.push(cleaned);
      seen.add(cleaned);
    };

    try {
      if (window.__SNOUTIQ_FRONTEND_BASE) addCandidate(window.__SNOUTIQ_FRONTEND_BASE);
    } catch (_) {}
    try {
      const ls = localStorage.getItem('snoutiq_frontend_base');
      if (ls) addCandidate(ls);
    } catch (_) {}
    try {
      const ss = sessionStorage.getItem('snoutiq_frontend_base');
      if (ss) addCandidate(ss);
    } catch (_) {}

    addCandidate('https://snoutiq.com');

    if (typeof window !== 'undefined') {
      const host = (window.location.hostname || '').toLowerCase();
      if (/localhost|127\.0\.0\.1|0\.0\.0\.0/.test(host)) {
        addCandidate('http://localhost:5173');
      }

      const origin = window.location.origin || '';
      const pathName = window.location.pathname || '';
      const sanitizedOrigin = origin.replace(/\/backend$/, '');
      if (origin && !/^\/backend\b/.test(pathName)) {
        addCandidate(sanitizedOrigin);
      }
    }

    return candidates[0] || '';
  }

  function buildDemoEntryUrl(session, doctorId){
    if (!DEMO_PET_ENTRY) return null;
    try{
      const target = new URL(DEMO_PET_ENTRY, window.location.origin);
      target.searchParams.set('demo', '1');
      if (session?.call_id) target.searchParams.set('callId', session.call_id);
      if (session?.channel) target.searchParams.set('channel', session.channel);
      if (session?.session_id) target.searchParams.set('sessionId', session.session_id);
      if (doctorId) target.searchParams.set('doctorId', doctorId);
      if (session?.patient_id) target.searchParams.set('patientId', session.patient_id);
      return target.toString();
    }catch(err){
      console.warn('[demo-call] unable to build pet dashboard url', err);
      return DEMO_PET_ENTRY;
    }
  }
})();
</script>

<!-- Inject Logout link next to "+ Add Service" on header -->
<script>
  document.addEventListener('DOMContentLoaded', function(){
    try{
      var rightGroup = document.querySelector('header .flex.items-center.gap-3:last-child');
      if (rightGroup) {
        if (!rightGroup.querySelector('a[data-role="logout-link"]')) {
          var a = document.createElement('a');
          a.href = '{{ route('logout') }}';
          a.setAttribute('data-role','logout-link');
          a.className = 'px-3 py-1.5 text-sm rounded border border-gray-300 hover:bg-gray-50 text-gray-700';
          a.textContent = 'Logout';
          rightGroup.appendChild(a);
        }
      }
    }catch(_){ /* noop */ }
  });
</script>

<script>
// =========================
// Socket.IO: come online and receive calls
// =========================
(function(){
  if (typeof io === 'undefined') { console.warn('[doctor] Socket.IO not loaded'); return; }
  const $ = s => document.querySelector(s);
  const elSocketId = $('#socket-id');
  const elConn     = $('#conn-status');
  const elConnYes  = $('#socket-connected');
  const elIsOnline = $('#is-online');
  const elHeaderDot= document.getElementById('status-dot');
  const elNoCallsSub = document.getElementById('no-calls-sub');
  const modal      = document.getElementById('incoming-modal');
  const elMPatient = document.getElementById('m-patient');
  const elMChannel = document.getElementById('m-channel');
  const elMTime    = document.getElementById('m-time');
  const elMSummary = document.getElementById('m-summary');
  const elMSummaryStatus = document.getElementById('m-summary-status');

  let audioCtx = null;
  let ringtoneOsc = null;
  let ringtoneGain = null;

  function ensureAudioContext(){
    if (audioCtx) return audioCtx;
    const Ctor = window.AudioContext || window.webkitAudioContext;
    if (!Ctor) return null;
    audioCtx = new Ctor();
    return audioCtx;
  }

  function startIncomingTone(){
    const ctx = ensureAudioContext();
    if (!ctx) return;
    if (ctx.state === 'suspended') {
      ctx.resume().catch(()=>{});
    }
    stopIncomingTone();
    ringtoneOsc = ctx.createOscillator();
    ringtoneGain = ctx.createGain();
    ringtoneOsc.type = 'sine';
    ringtoneOsc.frequency.setValueAtTime(660, ctx.currentTime);
    ringtoneGain.gain.setValueAtTime(0, ctx.currentTime);
    ringtoneOsc.connect(ringtoneGain).connect(ctx.destination);
    ringtoneOsc.start();
    ringtoneGain.gain.linearRampToValueAtTime(0.18, ctx.currentTime + 0.05);
  }

  function stopIncomingTone(){
    try {
      if (ringtoneGain) {
        const ctx = ringtoneGain.context;
        const stopTime = ctx?.currentTime ?? 0;
        try {
          ringtoneGain.gain.cancelScheduledValues(0);
          ringtoneGain.gain.setValueAtTime(0, stopTime);
        } catch (_) {}
        ringtoneGain.disconnect();
      }
      if (ringtoneOsc) {
        ringtoneOsc.stop();
        ringtoneOsc.disconnect();
      }
    } catch (_) { /* ignore */ }
    ringtoneOsc = null;
    ringtoneGain = null;
  }

  let summaryFetchToken = 0;
  async function loadAiSummary(patientId){
    const hasSummaryTargets = !!(elMSummary || elMSummaryStatus);
    if (!hasSummaryTargets) return;

    const token = ++summaryFetchToken;

    if (elMSummary) {
      elMSummary.innerHTML = '';
    }
    if (elMSummaryStatus) {
      elMSummaryStatus.textContent = patientId ? 'Fetching AI summary…' : 'Patient ID missing.';
      elMSummaryStatus.classList.remove('hidden');
    }

    if (!patientId) {
      return;
    }

    try {
      const url = `${API_BASE}/ai/summary?user_id=${encodeURIComponent(patientId)}&limit=1&summarize=1`;
      const res = await fetch(url, { credentials: 'include' });
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }
      const data = await res.json();
      if (summaryFetchToken !== token) return; // stale
      const summaryRaw = [
        data?.summary,
        data?.data?.summary,
        data?.data?.data?.summary,
        data?.payload?.summary,
        data?.result?.summary
      ].find(value => typeof value === 'string' && value.trim());
      const summaryText = (summaryRaw || '').trim();

      if (summaryText) {
        if (elMSummaryStatus) {
          elMSummaryStatus.classList.add('hidden');
        }
        if (elMSummary) {
          elMSummary.innerHTML = '';
          const entries = [];
          let current = null;
          summaryText.split(/\n+/).forEach(line => {
            const trimmed = line.trim();
            if (!trimmed) return;
            if (/^Q:/i.test(trimmed)) {
              current = { question: trimmed.replace(/^Q:\s*/i, '').trim(), diagnosis: [], inDiagnosis: false };
              entries.push(current);
              return;
            }
            if (/^===\s*DIAGNOSIS\s*===/i.test(trimmed)) {
              if (!current) {
                current = { question: '', diagnosis: [], inDiagnosis: true };
                entries.push(current);
              } else {
                current.inDiagnosis = true;
              }
              return;
            }
            if (/^===\s*END\s*===/i.test(trimmed)) {
              if (current) current.inDiagnosis = false;
              return;
            }
            if (current?.inDiagnosis) {
              current.diagnosis.push(trimmed.replace(/^A:\s*/i, '').trim());
            }
          });
          const primaryEntry = entries.find(entry => entry.question || entry.diagnosis.length);
          if (primaryEntry) {
            if (primaryEntry.question) {
              const p = document.createElement('p');
              p.className = 'text-sm text-gray-700 leading-relaxed';
              const strong = document.createElement('span');
              strong.className = 'font-semibold text-gray-800 pr-1';
              strong.textContent = 'Q';
              p.appendChild(strong);
              p.appendChild(document.createTextNode(primaryEntry.question));
              elMSummary.appendChild(p);
            }
            if (primaryEntry.diagnosis.length) {
              const p = document.createElement('p');
              p.className = 'text-sm text-gray-700 leading-relaxed';
              const strong = document.createElement('span');
              strong.className = 'font-semibold text-gray-800 pr-1';
              strong.textContent = 'Diagnosis';
              p.appendChild(strong);
              p.appendChild(document.createTextNode(primaryEntry.diagnosis[0]));
              elMSummary.appendChild(p);
            }
          } else {
            summaryText.split(/\n+/).forEach(line => {
              const trimmed = line.trim();
              if (!trimmed) return;
              const p = document.createElement('p');
              p.className = 'text-sm text-gray-700 leading-relaxed';
              p.textContent = trimmed;
              elMSummary.appendChild(p);
            });
          }
        }
        if (elMSummary && elMSummary.childElementCount === 0 && elMSummaryStatus) {
          elMSummaryStatus.textContent = 'No recent AI chats found.';
          elMSummaryStatus.classList.remove('hidden');
        }
      } else if (elMSummaryStatus) {
        elMSummaryStatus.textContent = 'No recent AI chats found.';
        elMSummaryStatus.classList.remove('hidden');
      }
    } catch (err) {
      if (summaryFetchToken !== token) return; // stale
      console.warn('[doctor] failed to load AI summary', err);
      if (elMSummaryStatus) {
        elMSummaryStatus.textContent = 'Unable to load AI chat summary.';
        elMSummaryStatus.classList.remove('hidden');
      }
    }
  }

  let joined = false;
  let lastCall = null;
  const url = new URL(window.location.href);
  const autoLive = (url.searchParams.get('live') === '1');
  const doctorId = Number(url.searchParams.get('doctorId') || window.CURRENT_USER_ID || DEFAULT_DOCTOR_ID || 0) || null;

  if (window.snoutiqCall && typeof window.snoutiqCall.updateDoctorId === 'function') {
    window.snoutiqCall.updateDoctorId(doctorId || window.CURRENT_USER_ID || DEFAULT_DOCTOR_ID || null);
    if (typeof window.snoutiqCall.goOnline === 'function' && window.snoutiqCall.isVisible && window.snoutiqCall.isVisible()) {
      try { window.snoutiqCall.goOnline({ showAlert: false }); } catch(_){}
    }
  }

  window.addEventListener('snoutiq:call-api-ready', function(event){
    try{
      const api = event?.detail;
      if (api && typeof api.updateDoctorId === 'function') {
        api.updateDoctorId(doctorId || window.CURRENT_USER_ID || DEFAULT_DOCTOR_ID || null);
        if (typeof api.goOnline === 'function' && typeof api.isVisible === 'function' && api.isVisible()) {
          try { api.goOnline({ showAlert: false }); } catch(_){}
        }
      }
    }catch(_){ }
  }, { once: true });

  // ===== Visibility state (persisted) =====
  function isVisible(){ return (localStorage.getItem('clinic_visible') ?? 'on') !== 'off'; }
  function setVisible(v){ localStorage.setItem('clinic_visible', v ? 'on' : 'off'); }
  function updateNoCalls(on){ if (elNoCallsSub) elNoCallsSub.textContent = on ? 'Connect to receive calls' : 'You will not be receiving calls'; }
  function ensureToggle(){
    try{
      const groups = document.querySelectorAll('header .flex.items-center.gap-3');
      const left = groups && groups.length ? groups[0] : null;
      if (!left) return null;
      if (document.getElementById('visibility-toggle')) return document.getElementById('visibility-toggle');
      const label = document.createElement('label');
      label.className = 'inline-flex items-center gap-2 cursor-pointer select-none';
      label.title = 'Clinic Visibility';
      label.innerHTML = `
        <input id="visibility-toggle" type="checkbox" class="sr-only peer">
        <span class="relative w-10 h-5 bg-gray-300 rounded-full transition peer-checked:bg-green-500">
          <span class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform duration-200 peer-checked:translate-x-5"></span>
        </span>
        <span id="visibility-label" class="text-sm text-gray-700">Visible</span>
      `;
      left.appendChild(label);
      return label.querySelector('#visibility-toggle');
    }catch(_){ return null; }
  }
  const toggle = ensureToggle();
  const toggleLabel = document.getElementById('visibility-label');
  const initialVisible = isVisible();
  if (toggle) {
    toggle.checked = initialVisible;
    if (toggleLabel) toggleLabel.textContent = initialVisible ? 'Visible' : 'Hidden';
  }
  updateNoCalls(initialVisible);

  const socket = (window.snoutiqCall && typeof window.snoutiqCall.ensureSocket === 'function')
    ? window.snoutiqCall.ensureSocket()
    : io(SOCKET_URL, {
        transports: ['websocket','polling'],
        withCredentials: false,
        path: '/socket.io/',
        autoConnect: initialVisible,
        reconnection: true
      });

  if (typeof window !== 'undefined' && !window.__SNOUTIQ_SOCKET) {
    window.__SNOUTIQ_SOCKET = socket;
  }

  function addLog(msg){ console.log('[doctor][log]', msg); }

  function setConn(state){
    if (elConn) elConn.textContent = state ? 'connected' : 'disconnected';
    if (elConnYes) elConnYes.textContent = state ? 'Yes' : 'No';
  }

  function setHeaderStatus(status){
    if (window.snoutiqCall?.setStatus) {
      try { window.snoutiqCall.setStatus(status); } catch(_){}
    }
    if (!elHeaderDot) return;
    elHeaderDot.classList.remove('bg-yellow-400','bg-green-500','bg-red-500');
    if (status==='online') elHeaderDot.classList.add('bg-green-500');
    else if (status==='error' || status==='offline') elHeaderDot.classList.add('bg-red-500');
    else elHeaderDot.classList.add('bg-yellow-400');
  }

  // Initial alert on load based on visibility
  try{
    if (initialVisible) {
      setHeaderStatus('connecting');
      if (window.Swal) Swal.fire({icon:'success', title:'Online', text:'Your clinic is currently visible to patients within 10 km.'});
    } else {
      setHeaderStatus('offline');
      if (window.Swal) Swal.fire({icon:'info', title:'Offline', text:'You will not be receiving calls. Turn on this button to receive video consultation calls.'});
    }
  }catch(_){ }

  socket.on('connect', ()=>{
    if (elSocketId) elSocketId.textContent = socket.id;
    setConn(true);
    addLog('Socket connected: ' + socket.id);
    setHeaderStatus('connecting');
    console.log('[doctor] socket connected', { id: socket.id, url: SOCKET_URL });
    if (doctorId && !joined) {
      socket.emit('join-doctor', Number(doctorId));
      addLog('emit join-doctor ' + doctorId);
      console.log('[doctor] emit join-doctor', doctorId);
      joined = true;
      setHeaderStatus('online');
    }
  });

  socket.on('connect_error', (err)=>{
    setConn(false);
    addLog('connect_error: ' + (err && err.message));
    setHeaderStatus('error');
    console.warn('[doctor] socket connect_error:', err?.message||err);
  });

  function pageHidden(){
    try { return typeof document !== 'undefined' && document.hidden; }
    catch (_) { return false; }
  }

  function wantsOnline(){
    try {
      if (window.snoutiqCall?.isVisible) {
        return window.snoutiqCall.isVisible();
      }
    } catch (_) { /* ignore */ }
    return isVisible();
  }

  function triggerReconnect(){
    if (!wantsOnline()) return;
    if (window.snoutiqCall?.goOnline) {
      try { window.snoutiqCall.goOnline({ showAlert: false }); } catch (_) {}
      return;
    }
    if (!socket.connected) {
      try {
        socket.io.opts.reconnection = true;
        socket.connect();
      } catch (_) {}
    }
  }

  socket.on('disconnect', ()=>{
    setConn(false);
    joined = false;
    addLog('Socket disconnected');
    if (wantsOnline() && pageHidden()) {
      setHeaderStatus('connecting');
      triggerReconnect();
    } else {
      setHeaderStatus('offline');
    }
  });

  document.addEventListener('visibilitychange', ()=>{
    if (!pageHidden()) {
      triggerReconnect();
    }
  });

  window.addEventListener('focus', triggerReconnect);

  socket.on('doctor-online', (data)=>{
    try {
      if (Number(data?.doctorId) === Number(doctorId)) {
        joined = true;
        if (elIsOnline) elIsOnline.textContent = 'Yes';
        addLog('Doctor online ack for ' + doctorId);
        setHeaderStatus('online');
        console.log('[doctor] online ack for', doctorId);
      }
    } catch {}
  });

  socket.on('call-requested', (payload)=>{
    lastCall = payload || null;
    try{
      if (!modal) return;
      const patientId = payload?.patientId ?? '';
      if (elMPatient) {
        const numericId = Number(patientId);
        const displayId = Number.isFinite(numericId) && numericId > 0 ? `#${numericId}` : String(patientId || 'Unknown');
        elMPatient.textContent = displayId;
      }
      if (elMChannel) elMChannel.textContent = String(payload?.channel ?? '');
      if (elMTime)    elMTime.textContent    = new Date().toLocaleString();
      if (elMSummaryStatus) {
        elMSummaryStatus.textContent = 'Fetching AI summary…';
        elMSummaryStatus.classList.remove('hidden');
      }
      if (elMSummary) {
        elMSummary.innerHTML = '';
      }
      stopIncomingTone();
      startIncomingTone();
      loadAiSummary(patientId);
      modal.classList.remove('hidden');
      addLog('Incoming call for doctor ' + doctorId + ' ch=' + (payload?.channel||''));
    }catch(e){ console.warn('[doctor] call-requested render failed', e); }
  });

  document.getElementById('m-accept')?.addEventListener('click', ()=>{
    try{
      modal?.classList.add('hidden');
      stopIncomingTone();
      const ch = (lastCall?.channel || elMChannel?.textContent || '').trim();
      const callId = (lastCall?.callId || '').trim();
      const payload = {
        callId,
        channel: ch,
        doctorId: Number(doctorId || window.CURRENT_USER_ID || 0),
        patientId: Number(lastCall?.patientId || 0),
      };
      payload.uid = String(payload.doctorId || '');
      console.log('[doctor] accept redirect payload', payload);
      if (window.snoutiqCall?.accept) {
        window.snoutiqCall.accept(payload);
        return;
      }
      if (callId) {
        socket.emit('call-accepted', {
          callId,
          doctorId: Number(doctorId||0),
          patientId: Number(lastCall?.patientId||0),
          channel: ch,
        });
      }
      const patientParam = (lastCall?.patientId || lastCall?.patient_id || '').toString();
      const callUrl = '/call-page/' + encodeURIComponent(ch)
        + '?uid=' + encodeURIComponent(doctorId||'')
        + '&doctorId=' + encodeURIComponent(doctorId||'')
        + '&role=host'
        + (callId ? ('&callId=' + encodeURIComponent(callId)) : '')
        + '&pip=1'
        + (patientParam ? ('&patientId=' + encodeURIComponent(patientParam)) : '');
      console.log('[doctor] redirecting to call-page', callUrl);
      window.location.href = callUrl;
    }catch(e){ console.warn('[doctor] accept failed', e); }
  });

  document.getElementById('m-reject')?.addEventListener('click', ()=>{
    try{
      modal?.classList.add('hidden');
      stopIncomingTone();
      const callId = (lastCall?.callId || '').trim();
      if (window.snoutiqCall?.reject) {
        window.snoutiqCall.reject(lastCall || { callId }, 'rejected');
        return;
      }
      if (callId) socket.emit('call-rejected', { callId, reason: 'rejected' });
    }catch(e){ /* no-op */ }
  });


  // Auto-join like /doctor/live?doctorId=...&live=1
  if (autoLive && doctorId && isVisible()) {
    if (!socket.connected) try{ socket.connect(); }catch(_){ }
    // If already connected, emit immediately
    if (socket.connected) { socket.emit('join-doctor', Number(doctorId)); addLog('Auto join-doctor ' + doctorId); }
  }


  // Toggle behaviour: connect/disconnect and SweetAlerts
  if (toggle) {
    toggle.addEventListener('change', ()=>{
      const on = !!toggle.checked;
      setVisible(on);
      if (toggleLabel) toggleLabel.textContent = on ? 'Visible' : 'Hidden';
      updateNoCalls(on);
      if (on) {
        if (window.snoutiqCall?.goOnline) {
          window.snoutiqCall.goOnline({ showAlert: true });
        } else {
          setHeaderStatus('connecting');
          try{ socket.io.opts.reconnection = true; socket.connect(); }catch(_){ }
          if (window.Swal) Swal.fire({icon:'success', title:'Online', text:'Your clinic is currently visible to patients within 10 km.'});
        }
      } else {
        if (window.snoutiqCall?.goOffline) {
          window.snoutiqCall.goOffline({ showAlert: true });
        } else {
          try{ socket.io.opts.reconnection = false; socket.disconnect(); }catch(_){ }
          setHeaderStatus('offline');
          if (window.Swal) Swal.fire({icon:'info', title:'Offline', text:'You will not be receiving calls. Turn on this button to receive video consultation calls.'});
        }
      }
    });
  }
})();
</script>


@include('layouts.partials.call-core', ['socketUrl' => $socketUrl, 'pathPrefix' => $pathPrefix, 'sessionUser' => session('user'), 'sessionAuth' => session('auth_full'), 'sessionDoctor' => session('doctor')])
</body>
</html>
