<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard | Test Clinic</title>
  <link rel="icon" href="https://snoutiq.com/favicon.webp" type="image/png"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <style>
    /* nicer bubbles + wrapping */
    .bubble{word-break:break-word;overflow-wrap:anywhere;line-height:1.45}
  </style>
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
        <button id="newChatBtn" class="text-blue-600 text-xs flex items-center gap-1">➕ New Chat</button>
      </div>
      <div id="chatHistory" class="p-3 space-y-1 text-sm"></div>
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
            <button id="sendBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Send</button>
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
    // ======= CONSTANTS =======
    const FIXED_USER_ID = 356;
    const STATIC_ROOM = "room_fa86a154-5fe0-4a27-bef7-110adfe3d637";
    let currentChatRoomToken = localStorage.getItem("lastChatRoomToken") || "";

    // ======= HELPERS =======
    const chatBoxEl = document.getElementById("chatBox");
    function scrollToBottom(){ chatBoxEl.scrollTop = chatBoxEl.scrollHeight; }

    // ======= NEARBY VETS =======
    async function fetchNearbyVets() {
      try {
        const res = await axios.get(`/api/nearby-vets?user_id=${FIXED_USER_ID}`);
        const vets = res.data?.data || [];
        document.getElementById("nearbyVets").innerHTML =
          vets.map(v => `<div class="p-2 border rounded">${v.name}</div>`).join("") || `
          <div class="text-gray-500 text-sm">No vets found</div>`;
      } catch (e) {
        console.error("Failed to fetch vets", e);
      }
    }

    // ======= ROOMS (ALL CHATS LIST) =======
    async function fetchChatRooms() {
      try {
        const res = await axios.get(`/api/chat/listRooms?user_id=${FIXED_USER_ID}`);
        const rooms = res.data?.rooms || res.data || [];
        const list = rooms.map(r => `
          <div class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-100 cursor-pointer"
               onclick="openChatRoom('${r.chat_room_token || ""}')">
            <span class="truncate pr-2">${r.name && !r.name.startsWith("New chat -") ? r.name : "New Chat"}</span>
            <button class="text-xs text-red-500 hover:text-red-700"
                    onclick="event.stopPropagation(); deleteChatRoom('${r.chat_room_token || ""}')">✖</button>
          </div>
        `).join("");

        document.getElementById("chatHistory").innerHTML =
          list || `<div class="text-gray-500 text-sm px-2">No chat history</div>`;

        // Auto-open last room or first available
        if (!currentChatRoomToken) {
          const firstToken = rooms[0]?.chat_room_token;
          if (firstToken) openChatRoom(firstToken);
        }
      } catch (e) {
        console.error("Failed to fetch chat rooms", e);
        document.getElementById("chatHistory").innerHTML =
          `<div class="text-gray-500 text-sm px-2">Failed to load chat history</div>`;
      }
    }

    async function createNewChat() {
      try {
        const res = await axios.get(`/api/chat-rooms/new?user_id=${FIXED_USER_ID}`);
        const token = res.data?.chat_room_token;
        if (token) {
          currentChatRoomToken = token;
          localStorage.setItem("lastChatRoomToken", token);
          await fetchChatRooms();
          await fetchChatHistory(); // show empty state for new room
        }
      } catch (e) {
        console.error("Failed to create room", e);
      }
    }

    async function deleteChatRoom(roomToken) {
      if (!roomToken) return;
      if (!confirm("Delete this chat?")) return;
      try {
        await axios.delete(`/api/chat-rooms/${roomToken}`, {
          data: { user_id: FIXED_USER_ID }
        });
        if (currentChatRoomToken === roomToken) {
          currentChatRoomToken = "";
          localStorage.removeItem("lastChatRoomToken");
          chatBoxEl.innerHTML = `<div class='text-center text-gray-500 mt-20'>Chat deleted. Start a new chat.</div>`;
        }
        await fetchChatRooms();
      } catch (e) {
        console.error("Failed to delete chat", e);
      }
    }

    function openChatRoom(token){
      if (!token) return;
      currentChatRoomToken = token;
      localStorage.setItem("lastChatRoomToken", token);
      chatBoxEl.innerHTML = `<div class='text-center text-gray-500'>Loading chat...</div>`;
      fetchChatHistory();
    }

    // ======= CHAT HISTORY (MESSAGES IN A ROOM) =======
    // async function fetchChatHistory() {
    //   if (!currentChatRoomToken) {
    //     chatBoxEl.innerHTML = `<div class="text-center text-gray-500 mt-20">Start by creating a new chat.</div>`;
    //     return;
    //   }
    //   try {
    //     const res = await axios.get(`/api/chat-rooms/${currentChatRoomToken}/chats?user_id=${FIXED_USER_ID}`);
    //     const chats = res.data?.chats || [];
    //     if (chats.length === 0) {
    //       chatBoxEl.innerHTML = `<div class="text-center text-gray-500 mt-12">No messages yet</div>`;
    //       return;
    //     }
    //     const html = chats.map(c => `
    //       <div class="mb-3">
    //         <div class="flex justify-end">
    //           <div class="max-w-[75%] bg-blue-600 text-white px-4 py-2 rounded-2xl rounded-br-sm shadow bubble">${c.question || ""}</div>
    //         </div>
    //         <div class="flex justify-start mt-1">
    //           <div class="max-w-[75%] bg-gray-100 text-gray-900 px-4 py-2 rounded-2xl rounded-bl-sm shadow bubble">${c.answer || ""}</div>
    //         </div>
    //       </div>
    //     `).join("");
    //     chatBoxEl.innerHTML = html;
    //     scrollToBottom();
    //   } catch (e) {
    //     console.error("Failed to fetch chat history", e);
    //     chatBoxEl.innerHTML = `<div class="text-center text-red-600 mt-12">Failed to load messages</div>`;
    //   }
    // }

    async function fetchChatHistory() {
  // ab hamesha static token use hoga
  const STATIC_ROOM = "room_fa86a154-5fe0-4a27-bef7-110adfe3d637";

  try {
    const res = await axios.get(`/api/chat-rooms/${STATIC_ROOM}/chats?user_id=${FIXED_USER_ID}`);
    const chats = res.data?.chats || [];
    if (chats.length === 0) {
      chatBoxEl.innerHTML = `<div class="text-center text-gray-500 mt-12">No messages yet</div>`;
      return;
    }
    const html = chats.map(c => `
      <div class="mb-3">
        <div class="flex justify-end">
          <div class="max-w-[75%] bg-blue-600 text-white px-4 py-2 rounded-2xl rounded-br-sm shadow bubble">
            ${c.question || ""}
          </div>
        </div>
        <div class="flex justify-start mt-1">
          <div class="max-w-[75%] bg-gray-100 text-gray-900 px-4 py-2 rounded-2xl rounded-bl-sm shadow bubble">
            ${c.answer || ""}
          </div>
        </div>
      </div>
    `).join("");
    chatBoxEl.innerHTML = html;
    scrollToBottom();
  } catch (e) {
    console.error("Failed to fetch chat history", e);
    chatBoxEl.innerHTML = `<div class="text-center text-red-600 mt-12">Failed to load messages</div>`;
  }
}


    // ======= SEND MESSAGE (STATIC PAYLOAD) =======
    async function sendMessage() {
      const input = document.getElementById("chatInput");
      const uiMsg = (input.value || "").trim();
      input.value = "";

      // Show user's typed text in UI (even though payload stays static)
      if (uiMsg) {
        chatBoxEl.insertAdjacentHTML("beforeend", `
          <div class="flex justify-end mb-3">
            <div class="max-w-[75%] bg-blue-600 text-white px-4 py-2 rounded-2xl rounded-br-sm shadow bubble">${uiMsg}</div>
          </div>
        `);
        scrollToBottom();
      }

      try {
        const payload = {
          user_id: 356,
          question: "hi", // << static
          context_token: STATIC_ROOM,
          chat_room_token: STATIC_ROOM
        };

        const res = await axios.post(`/api/chat/send`, payload, {
          headers: { "Content-Type": "application/json" }
        });

        const answer = res.data?.chat?.answer || "No response";
        chatBoxEl.insertAdjacentHTML("beforeend", `
          <div class="flex justify-start mb-3">
            <div class="max-w-[75%] bg-gray-100 text-gray-900 px-4 py-2 rounded-2xl rounded-bl-sm shadow bubble">${answer}</div>
          </div>
        `);
        scrollToBottom();

        // refresh left list so latest room titles/snippets feel current
        fetchChatRooms();
      } catch (e) {
        console.error(e);
        chatBoxEl.insertAdjacentHTML("beforeend",
          `<div class="text-left text-red-600 mb-2">⚠️ Error sending message</div>`);
      }
    }

    // ======= INIT =======
    document.getElementById("newChatBtn").addEventListener("click", createNewChat);
    document.getElementById("sendBtn").addEventListener("click", sendMessage);
    document.getElementById("chatInput").addEventListener("keydown", (e)=>{ if(e.key==="Enter") sendMessage(); });

    (async function init(){
      await fetchNearbyVets();
      await fetchChatRooms();
      if (currentChatRoomToken) fetchChatHistory();
    })();
  </script>
</body>
</html>
