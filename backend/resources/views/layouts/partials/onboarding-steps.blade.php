@php
  $active = (int) ($active ?? (request('step') ? (int)request('step') : 1));
  $active = max(1, min(3, $active));
  $s1 = route('groomer.services.index') . '?onboarding=1&step=1&open=create';
  $s2 = route('doctor.video.schedule.manage') . '?onboarding=1&step=2';
  $s3 = route('doctor.schedule') . '?onboarding=1&step=3';
  $nextUrl = $active === 1 ? $s2 : ($active === 2 ? $s3 : route('doctor.dashboard'));
  $backUrl = $active === 2 ? $s1 : ($active === 3 ? $s2 : null);
  $percent = $active === 1 ? 33 : ($active === 2 ? 66 : 100);
  $labels = [
    1 => ['title'=>'Add a Service','desc'=>'Create at least one service patients can book'],
    2 => ['title'=>'Video Calling Schedule','desc'=>'Set your live video consultation hours'],
    3 => ['title'=>'Clinic Schedule','desc'=>'Configure in‑clinic hours for walk‑ins/visits'],
  ];
@endphp

<div class="mb-4">
  <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-gray-800">Clinic Setup</span>
        <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">Step {{ $active }} of 3</span>
      </div>
      <div class="flex items-center gap-2">
        @if($backUrl)
          <a href="{{ $backUrl }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-300 hover:bg-gray-50 text-gray-700">Back</a>
        @endif
        <a href="{{ $nextUrl }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white">{{ $active < 3 ? 'Next' : 'Finish' }}</a>
        <a href="{{ route('doctor.dashboard') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-500 hover:text-gray-700">Skip for now</a>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-3">
      @foreach([1,2,3] as $i)
        @php
          $state = $i < $active ? 'done' : ($i === $active ? 'active' : 'todo');
          $link = $i === 1 ? $s1 : ($i === 2 ? $s2 : $s3);
        @endphp
        <a href="{{ $link }}" class="group flex items-center gap-3 p-3 rounded-xl border transition @if($state==='active') border-indigo-500 bg-indigo-50 @elseif($state==='done') border-emerald-400 bg-emerald-50 @else border-gray-200 bg-white hover:bg-gray-50 @endif">
          <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold @if($state==='active') bg-indigo-600 text-white @elseif($state==='done') bg-emerald-600 text-white @else bg-gray-200 text-gray-700 @endif">
            {{ $state==='done' ? '✓' : $i }}
          </span>
          <span>
            <div class="text-sm font-semibold @if($state==='active') text-indigo-800 @elseif($state==='done') text-emerald-800 @else text-gray-800 @endif">{{ $labels[$i]['title'] }}</div>
            <div class="text-xs text-gray-500">{{ $labels[$i]['desc'] }}</div>
          </span>
        </a>
      @endforeach
    </div>

    <div class="mt-3 h-2 bg-gray-200 rounded-full overflow-hidden">
      <div class="h-2 bg-indigo-600" style="width: {{ $percent }}%"></div>
    </div>
  </div>
</div>

