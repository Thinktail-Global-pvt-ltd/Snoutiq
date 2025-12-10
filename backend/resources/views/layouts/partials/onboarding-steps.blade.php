@php
  $labels = [
    1 => ['title'=>'Add a Service','desc'=>'Create at least one service patients can book'],
    2 => ['title'=>'Video Calling Schedule','desc'=>'Set your live video consultation hours'],
    3 => ['title'=>'Clinic Schedule','desc'=>'Configure in-clinic hours for walk-ins/visits'],
    4 => ['title'=>'Emergency Hours Collection','desc'=>'Share night coverage team & pricing'],
    5 => ['title'=>'Documents & Compliance','desc'=>'Upload clinic & doctor credentials'],
  ];
  $steps = array_keys($labels);
  $totalSteps = count($steps);
  $active = (int) ($active ?? (request('step') ? (int)request('step') : 1));
  $active = max(1, min($totalSteps, $active));
  $s1 = route('groomer.services.index') . '?onboarding=1&step=1&open=create';
  $s2 = route('doctor.video.schedule.manage') . '?onboarding=1&step=2';
  $s3 = route('doctor.schedule') . '?onboarding=1&step=3';
  $s4 = route('doctor.emergency-hours') . '?onboarding=1&step=4';
  $s5 = route('doctor.documents') . '?onboarding=1&step=5';
  $routes = [
    1 => $s1,
    2 => $s2,
    3 => $s3,
    4 => $s4,
    5 => $s5,
  ];
  $nextUrl = $active < $totalSteps ? $routes[$active + 1] : route('dashboard.profile');
  $backUrl = $active > 1 ? $routes[$active - 1] : null;
  $percent = (int) round(($active / max(1, $totalSteps)) * 100);
  $stepStatus = $stepStatus ?? [];
  $statusKeys = [
    1 => 'services',
    2 => 'video',
    3 => 'clinic_hours',
    4 => 'emergency',
    5 => 'documents',
  ];
  $hasExplicitStatus = count($stepStatus) > 0;
@endphp

<div class="mb-4">
  <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-gray-800">Clinic Setup</span>
        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">Step {{ $active }} of {{ $totalSteps }}</span>
      </div>
      <div class="flex items-center gap-2">
        @if($backUrl)
          <a href="{{ $backUrl }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 hover:bg-gray-50 text-gray-700">Back</a>
        @endif
        <a href="{{ $nextUrl }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white">{{ $active < $totalSteps ? 'Next' : 'Finish' }}</a>
        <a href="{{ route('doctor.dashboard') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-500 hover:text-gray-700">Skip for now</a>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      @foreach($steps as $i)
        @php
          $statusKey = $statusKeys[$i] ?? null;
          $statusDone = $statusKey ? !empty($stepStatus[$statusKey]) : false;
          if ($hasExplicitStatus) {
            $state = $i === $active ? 'active' : ($statusDone ? 'done' : 'todo');
          } else {
            $state = $i < $active ? 'done' : ($i === $active ? 'active' : 'todo');
          }
          $link = $routes[$i] ?? $s5;
        @endphp
        <a href="{{ $link }}" class="group flex items-center gap-3 p-3 rounded-xl border transition @if($state==='active') border-indigo-500 bg-indigo-50 @elseif($state==='done') border-emerald-400 bg-emerald-50 @else border-rose-200 bg-rose-50 hover:bg-rose-100 @endif">
          <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold @if($state==='active') bg-indigo-600 text-white @elseif($state==='done') bg-emerald-600 text-white @else bg-rose-100 text-rose-700 @endif">
            {{ $state==='done' ? '✓' : $i }}
          </span>
          <span>
            <div class="text-sm font-semibold @if($state==='active') text-indigo-800 @elseif($state==='done') text-emerald-800 @else text-rose-800 @endif">{{ $labels[$i]['title'] }}</div>
            <div class="text-xs @if($state==='active' || $state==='done') text-gray-500 @else text-rose-500 @endif">{{ $labels[$i]['desc'] }}</div>
          </span>
        </a>
      @endforeach
    </div>

    <div class="mt-3 h-2 bg-gray-200 rounded-full overflow-hidden">
      <div class="h-2 bg-indigo-600" style="width: {{ $percent }}%"></div>
    </div>
  </div>
</div>
