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

  <!-- ========================================= -->
  <!-- Pet Details Modal -->
<div id="petDetailsModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-y-auto flex flex-col mx-auto my-auto shadow-lg">

    <!-- Header with Close Button -->
    <div class="p-6 border-b border-gray-200 flex justify-between items-start">
      <div>
        <h2 class="text-xl font-bold mb-2">Complete Your Pet Profile</h2>
        <p class="text-gray-600 text-sm">
          Please fill your pet's details to unlock the full Test Clinic experience.  
          You cannot access the dashboard until this form is complete.
        </p>
      </div>
      <button onclick="closePetModal()" class="text-gray-400 hover:text-gray-600 ml-4">
        ✖
      </button>
    </div>

    <!-- Scrollable Content -->
    <div class="overflow-y-auto px-6 py-4 flex-1 grid grid-cols-1 md:grid-cols-2 gap-6">

      <!-- Pet Type -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Pet Type *</label>
        <select id="petType" class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
          <option value="">Select Pet Type</option>
          <option value="Dog">Dog</option>
          <option value="Cat">Cat</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <!-- Pet Name -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Pet Name *</label>
        <input id="petName" type="text" placeholder="e.g., Bruno"
          class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500 focus:border-blue-500" />
      </div>

      <!-- Pet Gender -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Pet Gender *</label>
        <select id="petGender" class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
          <option value="">Select Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
      </div>

      <!-- Allow Home Visit -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Allow Home Visit *</label>
        <select id="homeVisit" class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500 focus:border-blue-500">
          <option value="">Select Option</option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
        </select>
      </div>

      <!-- Pet Age -->
      <div class="mb-4 flex gap-4 col-span-2">
        <div class="flex-1">
          <label class="block text-sm font-medium text-gray-700 mb-2">Pet Age (Years) *</label>
          <input id="petAgeYears" type="number" min="0" value="0"
            class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500 focus:border-blue-500" />
        </div>
        <div class="flex-1">
          <label class="block text-sm font-medium text-gray-700 mb-2">Pet Age (Months)</label>
          <input id="petAgeMonths" type="number" min="0" max="11" value="0"
            class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500 focus:border-blue-500" />
        </div>
      </div>

      <!-- Pet Breed -->
      <div class="mb-4 col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">Pet Breed *</label>
        <input id="petBreed" type="text" placeholder="Breed"
          class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500 focus:border-blue-500" />
      </div>

    </div>

    <!-- Footer -->
    <div class="p-6 border-t border-gray-200">
      <button onclick="savePetDetails()" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700">
        Save Pet Details
      </button>
    </div>
  </div>
</div>

<!-- Script -->
<script>
  function closePetModal() {
    const modal = document.getElementById("petDetailsModal");
    if (modal) {
      modal.classList.add("hidden"); // Hide modal
    }
  }

  function savePetDetails() {
    // Example only - hook your API here
    const petName = document.getElementById("petName").value;
    alert(`Pet Details Saved for: ${petName}`);
    closePetModal();
  }
</script>

  <!-- Breed lightbox -->
  <div id="breedLightbox" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg p-4 max-w-2xl w-full">
      <div class="flex justify-between items-center mb-3">
        <h3 class="font-semibold">Breed Image</h3>
        <button onclick="closeBreedLightbox()" class="text-gray-600 hover:text-gray-800">✖</button>
      </div>
      <img id="breedLarge" class="w-full h-[60vh] object-cover rounded"/>
    </div>
  </div>

  <!-- JS -->
  <script>
    // ---------- Constants ----------
    const FIXED_USER_ID = 356; // 🔥 switched to 356
    const BACKEND = "https://snoutiq.com/backend/api";
    let currentChatRoomToken = localStorage.getItem("lastChatRoomToken") || "";
    let contextToken = localStorage.getItem("contextToken") || "";
    // Log session on load (as requested earlier)
    console.log("sessionUser:", sessionStorage.getItem("sessionUser"));

    // ---------- Helpers ----------
    const getAuthHeaders = () => {
      const token = localStorage.getItem("token");
      return token ? { Authorization: `Bearer ${token}` } : {};
    };
    const show = (el) => el.classList.remove("hidden");
    const hide = (el) => el.classList.add("hidden");
    const setErr = (id, msg) => {
      const p = document.getElementById(id);
      if (!p) return;
      if (msg) { p.textContent = msg; show(p); }
      else { p.textContent = ""; hide(p); }
    };

    // ---------- Nearby Vets ----------
    async function fetchNearbyVets() {
      try {
        const res = await axios.get(`/api/nearby-vets?user_id=${FIXED_USER_ID}`);
        document.getElementById("nearbyVets").innerHTML =
          (res.data?.data || []).map(v => `<div class="p-2 border rounded">${v.name}</div>`).join("");
      } catch (e) { console.error("Failed to fetch vets", e); }
    }

    // ---------- Rooms ----------
    async function fetchChatRooms() {
      try {
     const res = await axios.get(`https://snoutiq.com/backend/api/chat/listRooms?user_id=${FIXED_USER_ID}`);
        const rooms = res.data?.rooms || [];
        let html = "";
        if (rooms.length === 0) {
          html = `<div class="text-gray-500 text-sm">No chat history</div>`;
        } else {
          rooms.forEach(room => {
            html += `
              <div class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-100 mb-1 cursor-pointer"
                   onclick="openChatRoom('${room.chat_room_token}')">
                <span class="text-sm font-medium text-gray-700 truncate">${room.name || "New Chat"}</span>
                <button onclick="deleteChatRoom(event, ${room.id}, '${room.chat_room_token}')"
                        class="text-xs text-red-500 hover:text-red-700">✖</button>
              </div>
            `;
          });
        }
        document.getElementById("chatHistory").innerHTML = html;
      } catch (err) {
        console.error("Failed to fetch rooms", err);
      }
    }
    function openChatRoom(token) {
      currentChatRoomToken = token;
      localStorage.setItem("lastChatRoomToken", token);
      document.getElementById("chatBox").innerHTML = "<div class='text-center text-gray-500'>Loading chat...</div>";
      fetchChatHistory();
    }
    async function createNewChat() {
      try {
        // const res = await axios.get(`/api/chat-rooms/new?user_id=${FIXED_USER_ID}`);
        await axios.delete(`https://snoutiq.com/backend/api/chat-rooms/${token}`, { data: { user_id: FIXED_USER_ID } });
        const { chat_room_token } = res.data || {};
        if (chat_room_token) {
          currentChatRoomToken = chat_room_token;
          localStorage.setItem("lastChatRoomToken", chat_room_token);
          await fetchChatRooms();
          document.getElementById("chatBox").innerHTML = "<div class='text-center text-gray-500'>New chat started</div>";
        }
      } catch (err) {
        console.error("Failed to create room", err);
      }
    }
    async function deleteChatRoom(e, chatId, token) {
      e.stopPropagation();
      if (!confirm("Are you sure you want to delete this chat?")) return;
      try {
        await axios.delete(`/api/chat-rooms/${token}`, { data: { user_id: FIXED_USER_ID } });
        await fetchChatRooms();
        if (token === currentChatRoomToken) {
          currentChatRoomToken = "";
          localStorage.removeItem("lastChatRoomToken");
          document.getElementById("chatBox").innerHTML =
            "<div class='text-center text-gray-500'>Chat deleted. Start a new chat.</div>";
        }
      } catch (err) {
        console.error("Failed to delete chat", err);
      }
    }

    // ---------- Chat ----------
    async function fetchChatHistory() {
      if (!currentChatRoomToken) return;
      try {
        // const res = await axios.get(`/api/chat-rooms/${currentChatRoomToken}/chats?user_id=${FIXED_USER_ID}`);
        const res = await axios.get(`https://snoutiq.com/backend/api/chat-rooms/${currentChatRoomToken}/chats?user_id=${FIXED_USER_ID}`);
        const chats = res.data?.chats || [];
        let html = "";
        chats.forEach(chat => {
          html += `
            <div class="mb-3">
              <div class="flex justify-end">
                <div class="max-w-[75%] bg-blue-600 text-white px-4 py-2 rounded-2xl rounded-br-sm shadow text-sm leading-relaxed break-words">
                  ${chat.question}
                </div>
              </div>
              <div class="flex justify-start mt-1">
                <div class="max-w-[75%] bg-gray-100 text-gray-900 px-4 py-2 rounded-2xl rounded-bl-sm shadow text-sm leading-relaxed break-words">
                  ${chat.answer}
                </div>
              </div>
            </div>
          `;
        });
        document.getElementById("chatBox").innerHTML =
          html || "<div class='text-center text-gray-500'>No messages yet</div>";
        // Auto-scroll bottom
        const chatBox = document.getElementById("chatBox");
        chatBox.scrollTop = chatBox.scrollHeight;
      } catch (err) {
        console.error("Failed to fetch chat history", err);
      }
    }

    
    async function sendMessage_001() {
      const input = document.getElementById("chatInput");
      const question = (input.value || "").trim();
      if (!question) return;
      input.value = "";

      // append user bubble
      const chatBox = document.getElementById("chatBox");
      chatBox.innerHTML += `
        <div class="flex justify-end mb-3">
          <div class="max-w-[75%] bg-blue-600 text-white px-4 py-2 rounded-2xl rounded-br-sm shadow text-sm leading-relaxed break-words">
            ${question}
          </div>
        </div>
      `;
      chatBox.scrollTop = chatBox.scrollHeight;

      try {
        const payload = {
          user_id: FIXED_USER_ID,
          question,
          context_token: contextToken,
          chat_room_token: currentChatRoomToken
        };
        const res = await axios.post(`api/chat/send`, payload);
        const { chat = {}, context_token: newCtx } = res.data || {};
        if (newCtx) {
          contextToken = newCtx;
          localStorage.setItem("contextToken", newCtx);
        }
        const answer = chat.answer || "No response";
        chatBox.innerHTML += `
          <div class="flex justify-start mb-3">
            <div class="max-w-[75%] bg-gray-100 text-gray-900 px-4 py-2 rounded-2xl rounded-bl-sm shadow text-sm leading-relaxed break-words">
              ${answer}
            </div>
          </div>
        `;
        chatBox.scrollTop = chatBox.scrollHeight;
      } catch (e) {
        chatBox.innerHTML += `
          <div class="text-left text-red-600 mb-2">⚠️ Error sending message</div>
        `;
      }
    }

    // ---------- Pet Modal Logic ----------
    // NOTE: Legacy ids guarded (only run if elements exist)
    const petModal = document.getElementById("petDetailsModal");
    const md_petType = document.getElementById("md_petType");
    const md_petName = document.getElementById("md_petName");
    const md_petGender = document.getElementById("md_petGender");
    const md_homeVisit = document.getElementById("md_homeVisit");
    const md_petAgeYears = document.getElementById("md_petAgeYears");
    const md_petAgeMonths = document.getElementById("md_petAgeMonths");
    const breedSelectWrap = document.getElementById("breedSelectWrap");
    const breedInputWrap = document.getElementById("breedInputWrap");
    const md_petBreed_select = document.getElementById("md_petBreed_select");
    const md_petBreed_input = document.getElementById("md_petBreed_input");
    const md_doc1 = document.getElementById("md_doc1");
    const md_doc2 = document.getElementById("md_doc2");
    const md_doc1_label = document.getElementById("md_doc1_label");
    const md_doc2_label = document.getElementById("md_doc2_label");
    const breedImageWrap = document.getElementById("breedImageWrap");
    const breedPreview = document.getElementById("breedPreview");
    const breedLightbox = document.getElementById("breedLightbox");
    const breedLarge = document.getElementById("breedLarge");

    function openBreedLightbox(){ if (breedLarge && breedPreview) { breedLarge.src = breedPreview.src; show(breedLightbox); } }
    function closeBreedLightbox(){ if (breedLightbox) hide(breedLightbox); }

    if (md_doc1) md_doc1.addEventListener("change", () => md_doc1_label.textContent = md_doc1.files?.[0]?.name || "Click to upload or drag & drop");
    if (md_doc2) md_doc2.addEventListener("change", () => md_doc2_label.textContent = md_doc2.files?.[0]?.name || "Click to upload or drag & drop");

    if (md_petType) {
      md_petType.addEventListener("change", async () => {
        clearBreedErrors();
        if (md_petType.value === "Dog") {
          if (breedSelectWrap) show(breedSelectWrap);
          if (breedInputWrap) hide(breedInputWrap);
          await loadDogBreeds();
        } else {
          if (breedSelectWrap) hide(breedSelectWrap);
          if (breedInputWrap) show(breedInputWrap);
          if (breedImageWrap) hide(breedImageWrap);
        }
      });
    }

    async function loadDogBreeds() {
      if (!md_petBreed_select) return;
      try {
        md_petBreed_select.innerHTML = `<option value="">Loading breeds...</option>`;
        const res = await axios.get(`${BACKEND}/dog-breeds/all`);
        const breedsData = res.data?.breeds || {};
        const list = [];
        Object.entries(breedsData).forEach(([breed, subs]) => {
          if (Array.isArray(subs) && subs.length) {
            subs.forEach(sub => list.push(`${capitalize(sub)} ${capitalize(breed)}`));
          } else {
            list.push(capitalize(breed));
          }
        });
        list.sort();
        md_petBreed_select.innerHTML = `<option value="">Select Breed</option>` + list.map(b => `<option>${b}</option>`).join("");
      } catch (e) {
        console.error("Failed to load dog breeds", e);
        md_petBreed_select.innerHTML = `<option value="">Failed to load</option>`;
      }
    }

    if (md_petBreed_select) {
      md_petBreed_select.addEventListener("change", async () => {
        const label = md_petBreed_select.value || "";
        if (!label) { if (breedImageWrap) hide(breedImageWrap); return; }
        try {
          const path = toDogCeoPath(label); // e.g. "bulldog/french"
          if (!path) { if (breedImageWrap) hide(breedImageWrap); return; }
          const imgRes = await axios.get(`https://dog.ceo/api/breed/${path}/images/random`);
          const url = imgRes.data?.message;
          if (url) {
            if (breedPreview) breedPreview.src = url;
            if (breedImageWrap) show(breedImageWrap);
          } else {
            if (breedImageWrap) hide(breedImageWrap);
          }
        } catch {
          if (breedImageWrap) hide(breedImageWrap);
        }
      });
    }

    function toDogCeoPath(label){
      // "French Bulldog" => "bulldog/french" ; "Labrador" => "labrador"
      const parts = label.trim().toLowerCase().split(" ");
      if (parts.length === 1) return parts[0];
      // assume "<sub> <breed>"
      const sub = parts.slice(0, parts.length - 1).join("");
      const breed = parts[parts.length - 1];
      return `${breed}/${sub}`;
    }
    const capitalize = s => s.charAt(0).toUpperCase() + s.slice(1);

    function clearBreedErrors(){
      setErr("err_petBreed", "");
    }

    function validatePetForm(){
      // legacy form validation guarded in new template
      return true;
    }

    async function savePetDetails_backup_archived_(){
      if (!validatePetForm()) return;

      const years = parseInt(md_petAgeYears?.value || "0", 10);
      const months = parseInt(md_petAgeMonths?.value || "0", 10);
      const totalMonths = years * 12 + months;

      const form = new FormData();
      form.append("user_id", FIXED_USER_ID);
      form.append("pet_type", md_petType?.value || "");
      form.append("pet_name", (md_petName?.value || "").trim());
      form.append("pet_gender", md_petGender?.value || "");
      form.append("home_visit", md_homeVisit?.value || "");
      form.append("role", "pet"); // force role
      form.append("pet_age", totalMonths);
      form.append("breed", md_petType?.value === "Dog" ? (md_petBreed_select?.value || "") : (md_petBreed_input?.value || ""));
      if (md_doc1?.files?.[0]) form.append("pet_doc1", md_doc1.files[0]);
      if (md_doc2?.files?.[0]) form.append("pet_doc2", md_doc2.files[0]);

      const btn = document.getElementById("md_submitBtn");
      if (btn) { btn.disabled = true; btn.textContent = "Saving..."; }

      try {
        const res = await axios.post(`${BACKEND}/auth/register`, form, { headers: { "Content-Type": "multipart/form-data", ...getAuthHeaders() }});
        if (res.data?.message?.toLowerCase().includes("success")) {
          if (res.data.user) {
            sessionStorage.setItem("sessionUser", JSON.stringify({ ...res.data.user, role: "pet" }));
            console.log("sessionUser saved:", JSON.parse(sessionStorage.getItem("sessionUser")));
          } else {
            try {
              const userRes = await axios.get(`${BACKEND}/petparents/${FIXED_USER_ID}`, { headers: { ...getAuthHeaders() }});
              const u = userRes.data?.user || userRes.data;
              if (u) {
                sessionStorage.setItem("sessionUser", JSON.stringify({ ...u, role: "pet" }));
              }
            } catch (e) {}
          }
          localStorage.setItem("petProfileCompleted", "1");
          if (petModal) hide(petModal);
          alert("Pet profile saved successfully!");
        } else {
          alert(res.data?.message || "Failed to save pet data");
        }
      } catch (error) {
        console.error("Registration error:", error);
        alert(error?.response?.data?.message || "Something went wrong!");
      } finally {
        if (btn) { btn.disabled = false; btn.textContent = "Save Pet Details"; }
      }
    }

    // ---------- Modal open check on load ----------
    async function checkAndOpenModal(){
      try {
        if (localStorage.getItem("petProfileCompleted") === "1") {
          try {
            const userRes = await axios.get(`${BACKEND}/petparents/${FIXED_USER_ID}`, { headers: { ...getAuthHeaders() }});
            const u = userRes.data?.user || userRes.data || {};
            if (u?.pet_name && u?.pet_gender && (u?.breed || u?.pet_breed) && (u?.pet_age || u?.age_months >= 0)) {
              if (petModal) hide(petModal);
              return;
            }
          } catch {}
        }
        try {
          const userRes = await axios.get(`${BACKEND}/petparents/${FIXED_USER_ID}`, { headers: { ...getAuthHeaders() }});
          const u = userRes.data?.user || userRes.data || {};
          if (u?.pet_name && u?.pet_gender && (u?.breed || u?.pet_breed) && (u?.pet_age || u?.age_months >= 0)) {
            if (petModal) hide(petModal);
            return;
          }
        } catch (e) {}
        if (petModal) show(petModal);
      } catch {
        if (petModal) show(petModal);
      }
    }

    // ---------- Init ----------
    (async function init(){
      fetchNearbyVets();
      fetchChatRooms();
      if (currentChatRoomToken) fetchChatHistory();

      // open modal on load (with profile check)
      await checkAndOpenModal();
    })();
  </script>

  <script>
async function savePetDetails() {
  const userId = 356; // 🔥 fix user_id updated to 356
  const petType = document.getElementById("petType").value;
  const petName = document.getElementById("petName").value;
  const petGender = document.getElementById("petGender").value;
  const homeVisit = document.getElementById("homeVisit").value;
  const petAgeYears = parseInt(document.getElementById("petAgeYears").value || 0);
  const petAgeMonths = parseInt(document.getElementById("petAgeMonths").value || 0);
  const petBreed = document.getElementById("petBreed").value;

  // ✅ total months
  const totalMonths = petAgeYears * 12 + petAgeMonths;

  const formData = new FormData();
  formData.append("user_id", userId);
  formData.append("pet_type", petType);
  formData.append("pet_name", petName.trim());
  formData.append("pet_gender", petGender);
  formData.append("home_visit", homeVisit);
  formData.append("role", "pet"); // force role
  formData.append("pet_age", totalMonths);
  formData.append("breed", petBreed);

  try {
    const res = await axios.post(
      "https://snoutiq.com/backend/api/auth/register",
      formData,
      { headers: { "Content-Type": "multipart/form-data" } }
    );

    if (res.data.message && res.data.message.includes("successfully")) {
      alert("✅ Pet profile saved successfully!");
      closePetModal();
    } else {
      alert("⚠️ Failed: " + (res.data.message || "Unknown error"));
    }
  } catch (err) {
    console.error("Error saving pet details", err);
    alert("❌ Server error saving pet profile");
  }
}
</script>

<script>
async function sendMessage() {
  const input = document.getElementById("chatInput");
  if (!input.value.trim()) return;
  const question = input.value;
  input.value = "";

  // UI show user msg
  document.getElementById("chatBox").innerHTML += `
    <div class="text-right mb-2">
      <span class="bg-blue-100 px-3 py-2 rounded">${question}</span>
    </div>
  `;

  try {
    const payload = {
      user_id: 356,   // 🔥 static user_id updated to 356
      question,
      context_token: "room_static_12345",   // 🔥 static context
      chat_room_token: "room_static_12345"  // 🔥 static room
    };

const res = await axios.post(`https://snoutiq.com/backend/api/chat/send`, payload);

    const { chat = {}, decision, score } = res.data || {};

    // (optional chips) update only if present
    const decisionChip = document.getElementById("decisionChip");
    const scoreChip = document.getElementById("scoreChip");
    if (decisionChip) decisionChip.innerText = "Decision: " + (decision ?? "—");
    if (scoreChip) scoreChip.innerText = "Score: " + (score ?? "—");

    const answer = chat.answer || "No response";
    document.getElementById("chatBox").innerHTML += `
      <div class="text-left mb-2">
        <span class="bg-gray-200 px-3 py-2 rounded">${answer}</span>
      </div>
    `;
  } catch (e) {
    console.error(e);
    document.getElementById("chatBox").innerHTML += `
      <div class="text-left text-red-600 mb-2">⚠️ Error sending message</div>
    `;
  }
}
</script>


  
</body>
</html>
