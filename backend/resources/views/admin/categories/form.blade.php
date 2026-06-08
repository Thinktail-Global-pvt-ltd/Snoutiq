@extends('layouts.admin-panel')

@section('page-title', $category->exists ? 'Edit Category' : 'New Category')

@section('content')
<div class="container-fluid px-0" style="max-width: 800px; margin: 0 auto;">
    <!-- Breadcrumbs -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.posts.index') }}" class="text-decoration-none">Blog Manager</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}" class="text-decoration-none">Categories</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $category->exists ? 'Edit Category' : 'Create Category' }}</li>
                </ol>
            </nav>
            <h2 class="h4 mb-0 fw-bold text-dark">{{ $category->exists ? 'Edit Category' : 'Create New Category' }}</h2>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary rounded-3 px-3">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <!-- Error/Success Alerts -->
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4 rounded-3" role="alert">
            <div class="fw-bold mb-2">Please correct the following errors:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 rounded-3 d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-3 fs-5 text-success"></i>
            <div>{{ session('ok') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Form -->
    <div class="card admin-card border-0 shadow-sm p-4 bg-white">
        <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
            @csrf 
            @if($category->exists) 
                @method('PUT') 
            @endif

            <div class="mb-3">
                <label class="form-label fw-semibold text-dark">Category Name <span class="text-danger">*</span></label>
                <input id="name-input" name="name" class="form-control rounded-3 border-secondary-subtle" value="{{ old('name', $category->name) }}" placeholder="e.g. Dog Health" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-dark">Slug <span class="text-muted">(Auto-generated or custom)</span></label>
                <input id="slug-input" name="slug" class="form-control rounded-3 border-secondary-subtle" value="{{ old('slug', $category->slug) }}" placeholder="e.g. dog-health" data-auto="true">
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold text-dark">Description</label>
                <textarea name="description" class="form-control rounded-3 border-secondary-subtle" rows="4" placeholder="Briefly describe what kind of posts belong to this category...">{{ old('description', $category->description) }}</textarea>
            </div>

            <div class="d-flex align-items-center gap-2 pt-2 border-top">
                <button class="btn btn-primary px-4 py-2.5 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #2563eb, #7c3aed); border: none;">
                    <i class="bi bi-save me-2"></i>{{ $category->exists ? 'Save Changes' : 'Create Category' }}
                </button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary px-4 py-2.5 rounded-3">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .breadcrumb-item a {
      color: #64748b;
    }
    .breadcrumb-item a:hover {
      color: #2563eb;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('name-input');
        const slugInput = document.getElementById('slug-input');

        if (nameInput && slugInput) {
            if (slugInput.value) {
                slugInput.dataset.auto = 'false';
            }

            nameInput.addEventListener('input', function() {
                if (slugInput.dataset.auto === 'true') {
                    const slug = nameInput.value
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '') // remove special chars
                        .replace(/\s+/g, '-')          // replace spaces with -
                        .replace(/-+/g, '-');          // remove multiple -
                    
                    let trimmedSlug = slug.replace(/^-+|-+$/g, '');
                    slugInput.value = trimmedSlug;
                }
            });

            slugInput.addEventListener('input', function() {
                slugInput.dataset.auto = 'false';
            });
        }
    });
</script>
@endpush
@endsection
