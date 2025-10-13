<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login | Test Clinic</title>
  <link rel="icon" href="https://snoutiq.com/favicon.webp" sizes="32x32" type="image/png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <style>
    :root{--bg1:#eef4ff;--bg2:#e9eefe;--card:#ffffff;--text:#0f172a;--muted:#64748b;--blue:#2563eb;--blue-d:#1e40af;--border:#e5e7eb;--ring:rgba(37,99,235,.25)}
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Ubuntu,sans-serif;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:var(--text)}
    .wrap{min-height:100dvh;display:grid;place-items:center;padding:32px}
    .card{width:100%;max-width:480px;background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.08);padding:24px 22px}
    .logo{height:22px;margin:0 auto 14px;display:block}
    h1{font-size:24px;margin:0 0 6px;text-align:center}
    .sub{color:var(--muted);text-align:center;margin:0 0 16px}
    .seg{display:flex;background:#f3f4f6;border-radius:10px;padding:4px;margin:8px 0 20px;border:1px solid var(--border)}
    .seg button{flex:1;padding:10px;border:0;background:transparent;border-radius:8px;cursor:pointer;font-weight:600;color:#475569}
    .seg button.active{background:#fff;color:var(--blue);box-shadow:0 1px 0 rgba(0,0,0,.02)}
    label{display:block;font-size:13px;font-weight:600;color:#334155;margin:0 0 6px}
    .input{width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;background:#fff;outline:none}
    .input:focus{border-color:transparent;box-shadow:0 0 0 3px var(--ring)}
    .row{margin-bottom:14px}
    .btn{width:100%;padding:12px 14px;border-radius:10px;border:0;font-weight:700;cursor:pointer}
    .btn-primary{background:var(--blue);color:#fff}
    .btn-primary:hover{background:var(--blue-d)}
    .google-box{border:1px solid var(--border);border-radius:12px;padding:16px;box-shadow:0 6px 20px rgba(0,0,0,.06)}
    .pw-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:transparent;border:0;cursor:pointer}
    .debug{margin-top:18px;border-top:1px solid var(--border);padding-top:14px}
    .debug h3{margin:0 0 8px;font-size:14px}
    .debug pre{max-height:360px;overflow:auto;background:#0b1020;color:#d1e7ff;padding:12px;border-radius:10px;font-size:12px;line-height:1.45}
  </style>
</head>
<body>
<div class="wrap">
  <main class="card">
    <img class="logo" src="https://snoutiq.com/favicon.webp" alt="SnoutIQ"/>
    <h1>Welcome Back!</h1>
    <p class="sub">Sign in to continue to your Test Clinic account</p>

    <div class="seg" role="tablist" aria-label="Login role">
      <button id="tab-pet" class="active" type="button" aria-selected="true">Pet Owner</button>
      <button id="tab-vet" type="button" aria-selected="false">Veterinarian</button>
    </div>

    <!-- Vet form -->
    <form id="vetForm" style="display:none;">
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

    <!-- Pet Google login -->
    <div id="petBox" class="google-box">
      <div style="display:grid;place-items:center;min-height:42px">
        <div id="googleBtn"></div>
      </div>
    </div>

    <!-- On-page dump -->
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
  // -------- Smart bases: local vs production --------
  const ORIGIN   = window.location.origin; // http://127.0.0.1:8000 or https://snoutiq.com
  const IS_LOCAL = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const API_BASE = IS_LOCAL ? `${ORIGIN}/api`        : `${ORIGIN}/backend/api`;
  const WEB_BASE = IS_LOCAL ? `${ORIGIN}`           : `${ORIGIN}/backend`;

  // EXACT local session endpoint; prod uses /backend/api/session/login
  function getSessionLoginBase(){
    if (IS_LOCAL) return 'http://127.0.0.1:8000/api/session/login';
    return `${ORIGIN}/backend/api/session/login`;
  }

  // 👇 Redirect target per environment
  const POST_LOGIN_REDIRECT = IS_LOCAL
    ? `${ORIGIN}/dashboard/services`
    : `${ORIGIN}/backend/doctor`;

  const POST_LOGIN_REDIRECT_PET = IS_LOCAL
    ? `${ORIGIN}/user/bookings`
    : `${ORIGIN}/backend/user/bookings`;

  const ROUTES = {
    login:            `${API_BASE}/auth/login`,
    googleLogin:      `${API_BASE}/google-login`,
    sessionLogin:     getSessionLoginBase(),
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
  function extractUserId(obj, isVet) {
    const cands = isVet
      ? [obj?.user?.id, obj?.user_id, obj?.vet_registerations_temp_id, obj?.vet_registration_temp_id, obj?.vet_registeration_id, obj?.vet_id]
      : [obj?.user?.id, obj?.user_id];
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
      console.log(`[debug] ${title}:`, obj);
      document.getElementById('dump').textContent = pretty;
    }catch(e){
      console.warn('dump failed', e);
      document.getElementById('dump').textContent = String(obj);
    }
  }
  function saveAuthFull(obj){
    try{
      const json = JSON.stringify(obj);
      sessionStorage.setItem('auth_full', json);
      localStorage.setItem('auth_full', json);
      console.log('[login] auth_full saved:', obj);
    }catch(e){ console.warn('auth_full save failed', e); }
  }
  async function syncSessionWithBackend(userId, role){
    if(!userId){
      console.warn('[session] No user_id provided to session sync');
      return { ok:false, error:'missing user_id' };
    }
    try{
      const { data } = await axios.get(ROUTES.sessionLogin, {
        params: { user_id: userId, role: role || undefined },
        withCredentials: true
      });
      console.log('[session] requested_user_id:', userId);
      console.log('[session] server.session_user_id:', data?.session_user_id ?? null);
      console.log('[session] full response:', data);
      return { ok:true, response:data };
    }catch(err){
      const payload = err?.response?.data || { message: String(err) };
      console.warn('Failed to sync session with backend', payload);
      return { ok:false, error:payload };
    }
  }

  // ---------- UI ----------
  let userType = 'vet'; // start on vet while testing; change if needed
  const els = {
    tabPet: document.getElementById('tab-pet'),
    tabVet: document.getElementById('tab-vet'),
    petBox: document.getElementById('petBox'),
    vetForm: document.getElementById('vetForm'),
    email: document.getElementById('email'),
    password: document.getElementById('password'),
    pwBtn: document.getElementById('pwBtn'),
    loginBtn: document.getElementById('loginBtn'),
    googleBtn: document.getElementById('googleBtn'),
    dump: document.getElementById('dump'),
  };
  function setRole(type){
    userType = type;
    if(type==='vet'){
      els.tabVet?.classList.add('active'); els.tabPet?.classList.remove('active');
      els.vetForm.style.display='block'; els.petBox.style.display='none';
    } else {
      els.tabPet?.classList.add('active'); els.tabVet?.classList.remove('active');
      els.vetForm.style.display='none'; els.petBox.style.display='block';
    }
  }
  els.tabPet?.addEventListener('click', ()=> setRole('pet'));
  els.tabVet?.addEventListener('click', ()=> setRole('vet'));
  setRole('vet');

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

      console.log('[auth][vet] parsed:', loginDataParsed);
      const computedUserId = extractUserId(loginDataParsed, true);
      console.log('[auth][vet] computedUserId:', computedUserId);

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

      const sessionSync = await syncSessionWithBackend(payload.user_id, payload.role);
      dump({ loginDataRaw, loginDataParsed, payload, sessionSync }, 'Vet Login + Session');

      if (sessionSync.ok) {
        console.log('[redirect] ->', POST_LOGIN_REDIRECT);
        // Small delay helps ensure cookie is set before nav
        setTimeout(()=> window.location.replace(POST_LOGIN_REDIRECT), 150);
      }
    }catch(err){
      const data = err?.response?.data || { error:String(err) };
      console.error('Vet login failed:', data);
      dump({ error:'Vet login failed', detail:data }, 'Vet Login Error');
    }finally{
      els.loginBtn.disabled = false; els.loginBtn.textContent = 'Login';
    }
  });

  // ---------- (Optional) Pet Google: keep no-redirect while testing ----------
  window.onGoogleCredential = async (response)=>{
    try{
      const base64Url = response.credential.split(".")[1];
      const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
      const jsonPayload = decodeURIComponent(atob(base64).split("").map(
        c=>"%"+("00"+c.charCodeAt(0).toString(16)).slice(-2)
      ).join(""));
      const googleData = JSON.parse(jsonPayload);
      const email = googleData.email || '';
      const uniqueUserId = googleData.sub;

      const res = await axios.post(ROUTES.googleLogin,{
        email,
        google_token: uniqueUserId,
        role: 'pet'
      }, { withCredentials: true });

      const loginDataRaw    = res?.data;
      const loginDataParsed = parseDeepJSON(loginDataRaw);
      const computedUserId  = extractUserId(loginDataParsed, false);

      const payload = {
        success: true,
        message: loginDataParsed?.message || 'Login success',
        role: 'pet',
        email: loginDataParsed?.user?.email ?? email ?? null,
        token: loginDataParsed?.token,
        token_type: loginDataParsed?.token_type || 'Bearer',
        chat_room: loginDataParsed?.chat_room || null,
        user: loginDataParsed?.user || null,
        user_id: computedUserId,
      };
      saveAuthFull(payload);

      const sessionSync = await syncSessionWithBackend(payload.user_id, payload.role);
      dump({ loginDataRaw, loginDataParsed, payload, sessionSync }, 'Pet Login + Session');

      // Redirect pet parent to their orders/bookings page
      if (sessionSync.ok) setTimeout(()=> window.location.replace(POST_LOGIN_REDIRECT_PET), 150);

      // If you want pet to redirect too, uncomment:
      // if (sessionSync.ok) setTimeout(()=> window.location.replace(POST_LOGIN_REDIRECT), 150);
    }catch(err){
      const data = err?.response?.data || { error:String(err) };
      console.error('Google login failed:', data);
      dump({ error:'Google login failed', detail:data }, 'Pet Login Error');
    }
  };

  // Google button render
  window.onload=()=>{
    if(window.google && window.google.accounts){
      window.google.accounts.id.initialize({
        client_id:"325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com",
        callback:onGoogleCredential
      });
      window.google.accounts.id.renderButton(
        document.getElementById('googleBtn'),
        { theme:"filled_blue", size:"large", text:"continue_with", shape:"rectangular" }
      );
      try{ window.google.accounts.id.prompt(); }catch(_){}
    }
  };
</script>

</body>
</html>


