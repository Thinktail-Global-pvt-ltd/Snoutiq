<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Call Page</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
  <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-lg space-y-3">
    <h1 class="text-xl font-bold">Joining Call</h1>
    <div class="text-sm text-gray-700 space-y-1">
      <div><span class="font-semibold">Channel:</span> <span class="font-mono">{{ $channel }}</span></div>
      <div><span class="font-semibold">UID:</span> <span class="font-mono">{{ $uid }}</span></div>
      <div><span class="font-semibold">Role:</span> <span class="font-mono">{{ $role }}</span></div>
      <div><span class="font-semibold">Call ID:</span> <span class="font-mono">{{ $callId }}</span></div>
    </div>
    <p class="text-gray-500 text-sm">Place your video SDK (Agora/Jitsi/etc.) here.</p>
    <a href="{{ route('patient.chat') }}" class="inline-block mt-4 bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900">Back to Chat</a>
  </div>
</body>
</html>
