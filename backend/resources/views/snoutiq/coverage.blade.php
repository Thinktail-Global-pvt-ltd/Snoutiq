@extends('snoutiq.layout')

@section('content')
<h2>Coverage Dashboard</h2>

<fieldset>
  <legend>Fetch Dashboard</legend>
  <button id="btnDash">Load Dashboard</button>
  <div id="dashOut"></div>
  <div id="dashTable"></div>
</fieldset>

<fieldset>
  <legend>Zone Details</legend>
  <form id="formZone">
    <label>Zone ID</label>
    <input type="number" id="zone_id" value="1">
    <button type="submit">Fetch Zone</button>
  </form>
  <div id="zoneOut"></div>
</fieldset>

<script>
  const apiBase = '/api';
  const el = s => document.querySelector(s);
  const out = (sel, payload, ok=true) => { const d = el(sel); d.innerHTML = `<pre>${fmt(payload)}</pre>`; d.className = ok ? 'success' : 'error'; };

  el('#btnDash').addEventListener('click', async ()=>{
    const res = await api('GET', `${apiBase}/coverage/dashboard`);
    out('#dashOut', res.json||res.raw, res.ok);
    if(res.ok && res.json){
      const zones = res.json.zone_map || [];
      let html = '<table border="1" cellpadding="6" cellspacing="0"><tr><th>ID</th><th>Name</th><th>Status</th><th>Score</th><th>Providers</th></tr>';
      zones.forEach(z=> html += `<tr><td>${z.id}</td><td>${z.name}</td><td>${z.status}</td><td>${z.score}</td><td>${z.providers}</td></tr>`);
      html += '</table>';
      el('#dashTable').innerHTML = html;
    }
  });

  el('#formZone').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const id = Number(el('#zone_id').value);
    const res = await api('GET', `${apiBase}/coverage/zone/${id}`);
    out('#zoneOut', res.json||res.raw, res.ok);
  });
</script>
@endsection

