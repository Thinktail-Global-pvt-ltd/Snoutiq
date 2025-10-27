<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'Admin Dashboard') • SnoutIQ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f3f4f8; min-height: 100vh; font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .admin-shell { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 260px; background: linear-gradient(180deg, #0f172a 0%, #1f2937 100%); color: #f9fafb; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .admin-sidebar .brand { font-size: 1.25rem; font-weight: 600; margin-bottom: 2rem; }
        .admin-sidebar .brand span { display: block; font-size: 0.85rem; font-weight: 400; color: rgba(255, 255, 255, 0.65); margin-top: .35rem; }
        .admin-nav a { color: rgba(255,255,255,0.75); border-radius: 0.75rem; padding: 0.65rem 1rem; text-decoration: none; font-weight: 500; display: block; transition: background 0.2s ease, color 0.2s ease; }
        .admin-nav a.active, .admin-nav a:hover { background: rgba(255, 255, 255, 0.15); color: #fff; }
        .admin-main { flex: 1; display: flex; flex-direction: column; }
        .admin-header { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); padding: 1.25rem 2rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
        .admin-content { flex: 1; padding: 2rem; }
        .avatar-sm { width: 3rem; height: 3rem; }
        .admin-sidebar .logout-btn { margin-top: auto; }
        @media (max-width: 991px) {
            .admin-shell { flex-direction: column; }
            .admin-sidebar { width: 100%; flex-direction: row; align-items: center; gap: 1rem; padding: 1.5rem; }
            .admin-nav { display: flex; gap: 0.5rem; }
            .admin-nav a { padding: 0.5rem 0.75rem; }
        }
    </style>
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="brand">
            SnoutIQ Admin
            <span>Signed in as {{ session('admin_email', config('admin.email')) }}</span>
        </div>
        <nav class="admin-nav mb-4">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'active' : '' }}">Users</a>
            <a href="{{ route('admin.bookings') }}" class="{{ request()->routeIs('admin.bookings') ? 'active' : '' }}">Bookings</a>
            <a href="{{ route('admin.supports') }}" class="{{ request()->routeIs('admin.supports') ? 'active' : '' }}">Supports</a>
        </nav>
        <form action="{{ route('admin.logout') }}" method="POST" class="logout-btn w-100">
            @csrf
            <button type="submit" class="btn btn-outline-light w-100">Log out</button>
        </form>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <h1 class="h4 mb-0">@yield('page-title', 'Admin Dashboard')</h1>
            <div class="d-flex align-items-center gap-3 text-muted">
                <span class="badge text-bg-dark">Admin access</span>
                <small>{{ now()->format('d M Y') }}</small>
            </div>
        </header>
        <section class="admin-content">
            @yield('content')
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'Admin Dashboard') • SnoutIQ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background: #f4f6f9; min-height: 100vh; }
        .admin-shell { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 260px; background: #111827; color: #f9fafb; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .admin-sidebar .brand { font-size: 1.25rem; font-weight: 600; margin-bottom: 2rem; }
        .admin-sidebar .brand span { display: block; font-size: 0.875rem; font-weight: 400; color: #9ca3af; margin-top: .25rem; }
        .admin-nav a { color: #d1d5db; border-radius: 0.75rem; padding: 0.65rem 1rem; text-decoration: none; font-weight: 500; display: block; }
        .admin-nav a.active, .admin-nav a:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }
        .admin-main { flex: 1; display: flex; flex-direction: column; }
        .admin-header { background: #fff; padding: 1.25rem 2rem; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .admin-content { flex: 1; padding: 2rem; }
    </style>
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="brand">
            SnoutIQ Admin
            <span>{{ session('admin_email', config('admin.email')) }}</span>
        </div>
        <nav class="admin-nav mb-4">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'active' : '' }}">Users</a>
            <a href="{{ route('admin.bookings') }}" class="{{ request()->routeIs('admin.bookings') ? 'active' : '' }}">Bookings</a>
            <a href="{{ route('admin.supports') }}" class="{{ request()->routeIs('admin.supports') ? 'active' : '' }}">Supports</a>
        </nav>
        <form action="{{ route('admin.logout') }}" method="POST" class="mt-auto">
            @csrf
            <button type="submit" class="btn btn-outline-light w-100">Log out</button>
        </form>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <h1 class="h4 mb-0">@yield('page-title', 'Admin Dashboard')</h1>
        </header>
        <section class="admin-content">
            @yield('content')
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
