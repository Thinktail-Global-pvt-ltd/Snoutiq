{{-- resources/views/clinic/payments.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Financials')
@section('page_title','Financials')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<style>
  .fin-root{
    --fin-bg:#f5f7fb; --fin-panel:#ffffff; --fin-muted:#6b7280;
    --fin-blue:#2563eb; --fin-accent:#7c3aed; --fin-green:#10b981; --fin-orange:#f97316; --fin-danger:#ef4444;
    --fin-radius:12px; --fin-shadow:0 10px 30px rgba(10,20,40,0.06);
    background:var(--fin-bg); color:#0b1220; font-family:'Inter',system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;
    padding:4px;
  }
  .fin-root *{box-sizing:border-box;}
  .fin-container{max-width:1200px;margin:0 auto;padding:12px;display:flex;flex-direction:column;gap:16px}
  .fin-header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
  .fin-title{font-size:26px;font-weight:700;color:var(--fin-blue)}
  .fin-subtitle{color:var(--fin-muted);font-size:13px;margin-top:6px}
  .fin-header-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end}
  .fin-tabs{display:flex;gap:8px;flex-wrap:wrap}
  .fin-tabBtn{padding:10px 14px;border-radius:999px;border:1px solid #eef6ff;background:#fff;cursor:pointer;font-weight:700;transition:all .15s}
  .fin-tabBtn.fin-active{background:linear-gradient(90deg,var(--fin-blue),var(--fin-accent));color:#fff;box-shadow:var(--fin-shadow)}
  .fin-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
  .fin-kpi{background:var(--fin-panel);padding:16px;border-radius:12px;box-shadow:var(--fin-shadow);border:1px solid #eef3ff}
  .fin-num{font-weight:800;font-size:20px}
  .fin-label{color:var(--fin-muted);font-size:13px;margin-top:6px}
  .fin-panel{background:var(--fin-panel);padding:18px;border-radius:12px;box-shadow:var(--fin-shadow);border:1px solid #eef3ff}
  .fin-flexRow{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
  .fin-filters{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:12px 0}
  .fin-input,.fin-select{padding:10px;border-radius:10px;border:1px solid #eef6ff;background:#fff;font-size:14px}
  .fin-search{width:320px}
  .fin-charts{display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-top:12px}
  .fin-chartCard{background:#fff;padding:12px;border-radius:12px;border:1px solid #f5f8fb;box-shadow:var(--fin-shadow)}
  .fin-settleCard{background:#fff;padding:12px;border-radius:12px;border:1px solid #f5f8fb;display:flex;flex-direction:column;gap:8px}
  .fin-tableWrap{overflow:auto;border-radius:12px;border:1px solid #eef6ff;margin-top:12px;background:#fff}
  .fin-table{width:100%;border-collapse:collapse}
  .fin-table thead th{padding:14px 16px;text-align:left;color:var(--fin-muted);font-size:13px;border-bottom:1px solid #f6f9ff;background:#fbfdff}
  .fin-table tbody td{padding:16px;border-bottom:1px solid #fbfdff;font-size:14px;vertical-align:top}
  .fin-rowHover:hover{background:#fcfdff}
  .fin-expanded{background:#fcfdff}
  .fin-expandPanel{padding:12px 16px;border-top:1px dashed #eef6ff;background:#fafcff;color:var(--fin-muted)}
  .fin-pill{display:inline-block;padding:6px 10px;border-radius:999px;font-weight:700;font-size:13px;text-transform:capitalize}
  .fin-pill-settled{background:#ecfdf5;color:var(--fin-green)}
  .fin-pill-pending{background:#fff7ed;color:var(--fin-orange)}
  .fin-pill-failed{background:#fff0f0;color:var(--fin-danger)}
  .fin-btn{padding:8px 12px;border-radius:10px;border:0;cursor:pointer;font-weight:700;font-size:14px;transition:transform .12s ease, box-shadow .12s ease}
  .fin-btn-primary{background:linear-gradient(90deg,var(--fin-blue),var(--fin-accent));color:white}
  .fin-btn-ghost{background:#fff;border:1px solid #eef6ff;color:var(--fin-muted)}
  .fin-btn:hover{transform:translateY(-1px);box-shadow:var(--fin-shadow)}
  .fin-small{color:var(--fin-muted);font-size:13px}
  .fin-muted{color:var(--fin-muted)}
  .fin-empty{text-align:center;padding:16px;color:var(--fin-muted)}
  .fin-drawer{position:fixed;right:0;top:0;bottom:0;width:460px;background:var(--fin-panel);box-shadow:-12px 0 40px rgba(2,6,23,0.12);transform:translateX(100%);transition:transform .28s;z-index:1200;padding:16px;overflow:auto}
  .fin-drawer.fin-open{transform:translateX(0)}
  @media(max-width:1100px){
    .fin-kpis{grid-template-columns:repeat(2,1fr)}
    .fin-charts{grid-template-columns:1fr;gap:12px}
    .fin-search{width:220px}
    .fin-drawer{width:100%}
  }
</style>
@endsection

@section('content')
@php
  use Carbon\Carbon;
  use Illuminate\Support\Str;

  $transactions = $transactions ?? collect();
  $clinicName = $vet->name ?? ('Clinic #'.($vetId ?? '—'));
  $slugLabel = $slug ?? null;

  $successfulStatuses = ['captured','authorized','verified','completed','paid','success','successful','settled'];
  $pendingStatuses = ['created','pending','processing','initiated','in_progress'];

  $normalizeType = function ($txn) {
    $raw = strtolower(trim((string) (
      data_get($txn->metadata, 'type')
      ?? data_get($txn->metadata, 'service_type')
      ?? data_get($txn->metadata, 'service')
      ?? $txn->type
      ?? ''
    )));

    if (Str::contains($raw, ['tele', 'video'])) {
      return 'telemed';
    }
    if (Str::contains($raw, ['clinic', 'in-clinic', 'inclinic', 'offline'])) {
      return 'inclinic';
    }
    if (Str::contains($raw, ['booking'])) {
      return 'booking_fee';
    }
    if (Str::contains($raw, ['order', 'medicine', 'pharmacy', 'lab', 'test'])) {
      return 'order';
    }

    return $raw ?: 'other';
  };

  $transactionsView = $transactions->map(function ($txn) use ($normalizeType) {
    $createdAt = optional($txn->created_at)->timezone('Asia/Kolkata');
    $type = $normalizeType($txn);
    $gross = ((int) ($txn->amount_paise ?? 0)) / 100;
    $commissionPct = data_get($txn->metadata, 'commission_pct')
      ?? data_get($txn->metadata, 'commission_percent')
      ?? 0;
    $commissionPct = is_numeric($commissionPct) ? (float) $commissionPct : 0;
    $commission = round($gross * ($commissionPct / 100), 2);
    $net = round($gross - $commission, 2);

    $user = $txn->user;
    $pet = data_get($user, 'pets.0.name')
      ?? data_get($txn->metadata, 'pet_name')
      ?? '-';
    $doctor = $txn->doctor;
    $doctorName = $doctor?->doctor_name ?? $doctor?->name ?? '-';
    $clinicName = data_get($doctor, 'clinic.name') ?? data_get($txn->metadata, 'clinic_name') ?? '-';

    return [
      'id' => $txn->id,
      'payment_id' => $txn->reference ?? ('TXN#'.$txn->id),
      'order_id' => data_get($txn->metadata, 'order_id')
        ?? data_get($txn->metadata, 'razorpay_order_id'),
      'date_iso' => $createdAt?->toIso8601String(),
      'type' => $type,
      'pet' => $pet,
      'owner' => $user?->name ?? data_get($txn->metadata, 'user_name') ?? '-',
      'doctor' => $doctorName,
      'clinic' => $clinicName,
      'gross' => $gross,
      'commission_pct' => $commissionPct,
      'commission' => $commission,
      'net' => $net,
      'status' => strtolower((string) ($txn->status ?? '')),
      'status_label' => strtoupper($txn->status ?? '-'),
      'payment_mode' => strtoupper($txn->payment_method ?? $txn->type ?? '-'),
      'notes' => data_get($txn->metadata, 'notes'),
      'service' => data_get($txn->metadata, 'service_name')
        ?? data_get($txn->metadata, 'service')
        ?? $txn->type
        ?? '-',
    ];
  })->values();

  $now = Carbon::now('Asia/Kolkata');
  $thirtyDaysAgo = $now->copy()->subDays(30);
  $recentTransactions = $transactionsView->filter(function ($txn) use ($thirtyDaysAgo) {
    return $txn['date_iso'] && Carbon::parse($txn['date_iso'])->gte($thirtyDaysAgo);
  });

  $kpiTotal = round($recentTransactions->sum('gross'), 2);
  $kpiTele  = round($recentTransactions->where('type', 'telemed')->sum('gross'), 2);
  $kpiInclinic = round($recentTransactions->where('type', 'inclinic')->sum('gross'), 2);
  $kpiOrders = round($recentTransactions->where('type', 'order')->sum('gross'), 2);

  $lineLabels = [];
  $lineValues = [];
  $lineBase = $now->copy()->startOfDay();
  for ($i = 9; $i >= 0; $i--) {
    $day = $lineBase->copy()->subDays($i);
    $lineLabels[] = $day->format('M j');
    $lineValues[] = round(
      $transactionsView
        ->filter(fn ($txn) => $txn['date_iso'] && Carbon::parse($txn['date_iso'])->isSameDay($day))
        ->sum('gross'),
      2
    );
  }

  $donutData = [
    'telemed' => round($transactionsView->where('type', 'telemed')->sum('gross'), 2),
    'inclinic' => round($transactionsView->where('type', 'inclinic')->sum('gross'), 2),
    'order' => round($transactionsView->where('type', 'order')->sum('gross'), 2),
    'other' => round(
      $transactionsView->reject(fn ($t) => in_array($t['type'], ['telemed','inclinic','order'], true))->sum('gross'),
      2
    ),
  ];

  $kpiData = [
    'total' => $kpiTotal,
    'telemed' => $kpiTele,
    'inclinic' => $kpiInclinic,
    'orders' => $kpiOrders,
  ];
  $donutValues = array_values($donutData);

  $lastSuccess = $transactionsView->first(function ($txn) use ($successfulStatuses) {
    return in_array($txn['status'], $successfulStatuses, true);
  });
  $lastPayout = $lastSuccess && $lastSuccess['date_iso']
    ? Carbon::parse($lastSuccess['date_iso'])->format('d M Y')
    : '—';
  $pendingPayouts = round(
    $transactionsView->filter(fn ($txn) => in_array($txn['status'], $pendingStatuses, true))->sum('gross'),
    2
  );
  $nextPayout = $now->copy()->addDays(7)->format('d M Y');
@endphp

<div class="fin-root">
  <div class="fin-container">

    <div class="fin-header">
      <div>
        <div class="fin-title">Financials</div>
        <div class="fin-subtitle">Payments & Revenue — together with Orders management</div>
        <div class="fin-subtitle">Clinic: {{ $clinicName }} @if($slugLabel) · Slug: {{ $slugLabel }} @endif</div>
      </div>
      <div class="fin-header-actions">
        <div class="fin-tabs" role="tablist">
          <button class="fin-tabBtn fin-active" id="tabPayments">Payments & Revenue</button>
          <button class="fin-tabBtn" id="tabOrders">Orders</button>
        </div>
        <button class="fin-btn fin-btn-ghost" id="exportAll" type="button">Export CSV</button>
      </div>
    </div>

    <div class="fin-kpis" id="kpiRow">
      <div class="fin-kpi">
        <div class="fin-num" id="k_totalRevenue">₹{{ number_format($kpiTotal, 0) }}</div>
        <div class="fin-label">Total Revenue (30d)</div>
      </div>
      <div class="fin-kpi">
        <div class="fin-num" id="k_telemed">₹{{ number_format($kpiTele, 0) }}</div>
        <div class="fin-label">Telemedicine Revenue (30d)</div>
      </div>
      <div class="fin-kpi">
        <div class="fin-num" id="k_inclinic">₹{{ number_format($kpiInclinic, 0) }}</div>
        <div class="fin-label">In-Clinic Revenue (30d)</div>
      </div>
      <div class="fin-kpi">
        <div class="fin-num" id="k_ordersRev">₹{{ number_format($kpiOrders, 0) }}</div>
        <div class="fin-label">Orders Revenue (30d)</div>
      </div>
    </div>

    <div class="fin-panel" id="paymentsPanel">
      <div class="fin-flexRow">
        <div style="font-weight:800">Payments & Revenue</div>
        <div class="fin-small">Financial transactions and settlements</div>
      </div>

      <div class="fin-filters">
        <input id="paySearch" class="fin-input fin-search" placeholder="Search pet, owner, doctor, trx id..." type="search" />
        <input id="payFrom" class="fin-input" type="date" />
        <input id="payTo" class="fin-input" type="date" />
        <select id="payType" class="fin-select">
          <option value="">All types</option>
          <option value="telemed">Telemedicine</option>
          <option value="inclinic">In-Clinic</option>
          <option value="booking_fee">Booking Fee</option>
          <option value="order">Order</option>
          <option value="other">Other</option>
        </select>
        <select id="payStatus" class="fin-select">
          <option value="">All status</option>
          <option value="settled">Settled</option>
          <option value="pending">Pending</option>
          <option value="failed">Failed</option>
        </select>
        <div style="margin-left:auto;display:flex;gap:8px">
          <button class="fin-btn fin-btn-ghost" id="exportPayments" type="button">Export</button>
          <button class="fin-btn fin-btn-primary" id="downloadStatement" type="button">Download Statement</button>
        </div>
      </div>

      <div class="fin-charts">
        <div class="fin-chartCard">
          <div class="fin-flexRow">
            <div style="font-weight:800">Revenue — Last 10 days</div>
            <div class="fin-small">Trend of clinic revenue</div>
          </div>
          <canvas id="revenueLine" style="height:220px;margin-top:8px"></canvas>
        </div>

        <div class="fin-chartCard">
          <div class="fin-flexRow">
            <div style="font-weight:800">Revenue Breakdown</div>
            <div class="fin-small">By source</div>
          </div>
          <canvas id="revDonut" style="height:220px;margin-top:8px"></canvas>

          <div style="margin-top:12px" class="fin-settleCard">
            <div style="font-weight:800">Settlement Summary</div>
            <div class="fin-small">Last payout: <strong id="lastPayout">{{ $lastPayout }}</strong></div>
            <div class="fin-small">Next payout date: <strong id="nextPayout">{{ $nextPayout }}</strong></div>
            <div class="fin-small">Pending payouts: <strong id="pendingPayouts">₹{{ number_format($pendingPayouts, 2) }}</strong></div>
            <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
              <button class="fin-btn fin-btn-ghost" id="viewPayoutHistory" type="button">Payout History</button>
              <button class="fin-btn fin-btn-primary" id="downloadPayout" type="button">Download Report</button>
            </div>
          </div>
        </div>
      </div>

      <div style="margin-top:16px">
        <div style="font-weight:800;margin-bottom:8px">Transactions</div>
        <div class="fin-tableWrap">
          <table class="fin-table" id="paymentsTable">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Pet</th>
                <th>Owner</th>
                <th>Doctor</th>
                <th>Gross</th>
                <th>Commission</th>
                <th>Net</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="paymentsBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="fin-panel" id="ordersPanel" style="display:none">
      <div class="fin-flexRow">
        <div style="font-weight:800">Orders</div>
        <div class="fin-small">Operational orders (medicines, tests, packages) — fulfillment & invoices</div>
      </div>

      <div class="fin-filters">
        <input id="orderSearch" class="fin-input fin-search" placeholder="Search order ID, pet, owner..." type="search" />
        <input id="orderFrom" class="fin-input" type="date" />
        <input id="orderTo" class="fin-input" type="date" />
        <select id="orderType" class="fin-select">
          <option value="">All types</option>
          <option>Medicine</option>
          <option>Test</option>
          <option>Package</option>
        </select>
        <select id="orderStatus" class="fin-select">
          <option value="">All status</option>
          <option value="completed">Completed</option>
          <option value="pending">Pending</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <div style="margin-left:auto;display:flex;gap:8px">
          <button class="fin-btn fin-btn-ghost" id="bulkFulfill" type="button">Mark fulfilled</button>
          <button class="fin-btn fin-btn-primary" id="exportOrders" type="button">Export</button>
        </div>
      </div>

      <div class="fin-tableWrap">
        <table class="fin-table" id="ordersTableCombined">
          <thead>
            <tr>
              <th style="width:36px"><input type="checkbox" id="orderSelectAll"/></th>
              <th>Order ID</th>
              <th>Date</th>
              <th>Type</th>
              <th>Pet</th>
              <th>Owner</th>
              <th>Doctor</th>
              <th style="text-align:right">Amount</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="ordersBodyCombined"></tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<div class="fin-drawer" id="orderDrawer" aria-hidden="true">
  <h3 id="drawerTitle" style="margin-top:0">Order #</h3>
  <div class="fin-small" id="drawerMeta">Date • Type • Status</div>
  <hr style="margin:12px 0"/>
  <div id="drawerItems"></div>
  <div style="margin-top:12px">
    <div class="fin-small">Payment</div>
    <div style="display:flex;justify-content:space-between;margin-top:8px"><div>Payment mode</div><div id="drawerPaymentMode">Prepaid</div></div>
    <div style="display:flex;justify-content:space-between;margin-top:8px"><div>Subtotal</div><div id="drawerSubtotal">₹0</div></div>
    <div style="display:flex;justify-content:space-between;margin-top:8px"><div>Tax</div><div id="drawerTax">₹0</div></div>
    <div style="display:flex;justify-content:space-between;margin-top:8px;font-weight:800"><div>Net</div><div id="drawerNet">₹0</div></div>
  </div>

  <div style="margin-top:12px">
    <div class="fin-small">Notes</div>
    <div id="drawerNotes" style="margin-top:6px" class="fin-small">—</div>
  </div>

  <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
    <button class="fin-btn fin-btn-ghost" id="closeDrawer" type="button">Close</button>
    <button class="fin-btn fin-btn-primary" id="downloadInvoice" type="button">Download Invoice</button>
    <button class="fin-btn fin-btn-primary" id="markFulfilled" type="button">Mark Fulfilled</button>
  </div>
</div>
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const transactions = @json($transactionsView);
    const kpiData = @json($kpiData);
    const lineLabels = @json($lineLabels);
    const lineValues = @json($lineValues);
    const donutLabels = ['Telemed','In-Clinic','Orders','Other'];
    const donutValues = @json($donutValues);
    const lastPayout = @json($lastPayout);
    const nextPayout = @json($nextPayout);
    const pendingPayouts = @json($pendingPayouts);
    const successfulStatuses = @json($successfulStatuses);
    const pendingStatuses = @json($pendingStatuses);

    const orders = []; // Orders API not wired on this page yet; UI mirrors provided static mock.

    const $ = (id) => document.getElementById(id);
    const fmtMoney = (n, withPaise = false) => {
      const opts = withPaise
        ? { minimumFractionDigits: 2, maximumFractionDigits: 2 }
        : { maximumFractionDigits: 0 };
      return '₹' + (Number(n) || 0).toLocaleString('en-IN', opts);
    };
    const fmtDate = (iso) => {
      if (!iso) return '—';
      return new Date(iso).toLocaleString('en-IN', { hour12: true });
    };
    const statusClass = (status) => {
      const key = (status || '').toLowerCase();
      if (successfulStatuses.includes(key)) return 'fin-pill-settled';
      if (pendingStatuses.includes(key)) return 'fin-pill-pending';
      return 'fin-pill-failed';
    };

    // KPIs
    function renderKPIs() {
      const formatter = (val) => fmtMoney(val);
      $('k_totalRevenue').textContent = formatter(kpiData.total);
      $('k_telemed').textContent = formatter(kpiData.telemed);
      $('k_inclinic').textContent = formatter(kpiData.inclinic);
      $('k_ordersRev').textContent = formatter(kpiData.orders);
    }

    // Charts
    let revenueLineChart, donutChart;
    function renderCharts() {
      const ctx = $('revenueLine')?.getContext('2d');
      if (ctx) {
        if (revenueLineChart) revenueLineChart.destroy();
        revenueLineChart = new Chart(ctx, {
          type:'line',
          data:{ labels: lineLabels, datasets: [{ label:'Revenue', data: lineValues, borderColor:'#2563eb', backgroundColor:'rgba(37,99,235,0.08)', fill:true, tension:0.25 }]},
          options:{ plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, ticks:{ callback: (v) => '₹'+v } } } }
        });
      }

      const dctx = $('revDonut')?.getContext('2d');
      if (dctx) {
        if (donutChart) donutChart.destroy();
        donutChart = new Chart(dctx, {
          type:'doughnut',
          data:{ labels: donutLabels, datasets:[{ data: donutValues, backgroundColor:['#7c3aed','#2563eb','#10b981','#f59e0b'] }]},
          options:{ plugins:{ legend:{ position:'bottom' } } }
        });
      }
    }

    // Payments table
    let currentPayments = transactions.slice();
    function filteredTransactions() {
      const q = ($('paySearch').value || '').trim().toLowerCase();
      const from = $('payFrom').value ? new Date($('payFrom').value + 'T00:00:00') : null;
      const to = $('payTo').value ? new Date($('payTo').value + 'T23:59:59') : null;
      const type = $('payType').value;
      const statusFilter = $('payStatus').value;

      return transactions.filter((tr) => {
        if (q) {
          const s = `${tr.pet} ${tr.owner} ${tr.doctor} ${tr.payment_id}`.toLowerCase();
          if (!s.includes(q)) return false;
        }
        if (from) {
          if (!tr.date_iso || new Date(tr.date_iso) < from) return false;
        }
        if (to) {
          if (!tr.date_iso || new Date(tr.date_iso) > to) return false;
        }
        if (type && tr.type !== type) return false;
        if (statusFilter) {
          if (statusFilter === 'settled' && !successfulStatuses.includes(tr.status)) return false;
          if (statusFilter === 'pending' && !pendingStatuses.includes(tr.status)) return false;
          if (statusFilter === 'failed' && (successfulStatuses.includes(tr.status) || pendingStatuses.includes(tr.status))) return false;
        }
        return true;
      });
    }

    function renderPaymentsTable() {
      const tbody = $('paymentsBody');
      if (!tbody) return;
      const list = filteredTransactions();
      currentPayments = list;
      tbody.innerHTML = '';

      if (!list.length) {
        const row = document.createElement('tr');
        row.innerHTML = `<td class="fin-empty" colspan="10">No transactions found for the selected filters.</td>`;
        tbody.appendChild(row);
        return;
      }

      list.forEach((tr) => {
        const commission = tr.commission ?? Math.round((tr.gross || 0) * (tr.commission_pct || 0) / 100);
        const net = tr.net ?? (tr.gross - commission);
        const row = document.createElement('tr');
        row.className = 'fin-rowHover';
        row.innerHTML = `
          <td>${fmtDate(tr.date_iso)}<div class="fin-small">${tr.payment_id}</div></td>
          <td style="text-transform:capitalize">${(tr.type || 'other').replace('_',' ')}</td>
          <td>${tr.pet || '—'}</td>
          <td>${tr.owner || '—'}</td>
          <td>${tr.doctor || '—'}<div class="fin-small">${tr.clinic || ''}</div></td>
          <td style="font-weight:800">${fmtMoney(tr.gross, true)}</td>
          <td>${fmtMoney(commission, true)}</td>
          <td style="font-weight:800">${fmtMoney(net, true)}</td>
          <td><span class="fin-pill ${statusClass(tr.status)}">${tr.status_label || tr.status || '-'}</span></td>
          <td><button class="fin-btn fin-btn-ghost fin-detailBtn" data-id="${tr.id}" type="button">Details</button></td>
        `;
        tbody.appendChild(row);

        const exp = document.createElement('tr');
        exp.className = 'fin-expandRow';
        exp.style.display = 'none';
        exp.innerHTML = `
          <td colspan="10" class="fin-expandPanel">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
              <div style="flex:1;min-width:180px">
                <div style="font-weight:800">Payment Mode</div>
                <div class="fin-small">${tr.payment_mode || '—'}</div>
              </div>
              <div style="flex:1;min-width:180px">
                <div style="font-weight:800">Service</div>
                <div class="fin-small">${tr.service || '—'}</div>
              </div>
              <div style="flex:1;min-width:180px">
                <div style="font-weight:800">Order ID</div>
                <div class="fin-small">${tr.order_id || '—'}</div>
              </div>
              <div style="flex:1;min-width:180px">
                <div style="font-weight:800">Notes</div>
                <div class="fin-small">${tr.notes || '—'}</div>
              </div>
            </div>
          </td>`;
        tbody.appendChild(exp);
      });

      tbody.querySelectorAll('.fin-detailBtn').forEach((btn) => {
        btn.addEventListener('click', () => {
          const tr = btn.closest('tr');
          if (!tr) return;
          const next = tr.nextElementSibling;
          if (!next) return;
          const hidden = next.style.display === 'none' || next.style.display === '';
          next.style.display = hidden ? '' : 'none';
          if (hidden) tr.classList.add('fin-expanded'); else tr.classList.remove('fin-expanded');
        });
      });
    }

    function exportPaymentsCSV(list) {
      const rows = [['payment_id','order_id','date','type','pet','owner','doctor','clinic','gross','commission_pct','status','payment_mode','service']];
      list.forEach((t) => rows.push([
        t.payment_id, t.order_id || '', t.date_iso || '', t.type || '', t.pet || '', t.owner || '', t.doctor || '', t.clinic || '',
        t.gross || 0, t.commission_pct || 0, t.status || '', t.payment_mode || '', t.service || ''
      ]));
      const csv = rows.map(r => r.map(c => `"${String(c ?? '').replace(/"/g,'""')}"`).join(',')).join('\n');
      const blob = new Blob([csv], { type:'text/csv' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'payments.csv'; a.click();
      URL.revokeObjectURL(url);
    }

    // Orders (placeholder data hook)
    let orderDrawerOpen = false;
    function renderOrdersTable() {
      const tbody = $('ordersBodyCombined');
      if (!tbody) return;
      tbody.innerHTML = '';
      if (!orders.length) {
        const row = document.createElement('tr');
        row.innerHTML = `<td class="fin-empty" colspan="10">Orders view is coming soon.</td>`;
        tbody.appendChild(row);
        return;
      }
    }

    function openOrderDrawer() {
      const drawer = $('orderDrawer');
      if (!drawer) return;
      drawer.classList.add('fin-open');
      drawer.setAttribute('aria-hidden','false');
      orderDrawerOpen = true;
    }
    function closeOrderDrawer() {
      const drawer = $('orderDrawer');
      if (!drawer) return;
      drawer.classList.remove('fin-open');
      drawer.setAttribute('aria-hidden','true');
      orderDrawerOpen = false;
    }

    // Wire UI
    renderKPIs();
    renderCharts();
    renderPaymentsTable();
    renderOrdersTable();

    if ($('lastPayout')) $('lastPayout').textContent = lastPayout || '—';
    if ($('nextPayout')) $('nextPayout').textContent = nextPayout || '—';
    if ($('pendingPayouts')) $('pendingPayouts').textContent = fmtMoney(pendingPayouts, true);

    ['paySearch','payFrom','payTo'].forEach((id) => {
      const el = $(id);
      if (el) el.addEventListener('input', renderPaymentsTable);
    });
    ['payType','payStatus'].forEach((id) => {
      const el = $(id);
      if (el) el.addEventListener('change', renderPaymentsTable);
    });

    $('exportPayments')?.addEventListener('click', () => exportPaymentsCSV(currentPayments));
    $('downloadStatement')?.addEventListener('click', () => alert('Download statement — integrate backend export.'));
    $('downloadPayout')?.addEventListener('click', () => alert('Download payout report — integrate backend.'));
    $('viewPayoutHistory')?.addEventListener('click', () => alert('Payout history coming soon.'));
    $('exportAll')?.addEventListener('click', () => {
      exportPaymentsCSV(transactions);
      alert('Exported payments.csv (orders export pending).');
    });

    $('tabPayments')?.addEventListener('click', () => {
      $('tabPayments').classList.add('fin-active');
      $('tabOrders').classList.remove('fin-active');
      $('paymentsPanel').style.display = 'block';
      $('ordersPanel').style.display = 'none';
    });
    $('tabOrders')?.addEventListener('click', () => {
      $('tabOrders').classList.add('fin-active');
      $('tabPayments').classList.remove('fin-active');
      $('paymentsPanel').style.display = 'none';
      $('ordersPanel').style.display = 'block';
    });

    $('orderSelectAll')?.addEventListener('change', (e) => {
      const checked = e.target.checked;
      document.querySelectorAll('.orderCheckbox').forEach((cb) => (cb.checked = checked));
    });
    $('bulkFulfill')?.addEventListener('click', () => alert('Select orders and mark fulfilled — hook API when ready.'));
    $('exportOrders')?.addEventListener('click', () => alert('Orders export will be wired when data is available.'));
    $('closeDrawer')?.addEventListener('click', closeOrderDrawer);
    $('downloadInvoice')?.addEventListener('click', () => alert('Download invoice — integrate backend PDF.'));
    $('markFulfilled')?.addEventListener('click', () => alert('Mark as fulfilled — integrate backend update.'));

    // Close drawer on escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && orderDrawerOpen) closeOrderDrawer();
    });
  });
</script>
@endsection
