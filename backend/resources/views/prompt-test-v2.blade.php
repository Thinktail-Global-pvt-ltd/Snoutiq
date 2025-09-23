<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>SnoutIQ — Prompt engineering testing phase v2</title>

  <!-- API endpoint (works on local + prod) -->
  <meta name="api-endpoint" content="{{ url('/api/unified/process') }}">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme:{extend:{colors:{ink:{900:'#0b1020',800:'#10172b'}},boxShadow:{glass:'0 10px 25px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.04)'}}}
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    body{font-family:Inter,ui-sans-serif,system-ui}
    #chat{scroll-behavior:smooth}
    .chip{background:#0f172a;border:1px solid #334155;color:#e5e7eb;border-radius:.5rem;padding:.35rem .6rem;font-size:.75rem}
  </style>
</head>
<body class="bg-ink-900 text-white min-h-screen">
  <!-- Top -->
  <header class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
    <div class="font-extrabold tracking-wide">SN<span class="text-cyan-400">◦</span>TIQ</div>
    <div class="flex gap-2">
      <a class="px-4 py-2 rounded-xl border border-white/10 bg-white/5">Register</a>
      <a class="px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600">Login</a>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4">
    <!-- Hero -->
    <div class="text-center mt-6">
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-sm text-white/80">
        🧪 <span class="font-semibold">SnoutIQ Prompt engineering testing phase v2</span>
      </div>
      <h1 class="text-4xl sm:text-5xl font-extrabold mt-4">SnoutIQ – Your AI Pet Companion for
        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 mt-1">Smart Pet Care</span>
      </h1>
      <p class="text-white/70 mt-4">Intelligent pet care guidance, health advice, and training tips powered by advanced AI technology.</p>
    </div>

    <!-- Pet Profile (breed is now TEXT input) -->
    <section class="mt-8 rounded-2xl border border-white/10 bg-ink-800/70 shadow-glass p-4">
      <h3 class="text-white/90 font-semibold mb-3">🐕 Pet Profile</h3>
      <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
        <input id="pet_name"   class="bg-white/5 border border-white/10 rounded-xl p-3 outline-none"
               placeholder="Name (e.g., Max)" value="Max">
        <input id="pet_breed"  class="bg-white/5 border border-white/10 rounded-xl p-3 outline-none"
               placeholder="Breed (e.g., Pug)" value="Pug">
        <input id="pet_age"    class="bg-white/5 border border-white/10 rounded-xl p-3 outline-none"
               placeholder="Age (e.g., 3 years)" value="3 years">
        <input id="pet_weight" class="bg-white/5 border border-white/10 rounded-xl p-3 outline-none"
               placeholder="Weight (e.g., 12 kg)" value="12 kg">
        <select id="location"  class="bg-white/5 border border-white/10 rounded-xl p-3 outline-none">
          <option>Delhi</option><option>Mumbai</option><option>Bangalore</option>
          <option>Chennai</option><option>Hyderabad</option><option>Other</option>
        </select>
      </div>
      <div class="mt-3 flex items-center gap-2">
        <button id="newChatBtn" class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/15 border border-white/10">🔄 New chat</button>
        <span id="chips" class="text-sm text-white/70"></span>
      </div>
    </section>

    <!-- Composer -->
    <section class="mt-6 relative z-10">
      <div class="relative rounded-2xl bg-ink-800/70 border border-white/10 shadow-glass">
        <div class="flex items-center">
          <div class="pl-4">
            <svg class="h-5 w-5 text-white/70" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14a3 3 0 003-3V6a3 3 0 10-6 0v5a3 3 0 003 3z"/><path d="M19 11a1 1 0 10-2 0 5 5 0 01-10 0 1 1 0 10-2 0 7 7 0 006 6.92V20H8a1 1 0 100 2h8a1 1 0 100-2h-3v-2.08A7 7 0 0019 11z"/></svg>
          </div>
          <input id="prompt" type="text" placeholder="Ask anything about your pet"
                 class="w-full bg-transparent text-white placeholder-white/40 p-4 pr-28 outline-none">
          <input id="image" type="file" accept="image/*" class="hidden">
          <button id="attachBtn" title="Attach image"
                  class="absolute right-14 top-1/2 -translate-y-1/2 h-9 w-9 rounded-full border border-white/10 bg-white/5 hover:bg-white/10">📎</button>
          <button id="sendBtn" title="Send"
                  class="absolute right-2 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600">▶</button>
        </div>
      </div>
      <div id="helper" class="text-sm text-white/50 mt-2">Ask anything about your pet’s health, behavior, or training</div>
      <div id="statusLine" class="text-sm text-white/60 mt-1"></div>
    </section>

    <!-- Chat -->
    <section class="mt-6 mb-16">
      <div id="chat" class="rounded-2xl border border-white/10 bg-ink-800/60 shadow-glass p-4 min-h-[220px]">
        <div class="text-center text-white/50 py-10">🚀 Ready — start typing above.</div>
      </div>
    </section>
  </main>

  <script>
    const API_ENDPOINT = document.querySelector('meta[name="api-endpoint"]').content;

    // Session
    const SESSION_KEY = 'snoutiq_session_id';
    let sessionId = localStorage.getItem(SESSION_KEY) || (Math.random().toString(36).slice(2) + Date.now().toString(36));
    localStorage.setItem(SESSION_KEY, sessionId);

    // refs
    const chatEl   = document.getElementById('chat');
    const promptEl = document.getElementById('prompt');
    const fileEl   = document.getElementById('image');
    const sendBtn  = document.getElementById('sendBtn');
    const attachBtn= document.getElementById('attachBtn');
    const statusEl = document.getElementById('statusLine');
    const chipsEl  = document.getElementById('chips');

    // profile inputs (breed now text)
    const nameEl   = document.getElementById('pet_name');
    const breedEl  = document.getElementById('pet_breed');
    const ageEl    = document.getElementById('pet_age');
    const weightEl = document.getElementById('pet_weight');
    const locEl    = document.getElementById('location');

    // new chat
    document.getElementById('newChatBtn').addEventListener('click', () => {
      sessionId = (Math.random().toString(36).slice(2) + Date.now().toString(36));
      localStorage.setItem(SESSION_KEY, sessionId);
      chatEl.innerHTML = '<div class="text-center text-white/50 py-10">🆕 New chat — start typing.</div>';
      statusEl.textContent = '';
      renderChips();
    });

    attachBtn.addEventListener('click', () => fileEl.click());
    promptEl.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); sendMessage(); }});
    sendBtn.addEventListener('click', sendMessage);

    renderChips();

    async function sendMessage() {
      const message = (promptEl.value || '').trim();
      if (!message) return;

      const fd = new FormData();
      fd.append('session_id', sessionId);
      fd.append('message', message);

      // dynamic profile
      fd.append('pet_name',   nameEl.value || '');
      fd.append('pet_breed',  breedEl.value || '');   // <-- text input now
      fd.append('pet_age',    ageEl.value || '');
      fd.append('pet_weight', weightEl.value || '');
      fd.append('location',   locEl.value || '');

      if (fileEl.files[0]) {
        if (fileEl.files[0].size > 6*1024*1024) { statusEl.textContent='Image must be ≤ 6MB'; return; }
        fd.append('image', fileEl.files[0]);
      }

      statusEl.textContent = 'Processing…';
      sendBtn.disabled = true;

      try {
        const res  = await fetch(API_ENDPOINT, { method:'POST', body:fd, headers:{'Accept':'application/json'} });
        const body = await res.text();
        const ct   = (res.headers.get('content-type')||'').toLowerCase();

        if (!ct.includes('application/json')) {
          throw new Error(`API returned non-JSON (${ct || 'HTML'}) — check endpoint. Title: ${(body.match(/<title[^>]*>(.*?)<\/title>/i)||[])[1]||'n/a'}`);
        }
        const data = JSON.parse(body);
        if (!res.ok || data.success === false) throw new Error(data.message || `HTTP ${res.status}`);

        if (chatEl.textContent.includes('Ready') || chatEl.textContent.includes('New chat')) chatEl.innerHTML = '';
        chatEl.insertAdjacentHTML('beforeend', data.conversation_html);
        chatEl.lastElementChild?.scrollIntoView({behavior:'smooth', block:'end'});
        statusEl.textContent = data.status_text || '';
        renderChips();
      } catch (err) {
        statusEl.innerHTML = `<span class="chip">Error:</span> ${escapeHtml(err.message)}`;
        addAiErrorBubble('AI error. Please try again.');
        console.error(err);
      } finally {
        promptEl.value = '';
        fileEl.value = '';
        sendBtn.disabled = false;
      }
    }

    function renderChips(){
      chipsEl.innerHTML =
        `Session: <span class="chip">${escapeHtml(sessionId)}</span> `+
        `API: <span class="chip">${escapeHtml(API_ENDPOINT)}</span>`;
    }

    function addAiErrorBubble(text){
      const ts = new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
      const html =
        "<div style='display:flex;justify-content:flex-start;margin:15px 0;'>"+
        "<div style='background:white;border:2px solid #f0f0f0;padding:15px 20px;border-radius:20px 20px 20px 5px;max-width:75%;box-shadow:0 4px 15px rgba(0,0,0,.1);color:#111;'>"+
        "<div style='font-size:12px;color:#666;margin-bottom:8px;'>🔍 PetPal AI • "+ts+"</div>"+
        "<div style='line-height:1.6;color:#333;'>"+escapeHtml(text)+"</div>"+
        "</div></div>";
      if (chatEl.textContent.includes('Ready')) chatEl.innerHTML = '';
      chatEl.insertAdjacentHTML('beforeend', html);
      chatEl.lastElementChild?.scrollIntoView({behavior:'smooth', block:'end'});
    }

    function escapeHtml(s){return (s||'').replace(/[&<>"']/g,m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[m]))}
  </script>
</body>
</html>
