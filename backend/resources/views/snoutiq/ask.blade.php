<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Snoutiq — Is My Pet Okay? Free AI Pet Health Check</title>
<meta name="description" content="Free AI pet symptom checker for Indian pet parents. Get expert triage guidance in seconds. No signup needed.">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,700;0,9..144,900;1,9..144,600&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
<style>
:root{
  --blue:#1565C0;--blue-mid:#1e88e5;--blue-pale:#e8f1fb;--blue-deep:#0d3b7a;
  --orange:#F57C00;--orange-lt:#FF9800;--orange-pale:#fff3e0;
  --red:#C62828;--red-lt:#e53935;--red-pale:#ffebee;
  --green:#2E7D32;--green-lt:#43a047;--green-pale:#e8f5e9;
  --purple:#6A1B9A;--purple-pale:#f3e5f5;
  --ink:#111827;--ink-mid:#374151;--muted:#6B7280;
  --border:#E5E7EB;--surface:#F9FAFB;--white:#fff;
  --r:14px;--r-sm:10px;
}
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
html,body{height:100%;overscroll-behavior:none}
body{font-family:'DM Sans',sans-serif;background:#EEF2F7;color:var(--ink);display:flex;flex-direction:column;min-height:100vh}
.ann{background:var(--blue-deep);color:rgba(255,255,255,.88);text-align:center;padding:9px 16px;font-size:12.5px;font-weight:500;flex-shrink:0}
.nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 18px;height:54px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;position:sticky;top:0;z-index:200}
.logo{font-family:'Fraunces',serif;font-size:20px;font-weight:900;color:var(--blue);letter-spacing:-.5px}
.logo b{color:var(--orange)}
.nav-r{display:flex;align-items:center;gap:10px}
.checks-badge{background:var(--blue-pale);color:var(--blue);font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:50px;border:1px solid #93c5fd}
.nav-cta{background:var(--orange);color:#fff;border:none;padding:8px 16px;border-radius:50px;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:background .15s}
.nav-cta:hover{background:var(--orange-lt)}
.page{flex:1;display:flex;flex-direction:column;max-width:680px;width:100%;margin:0 auto;background:var(--white);box-shadow:0 0 0 1px var(--border);min-height:0}
.chdr{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:11px;background:var(--white);flex-shrink:0}
.chdr-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--blue-deep),var(--blue));display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;box-shadow:0 2px 8px rgba(21,101,192,.25)}
.chdr-info h2{font-family:'Fraunces',serif;font-size:15px;font-weight:700;color:var(--ink)}
.chdr-info p{font-size:11px;color:var(--muted);margin-top:2px;display:flex;align-items:center;gap:4px}
.ldot{width:6px;height:6px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 2s ease infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.dbar{display:flex;gap:6px;padding:9px 12px;border-bottom:1px solid var(--border);overflow-x:auto;scrollbar-width:none;flex-shrink:0;background:var(--surface)}
.dbar::-webkit-scrollbar{display:none}
.dtab{padding:5px 11px;border-radius:50px;font-size:11px;font-weight:700;cursor:pointer;border:1.5px solid transparent;white-space:nowrap;transition:all .15s;font-family:'DM Sans',sans-serif;letter-spacing:.01em}
.dtab.i{background:#f3f4f6;color:var(--muted);border-color:#d1d5db}.dtab.i.on{background:var(--ink);color:#fff}
.dtab.e{background:var(--red-pale);color:var(--red);border-color:#fca5a5}.dtab.e.on{background:var(--red);color:#fff}
.dtab.v{background:var(--blue-pale);color:var(--blue);border-color:#93c5fd}.dtab.v.on{background:var(--blue);color:#fff}
.dtab.c{background:var(--purple-pale);color:var(--purple);border-color:#d8b4fe}.dtab.c.on{background:var(--purple);color:#fff}
.dtab.m{background:var(--green-pale);color:var(--green);border-color:#86efac}.dtab.m.on{background:var(--green);color:#fff}
.dtab.rl{background:#fef3c7;color:#92400e;border-color:#fcd34d}.dtab.rl.on{background:#92400e;color:#fff}
.cbody{flex:1;overflow-y:auto;padding:18px 14px;display:flex;flex-direction:column;gap:14px;scroll-behavior:smooth}
.cbody::-webkit-scrollbar{width:3px}.cbody::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
.screen{display:none;flex-direction:column;gap:14px}
.screen.on{display:flex;animation:fu .3s ease both}
@keyframes fu{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.idle-wrap{text-align:center;padding:24px 12px 16px}
.idle-anim{font-size:54px;animation:fl 3.5s ease-in-out infinite;margin-bottom:12px}
@keyframes fl{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
.idle-h{font-family:'Fraunces',serif;font-size:28px;font-weight:900;color:var(--ink);line-height:1.1;margin-bottom:8px}
.idle-h em{color:var(--blue);font-style:italic}
.idle-sub{font-size:14px;color:var(--muted);line-height:1.5;max-width:280px;margin:0 auto 20px}
.trust-row{display:flex;justify-content:center;gap:14px;flex-wrap:wrap;margin-bottom:26px}
.tp{font-size:11.5px;font-weight:600;color:var(--muted);display:flex;align-items:center;gap:4px}
.tp::before{content:"✓";color:var(--green-lt);font-weight:800}
.qlabel{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:9px;text-align:left}
.qgrid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:20px}
.qbtn{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r-sm);padding:11px 12px;font-family:'DM Sans',sans-serif;font-size:12.5px;font-weight:500;color:var(--ink-mid);cursor:pointer;text-align:left;transition:all .15s;display:flex;align-items:center;gap:8px}
.qbtn:hover{background:var(--blue-pale);border-color:var(--blue);color:var(--blue)}
.qi{font-size:20px;flex-shrink:0}.qt strong{display:block;font-size:12.5px;font-weight:700}.qt span{font-size:11px;color:var(--muted)}
.slab{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:8px;text-align:left}
.srow{display:flex;gap:7px;flex-wrap:wrap}
.sbtn{background:var(--surface);border:1.5px solid var(--border);border-radius:50px;padding:7px 14px;font-family:'DM Sans',sans-serif;font-size:12.5px;font-weight:600;color:var(--ink-mid);cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:5px}
.sbtn:hover,.sbtn.on{background:var(--blue);border-color:var(--blue);color:#fff}
.mrow{display:flex;justify-content:flex-end}
.ppill{display:inline-flex;align-items:center;gap:5px;background:var(--surface);border:1px solid var(--border);border-radius:50px;padding:4px 10px;font-size:10.5px;color:var(--muted);font-weight:600;margin-bottom:4px;align-self:flex-end}
.mbub{background:var(--blue);color:#fff;padding:12px 15px;border-radius:18px 18px 4px 18px;max-width:82%;font-size:14.5px;line-height:1.55;box-shadow:0 3px 12px rgba(21,101,192,.22)}
.mtime{font-size:10px;opacity:.5;margin-top:4px;text-align:right}
.rcard{background:var(--white);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.07)}
.ub{padding:20px 20px 18px}
.ub.emergency{background:linear-gradient(135deg,#7f1d1d,#991b1b)}
.ub.video{background:linear-gradient(135deg,#1e3a8a,var(--blue))}
.ub.clinic{background:linear-gradient(135deg,#4a1d96,var(--purple))}
.ub.monitor{background:linear-gradient(135deg,#14532d,var(--green))}
.ub-lbl{font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.85);margin-bottom:7px}
.ub-title{font-family:'Fraunces',serif;font-size:24px;font-weight:900;color:#fff;line-height:1.05}
.ub-sub{font-size:13px;color:rgba(255,255,255,.9);line-height:1.45;margin-top:8px;max-width:470px}
.tbadge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);backdrop-filter:blur(4px);color:#fff;border:1px solid rgba(255,255,255,.18);padding:6px 11px;border-radius:50px;font-size:11.5px;font-weight:700;margin-top:12px}
.hs-wrap{padding:0 16px}
.hs-top{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:6px 0 0}
.hs-left{min-width:0}
.hs-eyebrow{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
.hs-score-row{display:flex;align-items:baseline;gap:2px;margin-top:4px}
.hs-num{font-family:'Fraunces',serif;font-size:38px;font-weight:900;line-height:1}
.hs-denom{font-size:18px;color:var(--muted);font-weight:700}
.hs-label{font-size:13px;font-weight:800;margin-top:2px}
.hs-sub{font-size:12px;color:var(--muted);margin-top:2px}
.hs-gauge{width:84px;height:84px;flex-shrink:0}
.hs-gauge svg{width:100%;height:100%}
.hs-share{display:flex;gap:8px;align-items:center;justify-content:space-between;background:var(--surface);border:1px solid var(--border);padding:10px 12px;border-radius:var(--r-sm);margin-top:10px}
.share-label{font-size:11.5px;color:var(--muted);line-height:1.35}
.share-label strong{display:block;color:var(--ink);font-size:12px}
.wa-btn,.copy-link{border:none;border-radius:50px;padding:9px 13px;font-family:'DM Sans',sans-serif;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap}
.wa-btn{background:#22c55e;color:#fff;display:inline-flex;align-items:center;gap:7px}
.copy-link{background:#fff;color:var(--ink-mid);border:1px solid var(--border)}
.wa-icon{width:15px;height:15px;fill:currentColor}
.ctas{display:flex;gap:8px;padding:0 16px 0}
.btn-p,.btn-s{flex:1;border:none;border-radius:12px;padding:13px 14px;font-family:'DM Sans',sans-serif;font-size:13.5px;font-weight:700;cursor:pointer;transition:transform .12s,filter .12s}
.btn-p:active,.btn-s:active{transform:scale(.99)}
.btn-p.emergency{background:var(--red);color:#fff}
.btn-p.video{background:var(--blue);color:#fff}
.btn-p.clinic{background:var(--purple);color:#fff}
.btn-p.monitor{background:var(--green);color:#fff}
.btn-s{background:var(--surface);color:var(--ink-mid);border:1.5px solid var(--border)}
.btn-p:hover,.btn-s:hover{filter:brightness(1.03)}
.svc-wrap{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:14px 16px 0}
.svc-card{border:1px solid var(--border);border-radius:var(--r-sm);overflow:hidden;background:var(--white)}
.svc-card.featured{box-shadow:0 8px 24px rgba(21,101,192,.08);border-color:#bfdbfe}
.svc-header{display:flex;justify-content:space-between;align-items:center;padding:12px 14px 0}
.svc-badge{background:var(--blue);color:#fff;font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:3px 8px;border-radius:50px}
.svc-badge.vah{background:var(--purple)}
.svc-badge.cb{background:var(--muted)}
.svc-title{font-size:13px;font-weight:700;color:var(--ink);margin-top:8px;padding:0 14px}
.svc-price-row{display:flex;align-items:baseline;gap:7px;padding:4px 14px 0}
.svc-price{font-family:'Fraunces',serif;font-size:26px;font-weight:900}
.svc-price.video{color:var(--blue)}.svc-price.vah{color:var(--purple)}.svc-price.cb{color:var(--green)}
.svc-orig{font-size:14px;color:var(--muted);text-decoration:line-through}
.svc-guarantee{font-size:11.5px;color:var(--muted);padding:4px 14px 0;font-weight:500;font-style:italic}
.svc-trust{display:flex;flex-direction:column;gap:3px;padding:8px 14px}
.svc-ti{font-size:12px;color:var(--ink-mid);font-weight:500;display:flex;align-items:center;gap:5px}
.svc-ti::before{content:"✓";color:var(--green-lt);font-weight:800;font-size:11px}
.svc-cta{display:block;width:100%;padding:13px;margin-top:2px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;border:none;cursor:pointer;transition:filter .15s,transform .1s;color:#fff}
.svc-cta:active{transform:scale(.98)}
.svc-cta.video{background:var(--blue)}.svc-cta.vah{background:var(--purple)}.svc-cta.cb{background:var(--green)}
.svc-cta:hover{filter:brightness(1.07)}
.cbdy{padding:16px;display:flex;flex-direction:column;gap:16px}
.slbl{font-size:10.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.ablock{display:flex;gap:10px}
.aico{width:32px;height:32px;border-radius:50%;background:var(--blue-pale);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;margin-top:2px}
.atxt p{font-size:14px;line-height:1.65;color:var(--ink-mid)}
.donow{background:var(--orange-pale);border:1.5px solid #fcd34d;border-radius:var(--r-sm);padding:12px 14px;display:flex;gap:9px;align-items:flex-start}
.dnicon{font-size:17px;flex-shrink:0;margin-top:1px}
.donow .slbl{color:var(--orange);margin-bottom:3px}
.donow p{font-size:13.5px;font-weight:600;color:#5d3e00;line-height:1.4}
.india{display:flex;gap:7px;align-items:flex-start;background:#fffbeb;border:1px solid #fcd34d;border-radius:var(--r-sm);padding:10px 12px;font-size:12.5px;color:#78350f;line-height:1.45}
.hclist{display:flex;flex-direction:column;gap:7px}
.hci{display:flex;gap:9px;align-items:flex-start;font-size:13.5px;color:var(--ink-mid);line-height:1.4}
.hcn{width:20px;height:20px;background:var(--green);color:#fff;border-radius:50%;font-size:10.5px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px}
.fcard{background:#faf5ff;border:1.5px solid #e9d5ff;border-radius:var(--r-sm);padding:13px}
.fcard .slbl{color:var(--purple);margin-bottom:7px}
.fq{font-family:'Fraunces',serif;font-size:15px;font-weight:700;color:var(--ink);line-height:1.35;margin-bottom:11px}
.fopts{display:flex;flex-direction:column;gap:6px}
.fopt{background:var(--white);border:1.5px solid #d8b4fe;border-radius:50px;padding:9px 15px;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:500;color:var(--purple);cursor:pointer;text-align:left;transition:all .15s}
.fopt:hover,.fopt.sel{background:var(--purple);color:#fff;border-color:var(--purple)}
.fopt:disabled{cursor:default;opacity:.7}
.fupd{display:none;align-items:center;gap:8px;font-size:12.5px;color:var(--purple);font-weight:500;margin-top:10px}
.fupd.show{display:flex}
.tbub{background:var(--surface);border:1px solid var(--border);padding:8px 12px;border-radius:16px;display:flex;gap:3px;align-items:center}
.td{width:6px;height:6px;background:var(--muted);border-radius:50%;animation:td 1.2s ease infinite}
.td:nth-child(2){animation-delay:.2s}.td:nth-child(3){animation-delay:.4s}
@keyframes td{0%,60%,100%{transform:translateY(0);opacity:.4}30%{transform:translateY(-4px);opacity:1}}
.rbadge{display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;border:1px solid #86efac;border-radius:50px;padding:4px 10px;font-size:11px;font-weight:700;color:var(--green-lt);align-self:flex-start}
.wlist{display:flex;flex-direction:column;gap:7px}
.wi{display:flex;gap:8px;align-items:flex-start;padding:10px 11px;border-radius:var(--r-sm);border-left:3px solid var(--border);background:var(--surface)}
.wi.warning{border-left-color:var(--orange-lt);background:#fffbeb}
.wi.danger{border-left-color:var(--red-lt);background:#fff5f5}
.wiico{font-size:13px;margin-top:1px;flex-shrink:0}
.wi p{font-size:13px;line-height:1.4;color:var(--ink-mid)}
.cpills{display:flex;flex-wrap:wrap;gap:5px}
.cpill{background:var(--blue-pale);color:var(--blue);border:1px solid #93c5fd;padding:5px 11px;border-radius:50px;font-size:12px;font-weight:500}
.vetq{display:flex;gap:9px;align-items:flex-start;background:#f0f7ff;border-radius:var(--r-sm);padding:11px 12px}
.vetqico{font-size:16px;flex-shrink:0}
.vetq .slbl{color:var(--blue);margin-bottom:3px}
.vetq p{font-size:13px;color:var(--ink-mid);line-height:1.4;font-weight:500}
.disc{border-top:1px solid var(--border);padding:12px 16px;display:flex;gap:9px;align-items:flex-start;background:var(--surface)}
.disc-i{font-size:15px;flex-shrink:0;margin-top:1px}
.disc-t{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:2px}
.disc p{font-size:11.5px;color:var(--muted);line-height:1.5}
.rlcard{background:var(--white);border:1.5px solid #fcd34d;border-radius:var(--r);overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06)}
.rlhdr{background:linear-gradient(135deg,#92400e,#b45309);padding:22px 20px}
.rli{font-size:34px;margin-bottom:8px}
.rlt{font-family:'Fraunces',serif;font-size:20px;font-weight:900;color:#fff;margin-bottom:4px}
.rls{font-size:13px;color:rgba(255,255,255,.8)}
.rlb{padding:18px}
.rldots{display:flex;gap:5px;margin-bottom:14px;justify-content:center}
.rld{width:34px;height:7px;border-radius:4px;background:#ef4444}
.rld.x{background:var(--border)}
.rlmsg{font-size:14px;color:var(--ink-mid);line-height:1.6;text-align:center;margin-bottom:16px}
.rlcta{background:var(--orange);color:#fff;border:none;width:100%;padding:14px;border-radius:var(--r-sm);font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;margin-bottom:8px;transition:background .15s}
.rlcta:hover{background:var(--orange-lt)}
.rllater{background:transparent;border:1.5px solid var(--border);color:var(--muted);width:100%;padding:11px;border-radius:var(--r-sm);font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;cursor:pointer}
.ibar{border-top:1px solid var(--border);padding:10px 12px;display:flex;gap:8px;align-items:flex-end;background:var(--white);flex-shrink:0;position:sticky;bottom:0;z-index:100}
.inp{flex:1;border:1.5px solid var(--border);border-radius:22px;padding:10px 15px;font-family:'DM Sans',sans-serif;font-size:14px;color:var(--ink);background:var(--surface);resize:none;outline:none;transition:border-color .2s;min-height:42px;max-height:100px}
.inp:focus{border-color:var(--blue);background:var(--white)}
.inp::placeholder{color:#9CA3AF}
.sendbtn{width:42px;height:42px;background:var(--blue);border:none;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:transform .15s,background .15s}
.sendbtn:hover{background:var(--blue-mid);transform:scale(1.05)}
.sendbtn svg{width:17px;height:17px;fill:#fff}
@media(max-width:480px){.ub-title{font-size:19px}.idle-h{font-size:23px}.qgrid{grid-template-columns:1fr 1fr}.cbdy{padding:13px}.cbody{padding:13px 11px}}
</style>
</head>
<body>
<div class="ann">🐾 Free AI Pet Health Check · {{ preg_replace('#^https?://#', '', rtrim(config('app.url', 'https://snoutiq.com'), '/')) }}/ask · No signup needed</div>
<nav class="nav">
  <div class="logo">SN<b>🐾</b>UTIQ</div>
  <div class="nav-r">
    <div class="checks-badge" id="chk-badge">2 free checks left</div>
    <button class="nav-cta" onclick="switchDemo('v')">Consult ₹499</button>
  </div>
</nav>
<div class="page">
  <div class="chdr">
    <div class="chdr-av">🐾</div>
    <div class="chdr-info">
      <h2>Snoutiq Vet AI</h2>
      <p><span class="ldot"></span> AI triage · Vet-reviewed · Free</p>
    </div>
  </div>
  <div class="dbar">
    <button class="dtab i on" onclick="sw('i',this)">💤 Idle</button>
    <button class="dtab e" onclick="sw('e',this)">🚨 Emergency</button>
    <button class="dtab v" onclick="sw('v',this)">📱 Video</button>
    <button class="dtab c" onclick="sw('c',this)">🏥 Clinic</button>
    <button class="dtab m" onclick="sw('m',this)">👁 Monitor</button>
    <button class="dtab rl" onclick="sw('rl',this)">🔒 Limit</button>
  </div>
  <div class="cbody" id="cbody">
    <div class="screen on" id="s-i">
      <div class="idle-wrap">
        <div class="idle-anim">🐾</div>
        <h1 class="idle-h">What's worrying<br><em>your pet</em> today?</h1>
        <p class="idle-sub">Describe symptoms in plain words. Takes 30 seconds. Free for all pet parents.</p>
        <div class="trust-row">
          <span class="tp">100% free</span>
          <span class="tp">India-trained AI</span>
          <span class="tp">Vet-reviewed</span>
          <span class="tp">No signup</span>
        </div>
      </div>
      <div class="qlabel">Common symptoms — tap to start</div>
      <div class="qgrid">
        <button class="qbtn" onclick="qsend('My dog has not eaten for 2 days and is very lethargic')"><span class="qi">🍽</span><span class="qt"><strong>Not Eating</strong><span>Appetite loss, skipping meals</span></span></button>
        <button class="qbtn" onclick="qsend('My dog has been vomiting repeatedly since this morning')"><span class="qi">🤢</span><span class="qt"><strong>Vomiting</strong><span>Throwing up, retching</span></span></button>
        <button class="qbtn" onclick="qsend('My dog is limping and putting no weight on one leg, leg is swollen')"><span class="qi">🦮</span><span class="qt"><strong>Limping</strong><span>Lameness, joint pain</span></span></button>
        <button class="qbtn" onclick="qsend('My pet has loose stools or diarrhea since yesterday')"><span class="qi">💧</span><span class="qt"><strong>Diarrhea</strong><span>Loose stools, stomach</span></span></button>
        <button class="qbtn" onclick="qsend('My cat has circular patches of hair loss and is scratching a lot')"><span class="qi">🐱</span><span class="qt"><strong>Skin / Itching</strong><span>Hair loss, scratching</span></span></button>
        <button class="qbtn" onclick="qsend('My pet seems very tired, lethargic and not interested in anything')"><span class="qi">😴</span><span class="qt"><strong>Lethargy</strong><span>Weak, dull, low energy</span></span></button>
      </div>
      <div class="slab">My pet is a</div>
      <div class="srow">
        <button class="sbtn on" onclick="setSp(this,'dog')">🐕 Dog</button>
        <button class="sbtn" onclick="setSp(this,'cat')">🐈 Cat</button>
        <button class="sbtn" onclick="setSp(this,'rabbit')">🐇 Rabbit</button>
        <button class="sbtn" onclick="setSp(this,'bird')">🐦 Bird</button>
      </div>
    </div>
    <div class="screen" id="s-e">
      <div style="display:flex;justify-content:flex-end"><div class="ppill">🐕 Max · German Shepherd · 5yr male · Gurgaon</div></div>
      <div class="mrow"><div><div class="mbub">Max suddenly collapsed. His stomach looks very bloated and he can't stand up. Breathing very fast.</div><div class="mtime">Just now</div></div></div>
      <div class="rcard">
        <div class="ub emergency">
          <div class="ub-lbl">⚠ Assessment complete — urgent action needed</div>
          <div class="ub-title">Go to Emergency Vet Now</div>
          <div class="ub-sub">Max needs immediate care — this can be fatal within hours</div>
          <div class="tbadge">🕐 Go now — every minute matters</div>
        </div>
        <div class="hs-wrap" style="margin-top:14px">
          <div class="hs-top">
            <div class="hs-left">
              <div class="hs-eyebrow">Pet Health Score</div>
              <div class="hs-score-row"><div class="hs-num" style="color:#C62828">22</div><div class="hs-denom">/100</div></div>
              <div class="hs-label" style="color:#C62828">Critical Risk</div>
              <div class="hs-sub">Needs emergency care now</div>
            </div>
            <div class="hs-gauge">
              <svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="32" fill="none" stroke="#f1f5f9" stroke-width="8"/><circle cx="40" cy="40" r="32" fill="none" stroke="#C62828" stroke-width="8" stroke-dasharray="201.1" stroke-dashoffset="156.9" stroke-linecap="round" transform="rotate(-90 40 40)"/><text x="40" y="44" text-anchor="middle" font-size="14" font-weight="900" fill="#C62828" font-family="Fraunces,serif">22</text></svg>
            </div>
          </div>
          <div class="hs-share">
            <div class="share-label"><strong>Share with other pet parents</strong>Let them know about Snoutiq</div>
            <button class="wa-btn" onclick="shareWA('emergency','Max',22)"><svg class="wa-icon" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>Share on WhatsApp</button>
          </div>
        </div>
        <div class="ctas" style="margin-top:12px">
          <button class="btn-p emergency">🏥 Find Emergency Vet Near Me</button>
          <button class="btn-s">🏛 Govt. Vet Hospital (Free/Low Cost)</button>
        </div>
        <div class="svc-wrap">
          <div class="svc-card">
            <div class="svc-header"><div class="svc-badge vah">Gurgaon &amp; Delhi NCR</div></div>
            <div class="svc-title">Vet at Home</div>
            <div class="svc-price-row"><div class="svc-price vah">₹999</div></div>
            <div class="svc-guarantee">Vet at your door in 60 mins or money back</div>
            <div class="svc-trust">
              <div class="svc-ti">Qualified vet visits you at home</div>
              <div class="svc-ti">In 60 mins or full money back</div>
              <div class="svc-ti">No travel stress for your pet</div>
            </div>
            <button class="svc-cta vah">🏠 Book Vet at Home</button>
          </div>
          <div class="svc-card">
            <div class="svc-header"><div></div></div>
            <div class="svc-title">Confirmed Clinic Booking</div>
            <div class="svc-price-row"><div class="svc-price cb">₹350</div></div>
            <div class="svc-guarantee">Guaranteed appointment, skip the wait</div>
            <div class="svc-trust">
              <div class="svc-ti">No queue — appointment confirmed instantly</div>
              <div class="svc-ti">Nearest available vet</div>
            </div>
            <button class="svc-cta cb">🗺 Book Clinic Appointment</button>
          </div>
        </div>
        <div class="cbdy">
          <div class="ablock"><div class="aico">🩺</div><div class="atxt"><div class="slbl">What we think is happening</div><p>A large dog collapsing with a visibly bloated abdomen and rapid breathing is the hallmark presentation of GDV (Gastric Dilatation-Volvulus), where the stomach twists and cuts off blood supply — without surgery this is fatal within 1–2 hours. Max needs to be at an emergency vet right now, not in a few hours.</p></div></div>
          <div class="donow"><div class="dnicon">⚡</div><div><div class="slbl">Do this right now</div><p>Keep Max as still and calm as possible. Do not give food, water, or any medication. Call the vet ahead while someone else drives — they need to be ready on arrival.</p></div></div>
          <div class="india"><span>🇮🇳</span><span>In Gurgaon, 24-hour emergency animal hospitals operate near Sohna Road. Government Animal Hospital (Sector 17) handles emergencies at minimal cost.</span></div>
          <div><div class="slbl">Tell the vet on arrival</div>
            <div class="wlist">
              <div class="wi danger"><span class="wiico">🔴</span><p>If Max's gums turn pale, white, or grey during travel — circulatory failure, alert vet immediately on arrival.</p></div>
              <div class="wi danger"><span class="wiico">🔴</span><p>If the abdomen becomes harder or more visibly swollen — increasing gastric pressure.</p></div>
              <div class="wi warning"><span class="wiico">🟡</span><p>If Max loses consciousness — carry him flat, do not flex the neck.</p></div>
              <div class="wi danger"><span class="wiico">🔴</span><p>Any of the above with laboured or open-mouth breathing — rush immediately.</p></div>
            </div>
          </div>
          <div><div class="slbl">Most likely causes</div><div class="cpills"><span class="cpill">GDV / Gastric Volvulus</span><span class="cpill">Splenic mass rupture</span><span class="cpill">Internal bleeding</span></div></div>
          <div class="vetq"><div class="vetqico">💬</div><div><div class="slbl">Be ready to tell the vet</div><p>When did Max last eat and how much? Did he run or exercise before collapsing? Any previous bloating episodes?</p></div></div>
        </div>
        <div class="disc"><div class="disc-i">🤖</div><div><div class="disc-t">Snoutiq AI — triage only</div><p>AI-generated guidance trained on veterinary cases across India, reviewed for clinical accuracy. Not a diagnosis. Always follow a licensed vet's advice.</p></div></div>
      </div>
    </div>
    <div class="screen" id="s-v">
      <div style="display:flex;justify-content:flex-end"><div class="ppill">🐕 Bruno · Labrador · 3yr male · Delhi NCR</div></div>
      <div class="mrow"><div><div class="mbub">Bruno hasn't eaten properly for 2 days and is very lethargic. Drinks water but refuses all food. No vomiting. Just lies around.</div><div class="mtime">Just now</div></div></div>
      <div class="rcard">
        <div class="ub video">
          <div class="ub-lbl">Assessment complete — routing decision</div>
          <div class="ub-title">See a Vet Today via Video</div>
          <div class="ub-sub">48 hours without eating plus lethargy needs professional assessment today</div>
          <div class="tbadge">🕐 Book a consult within the next 2–3 hours</div>
        </div>
        <div class="hs-wrap" style="margin-top:14px">
          <div class="hs-top">
            <div class="hs-left">
              <div class="hs-eyebrow">Pet Health Score</div>
              <div class="hs-score-row"><div class="hs-num" style="color:var(--orange)">63</div><div class="hs-denom">/100</div></div>
              <div class="hs-label" style="color:var(--orange)">Medium Risk</div>
              <div class="hs-sub">Needs professional check today</div>
            </div>
            <div class="hs-gauge">
              <svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="32" fill="none" stroke="#f1f5f9" stroke-width="8"/><circle cx="40" cy="40" r="32" fill="none" stroke="#F57C00" stroke-width="8" stroke-dasharray="201.1" stroke-dashoffset="74.4" stroke-linecap="round" transform="rotate(-90 40 40)"/><text x="40" y="44" text-anchor="middle" font-size="14" font-weight="900" fill="#F57C00" font-family="Fraunces,serif">63</text></svg>
            </div>
          </div>
          <div class="hs-share">
            <div class="share-label"><strong>Share Bruno's score</strong>Help other pet parents find Snoutiq</div>
            <button class="wa-btn" onclick="shareWA('video','Bruno',63)"><svg class="wa-icon" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>Share on WhatsApp</button>
            <button class="copy-link" onclick="copyLink(event)">Copy link</button>
          </div>
        </div>
        <div class="ctas" style="margin-top:12px">
          <button class="btn-p video">📱 Book Video Consult — ₹499</button>
          <button class="btn-s">🏥 Find Clinic Instead</button>
        </div>
        <div class="svc-wrap">
          <div class="svc-card featured">
            <div class="svc-header"><div class="svc-badge">Most popular</div></div>
            <div class="svc-title">Video Consultation</div>
            <div class="svc-price-row"><div class="svc-price video">₹499</div><div class="svc-orig">₹599</div></div>
            <div class="svc-guarantee">Connect in 15 mins or it's free</div>
            <div class="svc-trust">
              <div class="svc-ti">Experienced vets only</div>
              <div class="svc-ti">Connect in 15 mins</div>
              <div class="svc-ti">Money-back guarantee</div>
            </div>
            <button class="svc-cta video">📱 Book Video Consult Now</button>
          </div>
          <div class="svc-card">
            <div class="svc-header"><div class="svc-badge vah">Gurgaon &amp; Delhi NCR</div></div>
            <div class="svc-title">Vet at Home</div>
            <div class="svc-price-row"><div class="svc-price vah">₹999</div></div>
            <div class="svc-guarantee">Vet at your door in 60 mins or money back</div>
            <div class="svc-trust">
              <div class="svc-ti">Qualified vet visits you</div>
              <div class="svc-ti">60 mins or full refund</div>
            </div>
            <button class="svc-cta vah">🏠 Book Vet at Home</button>
          </div>
        </div>
        <div class="cbdy">
          <div class="ablock"><div class="aico">🩺</div><div class="atxt"><div class="slbl">What we think is happening</div><p>When a dog like Bruno goes 48 hours without eating and is noticeably lethargic, a vet needs to rule out fever, early gastrointestinal illness, or tick fever — which is very common in Delhi NCR and causes sudden appetite loss before other symptoms appear. A video consult is the right first step: a vet can check his gums on screen, assess energy levels, and tell you whether he needs bloodwork or can be managed at home.</p></div></div>
          <div class="donow"><div class="dnicon">⚡</div><div><div class="slbl">Do this right now</div><p>Check Bruno's gums — press gently, they should be moist and salmon-pink. If they look pale, white, or yellow, go to a clinic directly. Otherwise offer small sips of water and book a consult now.</p></div></div>
          <div class="india"><span>🇮🇳</span><span>Tick fever (Ehrlichia canis) is year-round in Delhi NCR and frequently causes sudden appetite loss with lethargy — often missed because fever can be intermittent. Worth ruling out early.</span></div>
          <div><div class="slbl">Safe to do while waiting</div>
            <div class="hclist">
              <div class="hci"><div class="hcn">1</div><span>Offer small sips of water every 30 minutes to prevent dehydration — do not force-feed.</span></div>
              <div class="hci"><div class="hcn">2</div><span>Note the last time Bruno passed urine and whether it looked normal — share in consult.</span></div>
              <div class="hci"><div class="hcn">3</div><span>Do not give paracetamol (Crocin/Dolo), ibuprofen, or any human medication — toxic to dogs.</span></div>
            </div>
          </div>
          <div class="fcard" id="fq-v">
            <div class="slbl">🔍 One question to narrow this down</div>
            <div class="fq">Has Bruno been outdoors recently, or could he have eaten anything unusual in the past 3 days?</div>
            <div class="fopts">
              <button class="fopt" onclick="answerFQ(this,'v','Yes, he was outdoors or may have eaten something unusual')">Yes — outdoors / may have eaten something</button>
              <button class="fopt" onclick="answerFQ(this,'v','No, he stayed home and has not eaten anything unusual')">No — stayed home, nothing unusual</button>
              <button class="fopt" onclick="answerFQ(this,'v','I am not sure whether he ate anything unusual')">I'm not sure</button>
            </div>
            <div class="fupd" id="fu-v">
              <div class="tbub"><div class="td"></div><div class="td"></div><div class="td"></div></div>
              <span>Updating assessment with your answer…</span>
            </div>
          </div>
          <div style="display:none" id="rev-v"><div class="rbadge">✓ Assessment updated based on your answer</div></div>
          <div><div class="slbl">Watch for — go to clinic if you see these</div>
            <div class="wlist">
              <div class="wi warning"><span class="wiico">🟡</span><p>If Bruno vomits more than twice in the next 6 hours — worsening sign, upgrade to clinic visit same day.</p></div>
              <div class="wi warning"><span class="wiico">🟡</span><p>If gums turn pale, white, or yellow at any point — clinic visit needed the same day.</p></div>
              <div class="wi"><span class="wiico">⚪</span><p>If he hasn't urinated in 12+ hours — mention to vet, indicates dehydration or organ stress.</p></div>
              <div class="wi danger"><span class="wiico">🔴</span><p>Any of the above with rapid or laboured breathing — skip video, go to emergency vet immediately.</p></div>
            </div>
          </div>
          <div><div class="slbl">Most likely causes</div><div class="cpills"><span class="cpill">Tick Fever (Ehrlichia)</span><span class="cpill">Gastroenteritis</span><span class="cpill">Dietary indiscretion</span><span class="cpill">Early infection / fever</span></div></div>
          <div class="vetq"><div class="vetqico">💬</div><div><div class="slbl">Be ready to tell the vet</div><p>When did Bruno last have a completely normal meal, and has he passed a normal stool in the last 24 hours?</p></div></div>
        </div>
        <div class="disc"><div class="disc-i">🤖</div><div><div class="disc-t">Snoutiq AI — triage only</div><p>AI-generated guidance trained on veterinary cases across India. Not a diagnosis. Always follow a licensed vet's advice.</p></div></div>
      </div>
    </div>
    <div class="screen" id="s-c">
      <div style="display:flex;justify-content:flex-end"><div class="ppill">🐕 Coco · Golden Retriever · 7yr female · Mumbai</div></div>
      <div class="mrow"><div><div class="mbub">Coco has been limping on her front right leg for 3 days. Leg looks swollen near the wrist. She yelps when I touch it. Still eating fine.</div><div class="mtime">Just now</div></div></div>
      <div class="rcard">
        <div class="ub clinic">
          <div class="ub-lbl">Assessment complete — routing decision</div>
          <div class="ub-title">Visit a Vet Clinic Today</div>
          <div class="ub-sub">Swollen joint with pain response after 3 days needs physical examination</div>
          <div class="tbadge">🕐 Book the earliest appointment today</div>
        </div>
        <div class="hs-wrap" style="margin-top:14px">
          <div class="hs-top">
            <div class="hs-left">
              <div class="hs-eyebrow">Pet Health Score</div>
              <div class="hs-score-row"><div class="hs-num" style="color:var(--red-lt)">47</div><div class="hs-denom">/100</div></div>
              <div class="hs-label" style="color:var(--red-lt)">High Risk</div>
              <div class="hs-sub">Needs vet attention today</div>
            </div>
            <div class="hs-gauge"><svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="32" fill="none" stroke="#f1f5f9" stroke-width="8"/><circle cx="40" cy="40" r="32" fill="none" stroke="#e53935" stroke-width="8" stroke-dasharray="201.1" stroke-dashoffset="106.6" stroke-linecap="round" transform="rotate(-90 40 40)"/><text x="40" y="44" text-anchor="middle" font-size="14" font-weight="900" fill="#e53935" font-family="Fraunces,serif">47</text></svg></div>
          </div>
          <div class="hs-share">
            <div class="share-label"><strong>Share Coco's score</strong>Spread the word</div>
            <button class="wa-btn" onclick="shareWA('clinic','Coco',47)"><svg class="wa-icon" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>Share on WhatsApp</button>
          </div>
        </div>
        <div class="ctas" style="margin-top:12px">
          <button class="btn-p clinic">🏠 Book Vet at Home — ₹999</button>
          <button class="btn-s">🗺 Find Nearest Clinic</button>
        </div>
        <div class="svc-wrap">
          <div class="svc-card"><div class="svc-header"><div class="svc-badge vah">Gurgaon &amp; Delhi NCR</div></div><div class="svc-title">Vet at Home</div><div class="svc-price-row"><div class="svc-price vah">₹999</div></div><div class="svc-guarantee">Vet at your door in 60 mins or money back</div><div class="svc-trust"><div class="svc-ti">Qualified vet visits you</div><div class="svc-ti">60 mins or full refund</div><div class="svc-ti">No travel stress for your pet</div></div><button class="svc-cta vah">🏠 Book Vet at Home</button></div>
          <div class="svc-card"><div class="svc-header"><div></div></div><div class="svc-title">Confirmed Clinic Booking</div><div class="svc-price-row"><div class="svc-price cb">₹350</div></div><div class="svc-guarantee">Guaranteed appointment, no waiting</div><div class="svc-trust"><div class="svc-ti">Skip the queue</div><div class="svc-ti">Nearest available vet</div></div><button class="svc-cta cb">🗺 Book Clinic Appointment</button></div>
        </div>
        <div class="cbdy">
          <div class="ablock"><div class="aico">🩺</div><div class="atxt"><div class="slbl">What we think is happening</div><p>Coco has had swelling and pain at her wrist joint for 3 days and reacts when it's touched. A vet needs to physically examine this — they'll feel the joint for crepitus, check range of motion, and likely recommend an X-ray to rule out fracture, ligament injury, or joint infection. At 7 years old, bone pathology also needs to be on the differential, which is why video alone isn't enough here.</p></div></div>
          <div class="donow"><div class="dnicon">⚡</div><div><div class="slbl">Do this right now</div><p>Restrict Coco's movement — no stairs, running, or jumping. Carry her where possible. Do not give ibuprofen or paracetamol — both are toxic to dogs. Book an appointment now.</p></div></div>
          <div class="fcard" id="fq-c">
            <div class="slbl">🔍 Helps narrow the diagnosis</div>
            <div class="fq">Did the limping start suddenly after Coco ran or fell, or did it come on gradually over several days?</div>
            <div class="fopts">
              <button class="fopt" onclick="answerFQ(this,'c','Suddenly after running or a fall or impact event')">Suddenly — after activity or a fall</button>
              <button class="fopt" onclick="answerFQ(this,'c','Gradually over several days with no specific incident I can recall')">Gradually — no specific incident</button>
              <button class="fopt" onclick="answerFQ(this,'c','I am not sure exactly when it started')">I'm not sure when it started</button>
            </div>
            <div class="fupd" id="fu-c"><div class="tbub"><div class="td"></div><div class="td"></div><div class="td"></div></div><span>Updating assessment…</span></div>
          </div>
          <div style="display:none" id="rev-c"><div class="rbadge">✓ Assessment updated based on your answer</div></div>
          <div><div class="slbl">Watch for — go to emergency if you see these</div>
            <div class="wlist">
              <div class="wi danger"><span class="wiico">🔴</span><p>If swelling spreads rapidly up the leg or skin becomes hot, red, or weeping — possible infection, same-day urgent attention.</p></div>
              <div class="wi warning"><span class="wiico">🟡</span><p>If Coco completely stops bearing weight on the leg — go immediately, don't wait for the appointment.</p></div>
              <div class="wi"><span class="wiico">⚪</span><p>If she stops eating or becomes lethargic alongside the limping — call the clinic and mention this change.</p></div>
              <div class="wi danger"><span class="wiico">🔴</span><p>Any of the above with rapid or laboured breathing — emergency vet immediately.</p></div>
            </div>
          </div>
          <div><div class="slbl">Most likely causes</div><div class="cpills"><span class="cpill">Ligament / tendon injury</span><span class="cpill">Incomplete fracture</span><span class="cpill">Septic arthritis</span><span class="cpill">Osteoarthritis (age)</span><span class="cpill">Bone lesion</span></div></div>
          <div class="vetq"><div class="vetqico">💬</div><div><div class="slbl">Be ready to tell the vet</div><p>Can Coco put any weight at all on the leg, and has she had any previous joint problems or injuries?</p></div></div>
        </div>
        <div class="disc"><div class="disc-i">🤖</div><div><div class="disc-t">Snoutiq AI — triage only</div><p>AI-generated guidance trained on veterinary cases across India. Not a diagnosis. Always follow a licensed vet's advice.</p></div></div>
      </div>
    </div>
    <div class="screen" id="s-m">
      <div style="display:flex;justify-content:flex-end"><div class="ppill">🐱 Luna · Persian cat · 2yr female · Pune</div></div>
      <div class="mrow"><div><div class="mbub">Luna has been sneezing a few times since yesterday. No discharge, no fever. She's eating and playing normally. Just sneezing more than usual.</div><div class="mtime">Just now</div></div></div>
      <div class="rcard">
        <div class="ub monitor">
          <div class="ub-lbl">Assessment complete — home monitoring guidance</div>
          <div class="ub-title">Monitor at Home for Now</div>
          <div class="ub-sub">Symptoms are mild and she's eating well — follow these guidelines for 48 hours</div>
          <div class="tbadge">🕐 If no improvement in 48 hours, book a consult</div>
        </div>
        <div class="hs-wrap" style="margin-top:14px">
          <div class="hs-top">
            <div class="hs-left">
              <div class="hs-eyebrow">Pet Health Score</div>
              <div class="hs-score-row"><div class="hs-num" style="color:var(--green)">82</div><div class="hs-denom">/100</div></div>
              <div class="hs-label" style="color:var(--green)">Low Risk</div>
              <div class="hs-sub">Monitor closely at home</div>
            </div>
            <div class="hs-gauge"><svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="32" fill="none" stroke="#f1f5f9" stroke-width="8"/><circle cx="40" cy="40" r="32" fill="none" stroke="#2E7D32" stroke-width="8" stroke-dasharray="201.1" stroke-dashoffset="36.2" stroke-linecap="round" transform="rotate(-90 40 40)"/><text x="40" y="44" text-anchor="middle" font-size="14" font-weight="900" fill="#2E7D32" font-family="Fraunces,serif">82</text></svg></div>
          </div>
          <div class="hs-share">
            <div class="share-label"><strong>Share Luna's score</strong>Help other pet parents</div>
            <button class="wa-btn" onclick="shareWA('monitor','Luna',82)"><svg class="wa-icon" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>Share on WhatsApp</button>
            <button class="copy-link" onclick="copyLink(event)">Copy link</button>
          </div>
        </div>
        <div class="ctas" style="margin-top:12px">
          <button class="btn-p monitor">📋 Save Monitoring Guide</button>
          <button class="btn-s">📱 Video Consult if Worried — ₹499</button>
        </div>
        <div class="svc-wrap">
          <div class="svc-card featured"><div class="svc-header"><div class="svc-badge">If symptoms worsen</div></div><div class="svc-title">Video Consultation</div><div class="svc-price-row"><div class="svc-price video">₹499</div><div class="svc-orig">₹599</div></div><div class="svc-guarantee">Connect in 15 mins or it's free</div><div class="svc-trust"><div class="svc-ti">Experienced vets only</div><div class="svc-ti">Connect in 15 mins</div><div class="svc-ti">Money-back guarantee</div></div><button class="svc-cta video">📱 Book Video Consult</button></div>
        </div>
        <div class="cbdy">
          <div class="ablock"><div class="aico">🩺</div><div class="atxt"><div class="slbl">What we think is happening</div><p>Occasional sneezing since yesterday with no discharge, normal appetite, and normal energy most likely reflects a mild upper respiratory irritation or the very early stage of a cat cold. Since Luna is eating, playing, and has no eye or nasal discharge, monitoring for 48 hours is appropriate. Persian cats are brachycephalic, so pay attention to her breathing — any sign of effort or open-mouth breathing changes the picture immediately.</p></div></div>
          <div class="donow"><div class="dnicon">⚡</div><div><div class="slbl">Do this right now</div><p>Check Luna's nose and eyes for any discharge — even clear discharge is worth noting. Run a steamy shower for 10 minutes and let her sit nearby (not in the steam) to help open the airways.</p></div></div>
          <div class="india"><span>🇮🇳</span><span>Persian cats have compressed nasal passages making them prone to respiratory symptoms. Any breathing difficulty in Persians should be treated as urgent regardless of other symptoms.</span></div>
          <div><div class="slbl">Home care for 48 hours</div>
            <div class="hclist">
              <div class="hci"><div class="hcn">1</div><span>Keep Luna warm — avoid AC blowing directly on her. Cold drafts worsen respiratory irritation.</span></div>
              <div class="hci"><div class="hcn">2</div><span>Gently wipe any crust around the nose with a warm damp cloth — do not use cotton buds inside the nostril.</span></div>
              <div class="hci"><div class="hcn">3</div><span>Warm her food slightly — smell helps cats eat even when nose feels congested.</span></div>
              <div class="hci"><div class="hcn">4</div><span>Do not give paracetamol (Crocin/Dolo) or any human medication — toxic to cats, even at small doses.</span></div>
            </div>
          </div>
          <div><div class="slbl">Watch for — book a consult if you see these</div>
            <div class="wlist">
              <div class="wi warning"><span class="wiico">🟡</span><p>If Luna develops yellow or green discharge from nose or eyes — bacterial infection that needs treatment.</p></div>
              <div class="wi warning"><span class="wiico">🟡</span><p>If she stops eating for more than 24 hours — cats develop serious liver disease from extended anorexia. Book same day.</p></div>
              <div class="wi"><span class="wiico">⚪</span><p>If sneezing increases to constant or fits of sneezing — suggests more significant respiratory involvement.</p></div>
              <div class="wi danger"><span class="wiico">🔴</span><p>Open-mouth breathing, neck stretching forward, or laboured breathing — respiratory emergency for cats, go to vet immediately.</p></div>
            </div>
          </div>
          <div><div class="slbl">Most likely causes</div><div class="cpills"><span class="cpill">Feline viral rhinotracheitis</span><span class="cpill">Environmental irritant</span><span class="cpill">Early calicivirus</span><span class="cpill">Nasal polyp (if recurrent)</span></div></div>
          <div class="vetq"><div class="vetqico">💬</div><div><div class="slbl">If you book a consult, tell the vet</div><p>Is Luna vaccinated, and has she had contact with other cats or been outdoors recently?</p></div></div>
        </div>
        <div class="disc"><div class="disc-i">🤖</div><div><div class="disc-t">Snoutiq AI — triage only</div><p>AI-generated guidance trained on veterinary cases across India. Not a diagnosis. Always follow a licensed vet's advice.</p></div></div>
      </div>
    </div>
    <div class="screen" id="s-rl">
      <div class="rlcard">
        <div class="rlhdr">
          <div class="rli">🔒</div>
          <div class="rlt">You've used all 3 free checks today</div>
          <div class="rls">Free limit resets at midnight · Your pet is the priority</div>
        </div>
        <div class="rlb">
          <div class="rldots">
            <div class="rld"></div><div class="rld"></div><div class="rld"></div><div class="rld x"></div>
          </div>
          <div class="rlmsg">If your pet needs help right now, book directly — an experienced vet will assess your pet personally and give you a clear plan.<br><br>Or come back tomorrow for 3 more free checks.</div>
          <button class="rlcta">📱 Video Consult Now — ₹499</button>
          <button class="rlcta" style="background:var(--purple);margin-bottom:8px">🏠 Vet at Home — ₹999</button>
          <button class="rllater">Come back tomorrow</button>
        </div>
      </div>
    </div>
  </div>
  <div class="ibar">
    <textarea class="inp" id="inp" placeholder="Describe your pet's symptoms…" rows="1" oninput="resize(this)"></textarea>
    <button class="sendbtn" onclick="send()">
      <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
    </button>
  </div>
</div>
<script>
const ASK_URL = @json(url('/ask'));
let species = 'dog';

function sw(name, el) {
  document.querySelectorAll('.screen').forEach((screen) => screen.classList.remove('on'));
  document.querySelectorAll('.dtab').forEach((tab) => tab.classList.remove('on'));
  document.getElementById('s-' + name).classList.add('on');
  if (el) {
    el.classList.add('on');
  }
  document.getElementById('cbody').scrollTop = 0;
}

function switchDemo(name) {
  const map = { v: 'v', video: 'v', emergency: 'e', clinic: 'c', monitor: 'm' };
  sw(map[name] || name);
}

function setSp(btn, sp) {
  document.querySelectorAll('.sbtn').forEach((button) => button.classList.remove('on'));
  btn.classList.add('on');
  species = sp;
}

function qsend(txt) {
  document.getElementById('inp').value = txt;
  send();
}

function send() {
  const el = document.getElementById('inp');
  const msg = el.value.trim();
  if (!msg) return;

  el.value = '';
  el.style.height = 'auto';

  const t = msg.toLowerCase();
  let target = 'v';
  if (/collaps|bleed|seizure|poison|not breath|limp body|bloat|gdv/.test(t)) target = 'e';
  else if (/limp|swell|swollen|fracture|lame|bone|joint|can.t walk/.test(t)) target = 'c';
  else if (/sneez|mild|slight|occasional|once|a bit/.test(t)) target = 'm';

  sw(target);
}

function answerFQ(btn, id) {
  const opts = btn.closest('.fopts');
  opts.querySelectorAll('.fopt').forEach((button) => {
    button.classList.remove('sel');
    button.disabled = true;
  });
  btn.classList.add('sel');

  const upd = document.getElementById('fu-' + id);
  upd.classList.add('show');

  setTimeout(() => {
    upd.classList.remove('show');
    document.getElementById('fq-' + id).style.opacity = '.55';
    const rev = document.getElementById('rev-' + id);
    if (rev) {
      rev.style.display = 'block';
    }
  }, 2200);
}

function shareWA(routing, petName, score) {
  const labels = { emergency: 'Critical Risk', video: 'Medium Risk', clinic: 'High Risk', monitor: 'Low Risk' };
  const label = labels[routing] || 'Medium Risk';
  const text = encodeURIComponent(
    `🐾 I just checked my pet ${petName} on Snoutiq AI.\n\n` +
    `Pet Health Score: *${score}/100* (${label})\n\n` +
    'Snoutiq AI gave me specific advice in seconds — it\'s free for all pet parents in India.\n\n' +
    `Check your pet here 👇\n${ASK_URL}`
  );
  window.open('https://wa.me/?text=' + text, '_blank');
}

function copyLink(event) {
  const target = event?.target;
  navigator.clipboard.writeText(ASK_URL).then(() => {
    if (!target) return;
    const original = target.textContent;
    target.textContent = 'Copied!';
    setTimeout(() => {
      target.textContent = original;
    }, 2000);
  });
}

function resize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 100) + 'px';
}

document.getElementById('inp').addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    send();
  }
});
</script>
</body>
</html>
