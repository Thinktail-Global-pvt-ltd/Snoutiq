@extends('layouts.app')
@section('content')
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 mb-0">Posts</h1>
    <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">New Post</a>
  </div>

  <div class="card shadow-soft">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Title</th>
            <th>Status</th>
            <th>Created</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
        @foreach($posts as $post)
          <tr>
            <td class="fw-medium">{{ $post->title }}</td>
            <td>
              @if(($post->status ?? '') === 'published')
                <span class="badge text-bg-success">Published</span>
              @elseif(($post->status ?? '') === 'draft')
                <span class="badge text-bg-secondary">Draft</span>
              @else
                <span class="badge text-bg-light text-muted">—</span>
              @endif
            </td>
            <td><small class="text-muted">{{ $post->created_at->format('M j, Y') }}</small></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary me-2" href="{{ route('admin.posts.edit',$post) }}">Edit</a>
              <a class="btn btn-sm btn-outline-secondary me-2" href="{{ route('blog.post',$post) }}" target="_blank">View</a>
              <form action="{{ route('admin.posts.destroy',$post) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this post?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">{{ $posts->links('pagination::bootstrap-5') }}</div>
</div>
@endsection

