@extends('layouts.app')
@section('content')
<div class="container py-4">
  <!-- Hero / header -->
  <section class="mb-3">
    <h1 class="display-6 fw-bold mb-1">{{ config('blog.site_name', config('app.name')) }}</h1>
    <p class="text-muted mb-0">Latest articles, tips, and updates.</p>
  </section>

  @if($posts->isEmpty())
    <div class="alert alert-light border">No posts yet. Try creating one in <a href="{{ route('admin.posts.create') }}">New Post</a>.</div>
  @endif

  <!-- Cards grid -->
  <div class="row g-4">
    @foreach($posts as $post)
      @php
        $placeholder = 'https://placehold.co/800x400?text=No+Image';
        $raw = trim((string)($post->featured_image ?? ''));
        $img = $placeholder;
        if ($raw) {
          if (preg_match('#^https?://#i', $raw)) {
            $img = $raw;
          } else {
            $path = ltrim($raw, '/');
            if (\Illuminate\Support\Str::startsWith($path, ['public/'])) {
              $path = substr($path, 7);
            }
            if (\Illuminate\Support\Str::startsWith($path, ['storage/','images/','uploads/'])) {
              $img = asset($path);
            }
          }
        }
      @endphp
      <div class="col-12 col-md-6 col-lg-4">
        <article class="card h-100 shadow-soft card-hover">
          <img class="card-img-top" src="{{ $img }}" alt="{{ $post->title }}" loading="lazy" decoding="async">
          <div class="card-body">
            <a href="{{ route('blog.post',$post) }}" class="stretched-link text-decoration-none">
              <h2 class="h5 card-title mb-2">{{ $post->title }}</h2>
            </a>
            @if($post->excerpt)
              <p class="card-text text-muted line-clamp-3">{{ $post->excerpt }}</p>
            @endif
            <div class="mt-2">
              @foreach(($post->categories ?? []) as $cat)
                <a href="{{ route('blog.category',$cat) }}" class="badge rounded-pill text-bg-light me-1 mb-1">{{ $cat->name }}</a>
              @endforeach
            </div>
          </div>
          <div class="card-footer bg-white border-0 pt-0">
            <small class="text-muted">{{ $post->created_at->format('M j, Y') }}</small>
          </div>
        </article>
      </div>
    @endforeach
  </div>

  <div class="mt-4">{{ $posts->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
