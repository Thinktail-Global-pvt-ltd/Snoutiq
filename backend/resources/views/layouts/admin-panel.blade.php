<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'Admin Dashboard') • SnoutIQ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @stack('styles')
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
        .admin-card { border: none; border-radius: 18px; box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16); }
        .tab-pill .nav-link { border-radius: 999px; padding: 0.55rem 1.6rem; font-weight: 600; color: #475569; }
        .tab-pill .nav-link.active { background: linear-gradient(135deg, #2563eb, #7c3aed); color: #fff; }
        .section-title { font-weight: 700; letter-spacing: 0.04em; color: #1e293b; }
        .section-title span { font-weight: 500; color: #64748b; }
        .badge-soft-success { background: rgba(34, 197, 94, 0.15); color: #15803d; }
        .badge-soft-warning { background: rgba(250, 204, 21, 0.18); color: #854d0e; }
        .badge-soft-info { background: rgba(14, 165, 233, 0.18); color: #0c4a6e; }
        .badge-soft-danger { background: rgba(248, 113, 113, 0.18); color: #7f1d1d; }
        .stat-chip { border-radius: 18px; padding: 0.8rem 1.2rem; background: #f1f5f9; }
        .stat-chip strong { display: block; font-size: 1.25rem; color: #0f172a; }
        .stat-chip span { color: #475569; font-size: 0.82rem; letter-spacing: 0.04em; text-transform: uppercase; }
        .admin-secondary-nav { display: flex; flex-direction: column; gap: 0.35rem; }
        .admin-secondary-nav a { color: rgba(255, 255, 255, 0.65); border-radius: 0.75rem; padding: 0.55rem 0.9rem; text-decoration: none; font-weight: 500; display: block; transition: background 0.2s ease, color 0.2s ease; }
        .admin-secondary-nav a.active, .admin-secondary-nav a:hover { background: rgba(255, 255, 255, 0.15); color: #fff; }
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
            <a href="{{ route('admin.onboarding.panel') }}" class="{{ request()->routeIs('admin.onboarding.panel') ? 'active' : '' }}">Onboarding Panel</a>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'active' : '' }}">Users</a>
            <a href="{{ route('admin.pets') }}" class="{{ request()->routeIs('admin.pets') ? 'active' : '' }}">Pets</a>
            <a href="{{ route('admin.doctors') }}" class="{{ request()->routeIs('admin.doctors') ? 'active' : '' }}">Doctors</a>
            <a href="{{ route('admin.online-doctors') }}" class="{{ request()->routeIs('admin.online-doctors') ? 'active' : '' }}">Online Doctors</a>
            <a href="{{ route('admin.vet-registrations') }}" class="{{ request()->routeIs('admin.vet-registrations') ? 'active' : '' }}">Vet Registrations</a>
            <a href="{{ route('admin.bookings') }}" class="{{ request()->routeIs('admin.bookings') ? 'active' : '' }}">Bookings</a>
            <a href="{{ route('admin.supports') }}" class="{{ request()->routeIs('admin.supports') ? 'active' : '' }}">Supports</a>
            <a href="{{ route('admin.video.slot-overview') }}" class="{{ request()->routeIs('admin.video.slot-overview') ? 'active' : '' }}">Video Slot Overview</a>
        </nav>
        @hasSection('sidebar-secondary')
            <div class="mb-4">
                @yield('sidebar-secondary')
            </div>
        @endif
        @php($logoutAction = trim($__env->yieldContent('logout-action')) ?: route('admin.logout'))
        <form action="{{ $logoutAction }}" method="POST" class="logout-btn w-100">
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
@stack('scripts')
</body>
</html>
