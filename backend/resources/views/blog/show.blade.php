@extends('layouts.app')

@push('meta')
  <title>{{ $post->meta_title ?: $post->title }}</title>
  <meta name="description" content="{{ $post->meta_description ?: ($post->excerpt ?? Str::limit(strip_tags($post->content),160)) }}">
  @if($post->featured_image)<meta property="og:image" content="{{ $post->featured_image }}">@endif
@endpush

@section('content')
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <header class="mb-3">
        <h1 class="h2 fw-bold mb-1">{{ $post->title }}</h1>
        <div class="text-muted small">{{ $post->created_at->format('F j, Y') }}</div>
      </header>

      @php
        $placeholder = 'https://placehold.co/1200x600?text=No+Image';
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
      <img class="img-fluid rounded mb-4" src="{{ $img }}" alt="{{ $post->title }}">

      <article class="content-prose">{!! nl2br(e($post->content)) !!}</article>

      <div class="mt-4">
        <a href="{{ route('blog.index') }}" class="btn btn-link px-0">&larr; Back to all posts</a>
      </div>
    </div>
  </div>
  
</div>
@endsection
