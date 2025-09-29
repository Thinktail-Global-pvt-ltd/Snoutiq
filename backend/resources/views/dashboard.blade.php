<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard | Test Clinic</title>
  <link rel="icon" href="https://snoutiq.com/favicon.webp" type="image/png"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-50">

  <!-- Navbar -->
  <nav class="fixed top-0 w-full bg-white border-b border-gray-200 shadow-sm z-50">
    <div class="max-w-7xl mx-auto px-6 py-3 flex justify-between items-center">
      <div class="flex items-center gap-2 font-bold text-xl text-blue-600">
        <img src="https://snoutiq.com/favicon.webp" class="h-6" alt="Test Clinic">
        Test Clinic
      </div>
      <div class="flex items-center gap-4">
        <button class="bg-blue-600 text-white px-3 py-1.5 rounded-md">Policies</button>
        <button class="bg-blue-600 text-white px-3 py-1.5 rounded-md">Tail Talks</button>
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-gray-200 grid place-items-center text-gray-600">👤</div>
          <div>
            <p id="userName" class="font-semibold">Demo User</p>
            <p id="userRole" class="text-xs text-gray-500">pet_owner</p>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <div class="flex h-screen pt-16">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 shadow-sm overflow-y-auto">
      <div class="p-4 border-b flex justify-between items-center">
        <h2 class="text-sm font-semibold mb-0">Chat History</h2>
        <button id="btnNewChat" class="text-blue-600 text-xs flex items-center gap-1">
          ➕ New Chat
        </button>
      </div>
      <div id="chatHistory" class="p-2 space-y-1 text-sm"></div>
    </aside>

    <!-- Chat Section -->
    <main class="flex-1 flex flex-col">
      <!-- Header -->
      <div class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center shadow-sm">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Test Clinic AI</h1>
          <p class="text-sm text-gray-500">Ask questions about your pet’s health</p>
        </div>
        <div id="weather" class="text-sm bg-blue-50 px-3 py-1 rounded-full">☀️ 34°C Sunny</div>
      </div>

      <!-- Chat Messages -->
      <div id="chatBox" class="flex-1 overflow-y-auto px-4 py-4 bg-gray-50">
        <div class="text-center text-gray-500 mt-20">
          Start by asking something about your pet 🐾
        </div>
      </div>

      <!-- Input -->
      <div class="border-t border-gray-200 bg-white p-4 shadow-lg">
        <div class="max-w-4xl mx-auto">
          <div class="flex gap-2">
            <input id="chatInput" type="text" placeholder="Type your message..."
                   class="flex-1 border rounded-lg px-4 py-2">
            <button id="btnSend" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
              <span>Send</span>
            </button>
          </div>
          <p class="text-xs text-gray-500 text-center mt-2">
            ⚠️ AI-generated advice. Consult a licensed veterinarian.
          </p>
        </div>
      </div>
    </main>

    <!-- Right Sidebar -->
    <aside class="w-64 bg-white border-l border-gray-200 shadow-sm p-4 space-y-4 overflow-y-auto">
      <h2 class="font-semibold text-sm border-b pb-1">Nearby Vets</h2>
      <div id="nearbyVets" class="space-y-2"></div>
      <div class="bg-blue-600 text-white rounded-lg p-4">
        ✨ First Consultation FREE!
        <button class="bg-white text-blue-600 w-full mt-2 py-2 rounded-lg">Claim Offer</button>
      </div>
      <div class="bg-red-50 text-red-700 rounded-lg p-4">
        🚨 Emergency<br/>
        <button class="bg-red-600 text-white w-full mt-2 py-2 rounded-lg">Call Emergency Vet</button>
      </div>
    </aside>
  </div>

  <!-- =============== Pet Details Modal =============== -->
  <div id="petDetailsModal" class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-y-auto flex flex-col shadow-lg mx-auto my-auto">
      <div class="p-6 border-b border-gray-200 flex justify-between items-start">
        <div>
          <h2 class="text-xl font-bold mb-2">Complete Your Pet Profile</h2>
          <p class="text-gray-600 text-sm">
            Please fill your pet's details to unlock the full Test Clinic experience.
          </p>
        </div>
        <button id="petModalClose" class="text-gray-400 hover:text-gray-600 ml-4">✖</button>
      </div>

      <div class="overflow-y-auto px-6 py-4 flex-1 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Pet Type *</label>
          <select id="petType" class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
            <option value="">Select Pet Type</option>
            <option>Dog</option><option>Cat</option><option>Other</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Pet Name *</label>
          <input id="petName" class="w-full px-4 py-3 border rounded-lg" placeholder="e.g., Bruno"/>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Pet Gender *</label>
          <select id="petGender" class="w-full px-4 py-3 border rounded-lg">
            <option value="">Select Gender</option>
            <option>Male</option><option>Female</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Allow Home Visit *</label>
          <select id="homeVisit" class="w-full px-4 py-3 border rounded-lg">
            <option value="">Select Option</option>
            <option>Yes</option><option>No</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Pet Age (Years) *</label>
          <input id="petAgeYears" type="number" min="0" value="0" class="w-full px-4 py-3 border rounded-lg"/>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Pet Age (Months)</label>
          <input id="petAgeMonths" type="number" min="0" max="11" value="0" class="w-full px-4 py-3 border rounded-lg"/>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Pet Breed *</label>
          <input id="petBreed" class="w-full px-4 py-3 border rounded-lg" placeholder="Breed"/>
        </div>
      </div>

      <div class="p-6 border-t border-gray-200">
        <button id="petModalSave" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700">
          Save Pet Details
        </button>
      </div>
    </div>
  </div>

  <!-- ==================== Scripts ==================== -->
  <script>
    // ====== Read login session saved by custom-doctor-login ======
    function readAuthFull() {
      try {
        const raw = sessionStorage.getItem('auth_full') || localStorage.getItem('auth_full');
        return raw ? JSON.parse(raw) : null;
      } catch(_) { return null; }
    }

    const AUTH = readAuthFull();
    const USER_ID = Number(
      (AUTH && (AUTH.user_id || (AUTH.user && AUTH.user.id))) || 0
    ) || 0;

    // Header: set name/role if present
    (function hydrateHeader(){
      const nm = (AUTH && (AUTH.user?.name || AUTH.user_name)) || 'User';
      const rl = (AUTH && (AUTH.user?.role || AUTH.role)) || 'member';
      document.getElementById('userName').textContent = nm;
      document.getElementById('userRole').textContent = rl;
      console.log('[dashboard] session USER_ID:', USER_ID, '| name:', nm, '| role:', rl);
    })();

    // ====== Constants (APIs unchanged) ======
    const STATIC_CONTEXT = "room_static_12345"; // keep your static context
    const SEND_API      = "https://snoutiq.com/backend/api/chat/send";
    const LIST_ROOMS_API= "https://snoutiq.com/backend/api/chat/listRooms";
    const NEW_ROOM_API  = "https://snoutiq.com/backend/api/chat-rooms/new";
    const ROOM_BASE_API = "https://snoutiq.com/backend/api/chat-rooms";

    // Prefer last used room, else room from login payload, else ""
    let currentChatRoomToken =
      localStorage.getItem("lastChatRoomToken") ||
      (AUTH && AUTH.chat_room && AUTH.chat_room.token) ||
      "";

    // ====== Helpers ======
    const chatBoxEl = document.getElementById("chatBox");
    const historyEl = document.getElementById("chatHistory");

    function escapeHTML(s=""){return s.replace(/[&<>"']/g,m=>({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[m]))}
    function renderText(s=""){ return escapeHTML(String(s)).replace(/\n/g,"<br>"); }
    function scrollToBottom(){ requestAnimationFrame(()=>{ chatBoxEl.scrollTop = chatBoxEl.scrollHeight; }); }
    function appendMessage(text, isUser=false) {
      const wrap = document.createElement("div");
      wrap.className = "mb-3 flex " + (isUser ? "justify-end" : "justify-start");
      wrap.innerHTML = `
        <div class="max-w-[75%] ${isUser ? "bg-blue-600 text-white rounded-br-sm" : "bg-gray-100 text-gray-900 rounded-bl-sm"} 
                    px-4 py-2 rounded-2xl shadow text-sm leading-relaxed break-words whitespace-pre-line">
          ${renderText(text)}
        </div>`;
      chatBoxEl.appendChild(wrap);
      scrollToBottom();
    }
    function setActiveRoom(token) {
      [...historyEl.querySelectorAll("[data-room]")].forEach(n => {
        n.classList.toggle("bg-blue-50", n.dataset.room === token);
        n.classList.toggle("border-blue-200", n.dataset.room === token);
      });
    }

    // ====== Nearby vets demo (uses dynamic USER_ID) ======
    async function fetchNearbyVets() {
      try {
        const res = await axios.get(`/api/nearby-vets?user_id=${USER_ID}`);
        const vets = res.data?.data || [];
        document.getElementById("nearbyVets").innerHTML =
          vets.map(v => `<div class="p-2 border rounded">${escapeHTML(v.name || "")}</div>`).join("") || 
          `<div class="text-xs text-gray-500">No vets found</div>`;
      } catch (e) {
        document.getElementById("nearbyVets").innerHTML =
          `<div class="text-xs text-gray-500">Failed to load</div>`;
      }
    }

    // ====== Rooms: list / open / new / delete ======
    async function fetchChatRooms() {
      if (!USER_ID) {
        historyEl.innerHTML = `<div class="text-red-600 text-sm px-3 py-2">Not logged in (no user_id)</div>`;
        return;
      }
      try {
        const res = await axios.get(`${LIST_ROOMS_API}?user_id=${USER_ID}`);
        const rooms = res.data?.rooms || [];
        if (!rooms.length) {
          historyEl.innerHTML = `<div class="text-gray-500 text-sm px-3 py-2">No chat history</div>`;
          return;
        }

        historyEl.innerHTML = rooms.map(r => `
          <div class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-100 border border-transparent cursor-pointer"
               data-room="${escapeHTML(r.chat_room_token)}"
               onclick="openChatRoom('${escapeHTML(r.chat_room_token)}')">
            <span class="text-sm font-medium text-gray-700 truncate">${escapeHTML(r.name || "New Chat")}</span>
            <button onclick="deleteChatRoom(event,'${escapeHTML(r.chat_room_token)}')"
                    class="text-xs text-red-500 hover:text-red-700 px-1">✖</button>
          </div>
        `).join("");

        // Auto-select previously opened room (if exists), else first
        if (currentChatRoomToken) {
          setActiveRoom(currentChatRoomToken);
        } else {
          currentChatRoomToken = rooms[0].chat_room_token;
          localStorage.setItem("lastChatRoomToken", currentChatRoomToken);
          setActiveRoom(currentChatRoomToken);
        }
      } catch (e) {
        console.error("listRooms failed:", e);
        historyEl.innerHTML = `<div class="text-red-600 text-sm px-3 py-2">Failed to load rooms</div>`;
      }
    }

    window.openChatRoom = function(token) {
      currentChatRoomToken = token;
      localStorage.setItem("lastChatRoomToken", token);
      setActiveRoom(token);
      chatBoxEl.innerHTML = `<div class="text-center text-gray-500 mt-12">Loading chat...</div>`;
      fetchChatHistory();
    };

    async function createNewChat() {
      if (!USER_ID) return alert("Not logged in.");
      try {
        const res = await axios.get(`${NEW_ROOM_API}?user_id=${USER_ID}`);
        const token = res.data?.chat_room_token;
        if (token) {
          currentChatRoomToken = token;
          localStorage.setItem("lastChatRoomToken", token);
          await fetchChatRooms();
          setActiveRoom(token);
          chatBoxEl.innerHTML = `<div class="text-center text-gray-500 mt-12">New chat started</div>`;
        } else {
          alert("Failed to create new room.");
        }
      } catch (e) {
        console.error("new room failed:", e);
        alert("Failed to create new room.");
      }
    }

    window.deleteChatRoom = async function (ev, token) {
      ev.stopPropagation();
      if (!USER_ID) return alert("Not logged in.");
      if (!confirm("Delete this chat?")) return;
      try {
        await axios.delete(`${ROOM_BASE_API}/${token}`, { data: { user_id: USER_ID } });
        if (token === currentChatRoomToken) {
          currentChatRoomToken = "";
          localStorage.removeItem("lastChatRoomToken");
          chatBoxEl.innerHTML = `<div class="text-center text-gray-500 mt-12">Chat deleted. Start a new chat.</div>`;
        }
        await fetchChatRooms();
      } catch (e) {
        console.error("delete failed:", e);
        alert("Failed to delete chat.");
      }
    };

    // ====== History & Send ======
    async function fetchChatHistory() {
      if (!currentChatRoomToken) {
        chatBoxEl.innerHTML = `<div class="text-center text-gray-500 mt-20">Start by creating a new chat.</div>`;
        return;
      }
      if (!USER_ID) {
        chatBoxEl.innerHTML = `<div class="text-center text-red-600 mt-12">Not logged in (no user_id)</div>`;
        return;
      }
      try {
        const url = `${ROOM_BASE_API}/${currentChatRoomToken}/chats?user_id=${USER_ID}`;
        const res = await axios.get(url);
        const chats = res.data?.chats || [];
        chatBoxEl.innerHTML = "";
        if (!chats.length) {
          chatBoxEl.innerHTML = `<div class="text-center text-gray-500 mt-12">No messages yet</div>`;
          return;
        }
        chats.forEach(c => {
          if (c.question) appendMessage(c.question, true);
          if (c.answer) appendMessage(c.answer, false);
        });
      } catch (e) {
        console.error("history failed:", e);
        chatBoxEl.innerHTML = `<div class="text-center text-red-600 mt-12">Failed to load messages</div>`;
      }
    }

    async function sendMessage() {
      const input = document.getElementById("chatInput");
      const question = (input.value || "").trim();
      if (!question) return;
      if (!USER_ID) return alert("Not logged in.");

      input.value = "";
      appendMessage(question, true);

      try {
        const payload = {
          user_id: USER_ID,
          question,
          // keep static context as requested
          context_token: STATIC_CONTEXT,
          // but send selected chat room if available
          chat_room_token: currentChatRoomToken || STATIC_CONTEXT
        };

        const res = await axios.post(SEND_API, payload);
        const answer = res.data?.chat?.answer || "No response";
        appendMessage(answer, false);
      } catch (e) {
        console.error("send failed:", e);
        appendMessage("⚠️ Error sending message", false);
      }
    }

    // ====== Pet Modal (uses dynamic USER_ID) ======
    const petModal = document.getElementById("petDetailsModal");
    const petModalSave = document.getElementById("petModalSave");
    const petModalClose = document.getElementById("petModalClose");

    function openPetModal(){ petModal.classList.remove("hidden"); petModal.classList.add("flex"); }
    function closePetModal(){ petModal.classList.add("hidden"); petModal.classList.remove("flex"); }
    function maybeOpenPetModal(){ if (!localStorage.getItem("petProfileDone")) openPetModal(); }

    petModalClose.addEventListener("click", closePetModal);
    petModalSave.addEventListener("click", async () => {
      if (!USER_ID) return alert("Not logged in.");
      try {
        const years = parseInt(document.getElementById("petAgeYears").value || 0,10);
        const months = parseInt(document.getElementById("petAgeMonths").value || 0,10);
        const totalMonths = years*12 + months;
        const fd = new FormData();
        fd.append("user_id", USER_ID);
        fd.append("pet_type", document.getElementById("petType").value);
        fd.append("pet_name", document.getElementById("petName").value.trim());
        fd.append("pet_gender", document.getElementById("petGender").value);
        fd.append("home_visit", document.getElementById("homeVisit").value);
        fd.append("role", "pet");
        fd.append("pet_age", totalMonths);
        fd.append("breed", document.getElementById("petBreed").value);

        await axios.post("https://snoutiq.com/backend/api/auth/register", fd, {
          headers: { "Content-Type": "multipart/form-data" }
        });

        localStorage.setItem("petProfileDone","1");
        closePetModal();
        alert("Pet profile saved!");
      } catch (e) {
        console.error("pet save error:", e);
        alert("Failed to save pet profile");
      }
    });

    // ====== Init ======
    document.getElementById("btnSend").addEventListener("click", sendMessage);
    document.getElementById("btnNewChat").addEventListener("click", createNewChat);
    document.getElementById("chatInput").addEventListener("keydown", (e)=>{ if(e.key==="Enter") sendMessage(); });

    (async function init(){
      await fetchNearbyVets();
      await fetchChatRooms();
      if (currentChatRoomToken) await fetchChatHistory();
      maybeOpenPetModal();
    })();
  </script>

</body>
</html>
