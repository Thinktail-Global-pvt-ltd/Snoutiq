<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SnoutIQ — Prompt engineering testing phase v2</title>

  <!-- Tailwind (CDN). Fine for quick deploy; for long-term prod, compile locally. -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { inter: ['Inter','ui-sans-serif','system-ui'] },
          colors: { ink: { 900:'#0b1020', 800:'#10172b', 700:'#131a3a' } },
          boxShadow: { glass:'0 10px 25px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.04)' }
        }
      }
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <style>
    .grain:before{content:"";position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.06) 1px,transparent 1px);background-size:2px 2px;opacity:.25;pointer-events:none}
    #chat { scroll-behavior: smooth; }
  </style>
</head>
<body class="font-inter bg-ink-900 text-white antialiased min-h-screen relative">
  <div class="pointer-events-none absolute -top-40 left-1/2 -translate-x-1/2 h-[28rem] w-[60rem] rounded-full blur-3xl opacity-25 bg-gradient-to-r from-cyan-500 to-blue-600"></div>

  <header class="relative">
    <nav class="mx-auto max-w-6xl px-4 sm:px-6 py-4 flex items-center justify-between">
      <a href="#" class="flex items-center gap-2">
        <span class="text-xl font-extrabold tracking-wide">SN<span class="text-cyan-400">◦</span>TIQ</span>
      </a>
      <div class="flex items-center gap-3">
        <a class="px-4 py-2 rounded-xl border border-white/10 hover:border-white/20 bg-white/5 hover:bg-white/10 transition">Register</a>
        <a class="px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 transition shadow-lg shadow-cyan-900/30">Login</a>
      </div>
    </nav>
  </header>

  <main class="relative">
    <section class="mx-auto max-w-5xl px-4 sm:px-6 pt-8 sm:pt-12 pb-6 text-center">
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-sm text-white/80 mb-6">
        <span>🧪</span><span class="font-semibold">SnoutIQ Prompt engineering testing phase v2</span>
      </div>

      <h1 class="text-3xl sm:text-5xl md:text-6xl font-extrabold leading-tight tracking-tight">
        SnoutIQ – Your AI Pet Companion for
        <span class="block mt-2 text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">Smart Pet Care</span>
      </h1>

      <p class="mt-5 text-lg sm:text-xl text-white/70 max-w-3xl mx-auto">
        Intelligent pet care guidance, health advice, and training tips powered by advanced AI technology.
      </p>

      <div class="mt-10 flex justify-center">
        <div class="w-full max-w-3xl">
          <div class="relative group grain rounded-2xl bg-ink-800/70 backdrop-blur border border-white/10 shadow-glass">
            <div class="flex items-center">
              <div class="pl-4 sm:pl-5 py-3.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white/60 group-focus-within:text-white" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 14a3 3 0 0 0 3-3V6a3 3 0 1 0-6 0v5a3 3 0 0 0 3 3z"/>
                  <path d="M19 11a1 1 0 1 0-2 0 5 5 0 0 1-10 0 1 1 0 1 0-2 0 7 7 0 0 0 6 6.92V20H8a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2h-3v-2.08A7 7 0 0 0 19 11z"/>
                </svg>
              </div>

              <input id="prompt" type="text"
                     placeholder="Ask anything about your pet"
                     class="w-full bg-transparent outline-none text-base sm:text-lg text-white placeholder-white/40 py-3.5 pr-24" />

              <input id="image" type="file" accept="image/*" class="hidden" />
              <button id="attachBtn" type="button" title="Attach image"
                      class="absolute right-14 top-1/2 -translate-y-1/2 h-9 w-9 rounded-full flex items-center justify-center border border-white/10 bg-white/5 hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-width="2" d="M16.5 6.75v8.25a4.5 4.5 0 1 1-9 0V5.25a3 3 0 1 1 6 0v8.25a1.5 1.5 0 0 1-3 0V6.75"/>
                </svg>
              </button>

              <button id="sendBtn" type="button" title="Send"
                      class="absolute right-2 top-1/2 -translate-y-1/2 h-10 w-10 rounded-full flex items-center justify-center
                             bg-gradient-to-br from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500
                             shadow-lg shadow-cyan-900/40 focus:ring-2 ring-cyan-400/60 transition">
                <svg id="sendIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M11.48 3.499a1 1 0 0 1 1.04 0l8.66 5.143a1 1 0 0 1 0 1.716l-8.66 5.143a1 1 0 0 1-1.52-.858V4.357a1 1 0 0 1 .48-.858z"/>
                </svg>
                <svg id="spinner" class="hidden animate-spin h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
              </button>
            </div>
          </div>
          <div id="helper" class="mt-3 text-sm text-white/50">
            Ask anything about your pet’s health, behavior, or training
          </div>
          <div id="statusLine" class="mt-2 text-sm text-white/60"></div>
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 sm:px-6 pb-16">
      <div id="chat" class="rounded-2xl border border-white/10 bg-ink-800/60 backdrop-blur shadow-glass p-4 sm:p-6 min-h-[220px]">
        <div class="text-center text-white/50 py-12">🚀 Ready — start typing above.</div>
      </div>
    </section>
  </main>

  <script>
    const API_ENDPOINT = '/api/unified/process';
    const DEFAULT_PROFILE = {
      pet_name:   'Max',
      pet_breed:  'Mixed Breed',
      pet_age:    '3 years',
      pet_weight: '12 kg',
      location:   'Delhi'
    };

    const SESSION_KEY = 'snoutiq_session_id';
    let sessionId = localStorage.getItem(SESSION_KEY);
    if (!sessionId) {
      sessionId = (Math.random().toString(36).slice(2) + Date.now().toString(36));
      localStorage.setItem(SESSION_KEY, sessionId);
    }

    const promptEl = document.getElementById('prompt');
    const sendBtn  = document.getElementById('sendBtn');
    const sendIcon = document.getElementById('sendIcon');
    const spinner  = document.getElementById('spinner');
    const attachBtn= document.getElementById('attachBtn');
    const fileEl   = document.getElementById('image');
    const chatEl   = document.getElementById('chat');
    const helperEl = document.getElementById('helper');
    const statusEl = document.getElementById('statusLine');

    attachBtn.addEventListener('click', () => fileEl.click());

    let isSending = false;

    async function sendMessage() {
      if (isSending) return;
      const message = (promptEl.value || '').trim();
      if (!message) return;

      isSending = true;
      toggleSending(true);
      smoothScrollToChatEnd();

      const fd = new FormData();
      fd.append('session_id', sessionId);
      fd.append('message', message);
      fd.append('pet_name',   DEFAULT_PROFILE.pet_name);
      fd.append('pet_breed',  DEFAULT_PROFILE.pet_breed);
      fd.append('pet_age',    DEFAULT_PROFILE.pet_age);
      fd.append('pet_weight', DEFAULT_PROFILE.pet_weight);
      fd.append('location',   DEFAULT_PROFILE.location);
      if (fileEl.files && fileEl.files[0]) {
        if (fileEl.files[0].size > 6 * 1024 * 1024) {
          statusEl.textContent = 'Image must be ≤ 6MB';
          isSending = false; toggleSending(false); return;
        }
        fd.append('image', fileEl.files[0]);
      }

      try {
        statusEl.textContent = 'Processing…';

        // Force JSON; safely handle HTML error pages
        const res = await fetch(API_ENDPOINT, {
          method: 'POST',
          body: fd,
          headers: { 'Accept': 'application/json' }
        });

        const raw = await res.text();    // read as text first
        let data;
        try {
          data = JSON.parse(raw);
        } catch {
          console.error('Non-JSON response', { status: res.status, raw });
          throw new Error(`HTTP ${res.status}: ${raw.slice(0,200)}`);
        }

        if (!res.ok || data.success === false) {
          throw new Error(data.message || `HTTP ${res.status}`);
        }

        ensureChatContainer();
        chatEl.insertAdjacentHTML('beforeend', data.conversation_html);
        smoothScrollToChatEnd();

        statusEl.textContent = data.status_text || '';
        helperEl.textContent = 'Session: ' + (data.session_id || sessionId);

      } catch (err) {
        statusEl.textContent = 'Error: ' + (err.message || err);
        addAiErrorBubble('AI error. Please try again.');
      } finally {
        promptEl.value = '';
        fileEl.value = '';
        isSending = false;
        toggleSending(false);
      }
    }

    function toggleSending(sending) {
      sendBtn.disabled = sending;
      if (sending) {
        sendIcon.classList.add('hidden');
        spinner.classList.remove('hidden');
      } else {
        spinner.classList.add('hidden');
        sendIcon.classList.remove('hidden');
      }
    }

    function addAiErrorBubble(text) {
      const ts = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
      const html =
        "<div style='display:flex;justify-content:flex-start;margin:15px 0;'>"+
          "<div style='background:white;border:2px solid #f0f0f0;padding:15px 20px;border-radius:20px 20px 20px 5px;max-width:75%;box-shadow:0 4px 15px rgba(0,0,0,.1);color:#111;'>"+
            "<div style='font-size:12px;color:#666;margin-bottom:8px;'>🔍 PetPal AI • "+ts+"</div>"+
            "<div style='line-height:1.6;color:#333;'>"+escapeHtml(text)+"</div>"+
          "</div>"+
        "</div>";
      ensureChatContainer();
      chatEl.insertAdjacentHTML('beforeend', html);
      smoothScrollToChatEnd();
    }

    function ensureChatContainer() {
      if (chatEl.textContent.includes('Ready — start typing')) chatEl.innerHTML = '';
    }

    function smoothScrollToChatEnd() {
      requestAnimationFrame(() => {
        chatEl.scrollTop = chatEl.scrollHeight;
        chatEl.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'end' });
      });
    }

    function escapeHtml(s){return s.replace(/[&<>"']/g,m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[m]))}

    sendBtn.addEventListener('click', sendMessage);
    promptEl.addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ e.preventDefault(); sendMessage(); }});
  </script>
</body>
</html>
