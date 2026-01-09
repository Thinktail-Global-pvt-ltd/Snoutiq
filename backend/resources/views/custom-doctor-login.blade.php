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
  <link rel="icon" href="{{ asset('favicon.png') }}" sizes="32x32" type="image/png"/>
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
    :root {
      --bg1: rgba(248, 250, 255, 0.9);
      --bg2: rgba(226, 232, 240, 0.7);
      --card: #ffffff;
      --text: #0f172a;
      --muted: #475569;
      --blue: #2563eb;
      --blue-d: #1e40af;
      --border: rgba(15, 23, 42, 0.08);
      --ring: rgba(37, 99, 235, 0.3);
    }
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: Inter, system-ui, "Segoe UI", Roboto, Ubuntu, sans-serif;
      color: #0b1a4d;
      background-color: #f9fbff;
      background-image:
        linear-gradient(120deg, rgba(255, 255, 255, 0.95), #f0f4ff 60%, #e2e8ff),
        url('https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1800&q=80');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      min-height: 100vh;
      position: relative;
    }
    body::after {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        radial-gradient(circle at 5% 20%, rgba(56, 189, 248, 0.35), transparent 40%),
        radial-gradient(circle at 85% 70%, rgba(59, 130, 246, 0.4), transparent 50%),
        radial-gradient(circle at 30% 90%, rgba(248, 113, 113, 0.35), transparent 50%);
      pointer-events: none;
      z-index: 0;
    }
    .wrap {
      min-height: 100dvh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 16px;
      position: relative;
      z-index: 1;
    }
    .card {
      width: 100%;
      max-width: 460px;
      min-height: auto;
      background: linear-gradient(180deg, rgba(59, 130, 246, 0.98), rgba(37, 99, 235, 0.96));
      border-radius: 28px;
      border: 1px solid rgba(255, 255, 255, 0.35);
      box-shadow: 0 45px 90px rgba(9, 30, 66, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.2);
      padding: 36px 32px 40px;
      position: relative;
      overflow: hidden;
      backdrop-filter: blur(18px);
    }
    .card::after {
      content: '';
      position: absolute;
      inset: -20px;
      background: radial-gradient(circle at 25% 20%, rgba(255, 255, 255, 0.45), transparent 40%);
      pointer-events: none;
      opacity: 0.8;
    }
    .badge {
      display: inline-flex;
      padding: 4px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      color: #e0f2ff;
      background: rgba(14, 165, 233, 0.3);
      margin-bottom: 12px;
    }
    h1 {
      font-size: 32px;
      margin: 0;
      font-weight: 700;
      color: white;
      line-height: 1.2;
    }
    .sub {
      color: rgba(226, 232, 240, 0.9);
      text-align: left;
      margin: 8px 0 28px;
      line-height: 1.5;
      z-index: 1;
    }
    .card-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }
    .card-icon {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      background: rgba(255, 255, 255, 0.2);
      display: grid;
      place-items: center;
      font-size: 26px;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
    }
    .pretitle {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.18em;
      color: rgba(226, 232, 240, 0.7);
      margin-bottom: 4px;
    }
    .note {
      text-align: center;
      font-size: 13px;
      color: rgba(226, 232, 240, 0.9);
      margin-top: 12px;
      z-index: 1;
    }
    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: rgba(255, 255, 255, 0.85);
      margin: 0 0 6px;
    }
    .input {
      width: 100%;
      padding: 14px 18px;
      border: 1px solid rgba(255, 255, 255, 0.6);
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.95);
      outline: none;
      font-size: 15px;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .input:focus {
      border-color: rgba(14, 165, 233, 0.9);
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.2);
      background: #fff;
    }
    .row {
      margin-bottom: 16px;
    }
    .btn {
      width: 100%;
      padding: 16px 18px;
      border: 0;
      font-weight: 700;
      cursor: pointer;
      font-size: 15px;
      letter-spacing: 0.04em;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .btn-primary {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      color: #fff;
      border-radius: 12px;
      box-shadow: 0 15px 45px rgba(15, 23, 42, 0.35);
    }
    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 18px 48px rgba(15, 23, 42, 0.35);
    }
    .pw-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: 0;
      cursor: pointer;
      font-size: 18px;
    }
    .debug {
      display: none !important;
    }
    .alert {
      margin: 0 0 16px;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid rgba(239, 68, 68, 0.2);
      background: rgba(248, 113, 113, 0.12);
      color: #fee2e2;
      font-weight: 600;
      display: none;
    }
    .debug h3 {
      margin: 0 0 8px;
      font-size: 14px;
    }
    .debug pre {
      max-height: 360px;
      overflow: auto;
      background: #0b1020;
      color: #d1e7ff;
      padding: 12px;
      border-radius: 10px;
      font-size: 12px;
      line-height: 1.45;
    }
    .foot {
      font-size: 14px;
      color: #475569;
    }
    .right {
      font-weight: 600;
    }
    @media (max-width: 600px) {
      .wrap {
        padding: 20px 12px;
      }
      .card {
        border-radius: 22px;
        padding: 28px 22px 30px;
        max-width: 420px;
      }
      h1 {
        font-size: 26px;
      }
      .card-icon {
        width: 44px;
        height: 44px;
        font-size: 22px;
      }
    }
  </style>
</head>
<body>
<div class="wrap">
  <main class="card">
    <span class="badge">Vet Dashboard</span>
    <div class="card-header">
      <span class="card-icon">🐾</span>
      <div>
        <p class="pretitle">SnoutIQ Vet Control</p>
        <h1>Vet Dashboard Login</h1>
      </div>
    </div>
    <p class="sub">Sign in to continue to your SnoutIQ veterinarian dashboard and manage pet care with confidence.</p>

    <div id="errorBox" class="alert" role="alert" aria-live="polite"></div>

    <!-- Vet form only -->
    <form id="vetForm">
      <div class="row">
        <label for="email">Email Address</label>
        <input id="email" type="email" class="input" placeholder="Enter your email address" autocomplete="email"/>
      </div>

      <div class="row" style="position:relative">
        <div>
          <label for="password">Password</label>
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

    <div class="note">Only verified veterinarians can access the dashboard. Reach out if you need help with your credentials.</div>
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
  const RECEPTIONIST_REDIRECT = `${WEB_BASE}/receptionist/front-desk`;

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
    const cands = [
      obj?.user?.id,
      obj?.user_id,
      obj?.doctor_id,
      obj?.receptionist_id,
      obj?.vet_registerations_temp_id,
      obj?.vet_registration_temp_id,
      obj?.vet_registeration_id,
      obj?.vet_id,
    ];
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
  async function syncSessionWithBackend(userId, role){
    if(!userId){ return { ok:false, error:'missing user_id' }; }
    const roleToSend = role || 'clinic_admin';
    try{
      const { data } = await axios.get(ROUTES.sessionLogin, {
        params: { user_id: userId, role: roleToSend },
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
    errorBox: document.getElementById('errorBox'),
  };

  function setError(message) {
    if (!els.errorBox) return;
    const msg = (message || '').toString().trim();
    els.errorBox.textContent = msg;
    els.errorBox.style.display = msg ? 'block' : 'none';
  }

  els.pwBtn.addEventListener('click', ()=>{
    const isText = els.password.type === 'text';
    els.password.type = isText ? 'password' : 'text';
    els.pwBtn.textContent = isText ? '👁️' : '🙈';
  });

  // ---------- Vet login (redirect after session OK) ----------
  els.vetForm.addEventListener('submit', async (e)=>{
    e.preventDefault();
    setError('');

    const emailVal = els.email.value.trim();
    const pwVal = els.password.value.trim();
    if (!emailVal || !pwVal) {
      setError('Email and password are required.');
      return;
    }

    els.loginBtn.disabled = true; els.loginBtn.textContent = 'Logging in...';
    try{
      const res = await axios.post(ROUTES.login, {
        login: emailVal,
        password: pwVal,
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
      const resolvedRole = loginDataParsed?.role || 'clinic_admin';
      const resolvedClinicId = loginDataParsed?.clinic_id
        ?? loginDataParsed?.vet_id
        ?? loginDataParsed?.vet_registerations_temp_id
        ?? loginDataParsed?.vet_registeration_id
        ?? loginDataParsed?.user?.clinic_id
        ?? null;
      const resolvedDoctorId = loginDataParsed?.doctor_id
        ?? loginDataParsed?.user?.doctor_id
        ?? (resolvedRole === 'doctor' ? computedUserId : null);

      const payload = {
        success: true,
        message: loginDataParsed?.message || 'Login success',
        role: resolvedRole,
        email:  loginDataParsed?.email ?? loginDataParsed?.user?.email ?? null,
        token:  loginDataParsed?.token,
        token_type: loginDataParsed?.token_type || 'Bearer',
        chat_room: loginDataParsed?.chat_room || null,
        user: loginDataParsed?.user || null,
        user_id: computedUserId,
        clinic_id: resolvedClinicId,
        doctor_id: resolvedDoctorId,
      };
      saveAuthFull(payload);

      const sessionSync = await syncSessionWithBackend(payload.user_id, resolvedRole);
      dump({ loginDataRaw, loginDataParsed, payload, sessionSync }, 'Vet Login + Session');

      if (sessionSync.ok) {
        const target =
          resolvedRole === 'receptionist'
            ? RECEPTIONIST_REDIRECT
            : POST_LOGIN_REDIRECT;
        setTimeout(()=> window.location.replace(target), 150);
      } else {
        setError('Login succeeded but session could not be established. Please retry.');
      }
    }catch(err){
      const data = err?.response?.data || { error:String(err) };
      dump({ error:'Vet login failed', detail:data }, 'Vet Login Error');
      const msg =
        data?.message ||
        data?.error ||
        (typeof data === 'string' ? data : null) ||
        'Unable to log in. Please check your credentials.';
      setError(msg);
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
