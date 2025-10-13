@extends('snoutiq.layout')

@section('content')
<h2>Provider Tester</h2>

<fieldset>
  <legend>Register Provider</legend>
  <form id="formReg">
    <div class="row">
      <div>
        <label>Type</label>
        <select name="type">
          <option value="vet_clinic" selected>vet_clinic</option>
          <option value="home_service">home_service</option>
          <option value="hybrid">hybrid</option>
        </select>
      </div>
      <div>
        <label>Name</label>
        <input type="text" name="name" value="Dr. Demo" required>
      </div>
      <div>
        <label>Clinic</label>
        <input type="text" name="clinic_name" value="Pet Care">
      </div>
    </div>
    <div class="row">
      <div>
        <label>Phone</label>
        <input type="text" name="phone" value="+919999000111" required>
      </div>
      <div>
        <label>Email</label>
        <input type="text" name="email" value="demo@example.com">
      </div>
      <div>
        <label>License</label>
        <input type="text" name="license_number" value="VET123">
      </div>
    </div>
    <div class="row">
      <div>
        <label>Latitude</label>
        <input type="number" step="0.000001" name="latitude" value="28.4949">
      </div>
      <div>
        <label>Longitude</label>
        <input type="number" step="0.000001" name="longitude" value="77.0868">
      </div>
    </div>
    <button type="submit">Register</button>
  </form>
  <div id="regOut"></div>
</fieldset>

<fieldset>
  <legend>Complete Profile</legend>
  <form id="formComplete">
    <div class="row">
      <div>
        <label>Provider ID</label>
        <input type="number" id="cp_provider_id" name="provider_id" placeholder="from register" required>
      </div>
      <div>
        <label>Weekly Hours</label>
        <input type="number" name="weekly_commitment_hours" value="15" required>
      </div>
      <div>
        <label>Service Radius (km)</label>
        <input type="number" name="service_radius_km" value="7">
      </div>
    </div>
    <div class="row">
      <div>
        <label>Emergency Callable</label>
        <select name="emergency_callable"><option value="1">true</option><option value="0" selected>false</option></select>
      </div>
      <div>
        <label>Notification Prefs</label>
        <div>
          <label><input type="checkbox" name="np_whatsapp" checked> whatsapp</label>
          <label><input type="checkbox" name="np_sms" checked> sms</label>
        </div>
      </div>
      <div>
        <label>Specializations (comma)</label>
        <input type="text" name="specializations" value="dermatology,surgery">
      </div>
    </div>
    <div class="row">
      <div>
        <label>DND Start</label>
        <input type="text" name="dnd_start" value="22:00">
      </div>
      <div>
        <label>DND End</label>
        <input type="text" name="dnd_end" value="07:00">
      </div>
    </div>
    <div>
      <label>Availability JSON</label>
      <textarea id="cp_availability" rows="6">[
  {
    "service_type": "video",
    "day_of_week": 1,
    "start_time": "09:00:00",
    "end_time": "18:00:00",
    "avg_consultation_mins": 20,
    "max_bookings_per_hour": 3
  }
]</textarea>
      <div class="muted">Edit JSON to add more slots if needed.</div>
    </div>
    <button type="submit">Activate Profile</button>
  </form>
  <div id="cpOut"></div>
</fieldset>

<fieldset>
  <legend>Update Availability</legend>
  <form id="formAvail">
    <div class="row">
      <div>
        <label>Provider ID</label>
        <input type="number" id="av_provider_id" name="provider_id" placeholder="from register" required>
      </div>
    </div>
    <label>Availability JSON</label>
    <textarea id="av_json" rows="6">[
  {"service_type":"video","day_of_week":2,"start_time":"10:00:00","end_time":"17:00:00","max_bookings_per_hour":3}
]</textarea>
    <button type="submit">Update Availability</button>
  </form>
  <div id="avOut"></div>
</fieldset>

<fieldset>
  <legend>Provider Status</legend>
  <form id="formStatus">
    <label>Provider ID</label>
    <input type="number" id="st_provider_id" placeholder="from register">
    <button type="submit">Fetch Status</button>
  </form>
  <div id="stOut"></div>
</fieldset>

<script>
  const apiBase = '/api';
  const el = s => document.querySelector(s);
  const out = (sel, payload, ok=true) => { const d = el(sel); d.innerHTML = `<pre>${fmt(payload)}</pre>`; d.className = ok ? 'success' : 'error'; };

  el('#formReg').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target); const data = Object.fromEntries(fd.entries());
    data.latitude = Number(data.latitude); data.longitude = Number(data.longitude);
    const res = await api('POST', `${apiBase}/providers/register`, data);
    out('#regOut', res.json||res.raw, res.ok);
    if(res.ok && res.json && res.json.provider_id){
      el('#cp_provider_id').value = res.json.provider_id;
      el('#av_provider_id').value = res.json.provider_id;
      el('#st_provider_id').value = res.json.provider_id;
    }
  });

  el('#formComplete').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const provider_id = Number(fd.get('provider_id'));
    const weekly_commitment_hours = Number(fd.get('weekly_commitment_hours'));
    const service_radius_km = Number(fd.get('service_radius_km'));
    const emergency_callable = fd.get('emergency_callable') === '1';
    const notification_prefs = { whatsapp: el('input[name=np_whatsapp]').checked, sms: el('input[name=np_sms]').checked };
    const dnd = [{ start: fd.get('dnd_start'), end: fd.get('dnd_end') }];
    let specializations = fd.get('specializations').split(',').map(s=>s.trim()).filter(Boolean);
    let availability; try { availability = JSON.parse(el('#cp_availability').value); } catch(e){ availability = []; }
    const payload = { provider_id, weekly_commitment_hours, service_radius_km, emergency_callable, notification_prefs, dnd_periods:dnd, specializations, availability };
    const res = await api('POST', `${apiBase}/providers/complete-profile`, payload);
    out('#cpOut', res.json||res.raw, res.ok);
  });

  el('#formAvail').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const pid = Number(el('#av_provider_id').value);
    let availability; try { availability = JSON.parse(el('#av_json').value); } catch(e){ availability = []; }
    const res = await api('PUT', `${apiBase}/providers/${pid}/availability`, { availability });
    out('#avOut', res.json||res.raw, res.ok);
  });

  el('#formStatus').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const pid = Number(el('#st_provider_id').value);
    const res = await api('GET', `${apiBase}/providers/${pid}/status`);
    out('#stOut', res.json||res.raw, res.ok);
  });
</script>
@endsection

