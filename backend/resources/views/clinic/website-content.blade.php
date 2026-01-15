{{-- Clinic website content editor --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Website Content')
@section('page_title','Website Content')

@section('content')
  <div class="max-w-5xl mx-auto space-y-6">
    @if(session('status'))
      <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
        {{ session('status') }}
      </div>
    @endif

    @if($errors->any())
      <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
        <div class="font-semibold">Update failed</div>
        <ul class="mt-2 list-disc pl-5 text-sm">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Public website content</h2>
        <p class="text-sm text-slate-500">
          Changes appear on your clinic landing page.
          @if(!empty($clinic->slug))
            <a href="{{ url('/vets/'.$clinic->slug) }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Preview page</a>
          @endif
        </p>
      </div>

      <form action="{{ route('clinic.website.update') }}" method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 class="text-base font-semibold text-slate-900">Editable preview</h3>
              <p class="text-xs text-slate-500">Edit inside the preview to match the exact placement on your landing page.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
              <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
              Click text blocks to edit
            </div>
          </div>
        </div>

        <section class="rounded-2xl border border-slate-200 overflow-hidden">
          <div class="bg-gradient-to-br from-sky-50 via-white to-blue-100 px-6 py-8">
            <div class="grid lg:grid-cols-2 gap-8 items-center">
              <div class="space-y-4">
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                  <span class="mr-2">*</span> Partnered with SnoutIQ
                </div>
                <label class="block text-[11px] uppercase tracking-[0.3em] text-slate-400" for="website_title">Hero title</label>
                <input
                  id="website_title"
                  name="website_title"
                  type="text"
                  value="{{ old('website_title', $clinic->website_title) }}"
                  class="block w-full rounded-2xl border border-dashed border-slate-200 bg-transparent px-3 py-2 text-4xl md:text-5xl font-bold text-slate-900 shadow-sm focus:border-indigo-500 focus:bg-white/80 focus:ring-2 focus:ring-indigo-200"
                  placeholder="Premium Care for Your Furry Family"
                />
                <label class="block text-[11px] uppercase tracking-[0.3em] text-slate-400 mt-2" for="website_subtitle">Hero subtitle</label>
                <textarea
                  id="website_subtitle"
                  name="website_subtitle"
                  rows="3"
                  class="block w-full rounded-2xl border border-dashed border-slate-200 bg-transparent px-3 py-2 text-lg text-slate-600 shadow-sm focus:border-indigo-500 focus:bg-white/80 focus:ring-2 focus:ring-indigo-200"
                  placeholder="Short paragraph about your clinic and services."
                >{{ old('website_subtitle', $clinic->website_subtitle) }}</textarea>

                <div class="mt-4 text-sm text-slate-600">Download SnoutIQ App</div>
                <div class="flex flex-wrap gap-3">
                  <div class="flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow">
                    <span class="text-lg">A</span>
                    <div>
                      <div class="text-[10px]">Download on the</div>
                      <div>App Store</div>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow">
                    <span class="text-lg">G</span>
                    <div>
                      <div class="text-[10px]">Get it on</div>
                      <div>Google Play</div>
                    </div>
                  </div>
                </div>
                <div class="mt-3 text-xs text-slate-500">Secure & HIPAA compliant - Thousands of pet parents trust SnoutIQ</div>
              </div>

              <div class="max-w-sm w-full mx-auto bg-white rounded-2xl shadow-xl p-6">
                <div class="text-center">
                  <div class="text-lg font-bold text-slate-900">Scan to Download</div>
                  <div class="text-sm text-slate-500">Use your phone camera to scan the QR code</div>
                </div>
                <div class="mt-4 flex items-center justify-center">
                  <div class="h-36 w-36 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 text-xs">
                    QR Preview
                  </div>
                </div>
                <div class="mt-4 text-xs text-slate-500 text-center">App Features</div>
                <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-slate-500">
                  <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-blue-400"></span>Easy Booking</div>
                  <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-blue-400"></span>Health Records</div>
                  <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-blue-400"></span>Virtual Visits</div>
                  <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-blue-400"></span>Medication Alerts</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
          <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold mb-3">
            <span class="mr-2">*</span> Our Story
          </div>
          <h3 class="text-3xl font-bold text-slate-900 mb-2">About {{ $clinic->name ?? 'Your Clinic' }}</h3>
          <label class="block text-[11px] uppercase tracking-[0.3em] text-slate-400 mb-2" for="website_about">About text</label>
          <textarea
            id="website_about"
            name="website_about"
            rows="4"
            class="block w-full rounded-2xl border border-dashed border-slate-200 bg-transparent px-3 py-2 text-base text-slate-700 shadow-sm focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-200"
            placeholder="Tell pet parents about your clinic story, specialties, or services."
          >{{ old('website_about', $clinic->website_about) }}</textarea>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
              <span class="mr-2">*</span> Clinic Gallery
            </div>
            <label class="inline-flex items-center gap-2 rounded-lg border border-dashed border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-indigo-300 hover:text-indigo-600 cursor-pointer">
              <input
                id="gallery_images"
                name="gallery_images[]"
                type="file"
                accept="image/*"
                multiple
                class="hidden"
              />
              + Upload new photos
            </label>
          </div>
          <p class="mt-2 text-xs text-slate-500">Upload JPG, PNG, or WEBP images (max 5 MB each).</p>

          @if(!empty($gallery))
            <div class="mt-5 grid grid-cols-2 sm:grid-cols-3 gap-4">
              @foreach($gallery as $path)
                @php
                  $src = $path;
                  if (!\Illuminate\Support\Str::startsWith($src, ['http://', 'https://', 'data:image', '/'])) {
                      $src = asset($src);
                  }
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2">
                  <img src="{{ $src }}" alt="Clinic gallery photo" class="h-32 w-full rounded-xl object-cover">
                  <label class="mt-2 flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="remove_gallery[]" value="{{ $path }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Remove
                  </label>
                </div>
              @endforeach
            </div>
          @else
            <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-400">
              Your gallery photos will appear here.
            </div>
          @endif
        </section>

        <div class="flex flex-wrap items-center gap-3">
          <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
          >
            Save changes
          </button>
          <span class="text-xs text-slate-500">Use this to keep your public clinic page fresh.</span>
        </div>
      </form>
    </div>
  </div>
@endsection
