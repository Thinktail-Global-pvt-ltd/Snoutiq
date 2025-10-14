{{-- Shared dashboard layout with gradient sidebar (matches /doctor look) --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>@yield('title','SnoutIQ')</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @yield('head')
</head>
<body class="h-screen bg-gray-50">
<div class="flex h-full">
  {{-- Sidebar --}}
  {{-- Sidebar --}}
  @include('layouts.partials.sidebar')

  {{-- Main content --}}
  <main class="flex-1 overflow-auto">
    <div class="h-16 bg-white border-b flex items-center justify-between px-6">
      <div class="text-base font-semibold">@yield('page_title','Dashboard') <span class="text-yellow-500">â€¢</span></div>
      <div class="flex items-center gap-2">
        @yield('header_actions')
        <a href="{{ route('logout') }}" class="px-3 py-1.5 text-sm rounded border border-gray-300 hover:bg-gray-50 text-gray-700">Logout</a>
      </div>
    </div>
    <div class="p-4">
      @yield('content')
    </div>
  </main>
</div>

@yield('scripts')
</body>
</html>
