@extends('layouts.admin-panel')

@section('page-title', 'Blog Manager')

@section('content')
<div class="container-fluid px-0">
    <!-- Tab Navigation -->
    <ul class="nav nav-pills tab-pill mb-4" id="blogTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link" href="{{ route('admin.posts.index') }}">
                <i class="bi bi-file-earmark-post me-2"></i>Posts
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link active" href="{{ route('admin.categories.index') }}">
                <i class="bi bi-folder2-open me-2"></i>Categories
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" href="{{ route('admin.tags.index') }}">
                <i class="bi bi-tags me-2"></i>Tags
            </a>
        </li>
    </ul>

    <!-- Header Actions -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 mb-0 fw-bold text-secondary">Manage Categories</h2>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #2563eb, #7c3aed); border: none;">
            <i class="bi bi-plus-lg me-2"></i>Add New Category
        </a>
    </div>

    <!-- Feedback Alerts -->
    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4 rounded-3 d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-3 fs-5 text-success"></i>
            <div>{{ session('ok') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Main Table Card -->
    <div class="card admin-card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase fs-7 tracking-wider">
                    <tr>
                        <th class="py-3 px-4" style="width: 30%;">Name</th>
                        <th class="py-3 px-3" style="width: 30%;">Slug</th>
                        <th class="py-3 px-3">Description</th>
                        <th class="py-3 px-4 text-end" style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($categories as $c)
                    <tr>
                        <td class="py-3 px-4 fw-semibold text-dark">{{ $c->name }}</td>
                        <td class="py-3 px-3">
                            <code class="text-secondary bg-light px-2 py-1 rounded fs-7">{{ $c->slug }}</code>
                        </td>
                        <td class="py-3 px-3 text-muted">
                            {{ Str::limit($c->description ?: '—', 80) }}
                        </td>
                        <td class="py-3 px-4 text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-primary rounded-3 px-3" href="{{ route('admin.categories.edit', $c) }}">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                                <form action="{{ route('admin.categories.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category? All posts associated with it will lose this category.')">
                                    @csrf 
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger rounded-3 px-3">
                                        <i class="bi bi-trash3 me-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-5 text-center text-muted">
                            <i class="bi bi-folder2-open fs-1 d-block mb-3 opacity-50"></i>
                            No categories found. <a href="{{ route('admin.categories.create') }}" class="text-primary fw-medium">Add one now</a>.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($categories->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-4 bg-white p-3 border rounded-3 shadow-sm">
            <div class="text-muted fs-7">
                Showing {{ $categories->firstItem() }} to {{ $categories->lastItem() }} of {{ $categories->total() }} categories
            </div>
            <div>
                {{ $categories->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>
@endsection
