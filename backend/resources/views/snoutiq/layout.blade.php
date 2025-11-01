<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SnoutIQ Dev</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 20px; }
        header { margin-bottom: 20px; }
        .container { max-width: 980px; margin: 0 auto; }
        nav a { margin-right: 12px; }
        fieldset { margin: 18px 0; padding: 12px 16px; }
        legend { font-weight: 600; }
        label { display:block; margin: 6px 0 2px; }
        input[type=text], input[type=number], select, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        button { margin-top: 10px; padding: 8px 12px; cursor: pointer; }
        .row { display:flex; gap: 12px; }
        .row > div { flex:1; }
        pre { background:#0b1021; color:#d6e0ff; padding:10px; border-radius:6px; overflow:auto; }
        .muted { color:#777; }
        .success { color: #0a7a21; }
        .error { color: #b20a0a; }
    </style>
    @yield('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
      function fmt(obj){ try{return JSON.stringify(obj,null,2)}catch(e){return String(obj)} }
      async function api(method, url, data){
        const opts = { method, headers: { 'Content-Type':'application/json' } };
        if(method !== 'GET' && data!==undefined) opts.body = JSON.stringify(data);
        const res = await fetch(url, opts);
        const text = await res.text();
        try { return { ok: res.ok, status:res.status, json: JSON.parse(text), raw:text } }
        catch { return { ok: res.ok, status:res.status, json: null, raw:text } }
      }
    </script>
    @yield('scripts_head')
</head>
<body>
<div class="container">
    <header>
        <h1>SnoutIQ Dev Tools</h1>
        <nav>
            @if(\Illuminate\Support\Facades\Route::has('snoutiq.dev.index'))
                <a href="{{ route('snoutiq.dev.index') }}">Home</a>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('snoutiq.dev.booking'))
                <a href="{{ route('snoutiq.dev.booking') }}">Booking Tester</a>
            @endif
        </nav>
        <div class="muted">Base: http://127.0.0.1:8000</div>
    </header>
    @yield('content')
</div>
</body>
</html>
