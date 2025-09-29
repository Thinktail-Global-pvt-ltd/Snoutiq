<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login | SnoutIQ</title>
  <link rel="icon" href="https://snoutiq.com/favicon.webp" sizes="32x32" type="image/png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <style>
    :root{--bg1:#eef4ff;--bg2:#e9eefe;--card:#ffffff;--text:#0f172a;--muted:#64748b;--blue:#2563eb;--blue-d:#1e40af;--border:#e5e7eb;--ring:rgba(37,99,235,.25)}
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Ubuntu,sans-serif;background:linear-gradient(135deg,var(--bg1),var(--bg2));color:var(--text)}
    .wrap{min-height:100dvh;display:grid;place-items:center;padding:32px}
    .card{width:100%;max-width:420px;background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.08);padding:24px 22px}
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

    <div class="foot" style="margin-top:16px;padding-top:14px;border-top:1px solid #e5e7eb;text-align:center">
      <p style="color:#64748b">Don't have an account?
        <a class="link" style="color:#2563eb;text-decoration:none;font-weight:600" href="/register">Create an account</a>
      </p>
    </div>
  </main>
</div>

<script>
  // ---------------- Role tabs ----------------
  let userType = 'pet';
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
  };
  function setRole(type){
    userType = type;
    if(type==='vet'){ els.tabVet.classList.add('active'); els.tabPet.classList.remove('active'); els.vetForm.style.display='block'; els.petBox.style.display='none'; }
    else{ els.tabPet.classList.add('active'); els.tabVet.classList.remove('active'); els.vetForm.style.display='none'; els.petBox.style.display='block'; }
  }
  els.tabPet.onclick = ()=> setRole('pet');
  els.tabVet.onclick = ()=> setRole('vet');
  setRole('pet');

  // ---------------- show/hide password ----------------
  els.pwBtn.addEventListener('click', ()=>{
    const isText = els.password.type === 'text';
    els.password.type = isText ? 'password' : 'text';
    els.pwBtn.textContent = isText ? '👁️' : '🙈';
  });

  // ---------------- helpers ----------------
  function saveAuthFull(obj){
    try{
      const json = JSON.stringify(obj);
      sessionStorage.setItem('auth_full', json);
      localStorage.setItem('auth_full', json); // optional
      console.log('[login] auth_full saved:', obj);
    }catch(e){ console.warn('auth_full save failed', e); }
  }

  // ---------------- Vet email/password login ----------------
  els.vetForm.addEventListener('submit', async (e)=>{
    e.preventDefault();
    els.loginBtn.disabled = true; els.loginBtn.textContent = 'Logging in...';
    try{
      const res = await axios.post('https://snoutiq.com/backend/api/auth/login', {
        login: els.email.value,
        password: els.password.value,
        role: 'vet',
      });

      const payload = {
        success: true,
        message: res.data?.message || 'Login success',
        role: 'vet',
        email: res.data?.user?.email,
        token: res.data?.token,
        token_type: res.data?.token_type || 'Bearer',
        chat_room: res.data?.chat_room || null,
        user: res.data?.user || null,
        user_id: res.data?.user?.id || null,
      };
      saveAuthFull(payload);

      const docId = payload.user_id;
      const prefix = '/backend';
      if (docId) {
        window.location.href = `${prefix}/doctor?doctorId=${encodeURIComponent(docId)}`;
      } else {
        window.location.href = `${prefix}/doctor`;
      }
    }catch(err){
      console.error('Vet login failed:', err?.response?.data || err);
    }finally{
      els.loginBtn.disabled = false; els.loginBtn.textContent = 'Login';
    }
  });

  // ---------------- Pet Google login ----------------
  window.onGoogleCredential = async (response)=>{
    try{
      const base64Url = response.credential.split(".")[1];
      const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
      const jsonPayload = decodeURIComponent(atob(base64).split("").map(c=>"%"+("00"+c.charCodeAt(0).toString(16)).slice(-2)).join(""));
      const googleData = JSON.parse(jsonPayload);
      const email = googleData.email || '';
      const uniqueUserId = googleData.sub;

      const res = await axios.post('https://snoutiq.com/backend/api/google-login',{
        email,
        google_token: uniqueUserId,
        role: 'pet'
      });

      const payload = {
        success: true,
        message: res.data?.message || 'Login success',
        role: 'pet',
        email: res.data?.user?.email,
        token: res.data?.token,
        token_type: res.data?.token_type || 'Bearer',
        chat_room: res.data?.chat_room || null,
        user: res.data?.user || null,
        user_id: res.data?.user?.id || null,
      };
      saveAuthFull(payload);
      window.location.href = '/backend/pet-dashboard';
    }catch(err){
      console.error('Google login failed:', err?.response?.data || err);
    }
  };

  window.onload=()=>{
    if(window.google && window.google.accounts){
      window.google.accounts.id.initialize({
        client_id:"325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com",
        callback:onGoogleCredential
      });
      window.google.accounts.id.renderButton(els.googleBtn,{ theme:"filled_blue", size:"large", text:"continue_with", shape:"rectangular" });
      try{ window.google.accounts.id.prompt(); }catch(_){}
    }
  };
</script>
</body>
</html>
