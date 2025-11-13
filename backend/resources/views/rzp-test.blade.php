{{-- resources/views/rzp-test.blade.php --}}
@php
  $ORDER_URL  = url('/api/create-order');
  $VERIFY_URL = url('/api/rzp/verify');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Razorpay Test Checkout</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    :root { --bg:#0f172a; --card:#111827; --text:#e5e7eb; --muted:#9ca3af; --ok:#22c55e; --err:#ef4444; --pri:#3b82f6; }
    * { box-sizing: border-box; }
    body { margin:0; background:var(--bg); color:var(--text); font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial;}
    .wrap { max-width:880px; margin:40px auto; padding:0 16px;}
    .card { background:var(--card); border:1px solid #1f2937; border-radius:16px; padding:20px; box-shadow:0 8px 30px rgba(0,0,0,.25); }
    h1 { margin:0 0 12px; font-size:24px; }
    p { color:var(--muted); margin:0 0 16px; }
    .row { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:12px;}
    .input { background:#0b1220; color:var(--text); border:1px solid #263041; border-radius:12px; padding:12px 14px; font-size:16px; width:180px; }
    .btn { cursor:pointer; border:none; border-radius:12px; padding:12px 16px; font-weight:600; }
    .btn-primary { background:var(--pri); color:white; }
    .btn-secondary { background:#374151; color:#fff; }
    .log { white-space:pre-wrap; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; background:#0b1220; color:#d1d5db; border:1px solid #263041; border-radius:12px; padding:14px; max-height:360px; overflow:auto; }
    .tag { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; border:1px solid #1f2937; margin-left:8px;}
    .ok { color:var(--ok); border-color:#14532d; background:#052e16; }
    .err{ color:var(--err); border-color:#4c0519; background:#1f0a12; }
    a { color:#93c5fd; }
    small { color:var(--muted); }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>Razorpay Test Checkout
        <span id="status" class="tag">idle</span>
      </h1>
      <p>Amount (INR) set karo → <b>Create &amp; Pay</b>. Success pe server <code>/api/rzp/verify</code> call karke <code>payments</code> table me save karega.</p>

      <div class="row">
        <input id="amount" class="input" type="number" min="1" step="1" value="500" />
        <button id="btnPay" class="btn btn-primary">Create &amp; Pay (Test)</button>
        <button id="btnRandom" class="btn btn-secondary" title="99–9999 random amount">Random</button>
        <button id="btnReset" class="btn btn-secondary">Reset Log</button>
      </div>

      <small>
        Test card: <b>4111 1111 1111 1111</b> · any future expiry · CVV <b>123</b>. — No real charge in test mode.
      </small>

      <h3 style="margin:18px 0 8px;">Log</h3>
      <div class="log" id="log"></div>
    </div>
  </div>

  <!-- Razorpay Checkout -->
  <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
  <script>
    const ORDER_URL  = @json($ORDER_URL);
    const VERIFY_URL = @json($VERIFY_URL);

    const $ = (s) => document.querySelector(s);
    const statusEl = $('#status');
    const logEl = $('#log');

    function setStatus(text, state) {
      statusEl.textContent = text;
      statusEl.className = 'tag ' + (state === true ? 'ok' : state === false ? 'err' : '');
    }

    function log(data, label='') {
      const time = new Date().toLocaleTimeString();
      logEl.textContent += (label ? `[${time}] ${label}\n` : `[${time}] `) + (
        typeof data === 'string' ? data : JSON.stringify(data, null, 2)
      ) + "\n\n";
      logEl.scrollTop = logEl.scrollHeight;
    }

    async function createOrder(amount) {
      const res = await fetch(ORDER_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          amount,
          order_type: 'test',
        })
      });
      if (!res.ok) throw new Error('Order API failed: ' + res.status);
      return res.json();
    }

    async function verifyPayment(payload) {
      const res = await fetch(VERIFY_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (!res.ok) throw new Error('Verify API failed: ' + res.status);
      return res.json();
    }

    $('#btnRandom').addEventListener('click', () => {
      const v = Math.floor(Math.random() * (9999 - 99 + 1)) + 99;
      $('#amount').value = v;
    });

    $('#btnReset').addEventListener('click', () => {
      logEl.textContent = '';
      setStatus('idle');
    });

    $('#btnPay').addEventListener('click', async () => {
      try {
        if (typeof Razorpay === 'undefined') {
          alert('Checkout JS not loaded'); return;
        }
        const amt = parseInt($('#amount').value || '0', 10);
        if (!Number.isInteger(amt) || amt < 1) {
          alert('Amount invalid'); return;
        }

        setStatus('creating order…');
        log({ amount: amt }, 'REQUEST create-order');
        const orderRes = await createOrder(amt);
        log(orderRes, 'RESPONSE create-order');

        if (!orderRes?.success || !orderRes?.order_id || !orderRes?.key) {
          throw new Error('Invalid order response');
        }

        setStatus('opening checkout…');
        const rzp = new Razorpay({
          key: orderRes.key,
          order_id: orderRes.order_id,
          name: 'SnoutIQ Test',
          description: 'Test payment',
          prefill: { name: 'Test User', email: 'test@example.com', contact: '9999999999' },
          theme: { color: '#3b82f6' },
          handler: async function (resp) {
            const payload = {
              razorpay_order_id: resp.razorpay_order_id,
              razorpay_payment_id: resp.razorpay_payment_id,
              razorpay_signature: resp.razorpay_signature,
              order_type: 'test',
            };
            log(payload, 'REQUEST verify');
            try {
              const verifyRes = await verifyPayment(payload);
              log(verifyRes, 'RESPONSE verify');
              if (verifyRes?.success || verifyRes?.verified) {
                setStatus('verified ✅', true);
              } else {
                setStatus('verify failed', false);
              }
            } catch (e) {
              log(String(e), 'VERIFY ERROR');
              setStatus('verify failed', false);
            }
          }
        });

        rzp.on('payment.failed', function (resp) {
          log(resp.error || resp, 'CHECKOUT payment.failed');
          setStatus('failed', false);
          alert('Payment failed: ' + (resp?.error?.description || ''));
        });

        rzp.open();
      } catch (err) {
        log(String(err), 'ERROR');
        setStatus('error', false);
        alert(err.message || err);
      }
    });
  </script>
</body>
</html>
