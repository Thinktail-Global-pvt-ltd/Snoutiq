@extends('layouts.snoutiq-dashboard')

@php
  $page_title = $page_title ?? 'Documents & Compliance';
  $clinic = $clinic ?? null;
  $doctors = $doctors ?? collect();
  $highlightDoctor = session('highlight_doctor');
  $defaultErrors = $errors->getBag('default');
@endphp

@section('title', $page_title)
@section('page_title', $page_title)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
  @php $stepStatus = $stepStatus ?? []; @endphp
  @if(request()->get('onboarding') === '1')
    @include('layouts.partials.onboarding-steps', [
      'active' => (int) (request()->get('step', 5)),
      'stepStatus' => $stepStatus,
    ])
  @endif

  @if (session('status'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
         data-onboarding-status="1"
         data-status-message="{{ session('status') }}">
      <span>{{ session('status') }}</span>
      @if(request()->get('onboarding') === '1')
        <button type="button"
                data-complete-onboarding
                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow">
          Complete onboarding
        </button>
      @endif
    </div>
  @elseif(request()->get('onboarding') === '1')
    <div class="flex justify-end">
      <button type="button"
              data-complete-onboarding
              class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow">
        Complete onboarding
      </button>
    </div>
  @endif

  @if ($defaultErrors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-700 px-4 py-3 text-sm space-y-1">
      @foreach ($defaultErrors->all() as $message)
        <div>{{ $message }}</div>
      @endforeach
    </div>
  @endif

  <div class="bg-white shadow-sm ring-1 ring-gray-200/60 rounded-2xl p-6 space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Clinic License & Documents</h2>
        <p class="text-sm text-gray-600 mt-1 max-w-2xl">
          Business registration proof (GST Certificate, MSME certificate, Shop registration). Upload the relevant registration document and confirm the active registration number.
        </p>
      </div>
      @if($clinic?->license_document)
        <a href="{{ asset($clinic->license_document) }}" target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M8 12l4 4m0 0l4-4m-4 4V4" />
          </svg>
          View current file
        </a>
      @endif
    </div>

    <form method="POST" action="{{ route('doctor.documents.update', request()->only(['onboarding', 'step'])) }}" enctype="multipart/form-data" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="license_no">Business registration number</label>
        <p class="text-xs text-gray-500 mb-2">Required to mark onboarding as complete.</p>
        <input
          type="text"
          id="license_no"
          name="license_no"
          value="{{ old('license_no', $clinic->license_no ?? '') }}"
          class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
          placeholder="Enter your business registration number"
        />
        @error('license_no')
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="license_document">Upload Business registration proof</label>
        <p class="text-xs text-gray-500 mb-2">Required to mark onboarding as complete.</p>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
          <div>
            <p class="text-sm text-gray-700" id="clinic_license_filename">
              {{ $clinic?->license_document ? basename($clinic->license_document) : 'No file uploaded yet.' }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG (max 5MB)</p>
          </div>
          <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium cursor-pointer hover:bg-indigo-700">
            Choose file
            <input type="file" name="license_document" id="license_document" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
          </label>
        </div>
        @error('license_document')
          <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
      </div>

      <div class="flex items-center justify-end">
        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          Save clinic documents
        </button>
      </div>
    </form>
  </div>

  <div class="bg-white shadow-sm ring-1 ring-gray-200/60 rounded-2xl p-6 space-y-5">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Doctor Credentials</h2>
        <p class="text-sm text-gray-600 mt-1 max-w-2xl">
          Upload certifications or renewed licenses for each doctor on your roster. These stay private and help us validate clinic compliance.
        </p>
      </div>
      <span class="text-xs text-gray-500">{{ $doctors->count() }} doctor{{ $doctors->count() === 1 ? '' : 's' }}</span>
    </div>

    @if($doctors->isEmpty())
      <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm">
        No doctors linked to this clinic yet. Add doctors first, then upload their credentials here.
      </div>
    @else
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach($doctors as $doctor)
          @php
            $bagName = 'doctor_' . $doctor->id;
            $doctorErrors = $errors->getBag($bagName);
            $isHighlighted = (int) ($highlightDoctor ?? 0) === (int) $doctor->id;
            $oldDoctorId = (int) old('doctor_id', 0);
            $licenseValue = $oldDoctorId === (int)$doctor->id
              ? old('doctor_license', $doctor->doctor_license)
              : ($doctor->doctor_license ?? '');
          @endphp
          <div class="rounded-2xl border {{ $isHighlighted ? 'border-indigo-400 bg-indigo-50/40' : 'border-gray-200 bg-white' }} p-4 space-y-4 shadow-sm">
            @php
              $existingLicense = trim((string) $doctor->doctor_license) !== '' ? trim($doctor->doctor_license) : null;
            @endphp
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <h3 class="text-base font-semibold text-gray-900">{{ $doctor->doctor_name }}</h3>
                <p class="text-xs text-gray-500">
                  License on file:
                  <span class="{{ $existingLicense ? 'text-gray-800 font-medium' : 'text-amber-600' }}">
                    {{ $existingLicense ?? 'Not provided yet' }}
                  </span>
                </p>
              </div>
              <div class="flex flex-col items-end gap-2">
                <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                  ID: {{ $doctor->id }}
                </span>
                @if($doctor->doctor_document)
                  <a href="{{ asset($doctor->doctor_document) }}" target="_blank" rel="noopener noreferrer"
                     class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-indigo-200 bg-indigo-50 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M8 12l4 4m0 0l4-4m-4 4V4" />
                    </svg>
                    View credential
                  </a>
                @endif
              </div>
            </div>

            <form method="POST"
                  action="{{ route('doctor.documents.doctor', array_merge(['doctor' => $doctor->id], request()->only(['onboarding', 'step']))) }}"
                  enctype="multipart/form-data"
                  class="space-y-3">
              @csrf
              <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="doctor_license_{{ $doctor->id }}">License / registration number</label>
                <input
                  type="text"
                  id="doctor_license_{{ $doctor->id }}"
                  name="doctor_license"
                  value="{{ $licenseValue }}"
                  class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="Enter doctor license number"
                />
                @error('doctor_license', $bagName)
                  <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="doctor_document_{{ $doctor->id }}">Upload credential</label>
                <div class="flex flex-col gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
                  <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center sm:justify-between gap-2 sm:gap-3">
                    <div class="text-sm text-gray-700 min-w-0 break-words" id="doctor_document_name_{{ $doctor->id }}">
                      {{ $doctor->doctor_document ? basename($doctor->doctor_document) : 'No file selected.' }}
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                      @if($doctor->doctor_document)
                        <a href="{{ asset($doctor->doctor_document) }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-indigo-200 bg-white text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-4.553a1 1 0 011.414 0L23 7.586a1 1 0 010 1.414L18.447 13.553a1 1 0 01-1.414 0L15 12" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13v6a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2h6" />
                          </svg>
                          View file
                        </a>
                      @endif
                      <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium cursor-pointer hover:bg-indigo-700 whitespace-nowrap">
                        Choose file
                        <input
                          type="file"
                          name="doctor_document"
                          id="doctor_document_{{ $doctor->id }}"
                          class="hidden"
                          accept=".pdf,.jpg,.jpeg,.png"
                          data-doctor-file-input
                          data-target="doctor_document_name_{{ $doctor->id }}"
                        >
                      </label>
                    </div>
                  </div>
                  <p class="text-xs text-gray-500">Accepted formats: PDF, JPG, PNG (max 5MB)</p>
                </div>
                @error('doctor_document', $bagName)
                  <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
              </div>

              <div class="flex items-center justify-end">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Save doctor credential
                </button>
              </div>
            </form>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>
@endsection

@section('scripts')
<script>
  (function(){
    var s=document.createElement('script'); s.src='https://cdn.jsdelivr.net/npm/sweetalert2@11'; document.head.appendChild(s);
  })();

  document.addEventListener('DOMContentLoaded', () => {
    const FINISH_URL = @json(route('dashboard.profile'));
    const HAS_LICENSE_NO = @json(trim((string) ($clinic->license_no ?? '')) !== '');
    const HAS_LICENSE_DOC = @json(!empty($clinic?->license_document));
    const setFileName = (input, display) => {
      if (!input || !display) return;
      const defaultText = display.textContent.trim();
      input.addEventListener('change', () => {
        const chosen = input.files && input.files.length ? input.files[0].name : defaultText;
        display.textContent = chosen || defaultText;
      });
    };

    setFileName(
      document.querySelector('#license_document'),
      document.querySelector('#clinic_license_filename')
    );

    document.querySelectorAll('[data-doctor-file-input]').forEach((input) => {
      const targetId = input.getAttribute('data-target');
      const display = targetId ? document.getElementById(targetId) : null;
      setFileName(input, display);
    });

    try{
      const url = new URL(window.location.href);
      const isOnboarding = (url.searchParams.get('onboarding')||'') === '1';
      const statusEl = document.querySelector('[data-onboarding-status]');
      const finishButton = document.querySelector('[data-complete-onboarding]');
      const finishUrl = FINISH_URL || `${window.location.origin}/profile`;
      const licenseInput = document.querySelector('#license_no');
      const licenseDocInput = document.querySelector('#license_document');

      if (isOnboarding && finishButton){
        const goToFinish = () => { window.location.href = finishUrl; };
        finishButton.addEventListener('click', (e) => {
          e.preventDefault();
          const hasLicense = (licenseInput && licenseInput.value.trim() !== '') || HAS_LICENSE_NO;
          const hasDoc = (licenseDocInput && licenseDocInput.files && licenseDocInput.files.length > 0) || HAS_LICENSE_DOC;
          if (!hasLicense || !hasDoc) {
            const msg = 'Business registration number and proof are required to complete onboarding.';
            if (window.Swal) {
              Swal.fire({ icon:'error', title:'Add clinic license details', text: msg });
            } else {
              alert(msg);
            }
            return;
          }
          if (window.Swal) {
            Swal.fire({
              icon:'success',
              title:'Onboarding complete',
              text:'You are all set! Head to your dashboard.',
              confirmButtonText:'Go to dashboard'
            }).then(goToFinish);
          } else {
            goToFinish();
          }
        });
      }

      if (isOnboarding && statusEl){
        const message = statusEl.getAttribute('data-status-message') || statusEl.textContent.trim();
        try{ localStorage.setItem('onboarding_v1_done','1'); }catch(_){}
        if (window.Swal){
          Swal.fire({
            icon:'success',
            title:'Documents saved',
            text: message || 'All onboarding steps are done.',
            timer:1700,
            showConfirmButton:false,
          });
        }
      }
    }catch(_){ }
  });
</script>
@endsection
