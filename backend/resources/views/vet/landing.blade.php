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
      n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];
      t=b.createElement(e);t.async=!0;t.src=v;
      s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}
      (window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
      fbq('init', '1909812439872823'); fbq('track', 'PageView');
    }, 7000);
  </script>

  <style>
    /* ======== Light Theme ======== */
:root{
  --bg:   #cfe1ff; /* a notch darker blue */
  --bg-2: #ffffff;
  --border:#d1ddff;
  --muted:#55607a;
  --text:#0f172a;
  --heading:#0b1220;
  --accent:#2563eb;
  --accent-2:#0ea5e9;
  --ring: rgba(37,99,235,.25);
  --success:#059669;

  /* gradient ke blobs thode darker */
  --sky1:#d7e6ff;
  --sky2:#d7e6ff;
}

    *{margin:0;padding:0;box-sizing:border-box}
    body{
      background:radial-gradient(1000px 500px at 20% -20%, var(--sky1), transparent 60%),
                 radial-gradient(1000px 500px at 120% 10%, var(--sky1), transparent 60%),
                 linear-gradient(180deg, var(--sky2), var(--bg));
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

    /* ===== HERO — compact to surface clinic info ===== */
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

    /* ===== Emergency bar (from demo) ===== */
    .emergency-bar{background:linear-gradient(135deg,#f44336,#e57373);color:#fff;padding:12px;border-radius:10px;text-align:center;cursor:pointer;box-shadow:0 10px 24px rgba(244,67,54,.25)}
    .emergency-bar:hover{transform:translateY(-1px)}

    /* ===== Quick Actions (from demo) ===== */
    .qa-grid{display:grid;gap:10px;grid-template-columns:repeat(4,1fr);max-width:720px;margin:8px auto 0}
    .qa-btn{background:#f8fbff;border:1px solid var(--border);border-radius:12px;padding:10px;display:flex;flex-direction:column;align-items:center;gap:4px;cursor:pointer}
    .qa-btn:hover{background:#eef6ff}
    .qa-icon{font-size:22px}
    .qa-sub{font-size:.72rem;color:#64748b}
    @media(max-width:640px){.qa-grid{grid-template-columns:repeat(4,1fr)}}

    /* ===== Floating AI button (from demo) ===== */
    .ai-fab{position:fixed;right:20px;bottom:20px;width:56px;height:56px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(135deg,#3b82f6,#06b6d4);color:#fff;box-shadow:0 8px 24px rgba(59,130,246,.35);cursor:pointer;z-index:80}

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
        <button class="btn btn-outline" onclick="location.href='https://snoutiq.com/backend/custom-doctor-register'">Login</button>
        <button class="btn btn-primary" onclick="location.href='https://snoutiq.com/backend/custom-doctor-register'">Register</button>
      </div>
    </div>
  </nav>

  <!-- Emergency CTA -->
  <section class="container" style="padding:10px 0 0">
    <div class="emergency-bar" onclick="emergency()">🚨 EMERGENCY - TAP FOR INSTANT CONNECT</div>
  </section>

  <!-- ===== HERO (compact) ===== -->
  <section class="container ai-hero">
    <div class="ai-pill">🩺 AI-Powered Pet Care Assistant</div>
    <h1>{{ $vet->name }} – Your AI Pet Companion for <span class="accent">Smart Pet Care</span></h1>
    <p class="sub">Intelligent pet care guidance, health advice, and training tips powered by advanced AI technology</p>

    <!-- Search-like Ask Bar -->
    <div class="askbar">
      <i class="fa-solid fa-microphone-lines"></i>
      <input id="top-ask" placeholder="Ask anything about your pet"/>
      <button id="top-ask-send" class="send" aria-label="Send">
        <i class="fa-solid fa-paper-plane"></i>
      </button>
    </div>
    <div class="ai-hint">Ask anything about your pet's health, behavior, or training</div>

    <!-- Demo buttons ported: Analyze + Quick Actions -->
    <div style="max-width:720px;margin:8px auto 0;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
      <button class="btn btn-outline" onclick="checkSymptoms()">🤖 Analyze Symptoms</button>
    </div>
    <div class="qa-grid">
      <button class="qa-btn" onclick="videoCall()"><div class="qa-icon">📹</div><div>Video Call</div><div class="qa-sub">₹299</div></button>
      <button class="qa-btn" onclick="bookClinic()"><div class="qa-icon">🏥</div><div>Book Visit</div><div class="qa-sub">Available</div></button>
      <button class="qa-btn" onclick="getRecords()"><div class="qa-icon">📋</div><div>Records</div><div class="qa-sub">Access</div></button>
      <button class="qa-btn" onclick="refillMeds()"><div class="qa-icon">💊</div><div>Refill</div><div class="qa-sub">Order</div></button>
    </div>
  </section>

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
      <a class="btn btn-primary" href="#book"><i class="fa-solid fa-calendar-check"></i> Book Appointment</a>
      @php $clinicPhone = $vet->mobile ?? null; @endphp
      <a class="btn btn-outline" href="{{ $clinicPhone ? 'tel:'.$clinicPhone : 'javascript:void(0)' }}"><i class="fa-solid fa-phone"></i> Call Clinic</a>
      <a class="btn btn-outline" id="video-consult-btn" href="/video?vet_slug={{ $vet->slug }}"><i class="fa-solid fa-video"></i> Start Video Consult</a>
    </div>
  </header>

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

  <!-- Booking -->
  <section class="container section" id="book">
    <h2 class="heading">Book Appointment</h2>
    <div class="card" style="padding:1rem;margin-top:1rem">
      <form action="/backend/appointments/create" method="GET" class="grid grid-2">
        <div>
          <label for="owner_name">Your Name</label>
          <input id="owner_name" name="name" class="input" placeholder="Pet parent name" required>
        </div>
        <div>
          <label for="phone">Phone</label>
          <input id="phone" name="phone" class="input" placeholder="+91…" required>
        </div>
        <div>
          <label for="pet">Pet Name</label>
          <input id="pet" name="pet" class="input" placeholder="Sheru / Momo" required>
        </div>
        <div>
          <label for="type">Visit Type</label>
          <select id="type" name="type" class="select">
            <option>Clinic</option>
            <option>Video</option>
          </select>
        </div>
        <div>
          <label for="doctor_id">Choose Doctor</label>
          <select id="doctor_id" name="doctor_id" class="select">
            @foreach($doctors as $doc)
              <option value="{{ $doc->id }}">{{ $doc->doctor_name }}</option>
            @endforeach
          </select>
        </div>
        <div style="grid-column:1/-1">
          <label for="issue">Concern</label>
          <textarea id="issue" name="issue" rows="3" class="input" placeholder="Briefly describe what's going on…"></textarea>
        </div>
        <input type="hidden" name="vet_slug" value="{{ $vet->slug }}"/>
        <div style="grid-column:1/-1;display:flex;gap:.75rem;flex-wrap:wrap">
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-arrow-right"></i> Continue</button>
          @php $digits = $vet->mobile ? preg_replace('/[^0-9]+/', '', $vet->mobile) : null; @endphp
          <a class="btn btn-outline" href="{{ $digits ? ('https://wa.me/'.$digits.'?text='.urlencode('Hi, I want to book at '.$vet->name)) : 'javascript:void(0)' }}"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
        </div>
      </form>
      <p class="muted" style="margin-top:.75rem">Already a user? <a href="https://snoutiq.com/login" style="color:var(--accent)">Login</a> for faster booking.</p>
    </div>
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

  <!-- Axios CDN -->
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <!-- Buttons logic from demo (alerts) + askbar redirect hook -->
  <script>
  (function() {
    const vetSlug = @json($vet->slug);

    function loginRedirect(prefill = "") {
      try { if (prefill) localStorage.setItem("pendingChatQuestion", prefill); } catch(_) {}
      const params = new URLSearchParams({ next: location.href, source: "chat", vet_slug: (typeof vetSlug !== "undefined" ? vetSlug : "") });
      if (prefill) params.set("prefill", prefill);
      location.href = `backend/custom-doctor-register?${params.toString()}`;
    }

    // Ask bar → redirect
    document.getElementById('top-ask-send')?.addEventListener('click', ()=> {
      const v = (document.getElementById('top-ask')?.value || '').trim(); if (!v) return; loginRedirect(v);
    });
    document.getElementById('top-ask')?.addEventListener('keydown', (e)=>{ if (e.key==='Enter'){ e.preventDefault(); const v=e.target.value.trim(); if (v) loginRedirect(v); }});

    // === Demo button handlers ===
    window.emergency = function(){
      alert('🚨 EMERGENCY ACTIVATED

Connecting to emergency vet...
Dr. on-call responding shortly.');
    };
    window.checkSymptoms = function(){
      const v = (document.getElementById('top-ask')?.value || '').trim();
      if(!v) return alert('Please describe symptoms in the ask bar first.');
      alert(`🤖 AI ANALYSIS

"${v}"

Recommendation: Video consultation
Urgency: Medium
Doctor available now.`);
    };
    window.videoCall = function(){ alert('📹 VIDEO CONSULTATION

Doctor available: NOW
Cost: ₹299
Duration: 15–20 mins'); };
    window.bookClinic = function(){ alert('🏥 IN-CLINIC BOOKING

Next available:
Today 4:30 PM
Tomorrow 10:00 AM'); };
    window.getRecords = function(){ alert('📋 MEDICAL RECORDS

Last visit: Dec 15, 2024
Vaccinations: Up to date
Next checkup: Mar 15, 2025'); };
    window.refillMeds = function(){ alert('💊 MEDICINE REFILL

Delivery: Tomorrow
Cost: ₹450'); };
    window.openChat = function(){ alert('🎩 VETCARE AI CONCIERGE

How can I help you today?'); };
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
