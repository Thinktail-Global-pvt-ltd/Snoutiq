<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        :root {
            color-scheme: light;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top right, #2145FF0D 0%, transparent 55%),
                        radial-gradient(circle at bottom left, #00AEEF14 0%, transparent 45%),
                        #0f172a;
            min-height: 100vh;
            color: #0f172a;
        }
        .admin-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .admin-frame {
            flex: 1;
            display: flex;
        }
        .admin-sidebar {
            width: 250px;
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(18px);
            color: rgba(226, 232, 240, 0.85);
            padding: 2rem 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .admin-sidebar .brand {
            font-size: 1.25rem;
            font-weight: 600;
            color: #f8fafc;
        }
        .admin-sidebar .brand span {
            display: block;
            font-size: 0.82rem;
            font-weight: 400;
            color: rgba(226, 232, 240, 0.6);
            margin-top: 0.35rem;
        }
        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .admin-nav a {
            display: block;
            padding: 0.65rem 0.9rem;
            border-radius: 0.75rem;
            text-decoration: none;
            color: rgba(226, 232, 240, 0.75);
            font-weight: 500;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .admin-nav a.active,
        .admin-nav a:hover {
            color: #fff;
            background: rgba(59, 130, 246, 0.25);
        }
        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .admin-header {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }
        .admin-header h1 {
            color: #fff;
        }
        .admin-header .header-meta {
            color: rgba(226, 232, 240, 0.65);
        }
        .admin-content {
            flex: 1;
            padding: 2rem 0 3rem;
        }
        .admin-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16);
        }
        .tab-pill .nav-link {
            border-radius: 999px;
            padding: 0.55rem 1.6rem;
            font-weight: 600;
            color: #475569;
        }
        .tab-pill .nav-link.active {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #fff;
        }
        .section-title {
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #1e293b;
        }
        .section-title span {
            font-weight: 500;
            color: #64748b;
        }
        table thead {
            background: #f8fafc;
        }
        .badge-soft-success {
            background: rgba(34, 197, 94, 0.15);
            color: #15803d;
        }
        .badge-soft-warning {
            background: rgba(250, 204, 21, 0.18);
            color: #854d0e;
        }
        .badge-soft-info {
            background: rgba(14, 165, 233, 0.18);
            color: #0c4a6e;
        }
        .badge-soft-danger {
            background: rgba(248, 113, 113, 0.18);
            color: #7f1d1d;
        }
        .stat-chip {
            border-radius: 18px;
            padding: 0.8rem 1.2rem;
            background: #f1f5f9;
        }
        .stat-chip strong {
            display: block;
            font-size: 1.25rem;
            color: #0f172a;
        }
        .stat-chip span {
            color: #475569;
            font-size: 0.82rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        @media (max-width: 1200px) {
            .admin-frame {
                flex-direction: column;
            }
            .admin-sidebar {
                width: 100%;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }
            .admin-nav {
                flex-direction: row;
                flex-wrap: wrap;
            }
        }
        @media (max-width: 992px) {
            .tab-pill {
                flex-wrap: wrap;
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body>
<div class="admin-shell">
    <div class="admin-frame">
        @php($hasSidebar = $__env->hasSection('sidebar'))
        @if($hasSidebar)
            <aside class="admin-sidebar">
                @yield('sidebar')
            </aside>
        @endif
        <div class="admin-main">
            @hasSection('header')
                <header class="admin-header py-4">
                    <div class="container-fluid">
                        @yield('header')
                    </div>
                </header>
            @endif
            @php($containerClass = $hasSidebar ? 'container-fluid' : 'container')
            <main class="admin-content">
                <div class="{{ $containerClass }}">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const dateTarget = document.getElementById('adminCurrentDate');
    if (dateTarget) {
        dateTarget.textContent = new Date().toLocaleDateString(undefined, {
            day: '2-digit', month: 'short', year: 'numeric'
        });
    }
</script>
@stack('scripts')
</body>
</html>
