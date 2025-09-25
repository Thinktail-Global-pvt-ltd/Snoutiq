<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register | SnoutIQ</title>
  <link rel="icon" href="https://snoutiq.com/favicon.webp" sizes="32x32" type="image/png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

  <!-- Google Identity Services (for the blue Google button) -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>

  <style>
    :root{
      --bg1:#eef4ff; --bg2:#fbfdff; --card:#ffffff; --border:#e6ebf5;
      --text:#0f172a; --muted:#64748b; --blue:#2563eb; --blue-600:#2563eb;
      --ring: rgba(37,99,235,.25); --green:#10b981; --amber:#f59e0b;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Ubuntu,sans-serif;color:var(--text);
      background:linear-gradient(135deg,var(--bg1),var(--bg2)) fixed;}
    a{text-decoration:none;color:var(--blue)}
    img{display:block;max-width:100%}

    /* Topbar */
    .topbar{position:sticky;top:0;z-index:40;background:rgba(255,255,255,.82);
      backdrop-filter:blur(8px);border-bottom:1px solid var(--border)}
    .nav{max-width:1120px;margin:0 auto;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 12px}
    .logo{display:flex;align-items:center;gap:.5rem;font-weight:800;color:var(--blue);cursor:pointer}
    .logo img{height:20px}
    .nav-cta{display:flex;gap:.5rem}
    .btn{border:1px solid var(--border);background:#fff;color:#111827;padding:.55rem .9rem;border-radius:12px;font-weight:700;cursor:pointer}
    .btn.primary{background:var(--blue);border-color:var(--blue);color:#fff}

    /* Page wrap */
    .wrap{min-height:calc(100dvh - 64px);display:grid;place-items:center;padding:24px}

    /* Card */
    .card{width:100%;max-width:520px;background:var(--card);border:1px solid var(--border);
      border-radius:18px;box-shadow:0 24px 80px -28px rgba(37,99,235,.28);padding:24px 24px 18px}
    .brand{height:24px;margin:6px auto 10px}
    h1{margin:.2rem 0 0;text-align:center;font-size:1.8rem}
    .sub{margin:.35rem 0 16px;text-align:center;color:var(--muted)}

    /* Tabs */
    .tabs{display:flex;gap:0;background:#f3f4f6;border:1px solid var(--border);border-radius:12px;padding:4px;margin:10px 0 16px}
    .tab{flex:1;padding:.6rem .9rem;border:0;background:transparent;border-radius:10px;font-weight:700;cursor:pointer;color:#334155}
    .tab.active{background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.08);color:var(--blue)}

    /* Location badge */
    .loc{display:flex;align-items:center;gap:.5rem;justify-content:center;border:1px solid #dbeafe;
      background:#eff6ff;border-radius:10px;padding:.65rem;font-weight:600;color:#1e3a8a}
    .loc[data-status="granted"]{border-color:#bbf7d0;background:#ecfdf5;color:#065f46}
    .loc[data-status="denied"]{border-color:#fde68a;background:#fffbeb;color:#92400e}

    /* Google row / email form */
    .panel{margin:14px 0 6px}
    .center{display:flex;justify-content:center}
    .divider{height:1px;background:#eef2f7;margin:16px 0}

    /* Simple vet email CTA (you can replace with your full form) */
    .email-cta{display:flex;gap:8px;flex-direction:column}
    .email-cta input{width:100%;padding:.85rem 1rem;border:1px solid var(--border);border-radius:12px}
    .email-cta .cta{padding:.85rem 1rem;border:none;border-radius:12px;background:var(--blue-600);color:#fff;font-weight:700;cursor:pointer}
    .hint{font-size:.85rem;color:var(--muted);text-align:center}

    /* Footer link */
    .foot{margin-top:12px;padding-top:12px;border-top:1px solid var(--border);text-align:center;color:var(--muted)}
    /* Toast */
    .toast{position:fixed;right:16px;bottom:16px;background:#111827;color:#fff;padding:.7rem .9rem;border-radius:.6rem;box-shadow:0 10px 24px rgba(0,0,0,.25);opacity:0;transform:translateY(8px);transition:.2s}
    .toast.show{opacity:1;transform:translateY(0)}
  </style>
</head>
<body>

  <!-- Topbar -->
  <div class="topbar">
    <div class="nav">
      <div class="logo" onclick="location.href='/'">
        <img src="https://snoutiq.com/favicon.webp" alt="SnoutIQ"/> SnoutIQ
      </div>
      <div class="nav-cta">
        <button class="btn primary" onclick="location.href='/register'">Register</button>
        <button class="btn" onclick="location.href='/login'">Login</button>
      </div>
    </div>
  </div>

  <!-- Center Card -->
  <main class="wrap">
    <div class="card">
      <img src="https://snoutiq.com/favicon.webp" alt="SnoutIQ Logo" class="brand"/>
      <h1>Welcome to Snoutiq!</h1>
      <p class="sub">Let's start by getting to know you</p>

      <!-- Tabs -->
      <div class="tabs">
        <button class="tab active" id="tab-pet">Pet Owner</button>
        <button class="tab" id="tab-vet">Veterinarian</button>
      </div>

      <!-- Location badge -->
      <div id="locBadge" class="loc" data-status="pending">
        <i class="fa-solid fa-location-crosshairs"></i>
        Checking location permission…
      </div>

      <!-- PET OWNER (Google) -->
      <section id="panel-pet" class="panel">
        <div id="googleBtn" class="center" style="min-height:44px"></div>
      </section>

      <!-- VETERINARIAN (email CTA or full form if you prefer) -->
      <section id="panel-vet" class="panel" style="display:none">
        <div class="email-cta">
          <input id="vetEmail" type="email" placeholder="Work email"/>
          <input id="vetPass" type="password" placeholder="Create password"/>
          <button id="vetCta" class="cta">Continue with Email</button>
          <div class="hint">You can connect Google later in settings.</div>
        </div>
      </section>

      <div class="foot">
        Already have an account? <a href="/login">Login here</a>
      </div>
    </div>
  </main>

  <div id="toast" class="toast"></div>

  <script>
    (function(){
      const tabPet = document.getElementById('tab-pet');
      const tabVet = document.getElementById('tab-vet');
      const panelPet = document.getElementById('panel-pet');
      const panelVet = document.getElementById('panel-vet');
      const toast = document.getElementById('toast');

      function showToast(msg){ toast.textContent = msg; toast.classList.add('show'); setTimeout(()=>toast.classList.remove('show'), 1800); }

      // Tabs switch
      tabPet.addEventListener('click', () => {
        tabPet.classList.add('active'); tabVet.classList.remove('active');
        panelPet.style.display = 'block'; panelVet.style.display = 'none';
      });
      tabVet.addEventListener('click', () => {
        tabVet.classList.add('active'); tabPet.classList.remove('active');
        panelPet.style.display = 'none'; panelVet.style.display = 'block';
      });

      // Location permission badge
      const badge = document.getElementById('locBadge');
      function setBadge(status, text, icon){
        badge.dataset.status = status;
        badge.innerHTML = `<i class="${icon}"></i> ${text}`;
      }
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          ()=> setBadge('granted','Location access granted','fa-solid fa-circle-check'),
          ()=> setBadge('denied','Location permission needed','fa-solid fa-triangle-exclamation'),
          { enableHighAccuracy:false, timeout:8000, maximumAge:0 }
        );
      } else {
        setBadge('denied','Location not supported','fa-solid fa-triangle-exclamation');
      }

      // Google button render (Pet Owner)
      window.onGoogleCredential = async (response) => {
        // TODO: send credential to your backend for /google-login
        // Example:
        // await axios.post('https://snoutiq.com/backend/api/google-login', { google_token: parsed.sub, email: parsed.email, role:'pet' })
        showToast('Google credential received');
        // Redirect after success:
        // location.href = '/dashboard';
      };
      window.addEventListener('load', function(){
        if (window.google && google.accounts && google.accounts.id) {
          google.accounts.id.initialize({
            client_id: "325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com",
            callback: onGoogleCredential
          });
          google.accounts.id.renderButton(
            document.getElementById("googleBtn"),
            { theme:"filled_blue", size:"large", text:"continue_with", shape:"rectangular" }
          );
        }
      });

      // Vet email CTA (demo)
      document.getElementById('vetCta').addEventListener('click', ()=>{
        // TODO: hook to your /register or /auth endpoint
        showToast('Continue with Email (vet) clicked');
        // Example redirect:
        // location.href = '/register/vet?step=details';
      });
    })();
  </script>
</body>
</html>
