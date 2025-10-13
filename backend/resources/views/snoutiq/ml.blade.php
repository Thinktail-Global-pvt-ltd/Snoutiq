@extends('snoutiq.layout')

@section('content')
<h2>ML Tools</h2>

<fieldset>
  <legend>Train</legend>
  <button id="btnTrain">Run Daily Training</button>
  <div id="trainOut"></div>
</fieldset>

<fieldset>
  <legend>Provider Performance</legend>
  <form id="formPerf">
    <label>Provider ID</label>
    <input type="number" id="perfProviderId" placeholder="1">
    <button type="submit">Fetch</button>
  </form>
  <div id="perfOut"></div>
</fieldset>

<fieldset>
  <legend>Demand Prediction</legend>
  <form id="formPred">
    <div class="row">
      <div>
        <label>Zone ID</label>
        <input type="number" id="dpZone" value="1">
      </div>
      <div>
        <label>Service Type</label>
        <select id="dpService"><option>video</option><option>in_clinic</option><option>home_visit</option></select>
      </div>
    </div>
    <div class="row">
      <div>
        <label>Date</label>
        <input type="date" id="dpDate" value="{{ date('Y-m-d') }}">
      </div>
      <div>
        <label>Hour (0-23)</label>
        <input type="number" id="dpHour" value="{{ date('G') }}" min="0" max="23">
      </div>
    </div>
    <button type="submit">Predict</button>
  </form>
  <div id="predOut"></div>
</fieldset>

<script>
  const apiBase = '/api';
  const el = s => document.querySelector(s);
  const out = (sel, payload, ok=true) => { const d = el(sel); d.innerHTML = `<pre>${fmt(payload)}</pre>`; d.className = ok ? 'success' : 'error'; };

  el('#btnTrain').addEventListener('click', async ()=>{
    const res = await api('POST', `${apiBase}/ml/train`);
    out('#trainOut', res.json||res.raw, res.ok);
  });

  el('#formPerf').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const id = el('#perfProviderId').value;
    if(!id) return;
    const res = await api('GET', `${apiBase}/ml/provider-performance/${id}`);
    out('#perfOut', res.json||res.raw, res.ok);
  });

  el('#formPred').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const zone_id = Number(el('#dpZone').value), service_type = el('#dpService').value, date = el('#dpDate').value, hour = Number(el('#dpHour').value);
    const url = `${apiBase}/ml/demand-prediction?zone_id=${zone_id}&service_type=${encodeURIComponent(service_type)}&date=${encodeURIComponent(date)}&hour=${hour}`;
    const res = await api('GET', url);
    out('#predOut', res.json||res.raw, res.ok);
  });
</script>
@endsection

