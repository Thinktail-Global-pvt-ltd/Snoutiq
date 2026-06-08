@extends('layouts.admin-panel')

@section('page-title', 'Blog Manager')

@section('content')
<div class="container-fluid px-0">
    <!-- Tab Navigation -->
    <ul class="nav nav-pills tab-pill mb-4" id="blogTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" href="{{ route('admin.posts.index') }}">
                <i class="bi bi-file-earmark-post me-2"></i>Posts
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" href="{{ route('admin.categories.index') }}">
                <i class="bi bi-folder2-open me-2"></i>Categories
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" href="{{ route('admin.tags.index') }}">
                <i class="bi bi-tags me-2"></i>Tags
            </a>
        </li>
    </ul>

    <!-- Quick Stats Overview -->
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="stat-chip d-flex align-items-center justify-content-between p-3 border shadow-sm bg-white rounded-3">
                <div>
                    <span>Total Posts</span>
                    <strong>{{ \App\Models\Post::count() }}</strong>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-inline-flex">
                    <i class="bi bi-journal-text fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="stat-chip d-flex align-items-center justify-content-between p-3 border shadow-sm bg-white rounded-3">
                <div>
                    <span>Published</span>
                    <strong class="text-success">{{ \App\Models\Post::where('status', 'published')->count() }}</strong>
                </div>
                <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-inline-flex">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="stat-chip d-flex align-items-center justify-content-between p-3 border shadow-sm bg-white rounded-3">
                <div>
                    <span>Drafts</span>
                    <strong class="text-warning">{{ \App\Models\Post::where('status', 'draft')->count() }}</strong>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-3 d-inline-flex">
                    <i class="bi bi-file-earmark-lock fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Header Actions -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h5 mb-0 fw-bold text-secondary">Manage Blog Posts</h2>
        <a href="{{ route('admin.posts.create') }}" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm" style="background: linear-gradient(135deg, #2563eb, #7c3aed); border: none;">
            <i class="bi bi-plus-lg me-2"></i>Create New Post
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
                        <th class="py-3 px-4" style="width: 40%;">Title</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3">Categories / Tags</th>
                        <th class="py-3 px-3">Created</th>
                        <th class="py-3 px-4 text-end" style="width: 250px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($posts as $post)
                    <tr>
                        <td class="py-3 px-4">
                            <div class="d-flex align-items-center">
                                @if($post->featured_image)
                                    <img src="{{ $post->featured_image }}" alt="" class="rounded-3 me-3 border" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="rounded-3 me-3 bg-light border d-flex align-items-center justify-content-center text-muted" style="width: 50px; height: 50px;">
                                        <i class="bi bi-image fs-5"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold text-dark">{{ $post->title }}</div>
                                    <small class="text-muted text-break">/blog/{{ $post->slug }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            @if(($post->status ?? '') === 'published')
                                <span class="badge badge-soft-success py-2 px-3 rounded-pill">
                                    <i class="bi bi-globe me-1"></i>Published
                                </span>
                            @else
                                <span class="badge badge-soft-warning py-2 px-3 rounded-pill">
                                    <i class="bi bi-pencil me-1"></i>Draft
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-3">
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($post->categories as $cat)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 rounded-pill">{{ $cat->name }}</span>
                                @endforeach
                                @foreach($post->tags as $tag)
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-10 rounded-pill">#{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            <small class="text-muted d-block">{{ $post->created_at->format('M j, Y') }}</small>
                            <small class="text-muted fs-7" style="font-size: 0.78rem;">{{ $post->created_at->diffForHumans() }}</small>
                        </td>
                        <td class="py-3 px-4 text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-primary rounded-3 px-3" href="{{ route('admin.posts.edit', $post) }}">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                                <a class="btn btn-sm btn-outline-secondary rounded-3 px-3" href="/blog/{{ $post->slug }}" target="_blank">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this blog post?')">
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
                        <td colspan="5" class="py-5 text-center text-muted">
                            <i class="bi bi-file-earmark-post fs-1 d-block mb-3 opacity-50"></i>
                            No blog posts found. <a href="{{ route('admin.posts.create') }}" class="text-primary fw-medium">Create one now</a>.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($posts->hasPages())
        <div class="d-flex justify-content-between align-items-center mt-4 bg-white p-3 border rounded-3 shadow-sm">
            <div class="text-muted fs-7">
                Showing {{ $posts->firstItem() }} to {{ $posts->lastItem() }} of {{ $posts->total() }} posts
            </div>
            <div>
                {{ $posts->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>
@endsection
