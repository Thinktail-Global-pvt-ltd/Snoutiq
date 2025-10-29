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
        <div class="text-right">
          <div class="text-sm font-medium text-gray-900">{{ auth()->user()->name ?? 'Doctor' }}</div>
          <div class="text-xs text-gray-500">{{ auth()->user()->role ?? 'doctor' }}</div>
        </div>
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold">
          {{ strtoupper(substr(auth()->user()->name ?? 'D',0,1)) }}
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
