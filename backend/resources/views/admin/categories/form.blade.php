@extends('layouts.app')
@section('content')
<div class="container py-4">
  <h1 class="h3 fw-bold mb-3">{{ $category->exists ? 'Edit' : 'New' }} Category</h1>
  <form method="POST" action="{{ $category->exists ? route('admin.categories.update',$category) : route('admin.categories.store') }}">
    @csrf @if($category->exists) @method('PUT') @endif

    <div class="mb-3">
      <label class="form-label">Name</label>
      <input name="name" class="form-control" value="{{ old('name',$category->name) }}" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Slug (optional)</label>
      <input name="slug" class="form-control" value="{{ old('slug',$category->slug) }}">
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="4">{{ old('description',$category->description) }}</textarea>
    </div>

    <div class="mt-3">
      <button class="btn btn-primary">Save</button>
      <a href="{{ route('admin.categories.index') }}" class="btn btn-link">Cancel</a>
    </div>
  </form>
</div>
@endsection

