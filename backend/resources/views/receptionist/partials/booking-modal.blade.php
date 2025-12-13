<div id="booking-modal" class="modal-overlay" hidden aria-hidden="true">
  <div class="modal-card bg-white rounded-2xl shadow-2xl p-6 space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold tracking-wide text-indigo-600 uppercase">Front Desk</p>
        <h3 class="text-xl font-semibold text-slate-900">Create Booking</h3>
      </div>
      <button type="button" data-close class="text-slate-500 hover:text-slate-700 text-lg">&times;</button>
    </div>

    <div class="bg-slate-50 rounded-xl p-2 flex items-center gap-2">
      <button type="button" class="tab-button active" data-patient-mode="existing">Existing Patient</button>
      <button type="button" class="tab-button" data-patient-mode="new">New Patient</button>
    </div>

    <form id="booking-form" class="space-y-5">
      <div id="existing-patient-section" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold mb-1">Patient</label>
          <div class="flex flex-col gap-2">
            <input id="patient-search" type="text" placeholder="Search patient..." class="bg-slate-50 rounded-lg px-3 py-2 text-sm border border-transparent focus:bg-white focus:ring-2 focus:ring-blue-500">
            <select id="patient-select" name="patient_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500"></select>
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Pet</label>
          <select id="pet-select" name="pet_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500"></select>
          <p class="text-xs text-slate-500 mt-1">Need a new pet? Fill details below and we'll add it automatically.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">New Pet Name</label>
            <input name="inline_pet_name" type="text" placeholder="Pet name" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Pet Type</label>
            <input name="inline_pet_type" type="text" placeholder="Dog, Cat..." class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Breed</label>
            <input name="inline_pet_breed" type="text" placeholder="Breed" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-xs font-semibold mb-1 uppercase tracking-wide text-slate-500">Gender</label>
            <input name="inline_pet_gender" type="text" placeholder="Male/Female" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
      </div>

      <div id="new-patient-section" class="hidden space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold mb-1">Patient Name</label>
            <input name="new_patient_name" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Phone</label>
            <input name="new_patient_phone" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Email</label>
            <input name="new_patient_email" type="email" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold mb-1">Pet Name</label>
            <input name="new_pet_name" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Pet Type</label>
            <input name="new_pet_type" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Breed</label>
            <input name="new_pet_breed" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Gender</label>
            <input name="new_pet_gender" type="text" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
        <p class="text-xs text-slate-500">Provide at least a phone number or email for the patient, and pet details so we can attach the booking.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-semibold mb-1">Doctor</label>
          <select id="doctor-select" name="doctor_id" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
            <option value="">Any available doctor</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Service Type</label>
          <select name="service_type" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
            <option value="in_clinic">In Clinic</option>
            <!-- <option value="video">Video</option>
            <option value="home_visit">Home Visit</option> -->
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-semibold mb-1">Date</label>
          <input name="scheduled_date" type="date" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-semibold mb-1">Available Slots</label>
          <select name="scheduled_time" id="slot-select" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500">
            <option value="">Select a time slot</option>
          </select>
          <p id="slot-hint" class="text-xs text-slate-500 mt-1">
            Select a doctor and date first to load available slots (via /api/doctors/{doctor}/slots/summary).
          </p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Notes</label>
        <textarea name="notes" rows="3" class="w-full bg-slate-50 rounded-lg px-3 py-2 text-sm focus:bg-white focus:ring-2 focus:ring-blue-500" placeholder="Any reason or context for this visit"></textarea>
      </div>

      <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
        <button type="button" data-close class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold">Cancel</button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Save Booking</button>
      </div>
    </form>
  </div>
</div>
