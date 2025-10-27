<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login • SnoutIQ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background: linear-gradient(135deg, #0f172a, #1e3a8a); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .card { max-width: 420px; width: 100%; border: none; border-radius: 1.25rem; box-shadow: 0 25px 60px rgba(15, 23, 42, 0.35); }
        .card-header { background: transparent; border: none; text-align: center; padding-bottom: 0; }
        .card-header h1 { font-size: 1.75rem; font-weight: 700; color: #1e293b; }
        .card-body { padding: 2.5rem; }
    </style>
</head>
<body>
    <div class="card bg-white">
        <div class="card-header">
            <h1>Admin Panel</h1>
            <p class="text-muted mb-0">Sign in with the static admin credentials to continue.</p>
        </div>
        <div class="card-body">
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
                    <input type="email" class="form-control form-control-lg" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="{{ config('admin.email') }}">
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="•••••••" required>
                    <small class="text-muted">Use <code>{{ config('admin.password') }}</code> as the admin password.</small>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">Sign in</button>
            </form>
        </div>
        <div class="card-footer text-center bg-transparent border-0 pb-4">
            <small class="text-muted">Only {{ config('admin.email') }} can access this panel.</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
