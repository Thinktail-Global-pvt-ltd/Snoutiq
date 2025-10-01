{{-- resources/views/vet/landing.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $vet->name }} | SnoutIQ</title>
  <meta name="description" content="Book appointments at {{ $vet->name }}. Video consults, clinic visits, vaccinations and more."/>
  <link rel="icon" href="https://snoutiq.com/favicon.webp" sizes="32x32" type="image/png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

  {{-- Optional: embed auth/user for JS (fallback: localStorage) --}}
  <script>
    window.SNOUTIQ = {
      user: @json($authUser ?? null),
      token: @json($apiToken ?? null)
    };
  </script>

  {{-- Meta Pixel (delayed 7s) --}}
  <script>
    setTimeout(function() {
      !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
      n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
      n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;
      s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
      fbq('init','1909812439872823'); fbq('track','PageView');
    },7000);
  </script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Axios (optional, we use fetch below) -->
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <style>
/* ======== Light Theme ======== */
:root{
  --bg:#cfe1ff;--bg-2:#ffffff;--border:#d1ddff;--muted:#55607a;--text:#0f172a;--heading:#0b1220;
  --accent:#2563eb;--accent-2:#0ea5e9;--ring:rgba(37,99,235,.25);--success:#059669;
  --sky1:#d7e6ff;--sky2:#d7e6ff;
}
*{margin:0;padding:0;box-sizing:border-box}
body{
  background:radial-gradient(1000px 500px at 20% -20%,var(--sky1),transparent 60%),
             radial-gradient(1000px 500px at 120% 10%,var(--sky1),transparent 60%),
             linear-gradient(180deg,var(--sky2),var(--bg));
  color:var(--text);font-family:Inter,system-ui,Segoe UI,Roboto,Ubuntu,sans-serif;line-height:1.6
}
a{text-decoration:none;color:inherit}
img{display:block;max-width:100%}
.container{max-width:1120px;margin:0 auto;padding:1rem}
.section{padding:3rem 0}
@media(min-width:768px){.section{padding:4rem 0}}
.card{background:var(--bg-2);border:1px solid var(--border);border-radius:1rem;box-shadow:0 10px 30px -12px rgba(37,99,235,.15)}
.pill{display:inline-flex;align-items:center;gap:.5rem;background:#ecf3ff;border:1px solid var(--border);color:var(--accent);border-radius:999px;padding:.35rem .7rem;font-size:.85rem;font-weight:600}
.tag{display:inline-flex;align-items:center;gap:.5rem;background:#ecf9ff;border:1px solid #c7ebff;color:#0284c7;border-radius:999px;padding:.3rem .75rem;font-weight:700}
.muted{color:var(--muted)}
.heading{color:var(--heading)}
.grid{display:grid;gap:1rem}
@media(min-width:768px){.grid-2{grid-template-columns:repeat(2,1fr)}.grid-3{grid-template-columns:repeat(3,1fr)}.grid-4{grid-template-columns:repeat(4,1fr)}}
.btn{display:inline-flex;align-items:center;gap:.55rem;border:none;border-radius:.75rem;padding:.8rem 1.2rem;font-weight:700;cursor:pointer;transition:all .15s}
.btn:focus{outline:2px solid var(--ring);outline-offset:2px}
.btn-primary{background:linear-gradient(90deg,#3b82f6,#06b6d4);color:#fff}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 12px 30px -10px rgba(59,130,246,.4)}
.btn-outline{background:#f8fbff;color:var(--accent);border:1px solid var(--border)}
.btn-outline:hover{background:#eef6ff}
nav{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.85);backdrop-filter:blur(8px);border-bottom:1px solid var(--border)}
.nav-wrap{height:70px;display:flex;align-items:center;justify-content:space-between}
.logo{display:flex;align-items:center;gap:.5rem;font-weight:800;color:var(--accent);cursor:pointer}
.logo img{height:20px}
.input,.select,textarea{width:100%;padding:.85rem 1rem;border-radius:.75rem;border:1px solid var(--border);background:#fbfdff;color:#0f172a}
.input:focus,.select:focus,textarea:focus{outline:2px solid var(--ring);border-color:transparent}
label{font-size:.9rem;color:#334155}
.doc-img{width:72px;height:72px;border-radius:12px;object-fit:cover;border:1px solid var(--border)}
/* hero bits / ask bar */
.ai-hero{padding:18px 0 8px;text-align:center}
.ai-hero .ai-pill{display:inline-flex;gap:.5rem;align-items:center;background:#e9f2ff;border:1px solid var(--border);color:#1d4ed8;padding:.35rem .8rem;border-radius:999px;font-weight:700}
.ai-hero h1{font-size:clamp(22px,4.5vw,40px);line-height:1.15;margin:.4rem auto .2rem;max-width:980px;font-weight:800}
.ai-hero .accent{background:linear-gradient(90deg,#2563eb,#06b6d4);-webkit-background-clip:text;background-clip:text;color:transparent}
.ai-hero p.sub{max-width:880px;margin:0 auto 10px;color:#6b7280;font-size:clamp(14px,2vw,18px)}
.askbar{max-width:680px;margin:8px auto 6px;padding:6px 8px;border:1px solid var(--border);border-radius:12px;background:#fff;display:flex;align-items:center;gap:8px;box-shadow:0 8px 30px -12px rgba(37,99,235,.18)}
.askbar i{font-size:16px;opacity:.85}
.askbar input{flex:1;border:none;outline:none;font-size:14px;padding:.5rem}
.askbar .send{width:36px;height:36px;border-radius:10px;border:1px solid var(--border);background:#f5f9ff;display:grid;place-items:center;cursor:pointer}
.askbar .send:hover{background:#eef6ff}
.ai-hint{font-size:.85rem;color:#6b7280}
/* quick services */
.qsvc-grid{display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}
.qsvc-btn{background:#ecf3ff;border:1px solid var(--border);color:var(--accent);border-radius:10px;padding:10px 12px;text-align:center;font-weight:800;cursor:pointer}
.qsvc-btn:hover{background:#e6f0ff}
/* reels */
.reel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.reel-card{position:relative;background:#eef4ff;border:1px solid var(--border);border-radius:12px;padding:18px 16px;min-height:120px;display:flex;align-items:flex-end;overflow:hidden}
.reel-play{position:absolute;top:10px;right:10px;background:#00000012;border:1px solid #0000001a;color:#111;padding:.35rem .55rem;border-radius:8px;font-weight:800}
.reel-title{font-weight:800;color:#0f172a}
/* offers */
.offers-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px}
.offer-card{position:relative;background:#ffffff;border:1px solid var(--border);border-radius:12px;padding:14px}
.badge{position:absolute;top:10px;right:10px;border-radius:999px;padding:.25rem .6rem;font-weight:800;font-size:.8rem}
.badge-pink{background:#ffe4f1;color:#be185d;border:1px solid #fbcfe8}
.badge-purple{background:#efe9ff;color:#6d28d9;border:1px solid #ddd6fe}
.badge-blue{background:#e6f5ff;color:#0369a1;border:1px solid #bae6fd}
.offer-title{font-weight:800;margin-bottom:.1rem}
.offer-sub{color:#55607a;margin-bottom:.4rem}
.price-old{text-decoration:line-through;color:#64748b;margin-right:.5rem}
.price-new{font-weight:900;color:#0ea5e9}
/* floating AI btn */
.ai-fab{position:fixed;right:16px;bottom:16px;border:none;border-radius:999px;width:54px;height:54px;display:grid;place-items:center;font-size:22px;background:linear-gradient(90deg,#3b82f6,#06b6d4);color:#fff;box-shadow:0 14px 30px -10px rgba(59,130,246,.45);cursor:pointer}
.ai-fab:focus{outline:2px solid var(--ring);outline-offset:2px}
  </style>
</head>
<body>

  <!-- Nav -->
  <nav>
    <div class="container nav-wrap">
      <div class="logo" onclick="location.href='https://snoutiq.com'">
        <img src="https://snoutiq.com/favicon.webp" alt="SnoutIQ"/> {{ $vet->name }}
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <button class="btn btn-outline" onclick="location.href='https://snoutiq.com/backend/custom-doctor-login'">Login</button>
        <button class="btn btn-primary" onclick="location.href='https://snoutiq.com/backend/custom-doctor-register'">Register</button>
      </div>
    </div>
  </nav>

  <!-- Clinic profile CTA row -->
  <header class="container" style="text-align:center;padding:12px 0 8px">
    <span class="tag">🏥 Clinic Profile • {{ $vet->business_status === 'OPERATIONAL' ? 'Open' : 'Status Unknown' }}</span>
    <h1 class="heading" style="font-size:2rem;margin:.7rem 0">{{ $vet->name }}</h1>
    <p class="muted" style="max-width:820px;margin:0 auto 1rem">
      {{ $vet->formatted_address ?? $vet->address ?? ($vet->city ?? '') }}
      @if($vet->pincode) • {{ $vet->pincode }} @endif
      @if(!is_null($vet->open_now)) • {{ $vet->open_now ? 'Open now' : 'Closed now' }} @endif
    </p>
    <div style="display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap;margin-top:.5rem">
      @php $clinicPhone = $vet->mobile ?? null; @endphp
      <a class="btn btn-outline" href="{{ $clinicPhone ? 'tel:'.$clinicPhone : 'javascript:void(0)' }}"><i class="fa-solid fa-phone"></i> Call Clinic</a>
      <a class="btn btn-outline" id="video-consult-btn" href="/video?vet_slug={{ $vet->slug }}"><i class="fa-solid fa-video"></i> Start Video Consult</a>
    </div>
  </header>

  <!-- Inline ask box -->
  <div class="container" id="clinic-ask-wrap" style="text-align:center;padding:6px 0 10px">
    <div class="askbar" style="max-width:720px;margin:0 auto">
      <i class="fa-solid fa-microphone-lines"></i>
      <input id="clinic-ask-input" placeholder="Ask anything about your pet"/>
      <button id="clinic-ask-send" class="send" aria-label="Send">
        <i class="fa-solid fa-paper-plane"></i>
      </button>
    </div>
    <div class="ai-hint">AI-generated advice. Consult a licensed veterinarian.</div>
  </div>

  <!-- Quick Services (DYNAMIC) -->
  <section class="container" id="quick-services" style="padding:0 1rem 1.25rem">
    <div class="reviews-card" style="padding:10px;border-radius:12px;background:#eaf3ff;color:var(--text);border:1px solid var(--border)">
      <div class="bar" style="display:flex;align-items:center;gap:.5rem;font-weight:800;background:#e1edff;border:1px solid var(--border);color:var(--accent);border-radius:10px;padding:.6rem .8rem;margin-bottom:.75rem">
        <span>⚡</span> <span>Quick Services</span>
      </div>
      <div id="qsvc-loading" class="muted" style="padding:.5rem 0">Loading services…</div>
      <div id="qsvc-grid" class="qsvc-grid" style="display:none"></div>
      <div id="qsvc-empty" class="muted" style="text-align:center;margin-top:.6rem;display:none">No services available right now.</div>
    </div>
  </section>

  <!-- Clinic Reels -->
  <section class="container section" id="reels">
    <h2 class="heading">Clinic Reels</h2>
    <div class="reel-grid" style="margin-top:.8rem">
      <a class="reel-card" href="#"><div class="reel-play">▶</div><div class="reel-title">Meet Dr. Sarah</div></a>
      <a class="reel-card" href="#"><div class="reel-play">▶</div><div class="reel-title">Clinic Tour</div></a>
      <a class="reel-card" href="#"><div class="reel-play">▶</div><div class="reel-title">Success Story</div></a>
      <a class="reel-card" href="#"><div class="reel-play">▶</div><div class="reel-title">Our Services</div></a>
    </div>
  </section>

  <!-- Reviews -->
  <section class="container" id="reviews" style="padding:0 1rem 2rem">
    <div class="reviews-card" style="padding:10px;border-radius:12px;background:#eaf3ff;color:var(--text);border:1px solid var(--border)">
      <div class="bar" style="display:flex;align-items:center;gap:.5rem;font-weight:800;background:#e1edff;border:1px solid var(--border);color:var(--accent);border-radius:10px;padding:.6rem .8rem;margin-bottom:.75rem">
        <span>⭐</span> <span>Reviews (4.9/5)</span>
      </div>
      <div class="rev-item" style="background:#ffffff;border:1px solid var(--border);border-radius:10px;padding:.7rem .9rem;color:var(--text)"><div style="font-weight:800;margin-bottom:.2rem">Priya S.</div><div>"AI diagnosis was perfect! Quick video call saved my cat."</div></div>
      <div class="rev-item" style="background:#ffffff;border:1px solid var(--border);border-radius:10px;padding:.7rem .9rem;color:var(--text);margin-top:.6rem"><div style="font-weight:800;margin-bottom:.2rem">Rakesh M.</div><div>"Emergency service at 2 AM. Excellent response time."</div></div>
    </div>
  </section>

  <!-- Current Offers -->
  <section class="container section" id="offers">
    <h2 class="heading">Current Offers</h2>
    <div class="offers-grid" style="margin-top:1rem">
      <div class="offer-card">
        <div class="badge badge-pink">50% OFF</div>
        <div class="offer-title">New Pet Package</div>
        <div class="offer-sub">Complete checkup + vaccines</div>
        <div><span class="price-old">₹1,998</span><span class="price-new">₹999</span></div>
      </div>
      <div class="offer-card">
        <div class="badge badge-purple">LIMITED</div>
        <div class="offer-title">Emergency Plan</div>
        <div class="offer-sub">24/7 support for 1 year</div>
        <div><span class="price-new">₹1,999/year</span></div>
      </div>
      <div class="offer-card">
        <div class="badge badge-blue">NEW</div>
        <div class="offer-title">AI Health Monitor</div>
        <div class="offer-sub">Wearable + monthly reports</div>
        <div><span class="price-new">₹899/month</span></div>
      </div>
    </div>
  </section>

  <!-- Quick stats -->
  <section class="container" style="margin-top:-.1rem">
    <div class="grid grid-3">
      <div class="card" style="padding:1.1rem;text-align:center">
        <div style="font-size:1.4rem;font-weight:800;color:var(--success)">{{ number_format((float)($vet->rating ?? 4.7), 1) }}★</div>
        <div class="muted" style="font-size:.95rem">{{ $vet->user_ratings_total ?? 0 }} reviews</div>
      </div>
      <div class="card" style="padding:1.1rem;text-align:center">
        <div style="font-size:1.4rem;font-weight:800;color:var(--accent)">{{ $vet->city ?? '—' }}</div>
        <div class="muted" style="font-size:.95rem">City</div>
      </div>
      <div class="card" style="padding:1.1rem;text-align:center">
        <div style="font-size:1.4rem;font-weight:800;color:var(--accent-2)">Cashless soon</div>
        <div class="muted" style="font-size:.95rem">Insurance support</div>
      </div>
    </div>
  </section>

  <!-- Doctors -->
  <section class="container section" id="doctors">
    <h2 class="heading">Our Doctors</h2>
    @php $doctors = $vet->doctors()->orderBy('doctor_name')->get(); @endphp
    @if($doctors->isEmpty())
      <div class="card" style="padding:1rem;margin-top:1rem"><p class="muted">No doctors listed yet. Please check back soon.</p></div>
    @else
      <div class="grid grid-3" style="margin-top:1rem">
        @foreach($doctors as $doc)
          <div class="card" style="padding:1rem;display:flex;gap:1rem;align-items:center">
            <img class="doc-img" src="{{ $doc->doctor_image ?: 'https://placehold.co/96x96?text=Dr' }}" alt="{{ $doc->doctor_name }}">
            <div style="flex:1">
              <div class="heading" style="font-weight:800">{{ $doc->doctor_name }}</div>
              <div class="muted" style="margin:.15rem 0">
                @if($doc->doctor_license) License: {{ $doc->doctor_license }} • @endif
                @if($doc->doctor_email) <a href="mailto:{{ $doc->doctor_email }}" style="color:var(--accent)">Email</a>@endif
              </div>
              <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.5rem">
                <a class="pill" href="{{ $doc->doctor_mobile ? 'tel:'.$doc->doctor_mobile : 'javascript:void(0)' }}"><i class="fa-solid fa-phone"></i>&nbsp;Call</a>
                <a class="pill" href="/video?vet_slug={{ $vet->slug }}"><i class="fa-solid fa-video"></i>&nbsp;Video Consult</a>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </section>

  <!-- Location -->
  @php
    $mapQuery = ($vet->lat && $vet->lng) ? ($vet->lat.','.$vet->lng) : ($vet->formatted_address ?: ($vet->address ?: $vet->city));
  @endphp
  <section class="container section" id="clinic">
    <h2 class="heading">Location</h2>
    <div class="card" style="padding:1rem;margin-top:1rem">
      <div class="muted"><strong class="heading" style="font-size:1rem">Address:</strong> {{ $vet->formatted_address ?? $vet->address ?? '—' }}</div>
      <div class="muted"><strong class="heading" style="font-size:1rem">Hours:</strong> Mon–Sat 10:00–18:00 • Sun closed</div>
      <div style="margin-top:1rem;overflow:hidden;border-radius:.75rem;border:1px solid var(--border)">
        <iframe src="https://maps.google.com/maps?q={{ urlencode($mapQuery) }}&t=&z=13&ie=UTF8&iwloc=&output=embed"
                width="100%" height="300" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="container section" style="padding:2rem 1rem">
      <div class="grid grid-3">
        <div>
          <div class="logo" onclick="location.href='https://snoutiq.com'">
            <img src="https://snoutiq.com/favicon.webp" alt="SnoutIQ"/> SnoutIQ
          </div>
          <p class="muted" style="margin-top:.6rem;max-width:420px">
            Clinic landing powered by SnoutIQ. Book clinic & video consultations seamlessly.
          </p>
        </div>
        <div>
          <div class="heading" style="font-weight:700;margin-bottom:.6rem">Policies</div>
          <div class="muted" style="display:flex;flex-direction:column;gap:.3rem">
            <a href="https://snoutiq.com/privacy-policy">Privacy Policy</a>
            <a href="https://snoutiq.com/terms-of-service">Terms of Service</a>
            <a href="https://snoutiq.com/cancellation-policy">Refund Policy</a>
            <a href="https://snoutiq.com/cookie-policy">Cookie Policy</a>
            <a href="https://snoutiq.com/medical-data-consent">Medical Disclaimer</a>
          </div>
        </div>
        <div>
          <div class="heading" style="font-weight:700;margin-bottom:.6rem">Contact</div>
          <div class="muted">
            {{ $vet->email ?? 'info@snoutiq.com' }}
            @if($vet->mobile) • {{ $vet->mobile }} @endif
          </div>
          <div style="display:flex;gap:.5rem;margin-top:.7rem">
            <a class="pill" href="https://www.instagram.com/snoutiq_marketplace/"><i class="fa-brands fa-instagram"></i>&nbsp;Instagram</a>
            <a class="pill" href="https://www.facebook.com/people/Snoutiq/61578226867078/"><i class="fa-brands fa-facebook"></i>&nbsp;Facebook</a>
          </div>
        </div>
      </div>
      <div class="muted" style="margin-top:1rem;font-size:.9rem">© 2024 SnoutIQ. All rights reserved.</div>
    </div>
  </footer>

  <!-- JSON-LD -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "MedicalClinic",
    "name": "{{ $vet->name }}",
    "image": "{{ $vet->image ?? 'https://snoutiq.com/favicon.webp' }}",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "{{ $vet->formatted_address ?? $vet->address ?? '' }}",
      "addressLocality": "{{ $vet->city ?? '' }}",
      "postalCode": "{{ $vet->pincode ?? '' }}",
      "addressCountry": "IN"
    },
    "telephone": "{{ $vet->mobile ?? '' }}",
    "url": "{{ url('/backend/vet/'.$vet->slug) }}"
  }
  </script>

  <!-- Buttons logic (askbar) -->
  <script>
  (function() {
    const vetSlug = @json($vet->slug);

    function loginRedirect(prefill = "") {
      try { if (prefill) localStorage.setItem("pendingChatQuestion", prefill); } catch(_) {}
      const params = new URLSearchParams({ next: location.href, source: "chat", vet_slug: (typeof vetSlug !== "undefined" ? vetSlug : "") });
      if (prefill) params.set("prefill", prefill);
      location.href = `backend/custom-doctor-register?${params.toString()}`;
    }

    function openChat(){
      const params = new URLSearchParams({ vet_slug: (vetSlug || '') });
      location.href = `/backend/pet-dashboard?${params.toString()}`;
    }
    window.openChat = openChat;

    const sendAsk = (id) => {
      const v = (document.getElementById(id)?.value || '').trim();
      if (!v) return; loginRedirect(v);
    };
    document.getElementById('clinic-ask-send')?.addEventListener('click', ()=> sendAsk('clinic-ask-input'));
    document.getElementById('clinic-ask-input')?.addEventListener('keydown', (e)=>{ if (e.key==='Enter'){ e.preventDefault(); sendAsk('clinic-ask-input'); }});
  })();
  </script>

  <!-- Dynamic Quick Services + SweetAlert request -->
  <script>
  (function(){
    const vetSlug     = @json($vet->slug);
    const API_SERVICES= 'https://snoutiq.com/backend/api/groomer/services';
    const API_REQUEST = 'https://snoutiq.com/backend/api/service-requests'; // change if different

    const grid    = document.getElementById('qsvc-grid');
    const empty   = document.getElementById('qsvc-empty');
    const loading = document.getElementById('qsvc-loading');

    const authToken = (window.SNOUTIQ && window.SNOUTIQ.token) || localStorage.getItem('token') || sessionStorage.getItem('token');

    function resolveUserId(){
      if (window.SNOUTIQ && window.SNOUTIQ.user && window.SNOUTIQ.user.id) return Number(window.SNOUTIQ.user.id);
      try{
        const raw = sessionStorage.getItem('auth_full') || localStorage.getItem('auth_full');
        const obj = raw ? JSON.parse(raw) : null;
        const id  = obj?.user?.id ?? obj?.user_id;
        return id ? Number(id) : null;
      }catch(_){ return null; }
    }
    const USER_ID = resolveUserId();
    console.log('[vet page] user_id resolved:', USER_ID, 'vet_slug:', vetSlug);

    function inr(n){ const x=Number(n); return Number.isFinite(x)? x.toFixed(0) : '—'; }

    function chip(service){
      const btn = document.createElement('button');
      btn.className = 'qsvc-btn';
      const price = inr(service.price);
      btn.innerHTML = `${service.name || 'Service'} ₹${price}`;
      btn.addEventListener('click', ()=> openServiceModal(service));
      return btn;
    }

    async function fetchServices(){
      loading.style.display = '';
      grid.style.display = 'none';
      empty.style.display = 'none';

      try{
        // build URL with vet_slug + optional user_id
        const url = new URL(API_SERVICES);
        if (vetSlug) url.searchParams.set('vet_slug', vetSlug);
        if (USER_ID) url.searchParams.set('user_id', String(USER_ID));

        const res = await fetch(url.toString(), {
          headers: {
            'Accept':'application/json',
            ...(vetSlug ? {'X-Vet-Slug': vetSlug} : {})
          }
        });

        const data = await res.json();
        const list = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
        renderServices(list);
      }catch(err){
        console.error('services.fetch.error', err);
        empty.textContent = 'Could not load services right now.';
        empty.style.display = '';
      }finally{
        loading.style.display = 'none';
      }
    }

    function renderServices(items){
      grid.innerHTML = '';
      const filtered = (items || []).filter(s => (s.status||'').toLowerCase() !== 'inactive');
      if (!filtered.length){
        empty.textContent = 'No services available right now.';
        empty.style.display = '';
        grid.style.display = 'none';
        return;
      }
      filtered.slice(0,24).forEach(s => grid.appendChild(chip(s)));
      grid.style.display = '';
      empty.style.display = 'none';
    }

    function formRow(label, value){
      const v = (value ?? '').toString().trim();
      return `<div style="display:flex;justify-content:space-between;gap:12px;margin:.25rem 0">
                <div style="color:#64748b">${label}</div>
                <div style="font-weight:700">${v || '—'}</div>
              </div>`;
    }

    function openServiceModal(svc){
      const price = inr(svc.price);
      const dur   = svc.duration ? `${svc.duration} mins` : '—';
      const pet   = svc.pet_type || '—';
      const cat   = svc.main_service || '—';
      const desc  = (svc.description && svc.description.trim()) ? svc.description.trim() : 'No description provided.';

      Swal.fire({
        title: svc.name || 'Service',
        html: `
          <div style="text-align:left">
            ${formRow('Category', cat)}
            ${formRow('Pet Type', pet)}
            ${formRow('Duration', dur)}
            ${formRow('Price', '₹' + price)}
            <div style="margin:.5rem 0 .35rem;font-weight:800">Description</div>
            <div style="background:#f8fbff;border:1px solid #e5edff;border-radius:10px;padding:.6rem .7rem;color:#334155">${desc}</div>
            <div style="margin:.7rem 0 .3rem;font-weight:800">Notes (optional)</div>
            <textarea id="svc-notes" rows="3" style="width:100%;padding:.6rem .7rem;border-radius:10px;border:1px solid #e5edff;background:#fbfdff;outline:none"></textarea>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Request Service',
        cancelButtonText: 'Close',
        focusConfirm: false,
        preConfirm: async ()=>{
          try{
            const notes = (document.getElementById('svc-notes')?.value || '').trim();
            await requestService(svc, notes);
            return { ok:true };
          }catch(err){
            const msg = err?.message || (typeof err==='string'?err:'Failed to request service');
            Swal.showValidationMessage(msg);
            return false;
          }
        }
      }).then((res)=>{
        if (res.isConfirmed){
          Swal.fire({icon:'success', title:'Request sent', text:'We will contact you shortly.', timer:1600, showConfirmButton:false});
        }
      });
    }

    async function requestService(svc, notes){
      const payload = {
        service_id: svc.id,
        vet_slug: vetSlug || '',
        notes: notes || '',
        user_id: USER_ID || null
      };
      const headers = { 'Accept':'application/json', 'Content-Type':'application/json' };
      if (authToken) headers['Authorization'] = 'Bearer ' + authToken;

      const resp = await fetch(API_REQUEST, { method:'POST', headers, body: JSON.stringify(payload), credentials:'include' });
      const ct   = resp.headers.get('content-type') || '';
      const isJSON = ct.includes('application/json');
      const body   = isJSON ? await resp.json() : await resp.text();

      if (!resp.ok){
        const msg = (body && body.message) ? body.message : ('HTTP ' + resp.status);
        throw new Error(msg);
      }
      console.log('[service.request.success]', body);
    }

    // init
    document.addEventListener('DOMContentLoaded', fetchServices);
  })();
  </script>

  <!-- Floating AI button -->
  <button class="ai-fab" onclick="openChat()" aria-label="Open AI Assistant">🎩</button>

  <noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id=1909812439872823&ev=PageView&noscript=1"/>
  </noscript>
</body>
</html>
