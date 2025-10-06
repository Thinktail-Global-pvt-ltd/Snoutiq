@extends('layouts.app')
@section('content')
<div class="container py-4">
  <h1 class="h3 fw-bold mb-3">{{ $tag->exists ? 'Edit' : 'New' }} Tag</h1>
  <form method="POST" action="{{ $tag->exists ? route('admin.tags.update',$tag) : route('admin.tags.store') }}">
    @csrf @if($tag->exists) @method('PUT') @endif

    <div class="mb-3">
      <label class="form-label">Name</label>
      <input name="name" class="form-control" value="{{ old('name',$tag->name) }}" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Slug (optional)</label>
      <input name="slug" class="form-control" value="{{ old('slug',$tag->slug) }}">
    </div>

    <div class="mt-3">
      <button class="btn btn-primary">Save</button>
      <a href="{{ route('admin.tags.index') }}" class="btn btn-link">Cancel</a>
    </div>
  </form>
</div>
@endsection

