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
    :root{
      --bg1:#eef4ff; --bg2:#e9eefe;
      --card:#ffffff; --text:#0f172a; --muted:#64748b;
      --blue:#2563eb; --blue-d:#1e40af; --border:#e5e7eb; --ring:rgba(37,99,235,.25);
      --err:#dc2626; --ok:#16a34a; --hint:#6b7280;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Ubuntu,sans-serif;color:var(--text);
      background:linear-gradient(135deg,var(--bg1),var(--bg2));}
    .wrap{min-height:100dvh;display:grid;place-items:center;padding:32px}
    .card{width:100%;max-width:420px;background:var(--card);border:1px solid var(--border);
      border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.08);padding:24px 22px}
    .logo{height:22px;margin:0 auto 14px;display:block}
    h1{font-size:24px;margin:0 0 6px;text-align:center}
    .sub{color:var(--muted);text-align:center;margin:0 0 16px}
    .seg{display:flex;background:#f3f4f6;border-radius:10px;padding:4px;margin:8px 0 20px;border:1px solid var(--border)}
    .seg button{flex:1;padding:10px;border:0;background:transparent;border-radius:8px;cursor:pointer;font-weight:600;color:#475569}
    .seg button.active{background:#fff;color:var(--blue);box-shadow:0 1px 0 rgba(0,0,0,.02)}
    label{display:block;font-size:13px;font-weight:600;color:#334155;margin:0 0 6px}
    .input{width:100%;padding:12px 14px;border:1px solid var(--border);border-radius:10px;background:#fff;
      outline:none}
    .input:focus{border-color:transparent;box-shadow:0 0 0 3px var(--ring)}
    .err{color:var(--err);font-size:12px;margin-top:6px}
    .row{margin-bottom:14px}
    .btn{width:100%;padding:12px 14px;border-radius:10px;border:0;font-weight:700;cursor:pointer}
    .btn-primary{background:var(--blue);color:#fff}
    .btn-primary:hover{background:var(--blue-d)}
    .muted{color:var(--muted)}
    .foot{margin-top:16px;padding-top:14px;border-top:1px solid var(--border);text-align:center}
    .link{color:var(--blue);text-decoration:none;font-weight:600}
    .right{float:right;font-size:12px}
    .toast{position:fixed;right:16px;bottom:16px;background:#111827;color:#fff;padding:.7rem .9rem;border-radius:.6rem;
      box-shadow:0 10px 24px rgba(0,0,0,.25);opacity:0;transform:translateY(8px);transition:.25s}
    .toast.show{opacity:1;transform:translateY(0)}
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

      <!-- Role switch -->
      <div class="seg" role="tablist" aria-label="Login role">
        <button id="tab-pet" class="active" type="button" aria-selected="true">Pet Owner</button>
        <button id="tab-vet" type="button" aria-selected="false">Veterinarian</button>
      </div>

      <!-- Vet form -->
      <form id="vetForm" style="display:none;">
        <div class="row">
          <label for="email">Email Address</label>
          <input id="email" type="email" class="input" placeholder="Enter your email address" autocomplete="email"/>
          <div id="emailErr" class="err" style="display:none"></div>
        </div>

        <div class="row" style="position:relative">
          <div>
            <label for="password">Password
              <a class="right link" href="/forgot-password">Forgot Password?</a>
            </label>
          </div>
          <input id="password" type="password" class="input" placeholder="Enter your password" autocomplete="current-password"/>
          <button id="pwBtn" class="pw-toggle" type="button" aria-label="Show password">👁️</button>
          <div id="passErr" class="err" style="display:none"></div>
        </div>

        <button id="loginBtn" class="btn btn-primary" type="submit">Login</button>
      </form>

      <!-- Pet Google login -->
      <div id="petBox" class="google-box">
        <div id="googleBtn" style="display:grid;place-items:center;min-height:42px"></div>
        <p id="googleMsg" class="err" style="display:none;margin:10px 0 0"></p>
      </div>

      <div class="foot">
        <p class="muted">Don't have an account?
          <a class="link" href="/register">Create an account</a>
        </p>
      </div>
    </main>
  </div>

  <div id="toast" class="toast"></div>

  <script>
    // --- tiny toast ---
    const toast = (msg, type="info")=>{
      const el=document.getElementById('toast');
      el.textContent=msg;
      el.style.background = type==="error" ? "#dc2626" : (type==="success" ? "#16a34a" : "#111827");
      el.classList.add('show');
      setTimeout(()=>el.classList.remove('show'), 2200);
    };

    // --- state ---
    let userType = "pet"; // default tab
    let isLoading = false;
    const ADMIN_EMAIL = "admin@gmail.com";
    const ADMIN_PASS  = "5f4dcc3b5d"; // as in your React override

    const els = {
      tabPet: document.getElementById('tab-pet'),
      tabVet: document.getElementById('tab-vet'),
      petBox: document.getElementById('petBox'),
      vetForm: document.getElementById('vetForm'),
      email: document.getElementById('email'),
      emailErr: document.getElementById('emailErr'),
      password: document.getElementById('password'),
      passErr: document.getElementById('passErr'),
      pwBtn: document.getElementById('pwBtn'),
      loginBtn: document.getElementById('loginBtn'),
      googleBtn: document.getElementById('googleBtn'),
      googleMsg: document.getElementById('googleMsg'),
    };

    // --- role switching (match React behavior: Pet → Google, Vet → form) ---
    function setRole(type){
      userType = type;
      if(type === 'vet'){
        els.tabVet.classList.add('active'); els.tabVet.setAttribute('aria-selected','true');
        els.tabPet.classList.remove('active'); els.tabPet.setAttribute('aria-selected','false');
        els.vetForm.style.display = 'block';
        els.petBox.style.display = 'none';
      }else{
        els.tabPet.classList.add('active'); els.tabPet.setAttribute('aria-selected','true');
        els.tabVet.classList.remove('active'); els.tabVet.setAttribute('aria-selected','false');
        els.vetForm.style.display = 'none';
        els.petBox.style.display = 'block';
      }
    }
    els.tabPet.onclick = ()=> setRole('pet');
    els.tabVet.onclick = ()=> setRole('vet');

    // --- show/hide password ---
    els.pwBtn.addEventListener('click', ()=>{
      const isText = els.password.type === 'text';
      els.password.type = isText ? 'password' : 'text';
      els.pwBtn.textContent = isText ? '👁️' : '🙈';
    });

    // --- validation like React ---
    function validate(){
      let ok = true;
      els.emailErr.style.display = 'none';
      els.passErr.style.display = 'none';

      if(userType === 'vet'){
        if(!els.email.value){
          els.emailErr.textContent = 'Email is required';
          els.emailErr.style.display='block';
          ok = false;
        }else if(!/\S+@\S+\.\S+/.test(els.email.value)){
          els.emailErr.textContent = 'Please enter a valid email address';
          els.emailErr.style.display='block';
          ok = false;
        }
        if(!els.password.value){
          els.passErr.textContent = 'Password is required';
          els.passErr.style.display='block';
          ok = false;
        }
      }
      return ok;
    }

    // --- storage helpers (replace React AuthContext.login) ---
    function saveSession(user, token, chatRoomToken){
      try{
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        if(chatRoomToken) localStorage.setItem('chatRoomToken', chatRoomToken);
      }catch(_){}
    }

    // --- Login submit (Vet only) — API unchanged ---
    els.vetForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      if(isLoading) return;
      if(!validate()) return;

      isLoading = true;
      els.loginBtn.disabled = true;
      els.loginBtn.textContent = 'Logging in...';
      try{
        const payload = {
          login: els.email.value,
          password: els.password.value,
          role: 'vet', // backend role for vet
        };
        const res = await axios.post('https://snoutiq.com/backend/api/auth/login', payload);

        const chatRoomToken = (res.data?.chat_room && res.data.chat_room.token) ? res.data.chat_room.token : null;
        let token = res.data?.token;
        let user  = res.data?.user;

        if(token && user){
          // super admin override (kept exactly as your React)
          if(els.email.value === ADMIN_EMAIL && els.password.value === ADMIN_PASS){
            user = { ...user, role: 'super_admin' };
          }
          saveSession(user, token, chatRoomToken);
          toast('Login successful!', 'success');

          // route like React
          if(user.role === 'vet' || user.role === 'super_admin'){
            location.href = '/user-dashboard/bookings';
          }else{
            location.href = '/dashboard';
            setTimeout(()=>toast(`Welcome ${user.role}, dashboard is only for vets.`), 300);
          }
        }else{
          toast('Invalid response from server.', 'error');
        }
      }catch(error){
        const msg = error?.response?.data?.message || error?.message || 'Login failed. Please check your credentials and try again.';
        toast(msg, 'error');
      }finally{
        isLoading = false;
        els.loginBtn.disabled = false;
        els.loginBtn.textContent = 'Login';
      }
    });

    // --- Google Sign-In (Pet) — API unchanged ---
    async function onGoogleCredential(response){
      try{
        const base64Url = response.credential.split(".")[1];
        const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
        const jsonPayload = decodeURIComponent(atob(base64).split("").map(c=>"%"+("00"+c.charCodeAt(0).toString(16)).slice(-2)).join(""));
        const googleData = JSON.parse(jsonPayload);

        const uniqueUserId = googleData.sub;
        const email = googleData.email || "";

        const res = await axios.post('https://snoutiq.com/backend/api/google-login', {
          email,
          google_token: uniqueUserId,
          role: 'pet',
        });

        const chatRoomToken = (res.data?.chat_room && res.data.chat_room.token) ? res.data.chat_room.token : null;
        const token = res.data?.token;
        const user  = res.data?.user;

        if(token && user){
          saveSession(user, token, chatRoomToken);
          toast('Login successful!', 'success');
          location.href = '/dashboard';
        }else{
          toast('Invalid response from server.', 'error');
        }
      }catch(error){
        const msg = error?.response?.data?.message || 'Google login failed.';
        const el = els.googleMsg; el.textContent = msg; el.style.display='block';
        toast(msg, 'error');
      }
    }

    window.onload = ()=>{
      // default: Pet Owner tab with Google
      setRole('pet');

      if(window.google && window.google.accounts){
        window.google.accounts.id.initialize({
          client_id: "325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com",
          callback: onGoogleCredential
        });
        window.google.accounts.id.renderButton(els.googleBtn, {
          theme: "filled_blue", size: "large", text: "continue_with", shape: "rectangular"
        });
        // One-tap (optional, mirrors React useOneTap)
        try{ window.google.accounts.id.prompt(); }catch(_){}
      }
    };
  </script>
</body>
</html>
