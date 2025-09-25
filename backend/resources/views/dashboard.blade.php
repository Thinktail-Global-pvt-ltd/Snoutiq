<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Snoutiq Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-gray-50">

  <!-- Navbar -->
  <header class="bg-white border-b shadow-sm px-6 py-3 flex justify-between items-center">
    <div class="flex items-center space-x-2 font-bold text-xl text-blue-600">
      <img src="https://snoutiq.com/favicon.webp" class="h-6" alt="Snoutiq">
      SnoutIQ
    </div>
    <div class="flex items-center space-x-4">
      <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold">Policies</button>
      <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold">Tail Talks</button>
      <div class="flex items-center space-x-2">
        <i class="fa-solid fa-user-circle text-gray-600 text-xl"></i>
        <div>
          <p class="font-semibold">Demo User</p>
          <p class="text-xs text-gray-500">pet_owner</p>
        </div>
      </div>
    </div>
  </header>

  <!-- Layout -->
  <div class="flex h-[calc(100vh-64px)]">

    <!-- LEFT SIDEBAR -->
    <aside class="w-64 bg-white border-r p-4 overflow-y-auto">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-sm font-semibold">Chat History</h2>
        <button onclick="newChat()" class="text-blue-600 text-xs flex items-center gap-1">
          <i class="fa-solid fa-plus"></i> New Chat
        </button>
      </div>
      <div id="chatHistory" class="space-y-2 text-sm"></div>

      <h2 class="mt-6 mb-2 text-sm font-semibold">Pet of the Day</h2>
      <div class="bg-orange-50 rounded-lg p-3 text-center shadow">
        <img src="https://placekitten.com/200/140" class="rounded-lg mb-2 mx-auto">
        <p class="font-bold">Lucy</p>
        <p class="text-xs text-gray-600">7 years, Pune</p>
        <span class="bg-red-500 text-white px-2 py-0.5 text-xs rounded-full">LIVE</span>
      </div>
    </aside>

    <!-- CENTER CHAT -->
    <main class="flex-1 flex flex-col">
      <div class="border-b bg-white px-6 py-3 flex justify-between items-center">
        <div>
          <h1 class="text-xl font-bold">Snoutiq AI</h1>
          <p class="text-sm text-gray-500">Ask questions about your pet's health</p>
        </div>
        <div id="weather" class="text-sm bg-blue-50 px-3 py-1 rounded-full">☀️ 34°C Sunny • Feels like 34°C</div>
      </div>

      <div id="chatBox" class="flex-1 overflow-y-auto px-4 bg-gray-50 py-4">
        <div class="text-center text-gray-600">
          No messages yet. Start by asking something about your pet 🐾
        </div>
      </div>

      <!-- Input -->
      <div class="border-t bg-white px-4 py-3">
        <div class="flex items-center space-x-2 max-w-3xl mx-auto">
          <input id="chatInput" type="text" placeholder="Type your message..." class="flex-1 border rounded-lg px-4 py-2">
          <button onclick="sendMessage()" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
            <i class="fa-solid fa-paper-plane"></i>
          </button>
        </div>
        <p class="text-xs text-gray-500 text-center mt-2">⚠️ AI-generated advice. Consult a licensed veterinarian.</p>
      </div>
    </main>

    <!-- RIGHT SIDEBAR -->
    <aside class="w-72 bg-white border-l p-4 space-y-4 overflow-y-auto">
      <div>
        <div class="flex space-x-4 border-b">
          <button class="font-semibold text-blue-600 border-b-2 border-blue-600 pb-1">Nearby Vets</button>
          <button class="text-gray-500 pb-1">Groomers</button>
        </div>
        <div id="nearbyVets" class="space-y-2 mt-3"></div>
      </div>
      <div class="bg-blue-600 text-white rounded-lg p-4">
        ✨ Limited Time Offer<br>
        <span class="font-bold">First Vet Consultation FREE!</span>
        <button class="bg-white text-blue-600 font-semibold w-full mt-2 px-3 py-2 rounded-lg">Claim Offer</button>
      </div>
      <div class="bg-red-50 text-red-700 rounded-lg p-4">
        🚨 Emergency<br>
        <span class="text-sm">Immediate veterinary care</span>
        <button class="bg-red-600 text-white w-full mt-2 px-3 py-2 rounded-lg">Call Emergency Vet</button>
      </div>
      <div class="bg-gray-50 rounded-lg p-3 text-center">
        <p class="text-sm mb-2">Better experience on our app</p>
        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play">
      </div>
    </aside>
  </div>

  <script>
    let currentChatRoomToken = "";

    async function fetchNearbyVets() {
      try {
        const res = await axios.get(`http://127.0.0.1:8000/api/nearby-vets?user_id=1`);
        document.getElementById("nearbyVets").innerHTML = res.data.data.map(v => `
          <div class="p-2 border rounded">${v.name}</div>
        `).join("");
      } catch(e) { console.error(e); }
    }

    async function fetchChatHistory() {
      try {
        let url = currentChatRoomToken
          ? `http://127.0.0.1:8000/api/chat-rooms/${currentChatRoomToken}/chats?user_id=1`
          : `http://127.0.0.1:8000/api/chat/listRooms?user_id=1`;

        const res = await axios.get(url);
        const chats = res.data?.chats || [];
        let html = "";
        chats.forEach(chat => {
          html += `
            <div class="mb-2">
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
        <div class="text-right mb-2">
          <span class="bg-blue-100 px-3 py-2 rounded">${question}</span>
        </div>
      `;

      try {
        const res = await axios.post("http://127.0.0.1:8000/api/chat/send", {
          user_id: 1,  // ✅ Static user
          question,
          chat_room_token: currentChatRoomToken || "",
        });

        const answer = res.data.chat?.answer || "No response";
        document.getElementById("chatBox").innerHTML += `
          <div class="text-left mb-2">
            <span class="bg-gray-200 px-3 py-2 rounded">${answer}</span>
          </div>
        `;
      } catch(e) {
        document.getElementById("chatBox").innerHTML += `
          <div class="text-left text-red-600 mb-2">
            ⚠️ Error sending message
          </div>
        `;
      }
    }

    function newChat() {
      currentChatRoomToken = "";
      document.getElementById("chatBox").innerHTML = "<div class='text-center text-gray-500'>New chat started</div>";
    }

    // init
    fetchNearbyVets();
    fetchChatHistory();
  </script>
</body>
</html>
