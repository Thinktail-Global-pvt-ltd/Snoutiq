<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'Sales Console') • SnoutIQ</title>
    @stack('sales-styles-head')
    <style>
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            display: flex;
            min-height: 100vh;
        }
        .sales-sidebar {
            width: 240px;
            background: linear-gradient(180deg, #0f172a 0%, #1d4ed8 100%);
            color: #f8fafc;
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .sales-sidebar h1 {
            margin: 0;
            font-size: 1.4rem;
            letter-spacing: -0.02em;
        }
        .sales-sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .sales-sidebar a {
            color: rgba(248, 250, 252, 0.85);
            text-decoration: none;
            padding: 0.55rem 0.75rem;
            border-radius: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .sales-sidebar a span.icon {
            font-size: 1rem;
        }
        .sales-sidebar a.active,
        .sales-sidebar a:hover {
            background: rgba(248, 250, 252, 0.15);
            color: #fff;
        }
        .sales-main {
            flex: 1;
            background: #f1f5f9;
            min-height: 100vh;
            overflow: auto;
        }
        .sales-main-header {
            background: linear-gradient(90deg, #1d4ed8, #0ea5e9);
            padding: 2rem 2.5rem 1.5rem;
            color: #fff;
        }
        .sales-content {
            padding: 2.5rem;
        }
        @media (max-width: 900px) {
            body {
                flex-direction: column;
            }
            .sales-sidebar {
                width: 100%;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
            .sales-sidebar nav {
                flex-direction: row;
                flex-wrap: wrap;
            }
        }
    </style>
    @stack('sales-styles')
</head>
@php
    $salesLinks = [
        ['label' => 'CRM', 'href' => route('sales.crm'), 'icon' => '🧾', 'pattern' => 'sales.crm'],
        ['label' => 'Clinic QRs', 'href' => route('sales.clinic-qr'), 'icon' => 'QR', 'pattern' => 'sales.clinic-qr'],
        ['label' => 'QR Analytics', 'href' => route('sales.qr-analytics'), 'icon' => '📈', 'pattern' => 'sales.qr-analytics'],
        ['label' => 'Dashboard', 'href' => route('sales.dashboard'), 'icon' => '📊', 'pattern' => 'sales.dashboard'],
        ['label' => 'Admin Onboarding', 'href' => route('admin.onboarding.panel'), 'icon' => '↗', 'external' => true],
    ];
@endphp
<body>
    <aside class="sales-sidebar">
        <div>
            <h1>Sales Console</h1>
            <p style="margin:0;color:rgba(248,250,252,0.7);font-size:0.9rem;">SnoutIQ GTM tools</p>
        </div>
        <nav>
            @foreach($salesLinks as $link)
                @php
                    $isActive = isset($link['pattern'])
                        ? request()->routeIs($link['pattern'])
                        : false;
                @endphp
                <a href="{{ $link['href'] }}"
                   @if(! empty($link['external'])) target="_blank" rel="noopener" @endif
                   class="{{ $isActive ? 'active' : '' }}">
                    <span class="icon">{{ $link['icon'] }}</span>
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div style="margin-top:auto;font-size:0.85rem;opacity:0.8;">
            {{ now()->format('d M Y') }}
        </div>
    </aside>
    <div class="sales-main">
        <header class="sales-main-header">
            <h2 style="margin:0;font-size:2rem;">@yield('hero-title', 'Sales Tools')</h2>
            <p style="margin:0.5rem 0 0;opacity:0.9;">@yield('hero-description', 'Operate faster with SnoutIQ sales tooling.')</p>
        </header>
        <main class="sales-content">
            @yield('content')
        </main>
    </div>
@stack('sales-scripts')
</body>
</html>
