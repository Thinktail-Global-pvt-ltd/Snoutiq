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
        .admin-header {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }
        .admin-header h1 {
            color: #fff;
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
    <header class="admin-header py-4">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="h3 mb-1">SnoutIQ Admin Overview</h1>
                    <p class="mb-0 text-white-50">Review clinic onboarding data without requiring authentication.</p>
                </div>
                <div class="text-end text-white-50 small">
                    Updated <span id="adminCurrentDate"></span>
                </div>
            </div>
        </div>
    </header>
    <main class="admin-content">
        <div class="container">
            @yield('content')
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('adminCurrentDate').textContent = new Date().toLocaleDateString(undefined, {
        day: '2-digit', month: 'short', year: 'numeric'
    });
</script>
@stack('scripts')
</body>
</html>
