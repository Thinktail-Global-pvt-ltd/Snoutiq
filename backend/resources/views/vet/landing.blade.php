<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $vet->name }} | SnoutIQ</title>
  <meta name="description" content="Book appointments with {{ $vet->name }}{{ $vet->clinic_profile ? ' ('.$vet->clinic_profile.')' : '' }}. Video consults, clinic visits, vaccinations and more."/>
  <link rel="icon" href="https://snoutiq.com/favicon.webp" sizes="32x32" type="image/png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

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
    /* ======== Dark Theme Base ======== */
    :root{
      --bg: #0b1220;           /* page background */
      --bg-2: #0f172a;         /* card background */
      --border: #1f2a44;       /* subtle borders */
      --muted: #94a3b8;        /* secondary text */
      --text: #e5e7eb;         /* primary text */
      --heading: #f8fafc;      /* headings */
      --accent: #60a5fa;       /* primary accent (blue-400) */
      --accent-2: #a78bfa;     /* secondary accent (violet-400) */
      --ring: rgba(96,165,250,.35);
      --star: #fbbf24;         /* amber-400 */
      --success: #34d399;      /* green-400 */
    }
    *{margin:0;padding:0;box-sizing:border-box}
    body{background:linear-gradient(180deg,#0b1220,#0b1220 40%,#0d1426 100%);color:var(--text);font-family:Inter,system-ui,Segoe UI,Roboto,Ubuntu,sans-serif;line-height:1.6}
    a{text-decoration:none;color:inherit}
    img{display:block;max-width:100%}
    .container{max-width:1120px;margin:0 auto;padding:1rem}
    .section{padding:3rem 0}
    @media(min-width:768px){.section{padding:4rem 0}}
    .card{background:var(--bg-2);border:1px solid var(--border);border-radius:1rem;box-shadow:0 1px 2px rgba(0,0,0,.25)}
    .pill{display:inline-flex;align-items:center;gap:.5rem;background:#0b1630;border:1px solid var(--border);color:var(--accent);border-radius:999px;padding:.35rem .7rem;font-size:.85rem;font-weight:600}
    .tag{display:inline-flex;align-items:center;gap:.5rem;background:#0b1630;border:1px solid var(--border);color:#7dd3fc;border-radius:999px;padding:.3rem .75rem;font-weight:700}
    .muted{color:var(--muted)}
    .heading{color:var(--heading)}
    .grid{display:grid;gap:1rem}
    @media(min-width:768px){.grid-2{grid-template-columns:repeat(2,1fr)}.grid-3{grid-template-columns:repeat(3,1fr)}}
    .btn{display:inline-flex;align-items:center;gap:.55rem;border:none;border-radius:.75rem;padding:.8rem 1.2rem;font-weight:700;cursor:pointer;transition:all .15s}
    .btn:focus{outline:2px solid var(--ring);outline-offset:2px}
    .btn-primary{background:linear-gradient(90deg,#2563eb,#7c3aed);color:#fff}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 12px 30px -10px rgba(99,102,241,.5)}
    .btn-outline{background:transparent;color:var(--accent);border:1px solid var(--border)}
    .btn-outline:hover{background:#0b1630}
    /* Nav */
    nav{position:sticky;top:0;z-index:50;background:rgba(11,18,32,.75);backdrop-filter:blur(8px);border-bottom:1px solid var(--border)}
    .nav-wrap{height:70px;display:flex;align-items:center;justify-content:space-between}
    .logo{display:flex;align-items:center;gap:.5rem;font-weight:800;color:var(--accent);cursor:pointer}
    .logo img{height:20px}
    /* Inputs (dark) */
    .input,.select,textarea{width:100%;padding:.85rem 1rem;border-radius:.75rem;border:1px solid var(--border);background:#0b1630;color:var(--text)}
    .input:focus,.select:focus,textarea:focus{outline:2px solid var(--ring);border-color:transparent}
    label{font-size:.9rem;color:var(--muted)}
    /* Footer */
    footer{border-top:1px solid var(--border);background:#0a1020}
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
        <button class="btn btn-primary" onclick="location.href='https://snoutiq.com/register?utm_source=vet-landing-dark'">Register</button>
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <header class="container section" style="text-align:center">
    <span class="tag">👩‍⚕️ Vet Profile • {{ $vet->business_status === 'OPERATIONAL' ? 'Verified' : 'Profile' }}</span>
    <h1 class="heading" style="font-size:2.25rem;margin:.9rem 0">
      {{ $vet->name }}
      @if($vet->clinic_profile || $vet->hospital_profile)
        <span class="muted" style="font-weight:500">| {{ $vet->clinic_profile ?? $vet->hospital_profile }}</span>
      @endif
    </h1>
    <p class="muted" style="max-width:760px;margin:0 auto 1.25rem">
      {{ $vet->license_no ? ('License: '.$vet->license_no.' • ') : '' }}
      {{ $vet->city ?? '—' }}
      @if($vet->pincode) ({{ $vet->pincode }}) @endif
      • {{ $vet->formatted_address ?? $vet->address ?? 'Clinic address' }}
      • Video consults available
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
        <div style="font-size:1.4rem;font-weight:800;color:var(--success)">{{ number_format((float)($vet->rating ?? 4.9), 1) }}★</div>
        <div class="muted" style="font-size:.95rem">{{ $vet->user_ratings_total ?? 0 }} pet parent reviews</div>
      </div>
      <div class="card" style="padding:1.1rem;text-align:center">
        <div style="font-size:1.4rem;font-weight:800;color:var(--accent)">24–48h</div>
        <div class="muted" style="font-size:.95rem">Avg. follow-up response</div>
      </div>
      <div class="card" style="padding:1.1rem;text-align:center">
        <div style="font-size:1.4rem;font-weight:800;color:var(--accent-2)">Same Day</div>
        <div class="muted" style="font-size:.95rem">Vaccination & Deworming</div>
      </div>
    </div>
  </section>

  <!-- About -->
  <section class="container section" id="about">
    <div class="grid grid-2">
      <div class="card" style="padding:1.25rem">
        <h2 class="heading">About {{ $vet->name }}</h2>
        <p class="muted" style="margin-top:.6rem">
          {{ $vet->bio ?? 'Small-animal veterinarian with focus on preventive care. Evidence-based medicine & low-stress handling.' }}
        </p>
        <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
          @if($vet->license_no)<span class="pill">VCI Registered</span>@endif
          <span class="pill">Tele-vet</span>
          <span class="pill">Fear-Free</span>
        </div>
        <div style="margin-top:1rem">
          <div class="muted"><strong class="heading" style="font-size:1rem">Languages:</strong> English, Hindi</div>
          <div class="muted"><strong class="heading" style="font-size:1rem">Clinic:</strong> {{ $vet->formatted_address ?? $vet->address ?? ($vet->city ?? '—') }}</div>
        </div>
      </div>
      <div class="card" style="padding:0;overflow:hidden">
        <img src="{{ $vet->image ?: 'https://images.unsplash.com/photo-1550831107-1553da8c8464?q=80&w=1200&auto=format&fit=crop' }}" alt="Vet" style="width:100%;height:100%;object-fit:cover;opacity:.95"/>
      </div>
    </div>
  </section>

  <!-- Services -->
  <section class="container section" id="services">
    <h2 class="heading">Services</h2>
    <div class="grid grid-3" style="margin-top:1rem">
      <div class="card" style="padding:1rem">
        <div class="pill" style="margin-bottom:.7rem"><i class="fa-solid fa-stethoscope"></i>&nbsp; General Consultation</div>
        <div class="muted">History, exam, initial plan</div>
      </div>
      <div class="card" style="padding:1rem">
        <div class="pill" style="margin-bottom:.7rem"><i class="fa-solid fa-syringe"></i>&nbsp; Vaccination & Deworming</div>
        <div class="muted">Core & lifestyle vaccines, schedule</div>
      </div>
      <div class="card" style="padding:1rem">
        <div class="pill" style="margin-bottom:.7rem"><i class="fa-solid fa-notes-medical"></i>&nbsp; Surgery Consultation</div>
        <div class="muted">Spay/neuter, soft-tissue, pre-op check</div>
      </div>
      <div class="card" style="padding:1rem">
        <div class="pill" style="margin-bottom:.7rem"><i class="fa-solid fa-paw"></i>&nbsp; Dermatology</div>
        <div class="muted">Allergies, itching, skin infections</div>
      </div>
      <div class="card" style="padding:1rem">
        <div class="pill" style="margin-bottom:.7rem"><i class="fa-solid fa-bone"></i>&nbsp; Nutrition & Weight</div>
        <div class="muted">Diet plans, weight management</div>
      </div>
      <div class="card" style="padding:1rem">
        <div class="pill" style="margin-bottom:.7rem"><i class="fa-solid fa-video"></i>&nbsp; Video Consult</div>
        <div class="muted">Follow-ups & minor issues online</div>
      </div>
    </div>
  </section>

  <!-- Pricing -->
  <section class="container section" id="pricing">
    <h2 class="heading">Fees</h2>
    <div class="grid grid-3" style="margin-top:1rem">
      <div class="card" style="padding:1rem;text-align:center">
        <div style="font-weight:800;font-size:1.15rem" class="heading">Clinic Consult</div>
        <div class="muted" style="margin:.25rem 0">₹{{ (int) $clinicFee }}</div>
        <button class="btn btn-primary" onclick="document.getElementById('book').scrollIntoView({behavior:'smooth'})">Book</button>
      </div>
      <div class="card" style="padding:1rem;text-align:center">
        <div style="font-weight:800;font-size:1.15rem" class="heading">Video Consult</div>
        <div class="muted" style="margin:.25rem 0">₹{{ (int) $videoFee }}</div>
        <a class="btn btn-outline" href="/video?vet_slug={{ $vet->slug }}"><i class="fa-solid fa-video"></i> Start</a>
      </div>
      <div class="card" style="padding:1rem;text-align:center">
        <div style="font-weight:800;font-size:1.15rem" class="heading">Vaccination</div>
        <div class="muted" style="margin:.25rem 0">From ₹699</div>
        @if($vet->mobile)
          <button class="btn btn-outline" onclick="location.href='tel:{{ $vet->mobile }}'"><i class="fa-solid fa-phone"></i> Call</button>
        @endif
      </div>
    </div>
  </section>

  <!-- Reviews -->
  <section class="container section" id="reviews">
    <h2 class="heading">Pet Parent Reviews</h2>
    <div class="grid grid-3" style="margin-top:1rem">
      <div class="card" style="padding:1rem">
        <div style="color:var(--star)">★★★★★</div>
        <p class="muted" style="margin-top:.5rem">Very patient with my anxious indie. Clear plan and follow-up.</p>
        <div class="muted" style="margin-top:.5rem">— A. Singh</div>
      </div>
      <div class="card" style="padding:1rem">
        <div style="color:var(--star)">★★★★★</div>
        <p class="muted" style="margin-top:.5rem">Helped our lab with chronic skin issues. Big relief!</p>
        <div class="muted" style="margin-top:.5rem">— R. Mehta</div>
      </div>
      <div class="card" style="padding:1rem">
        <div style="color:var(--star)">★★★★☆</div>
        <p class="muted" style="margin-top:.5rem">Video consult was smooth and to the point.</p>
        <div class="muted" style="margin-top:.5rem">— S. Verma</div>
      </div>
    </div>
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
        <div style="grid-column:1/-1">
          <label for="issue">Concern</label>
          <textarea id="issue" name="issue" rows="3" class="input" placeholder="Briefly describe what's going on…"></textarea>
        </div>
        <input type="hidden" name="vet_slug" value="{{ $vet->slug }}"/>
        <div style="grid-column:1/-1;display:flex;gap:.75rem;flex-wrap:wrap">
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-arrow-right"></i> Continue</button>
          @if($phoneDigits)
            <a class="btn btn-outline" href="https://wa.me/{{ $phoneDigits }}?text={{ urlencode('Hi I want to book '.$vet->name) }}"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
          @endif
        </div>
      </form>
      <p class="muted" style="margin-top:.75rem">Already a user? <a href="https://snoutiq.com/login" style="color:var(--accent)">Login</a> for faster booking.</p>
    </div>
  </section>

  <!-- Clinic & Map -->
  <section class="container section" id="clinic">
    <h2 class="heading">Clinic Location</h2>
    <div class="card" style="padding:1rem;margin-top:1rem">
      <div class="muted"><strong class="heading" style="font-size:1rem">Address:</strong> {{ $vet->formatted_address ?? $vet->address ?? '—' }}</div>
      <div class="muted"><strong class="heading" style="font-size:1rem">Hours:</strong> Mon–Sat 10:00–18:00 • Sun closed</div>
      <div style="margin-top:1rem;overflow:hidden;border-radius:.75rem;border:1px solid var(--border)">
        <iframe
          src="https://maps.google.com/maps?q={{ urlencode($mapQuery) }}&t=&z=13&ie=UTF8&iwloc=&output=embed"
          width="100%" height="300" style="border:0;filter:grayscale(20%) brightness(80%)"
          loading="lazy" referrerpolicy="no-referrer-when-downgrade">
        </iframe>
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
            Vet landing powered by SnoutIQ. Book clinic & video consultations seamlessly.
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

  <!-- SEO: JSON-LD -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "VeterinaryCare",
    "name": "{{ $vet->name }}",
    "image": "{{ $vet->image ?? 'https://images.unsplash.com/photo-1550831107-1553da8c8464' }}",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "{{ $vet->formatted_address ?? $vet->address ?? '' }}",
      "addressLocality": "{{ $vet->city ?? '' }}",
      "postalCode": "{{ $vet->pincode ?? '' }}",
      "addressCountry": "IN"
    },
    "telephone": "{{ $vet->mobile ?? '' }}",
    "areaServed": "{{ $vet->city ?? 'NCR' }}",
    "openingHours": "Mo-Sa 10:00-18:00",
    "url": "{{ url('/backend/vet/'.$vet->slug) }}",
    "makesOffer": [
      {"@type":"Offer","name":"Clinic Consultation","priceCurrency":"INR","price":"{{ (int) $clinicFee }}"},
      {"@type":"Offer","name":"Video Consultation","priceCurrency":"INR","price":"{{ (int) $videoFee }}"}
    ],
    "sameAs": [
      "https://www.facebook.com/people/Snoutiq/61578226867078/",
      "https://www.instagram.com/snoutiq_marketplace/"
    ],
    "aggregateRating": {
      "@type": "AggregateRating",
      "ratingValue": "{{ number_format((float)($vet->rating ?? 4.9), 1) }}",
      "reviewCount": "{{ (int) ($vet->user_ratings_total ?? 0) }}"
    }
  }
  </script>

  <!-- Hook for your existing socket.io flow (kept as link for now) -->
  <script>
    // document.getElementById('video-consult-btn')?.addEventListener('click', (e) => {
    //   e.preventDefault();
    //   // init socket.io here...
    // });
  </script>

  <noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id=1909812439872823&ev=PageView&noscript=1"/>
  </noscript>
</body>
</html>
