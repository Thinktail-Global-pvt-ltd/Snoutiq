{{-- Global sidebar partial used across dashboards --}}
@php
  $sessionAuth = session('auth_full');
  $sessionUser = session('user');
  $sessionRole = session('role') ?? data_get($sessionUser, 'role');
  $clinicName = data_get($sessionAuth, 'user.clinic_profile')
    ?? data_get($sessionAuth, 'user.clinic_name')
    ?? data_get($sessionAuth, 'user.name')
    ?? data_get($sessionUser, 'clinic_profile')
    ?? data_get($sessionUser, 'clinic_name')
    ?? data_get($sessionUser, 'name')
    ?? optional(auth()->user())->clinic_profile
    ?? optional(auth()->user())->clinic_name
    ?? optional(auth()->user())->name
    ?? null;
  $clinicEmail = data_get($sessionAuth, 'user.email')
    ?? data_get($sessionUser, 'email')
    ?? optional(auth()->user())->email
    ?? null;
  $clinicId = data_get($sessionAuth, 'user.id')
    ?? data_get($sessionUser, 'id')
    ?? optional(auth()->user())->id
    ?? session('user_id');

  // If nothing in session resolved, fall back to clinic record for vets/doctors
  if ((!$clinicName || !$clinicEmail) && ($sessionVetId = session('vet_id')
      ?? session('vet_registeration_id')
      ?? session('vet_registerations_temp_id')
      ?? session('user_id'))) {
    $clinic = \App\Models\VetRegisterationTemp::query()
      ->select('id', 'clinic_profile', 'name', 'email')
      ->find($sessionVetId);

    if ($clinic) {
      $clinicName = $clinicName ?: ($clinic->clinic_profile ?: $clinic->name);
      $clinicEmail = $clinicEmail ?: $clinic->email;
      $clinicId = $clinicId ?: $clinic->id;
    }
  }

  $clinicName = $clinicName ?: 'Clinic';
@endphp

<style>
  .sidebar-transition {
    transition: transform 0.3s ease-in-out;
  }
  
  .sidebar-overlay {
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
  }
  
  .nav-item {
    transition: all 0.2s ease;
  }
  
  .nav-item:hover {
    transform: translateX(4px);
  }
  
  @media (max-width: 1023px) {
    .sidebar-mobile {
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      z-index: 50;
      transform: translateX(-100%);
    
    }
    
    .sidebar-mobile.open {
      transform: translateX(0);
    }
  }
  
  /* Custom scrollbar for sidebar */
  .sidebar-scrollbar::-webkit-scrollbar {
    width: 4px;
  }
  
  .sidebar-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
  }
  
  .sidebar-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 10px;
  }
  
  .sidebar-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
  }
</style>

<!-- Mobile menu button (to be placed in header) -->
<div
  id="mobile-menu-button"
  class="fixed top-3 left-3 z-50
         p-3 sm:p-4 md:p-5
         rounded-xl
         text-gray-700
         bg-white/70 backdrop-blur-md
         shadow-md
         hover:bg-gray-100 active:scale-95
         focus:outline-none focus:ring-2 focus:ring-indigo-500
         lg:hidden transition-all duration-200"
>
  <svg
    class="w-6 h-6 sm:w-7 sm:h-7 md:w-8 md:h-8"
    fill="none"
    stroke="currentColor"
    viewBox="0 0 24 24"
  >
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M4 6h16M4 12h16M4 18h16"
    ></path>
  </svg>
</div>



<!-- Sidebar overlay for mobile -->
<div id="sidebar-overlay" class="fixed inset-0 z-40 sidebar-overlay lg:hidden hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar-mobile lg:relative w-64 bg-gradient-to-b from-indigo-700 to-purple-700 text-white flex flex-col sidebar-transition z-50 lg:z-auto">
  <!-- Close button for mobile -->
  <div class="flex items-center justify-between p-4 border-b border-white/10 lg:hidden">
    <span class="text-xl font-bold tracking-wide">SnoutIQ</span>
    <button id="sidebar-close" class="p-1 rounded-md text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>

  <!-- Clinic info section -->
  <div class="px-4 lg:px-6 py-4 border-b border-white/10">
    <span class="hidden lg:block text-xl font-bold tracking-wide">SnoutIQ</span>
    <div class="mt-3 space-y-1">
      <p class="text-sm font-semibold leading-tight truncate">{{ $clinicName }}</p>
      @if($clinicEmail)
        <p class="text-xs text-white/70 break-all truncate">{{ $clinicEmail }}</p>
      @endif
      @if($clinicId && !in_array($sessionRole, ['pet','patient','user'], true))
        <p class="text-[11px] text-white/50 truncate">ID: {{ $clinicId }}</p>
      @endif
    </div>
  </div>
  
  <!-- Navigation -->
  <nav class="px-2 lg:px-3 py-4 space-y-1 text-sm grow overflow-y-auto sidebar-scrollbar">
    @php
      $role = session('role') ?? data_get(session('user'), 'role');
      $active = function($patterns){
        foreach ((array)$patterns as $p) {
          if (request()->routeIs($p)) return true;
        }
        return false;
      };
      $baseItem = 'nav-item group flex items-center gap-3 px-3 py-2.5 rounded-lg transition hover:bg-white/10';
    @endphp
    
    <div class="px-3 text-xs font-semibold tracking-wider text-white/70 uppercase mb-2">Menu</div>
    
    @if($role === 'pet' || $role === 'patient' || $role === 'user')
      <!-- Pet/Patient User Menu -->
      <a href="{{ route('user.bookings') }}" class="{{ $baseItem }} {{ $active('user.bookings') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
        </svg>
        <span class="truncate">My Orders</span>
      </a>
      
      <a href="{{ route('booking.clinics') }}" class="{{ $baseItem }} {{ $active('booking.clinics') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M3 11h18M5 19h14a2 2 0 002-2v-6H3v6a2 2 0 002 2z"/>
        </svg>
        <span class="truncate">Book Appointment</span>
      </a>
      
      <a href="{{ route('pet.video.schedule') }}" class="{{ $baseItem }} {{ $active('pet.video.schedule') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <span class="truncate">Video Calling Schedule by Doctor</span>
      </a>
    @else
      <!-- Vet/Doctor Menu -->
      <a href="{{ route('doctor.dashboard') }}" class="{{ $baseItem }} {{ $active('doctor.dashboard') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <span class="truncate">Video Consultation</span>
      </a>
      
      <a href="{{ route('groomer.services.index') }}" class="{{ $baseItem }} {{ $active('groomer.services.index') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h6v6H4V6zm0 8h6v6H4v-6zm8-8h6v6h-6V6zm0 8h6v6H4v-6z"/>
        </svg>
        <span class="truncate">Services</span>
      </a>
      
      <a href="{{ route('doctor.bookings') }}" class="{{ $baseItem }} {{ $active('doctor.bookings') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h8l6 6v10a2 2 0 01-2 2z"/>
        </svg>
        <span class="truncate">My Bookings</span>
      </a>
      
      <a href="{{ route('doctor.video.schedule.manage') }}" class="{{ $baseItem }} {{ $active('doctor.video.schedule.manage') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="truncate">Video Calling Schedule</span>
      </a>
      
      <a href="{{ route('clinic.orders') }}" class="{{ $baseItem }} {{ $active('clinic.orders') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
        </svg>
        <span class="truncate">Order History</span>
      </a>
      
      <a href="{{ route('clinic.payments') }}" class="{{ $baseItem }} {{ $active('clinic.payments') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-4.418 0-8 1.79-8 4s3.582 4 8 4 8-1.79 8-4-3.582-4-8-4zm0-6v4m0 12v4"/>
        </svg>
        <span class="truncate">Payments</span>
      </a>
      
      <a href="{{ route('clinic.booking.payments') }}" class="{{ $baseItem }} {{ $active('clinic.booking.payments') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m-7 6h8a2 2 0 002-2V8a2 2 0 00-2-2H9l-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h2z"/>
        </svg>
        <span class="truncate">Booking Payments</span>
      </a>
      
      <a href="{{ route('clinic.doctors') }}" class="{{ $baseItem }} {{ $active('clinic.doctors') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m8-4a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        <span class="truncate">Clinic Doctors</span>
      </a>
      
      <a href="{{ route('doctor.schedule') }}" class="{{ $baseItem }} {{ $active('doctor.schedule') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="truncate">Clinic Schedule</span>
      </a>
      
      <a href="{{ route('doctor.emergency-hours') }}" class="{{ $baseItem }} {{ $active('doctor.emergency-hours') ? 'bg-white/20 ring-1 ring-white/20 text-white' : '' }}">
        <svg class="w-5 h-5 opacity-90 group-hover:opacity-100 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636A9 9 0 105.636 18.364 9 9 0 1018.364 5.636z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l2.5 2.5"/>
        </svg>
        <span class="truncate">Emergency Coverage</span>
      </a>
    @endif
  </nav>
  
  <!-- Logout section -->
  <div class="p-4 border-t border-white/10">
    <a href="{{ route('logout') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition hover:bg-white/10 text-sm text-white/80 hover:text-white">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      <span>Logout</span>
    </a>
  </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebar-overlay');
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const sidebarClose = document.getElementById('sidebar-close');
  
  // Function to open sidebar
  function openSidebar() {
    sidebar.classList.add('open');
    sidebarOverlay.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }
  
  // Function to close sidebar
  function closeSidebar() {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }
  
  // Event listeners
  if (mobileMenuButton) {
    mobileMenuButton.addEventListener('click', openSidebar);
  }
  
  if (sidebarClose) {
    sidebarClose.addEventListener('click', closeSidebar);
  }
  
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebar);
  }
  
  // Close sidebar when clicking on a link (mobile)
  const sidebarLinks = document.querySelectorAll('#sidebar a');
  sidebarLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth < 1024) {
        closeSidebar();
      }
    });
  });
  
  // Close sidebar on escape key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeSidebar();
    }
  });
  
  // Auto-close sidebar when window is resized to desktop size
  window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024) {
      closeSidebar();
    }
  });
});
</script>