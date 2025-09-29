<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SnoutIQ • Clinic Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen bg-gray-50">

@php
  // If app is under /backend in prod, set APP_PATH_PREFIX=/backend
  $pathPrefix = rtrim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/');

  // doctor id (pass via query or compute from auth in your app)
  $doctorId  = request('doctorId', 501);

  // links
  $clinicUrl = $pathPrefix . '/clinic-dashboard';
  $consoleUrl = $pathPrefix . '/doctor?doctorId=' . urlencode($doctorId);

  // active state helpers
  $clinicPath  = ltrim(($pathPrefix ? $pathPrefix.'/' : '').'clinic-dashboard', '/');
  $aiActive    = request()->is($clinicPath);
  $vcActive    = request()->fullUrlIs($consoleUrl . '*'); // highlight if already on /doctor
@endphp

<div class="flex h-full">
  {{-- Sidebar (same look as pet-dashboard) --}}
  <aside class="w-64 bg-gradient-to-b from-indigo-700 to-purple-700 text-white">
    <div class="h-16 flex items-center px-6 border-b border-white/10">
      <span class="text-xl font-bold tracking-wide">SnoutIQ</span>
    </div>

    <nav class="px-3 py-4 space-y-1">
      <div class="px-3 text-xs font-semibold tracking-wider text-white/70 uppercase mb-2">
        Menu
      </div>

      {{-- AI Chat -> this dashboard --}}
      <a href="{{ $clinicUrl }}"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg transition
                {{ $aiActive ? 'bg-white/15' : 'hover:bg-white/10' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v7a2 2 0 01-2 2h-4l-5 4v-4z"/>
        </svg>
        <span class="text-sm font-medium">AI Chat</span>
      </a>

      {{-- Video Consultation -> doctor console (receives calls) --}}
      <a href="{{ $consoleUrl }}"
         class="group flex items-center gap-3 px-3 py-2 rounded-lg transition
                {{ $vcActive ? 'bg-white/15' : 'hover:bg-white/10' }}">
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
      <h1 class="text-lg font-semibold text-gray-800">Clinic Dashboard</h1>

      <div class="flex items-center gap-3">
        <div class="text-right">
          <div class="text-sm font-medium text-gray-900">
            {{ auth()->user()->name ?? 'Doctor' }}
          </div>
          <div class="text-xs text-gray-500">
            {{ auth()->user()->role ?? 'doctor' }}
          </div>
        </div>
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold">
          {{ strtoupper(substr(auth()->user()->name ?? 'D',0,1)) }}
        </div>
      </div>
    </header>

    {{-- Content area (blank workspace) --}}
    <section class="flex-1 p-6">
      <div class="border-2 border-dashed border-gray-200 rounded-xl h-full bg-white flex items-center justify-center">
        <div class="text-center px-6 py-10">
          <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-3">
            <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
          </div>
          <h2 class="text-lg font-semibold text-gray-800 mb-1">Clinic Workspace</h2>
          <p class="text-sm text-gray-600 mb-4">
            Use the <span class="font-medium">Video Consultation</span> menu to open your call console.<br/>
            Keep that tab open to receive incoming patient calls.
          </p>
          <a href="{{ $consoleUrl }}"
             class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
            Open Call Console
          </a>
        </div>
      </div>
    </section>
  </main>
</div>

</body>
</html>
