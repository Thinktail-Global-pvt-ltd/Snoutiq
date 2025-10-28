<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login • SnoutIQ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .auth-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: min(100%, 1100px);
        }

        @media (min-width: 992px) {
            .auth-wrapper {
                flex-direction: row;
                align-items: stretch;
            }
        }

        .auth-card {
            width: 100%;
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35);
            background-color: #ffffff;
        }

        .card-header {
            background: transparent;
            border: none;
            text-align: center;
            padding: 2rem 2.5rem 0;
        }

        .card-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
        }

        .card-body {
            padding: 2.5rem;
        }

        .online-count {
            font-size: 0.95rem;
        }

        .online-empty {
            padding: 2.5rem 1rem;
        }

        .login-card {
            max-width: none;
        }

        .status-card .card-header {
            text-align: left;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card login-card">
            <div class="card-header">
                <h1>Admin Panel</h1>
                <p class="text-muted mb-0">Sign in with the static admin credentials to continue.</p>
            </div>
            <div class="card-body">
                <nav aria-label="Admin quick links" class="mb-4">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.online-doctors') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Available Clinics</span>
                            <span class="badge bg-success rounded-pill">Live</span>
                        </a>
                        <a href="{{ route('admin.video.slot-overview') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Video Slot Overview</span>
                            <span class="badge bg-primary rounded-pill">Open</span>
                        </a>
                    </div>
                </nav>
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.attempt') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="{{ $adminEmail }}">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="••••••" required>
                        <small class="text-muted">Use <code>{{ $adminPassword }}</code> as the admin password.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Sign in</button>
                </form>
            </div>
            <div class="card-footer text-center bg-transparent border-0 pb-4">
                <div class="alert alert-light border mt-3 mb-0" role="alert">
                    <div class="fw-semibold mb-1">Default admin credentials</div>
                    <div class="small mb-1">Email: <code>{{ $adminEmail }}</code></div>
                    <div class="small">Password: <code>{{ $adminPassword }}</code></div>
                </div>
                <small class="text-muted d-block mt-3">Only {{ $adminEmail }} can access this panel.</small>
            </div>
        </div>

        <div class="auth-card status-card">
            <div class="card-header">
                <h1>Available Clinics</h1>
                <p class="text-muted online-count mb-0">
                    {{ $onlineClinics->count() }} {{ \Illuminate\Support\Str::plural('clinic', $onlineClinics->count()) }} currently available.
                </p>
            </div>
            <div class="card-body">
                @if ($onlineClinics->isEmpty())
                    <div class="text-center text-muted online-empty">
                        <div class="fw-semibold mb-1">No clinics are live right now</div>
                        <div class="small">Clinics appear here when at least one doctor toggles on and connects to the console.</div>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($onlineClinics as $clinic)
                            <div class="list-group-item py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold">{{ $clinic->name ?? 'Clinic name unavailable' }}</div>
                                        <div class="small text-muted">
                                            @if (!empty($clinic->city))
                                                {{ $clinic->city }}
                                            @endif
                                            @if (!empty($clinic->available_doctors_count))
                                                @if (!empty($clinic->city))
                                                    •
                                                @endif
                                                {{ $clinic->available_doctors_count }} {{ \Illuminate\Support\Str::plural('doctor', $clinic->available_doctors_count) }} online
                                            @endif
                                        </div>
                                    </div>
                                    <span class="badge text-bg-success-subtle text-success-emphasis">Online</span>
                                </div>
                                <div class="small text-muted mt-2 d-flex flex-wrap gap-3">
                                    @if (!empty($clinic->email))
                                        <span>Email: <a href="mailto:{{ $clinic->email }}" class="text-decoration-none">{{ $clinic->email }}</a></span>
                                    @endif
                                    @if (!empty($clinic->mobile))
                                        <span>Phone: <a href="tel:{{ $clinic->mobile }}" class="text-decoration-none">{{ $clinic->mobile }}</a></span>
                                    @endif
                                    @if (!empty($clinic->address))
                                        <span>Address: {{ $clinic->address }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
