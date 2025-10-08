<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Clinic Business Hours</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen bg-gray-50">
<div class="flex h-full">
  <!-- Sidebar -->
  <aside class="w-64 bg-gradient-to-b from-indigo-700 to-purple-700 text-white">
    <div class="h-16 flex items-center px-6 border-b border-white/10">
      <span class="text-xl font-bold tracking-wide">SnoutIQ</span>
    </div>
    <nav class="px-3 py-4 space-y-1">
      <div class="px-3 text-xs font-semibold tracking-wider text-white/70 uppercase mb-2">Menu</div>
      <a href="{{ url('/doctor') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6v6H4V6zm0 8h6v6H4v-6zm8-8h6v6h-6V6zm0 8h6v6h-6v-6z"/>
        </svg>
        <span class="text-sm font-medium">Dashboard</span>
      </a>
      <a href="{{ route('clinic.hours') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"/>
        </svg>
        <span class="text-sm font-medium">Business Hours</span>
      </a>
      <a href="{{ route('appointments.book') }}" class="group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 11h18M5 19h14a2 2 0 002-2v-6H3v6a2 2 0 002 2z"/>
        </svg>
        <span class="text-sm font-medium">Appointments</span>
      </a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="flex-1 flex flex-col">
    <!-- Top Bar -->
    @php
      // Try to resolve clinic automatically from session/auth similar to services page
      $resolvedClinic = null;
      $sessionUserId = session('user_id');
      if ($sessionUserId) {
          $doc = \App\Models\Doctor::find($sessionUserId);
          if ($doc && $doc->vet_registeration_id) {
              $resolvedClinic = \App\Models\VetRegisterationTemp::find($doc->vet_registeration_id);
          }
      }
      if (!$resolvedClinic && auth()->check()) {
          $u = auth()->user();
          $doc = \App\Models\Doctor::where('doctor_email', $u->email)
                  ->orWhere('doctor_mobile', $u->phone ?? null)
                  ->first();
          if ($doc && $doc->vet_registeration_id) {
              $resolvedClinic = \App\Models\VetRegisterationTemp::find($doc->vet_registeration_id);
          }
          if (!$resolvedClinic) {
              $resolvedClinic = \App\Models\VetRegisterationTemp::where('email', $u->email)
                    ->orWhere('mobile', $u->phone ?? null)
                    ->orWhere('employee_id', (string)$u->id)
                    ->first();
          }
      }
    @endphp
    <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      <h1 class="text-lg font-semibold tracking-tight">Clinic Business Hours</h1>
      <div class="flex items-center gap-3">
        @if($resolvedClinic)
          <div class="text-sm text-gray-600">Clinic: <span class="font-semibold text-gray-900">{{ $resolvedClinic->name }}</span> <span class="text-gray-400">/</span> <code class="text-gray-700">{{ $resolvedClinic->slug }}</code></div>
          <input form="hoursForm" type="hidden" name="vet_id" value="{{ $resolvedClinic->id }}" />
        @else
          <input form="hoursForm" type="text" name="clinic_slug" value="{{ request('clinic_slug') }}" placeholder="clinic slug" class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-800 text-sm" />
        @endif
        <button type="submit" form="hoursForm" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700">Save Hours</button>
      </div>
    </header>

    <!-- Content -->
    <section class="p-6 overflow-y-auto">
      <div class="mx-auto max-w-4xl">
        @php
          $slug = request('clinic_slug');
          $clinicForPrefill = $resolvedClinic;
          if (!$clinicForPrefill && $slug) { $clinicForPrefill = \App\Models\VetRegisterationTemp::where('slug', $slug)->first(); }
          $existing = collect();
          if ($clinicForPrefill) {
            $existing = \App\Models\BusinessHour::where('vet_registeration_id', $clinicForPrefill->id)->get()->keyBy('day_of_week');
          }
          $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        @endphp

        <form id="hoursForm" action="{{ route('clinic.hours.save') }}" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          @csrf
          @if(session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
          @endif
          @if(session('error'))
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800">{{ session('error') }}</div>
          @endif
          @if($errors->any())
            <div class="mb-4 p-3 rounded bg-yellow-100 text-yellow-800">
              <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                  <li>{{ $e }}</li>
                @endforeach
              </ul>
            </div>
          @endif
          <div class="space-y-4">
            @for($i=1; $i<=7; $i++)
              @php $row = $existing->get($i); @endphp
              <div class="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0">
                <span class="w-28 text-sm font-medium text-gray-700">{{ $days[$i-1] }}</span>
                <div class="flex items-center gap-2">
                  <input type="time" name="open_time[{{ $i }}]" value="{{ $row?->open_time }}" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
                  <span class="text-gray-500">to</span>
                  <input type="time" name="close_time[{{ $i }}]" value="{{ $row?->close_time }}" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
                  <label class="flex items-center ml-4">
                    <input type="hidden" name="closed[{{ $i }}]" value="0">
                    <input type="checkbox" name="closed[{{ $i }}]" value="1" {{ $row?->closed ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                    <span class="text-sm text-gray-700">Closed</span>
                  </label>
                </div>
              </div>
            @endfor
          </div>
        </form>
      </div>
    </section>
  </main>
</div>
</body>
</html>
