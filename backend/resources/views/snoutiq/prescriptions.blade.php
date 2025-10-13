@extends('snoutiq.layout')

@section('content')
<h2>Prescriptions</h2>

<fieldset>
  <legend>List Prescriptions</legend>
  <form id="formList">
    <div class="row">
      <div>
        <label>User ID (optional)</label>
        <input type="number" id="lpUser">
      </div>
      <div>
        <label>Doctor ID (optional)</label>
        <input type="number" id="lpDoctor">
      </div>
    </div>
    <button type="submit">Load</button>
  </form>
  <div id="listOut"></div>
</fieldset>

<fieldset>
  <legend>Show Prescription</legend>
  <form id="formShow">
    <label>ID</label>
    <input type="number" id="spId">
    <button type="submit">Fetch</button>
  </form>
  <div id="showOut"></div>
</fieldset>

<fieldset>
  <legend>Create Prescription</legend>
  <form id="formCreate" enctype="multipart/form-data">
    <div class="row">
      <div>
        <label>Doctor ID</label>
        <input type="number" name="doctor_id" value="1" required>
      </div>
      <div>
        <label>User ID</label>
        <input type="number" name="user_id" value="101" required>
      </div>
    </div>
    <label>Content HTML</label>
    <textarea name="content_html" rows="4"><p>Rx: Rest and hydration.</p></textarea>
    <label>Image (optional)</label>
    <input type="file" name="image" accept="image/*">
    <button type="submit">Create</button>
  </form>
  <div id="createOut"></div>
</fieldset>

<script>
  const apiBase = '/api';
  const el = s => document.querySelector(s);
  const out = (sel, payload, ok=true) => { const d = el(sel); d.innerHTML = `<pre>${fmt(payload)}</pre>`; d.className = ok ? 'success' : 'error'; };

  el('#formList').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const u = el('#lpUser').value, d = el('#lpDoctor').value;
    const qs = new URLSearchParams();
    if(u) qs.set('user_id', u);
    if(d) qs.set('doctor_id', d);
    const res = await fetch(`${apiBase}/prescriptions?${qs.toString()}`);
    const data = await res.text(); let json = null; try{ json = JSON.parse(data);}catch{}
    out('#listOut', json||data, res.ok);
  });

  el('#formShow').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const id = el('#spId').value; if(!id) return;
    const res = await fetch(`${apiBase}/prescriptions/${id}`);
    const data = await res.text(); let json = null; try{ json = JSON.parse(data);}catch{}
    out('#showOut', json||data, res.ok);
  });

  el('#formCreate').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const res = await fetch(`${apiBase}/prescriptions`, { method:'POST', body: fd });
    const data = await res.text(); let json = null; try{ json = JSON.parse(data);}catch{}
    out('#createOut', json||data, res.ok);
  });
</script>
@endsection

