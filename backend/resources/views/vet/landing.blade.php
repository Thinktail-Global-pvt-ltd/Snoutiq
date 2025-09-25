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
      --bg: #f6f9ff;
      --bg-2:#ffffff;
      --border:#dbe7ff;
      --muted:#55607a;
      --text:#0f172a;
      --heading:#0b1220;
      --accent:#2563eb;
      --accent-2:#0ea5e9;
      --ring: rgba(37,99,235,.25);
      --success:#059669;
      --sky1:#eaf2ff;
      --sky2:#ffffff;
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
    .input,.select,textarea{width:100%;padding:.85rem 1rem;border-radius:.75rem;border:1px solid var(--border);background:#fbfdff;color:var(--text)}
    .input:focus,.select:focus,textarea:focus{outline:2px solid var(--ring);border-color:transparent}
    label{font-size:.9rem;color:#334155}
    .doc-img{width:72px;height:72px;border-radius:12px;object-fit:cover;border:1px solid var(--border)}

    /* ===== HERO — like screenshot ===== */
    .ai-hero{padding:42px 0 18px;text-align:center}
    .ai-hero .ai-pill{display:inline-flex;gap:.5rem;align-items:center;background:#e9f2ff;border:1px solid var(--border);color:#1d4ed8;padding:.4rem .9rem;border-radius:999px;font-weight:700}
    .ai-hero h1{font-size:clamp(28px,5vw,56px);line-height:1.1;margin:.65rem auto .4rem;max-width:980px;font-weight:800}
    .ai-hero .accent{background:linear-gradient(90deg,#2563eb,#06b6d4);-webkit-background-clip:text;background-clip:text;color:transparent}
    .ai-hero p.sub{max-width:880px;margin:0 auto 18px;color:#6b7280;font-size:clamp(14px,2.1vw,20px)}
    .askbar{max-width:720px;margin:14px auto 8px;padding:10px 10px;border:1px solid var(--border);border-radius:16px;background:#fff;display:flex;align-items:center;gap:10px;box-shadow:0 8px 30px -12px rgba(37,99,235,.18)}
    .askbar i{font-size:18px;opacity:.8}
    .askbar input{flex:1;border:none;outline:none;font-size:16px;padding:.6rem}
    .askbar .send{width:42px;height:42px;border-radius:12px;border:1px solid var(--border);background:#f5f9ff;display:grid;place-items:center;cursor:pointer}
    .askbar .send:hover{background:#eef6ff}
    .ai-hint{font-size:.92rem;color:#6b7280}

    /* --- Chat UI (reveals after first send) --- */
    .chat-wrap{display:grid;gap:1rem}
    @media(min-width:1024px){.chat-wrap{grid-template-columns:280px 1fr 280px}}
    .chat-card{background:#fff;border:1px solid var(--border);border-radius:1rem;box-shadow:0 8px 28px -14px rgba(37,99,235,.18);overflow:hidden}
    .rooms{height:540px;overflow:auto}
    .room-item{padding:.7rem .9rem;border-bottom:1px solid #eef5ff;cursor:pointer}
    .room-item.active{background:#eef6ff}
    .msgs{height:540px;overflow:auto;background:#f8fbff}
    .msg{max-width:78%;margin:.35rem 0;padding:.7rem .9rem;border-radius:.8rem;border:1px solid #e8f0ff;background:#fff}
    .msg.user{margin-left:auto;background:#e8f3ff;border-color:#d6e8ff}
    .msg.ai{margin-right:auto}
    .msg .time{font-size:.75rem;color:#64748b;margin-top:.25rem}
    .typing{display:inline-block;min-width:38px}
    .typing span{display:inline-block;width:6px;height:6px;margin:0 1px;background:#94a3b8;border-radius:50%;animation:bounce 1s infinite}
    .typing span:nth-child(2){animation-delay:.15s}
    .typing span:nth-child(3){animation-delay:.3s}
    @keyframes bounce{0%,80%,100%{transform:scale(0)}40%{transform:scale(1)}}
    .chat-input-wrap{display:flex;gap:.5rem;padding:.75rem;border-top:1px solid #e5efff;background:#fff}
    .toast{position:fixed;right:16px;bottom:16px;background:#111827;color:#fff;padding:.65rem .85rem;border-radius:.6rem;box-shadow:0 10px 24px rgba(0,0,0,.25);opacity:0;transform:translateY(10px);transition:.2s}
    .toast.show{opacity:1;transform:translateY(0)}
    .badge-blue{display:inline-flex;align-items:center;background:#e9f2ff;border:1px solid var(--border);color:#2563eb;border-radius:999px;font-size:.75rem;padding:.15rem .5rem;font-weight:700}
    .right-col{height:540px;overflow:auto}
    .doctor-mini{display:flex;gap:.7rem;align-items:center;padding:.6rem;border:1px solid var(--border);border-radius:.8rem;background:#fff}
  </style>
</head>
<body>

  <!-- Nav -->
  <nav>
    <div class="container nav-wrap">
      <div class="logo" onclick="location.href='https://snoutiq.com'">
        <img src="https://snoutiq.com/favicon.webp" alt="SnoutIQ"/> SnoutIQ
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <button class="btn btn-outline" onclick="location.href='https://snoutiq.com/login'">Login</button>
        <button class="btn btn-primary" onclick="location.href='https://snoutiq.com/register?utm_source=clinic-landing'">Register</button>
      </div>
    </div>
  </nav>

  <!-- ===== HERO (Screenshot-like) ===== -->
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
  </section>

  <!-- ===== AI CHAT (hidden until first send) ===== -->
  <section id="ai-chat" class="container section" style="display:none;padding-top:1.25rem">
    <div class="heading" style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem">
      <span class="badge-blue">SnoutIQ AI</span>
      <h2 class="heading" style="font-size:1.35rem">Ask about your pet's health</h2>
    </div>

    <div class="chat-wrap">
      <!-- Rooms -->
      <div class="chat-card">
        <div style="padding:.8rem;border-bottom:1px solid #e5efff;display:flex;justify-content:space-between;align-items:center">
          <strong>Chat Rooms</strong>
          <button id="btn-new-chat" class="btn btn-outline" style="padding:.45rem .75rem;font-size:.85rem">New</button>
        </div>
        <div id="rooms" class="rooms"></div>
      </div>

      <!-- Messages -->
      <div class="chat-card" style="display:flex;flex-direction:column">
        <div style="padding:.8rem;border-bottom:1px solid #e5efff;display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-weight:800">SnoutIQ AI Assistant</div>
            <div class="muted" style="font-size:.9rem" id="room-subtitle"></div>
          </div>
          <button id="btn-clear" class="btn btn-outline" style="padding:.45rem .75rem;font-size:.85rem">Clear</button>
        </div>
        <div id="msgs" class="msgs">
          <div id="empty-state" style="display:flex;align-items:center;justify-content:center;height:100%;padding:1rem">
            <div style="text-align:center;max-width:420px" class="card">
              <div style="padding:1rem">
                <div class="muted" style="margin:.25rem 0">Start a conversation about your pet's health.</div>
                <div style="margin-top:.5rem;background:#eef6ff;border:1px solid var(--border);padding:.6rem;border-radius:.6rem;text-align:left">
                  <div style="font-weight:700;color:#1e40af;margin-bottom:.25rem;font-size:.9rem">Try asking:</div>
                  <ul style="color:#1e3a8a;font-size:.9rem;line-height:1.5">
                    <li>• My dog is scratching constantly, what could it be?</li>
                    <li>• What's the best diet for a senior cat?</li>
                    <li>• How often should I groom my golden retriever?</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Chat input -->
        <div class="chat-input-wrap">
          <input id="chat-input" class="input" placeholder="Type your question…" />
          <button id="btn-send" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i>&nbsp;Send</button>
        </div>
        <div style="padding:.4rem 1rem .9rem" class="muted">⚠️ AI-generated advice. For serious concerns, consult a licensed vet.</div>
      </div>

      <!-- Right Column: Nearby Doctors -->
      <div class="chat-card right-col">
        <div style="padding:.8rem;border-bottom:1px solid var(--border)"><strong>Nearby Doctors</strong></div>
        <div id="nearby" style="padding:.8rem;display:grid;gap:.6rem"></div>
      </div>
    </div>
  </section>

  <!-- Hero (Clinic profile) -->
  <header class="container section" style="text-align:center">
    <span class="tag">🏥 Clinic Profile • {{ $vet->business_status === 'OPERATIONAL' ? 'Open' : 'Status Unknown' }}</span>
    <h1 class="heading" style="font-size:2.25rem;margin:.9rem 0">{{ $vet->name }}</h1>
    <p class="muted" style="max-width:820px;margin:0 auto 1.25rem">
      {{ $vet->formatted_address ?? $vet->address ?? ($vet->city ?? '') }}
      @if($vet->pincode) • {{ $vet->pincode }} @endif
      @if(!is_null($vet->open_now)) • {{ $vet->open_now ? 'Open now' : 'Closed now' }} @endif
    </p>
    <div style="display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap;margin-top:1rem">
      <a class="btn btn-primary" href="#book"><i class="fa-solid fa-calendar-check"></i> Book Appointment</a>
      @if($vet->mobile)
        <a class="btn btn-outline" href="tel:{{ $vet->mobile }}"><i class="fa-solid fa-phone"></i> Call Clinic</a>
      @endif
      <a class="btn btn-outline" id="video-consult-btn" href="/video?vet_slug={{ $vet->slug }}">
        <i class="fa-solid fa-video"></i> Start Video Consult
      </a>
    </div>
  </header>

  <!-- Quick stats -->
  <section class="container" style="margin-top:-.25rem">
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
                @if($doc->doctor_mobile)
                  <a class="pill" href="tel:{{ $doc->doctor_mobile }}"><i class="fa-solid fa-phone"></i>&nbsp;Call</a>
                @endif
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
          @php $digits = $vet->mobile ? preg_replace('/\D+/', '', $vet->mobile) : null; @endphp
          @if($digits)
            <a class="btn btn-outline" href="https://wa.me/{{ $digits }}?text={{ urlencode('Hi, I want to book at '.$vet->name) }}"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
          @endif
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

  <!-- Chat Logic -->
  <script>
  (function() {
    const API_BASE = 'https://snoutiq.com/backend/api';
    const vetSlug = @json($vet->slug);
    const clinicId = @json($vet->id);
    const userFromBlade = (window.SNOUTIQ && window.SNOUTIQ.user) ? window.SNOUTIQ.user : null;
    const bearerToken = (window.SNOUTIQ && window.SNOUTIQ.token) || localStorage.getItem('token') || null;

    // toast
    const toast = {
      el: null,
      show(msg) {
        if(!this.el) {
          this.el = document.createElement('div');
          this.el.className = 'toast';
          document.body.appendChild(this.el);
        }
        this.el.textContent = msg;
        this.el.classList.add('show');
        setTimeout(()=> this.el.classList.remove('show'), 1800);
      }
    };

    // state
    let user = userFromBlade;
    let token = bearerToken;
    let currentRoom = localStorage.getItem('lastChatRoomToken') || '';
    let contextToken = localStorage.getItem('contextToken') || '';
    let messages = [];
    let typingTimer = null;
    let isSending = false;

    const els = {
      rooms: document.getElementById('rooms'),
      msgs: document.getElementById('msgs'),
      empty: document.getElementById('empty-state'),
      input: document.getElementById('chat-input'),
      send: document.getElementById('btn-send'),
      clear: document.getElementById('btn-clear'),
      newChat: document.getElementById('btn-new-chat'),
      roomSubtitle: document.getElementById('room-subtitle'),
      nearby: document.getElementById('nearby'),
      heroInput: document.getElementById('top-ask'),
      heroSend: document.getElementById('top-ask-send'),
      chatSection: document.getElementById('ai-chat'),
    };

    const genId = () => Date.now() + Math.random();
    const storageKey = (token) => token ? `chatMessages_${token}` : 'chatMessages';
    const scrollToBottom = (smooth = true) => {
      setTimeout(() => {
        els.msgs.scrollTo({ top: els.msgs.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
      }, 50);
    };
    function getToken(){ if (token) return token; token = localStorage.getItem('token'); return token; }
    function getUser(){
      if (user) return user;
      try { const u = localStorage.getItem('user'); user = u ? JSON.parse(u) : null; } catch(e){}
      return user;
    }

    function revealChat(){
      if (els.chatSection && els.chatSection.style.display === 'none') {
        els.chatSection.style.display = 'block';
        location.hash = '#ai-chat';
      }
    }

    // rooms
    async function loadRooms() {
      const u = getUser(); const t = getToken();
      if (!u || !t) return;
      try {
        const res = await axios.get(`${API_BASE}/chat/listRooms?user_id=${u.id}`, { headers: { Authorization: `Bearer ${t}` }});
        const data = Array.isArray(res.data) ? res.data : (res.data.rooms || res.data.data || []);
        renderRooms(data);
      } catch(e){ console.error(e); }
    }
    function renderRooms(rooms){
      els.rooms.innerHTML = '';
      if (!rooms || rooms.length === 0) { els.rooms.innerHTML = `<div class="room-item muted">No rooms</div>`; return; }
      rooms.forEach(r => {
        const token = r.chat_room_token || r.token || r.room_token || r.id || '';
        const title = r.title || r.last_question || ('Room ' + String(token).slice(-6));
        const el = document.createElement('div');
        el.className = 'room-item' + (token === currentRoom ? ' active' : '');
        el.innerHTML = `<div style="font-weight:700">${title}</div>
                        <div class="muted" style="font-size:.8rem">${(r.updated_at || r.created_at || '').toString().slice(0,19)}</div>`;
        el.onclick = () => openRoom(token);
        els.rooms.appendChild(el);
      });
    }

    async function openRoom(roomToken){
      const u = getUser(); const t = getToken();
      if (!u || !t) { toast.show('Login required'); return; }
      currentRoom = roomToken || '';
      localStorage.setItem('lastChatRoomToken', currentRoom);

      const saved = localStorage.getItem(storageKey(currentRoom));
      messages = saved ? JSON.parse(saved) : [];
      renderMessages();

      try {
        const url = currentRoom
          ? `${API_BASE}/chat-rooms/${currentRoom}/chats?user_id=${u.id}`
          : `${API_BASE}/chat/listRooms?user_id=${u.id}`;
        const res = await axios.get(url, { headers: { Authorization: `Bearer ${t}` } });
        let msgs = [];
        if (res.data && res.data.chats) {
          msgs = res.data.chats.flatMap(chat => {
            const baseId = Number(new Date(chat.created_at)) + Math.random();
            return [
              { id: baseId + 1, sender:'user', text: chat.question, displayedText: chat.question, ts: chat.created_at },
              { id: baseId + 2, sender:'ai', text: chat.answer,  displayedText: chat.answer,  ts: chat.created_at }
            ];
          });
        }
        if (msgs.length) { messages = msgs; persistMessages(); renderMessages(); }
      } catch(e){ console.error(e); }
    }

    function renderMessages(){
      els.msgs.innerHTML = '';
      if (!messages.length) {
        els.msgs.appendChild(els.empty);
        els.empty.style.display = 'flex';
        els.roomSubtitle.textContent = currentRoom ? `Room: ${currentRoom}` : '';
        return;
      }
      els.empty.style.display = 'none';
      messages.forEach(m => {
        const div = document.createElement('div');
        div.className = 'msg ' + (m.sender === 'user' ? 'user' : 'ai');
        div.innerHTML = `<div>${(m.displayedText ?? m.text ?? '').replace(/\n/g,'<br>')}</div>
                         <div class="time">${new Date(m.timestamp || m.ts || Date.now()).toLocaleString()}</div>`;
        els.msgs.appendChild(div);
      });
      const end = document.createElement('div'); end.style.height = '1px'; els.msgs.appendChild(end);
      els.roomSubtitle.textContent = currentRoom ? `Room: ${currentRoom}` : '';
      scrollToBottom();
    }
    function persistMessages(){ localStorage.setItem(storageKey(currentRoom), JSON.stringify(messages)); }

    function typeAI(fullText, id){
      let idx = 0; const batch = 3, speed = 25;
      if (typingTimer) clearTimeout(typingTimer);
      const step = () => {
        const target = messages.find(m => m.id === id); if (!target) return;
        target.displayedText = fullText.slice(0, Math.min(idx + batch, fullText.length));
        renderMessages(); idx += batch;
        if (idx < fullText.length) typingTimer = setTimeout(step, speed);
      };
      setTimeout(step, 250);
    }

    function loginRedirect() {
    const next = encodeURIComponent(location.href);
    // optional: a source tag to know from where login came
    location.href = `/custom-doctor-register?next=${next}&source=chat`;
  }

  // 1) Hero ask bar → chat (sendFromHero)
  function sendFromHero(){
    const text = (els.heroInput.value || '').trim();
    if (!text) return;
    const u = getUser(); const t = getToken();
    if (!u || !t) { loginRedirect(); return; }   // 🔁 redirect if not logged in
    revealChat();
    els.input.value = text;
    els.heroInput.value = '';
    sendMessage();
  }

  // 2) Chat Send button (sendMessage)
  async function sendMessage(){
    const text = els.input.value.trim(); if (!text || isSending) return;

    const u = getUser(); const t = getToken();
    if (!u || !t) { loginRedirect(); return; }   // 🔁 redirect if not logged in

    // (baaki aapka original sendMessage code yahi se chalta rahe)
    isSending = true;
    const now = new Date();
    const userMsg = { id: genId(), sender:'user', text, displayedText:text, timestamp: now };
    const loaderId = '__loader__';
    messages.push(userMsg, { id: loaderId, sender:'ai', text:'', displayedText:`<span class="typing"><span></span><span></span><span></span></span>`, timestamp: now });
    renderMessages();
    try {
      const petData = { pet_name: u?.pet_name || 'Unknown', pet_breed: u?.breed || 'Unknown Breed', pet_age: String(u?.pet_age ?? 'Unknown'), pet_location: u?.city || 'Unknown' };
      const payload = { user_id: u.id, question: text, context_token: contextToken || '', chat_room_token: currentRoom || '', ...petData };
      const res = await axios.post(`${API_BASE}/chat/send`, payload, { headers: { Authorization: `Bearer ${t}` }, timeout: 30000 });
      const newCtx = res.data?.context_token; if (newCtx) { contextToken = newCtx; localStorage.setItem('contextToken', newCtx); }
      const returnedRoom = res.data?.chat_room_token || res.data?.chat?.chat_room_token || '';
      if (!currentRoom && returnedRoom) { currentRoom = returnedRoom; localStorage.setItem('lastChatRoomToken', currentRoom); }
      const loaderIdx = messages.findIndex(m => m.id === loaderId); if (loaderIdx !== -1) messages.splice(loaderIdx, 1);
      const full = String(res.data?.chat?.answer || res.data?.answer || 'OK');
      const aiId = genId();
      messages.push({ id: aiId, sender:'ai', text: full, displayedText:'', timestamp: new Date() });
      renderMessages(); typeAI(full, aiId);
      persistMessages(); els.input.value = ''; loadRooms();
    } catch(e) {
      console.error(e);
      const loaderIdx = messages.findIndex(m => m.id === '__loader__'); if (loaderIdx !== -1) messages.splice(loaderIdx, 1);
      messages.push({ id: genId(), sender:'ai', text:'⚠️ Sorry, I am having trouble connecting right now.', displayedText:'⚠️ Sorry, I am having trouble connecting right now.', timestamp:new Date() });
      renderMessages();
    } finally { isSending = false; }
  }


    // async function sendMessage(){
    //   const text = els.input.value.trim(); if (!text || isSending) return;
    //   const u = getUser(); const t = getToken();
    //   if (!u || !t) { toast.show('Login required'); return; }

    //   isSending = true;
    //   const now = new Date();
    //   const userMsg = { id: genId(), sender:'user', text, displayedText:text, timestamp: now };
    //   const loaderId = '__loader__';
    //   messages.push(userMsg, { id: loaderId, sender:'ai', text:'', displayedText:`<span class="typing"><span></span><span></span><span></span></span>`, timestamp: now });
    //   renderMessages();

    //   try{
    //     const petData = {
    //       pet_name: u?.pet_name || 'Unknown',
    //       pet_breed: u?.breed || 'Unknown Breed',
    //       pet_age: String(u?.pet_age ?? 'Unknown'),
    //       pet_location: u?.city || 'Unknown'
    //     };
    //     const payload = { user_id: u.id, question: text, context_token: contextToken || '', chat_room_token: currentRoom || '', ...petData };
    //     const res = await axios.post(`${API_BASE}/chat/send`, payload, { headers: { Authorization: `Bearer ${t}` }, timeout: 30000 });

    //     const newCtx = res.data?.context_token; if (newCtx) { contextToken = newCtx; localStorage.setItem('contextToken', newCtx); }
    //     const returnedRoom = res.data?.chat_room_token || res.data?.chat?.chat_room_token || '';
    //     if (!currentRoom && returnedRoom) { currentRoom = returnedRoom; localStorage.setItem('lastChatRoomToken', currentRoom); }

    //     const loaderIdx = messages.findIndex(m => m.id === loaderId); if (loaderIdx !== -1) messages.splice(loaderIdx, 1);
    //     const full = String(res.data?.chat?.answer || res.data?.answer || 'OK');
    //     const aiId = genId();
    //     messages.push({ id: aiId, sender:'ai', text: full, displayedText:'', timestamp: new Date() });
    //     renderMessages(); typeAI(full, aiId);

    //     persistMessages();
    //     els.input.value = '';
    //     loadRooms();
    //   } catch(e){
    //     console.error(e); toast.show('Something went wrong');
    //     const loaderIdx = messages.findIndex(m => m.id === '__loader__'); if (loaderIdx !== -1) messages.splice(loaderIdx, 1);
    //     messages.push({ id: genId(), sender:'ai', text:'⚠️ Sorry, I am having trouble connecting right now.', displayedText:'⚠️ Sorry, I am having trouble connecting right now.', timestamp:new Date() });
    //     renderMessages();
    //   } finally { isSending = false; }
    // }

    function clearChat(){ if (!confirm('Clear this chat?')) return; messages = []; persistMessages(); renderMessages(); toast.show('Chat cleared'); }
    function newChat(){ currentRoom = ''; localStorage.removeItem('lastChatRoomToken'); messages = []; renderMessages(); toast.show('New chat'); }

    async function loadNearbyDoctors(){
      const u = getUser(); const t = getToken(); if (!u || !t) return;
      try{
        const res = await axios.get(`${API_BASE}/nearby-vets?user_id=${u.id}`, { headers: { Authorization: `Bearer ${t}` }});
        const docs = res.data?.data || [];
        els.nearby.innerHTML = '';
        docs.slice(0,8).forEach(d => {
          const card = document.createElement('div'); card.className='doctor-mini';
          card.innerHTML = `
            <img src="${d.image || 'https://placehold.co/56x56?text=Dr'}" style="width:56px;height:56px;object-fit:cover;border-radius:10px;border:1px solid var(--border)" />
            <div style="flex:1">
              <div style="font-weight:700">${d.name || d.clinic_name || 'Doctor'}</div>
              <div class="muted" style="font-size:.85rem">${d.city || ''}</div>
              <div style="margin-top:.35rem;display:flex;gap:.4rem;flex-wrap:wrap">
                ${d.mobile ? `<a class="pill" href="tel:${d.mobile}"><i class="fa-solid fa-phone"></i>&nbsp;Call</a>`:''}
                <a class="pill" href="/video?vet_slug=${encodeURIComponent(d.slug || vetSlug)}"><i class="fa-solid fa-video"></i>&nbsp;Video</a>
              </div>
            </div>`;
          els.nearby.appendChild(card);
        });
      } catch(e){ console.log('nearby doctors error', e); }
    }

    // // wire up hero ask bar -> chat
    // function sendFromHero(){
    //   const text = (els.heroInput.value || '').trim();
    //   if (!text) return;
    //   const u = getUser(); const t = getToken();
    //   if (!u || !t) { toast.show('Login required'); return; }
    //   revealChat();
    //   els.input.value = text;  // move into chat input
    //   els.heroInput.value = '';
    //   sendMessage();
    // }

    // listeners
    els.send.addEventListener('click', sendMessage);
    els.input.addEventListener('keydown', (e)=> { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }});
    els.clear.addEventListener('click', clearChat);
    els.newChat.addEventListener('click', newChat);
    els.heroSend.addEventListener('click', sendFromHero);
    els.heroInput.addEventListener('keydown', (e)=>{ if (e.key === 'Enter') { e.preventDefault(); sendFromHero(); }});

    // init
    (function init(){
      if (!getToken()) console.warn('No token found. Expect login flow to populate localStorage("token").');
      loadRooms();
      loadNearbyDoctors();
      if (currentRoom) { revealChat(); openRoom(currentRoom); }
      setInterval(loadNearbyDoctors, 5*60*1000);
    })();
  })();
  </script>

  <noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id=1909812439872823&ev=PageView&noscript=1"/>
  </noscript>
</body>
</html>
