<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register | Test Clinic</title>
  <link rel="icon" href="https://snoutiq.com/favicon.webp" sizes="32x32" type="image/png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <style>
    body{margin:0;font-family:Inter,system-ui,sans-serif;background:#f9fafb;}
    .toast{position:fixed;right:16px;bottom:16px;background:#111827;color:#fff;
      padding:.7rem .9rem;border-radius:.6rem;box-shadow:0 10px 24px rgba(0,0,0,.25);
      opacity:0;transform:translateY(8px);transition:.3s}
    .toast.show{opacity:1;transform:translateY(0)}
    .tab.active{background:#2563eb;color:#fff}
  </style>
</head>
<body>

  <!-- UI -->
  <main style="max-width:400px;margin:40px auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.1)">
    <h1 style="text-align:center;margin-bottom:10px">Welcome to Test Clinic!</h1>
    <p style="text-align:center;color:#64748b">Let's start by getting to know you</p>

    <!-- Tabs -->
    <div style="display:flex;gap:8px;margin:16px 0">
      <button id="tab-pet" class="tab active" style="flex:1;padding:10px;border:1px solid #e5e7eb;border-radius:8px;background:#2563eb;color:#fff">Pet Owner</button>
      <button id="tab-vet" class="tab" style="flex:1;padding:10px;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb">Veterinarian</button>
    </div>

    <!-- Location Badge -->
    <div id="locBadge" style="padding:10px;border-radius:8px;text-align:center;border:1px solid #e5e7eb;background:#f3f4f6;margin-bottom:16px">
      Checking location permission...
    </div>

    <!-- Google login -->
    <div id="googleBtn" style="margin:12px auto;text-align:center;min-height:44px"></div>
    <p id="googleMsg" style="text-align:center;font-size:14px;margin-top:8px;color:#dc2626"></p>

    <!-- Vet email -->
    <div id="vetPanel" style="display:none;margin-top:16px">
      <input id="vetEmail" placeholder="Work email" style="width:100%;padding:10px;margin-bottom:8px;border:1px solid #e5e7eb;border-radius:8px"/>
      <input id="vetPass" type="password" placeholder="Password" style="width:100%;padding:10px;margin-bottom:8px;border:1px solid #e5e7eb;border-radius:8px"/>
      <button id="vetCta" style="width:100%;padding:10px;background:#2563eb;color:#fff;border:none;border-radius:8px">Continue</button>
    </div>
  </main>

  <div id="toast" class="toast"></div>

  <script>
    let locationStatus = "checking";
    let coords = { lat:null, lng:null };

    const toast = (msg,type="info")=>{
      const el=document.getElementById("toast");
      el.textContent=msg;
      el.style.background = type==="error" ? "#dc2626" : (type==="success"?"#16a34a":"#111827");
      el.classList.add("show");
      setTimeout(()=>el.classList.remove("show"),2500);
    };

    // Tab switching
    document.getElementById("tab-pet").onclick=()=>{
      document.getElementById("vetPanel").style.display="none";
      document.getElementById("tab-pet").classList.add("active");
      document.getElementById("tab-vet").classList.remove("active");
    };
    document.getElementById("tab-vet").onclick=()=>{
      document.getElementById("vetPanel").style.display="block";
      document.getElementById("tab-vet").classList.add("active");
      document.getElementById("tab-pet").classList.remove("active");
    };

    // Location
    const badge=document.getElementById("locBadge");
    function setBadge(status,msg,color){
      badge.textContent=msg;
      badge.style.background=color;
    }
    function requestLocation(){
      navigator.geolocation.getCurrentPosition(pos=>{
        locationStatus="granted";
        coords.lat=pos.coords.latitude; coords.lng=pos.coords.longitude;
        setBadge("granted","✅ Location access granted","#dcfce7");
        toast("Location access granted","success");
      },err=>{
        locationStatus="denied";
        setBadge("denied","❌ Location denied","#fee2e2");
        toast("Location access denied","error");
      });
    }
    if(navigator.geolocation){ requestLocation(); } else { setBadge("denied","Not supported","#fee2e2"); }

    // Google login
    window.onGoogleCredential = async (response)=>{
      if(locationStatus!=="granted"){ 
        document.getElementById("googleMsg").textContent="❌ Please allow location access first";
        toast("Location required","error"); return;
      }
      const base64Url = response.credential.split(".")[1];
      const base64 = base64Url.replace(/-/g,"+").replace(/_/g,"/");
      const jsonPayload = decodeURIComponent(atob(base64).split("").map(c=>"%"+("00"+c.charCodeAt(0).toString(16)).slice(-2)).join(""));
      const googleData = JSON.parse(jsonPayload);
      const email=googleData.email; const googleToken=googleData.sub;

      try{
        // 1. Try login with role pet
        let loginRes=await axios.post("https://snoutiq.com/backend/api/google-login",{ email, google_token:googleToken, role:"pet" });
        if(loginRes.data.status==="success"){
          toast("Login successful!","success");
          location.href="/dashboard"; return;
        }
      }catch(e){ console.warn("Login failed, will register"); }

      // 2. Register
      let reg=await fetch("https://snoutiq.com/backend/api/auth/initial-register",{
        method:"POST",headers:{ "Content-Type":"application/json" },
        body:JSON.stringify({ fullName:googleData.name,email,google_token:googleToken,latitude:coords.lat,longitude:coords.lng })
      });
      let regData=await reg.json();
      if(regData.status==="error"){ document.getElementById("googleMsg").textContent=regData.message; return; }

      // 3. Login again with role pet
      let finalLogin=await axios.post("https://snoutiq.com/backend/api/google-login",{ email, google_token:googleToken, role:"pet" });
      if(finalLogin.data.token){ toast("Registered & logged in!","success"); location.href="https://snoutiq.com/backend/dashboard"; }
    };

    window.onload=()=>{
      if(window.google && google.accounts){
        google.accounts.id.initialize({
          client_id:"325007826401-dhsrqhkpoeeei12gep3g1sneeg5880o7.apps.googleusercontent.com",
          callback:onGoogleCredential
        });
        google.accounts.id.renderButton(document.getElementById("googleBtn"),{ theme:"filled_blue", size:"large" });
      }
    };

    // Vet CTA
    document.getElementById("vetCta").onclick=()=>{
      const email=document.getElementById("vetEmail").value;
      const pass=document.getElementById("vetPass").value;
      if(!email||!pass){ toast("Email & password required","error"); return; }
      toast("Vet register flow started","info");
      // TODO: call your API
    };
  </script>
</body>
</html>
