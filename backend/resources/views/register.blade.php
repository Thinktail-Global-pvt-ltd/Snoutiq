<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register | SnoutIQ</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Google Identity Services -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center px-4 py-8">

  <div class="w-full max-w-sm sm:max-w-md">
    <div class="bg-white rounded-xl shadow-xl p-6 sm:p-8 text-center">

      <!-- Logo -->
      <div class="mb-6">
        <img src="{{ asset('images/logo.webp') }}" alt="Snoutiq Logo" class="h-6 mx-auto mb-3 cursor-pointer"/>
      </div>

      <!-- Welcome Message -->
      <div class="mb-4 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Welcome to Snoutiq!</h1>
        <p class="text-sm sm:text-base text-gray-600">Let's start by getting to know you</p>
      </div>

      <!-- Tabs -->
      <div class="mb-6">
        <div class="flex bg-gray-100 rounded-lg p-1">
          <button id="tab-owner" class="flex-1 py-2 px-4 rounded-md text-sm font-medium bg-white text-blue-600 shadow-sm">Pet Owner</button>
          <button id="tab-vet" class="flex-1 py-2 px-4 rounded-md text-sm font-medium text-gray-600 hover:text-gray-800">Veterinarian</button>
        </div>
      </div>

      <!-- Location Badge -->
      <div id="locationStatus" class="mb-4 text-sm"></div>

      <!-- Google Login -->
      <div class="mb-6">
        <div class="flex justify-center">
          <div id="googleBtn" class="w-full max-w-sm border rounded-xl shadow-md p-4 bg-white flex justify-center"></div>
        </div>
        <p id="googleMessage" class="mt-3 text-sm text-center"></p>
      </div>

      <!-- Footer -->
      <div class="mt-6 pt-4 border-t border-gray-200">
        <p class="text-gray-600 text-sm">
          Already have an account?
          <a href="{{ route('login') }}" class="text-blue-600 hover:underline font-medium">Login here</a>
        </p>
      </div>
    </div>
  </div>

  <script>
    let userType = "pet_owner";
    let coords = { lat: null, lng: null };

    // Tab switching
    const tabOwner = document.getElementById("tab-owner");
    const tabVet = document.getElementById("tab-vet");
    tabOwner.addEventListener("click", () => {
      userType = "pet_owner";
      tabOwner.classList.add("bg-white", "text-blue-600", "shadow-sm");
      tabVet.classList.remove("bg-white", "text-blue-600", "shadow-sm");
      tabVet.classList.add("text-gray-600");
    });
    tabVet.addEventListener("click", () => {
      userType = "veterinarian";
      tabVet.classList.add("bg-white", "text-blue-600", "shadow-sm");
      tabOwner.classList.remove("bg-white", "text-blue-600", "shadow-sm");
      tabOwner.classList.add("text-gray-600");
      // redirect if vet
      window.location.href = "/vet-register";
    });

    // Location handling
    const statusEl = document.getElementById("locationStatus");
    function setStatus(msg, color) {
      statusEl.innerHTML = `<div class="p-2 rounded-lg ${color}">${msg}</div>`;
    }

    function requestLocation() {
      navigator.geolocation.getCurrentPosition(
        pos => {
          coords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
          setStatus("✅ Location access granted", "bg-green-50 text-green-600");
        },
        err => {
          console.error("Location error:", err);
          setStatus("❌ Location denied. Please enable it.", "bg-red-50 text-red-600");
        }
      );
    }
    if (navigator.geolocation) {
      requestLocation();
    } else {
      setStatus("❌ Location not supported", "bg-red-50 text-red-600");
    }

    // Google Login
    window.onGoogleCredential = async (response) => {
      const messageEl = document.getElementById("googleMessage");
      try {
        const base64Url = response.credential.split(".")[1];
        const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
        const jsonPayload = decodeURIComponent(atob(base64).split("").map(function(c) {
          return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(""));
        const googleData = JSON.parse(jsonPayload);

        const email = googleData.email;
        const googleToken = googleData.sub;

        // TODO: call backend login/register
        messageEl.textContent = "✅ Google credential received: " + email;
        messageEl.className = "text-green-600 text-sm text-center";
        // Example redirect
        // window.location.href = "/dashboard";
      } catch (err) {
        console.error(err);
        messageEl.textContent = "❌ Google login failed";
        messageEl.className = "text-red-600 text-sm text-center";
      }
    };

    window.addEventListener("load", function(){
      if (window.google && google.accounts && google.accounts.id) {
        google.accounts.id.initialize({
          client_id: "325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com",
          callback: onGoogleCredential
        });
        google.accounts.id.renderButton(
          document.getElementById("googleBtn"),
          { theme:"filled_blue", size:"large", text:"continue_with", shape:"rectangular" }
        );
      }
    });
  </script>
</body>
</html>
