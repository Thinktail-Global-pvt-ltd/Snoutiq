{{-- resources/views/payment.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Video Call Payment</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
  <style>.spin{border-top-color:transparent}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">

@php
  // ===== Server-side config / params (safe fallbacks) =====
  $socketUrl   = $socketUrl   ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL','http://127.0.0.1:4000'));
  $backendBase = $backendBase ?? (config('app.backend_base') ?? env('BACKEND_BASE','https://snoutiq.com/backend'));
  $frontBase   = $frontBase   ?? (config('app.frontend_base') ?? env('FRONTEND_BASE','https://snoutiq.com'));

  // Route param + query params
  $callId      = $callId      ?? request()->route('callId');
  $doctorId    = $doctorId    ?? request()->query('doctorId');
  $channel     = $channel     ?? request()->query('channel');
  $patientId   = $patientId   ?? request()->query('patientId');
  $amountQ     = request()->query('amount'); // optional
@endphp

<div id="app" class="p-4 flex items-center justify-center"></div>

<script>
  /** ===== Env from PHP ===== */
  const RAW_SOCKET_URL   = @json($socketUrl);
  const RAW_BACKEND_BASE = @json($backendBase);   // e.g. https://snoutiq.com/backend (fallback)
  const RAW_FRONTEND_BASE= @json($frontBase);     // e.g. https://snoutiq.com (fallback)

  // Choose bases dynamically so local runs hit local API and prod hits /backend
  const ORIGIN    = window.location.origin;                     // http://127.0.0.1:8000 or https://snoutiq.com
  const IS_LOCAL  = /(localhost|127\.0\.0\.1|0\.0\.0\.0)/i.test(window.location.hostname);
  const ON_BACKEND_PATH = window.location.pathname.startsWith('/backend');
  const PATH_PREFIX = IS_LOCAL ? '' : '/backend';                // force /backend on prod

  const BACKEND_BASE = IS_LOCAL
    ? ORIGIN
    : (RAW_BACKEND_BASE || (ORIGIN + PATH_PREFIX));

  const FRONTEND_BASE = IS_LOCAL
    ? ORIGIN
    : (RAW_FRONTEND_BASE || ORIGIN);

  const CALL_ID      = @json($callId);
  const DOCTOR_ID    = Number(@json($doctorId));
  const CHANNEL      = @json($channel);
  const PATIENT_ID   = Number(@json($patientId));
  const AMOUNT_Q     = Number(@json($amountQ)); // may be NaN

  /** ===== Local state ===== */
  let timeLeft = 5 * 60;                // 5 minutes window
  let loading = false;
  let razorpayLoaded = false;
  let paymentStatus = null;             // 'success'|'error'|'cancelled'|'verification-failed'|'timeout'
  const DEFAULT_PRICE = 499;

  const doctorInfo = {
    id: DOCTOR_ID,
    name: `Doctor ${DOCTOR_ID}`,
    specialty: 'Veterinarian',
    amount: Number.isFinite(AMOUNT_Q) && AMOUNT_Q > 0 ? AMOUNT_Q : DEFAULT_PRICE
  };

  /** ===== Helpers ===== */
  const $ = (sel) => document.querySelector(sel);
  const formatTime = (s) => `${Math.floor(s/60)}:${String(s%60).padStart(2,'0')}`;

  /** ===== Socket (optional, for notifying doctor) ===== */
  const SOCKET_URL = (!IS_LOCAL && /localhost|127\.0\.0\.1/i.test(RAW_SOCKET_URL))
    ? ORIGIN
    : RAW_SOCKET_URL;
  const socket = io(SOCKET_URL, { transports:['websocket','polling'], withCredentials:false, path:'/socket.io/' });

  /** ===== Razorpay loader ===== */
  function loadRazorpay(){
    return new Promise((resolve)=>{
      if (window.Razorpay){ razorpayLoaded = true; return resolve(true); }
      const script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.onload = ()=>{ razorpayLoaded = true; resolve(true); };
      script.onerror = ()=>{ razorpayLoaded = false; resolve(false); };
      document.body.appendChild(script);
    });
  }

  /** ===== UI ===== */
  const app = $('#app');

  function renderLoading(){
    app.innerHTML = `
      <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
        <div class="w-12 h-12 border-4 border-blue-500 spin rounded-full animate-spin mx-auto mb-4"></div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Loading Payment Gateway...</h2>
        <p class="text-gray-600">Please wait while we set up secure payment processing</p>
      </div>`;
  }

  function renderTimeout(){
    app.innerHTML = `
      <div class="bg-red-50 rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
        <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"/>
        </svg>
        <h2 class="text-2xl font-bold text-red-800 mb-4">Payment Timeout</h2>
        <p class="text-red-600 mb-6">The payment window has expired. The doctor has been notified.</p>
        <a href="${FRONTEND_BASE}/chat" class="inline-block w-full py-3 bg-red-600 text-white rounded-lg hover:bg-red-700">Back to Chat</a>
      </div>`;
  }

  function renderMain(){
    const priceText = `₹${doctorInfo.amount}`;
    app.innerHTML = `
      <div class="max-w-4xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 text-white">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="w-8 h-8 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
              </svg>
              <div>
                <h1 class="text-2xl font-bold">Video Call Payment</h1>
                <p class="opacity-90">Complete payment to join consultation</p>
              </div>
            </div>
            <div class="text-center">
              <div id="timer" class="text-2xl font-bold">${formatTime(timeLeft)}</div>
              <div class="text-sm opacity-80">Time left</div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6">
          <!-- Left: Details -->
          <div class="space-y-6">
            <div class="bg-green-50 border border-green-200 rounded-xl p-5">
              <div class="flex items-center mb-2">
                <svg class="w-6 h-6 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                </svg>
                <h2 class="text-lg font-semibold text-green-800">Call Accepted</h2>
              </div>
              <p class="text-sm text-green-700">The doctor has accepted your call request and is waiting for you to complete the payment.</p>
            </div>

            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
              <h2 class="text-lg font-semibold text-gray-800 mb-4">Call Details</h2>

              <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                  <span class="text-indigo-600 font-semibold">D${DOCTOR_ID}</span>
                </div>
                <div>
                  <h3 class="font-medium text-gray-900">${doctorInfo.name}</h3>
                  <p class="text-sm text-gray-600">${doctorInfo.specialty}</p>
                </div>
              </div>

              <div class="space-y-3">
                <div class="flex justify-between"><span class="text-gray-600">Duration:</span><span class="font-medium">30 minutes</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Call ID:</span><span class="font-mono text-sm">${CALL_ID}</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Patient ID:</span><span class="font-mono text-sm">${PATIENT_ID}</span></div>
                <div class="flex justify-between text-lg font-semibold mt-4 pt-4 border-t border-gray-200">
                  <span>Total Amount:</span><span class="text-indigo-600">${priceText}</span>
                </div>
              </div>
            </div>

            <div class="bg-blue-50 rounded-xl p-5 border border-blue-200">
              <h3 class="font-semibold text-blue-800 mb-3">What's Included</h3>
              <ul class="space-y-2 text-sm text-blue-700">
                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>One-on-one video consultation</li>
                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>Professional veterinary advice</li>
                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>Post-consultation summary</li>
                <li class="flex items-center"><svg class="w-4 h-4 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>Prescription if needed</li>
              </ul>
            </div>
          </div>

          <!-- Right: Payment -->
          <div class="space-y-6">
            <div class="bg-white rounded-xl p-5 border border-gray-200">
              <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Payment Method</h2>
              <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="p-4 border rounded-lg bg-indigo-50 ring-2 ring-indigo-100 border-indigo-500">
                  <div class="flex items-center">
                    <span class="text-2xl mr-2">💳</span>
                    <div><p class="font-medium text-gray-900 text-sm">Credit/Debit Card</p><p class="text-xs text-gray-500">Pay securely with your card</p></div>
                  </div>
                </div>
                <div class="p-4 border rounded-lg border-gray-300">
                  <div class="flex items-center"><span class="text-2xl mr-2">📱</span>
                    <div><p class="font-medium text-gray-900 text-sm">UPI</p><p class="text-xs text-gray-500">Pay using UPI apps</p></div>
                  </div>
                </div>
                <div class="p-4 border rounded-lg border-gray-300">
                  <div class="flex items-center"><span class="text-2xl mr-2">🏦</span>
                    <div><p class="font-medium text-gray-900 text-sm">Net Banking</p><p class="text-xs text-gray-500">Pay using net banking</p></div>
                  </div>
                </div>
                <div class="p-4 border rounded-lg border-gray-300">
                  <div class="flex items-center"><span class="text-2xl mr-2">💰</span>
                    <div><p class="font-medium text-gray-900 text-sm">Wallet</p><p class="text-xs text-gray-500">Pay using wallet</p></div>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 11c0 1.657-1.343 3-3 3S6 12.657 6 11s1.343-3 3-3 3 1.343 3 3z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7v10a2 2 0 01-2 2H7a2 2 0 01-2-2V7"/>
                </svg>
                <span class="text-sm text-gray-600">Secure & encrypted payment processing</span>
              </div>
            </div>

            <button id="pay-btn"
              class="w-full py-4 px-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
              <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10M5 6h14"/>
              </svg>
              Pay ${priceText} & Join Call
            </button>

            <div id="status-box" class="hidden p-4 rounded-xl"></div>

            <div class="text-center">
              <div class="flex items-center justify-center space-x-6 mb-2 text-xs text-gray-600">
                <div class="flex items-center"><svg class="w-4 h-4 text-green-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>SSL Secure</div>
                <div class="flex items-center"><svg class="w-4 h-4 text-green-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.657-1.343 3-3 3S6 12.657 6 11s1.343-3 3-3 3 1.343 3 3z"/></svg>Encrypted</div>
                <div class="flex items-center"><svg class="w-4 h-4 text-green-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10M5 6h14"/></svg>PCI DSS Compliant</div>
              </div>
              <p class="text-xs text-gray-500">Your payment information is secure and encrypted</p>
            </div>

            <div id="expire-note" class="hidden p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800 font-medium"></div>
          </div>
        </div>

        <div class="bg-gray-50 p-4 border-t border-gray-200 text-center">
          <p class="text-sm text-gray-600">Need help? Contact <span class="text-indigo-600">support@snoutiq.com</span></p>
        </div>
      </div>`;
    $('#pay-btn').addEventListener('click', handlePayment);
  }

  function showStatus(cls, text){
    const box = $('#status-box');
    box.className = `p-4 rounded-xl ${cls}`;
    box.textContent = text;
    box.classList.remove('hidden');
  }

  /** ===== Timer ===== */
  function startTimer(){
    const timerEl = $('#timer');
    const expireEl = $('#expire-note');
    const t = setInterval(()=>{
      timeLeft--;
      if (timerEl) timerEl.textContent = formatTime(timeLeft);
      if (timeLeft === 60 && expireEl){
        expireEl.textContent = `Hurry! Payment window expires in ${formatTime(timeLeft)}`;
        expireEl.classList.remove('hidden');
      }
      if (timeLeft <= 0){
        clearInterval(t);
        paymentStatus = 'timeout';
        // notify server/doctor
        socket.emit('payment-cancelled', {
          callId: CALL_ID, patientId: PATIENT_ID, doctorId: DOCTOR_ID, reason: 'timeout'
        });
        renderTimeout();
      }
    }, 1000);
  }

  /** ===== Payment Flow ===== */
  async function handlePayment(){
    if (!window.Razorpay){
      showStatus('bg-red-50 border border-red-200 text-red-800', 'Gateway failed to load. Please refresh.');
      return;
    }
    if (loading) return;
    loading = true;
    showStatus('bg-blue-50 border border-blue-200 text-blue-800', 'Creating order...');

    try {
      // 1) Create order on backend (expect: { success, order_id, key })
      const res = await fetch(`${BACKEND_BASE}/api/create-order`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
          amount: doctorInfo.amount,
          callId: CALL_ID,
          doctorId: DOCTOR_ID,
          patientId: PATIENT_ID,
          channel: CHANNEL
        })
      });
      const order = await res.json();
      if (!order?.success || !order?.order_id || !order?.key){
        throw new Error('Invalid order response');
      }

      // 2) Open Razorpay
      const options = {
        key: order.key,
        order_id: order.order_id,
        name: "Snoutiq Veterinary Consultation",
        description: `Video consultation with Doctor ${DOCTOR_ID}`,
        image: `${FRONTEND_BASE}/logo.webp`,
        theme: { color: "#4F46E5" },
        handler: async (rp) => {
          try {
            // 3) Verify payment with backend
            const verify = await fetch(`${BACKEND_BASE}/api/rzp/verify`, {
              method:'POST',
              headers:{'Content-Type':'application/json'},
              body: JSON.stringify({
                callId: CALL_ID,
                doctorId: DOCTOR_ID,
                patientId: PATIENT_ID,
                channel: CHANNEL,
                razorpay_order_id: rp.razorpay_order_id,
                razorpay_payment_id: rp.razorpay_payment_id,
                razorpay_signature: rp.razorpay_signature
              })
            });
            const vres = await verify.json();
            if (!vres?.success) throw new Error('Verification failed');

            // 4) Notify via socket (optional)
            socket.emit('payment-completed', {
              callId: CALL_ID, patientId: PATIENT_ID, doctorId: DOCTOR_ID, channel: CHANNEL,
              paymentId: rp.razorpay_payment_id
            });

            paymentStatus = 'success';
            showStatus('bg-green-50 border border-green-200 text-green-800', 'Payment successful! Joining call...');

            // 5) ABSOLUTE REDIRECT (role=host) — as requested
            window.location.href =
              `${FRONTEND_BASE}/call-page/${encodeURIComponent(CHANNEL)}?uid=${encodeURIComponent(PATIENT_ID)}&role=host`;
          } catch (e) {
            paymentStatus = 'verification-failed';
            showStatus('bg-red-50 border border-red-200 text-red-800', 'Payment verification failed. Please contact support.');
          } finally {
            loading = false;
          }
        },
        modal: {
          ondismiss: () => {
            paymentStatus = 'cancelled';
            loading = false;
            showStatus('bg-yellow-50 border border-yellow-200 text-yellow-800', 'Payment cancelled. Doctor is still waiting.');
            socket.emit('payment-cancelled', {
              callId: CALL_ID, patientId: PATIENT_ID, doctorId: DOCTOR_ID, reason: 'user-cancelled'
            });
          }
        }
      };

      const rzp = new window.Razorpay(options);
      rzp.open();
      showStatus('bg-blue-50 border border-blue-200 text-blue-800', 'Opening secure payment...');
    } catch (err){
      console.error(err);
      paymentStatus = 'error';
      showStatus('bg-red-50 border border-red-200 text-red-800', 'Payment failed. Please try again.');
      loading = false;
    }
  }

  /** ===== Boot ===== */
  (async ()=>{
    renderLoading();
    await loadRazorpay();
    if (!razorpayLoaded){
      app.innerHTML = `
        <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
          <div class="w-12 h-12 border-4 border-red-500 spin rounded-full animate-spin mx-auto mb-4"></div>
          <h2 class="text-xl font-semibold text-gray-800 mb-2">Failed to load Razorpay</h2>
          <p class="text-gray-600">Please check your internet and refresh the page.</p>
        </div>`;
      return;
    }
    renderMain();
    startTimer();
  })();
</script>
</body>
</html>
