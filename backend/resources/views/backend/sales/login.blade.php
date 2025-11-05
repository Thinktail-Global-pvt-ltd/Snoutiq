<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Login • SnoutIQ</title>
    <style>
        :root {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            background: linear-gradient(145deg,#0f172a 0%,#1d4ed8 45%,#38bdf8 100%);
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .card {
            background: rgba(255,255,255,0.98);
            border-radius: 1.4rem;
            padding: 2.75rem;
            box-shadow: 0 35px 60px -40px rgba(15,23,42,0.75);
            width: min(420px, 92vw);
        }
        h1 {
            margin: 0 0 .35rem;
            font-size: 2rem;
            letter-spacing: -.02em;
        }
        p {
            margin: 0;
            color: #475569;
        }
        form {
            margin-top: 2rem;
            display: grid;
            gap: 1.4rem;
        }
        label {
            font-weight: 600;
            color: #0f172a;
            display: block;
            margin-bottom: .5rem;
        }
        input {
            width: 100%;
            border-radius: 0.95rem;
            border: 1px solid #cbd5f5;
            padding: .85rem 1rem;
            font-size: 1rem;
            background: #f8fafc;
            transition: border .2s ease, box-shadow .2s ease;
        }
        input:focus {
            outline: none;
            border-color: rgba(37,99,235,.8);
            box-shadow: 0 0 0 3px rgba(37,99,235,.15);
        }
        button {
            border: none;
            border-radius: 0.95rem;
            padding: .9rem 1.2rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(90deg,#2563eb,#0ea5e9);
            cursor: pointer;
            box-shadow: 0 20px 35px -28px rgba(37,99,235,.85);
        }
        .error {
            color: #b91c1c;
            background: #fee2e2;
            border-radius: 0.9rem;
            padding: .8rem 1rem;
            font-weight: 600;
            font-size: .92rem;
        }
        .note {
            font-size: .85rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Sales Console Login</h1>
        <p>Use your sales credentials to access the QR tools.</p>

        @if(session('status'))
            <div class="error" style="background:#dcfce7;color:#047857;margin-top:1rem;">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="error" style="margin-top:1rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('sales.login.attempt') }}">
            @csrf
            <div>
                <label for="email">Work Email</label>
                <input type="email" id="email" name="email" placeholder="sales@admin.com" value="{{ old('email') }}" required>
            </div>
            <div>
                <label for="password">Passcode</label>
                <input type="password" id="password" name="password" placeholder="Enter passcode" required>
                <p class="note">Need access? Ping SnoutIQ admin to get the passcode.</p>
            </div>
            <button type="submit">Sign in</button>
        </form>
    </div>
</body>
</html>

