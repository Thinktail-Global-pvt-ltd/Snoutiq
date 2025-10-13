@extends('snoutiq.layout')

@section('content')
<h2>Admin Tools</h2>

<fieldset>
  <legend>Recruitment Tasks</legend>
  <form id="formTasks">
    <label>Status</label>
    <select id="taskStatus"><option>open</option><option>in_progress</option><option>completed</option><option>cancelled</option></select>
    <button type="submit">Load Tasks</button>
  </form>
  <div id="tasksOut"></div>
</fieldset>

<fieldset>
  <legend>Alerts</legend>
  <form id="formAlerts">
    <label>Resolved</label>
    <select id="alertResolved"><option value="0" selected>0 (unresolved)</option><option value="1">1 (resolved)</option></select>
    <button type="submit">Load Alerts</button>
  </form>
  <div id="alertsOut"></div>
</fieldset>

<fieldset>
  <legend>Resolve Alert</legend>
  <form id="formResolve">
    <label>Alert ID</label>
    <input type="number" id="resolveId">
    <button type="submit">Resolve</button>
  </form>
  <div id="resolveOut"></div>
</fieldset>

<fieldset>
  <legend>Providers Queue</legend>
  <button id="btnQueue">Load Queue</button>
  <div id="queueOut"></div>
</fieldset>

<fieldset>
  <legend>Analytics</legend>
  <form id="formAnalytics">
    <label>Period (days)</label>
    <input type="number" id="anaPeriod" value="30">
    <button type="submit">Load Analytics</button>
  </form>
  <div id="analyticsOut"></div>
</fieldset>

<script>
  const apiBase = '/api';
  const el = s => document.querySelector(s);
  const out = (sel, payload, ok=true) => { const d = el(sel); d.innerHTML = `<pre>${fmt(payload)}</pre>`; d.className = ok ? 'success' : 'error'; };

  el('#formTasks').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const status = el('#taskStatus').value;
    const res = await api('GET', `${apiBase}/admin/tasks?status=${encodeURIComponent(status)}`);
    out('#tasksOut', res.json||res.raw, res.ok);
  });

  el('#formAlerts').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const resolved = el('#alertResolved').value;
    const res = await api('GET', `${apiBase}/admin/alerts?resolved=${encodeURIComponent(resolved)}`);
    out('#alertsOut', res.json||res.raw, res.ok);
  });

  el('#formResolve').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const id = el('#resolveId').value;
    if(!id) return;
    const res = await api('POST', `${apiBase}/admin/resolve-alert/${id}`);
    out('#resolveOut', res.json||res.raw, res.ok);
  });

  el('#btnQueue').addEventListener('click', async ()=>{
    const res = await api('GET', `${apiBase}/admin/providers-queue`);
    out('#queueOut', res.json||res.raw, res.ok);
  });

  el('#formAnalytics').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const period = Number(el('#anaPeriod').value || 30);
    const res = await api('GET', `${apiBase}/admin/analytics?period=${period}`);
    out('#analyticsOut', res.json||res.raw, res.ok);
  });
</script>
@endsection

