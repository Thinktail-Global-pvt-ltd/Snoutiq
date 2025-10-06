@extends('layouts.app')
@section('content')
<div class="container py-4">
  <div class="page-header">
    <h1 class="h3">Category: <span class="text-muted">{{ $category->name }}</span></h1>
  </div>

  <div class="row g-4">
    @foreach($posts as $post)
      <div class="col-12 col-md-6 col-lg-4">
        <a class="card h-100 text-decoration-none text-reset shadow-soft" href="{{ route('blog.post',$post) }}">
          <div class="card-body">
            <h2 class="h5 mb-2">{{ $post->title }}</h2>
            @if($post->excerpt)
              <p class="text-muted mb-0">{{ $post->excerpt }}</p>
            @endif
          </div>
        </a>
      </div>
    @endforeach
  </div>

  <div class="mt-4">{{ $posts->links('pagination::bootstrap-5') }}</div>
</div>
@endsection

