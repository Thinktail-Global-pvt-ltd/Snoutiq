<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    @stack('meta') {{-- allows pages to push <title> and meta for SEO --}}

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous">

    @stack('styles')

    <!-- Minimal theme overrides for better readability -->
    <style>
      :root {
        --si-bg: #f8fafc;
        --si-surface: #ffffff;
        --si-border: #e5e7eb;
        --si-text: #0f172a;
        --si-muted: #64748b;
        --si-primary: #0d6efd;
      }
      body { background-color: var(--si-bg); color: var(--si-text); }
      .navbar-brand { font-weight: 700; letter-spacing: .2px; }
      .page-header { margin-bottom: 1.5rem; }
      .page-header h1 { margin: 0; font-weight: 700; }
      .card-img-top { object-fit: cover; height: 180px; }
      .content-prose p { line-height: 1.8; margin-bottom: 1rem; }
      .content-prose img { max-width: 100%; height: auto; border-radius: .5rem; margin: 1rem 0; }
      .content-prose h2, .content-prose h3 { margin-top: 1.5rem; font-weight: 700; }
      .table > :not(caption) > * > * { vertical-align: middle; }
      .shadow-soft { box-shadow: 0 1px 2px rgba(16,24,40,.06), 0 1px 3px rgba(16,24,40,.1); }
      .card-hover{ transition: transform .12s ease, box-shadow .12s ease; }
      .card-hover:hover{ transform: translateY(-2px); box-shadow: 0 8px 24px rgba(16,24,40,.12); }
      .line-clamp-2{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
      .line-clamp-3{ display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
      .badge-soft{ background: #eef2ff; color:#3846a1; }
    </style>
</head>

<body>
@include('layouts.partials.page-loader')
<div id="app">
    <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
        <div class="container">
            {{-- Brand → /blogs --}}
            <a class="navbar-brand" href="{{ route('blog.index') }}">
                {{ config('app.name', 'Laravel') }}
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.posts.index') }}">Posts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.posts.create') }}">New Post</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.categories.index') }}">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.tags.index') }}">Tags</a>
                    </li>
                </ul>

                <!-- Right -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/blogs/feed') }}">Feed</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/blogs/sitemap.xml') }}">Sitemap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        @yield('content')
    </main>
</div>

<!-- Bootstrap JS (placed at end for performance) -->
<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
  crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>
