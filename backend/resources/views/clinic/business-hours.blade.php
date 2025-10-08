<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clinic Business Hours</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">

  <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-3xl">
    <h3 class="text-xl font-semibold text-gray-900 mb-6 text-center">Clinic Business Hours</h3>

    <div class="space-y-4">
      <!-- Repeat for each day -->
      <div class="flex items-center justify-between border-b pb-3">
        <span class="w-24 text-sm font-medium text-gray-700">Monday</span>
        <div class="flex items-center gap-2">
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <span class="text-gray-500">to</span>
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <label class="flex items-center ml-4">
            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
            <span class="text-sm text-gray-700">Closed</span>
          </label>
        </div>
      </div>

      <div class="flex items-center justify-between border-b pb-3">
        <span class="w-24 text-sm font-medium text-gray-700">Tuesday</span>
        <div class="flex items-center gap-2">
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <span class="text-gray-500">to</span>
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <label class="flex items-center ml-4">
            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
            <span class="text-sm text-gray-700">Closed</span>
          </label>
        </div>
      </div>

      <div class="flex items-center justify-between border-b pb-3">
        <span class="w-24 text-sm font-medium text-gray-700">Wednesday</span>
        <div class="flex items-center gap-2">
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <span class="text-gray-500">to</span>
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <label class="flex items-center ml-4">
            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
            <span class="text-sm text-gray-700">Closed</span>
          </label>
        </div>
      </div>

      <div class="flex items-center justify-between border-b pb-3">
        <span class="w-24 text-sm font-medium text-gray-700">Thursday</span>
        <div class="flex items-center gap-2">
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <span class="text-gray-500">to</span>
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <label class="flex items-center ml-4">
            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
            <span class="text-sm text-gray-700">Closed</span>
          </label>
        </div>
      </div>

      <div class="flex items-center justify-between border-b pb-3">
        <span class="w-24 text-sm font-medium text-gray-700">Friday</span>
        <div class="flex items-center gap-2">
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <span class="text-gray-500">to</span>
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <label class="flex items-center ml-4">
            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
            <span class="text-sm text-gray-700">Closed</span>
          </label>
        </div>
      </div>

      <div class="flex items-center justify-between border-b pb-3">
        <span class="w-24 text-sm font-medium text-gray-700">Saturday</span>
        <div class="flex items-center gap-2">
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <span class="text-gray-500">to</span>
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <label class="flex items-center ml-4">
            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
            <span class="text-sm text-gray-700">Closed</span>
          </label>
        </div>
      </div>

      <div class="flex items-center justify-between">
        <span class="w-24 text-sm font-medium text-gray-700">Sunday</span>
        <div class="flex items-center gap-2">
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <span class="text-gray-500">to</span>
          <input type="time" class="w-32 px-3 py-1 border border-gray-300 rounded-md text-sm">
          <label class="flex items-center ml-4">
            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
            <span class="text-sm text-gray-700">Closed</span>
          </label>
        </div>
      </div>
    </div>
  </div>

</body>
</html>

