{{-- resources/views/vet/landing.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  @php
    $snoutiqLogo = 'data:image/webp;base64,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';
  @endphp
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $vet->name }} | SnoutIQ</title>
  <meta name="description" content="Book appointments at {{ $vet->name }}. Video consults, clinic visits, vaccinations and more."/>
  <link rel="icon" href="{{ $snoutiqLogo }}" sizes="32x32" type="image/png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

  {{-- Optional: pass user/token from backend if available --}}
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
  <!-- Razorpay Checkout -->
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

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
/* hero / askbar */
.ai-hero{padding:18px 0 8px;text-align:center}
.askbar{max-width:680px;margin:8px auto 6px;padding:6px 8px;border:1px solid var(--border);border-radius:12px;background:#fff;display:flex;align-items:center;gap:8px;box-shadow:0 8px 30px -12px rgba(37,99,235,.18)}
.askbar i{font-size:16px;opacity:.85}
.askbar input{flex:1;border:none;outline:none;font-size:14px;padding:.5rem}
.askbar .send{width:36px;height:36px;border-radius:10px;border:1px solid var(--border);background:#f5f9ff;display:grid;place-items:center;cursor:pointer}
.askbar .send:hover{background:#eef6ff}
.ai-hint{font-size:.85rem;color:#6b7280}
.ai-cta{border:none;background:none;color:var(--accent);font-weight:700;cursor:pointer;padding:0 .2rem;text-decoration:underline;text-decoration-thickness:2px;text-underline-offset:3px}
.ai-cta:focus{outline:2px solid var(--ring);outline-offset:2px;border-radius:6px}
.modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:1rem;z-index:999}
.modal-card{background:#fff;border-radius:16px;border:1px solid var(--border);max-width:420px;width:100%;padding:1.5rem;box-shadow:0 18px 45px -20px rgba(15,23,42,.4)}
.modal-head{display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-bottom:.35rem}
.modal-title{font-size:1.1rem;font-weight:800;color:var(--heading)}
.modal-close{border:none;background:transparent;font-size:1.1rem;cursor:pointer;color:#475569}
.modal-actions{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem}
/* Quick services */
.qsvc-grid{display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}
.qsvc-btn{background:#ecf3ff;border:1px solid var(--border);color:var(--accent);border-radius:10px;padding:10px 12px;text-align:center;font-weight:800;cursor:pointer}
.qsvc-btn:hover{background:#e6f0ff}
.qsvc-btn[disabled]{opacity:.55;cursor:not-allowed;filter:grayscale(1)}
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

  @php
    $qrI = request('qr_i');
    $qrCounted = request('qr_counted');
  @endphp
  @if(!empty($qrI) && (string)$qrCounted !== '1')
    <img src="{{ route('qr.beacon', ['i' => $qrI]) }}" alt="" width="1" height="1" style="position:absolute;left:-9999px;top:-9999px;" />
  @endif

  @if($isDraft)
  <section class="container" style="padding:4rem 1rem 3rem;text-align:center">
    <div class="card" style="padding:2.5rem 2rem;max-width:640px;margin:0 auto">
      <span class="pill" style="margin-bottom:1rem">🚧 Clinic Profile In Draft</span>
      <h1 class="heading" style="font-size:2.2rem;margin-bottom:.75rem">{{ $vet->name ?? 'SnoutIQ Clinic' }}</h1>
      <p class="muted" style="margin:0 auto 1.5rem;max-width:480px">
        This clinic page is being set up with SnoutIQ. Share the QR or short link below so the clinic owner can claim
        the profile and complete onboarding. Until then, we only show safe placeholder details to the public.
      </p>

      <div class="card" style="padding:1rem;margin-bottom:1.25rem">
        <p class="muted" style="font-size:.9rem;margin-bottom:.35rem">Permanent short link</p>
        <a href="{{ $publicUrl }}" data-permanent-short-link style="font-weight:700;color:var(--accent)">{{ $publicUrl }}</a>
      </div>

      @if($canClaim)
        @php $claimToken = request('claim_token'); @endphp
        <p style="margin-bottom:1rem">Looks like you have the invite link. Claim this clinic to unlock full editing.</p>
        <a class="btn btn-primary" style="justify-content:center"
           href="https://snoutiq.com/backend/custom-doctor-register?claim_token={{ urlencode($claimToken) }}&public_id={{ $vet->public_id }}">
          Claim This Clinic
        </a>
      @else
        <div style="display:flex;flex-wrap:wrap;gap:.75rem;justify-content:center">
          <a class="btn btn-outline" style="justify-content:center" href="https://snoutiq.com/contact?topic=clinic">
            Notify Me
          </a>
          <a class="btn btn-primary" style="justify-content:center" href="https://snoutiq.com/contact">
            Talk To SnoutIQ Sales
          </a>
        </div>
      @endif
    </div>
  </section>

  @php
    $draftMapQuery = trim((string)($mapQuery ?? ''));
    $draftMapSrc = null;
    if ($draftMapQuery !== '') {
        $encodedQuery = urlencode($draftMapQuery);
        if (!empty($mapsEmbedKey)) {
            $draftMapSrc = 'https://www.google.com/maps/embed/v1/place?key='.urlencode($mapsEmbedKey).'&q='.$encodedQuery;
        } else {
            $draftMapSrc = 'https://maps.google.com/maps?q='.$encodedQuery.'&t=&z=13&ie=UTF8&iwloc=&output=embed';
        }
    }
  @endphp

  @if($draftMapSrc)
  <section class="container" style="padding:0 1rem 3rem">
    <div class="card" style="padding:1.5rem;max-width:720px;margin:0 auto">
      <div style="display:flex;flex-direction:column;gap:.65rem;align-items:center;text-align:center">
        <span class="pill" style="margin-bottom:.25rem">📍 Location Preview</span>
        <div class="muted" style="max-width:520px">
          {{ $vet->formatted_address ?? $vet->address ?? $vet->city ?? 'Location coming soon' }}
        </div>
        <div style="width:100%;border-radius:1rem;overflow:hidden;border:1px solid var(--border);box-shadow:0 15px 35px -25px rgba(15,23,42,.4)">
          <iframe
            src="{{ $draftMapSrc }}"
            width="100%"
            height="320"
            style="border:0"
            allowfullscreen
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
      </div>
    </div>
  </section>
  @endif
  @endif

  @unless($isDraft)

  <!-- Nav -->
  <nav>
    <div class="container nav-wrap">
      <div class="logo" onclick="location.href='https://snoutiq.com'">
        <img src="{{ $snoutiqLogo }}" alt="SnoutIQ"/> {{ $vet->name }}
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <button class="btn btn-outline" onclick="location.href='https://snoutiq.com/backend/custom-doctor-login'">Vet Login</button>
        <button class="btn btn-primary" onclick="location.href='https://snoutiq.com/vet-register'">Vet Register</button>
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
      <a class="btn btn-outline" href="https://play.google.com/store/apps/details?id=com.petai.snoutiq" target="_blank" rel="noopener">
        <i class="fa-solid fa-phone"></i> Call Clinic
      </a>
      <a class="btn btn-outline" id="video-consult-btn" href="https://play.google.com/store/apps/details?id=com.petai.snoutiq" target="_blank" rel="noopener">
        <i class="fa-solid fa-video"></i> Start Video Consult
      </a>
      <a class="btn btn-primary" href="https://play.google.com/store/apps/details?id=com.petai.snoutiq" target="_blank" rel="noopener" id="download-app-btn">
        <i class="fa-solid fa-download"></i> Download App
      </a>
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
    <div class="ai-hint">
      AI-generated advice. Consult a licensed veterinarian.
      <button id="ai-feature-cta" class="ai-cta" type="button">Download app to use this feature</button>
    </div>
  </div>

  <div class="modal-backdrop" id="app-download-modal" role="dialog" aria-modal="true" aria-labelledby="app-download-title" aria-describedby="app-download-desc">
    <div class="modal-card">
      <div class="modal-head">
        <div class="modal-title" id="app-download-title">Use this feature in the app</div>
        <button class="modal-close" type="button" data-app-modal-close aria-label="Close">×</button>
      </div>
      <p class="muted" id="app-download-desc" style="margin-top:.1rem">
        Get the SnoutIQ app to continue with video consults, clinic calls, and AI-guided care.
      </p>
      <div class="modal-actions">
        <a class="btn btn-primary" href="https://play.google.com/store/apps/details?id=com.petai.snoutiq" target="_blank" rel="noopener" id="app-modal-download">
          <i class="fa-solid fa-download"></i> Download App
        </a>
        <button class="btn btn-outline" type="button" data-app-modal-close>Not now</button>
      </div>
    </div>
  </div>

  <!-- Quick Services (DYNAMIC) -->
  <section class="container" id="quick-services" style="padding:0 1rem 1.25rem">
    <div class="card" style="padding:10px">
      <div class="bar" style="display:flex;align-items:center;gap:.5rem;font-weight:800;background:#e1edff;border:1px solid var(--border);color:var(--accent);border-radius:10px;padding:.6rem .8rem;margin-bottom:.75rem">
        <span>⚡</span> <span>Quick Services</span>
      </div>
      <div id="qsvc-loading" class="muted" style="padding:.5rem 0">Loading services…</div>
      <div id="qsvc-grid" class="qsvc-grid" style="display:none"></div>
      <div id="qsvc-empty" class="muted" style="text-align:center;margin-top:.6rem;display:none">No services available right now.</div>
    </div>
  </section>

  <!-- Reviews -->
  <section class="container" id="reviews" style="padding:0 1rem 2rem">
    <div class="card" style="padding:10px">
      <div class="bar" style="display:flex;align-items:center;gap:.5rem;font-weight:800;background:#e1edff;border:1px solid var(--border);color:#2563eb;border-radius:10px;padding:.6rem .8rem;margin-bottom:.75rem">
        <span>⭐</span> <span>Reviews (4.9/5)</span>
      </div>
      <div class="rev-item" style="background:#ffffff;border:1px solid var(--border);border-radius:10px;padding:.7rem .9rem;color:#0f172a"><div style="font-weight:800;margin-bottom:.2rem">Priya S.</div><div>"AI diagnosis was perfect! Quick video call saved my cat."</div></div>
      <div class="rev-item" style="background:#ffffff;border:1px solid var(--border);border-radius:10px;padding:.7rem .9rem;color:#0f172a;margin-top:.6rem"><div style="font-weight:800;margin-bottom:.2rem">Rakesh M.</div><div>"Emergency service at 2 AM. Excellent response time."</div></div>
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
        <div style="font-size:1.4rem;font-weight:800;color:#2563eb">{{ $vet->city ?? '—' }}</div>
        <div class="muted" style="font-size:.95rem">City</div>
      </div>
      <div class="card" style="padding:1.1rem;text-align:center">
        <div style="font-size:1.4rem;font-weight:800;color:#0ea5e9">Cashless soon</div>
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
                <a class="pill" href="https://snoutiq.com/backend/custom-doctor-login"><i class="fa-solid fa-phone"></i>&nbsp;Call</a>
                <a class="pill" href="https://snoutiq.com/backend/custom-doctor-login"><i class="fa-solid fa-video"></i>&nbsp;Video Consult</a>
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
            <img src="{{ $snoutiqLogo }}" alt="SnoutIQ"/> SnoutIQ
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

  @if(! empty($publicUrl))
  <script>
  (function() {
    if (window.__snoutiqPermanentShortLinkPinged) {
      return;
    }

    var fetchUrl = null;

    try {
      var params = new URLSearchParams(window.location.search);
      var slugParam = params.get('slug');
      if (slugParam) {
        fetchUrl = new URL(
          '/backend/legacy-qr/' + encodeURIComponent(slugParam),
          window.location.origin
        ).toString();
      }
    } catch (err) {
      if (typeof console !== 'undefined' && typeof console.debug === 'function') {
        console.debug('short-link slug parse failed', err);
      }
    }

    if (!fetchUrl) {
      var link = document.querySelector('[data-permanent-short-link]');
      if (link) {
        fetchUrl = link.getAttribute('href');
      }
    }

    if (!fetchUrl) {
      return;
    }

    window.__snoutiqPermanentShortLinkPinged = true;

    try {
      fetch(fetchUrl, {
        method: 'GET',
        credentials: 'include',
        cache: 'no-store'
      }).catch(function(err) {
        if (typeof console !== 'undefined' && typeof console.debug === 'function') {
          console.debug('short-link ping failed', err);
        }
      });
    } catch (err) {
      if (typeof console !== 'undefined' && typeof console.debug === 'function') {
        console.debug('short-link ping error', err);
      }
    }
  })();
  </script>
  @endif

  <!-- JSON-LD -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "MedicalClinic",
    "name": "{{ $vet->name }}",
    "image": "{{ $vet->image ?? $snoutiqLogo }}",
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

  <!-- Buttons logic (askbar + AI FAB) -->
  <script>
  (function() {
    const vetSlug = @json($vet->slug);
    const clinicId = @json($vet->clinic_id ?? $vet->id ?? null);
    const downloadBtn = document.getElementById('download-app-btn');
    const appDownloadLink = 'https://play.google.com/store/apps/details?id=com.petai.snoutiq';
    const aiFeatureCta = document.getElementById('ai-feature-cta');
    const appModal = document.getElementById('app-download-modal');
    const appModalCloseEls = Array.from(document.querySelectorAll('[data-app-modal-close]'));

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

    const showAppModal = () => toggleModal(true);

    const sendAsk = (id) => {
      const v = (document.getElementById(id)?.value || '').trim();
      if (v) {
        try { sessionStorage.setItem('pendingChatQuestion', v); } catch (_) {}
      }
      showAppModal();
    };
    document.getElementById('clinic-ask-send')?.addEventListener('click', (e)=>{ e.preventDefault(); sendAsk('clinic-ask-input'); });
    document.getElementById('clinic-ask-input')?.addEventListener('keydown', (e)=>{ if (e.key==='Enter'){ e.preventDefault(); sendAsk('clinic-ask-input'); }});

    downloadBtn?.addEventListener('click', function(event) {
      event.preventDefault();
      triggerDownload();
    });
    aiFeatureCta?.addEventListener('click', function() { showAppModal(); });
    appModalCloseEls.forEach(function(btn){ btn.addEventListener('click', function(){ toggleModal(false); }); });
    document.addEventListener('keydown', function(e){
      if (e.key === 'Escape') toggleModal(false);
    });

    function triggerDownload() {
      if (!appDownloadLink) return;
      window.open(appDownloadLink, '_blank', 'noopener');
    }

    function toggleModal(show) {
      if (!appModal) return;
      appModal.style.display = show ? 'flex' : 'none';
    }
  })();
  </script>

  <!-- Dynamic Quick Services (fetch by slug) + Request modal + Payment -->
  <script>
  (function(){
    const vetSlug      = @json($vet->slug);
    const API_SERVICES = 'https://snoutiq.com/backend/api/groomer/services';
    // Payment endpoints (served from this Laravel app)
    const ORDER_URL    = @json(url('/api/create-order'));
    const VERIFY_URL   = @json(url('/api/rzp/verify'));
    const CLINIC_ID    = @json($vet->clinic_id ?? $vet->id ?? null);

    const grid    = document.getElementById('qsvc-grid');
    const empty   = document.getElementById('qsvc-empty');
    const loading = document.getElementById('qsvc-loading');

    const authToken = (window.SNOUTIQ && window.SNOUTIQ.token) || localStorage.getItem('token') || sessionStorage.getItem('token');

    const esc = s => (''+(s??'')).replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));
    const inr = n => Number.isFinite(Number(n)) ? Number(n).toFixed(0) : '—';

    function chip(svc){
      const btn = document.createElement('button');
      btn.className = 'qsvc-btn';
      const price = inr(svc.price);
      const dur   = svc.duration ? `${svc.duration}m` : '—';
      btn.innerHTML = `${esc(svc.name || 'Service')} ₹${price} <small style="font-weight:700;color:#64748b">• ${dur}</small>`;

      const isActive = (svc.status || '').toLowerCase() === 'active';
      if(!isActive){ btn.disabled = true; btn.title = 'Unavailable'; }
      btn.addEventListener('click', ()=> { if(isActive) openServiceModal(svc); });
      return btn;
    }

    async function fetchServices(){
      loading.style.display = '';
      grid.style.display = 'none';
      empty.style.display = 'none';

      try{
        const url = new URL(API_SERVICES);
        if (vetSlug) url.searchParams.set('vet_slug', vetSlug); // <- IMPORTANT

        const res  = await fetch(url.toString(), { headers: { 'Accept':'application/json' }});
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
      if(!items || !items.length){
        empty.textContent = 'No services available right now.';
        empty.style.display = '';
        grid.style.display = 'none';
        return;
      }
      items.slice(0,24).forEach(s => grid.appendChild(chip(s))); // show all, inactive disabled
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
      const desc  = (svc.description && String(svc.description).trim()) ? esc(svc.description) : 'No description provided.';

      Swal.fire({
        title: esc(svc.name || 'Service'),
        html: `
          <div style="text-align:left">
            ${formRow('Category', esc(cat))}
            ${formRow('Pet Type', esc(pet))}
            ${formRow('Duration', esc(dur))}
            ${formRow('Price', '₹' + esc(price))}
            <div style="margin:.5rem 0 .35rem;font-weight:800">Description</div>
            <div style="background:#f8fbff;border:1px solid #e5edff;border-radius:10px;padding:.6rem .7rem;color:#334155">${desc}</div>
            <div style="margin:.7rem 0 .3rem;font-weight:800">Notes (optional)</div>
            <textarea id="svc-notes" rows="3" style="width:100%;padding:.6rem .7rem;border-radius:10px;border:1px solid #e5edff;background:#fbfdff;outline:none"></textarea>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Request Service',
        cancelButtonText: 'Close',
        preConfirm: async ()=>{
          try{
            const notes = (document.getElementById('svc-notes')?.value || '').trim();
            await requestService(svc, notes);
            return { ok:true };
          }catch(err){
            Swal.showValidationMessage(err?.message || 'Failed to request service');
            return false;
          }
        }
      }).then((res)=>{
        if (res.isConfirmed){
          Swal.fire({icon:'success', title:'Payment successful', text:'Your request has been recorded.', timer:1800, showConfirmButton:false});
        }
      });
    }

    // Launch Razorpay checkout; resolve when payment verified and saved to DB
    async function requestService(svc, notes){
      if (typeof Razorpay === 'undefined') throw new Error('Payment library not loaded');

      // 1) Create order on server (amount in INR)
      const amountInInr = parseInt(String(svc.price || '0'), 10);
      if (!Number.isInteger(amountInInr) || amountInInr < 1) throw new Error('Invalid service price');

      const makeOrder = async () => {
        const res = await fetch(ORDER_URL, {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({
            amount: amountInInr,
            clinic_id: CLINIC_ID,
            order_type: 'service',
          })
        });
        if (!res.ok) throw new Error('Order create failed: ' + res.status);
        return res.json();
      };

      const orderRes = await makeOrder();
      if (!orderRes?.success || !orderRes?.order_id || !orderRes?.key) throw new Error('Invalid order response');

      // 2) Open Razorpay checkout and verify
      return new Promise((resolve, reject) => {
        const rzp = new Razorpay({
          key: orderRes.key,
          order_id: orderRes.order_id,
          name: 'SnoutIQ',
          description: (svc.name ? ('Service: ' + svc.name) : 'Clinic Service'),
          notes: {
            service_id: String(svc.id || ''),
            vet_slug: String(vetSlug || ''),
          },
          theme: { color: '#3b82f6' },
          handler: async function (resp) {
            try {
              const payload = {
                razorpay_order_id: resp.razorpay_order_id,
                razorpay_payment_id: resp.razorpay_payment_id,
                razorpay_signature: resp.razorpay_signature,
                vet_slug: String(vetSlug || ''),
                service_id: String(svc.id || ''),
                clinic_id: CLINIC_ID,
                order_type: 'service',
              };
              const vres = await fetch(VERIFY_URL, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
              });
              if (!vres.ok) throw new Error('Verify API failed: ' + vres.status);
              const vjson = await vres.json();
              if (!(vjson?.success || vjson?.verified)) throw new Error('Payment not verified');
              resolve(vjson);
            } catch (e) {
              reject(e);
            }
          }
        });
        rzp.on('payment.failed', function (resp) {
          reject(new Error(resp?.error?.description || 'Payment failed'));
        });
        rzp.open();
      });
    }

    document.addEventListener('DOMContentLoaded', fetchServices);

  })();
  </script>

  <!-- Floating AI button -->
  <button class="ai-fab" onclick="openChat()" aria-label="Open AI Assistant">🎩</button>

  <noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id=1909812439872823&ev=PageView&noscript=1"/>
  </noscript>
  @endunless
</body>
</html>
