@php
    $appDownloadUrl = 'https://play.google.com/store/apps/details?id=com.petai.snoutiq';
    $downloadQrSrc  = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($appDownloadUrl);
    $appStoreUrl = 'https://apps.apple.com/';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="theme-color" content="#1d4ed8"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-status-bar-style" content="default"/>
  <meta name="mobile-web-app-capable" content="yes"/>
  <title>Clinic Admin Login | SnoutIQ</title>
  <link rel="icon" href="{{ asset('favicon.png') }}" sizes="32x32" type="image/png"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet"/>
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
      --font-display: "Fraunces", serif;
      --font-body: "Manrope", "Segoe UI", sans-serif;
      --text: #0f172a;
      --muted: #64748b;
      --border: #e2e8f0;
      --surface: #ffffff;
      --shadow: 0 28px 70px rgba(15, 23, 42, 0.12);
      --accent: #1d4ed8;
      --accent-deep: #0f2b65;
      --accent-soft: rgba(29, 78, 216, 0.12);
      --pill-bg: #e0ecff;
      --pill-text: #1d4ed8;
      --panel-1: #1d4ed8;
      --panel-2: #0f2b65;
      --glow: rgba(29, 78, 216, 0.26);
      --role-icon-bg: #e8efff;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: var(--font-body);
      color: var(--text);
      background: linear-gradient(90deg, #f8fafc 0 53%, var(--panel-1) 53%, var(--panel-2) 100%);
      transition: background 0.6s ease;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background:
        radial-gradient(circle at 18% 12%, rgba(255, 255, 255, 0.95), transparent 40%),
        radial-gradient(circle at 76% 24%, var(--glow), transparent 45%),
        radial-gradient(circle at 84% 78%, rgba(255, 255, 255, 0.12), transparent 50%);
      pointer-events: none;
      z-index: 0;
    }

    body[data-role="clinic_admin"] {
      --accent: #1d4ed8;
      --accent-deep: #0f2b65;
      --accent-soft: rgba(29, 78, 216, 0.14);
      --pill-bg: #e0ecff;
      --pill-text: #1d4ed8;
      --panel-1: #1f4fd9;
      --panel-2: #0f2b65;
      --glow: rgba(29, 78, 216, 0.28);
      --role-icon-bg: #e7efff;
    }

    body[data-role="doctor"] {
      --accent: #0f766e;
      --accent-deep: #0b4f4a;
      --accent-soft: rgba(15, 118, 110, 0.14);
      --pill-bg: #dcfdf3;
      --pill-text: #0f766e;
      --panel-1: #0f766e;
      --panel-2: #064e46;
      --glow: rgba(20, 184, 166, 0.26);
      --role-icon-bg: #e7f8f4;
    }

    body[data-role="receptionist"] {
      --accent: #b45309;
      --accent-deep: #7c2d12;
      --accent-soft: rgba(180, 83, 9, 0.14);
      --pill-bg: #ffedd5;
      --pill-text: #b45309;
      --panel-1: #b45309;
      --panel-2: #7c2d12;
      --glow: rgba(251, 146, 60, 0.28);
      --role-icon-bg: #fff4e6;
    }

    .shell {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: grid;
      grid-template-columns: minmax(0, 1.08fr) minmax(0, 0.92fr);
      gap: 48px;
      align-items: center;
      padding: 42px clamp(16px, 4vw, 72px);
    }

    .login-card {
      background: var(--surface);
      border-radius: 28px;
      padding: 32px 34px 28px;
      border: 1px solid rgba(148, 163, 184, 0.2);
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    .role-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--pill-bg);
      color: var(--pill-text);
      font-weight: 700;
      font-size: 12px;
      letter-spacing: 0.12em;
      padding: 6px 16px;
      border-radius: 999px;
      text-transform: uppercase;
    }

    .brand-row {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-top: 18px;
    }

    .brand-icon {
      width: 54px;
      height: 54px;
      border-radius: 16px;
      background: var(--role-icon-bg);
      display: grid;
      place-items: center;
      font-size: 22px;
      color: var(--accent);
      transition: transform 0.3s ease;
    }

    .brand-kicker {
      text-transform: uppercase;
      letter-spacing: 0.22em;
      font-size: 11px;
      color: var(--muted);
      margin-bottom: 6px;
      font-weight: 600;
    }

    .role-title {
      font-family: var(--font-display);
      font-size: 32px;
      margin: 0;
      color: #111827;
    }

    .role-sub {
      margin: 14px 0 18px;
      color: var(--muted);
      line-height: 1.6;
      font-size: 15px;
    }

    .role-select-label {
      font-size: 12px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #64748b;
      font-weight: 700;
      margin-bottom: 12px;
    }

    .role-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin-bottom: 18px;
    }

    .role-btn {
      border: 1px solid var(--border);
      background: #f8fafc;
      border-radius: 14px;
      padding: 12px 14px;
      display: flex;
      gap: 10px;
      align-items: center;
      cursor: pointer;
      text-align: left;
      transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
      font-family: var(--font-body);
    }

    .role-btn:focus-visible {
      outline: 2px solid var(--accent);
      outline-offset: 2px;
    }

    .role-btn-icon {
      width: 30px;
      height: 30px;
      border-radius: 10px;
      display: grid;
      place-items: center;
      background: #ffffff;
      color: #94a3b8;
      box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.2);
    }

    .role-btn.is-active {
      border-color: var(--accent);
      background: var(--accent-soft);
      box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
      transform: translateY(-1px);
    }

    .role-btn.is-active .role-btn-icon {
      color: var(--accent);
    }

    .role-btn-title {
      font-weight: 700;
      font-size: 14px;
      color: #0f172a;
    }

    .role-btn-desc {
      display: block;
      font-size: 12px;
      color: var(--muted);
      margin-top: 2px;
    }

    .alert {
      margin: 0 0 16px;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid #fecaca;
      background: #fee2e2;
      color: #b91c1c;
      font-weight: 600;
      display: none;
    }

    .row {
      margin-bottom: 14px;
    }

    label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: #1f2937;
      margin: 0 0 6px;
    }

    .input {
      width: 100%;
      padding: 13px 14px;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      font-size: 14px;
      font-family: var(--font-body);
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-soft);
      outline: none;
    }

    .input-wrap {
      position: relative;
    }

    .pw-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      border: 1px solid rgba(148, 163, 184, 0.4);
      background: #f8fafc;
      border-radius: 999px;
      padding: 4px 10px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.08em;
      color: #475569;
      cursor: pointer;
      transition: border-color 0.2s ease, color 0.2s ease;
    }

    .pw-toggle:hover {
      border-color: var(--accent);
      color: var(--accent);
    }

    .btn {
      width: 100%;
      padding: 14px 18px;
      border-radius: 12px;
      border: none;
      font-size: 14px;
      font-weight: 700;
      letter-spacing: 0.02em;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      font-family: var(--font-body);
    }

    .btn-primary {
      background: linear-gradient(180deg, var(--accent) 0%, var(--accent-deep) 100%);
      color: #ffffff;
      box-shadow: 0 16px 32px rgba(15, 23, 42, 0.18);
      margin-bottom: 10px;
    }

    .btn-primary:hover {
      transform: translateY(-1px);
    }

    .btn-ghost {
      background: #ffffff;
      color: var(--accent);
      border: 1px solid #e2e8f0;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .role-note {
      margin-top: 14px;
      color: var(--muted);
      text-align: center;
      font-size: 13px;
    }

    .promo-panel {
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .promo-card {
      max-width: 420px;
      width: 100%;
      color: #f8fafc;
      background: linear-gradient(145deg, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0.08));
      border-radius: 26px;
      padding: 28px;
      border: 1px solid rgba(255, 255, 255, 0.25);
      box-shadow: 0 24px 50px rgba(15, 23, 42, 0.25);
    }

    .promo-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 14px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      background: rgba(255, 255, 255, 0.18);
    }

    .promo-title {
      font-family: var(--font-display);
      font-size: 26px;
      line-height: 1.25;
      margin: 14px 0 10px;
    }

    .promo-desc {
      color: rgba(248, 250, 252, 0.88);
      font-size: 14px;
      line-height: 1.6;
      margin-bottom: 18px;
    }

    .qr-frame {
      background: #ffffff;
      border-radius: 20px;
      padding: 14px;
      width: fit-content;
      margin: 0 auto 18px;
      box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
    }

    .qr-frame img {
      display: block;
      width: 180px;
      height: 180px;
      border-radius: 14px;
    }

    .store-row {
      display: flex;
      gap: 10px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .store-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      border-radius: 999px;
      font-weight: 700;
      font-size: 13px;
      text-decoration: none;
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.3);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .store-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.2);
    }

    .store-play {
      background: #0b1220;
      border-color: #0b1220;
    }

    .store-apple {
      background: rgba(255, 255, 255, 0.18);
    }

    .debug {
      display: none !important;
    }

    body.role-switch .login-card,
    body.role-switch .promo-card {
      animation: roleSwap 420ms ease;
    }

    body.role-switch .role-title,
    body.role-switch .promo-title {
      animation: textSwap 420ms ease;
    }

    @keyframes roleSwap {
      0% { transform: translateY(0) scale(1); opacity: 1; }
      45% { transform: translateY(8px) scale(0.985); opacity: 0.85; }
      100% { transform: translateY(0) scale(1); opacity: 1; }
    }

    @keyframes textSwap {
      0% { opacity: 0.65; transform: translateY(4px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 980px) {
      body {
        background: linear-gradient(180deg, #f8fafc 0 56%, var(--panel-1) 56%, var(--panel-2) 100%);
      }

      .shell {
        grid-template-columns: 1fr;
        padding: 28px 16px 40px;
        gap: 28px;
      }

      .promo-panel {
        order: 2;
      }

      .login-card {
        padding: 28px 24px;
      }

      .role-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .promo-card {
        margin-bottom: 10px;
      }
    }

    @media (max-width: 640px) {
      .role-grid {
        grid-template-columns: 1fr;
      }

      .role-title {
        font-size: 26px;
      }

      .promo-title {
        font-size: 22px;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      *,
      *::before,
      *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>
<body data-role="clinic_admin">
<div class="shell">
  <section class="login-panel">
    <main class="login-card" data-role-card>
      <span class="role-pill" id="roleBadge">Clinic Admin</span>

      <div class="brand-row">
        <div class="brand-icon" id="roleIcon">
          <i class="fa-solid fa-hospital"></i>
        </div>
        <div>
          <div class="brand-kicker" id="roleKicker">SnoutIQ Clinic Control</div>
          <h1 class="role-title" id="roleTitle">Clinic Admin Login</h1>
        </div>
      </div>

      <p class="role-sub" id="roleDesc">Manage staff, services, and clinic operations with confidence.</p>

      <div class="role-select">
        <div class="role-select-label">Choose your role</div>
        <div class="role-grid">
          <button class="role-btn is-active" type="button" data-role="clinic_admin" aria-pressed="true">
            <span class="role-btn-icon"><i class="fa-solid fa-hospital"></i></span>
            <span>
              <span class="role-btn-title">Clinic Admin</span>
              <span class="role-btn-desc">Clinic operations</span>
            </span>
          </button>
          <button class="role-btn" type="button" data-role="doctor" aria-pressed="false">
            <span class="role-btn-icon"><i class="fa-solid fa-user-doctor"></i></span>
            <span>
              <span class="role-btn-title">Doctor</span>
              <span class="role-btn-desc">Patient care</span>
            </span>
          </button>
          <button class="role-btn" type="button" data-role="receptionist" aria-pressed="false">
            <span class="role-btn-icon"><i class="fa-solid fa-headset"></i></span>
            <span>
              <span class="role-btn-title">Receptionist</span>
              <span class="role-btn-desc">Front desk</span>
            </span>
          </button>
        </div>
      </div>

      <div id="errorBox" class="alert" role="alert" aria-live="polite"></div>

      <form id="loginForm" novalidate>
        <div class="row">
          <label for="email">Email Address</label>
          <input id="email" type="email" class="input" placeholder="Enter your email address" autocomplete="email"/>
        </div>

        <div class="row">
          <label for="password">Password</label>
          <div class="input-wrap">
            <input id="password" type="password" class="input" placeholder="Enter your password" autocomplete="current-password"/>
            <button id="pwBtn" class="pw-toggle" type="button" aria-label="Show password">SHOW</button>
          </div>
        </div>

        <button id="loginBtn" class="btn btn-primary" type="submit">Login as Clinic Admin</button>
      </form>

      <p class="role-note" id="roleNote">Only authorized clinic staff can access this dashboard. Reach out if you need help with your credentials.</p>

      <div class="debug">
        <h3>Debug dump (user + session)</h3>
        <pre id="dump">Waiting for login...</pre>
      </div>
    </main>
  </section>

  <aside class="promo-panel">
    <div class="promo-card" data-role-card>
      <span class="promo-pill" id="promoBadge">Clinic Companion App</span>
      <h2 class="promo-title" id="promoTitle">Scan the QR to download the app</h2>
      <p class="promo-desc" id="promoDesc">Get instant access to bookings, patient updates, and clinic alerts.</p>
      <div class="qr-frame">
        <img src="{{ $downloadQrSrc }}" alt="Download app QR code"/>
      </div>
      <div class="store-row">
        <a class="store-btn store-play" href="{{ $appDownloadUrl }}" target="_blank" rel="noopener">
          <i class="fa-brands fa-google-play"></i>
          Google Play
        </a>
        <a class="store-btn store-apple" href="{{ $appStoreUrl }}" target="_blank" rel="noopener">
          <i class="fa-brands fa-apple"></i>
          App Store
        </a>
      </div>
    </div>
  </aside>
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

  // Redirect target per environment (vet onboarding)
  const POST_LOGIN_REDIRECT = IS_LOCAL
    ? `${ORIGIN}/dashboard/services?open=create&onboarding=1&step=1`
    : `${ORIGIN}/backend/dashboard/services?open=create&onboarding=1&step=1`;
  const RECEPTIONIST_REDIRECT = `${WEB_BASE}/receptionist/front-desk`;

  const ROUTES = {
    login:        `${API_BASE}/auth/role-login`,
    sessionLogin: IS_LOCAL ? 'http://127.0.0.1:8000/api/session/login' : `${ORIGIN}/backend/api/session/login`,
  };

  axios.defaults.withCredentials = true;

  const ROLE_CONTENT = {
    clinic_admin: {
      badge: 'Clinic Admin',
      kicker: 'SnoutIQ Clinic Control',
      title: 'Clinic Admin Login',
      description: 'Manage staff, services, and clinic operations with confidence.',
      note: 'Only authorized clinic staff can access this dashboard. Reach out if you need help with your credentials.',
      cta: 'Login as Clinic Admin',
      promoBadge: 'Clinic Companion App',
      promoTitle: 'Scan the QR to download the app',
      promoDesc: 'Get instant access to bookings, patient updates, and clinic alerts.',
      icon: 'fa-hospital',
      themeColor: '#1d4ed8',
    },
    doctor: {
      badge: 'Doctor Console',
      kicker: 'SnoutIQ Doctor Hub',
      title: 'Doctor Login',
      description: 'Access your schedule, patient queue, and clinical notes in one place.',
      note: 'Doctors only. Contact your clinic admin if you need access.',
      cta: 'Login as Doctor',
      promoBadge: 'Doctor Mobile Suite',
      promoTitle: 'Scan the QR for the clinician app',
      promoDesc: 'Review cases, update prescriptions, and follow up with patients on the go.',
      icon: 'fa-user-doctor',
      themeColor: '#0f766e',
    },
    receptionist: {
      badge: 'Reception Desk',
      kicker: 'SnoutIQ Front Desk',
      title: 'Receptionist Login',
      description: 'Handle walk-ins, bookings, and intake from a single screen.',
      note: 'Front-desk access only. Ask your clinic admin if you need help.',
      cta: 'Login as Receptionist',
      promoBadge: 'Front Desk Companion',
      promoTitle: 'Scan the QR to manage check-ins',
      promoDesc: 'Confirm appointments, manage queues, and send updates in seconds.',
      icon: 'fa-headset',
      themeColor: '#b45309',
    },
  };

  const els = {
    loginForm: document.getElementById('loginForm'),
    email: document.getElementById('email'),
    password: document.getElementById('password'),
    pwBtn: document.getElementById('pwBtn'),
    loginBtn: document.getElementById('loginBtn'),
    dump: document.getElementById('dump'),
    errorBox: document.getElementById('errorBox'),
    roleBadge: document.getElementById('roleBadge'),
    roleKicker: document.getElementById('roleKicker'),
    roleTitle: document.getElementById('roleTitle'),
    roleDesc: document.getElementById('roleDesc'),
    roleNote: document.getElementById('roleNote'),
    promoBadge: document.getElementById('promoBadge'),
    promoTitle: document.getElementById('promoTitle'),
    promoDesc: document.getElementById('promoDesc'),
    roleIcon: document.getElementById('roleIcon'),
    roleButtons: Array.from(document.querySelectorAll('.role-btn')),
  };

  const themeMeta = document.querySelector('meta[name="theme-color"]');
  let currentRole = 'clinic_admin';
  let roleSwitchTimer;

  function normalizeRole(role){
    const clean = (role || '').toString().trim().toLowerCase().replace(/\s|-/g, '_');
    if (clean === 'clinicadmin') return 'clinic_admin';
    if (clean === 'reseptionist') return 'receptionist';
    return clean;
  }

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
      if (els.dump) els.dump.textContent = pretty;
    }catch(e){
      if (els.dump) els.dump.textContent = String(obj);
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

  function setError(message) {
    if (!els.errorBox) return;
    const msg = (message || '').toString().trim();
    els.errorBox.textContent = msg;
    els.errorBox.style.display = msg ? 'block' : 'none';
  }

  function triggerRoleAnimation() {
    document.body.classList.remove('role-switch');
    void document.body.offsetWidth;
    document.body.classList.add('role-switch');
    clearTimeout(roleSwitchTimer);
    roleSwitchTimer = setTimeout(() => {
      document.body.classList.remove('role-switch');
    }, 460);
  }

  function applyRole(role, animate = true) {
    const normalized = normalizeRole(role);
    const data = ROLE_CONTENT[normalized] || ROLE_CONTENT.clinic_admin;
    currentRole = normalized in ROLE_CONTENT ? normalized : 'clinic_admin';

    document.body.dataset.role = currentRole;
    if (themeMeta) themeMeta.setAttribute('content', data.themeColor);
    if (els.roleBadge) els.roleBadge.textContent = data.badge;
    if (els.roleKicker) els.roleKicker.textContent = data.kicker;
    if (els.roleTitle) els.roleTitle.textContent = data.title;
    if (els.roleDesc) els.roleDesc.textContent = data.description;
    if (els.roleNote) els.roleNote.textContent = data.note;
    if (els.promoBadge) els.promoBadge.textContent = data.promoBadge;
    if (els.promoTitle) els.promoTitle.textContent = data.promoTitle;
    if (els.promoDesc) els.promoDesc.textContent = data.promoDesc;
    if (els.loginBtn) {
      els.loginBtn.textContent = data.cta;
      els.loginBtn.setAttribute('aria-label', data.cta);
    }
    if (els.roleIcon) {
      els.roleIcon.innerHTML = `<i class="fa-solid ${data.icon}"></i>`;
    }

    els.roleButtons.forEach((btn) => {
      const isActive = btn.dataset.role === currentRole;
      btn.classList.toggle('is-active', isActive);
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });

    document.title = `${data.title} | SnoutIQ`;
    setError('');

    if (animate) {
      triggerRoleAnimation();
    }
  }

  els.roleButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const nextRole = btn.dataset.role;
      if (nextRole && nextRole !== currentRole) {
        applyRole(nextRole, true);
      }
    });
  });

  const requestedRole = new URLSearchParams(window.location.search).get('role');
  applyRole(requestedRole || 'clinic_admin', false);

  els.pwBtn.addEventListener('click', ()=>{
    const isText = els.password.type === 'text';
    els.password.type = isText ? 'password' : 'text';
    els.pwBtn.textContent = isText ? 'SHOW' : 'HIDE';
    els.pwBtn.setAttribute('aria-label', isText ? 'Show password' : 'Hide password');
  });

  // ---------- Login (redirect after session OK) ----------
  els.loginForm.addEventListener('submit', async (e)=>{
    e.preventDefault();
    setError('');

    const emailVal = els.email.value.trim();
    const pwVal = els.password.value.trim();
    if (!emailVal || !pwVal) {
      setError('Email and password are required.');
      return;
    }

    const roleToSend = currentRole;

    els.loginBtn.disabled = true;
    els.loginBtn.textContent = 'Logging in...';
    try{
      const res = await axios.post(ROUTES.login, {
        login: emailVal,
        password: pwVal,
        role: roleToSend,
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
      const resolvedRole = loginDataParsed?.role || roleToSend;
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
      els.loginBtn.disabled = false;
      els.loginBtn.textContent = ROLE_CONTENT[currentRole]?.cta || 'Login';
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
