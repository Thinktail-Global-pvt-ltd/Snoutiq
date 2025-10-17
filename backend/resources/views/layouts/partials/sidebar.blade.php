{{-- Global sidebar partial used across dashboards --}}
<aside class="w-64 bg-gradient-to-b from-indigo-700 to-purple-700 text-white flex flex-col">
  <div class="h-16 flex items-center px-6 border-b border-white/10">
    <span class="text-xl font-bold tracking-wide">SnoutIQ</span>
  </div>
  <nav class="px-3 py-4 space-y-1 text-sm grow">
    @php
      $role = session('role') ?? data_get(session('user'), 'role');
      $active = function($patterns){
        foreach ((array)$patterns as $p) {
          if (request()->routeIs($p)) return true;
        }
        return false;
      };
      $baseItem = 'group flex items-center gap-3 px-3 py-2 rounded-lg transition hover:bg-white/10';
    @endphp
    <div class="px-3 text-xs font-semibold tracking-wider text-white/70 uppercase mb-2">Menu</div>
    @if($role === 'pet' || $role === 'patient' || $role === 'user')
      <a href="{{ route('user.bookings') }}" class="{{ $baseItem }} {{ $active('user.bookings') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
        <span>My Orders</span>
      </a>
      <a href="{{ route('booking.clinics') }}" class="{{ $baseItem }} {{ $active('booking.clinics') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 11h18M5 19h14a2 2 0 002-2v-6H3v6a2 2 0 002 2z"/></svg>
        <span>Book Appointment</span>
      </a>
      <a href="{{ route('pet.video.schedule') }}" class="{{ $baseItem }} {{ $active('pet.video.schedule') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        <span>Video Calling Schedule by Doctor</span>
      </a>
    @else
      {{-- Vet/Doctor sidebar --}}
      <a href="{{ route('doctor.dashboard') }}" class="{{ $baseItem }} {{ $active('doctor.dashboard') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        <span>Video Consultation</span>
      </a>
      <a href="{{ route('groomer.services.index') }}" class="{{ $baseItem }} {{ $active('groomer.services.index') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6v6H4V6zm0 8h6v6H4v-6zm8-8h6v6h-6V6zm0 8h6v6H4v-6z"/></svg>
        <span>Services</span>
      </a>
      <a href="{{ route('doctor.bookings') }}" class="{{ $baseItem }} {{ $active('doctor.bookings') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h8l6 6v10a2 2 0 01-2 2z"/></svg>
        <span>My Bookings</span>
      </a>
      <a href="{{ route('doctor.video.schedule.manage') }}" class="{{ $baseItem }} {{ $active('doctor.video.schedule.manage') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Video Calling Schedule</span>
      </a>
      <a href="{{ route('clinic.orders') }}" class="{{ $baseItem }} {{ $active('clinic.orders') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
        <span>Order History</span>
      </a>
      <a href="{{ route('clinic.payments') }}" class="{{ $baseItem }} {{ $active('clinic.payments') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-4.418 0-8 1.79-8 4s3.582 4 8 4 8-1.79 8-4-3.582-4-8-4zm0-6v4m0 12v4"/></svg>
        <span>Payments</span>
      </a>
      <a href="{{ route('clinic.booking.payments') }}" class="{{ $baseItem }} {{ $active('clinic.booking.payments') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m-7 6h8a2 2 0 002-2V8a2 2 0 00-2-2H9l-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h2z"/></svg>
        <span>Booking Payments</span>
      </a>
      <a href="{{ route('clinic.doctors') }}" class="{{ $baseItem }} {{ $active('clinic.doctors') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-4a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span>Clinic Doctors</span>
      </a>
      <a href="{{ route('doctor.schedule') }}" class="{{ $baseItem }} {{ $active('doctor.schedule') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Clinic Schedule</span>
      </a>
    @endif
  </nav>
</aside>
