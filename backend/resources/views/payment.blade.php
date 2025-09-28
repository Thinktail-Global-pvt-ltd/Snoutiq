<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js" crossorigin="anonymous"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
@php
  $socketUrl = $socketUrl ?? (config('app.socket_server_url') ?? env('SOCKET_SERVER_URL', 'http://127.0.0.1:4000'));
@endphp

<div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md">
  <h1 class="text-xl font-bold mb-2">Payment for Call</h1>
  <p class="text-sm text-gray-600 mb-6">Call ID: <span class="font-mono">{{ $callId }}</span></p>
  <div id="info" class="text-xs text-gray-600 mb-4"></div>
  <button id="pay" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl">Simulate Payment Success</button>
  <button id="cancel" class="w-full mt-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-xl">Cancel Payment</button>
</div>

<script>
  const SOCKET_URL=@json($socketUrl);
  const params=new URLSearchParams(location.search);
  const callId=@json($callId);
  const doctorId=params.get('doctorId');
  const channel=params.get('channel');
  const patientId=params.get('patientId');
  document.getElementById('info').textContent=`doctorId=${doctorId}, channel=${channel}, patientId=${patientId}`;

  const socket=io(SOCKET_URL,{transports:['polling','websocket'],withCredentials:true,path:'/socket.io'});

  document.getElementById('pay').addEventListener('click',()=>{
    const paymentId='pay_'+Math.random().toString(36).slice(2,10);
    socket.emit('payment-completed',{callId,patientId,doctorId,channel,paymentId});
  });

  document.getElementById('cancel').addEventListener('click',()=>{
    socket.emit('payment-cancelled',{callId,patientId,doctorId,reason:'user_cancelled'}); history.back();
  });

  socket.on('payment-verified',(data)=>{
    if(data?.videoUrl){ window.location.href=data.videoUrl; }
    else { alert('Payment verified, but no video URL returned.'); }
  });
</script>
</body>
</html>
