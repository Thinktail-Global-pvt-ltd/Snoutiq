@extends('snoutiq.layout')

@section('content')
<h2>Booking Tester</h2>

<fieldset>
  <legend>Select Clinic and Doctor</legend>
  <div class="row">
    <div>
      <label>Clinic ID (vet_registerations_temp.id)</label>
      <input type="number" id="clinic_id" placeholder="e.g. 10">
    </div>
    <div>
      <label>Doctor</label>
      <select id="doctor_id">
        <option value="">-- load doctors --</option>
      </select>
    </div>
  </div>
  <button id="btnLoadDoctors" type="button">Load Doctors</button>
  <div class="row" style="margin-top:10px">
    <div>
      <label>Date</label>
      <input type="date" id="sched_date" value="{{ date('Y-m-d') }}">
    </div>
    <div style="align-self:flex-end">
      <button id="btnLoadAvailability" type="button">Load Availability</button>
    </div>
  </div>
  <div id="doctorsOut"></div>
  <div id="availabilityOut"></div>
  <div class="muted">Load the clinic’s doctors, then proceed to create booking.</div>
</fieldset>

<fieldset>
  <legend>Create Booking</legend>
  <form id="formCreate">
    <div class="row">
      <div>
        <label>User ID</label>
        <input type="number" name="user_id" value="101" required>
      </div>
      <div>
        <label>Pet ID</label>
        <input type="number" name="pet_id" value="201" required>
      </div>
    </div>
    <div class="row">
      <div>
        <label>Service Type</label>
        <select name="service_type">
          <option value="video">video</option>
          <option value="in_clinic">in_clinic</option>
          <option value="home_visit">home_visit</option>
        </select>
      </div>
      <div>
        <label>Urgency</label>
        <select name="urgency">
          <option>low</option>
          <option selected>medium</option>
          <option>high</option>
          <option>emergency</option>
        </select>
      </div>
    </div>
    <label>AI Summary</label>
    <input type="text" name="ai_summary" placeholder="Dog vomited 3 times">
    <div class="row">
      <div>
        <label>AI Urgency Score</label>
        <input type="number" name="ai_urgency_score" min="0" max="1" step="0.01" value="0.45">
      </div>
      <div>
        <label>Symptoms (comma separated)</label>
        <input type="text" name="symptoms" value="vomiting,lethargy">
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
    <label>Address</label>
    <input type="text" name="address" value="Sector 28, Gurgaon">

    <button type="submit">Create Booking</button>
  </form>
  <div id="createResult"></div>
</fieldset>

<fieldset>
  <legend>Get Booking Details</legend>
  <form id="formDetails">
    <label>Booking ID</label>
    <input type="number" name="booking_id" id="detail_booking_id" placeholder="e.g. 1">
    <button type="submit">Fetch Details</button>
  </form>
  <div id="detailsResult"></div>
</fieldset>

<fieldset>
  <legend>Update Booking Status</legend>
  <form id="formStatus">
    <div class="row">
      <div>
        <label>Booking ID</label>
        <input type="number" name="booking_id" id="status_booking_id" placeholder="e.g. 1">
      </div>
      <div>
        <label>Status</label>
        <select name="status">
          <option>pending</option>
          <option>routing</option>
          <option>accepted</option>
          <option>in_progress</option>
          <option>completed</option>
          <option>cancelled</option>
          <option>failed</option>
        </select>
      </div>
    </div>
    <button type="submit">Update Status</button>
  </form>
  <div id="statusResult"></div>
  <div class="muted">Tip: Set to accepted → in_progress → completed to simulate a full flow.</div>
  
</fieldset>

<fieldset>
  <legend>Rate Provider</legend>
  <form id="formRate">
    <div class="row">
      <div>
        <label>Booking ID</label>
        <input type="number" name="booking_id" id="rate_booking_id" placeholder="e.g. 1">
      </div>
      <div>
        <label>Rating</label>
        <input type="number" name="rating" min="1" max="5" value="5">
      </div>
    </div>
    <label>Review</label>
    <input type="text" name="review" value="Excellent service!">
    <button type="submit">Submit Rating</button>
  </form>
  <div id="rateResult"></div>
</fieldset>

<script>
  const apiBase = '/api';
  const el = s => document.querySelector(s);
  const out = (sel, payload, ok=true) => {
    const d = el(sel);
    d.innerHTML = `<pre>${fmt(payload)}</pre>`;
    d.className = ok ? 'success' : 'error';
  };

  // Load doctors for a clinic
  el('#btnLoadDoctors').addEventListener('click', async ()=>{
    const cid = el('#clinic_id').value;
    if(!cid){ alert('Enter Clinic ID'); return; }
    const res = await api('GET', `${apiBase}/clinics/${cid}/doctors`);
    out('#doctorsOut', res.json||res.raw, res.ok);
    if(res.ok && res.json && Array.isArray(res.json.doctors)){
      const sel = el('#doctor_id');
      sel.innerHTML = '<option value="">-- select --</option>' + res.json.doctors.map(d => `<option value="${d.id}">${d.name||('Doctor #'+d.id)}</option>`).join('');
    }
  });

  // Load availability for all doctors in clinic for selected date
  el('#btnLoadAvailability').addEventListener('click', async ()=>{
    const cid = el('#clinic_id').value;
    if(!cid){ alert('Enter Clinic ID'); return; }
    const res = await api('GET', `${apiBase}/clinics/${cid}/availability`);
    if(!res.ok){ out('#availabilityOut', res.json||res.raw, res.ok); return; }
    const dayOfWeek = (new Date(el('#sched_date').value || new Date())).getDay(); // 0=Sun
    const selectedDoctor = el('#doctor_id').value;
    const data = res.json;
    // Group availability by doctor
    const byDoc = {};
    (data.availability||[]).forEach(row => {
      if(row.day_of_week !== dayOfWeek) return;
      byDoc[row.doctor_id] = byDoc[row.doctor_id] || [];
      byDoc[row.doctor_id].push(row);
    });
    // Build slots per doctor
    function toMinutes(t){ const [h,m,s] = (t||'00:00:00').split(':').map(Number); return h*60 + m; }
    function fmtTime(min){ const h = Math.floor(min/60).toString().padStart(2,'0'); const m=(min%60).toString().padStart(2,'0'); return `${h}:${m}:00`; }
    function withinBreak(min, bstart, bend){ if(!bstart||!bend) return false; const bs=toMinutes(bstart), be=toMinutes(bend); return min>=bs && min<be; }

    let html = '';
    (data.doctors||[]).forEach(doc => {
      const rows = byDoc[doc.id]||[];
      let slots = [];
      rows.forEach(r => {
        const start = toMinutes(r.start_time); const end = toMinutes(r.end_time);
        const step = Math.max(5, parseInt(r.avg_consultation_mins||20));
        for(let t=start; t+step<=end; t+=step){ if(!withinBreak(t, r.break_start, r.break_end)) { slots.push(fmtTime(t)); } }
      });
      slots = Array.from(new Set(slots));
      const prefer = (String(doc.id) === String(selectedDoctor));
      html += `<div style="border:1px solid #ddd;padding:10px;margin:8px 0;${prefer?'background:#f5fbff;':''}">`+
              `<div><strong>${doc.name||('Doctor #'+doc.id)}</strong> ${prefer?'<span class=\'muted\'>(selected)</span>':''}</div>`+
              `<div><label>Time Slot</label> <select class="slot-select" data-doctor-id="${doc.id}">`+
              `<option value="">-- choose --</option>`+
              slots.map(s=>`<option value="${s}" ${prefer && s? 'selected':''}>${s.slice(0,5)}</option>`).join('')+
              `</select></div>`+
              `</div>`;
    });
    if(!html){ html = '<div class="muted">No availability found for selected date.</div>'; }
    el('#availabilityOut').innerHTML = html;
  });

  el('#formCreate').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd.entries());
    // attach clinic & doctor from the top selector
    const clinicId = el('#clinic_id').value;
    const doctorId = el('#doctor_id').value;
    if(clinicId) data.clinic_id = Number(clinicId);
    // Use chosen slot doctor if selected; otherwise selected doctor dropdown
    const selectedSlotEl = Array.from(document.querySelectorAll('.slot-select')).find(sel => sel.value);
    if(selectedSlotEl){
      data.doctor_id = Number(selectedSlotEl.getAttribute('data-doctor-id'));
      data.scheduled_date = el('#sched_date').value;
      data.scheduled_time = selectedSlotEl.value;
    } else if(doctorId){
      data.doctor_id = Number(doctorId);
    }
    if(data.symptoms){ data.symptoms = data.symptoms.split(',').map(s=>s.trim()).filter(Boolean); }
    if(data.ai_urgency_score!=='' && data.ai_urgency_score!=null) data.ai_urgency_score = Number(data.ai_urgency_score);
    ['user_id','pet_id'].forEach(k => data[k] = Number(data[k]));
    ;['latitude','longitude'].forEach(k => { if(data[k]!=='' && data[k]!=null) data[k] = Number(data[k]); });
    const res = await api('POST', `${apiBase}/bookings/create`, data);
    out('#createResult', res.json||res.raw, res.ok);
    if(res.ok && res.json && res.json.booking_id){
      const id = res.json.booking_id;
      el('#detail_booking_id').value = id;
      el('#status_booking_id').value = id;
      el('#rate_booking_id').value = id;
    }
  });

  el('#formDetails').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const id = el('#detail_booking_id').value;
    if(!id) return;
    const res = await api('GET', `${apiBase}/bookings/details/${id}`);
    out('#detailsResult', res.json||res.raw, res.ok);
  });

  el('#formStatus').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const id = fd.get('booking_id');
    const status = fd.get('status');
    if(!id) return;
    const res = await api('PUT', `${apiBase}/bookings/${id}/status`, { status });
    out('#statusResult', res.json||res.raw, res.ok);
  });

  el('#formRate').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const id = fd.get('booking_id');
    if(!id) return;
    const payload = { rating: Number(fd.get('rating')), review: fd.get('review') };
    const res = await api('POST', `${apiBase}/bookings/${id}/rate`, payload);
    out('#rateResult', res.json||res.raw, res.ok);
  });
</script>
@endsection
