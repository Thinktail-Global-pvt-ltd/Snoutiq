import React, { useEffect, useState, useRef } from "react";

// Inline helper to base64-decode the JWT payload safely in the browser
const decodeJwt = (token) => {
  try {
    const base64Url = token.split(".")[1];
    const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
    const jsonPayload = decodeURIComponent(
      window
        .atob(base64)
        .split("")
        .map((c) => "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2))
        .join("")
    );
    return JSON.parse(jsonPayload);
  } catch (error) {
    console.error("JWT decoding failed:", error);
    return null;
  }
};

const GoogleAuthTest = () => {
  const [clientId, setClientId] = useState(
    () => import.meta.env.VITE_GOOGLE_CLIENT_ID || ""
  );
  const [logs, setLogs] = useState([]);
  const [isScriptLoaded, setIsScriptLoaded] = useState(false);
  const [userProfile, setUserProfile] = useState(null);
  const [rawCredential, setRawCredential] = useState("");
  const buttonContainerRef = useRef(null);

  const addLog = (message) => {
    const timestamp = new Date().toLocaleTimeString();
    setLogs((prev) => [...prev, `[${timestamp}] ${message}`]);
  };

  // 1. Dynamically load the Google Identity Services client script
  useEffect(() => {
    addLog("Checking if Google Identity script is already loaded...");
    if (window.google?.accounts?.id) {
      setIsScriptLoaded(true);
      addLog("Google Identity script is already present.");
      return;
    }

    addLog("Loading Google Identity script from accounts.google.com...");
    const script = document.createElement("script");
    script.src = "https://accounts.google.com/gsi/client";
    script.async = true;
    script.defer = true;
    script.onload = () => {
      setIsScriptLoaded(true);
      addLog("Google Identity script loaded successfully.");
    };
    script.onerror = () => {
      addLog("CRITICAL: Failed to load Google Identity script.");
    };
    document.head.appendChild(script);

    return () => {
      // Clean up script on unmount
      if (script.parentNode) {
        script.parentNode.removeChild(script);
      }
    };
  }, []);

  // 2. Initialize and render the button whenever script loads or client ID changes
  useEffect(() => {
    if (!isScriptLoaded) return;
    if (!clientId) {
      addLog("Google Client ID is empty. Please enter a valid Client ID.");
      return;
    }

    try {
      addLog(`Initializing Google Auth with Client ID: ${clientId.substring(0, 15)}...`);
      
      window.google.accounts.id.initialize({
        client_id: clientId,
        callback: handleCredentialResponse,
        auto_select: false,
        cancel_on_tap_outside: true,
      });

      addLog("Google client initialized. Rendering Sign-In button...");

      if (buttonContainerRef.current) {
        buttonContainerRef.current.innerHTML = ""; // Clear old button
        window.google.accounts.id.renderButton(buttonContainerRef.current, {
          theme: "filled_blue",
          size: "large",
          shape: "pill",
          text: "signin_with",
          width: 280,
        });
        addLog("Google Sign-In button rendered successfully.");
      }

      // Prompt One Tap
      addLog("Prompting Google One Tap...");
      window.google.accounts.id.prompt((notification) => {
        if (notification.isNotDisplayed()) {
          addLog(`One Tap not displayed. Reason: ${notification.getNotDisplayedReason()}`);
        } else if (notification.isSkippedMoment()) {
          addLog(`One Tap skipped. Reason: ${notification.getSkippedReason()}`);
        } else if (notification.isDismissedMoment()) {
          addLog(`One Tap dismissed. Reason: ${notification.getDismissedReason()}`);
        } else {
          addLog("One Tap notification displayed.");
        }
      });
    } catch (err) {
      addLog(`Error during initialization/rendering: ${err.message}`);
    }
  }, [isScriptLoaded, clientId]);

  const handleCredentialResponse = (response) => {
    addLog("Credential callback received from Google!");
    if (!response.credential) {
      addLog("No credential received in the response.");
      return;
    }

    setRawCredential(response.credential);
    addLog("Decoding credential JWT...");
    const profile = decodeJwt(response.credential);

    if (profile) {
      setUserProfile(profile);
      addLog(`Authentication successful! Logged in as ${profile.name || profile.email}`);

      // Send to backend endpoint to store in users table
      const getBaseUrl = () => {
        const envUrl = import.meta.env.VITE_BACKEND_BASE_URL;
        if (envUrl) return `${envUrl}/api`;
        const origin = window.location.origin;
        if (origin.includes("snoutiq.com") && !origin.includes("app.snoutiq.com")) {
          return `${origin}/backend/api`;
        }
        return "http://127.0.0.1:8000/api";
      };

      addLog("Sending payload to backend API /api/google-store-user...");
      fetch(`${getBaseUrl()}/google-store-user`, {
        method: "POST",
        headers: {
          "Accept": "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          email: profile.email,
          name: profile.name,
          google_token: response.credential,
        }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            addLog(`Backend storage success! User ID registered: ${data.user_id}`);
          } else {
            addLog(`Backend storage failed: ${data.message || "Unknown error"}`);
          }
        })
        .catch((err) => {
          addLog(`Backend API error: ${err.message}`);
        });
    } else {
      addLog("Failed to decode user profile from JWT.");
    }
  };

  const handleReset = () => {
    setUserProfile(null);
    setRawCredential("");
    addLog("Profile cleared.");
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-stone-900 to-indigo-950 text-white flex flex-col p-4 md:p-8">
      {/* Container */}
      <div className="max-w-4xl w-full mx-auto flex-1 flex flex-col gap-6">
        
        {/* Header */}
        <header className="flex flex-col gap-1 border-b border-white/10 pb-4">
          <div className="flex items-center gap-2">
            <span className="text-xl">🔑</span>
            <h1 className="text-2xl font-bold tracking-tight bg-gradient-to-r from-indigo-400 to-violet-400 bg-clip-text text-transparent">
              Google OAuth Integration Lab
            </h1>
          </div>
          <p className="text-sm text-stone-400">
            A real-time laboratory to test and debug Google Identity Services authentication tokens.
          </p>
        </header>

        {/* Content Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 flex-1 items-stretch">
          
          {/* Left Column: Configuration & Authentication */}
          <div className="flex flex-col gap-6">
            
            {/* Config Card */}
            <div className="bg-white/5 border border-white/10 rounded-xl p-5 backdrop-blur-md flex flex-col gap-4">
              <h2 className="text-md font-semibold text-indigo-300 flex items-center gap-2">
                ⚙️ Configuration
              </h2>
              
              <div className="flex flex-col gap-2">
                <label className="text-xs text-stone-400 font-medium">Google Client ID</label>
                <input
                  type="text"
                  placeholder="Paste your Client ID here (e.g. xxx.apps.googleusercontent.com)"
                  value={clientId}
                  onChange={(e) => {
                    setClientId(e.target.value);
                    addLog("Client ID changed by user.");
                  }}
                  className="w-full bg-black/40 border border-white/15 rounded-lg px-3 py-2 text-sm text-stone-200 placeholder-stone-500 focus:outline-none focus:border-indigo-500 transition-colors"
                />
                <span className="text-[10px] text-stone-400">
                  Tip: Get your Client ID from the Google Cloud Console.
                </span>
              </div>
            </div>

            {/* Auth Execution Card */}
            <div className="bg-white/5 border border-white/10 rounded-xl p-5 backdrop-blur-md flex flex-col gap-5 items-center justify-center min-h-[220px]">
              <h2 className="text-md font-semibold text-indigo-300 w-full text-left">
                ⚡ Authenticate
              </h2>

              {!userProfile ? (
                <div className="flex flex-col items-center gap-4 py-6">
                  {/* Google Button Div */}
                  <div
                    ref={buttonContainerRef}
                    id="google-button-div"
                    className="min-h-[44px]"
                  ></div>
                  <p className="text-xs text-stone-400 text-center max-w-[280px]">
                    One Tap may also prompt automatically if you are logged in to Chrome/Google Accounts.
                  </p>
                </div>
              ) : (
                <div className="flex flex-col items-center gap-4 w-full py-4 text-center">
                  <div className="w-16 h-16 rounded-full overflow-hidden border-2 border-indigo-500">
                    <img
                      src={userProfile.picture || "https://www.gravatar.com/avatar?d=mp"}
                      alt="Profile"
                      className="w-full h-full object-cover"
                    />
                  </div>
                  <div className="flex flex-col gap-1">
                    <span className="font-semibold text-lg">{userProfile.name}</span>
                    <span className="text-sm text-stone-300">{userProfile.email}</span>
                  </div>
                  <div className="text-[10px] text-stone-400 bg-black/40 px-2 py-1 rounded">
                    Subject ID: {userProfile.sub}
                  </div>
                  
                  <button
                    onClick={handleReset}
                    className="mt-2 text-xs font-semibold px-4 py-2 bg-white/10 hover:bg-white/15 active:scale-95 text-stone-200 rounded-lg transition-all"
                  >
                    Log Out / Switch Account
                  </button>
                </div>
              )}
            </div>

          </div>

          {/* Right Column: Console Logs & Raw Tokens */}
          <div className="flex flex-col gap-6">
            
            {/* Logs Console */}
            <div className="bg-black/50 border border-white/10 rounded-xl p-5 flex flex-col gap-3 flex-1 min-h-[300px]">
              <div className="flex items-center justify-between border-b border-white/10 pb-2">
                <h2 className="text-md font-semibold text-indigo-300 flex items-center gap-2">
                  🖥️ Console Logs
                </h2>
                <button
                  onClick={() => setLogs([])}
                  className="text-[10px] uppercase font-bold text-stone-400 hover:text-stone-200"
                >
                  Clear Console
                </button>
              </div>

              <div className="flex-1 overflow-y-auto text-xs font-mono text-stone-300 flex flex-col gap-1.5 max-h-[400px] pr-2">
                {logs.length === 0 ? (
                  <span className="text-stone-500 italic">No logs recorded yet...</span>
                ) : (
                  logs.map((log, index) => (
                    <div key={index} className="leading-relaxed break-all">
                      {log}
                    </div>
                  ))
                )}
              </div>
            </div>

            {/* Raw JWT Token */}
            {rawCredential && (
              <div className="bg-white/5 border border-white/10 rounded-xl p-5 flex flex-col gap-3">
                <h2 className="text-md font-semibold text-indigo-300">
                  🎫 Raw Identity Token (JWT)
                </h2>
                <div className="bg-black/60 p-3 rounded-lg border border-white/10">
                  <p className="text-[10px] font-mono text-stone-400 break-all select-all">
                    {rawCredential}
                  </p>
                </div>
                <button
                  onClick={() => {
                    navigator.clipboard.writeText(rawCredential);
                    addLog("Raw JWT copied to clipboard.");
                  }}
                  className="text-xs bg-indigo-600 hover:bg-indigo-700 active:scale-98 font-semibold text-white py-1.5 rounded-lg transition-all"
                >
                  Copy Raw Token
                </button>
              </div>
            )}

          </div>

        </div>

      </div>
    </div>
  );
};

export default GoogleAuthTest;
