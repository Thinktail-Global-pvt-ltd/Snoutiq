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

        <section class="rounded-2xl border border-slate-200 bg-gradient-to-br from-blue-50 via-white to-slate-50 p-6">
          <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold mb-3">Hero section</div>
          <div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold mb-4">
            <span class="mr-2">*</span> Partnered with SnoutIQ
          </div>
          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2" for="website_title">Hero title</label>
          <input
            id="website_title"
            name="website_title"
            type="text"
            value="{{ old('website_title', $clinic->website_title) }}"
            class="block w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-3xl md:text-4xl font-bold text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
            placeholder="Premium Care for Your Furry Family"
          />
          <p class="mt-2 text-xs text-slate-500">Optional. Displays as the main headline on your landing page.</p>

          <label class="mt-6 block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2" for="website_subtitle">Hero subtitle</label>
          <textarea
            id="website_subtitle"
            name="website_subtitle"
            rows="3"
            class="block w-full rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-lg text-slate-600 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
            placeholder="Short paragraph about your clinic and services."
          >{{ old('website_subtitle', $clinic->website_subtitle) }}</textarea>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
          <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold mb-3">About section</div>
          <div class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold mb-3">
            <span class="mr-2">*</span> Our Story
          </div>
          <h3 class="text-2xl font-bold text-slate-900 mb-3">About {{ $clinic->name ?? 'Your Clinic' }}</h3>
          <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2" for="website_about">About text</label>
          <textarea
            id="website_about"
            name="website_about"
            rows="4"
            class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-base text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
            placeholder="Tell pet parents about your clinic story, specialties, or services."
          >{{ old('website_about', $clinic->website_about) }}</textarea>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6">
          <div class="text-xs uppercase tracking-wide text-slate-500 font-semibold mb-3">Gallery photos</div>
          <label class="block text-sm font-medium text-slate-700 mb-2" for="gallery_images">Upload new photos</label>
          <input
            id="gallery_images"
            name="gallery_images[]"
            type="file"
            accept="image/*"
            multiple
            class="block w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
          />
          <p class="mt-2 text-xs text-slate-500">Upload JPG, PNG, or WEBP images (max 5 MB each).</p>

        @if(!empty($gallery))
          <div class="mt-5">
            <div class="text-sm font-semibold text-slate-700 mb-3">Current gallery</div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
              @foreach($gallery as $path)
                @php
                  $src = $path;
                  if (!\Illuminate\Support\Str::startsWith($src, ['http://', 'https://', 'data:image', '/'])) {
                      $src = asset($src);
                  }
                @endphp
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                  <img src="{{ $src }}" alt="Clinic gallery photo" class="h-32 w-full rounded-md object-cover">
                  <label class="mt-2 flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="remove_gallery[]" value="{{ $path }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Remove
                  </label>
                </div>
              @endforeach
            </div>
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
