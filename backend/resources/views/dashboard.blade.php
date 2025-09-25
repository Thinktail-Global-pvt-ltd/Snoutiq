<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard | SnoutIQ</title>
  <link rel="icon" href="https://snoutiq.com/favicon.webp" type="image/png"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-50">

  <!-- Navbar -->
  <nav class="fixed top-0 w-full bg-white border-b border-gray-200 shadow-sm z-50">
    <div class="max-w-7xl mx-auto px-6 py-3 flex justify-between items-center">
      <div class="flex items-center gap-2 font-bold text-xl text-blue-600">
        <img src="https://snoutiq.com/favicon.webp" class="h-6" alt="SnoutIQ">
        SnoutIQ
      </div>
      <div class="flex items-center gap-4">
        <button class="bg-blue-600 text-white px-3 py-1.5 rounded-md">Policies</button>
        <button class="bg-blue-600 text-white px-3 py-1.5 rounded-md">Tail Talks</button>
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-user-circle text-gray-600 text-xl"></i>
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
      <div class="p-4 border-b">
        <h2 class="text-sm font-semibold mb-2">Chat History</h2>
        <button onclick="newChat()" class="text-blue-600 text-xs flex items-center gap-1">
          <i class="fa-solid fa-plus"></i> New Chat
        </button>
      </div>
      <div id="chatHistory" class="p-4 space-y-2 text-sm"></div>
    </aside>

    <!-- Chat Section -->
    <main class="flex-1 flex flex-col">
      <!-- Header -->
      <div class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center shadow-sm">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">SnoutIQ AI</h1>
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
          <!-- Pet Profile bar (editable for admins) -->
          <div id="petProfileBar" class="bg-gray-900 text-white px-4 py-3 rounded-xl mb-3 hidden">
            <div class="flex justify-between items-center mb-2">
              <div class="flex items-center gap-2">
                <span class="text-lg">🐶</span>
                <span class="font-semibold">Pet Profile</span>
              </div>
              <div class="flex gap-2">
                <span id="decisionChip" class="text-[11px] px-2 py-1 rounded-md bg-blue-600/90">Decision: —</span>
                <span id="scoreChip" class="text-[11px] px-2 py-1 rounded-md bg-gray-700">Score: —</span>
              </div>
            </div>
            <div class="flex flex-wrap gap-2">
              <input id="petName" class="bg-gray-800 text-white px-3 py-1 rounded-lg" placeholder="Name">
              <input id="petBreed" class="bg-gray-800 text-white px-3 py-1 rounded-lg" placeholder="Breed">
              <input id="petAge" class="bg-gray-800 text-white px-3 py-1 rounded-lg" placeholder="Age">
              <input id="petWeight" class="bg-gray-800 text-white px-3 py-1 rounded-lg" placeholder="Weight">
              <input id="petLocation" class="bg-gray-800 text-white px-3 py-1 rounded-lg min-w-[200px]" placeholder="Location">
            </div>
          </div>

          <!-- Input box -->
          <div class="flex gap-2">
            <input id="chatInput" type="text" placeholder="Type your message..." class="flex-1 border rounded-lg px-4 py-2">
            <button onclick="sendMessage()" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
              <i class="fa-solid fa-paper-plane"></i>
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

  <script>
    let currentChatRoomToken = localStorage.getItem("lastChatRoomToken") || "";
    let contextToken = localStorage.getItem("contextToken") || "";
    let lastDecision = null;
    let lastScore = null;

    async function fetchNearbyVets() {
      try {
        const res = await axios.get(`/api/nearby-vets?user_id=355`);
        document.getElementById("nearbyVets").innerHTML =
          res.data.data.map(v => `<div class="p-2 border rounded">${v.name}</div>`).join("");
      } catch (e) { console.error(e); }
    }

    async function fetchChatHistory() {
      try {
        let url = currentChatRoomToken
          ? `/api/chat-rooms/${currentChatRoomToken}/chats?user_id=1`
          : `/api/chat/listRooms?user_id=1`;

        const res = await axios.get(url);
        const chats = res.data?.chats || [];
        let html = "";
        chats.forEach(chat => {
          html += `
            <div class="mb-3">
              <div class="text-right"><span class="bg-blue-100 px-3 py-2 rounded">${chat.question}</span></div>
              <div class="text-left mt-1"><span class="bg-gray-200 px-3 py-2 rounded">${chat.answer}</span></div>
            </div>
          `;
        });
        document.getElementById("chatBox").innerHTML = html || "<div class='text-center text-gray-500'>No messages yet</div>";
      } catch (e) {
        console.error("Failed to fetch chat history", e);
      }
    }

    async function sendMessage() {
      const input = document.getElementById("chatInput");
      if (!input.value.trim()) return;
      const question = input.value;
      input.value = "";

      document.getElementById("chatBox").innerHTML += `
        <div class="text-right mb-2"><span class="bg-blue-100 px-3 py-2 rounded">${question}</span></div>
      `;

      try {
        const payload = {
          user_id: 1,
          question,
          context_token: contextToken,
          chat_room_token: currentChatRoomToken
        };

        const res = await axios.post(`/api/chat/send`, payload);
        const { chat = {}, context_token: newCtx, decision, score } = res.data || {};
        if (newCtx) { contextToken = newCtx; localStorage.setItem("contextToken", newCtx); }

        lastDecision = decision || null;
        lastScore = score || null;
        document.getElementById("decisionChip").innerText = "Decision: " + (lastDecision ?? "—");
        document.getElementById("scoreChip").innerText = "Score: " + (lastScore ?? "—");

        const answer = chat.answer || "No response";
        document.getElementById("chatBox").innerHTML += `
          <div class="text-left mb-2"><span class="bg-gray-200 px-3 py-2 rounded">${answer}</span></div>
        `;
      } catch (e) {
        document.getElementById("chatBox").innerHTML += `
          <div class="text-left text-red-600 mb-2">⚠️ Error sending message</div>
        `;
      }
    }

    function newChat() {
      currentChatRoomToken = "";
      localStorage.removeItem("lastChatRoomToken");
      document.getElementById("chatBox").innerHTML = "<div class='text-center text-gray-500'>New chat started</div>";
    }

    // Init
    fetchNearbyVets();
    fetchChatHistory();
  </script>
</body>
</html>
