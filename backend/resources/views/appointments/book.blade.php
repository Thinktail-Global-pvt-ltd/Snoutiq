<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book Appointment</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
  <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-2xl">
    <h3 class="text-2xl font-semibold text-gray-900 mb-6 text-center">Book an Appointment</h3>

    @if(session('success'))
      <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="mb-4 p-3 rounded bg-red-100 text-red-800">{{ session('error') }}</div>
    @endif
    @if($errors->any())
      <div class="mb-4 p-3 rounded bg-yellow-100 text-yellow-800">
        <ul class="list-disc list-inside">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('appointments.store') }}" method="POST">
      @csrf
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Clinic Slug</label>
          <input type="text" name="clinic_slug" value="{{ old('clinic_slug', request('clinic_slug')) }}" class="w-full px-3 py-2 border rounded" placeholder="e.g. happy-paws-clinic" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Doctor ID (optional)</label>
          <input type="number" name="doctor_id" value="{{ old('doctor_id') }}" class="w-full px-3 py-2 border rounded" placeholder="Numeric ID">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
          <input type="text" name="name" value="{{ old('name') }}" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Mobile</label>
          <input type="text" name="mobile" value="{{ old('mobile') }}" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Pet Name</label>
          <input type="text" name="pet_name" value="{{ old('pet_name') }}" class="w-full px-3 py-2 border rounded">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
          <input type="date" name="appointment_date" value="{{ old('appointment_date') }}" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
          <input type="time" name="appointment_time" value="{{ old('appointment_time') }}" class="w-full px-3 py-2 border rounded" required>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea name="notes" rows="3" class="w-full px-3 py-2 border rounded" placeholder="Describe your pet's condition, preferences, etc.">{{ old('notes') }}</textarea>
        </div>
      </div>

      <div class="mt-6 text-center">
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Confirm Booking</button>
      </div>
    </form>
  </div>
</body>
</html>

