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
        <button onclick="createNewChat()" class="text-blue-600 text-xs flex items-center gap-1">
          ➕ New Chat
        </button>
      </div>
      <div id="chatHistory" class="p-4 space-y-2 text-sm"></div>
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
            <button onclick="sendMessage()" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
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

  <!-- ==================== Scripts ==================== -->
  <script>
    const FIXED_USER_ID = 356; 
    let currentChatRoomToken = "room_static_12345";
    let contextToken = "room_static_12345";

    // helper to append bubble safely
    function appendMessage(message, isUser = false) {
      const chatBox = document.getElementById("chatBox");
      const wrapper = document.createElement("div");
      wrapper.className = "mb-3 flex " + (isUser ? "justify-end" : "justify-start");

      wrapper.innerHTML = `
        <div class="max-w-[75%] ${isUser ? "bg-blue-600 text-white rounded-br-sm" : "bg-gray-100 text-gray-900 rounded-bl-sm"} 
                    px-4 py-2 rounded-2xl shadow text-sm leading-relaxed break-words">
          ${message}
        </div>
      `;
      chatBox.appendChild(wrapper);
      chatBox.scrollTop = chatBox.scrollHeight;
    }

    // fetch chat history
    async function fetchChatHistory() {
      try {
        const res = await axios.get(`https://snoutiq.com/api/chat-rooms/${currentChatRoomToken}/chats?user_id=${FIXED_USER_ID}`);
        const chats = res.data?.chats || [];
        const chatBox = document.getElementById("chatBox");
        chatBox.innerHTML = "";

        if (chats.length === 0) {
          chatBox.innerHTML = "<div class='text-center text-gray-500'>No messages yet</div>";
          return;
        }

        chats.forEach(chat => {
          appendMessage(chat.question, true);
          appendMessage(chat.answer, false);
        });
      } catch (err) {
        console.error("Failed to fetch chat history", err);
      }
    }

    // send message
    async function sendMessage() {
      const input = document.getElementById("chatInput");
      if (!input.value.trim()) return;
      const question = input.value.trim();
      input.value = "";

      appendMessage(question, true); // user bubble

      try {
        const payload = {
          user_id: FIXED_USER_ID,
          question,
          context_token: contextToken,
          chat_room_token: currentChatRoomToken
        };

        const res = await axios.post(`https://snoutiq.com/backend/api/chat/send`, payload);
        const { chat = {} } = res.data || {};
        appendMessage(chat.answer || "No response", false);
      } catch (e) {
        console.error("Send error:", e);
        appendMessage("⚠️ Error sending message", false);
      }
    }

    // placeholder for room creation
    function createNewChat() {
      currentChatRoomToken = "room_static_12345";
      contextToken = "room_static_12345";
      document.getElementById("chatBox").innerHTML =
        "<div class='text-center text-gray-500'>New chat started</div>";
    }

    // init
    (function init() {
      fetchChatHistory();
    })();
  </script>

</body>
</html>
