<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="theme-color" content="#2563eb"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-status-bar-style" content="default"/>
  <meta name="mobile-web-app-capable" content="yes"/>
  <title>Login | Test Clinic (Vet Only)</title>
  <link rel="icon" href="https://snoutiq.com/favicon.webp" sizes="32x32" type="image/png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <script>
    (function(){
      const isLocal = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
      const prefix = isLocal ? '' : '/backend';
      const manifestHref = `${prefix}/custom-doctor-manifest.webmanifest`;
      const appleIconHref = `${prefix}/custom-doctor-icon-192.png`;

      const manifestLink = document.createElement('link');
      manifestLink.rel = 'manifest';
      manifestLink.href = manifestHref;
      document.head.appendChild(manifestLink);

      const appleTouchIcon = document.createElement('link');
      appleTouchIcon.rel = 'apple-touch-icon';
      appleTouchIcon.href = appleIconHref;
      document.head.appendChild(appleTouchIcon);

      window.__CUSTOM_DOCTOR_PWA__ = { prefix, manifestHref };
    })();
  </script>

  <style>
    :root{--bg1:#eef4ff;--bg2:#e9eefe;--card:#ffffff;--text:#0f172a;--muted:#64748b;--blue:#2563eb;--blue-d:#1e40af;--border:#e5e7eb;--ring:rgba(37,99,235,.25)}
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Ubuntu,sans-serif;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:var(--text)}
    .wrap{min-height:100dvh;display:grid;place-items:center;padding:32px}
    .card{width:100%;max-width:480px;background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.08);padding:24px 22px}

    h1{font-size:24px;margin:0 0 6px;text-align:center}
    .sub{color:var(--muted);text-align:center;margin:0 0 16px}
    label{display:block;font-size:13px;font-weight:600;color:#334155;margin:0 0 6px}
    .input{width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;background:#fff;outline:none}
    .input:focus{border-color:transparent;box-shadow:0 0 0 3px var(--ring)}
    .row{margin-bottom:14px}
    .btn{width:100%;padding:12px 14px;border-radius:10px;border:0;font-weight:700;cursor:pointer}
    .btn-primary{background:var(--blue);color:#fff}
    .btn-primary:hover{background:var(--blue-d)}
    .pw-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:transparent;border:0;cursor:pointer}
    .debug{display:none!important}
    .debug h3{margin:0 0 8px;font-size:14px}
    .debug pre{max-height:360px;overflow:auto;background:#0b1020;color:#d1e7ff;padding:12px;border-radius:10px;font-size:12px;line-height:1.45}
  </style>
</head>
<body>
<div class="wrap">
  <main class="card"><h1>Welcome Back!</h1>
    <p class="sub">Sign in to continue to your Test Clinic account</p>

    <!-- Vet form only -->
    <form id="vetForm">
      <div class="row">
        <label for="email">Email Address</label>
        <input id="email" type="email" class="input" placeholder="Enter your email address" autocomplete="email"/>
      </div>

      <div class="row" style="position:relative">
        <div>
          <label for="password">Password
            <a class="right" style="float:right;color:#2563eb;text-decoration:none;font-weight:600" href="/forgot-password">Forgot Password?</a>
          </label>
        </div>
        <input id="password" type="password" class="input" placeholder="Enter your password" autocomplete="current-password"/>
        <button id="pwBtn" class="pw-toggle" type="button" aria-label="Show password">👁️</button>
      </div>

      <button id="loginBtn" class="btn btn-primary" type="submit">Login</button>
    </form>

    <!-- On-page dump (hidden by default) -->
    <div class="debug">
      <h3>Debug dump (user + session)</h3>
      <pre id="dump">Waiting for login…</pre>
    </div>

    <div class="foot" style="margin-top:16px;text-align:center">
      <p style="color:#64748b">Don't have an account?
        <a class="link" style="color:#2563eb;text-decoration:none;font-weight:600" href="/register">Create an account</a>
      </p>
    </div>
  </main>
</div>

<script>
  // Silence all console output on the login page
  (function(){
    try{
      const noop=function(){};
      if (window.console) {
        ['log','warn','error','info','debug','trace'].forEach(k=>{ try{ console[k]=noop; }catch(_){ /* ignore */ } });
      }
    }catch(_){ /* ignore */ }
  })();

  // -------- Smart bases: local vs production --------
  const ORIGIN   = window.location.origin; // http://127.0.0.1:8000 or https://snoutiq.com
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const API_BASE = IS_LOCAL ? `${ORIGIN}/api`        : `${ORIGIN}/backend/api`;
  const WEB_BASE = IS_LOCAL ? `${ORIGIN}`            : `${ORIGIN}/backend`;

  // 👇 Redirect target per environment (vet onboarding)
  const POST_LOGIN_REDIRECT = IS_LOCAL
    ? `${ORIGIN}/dashboard/services?open=create&onboarding=1&step=1`
    : `${ORIGIN}/backend/dashboard/services?open=create&onboarding=1&step=1`;

  const ROUTES = {
    login:        `${API_BASE}/auth/login`,
    sessionLogin: IS_LOCAL ? 'http://127.0.0.1:8000/api/session/login' : `${ORIGIN}/backend/api/session/login`,
  };

  axios.defaults.withCredentials = true;

  // ---------- helpers ----------
  function stripBOM(s){ return typeof s === 'string' ? s.replace(/^\uFEFF/, '').trim() : s; }
  function parseDeepJSON(x) {
    let v = stripBOM(x);
    if (typeof v !== 'string') return v;
    try { v = JSON.parse(v); } catch { return x; }
    if (typeof v === 'string') { try { v = JSON.parse(v); } catch {} }
    return v;
  }
  function extractUserId(obj) {
    const cands = [obj?.user?.id, obj?.user_id, obj?.vet_registerations_temp_id, obj?.vet_registration_temp_id, obj?.vet_registeration_id, obj?.vet_id];
    for (const c of cands) {
      if (c === null || c === undefined || String(c).trim() === '') continue;
      const n = Number(c);
      if (Number.isFinite(n) && n > 0) return n;
    }
    return null;
  }
  function dump(obj, title='Dump'){
    try{
      const meta = {
        title,
        is_local: IS_LOCAL,
        page_origin: ORIGIN,
        login_endpoint: ROUTES.login,
        session_endpoint: ROUTES.sessionLogin,
        post_login_redirect: POST_LOGIN_REDIRECT
      };
      const pretty = JSON.stringify({ meta, ...obj }, null, 2);
      document.getElementById('dump').textContent = pretty;
    }catch(e){
      document.getElementById('dump').textContent = String(obj);
    }
  }
  function saveAuthFull(obj){
    try{
      const json = JSON.stringify(obj);
      sessionStorage.setItem('auth_full', json);
      localStorage.setItem('auth_full', json);
    }catch(e){}
  }
  async function syncSessionWithBackend(userId){
    if(!userId){ return { ok:false, error:'missing user_id' }; }
    try{
      const { data } = await axios.get(ROUTES.sessionLogin, {
        params: { user_id: userId, role: 'vet' },
        withCredentials: true
      });
      return { ok:true, response:data };
    }catch(err){
      const payload = err?.response?.data || { message: String(err) };
      return { ok:false, error:payload };
    }
  }

  // ---------- Password show/hide ----------
  const els = {
    vetForm: document.getElementById('vetForm'),
    email: document.getElementById('email'),
    password: document.getElementById('password'),
    pwBtn: document.getElementById('pwBtn'),
    loginBtn: document.getElementById('loginBtn'),
    dump: document.getElementById('dump'),
  };

  els.pwBtn.addEventListener('click', ()=>{
    const isText = els.password.type === 'text';
    els.password.type = isText ? 'password' : 'text';
    els.pwBtn.textContent = isText ? '👁️' : '🙈';
  });

  // ---------- Vet login (redirect after session OK) ----------
  els.vetForm.addEventListener('submit', async (e)=>{
    e.preventDefault();
    els.loginBtn.disabled = true; els.loginBtn.textContent = 'Logging in...';
    try{
      const res = await axios.post(ROUTES.login, {
        login: els.email.value,
        password: els.password.value,
        role: 'vet',
      }, { withCredentials: true });

      const loginDataRaw    = res?.data;
      const loginDataParsed = parseDeepJSON(loginDataRaw);

      if (loginDataParsed?.role === 'admin') {
        const adminTarget = loginDataParsed?.redirect || `${WEB_BASE}/admin/dashboard`;
        dump({ loginDataRaw, loginDataParsed, adminTarget }, 'Admin Login');
        setTimeout(()=> window.location.replace(adminTarget), 150);
        return;
      }

      const computedUserId = extractUserId(loginDataParsed);

      const payload = {
        success: true,
        message: loginDataParsed?.message || 'Login success',
        role: 'vet',
        email:  loginDataParsed?.email ?? loginDataParsed?.user?.email ?? null,
        token:  loginDataParsed?.token,
        token_type: loginDataParsed?.token_type || 'Bearer',
        chat_room: loginDataParsed?.chat_room || null,
        user: loginDataParsed?.user || null,
        user_id: computedUserId,
      };
      saveAuthFull(payload);

      const sessionSync = await syncSessionWithBackend(payload.user_id);
      dump({ loginDataRaw, loginDataParsed, payload, sessionSync }, 'Vet Login + Session');

      if (sessionSync.ok) {
        const target = POST_LOGIN_REDIRECT;
        setTimeout(()=> window.location.replace(target), 150);
      }
    }catch(err){
      const data = err?.response?.data || { error:String(err) };
      dump({ error:'Vet login failed', detail:data }, 'Vet Login Error');
    }finally{
      els.loginBtn.disabled = false; els.loginBtn.textContent = 'Login';
    }
  });

  // ---------- PWA service worker registration ----------
  (function(){
    if (!('serviceWorker' in navigator)) { return; }
    const scopePrefix = (window.__CUSTOM_DOCTOR_PWA__?.prefix ?? '') || '';
    const scopePath = `${scopePrefix}/custom-doctor-login`.replace(/\/$/, '');
    const swUrl = `${scopePrefix}/custom-doctor-sw.js`;
    const isLocal = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
    const isSecure = window.isSecureContext || window.location.protocol === 'https:' || isLocal;
    if (!isSecure) { return; }

    window.addEventListener('load', () => {
      navigator.serviceWorker.register(swUrl, { scope: `${scopePath}/` })
        .catch(() => {
          /* registration errors are intentionally swallowed to keep the login UX silent */
        });
    });
  })();
</script>

</body>
</html>
