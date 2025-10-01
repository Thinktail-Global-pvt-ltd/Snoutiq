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
    /* your CSS is untouched, already correct */
    /* ======== Light Theme ======== */
    :root{--bg:#cfe1ff;--bg-2:#ffffff;--border:#d1ddff;--muted:#55607a;--text:#0f172a;--heading:#0b1220;--accent:#2563eb;--accent-2:#0ea5e9;--ring:rgba(37,99,235,.25);--success:#059669;--sky1:#d7e6ff;--sky2:#d7e6ff;}
    *{margin:0;padding:0;box-sizing:border-box}
    body{background:radial-gradient(1000px 500px at 20% -20%,var(--sky1),transparent 60%),radial-gradient(1000px 500px at 120% 10%,var(--sky1),transparent 60%),linear-gradient(180deg,var(--sky2),var(--bg));color:var(--text);font-family:Inter,system-ui,Segoe UI,Roboto,Ubuntu,sans-serif;line-height:1.6}
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
    /* other styles unchanged … */
    .ai-fab{position:fixed;right:16px;bottom:16px;border:none;border-radius:999px;width:54px;height:54px;display:grid;place-items:center;font-size:22px;background:linear-gradient(90deg,#3b82f6,#06b6d4);color:#fff;box-shadow:0 14px 30px -10px rgba(59,130,246,.45);cursor:pointer}
    .ai-fab:focus{outline:2px solid var(--ring);outline-offset:2px}
  </style>
</head>
<body>

  {{-- The HTML layout (nav, header, askbar, services, reels, reviews, offers, stats, doctors, location, footer) remains as in your file --}}

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

  <!-- Buttons logic -->
  <script>
  (function() {
    const vetSlug = @json($vet->slug);

    function loginRedirect(prefill = "") {
      try { if (prefill) localStorage.setItem("pendingChatQuestion", prefill); } catch(_) {}
      const params = new URLSearchParams({ next: location.href, source: "chat", vet_slug: vetSlug || "" });
      if (prefill) params.set("prefill", prefill);
      location.href = `backend/custom-doctor-register?${params.toString()}`;
    }

    function openChat(){
      const params = new URLSearchParams({ vet_slug: vetSlug || '' });
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

  <!-- Dynamic Quick Services -->
  <script>
  (function(){
    const vetSlug     = @json($vet->slug);
    const API_SERVICES= 'https://snoutiq.com/backend/api/groomer/services';
    const API_REQUEST = 'https://snoutiq.com/backend/api/service-requests';

    const grid    = document.getElementById('qsvc-grid');
    const empty   = document.getElementById('qsvc-empty');
    const loading = document.getElementById('qsvc-loading');

    const authToken = (window.SNOUTIQ && window.SNOUTIQ.token) || localStorage.getItem('token') || sessionStorage.getItem('token');

    function resolveUserId(){
      if (window.SNOUTIQ?.user?.id) return Number(window.SNOUTIQ.user.id);
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
      btn.innerHTML = `${service.name || 'Service'} ₹${inr(service.price)}`;
      btn.addEventListener('click', ()=> openServiceModal(service));
      return btn;
    }

    async function fetchServices(){
      loading.style.display = '';
      grid.style.display = 'none';
      empty.style.display = 'none';

      try{
        const url = new URL(API_SERVICES);
        if (vetSlug) url.searchParams.set('vet_slug', vetSlug);
        if (USER_ID) url.searchParams.set('user_id', String(USER_ID));

        const res = await fetch(url.toString(), {
          headers: { 'Accept':'application/json' }
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
      return `<div style="display:flex;justify-content:space-between;gap:12px;margin:.25rem 0">
                <div style="color:#64748b">${label}</div>
                <div style="font-weight:700">${value || '—'}</div>
              </div>`;
    }

    function openServiceModal(svc){
      Swal.fire({
        title: svc.name || 'Service',
        html: `
          <div style="text-align:left">
            ${formRow('Category', svc.main_service)}
            ${formRow('Pet Type', svc.pet_type)}
            ${formRow('Duration', svc.duration ? svc.duration + ' mins' : '—')}
            ${formRow('Price', '₹' + inr(svc.price))}
            <div style="margin:.5rem 0;font-weight:800">Description</div>
            <div style="background:#f8fbff;border:1px solid #e5edff;border-radius:10px;padding:.6rem .7rem;color:#334155">${svc.description || 'No description provided.'}</div>
            <div style="margin:.7rem 0;font-weight:800">Notes (optional)</div>
            <textarea id="svc-notes" rows="3" style="width:100%;padding:.6rem .7rem;border-radius:10px;border:1px solid #e5edff;background:#fbfdff;"></textarea>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Request Service',
        cancelButtonText: 'Close',
        preConfirm: async ()=> {
          const notes = (document.getElementById('svc-notes')?.value || '').trim();
          await requestService(svc, notes);
        }
      }).then((res)=>{ if (res.isConfirmed) Swal.fire({icon:'success', title:'Request sent', text:'We will contact you shortly.', timer:1600, showConfirmButton:false}); });
    }

    async function requestService(svc, notes){
      const payload = { service_id: svc.id, vet_slug: vetSlug || '', notes: notes || '', user_id: USER_ID || null };
      const headers = { 'Accept':'application/json', 'Content-Type':'application/json' };
      if (authToken) headers['Authorization'] = 'Bearer ' + authToken;
      const resp = await fetch(API_REQUEST, { method:'POST', headers, body: JSON.stringify(payload), credentials:'include' });
      if (!resp.ok) throw new Error((await resp.json()).message || 'Failed');
    }

    document.addEventListener('DOMContentLoaded', fetchServices);
  })();
  </script>

  <!-- Floating AI button -->
  <button class="ai-fab" onclick="openChat()" aria-label="Open AI Assistant">🎩</button>

</body>
</html>
