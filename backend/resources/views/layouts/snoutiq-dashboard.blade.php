{{-- Shared dashboard layout with gradient sidebar (matches /doctor look) --}}
@php
  $pathPrefix = rtrim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/');
  $socketUrl  = config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000');
  $sessionUser = session('user');
  $sessionAuth = session('auth_full');
  $sessionDoctor = session('doctor');
  $sessionUserId = session('user_id')
    ?? data_get($sessionUser, 'id')
    ?? optional(auth()->user())->id;
  $serverCandidate = session('doctor_id')
    ?? data_get($sessionDoctor, 'id')
    ?? $sessionUserId
    ?? data_get($sessionUser, 'doctor_id')
    ?? data_get($sessionAuth, 'user.doctor_id')
    ?? optional(auth()->user())->doctor_id
    ?? request()->input('doctorId');
  $serverDoctorId = $serverCandidate ? (int) $serverCandidate : null;

  $sessionRole = session('role')
    ?? data_get($sessionAuth, 'role')
    ?? data_get($sessionUser, 'role')
    ?? optional(auth()->user())->role
    ?? 'clinic_admin';

  $sessionClinicId = session('clinic_id')
    ?? session('vet_registerations_temp_id')
    ?? session('vet_registeration_id')
    ?? session('vet_id')
    ?? data_get($sessionUser, 'clinic_id')
    ?? data_get($sessionAuth, 'clinic_id')
    ?? data_get($sessionAuth, 'user.clinic_id');

  $sessionDoctorId = session('doctor_id')
    ?? data_get($sessionDoctor, 'id')
    ?? data_get($sessionAuth, 'doctor_id')
    ?? data_get($sessionAuth, 'user.doctor_id')
    ?? ($sessionRole === 'doctor' ? $sessionUserId : null);

  $resolvedDoctor = null;
  if ($sessionDoctorId) {
    $resolvedDoctor = \App\Models\Doctor::query()
      ->select('id', 'doctor_name', 'vet_registeration_id')
      ->find($sessionDoctorId);
  }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>@yield('title','SnoutIQ')</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @yield('head')
</head>
<body class="bg-gray-50 min-h-screen">
<div class="flex min-h-screen">
  {{-- Sidebar --}}
  {{-- Sidebar --}}
  @include('layouts.partials.sidebar')

  {{-- Main content --}}
  <main class="flex-1">
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <div class="flex items-center gap-3">
        <h1 class="text-lg font-semibold text-gray-800">@yield('page_title','Dashboard')</h1>
        <span id="status-dot" class="inline-block w-2.5 h-2.5 rounded-full bg-yellow-400" title="Connecting..."></span>
        <span id="status-pill" class="hidden px-3 py-1 rounded-full text-xs font-bold">...</span>
        <label class="inline-flex items-center gap-2 cursor-pointer select-none" title="Clinic Visibility">
          <input id="visibility-toggle" type="checkbox" class="sr-only peer">
          <span class="relative w-10 h-5 bg-gray-300 rounded-full transition peer-checked:bg-green-500">
            <span class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform duration-200 peer-checked:translate-x-5"></span>
          </span>
          <span id="visibility-label" class="text-sm text-gray-700">Visible</span>
        </label>
      </div>

      <div class="flex items-center gap-3">
        @yield('header_actions')
        <div id="call-ringing-badge" class="hidden items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 shadow-sm cursor-pointer hover:bg-amber-100 transition">
          <span class="inline-block w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
          <span data-role="ring-text">Phone is ringing</span>
        </div>
        <span class="hidden md:inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold
              {{ $sessionRole === 'doctor' ? 'bg-indigo-100 text-indigo-700 border border-indigo-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
          <span class="uppercase tracking-wide text-[11px] {{ $sessionRole === 'doctor' ? 'text-indigo-500' : 'text-emerald-500' }}">Role</span>
          {{ ucfirst(str_replace('_',' ',$sessionRole)) }}
          @if($sessionRole === 'doctor')
            · #{{ $resolvedDoctor?->id ?? $sessionDoctorId ?? '—' }}
          @endif
        </span>
        <div class="text-right">
          <div class="text-sm font-medium text-gray-900">
            {{ auth()->user()->name ?? ($resolvedDoctor?->doctor_name ?? 'Doctor') }}
          </div>
          <div class="text-xs text-gray-500">
            {{ ucfirst(str_replace('_',' ',$sessionRole)) }}
          </div>
        </div>
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold">
          {{ strtoupper(substr(auth()->user()->name ?? $resolvedDoctor?->doctor_name ?? 'C',0,1)) }}
        </div>
        <a href="{{ route('logout') }}" class="px-3 py-1.5 text-sm rounded border border-gray-300 hover:bg-gray-50 text-gray-700">Logout</a>
      </div>
    </header>
    <div class="p-4">
      @yield('content')
    </div>
  </main>
</div>

@include('layouts.partials.call-core', ['socketUrl' => $socketUrl, 'pathPrefix' => $pathPrefix, 'sessionUser' => $sessionUser, 'sessionAuth' => $sessionAuth, 'sessionDoctor' => $sessionDoctor])

@yield('scripts')
</body>
</html>
