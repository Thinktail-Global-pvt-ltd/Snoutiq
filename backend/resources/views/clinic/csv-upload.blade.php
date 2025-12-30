{{-- Simple CSV upload view for clinic admins --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Upload CSV')
@section('page_title','Upload CSV')

@section('content')
  <div class="max-w-3xl mx-auto">
    @if(session('status'))
      <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
        {{ session('status') }}
      </div>
    @endif

    @if($errors->any())
      <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
        <div class="font-semibold">Upload failed</div>
        <ul class="mt-2 list-disc pl-5 text-sm">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-6 py-4">
        <h2 class="text-lg font-semibold text-slate-900">Upload a CSV file</h2>
        <p class="text-sm text-slate-500">Files are saved under <code>storage/app/csv-uploads</code> on the server.</p>
      </div>

      <form action="{{ route('clinic.csv-upload.store') }}" method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
        @csrf
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2" for="csv-file">CSV file</label>
          <input
            id="csv-file"
            name="file"
            type="file"
            accept=".csv,text/csv"
            required
            class="block w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30"
          />
          <p class="mt-2 text-xs text-slate-500">Allowed: .csv (max 10 MB).</p>
        </div>

        <div class="flex items-center gap-3">
          <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
          >
            Upload CSV
          </button>
          <span class="text-xs text-slate-500">This does not process rows; it just stores the file safely.</span>
        </div>
      </form>

      @if(session('upload'))
        @php $upload = session('upload'); @endphp
        <div class="border-t border-slate-100 px-6 py-4 bg-slate-50">
          <div class="text-sm font-semibold text-slate-800 mb-2">Saved file details</div>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm text-slate-700">
            <div>
              <dt class="text-xs uppercase tracking-wide text-slate-500">Path</dt>
              <dd class="font-mono text-sm text-slate-900">{{ $upload['path'] ?? '' }}</dd>
            </div>
            @if(!empty($upload['absolute_path']))
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Server path</dt>
                <dd class="font-mono text-sm text-slate-900 break-all">{{ $upload['absolute_path'] }}</dd>
              </div>
            @endif
            @if(!empty($upload['original_name']))
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Original name</dt>
                <dd>{{ $upload['original_name'] }}</dd>
              </div>
            @endif
            @if(isset($upload['size_bytes']))
              <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Size</dt>
                <dd>{{ number_format($upload['size_bytes'] / 1024, 1) }} KB</dd>
              </div>
            @endif
          </dl>
          @if(!empty($upload['path']))
            @php $encodedPath = base64_encode($upload['path']); @endphp
            <div class="mt-3">
              <a
                href="{{ route('clinic.csv-upload.show', ['path' => $encodedPath]) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
              >
                View uploaded file
              </a>
            </div>
          @endif
        </div>
      @endif
    </div>
  </div>
@endsection
