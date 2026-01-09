<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register | Test Clinic</title>
  <link rel="icon" href="{{ asset('favicon.png') }}" sizes="32x32" type="image/png"/>
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
    <p style="text-align:center;color:#64748b">Sign up as a pet owner to continue</p>

    <!-- Location Badge -->
    <div id="locBadge" style="padding:10px;border-radius:8px;text-align:center;border:1px solid #e5e7eb;background:#f3f4f6;margin-bottom:16px">
      Checking location permission...
    </div>

    <!-- Google login -->
    <div id="googleBtn" style="margin:12px auto;text-align:center;min-height:44px"></div>
    <p id="googleMsg" style="text-align:center;font-size:14px;margin-top:8px;color:#dc2626"></p>
  </main>

  <div id="toast" class="toast"></div>

  <script>
    // Dynamic base so it works on local and production
    const PATH_PREFIX = "{{ trim(config('app.path_prefix') ?? env('APP_PATH_PREFIX', ''), '/') }}";
    const BASE = PATH_PREFIX ? ('/' + PATH_PREFIX) : '';
    const API  = BASE + '/api';

    let locationStatus = "checking";
    let coords = { lat:null, lng:null };

    const toast = (msg,type="info")=>{
      const el=document.getElementById("toast");
      el.textContent=msg;
      el.style.background = type==="error" ? "#dc2626" : (type==="success"?"#16a34a":"#111827");
      el.classList.add("show");
      setTimeout(()=>el.classList.remove("show"),2500);
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

    // Google login -> pet flow only
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
        // 1) Try login (role: pet)
        let loginRes=await axios.post(`${API}/google-login`,{ email, google_token:googleToken, role:"pet" });
        if(loginRes.data.status==="success" || loginRes.data.token){
          toast("Login successful!","success");
          location.href = `${BASE}/dashboard`;
          return;
        }
      }catch(e){ console.warn("Login failed, will register"); }

      // 2) Register (initial)
      let reg=await fetch(`${API}/auth/initial-register`,{
        method:"POST",headers:{ "Content-Type":"application/json" },
        body:JSON.stringify({ fullName:googleData.name,email,google_token:googleToken,latitude:coords.lat,longitude:coords.lng })
      });
      let regData=await reg.json();
      if(regData.status==="error"){ document.getElementById("googleMsg").textContent=regData.message || "Registration error"; return; }

      // 3) Login again (role: pet)
      let finalLogin=await axios.post(`${API}/google-login`,{ email, google_token:googleToken, role:"pet" });
      if(finalLogin.data.token || finalLogin.data.status==="success"){
        toast("Registered & logged in!","success");
        location.href = `${BASE}/dashboard`;
      }else{
        document.getElementById("googleMsg").textContent="Could not log you in. Please try again.";
      }
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
  </script>
</body>
</html>
