@extends('layouts.snoutiq-dashboard')

@section('title', 'Clinic Dashboard')
@section('page_title', 'Clinic Dashboard')

@section('head')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <style>
    .clinic-dashboard *,
    .clinic-dashboard *::before,
    .clinic-dashboard *::after {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    .clinic-dashboard {
      background: #f5f7fa;
      color: #2d3748;
      line-height: 1.6;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }

    .clinic-dashboard .dashboard {
      max-width: 1600px;
      margin: 0 auto;
      padding: 20px;
    }

    /* Header */
    .clinic-dashboard .header {
      background: white;
      padding: 24px 32px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }

    .clinic-dashboard .header-left h1 {
      font-size: 28px;
      color: #1a202c;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .clinic-dashboard .header-left .date {
      color: #718096;
      font-size: 14px;
    }

    .clinic-dashboard .header-icons {
      display: flex;
      gap: 16px;
    }

    .clinic-dashboard .icon-btn {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      background: #f7fafc;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
      position: relative;
      text-decoration: none;
      color: inherit;
    }

    .clinic-dashboard .icon-btn:hover {
      background: #edf2f7;
    }

    .clinic-dashboard .icon-btn.notification::after {
      content: '•';
      position: absolute;
      top: -4px;
      right: -4px;
      background: #f56565;
      color: white;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      font-size: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
    }

    /* Alerts */
    .clinic-dashboard .alerts {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .clinic-dashboard .alert {
      padding: 16px 20px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }

    .clinic-dashboard .alert.warning {
      background: #fff8e1;
      border-left: 4px solid #ffa000;
    }

    .clinic-dashboard .alert.danger {
      background: #ffebee;
      border-left: 4px solid #e53e3e;
    }

    .clinic-dashboard .alert.info {
      background: #e3f2fd;
      border-left: 4px solid #3182ce;
    }

    .clinic-dashboard .alert-icon {
      font-size: 24px;
    }

    .clinic-dashboard .alert-content h4 {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 2px;
    }

    .clinic-dashboard .alert-content p {
      font-size: 13px;
      color: #4a5568;
    }

    /* Quick Actions */
    .clinic-dashboard .quick-actions {
      background: white;
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      margin-bottom: 24px;
    }

    .clinic-dashboard .quick-actions h3 {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 16px;
      color: #1a202c;
    }

    .clinic-dashboard .actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
    }

    .clinic-dashboard .action-btn {
      padding: 20px;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      background: white;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 15px;
      font-weight: 600;
      color: #2d3748;
      text-decoration: none;
    }

    .clinic-dashboard .action-btn:hover {
      border-color: #3182ce;
      background: #f7fafc;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(49, 130, 206, 0.15);
    }

    .clinic-dashboard .action-btn .icon {
      font-size: 24px;
    }

    /* Services Overview */
    .clinic-dashboard .services {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
      gap: 20px;
      margin-bottom: 24px;
    }

    .clinic-dashboard .service-card {
      background: white;
      padding: 28px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .clinic-dashboard .service-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
    }

    .clinic-dashboard .service-icon {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }

    .clinic-dashboard .service-card.telemedicine .service-icon {
      background: #e0f2fe;
      color: #0284c7;
    }

    .clinic-dashboard .service-card.appointments .service-icon {
      background: #dbeafe;
      color: #2563eb;
    }

    .clinic-dashboard .service-card.walkins .service-icon {
      background: #e0e7ff;
      color: #6366f1;
    }

    .clinic-dashboard .service-header h3 {
      font-size: 18px;
      font-weight: 700;
      color: #1a202c;
    }

    .clinic-dashboard .metrics {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
      margin-bottom: 16px;
    }

    .clinic-dashboard .metric {
      padding: 12px;
      background: #f7fafc;
      border-radius: 8px;
    }

    .clinic-dashboard .metric-label {
      font-size: 13px;
      color: #718096;
      margin-bottom: 4px;
    }

    .clinic-dashboard .metric-value {
      font-size: 24px;
      font-weight: 700;
      color: #1a202c;
    }

    .clinic-dashboard .funnel {
      padding: 12px;
      background: #f7fafc;
      border-radius: 8px;
    }

    .clinic-dashboard .funnel-label {
      font-size: 13px;
      color: #718096;
      margin-bottom: 8px;
    }

    .clinic-dashboard .funnel-bar {
      display: flex;
      height: 28px;
      border-radius: 6px;
      overflow: hidden;
      background: #e2e8f0;
    }

    .clinic-dashboard .funnel-segment {
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 600;
      color: white;
    }

    .clinic-dashboard .funnel-segment.inquiry {
      background: #60a5fa;
    }

    .clinic-dashboard .funnel-segment.scheduled {
      background: #34d399;
    }

    .clinic-dashboard .funnel-segment.paid {
      background: #10b981;
    }

    /* KPI Cards */
    .clinic-dashboard .kpis {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }

    .clinic-dashboard .kpi-card {
      background: white;
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
      border-top: 4px solid #3182ce;
    }

    .clinic-dashboard .kpi-label {
      font-size: 13px;
      color: #718096;
      margin-bottom: 8px;
      font-weight: 500;
    }

    .clinic-dashboard .kpi-value {
      font-size: 32px;
      font-weight: 700;
      color: #1a202c;
      margin-bottom: 4px;
    }

    .clinic-dashboard .kpi-change {
      font-size: 13px;
      color: #48bb78;
    }

    .clinic-dashboard .snapshot {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 12px;
      margin-bottom: 20px;
    }

    .clinic-dashboard .snapshot-card {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 14px 16px;
      display: flex;
      flex-direction: column;
      gap: 6px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .clinic-dashboard .snapshot-label {
      font-size: 13px;
      color: #6b7280;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .clinic-dashboard .snapshot-value {
      font-size: 22px;
      font-weight: 700;
      color: #111827;
    }

    .clinic-dashboard .pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
    }

    .clinic-dashboard .pill.green {
      background: #ecfdf3;
      color: #15803d;
      border: 1px solid #bbf7d0;
    }

    .clinic-dashboard .pill.red {
      background: #fef2f2;
      color: #b91c1c;
      border: 1px solid #fecaca;
    }

    .clinic-dashboard .pill.gray {
      background: #f3f4f6;
      color: #374151;
      border: 1px solid #e5e7eb;
    }

    /* Queue Panel */
    .clinic-dashboard .queue-panel {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 20px;
      margin-bottom: 24px;
    }

    .clinic-dashboard .panel {
      background: white;
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .clinic-dashboard .panel h3 {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 16px;
      color: #1a202c;
    }

    .clinic-dashboard table {
      width: 100%;
      border-collapse: collapse;
    }

    .clinic-dashboard thead {
      background: #f7fafc;
    }

    .clinic-dashboard th {
      padding: 12px;
      text-align: left;
      font-size: 13px;
      font-weight: 600;
      color: #4a5568;
      border-bottom: 2px solid #e2e8f0;
    }

    .clinic-dashboard td {
      padding: 12px;
      font-size: 14px;
      border-bottom: 1px solid #e2e8f0;
    }

    .clinic-dashboard .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }

    .clinic-dashboard .status-badge.confirmed {
      background: #d1fae5;
      color: #065f46;
    }

    .clinic-dashboard .status-badge.waiting {
      background: #fef3c7;
      color: #92400e;
    }

    .clinic-dashboard .status-badge.completed {
      background: #dbeafe;
      color: #1e40af;
    }

    .clinic-dashboard .status-badge.ongoing {
      background: #fce7f3;
      color: #9f1239;
    }

    .clinic-dashboard .action-link {
      color: #3182ce;
      text-decoration: none;
      font-weight: 500;
      font-size: 13px;
    }

    .clinic-dashboard .action-link:hover {
      text-decoration: underline;
    }

    .clinic-dashboard .pipeline-card {
      padding: 16px;
      background: #f7fafc;
      border-radius: 8px;
      margin-bottom: 12px;
    }

    .clinic-dashboard .pipeline-label {
      font-size: 13px;
      color: #718096;
      margin-bottom: 4px;
    }

    .clinic-dashboard .pipeline-value {
      font-size: 24px;
      font-weight: 700;
      color: #1a202c;
    }

    /* Charts */
    .clinic-dashboard .charts {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
      gap: 20px;
      margin-bottom: 24px;
    }

    .clinic-dashboard .chart-card {
      background: white;
      padding: 24px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .clinic-dashboard .chart-card h3 {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 20px;
      color: #1a202c;
    }

    .clinic-dashboard .chart-container {
      position: relative;
      height: 300px;
    }

    /* Footer */
    .clinic-dashboard .footer {
      text-align: center;
      padding: 20px;
      color: #718096;
      font-size: 14px;
    }

    @media (max-width: 768px) {
      .clinic-dashboard .queue-panel {
        grid-template-columns: 1fr;
      }

      .clinic-dashboard .charts {
        grid-template-columns: 1fr;
      }
    }
  </style>
@endsection

@section('content')
  @php
    $clinicName = trim($clinic->clinic_profile ?? $clinic->name ?? 'Your Clinic');
    $displayDate = $today ? $today->timezone('Asia/Kolkata')->translatedFormat('l d F Y') : now('Asia/Kolkata')->translatedFormat('l d F Y');
    $tele = $stats['telemedicine'] ?? [];
    $appointments = $stats['appointments'] ?? [];
    $walkins = $stats['walkins'] ?? [];
    $funnel = $tele['funnel'] ?? [];
    $cohortChart = $charts['cohort'] ?? ['labels' => [], 'new' => [], 'returning' => []];
    $revenueChart = $charts['revenue'] ?? ['labels' => [], 'series' => []];
    $queue = $queue ?? collect();
    $snapshot = $snapshot ?? [];
    $statusPill = function ($status) {
      $map = [
        'completed' => 'completed',
        'done' => 'completed',
        'finished' => 'completed',
        'resolved' => 'completed',
        'closed' => 'completed',
        'success' => 'completed',
        'successful' => 'completed',
        'ongoing' => 'ongoing',
        'in_progress' => 'ongoing',
        'accepted' => 'ongoing',
        'started' => 'ongoing',
        'live' => 'ongoing',
        'waiting' => 'waiting',
        'scheduled' => 'confirmed',
        'confirmed' => 'confirmed',
      ];
      $key = strtolower((string) $status);
      return $map[$key] ?? 'confirmed';
    };
  @endphp
  <div class="clinic-dashboard">
    <div class="dashboard">
      <div class="header">
        <div class="header-left">
          <h1>{{ $clinicName }}</h1>
          <div class="date">{{ $displayDate }}</div>
        </div>
        <div class="header-icons">
          <a class="icon-btn notification" href="{{ route('dashboard.profile') }}" title="Notifications">🔔</a>
          <a class="icon-btn" href="{{ route('dashboard.profile') }}" title="Profile settings">⚙️</a>
        </div>
      </div>

      <div class="alerts">
        @foreach(($alerts ?? []) as $alert)
          @php $tone = $alert['tone'] ?? 'info'; @endphp
          <div class="alert {{ $tone }}">
            <div class="alert-icon">{{ $alert['icon'] ?? 'ℹ️' }}</div>
            <div class="alert-content">
              <h4>{{ $alert['title'] ?? 'Update' }}</h4>
              <p>{{ $alert['description'] ?? '' }}</p>
            </div>
          </div>
        @endforeach
      </div>

      <div class="snapshot">
        <div class="snapshot-card">
          <div class="snapshot-label">Services</div>
          <div class="snapshot-value">{{ number_format($snapshot['services'] ?? 0) }}</div>
        </div>
        <div class="snapshot-card">
          <div class="snapshot-label">Staff</div>
          <div class="snapshot-value">{{ number_format($snapshot['staff'] ?? 0) }}</div>
        </div>
        <div class="snapshot-card">
          <div class="snapshot-label">Bookings Today</div>
          <div class="snapshot-value">{{ number_format($snapshot['bookings_today'] ?? 0) }}</div>
        </div>
        <div class="snapshot-card">
          <div class="snapshot-label">Video Slots</div>
          <div class="snapshot-value">{{ number_format($snapshot['video_slots'] ?? 0) }}</div>
        </div>
        <div class="snapshot-card">
          <div class="snapshot-label">Emergency Profiles</div>
          <div class="snapshot-value">{{ number_format($snapshot['emergency_profiles'] ?? 0) }}</div>
        </div>
        <div class="snapshot-card">
          <div class="snapshot-label">Payments Pending</div>
          <div class="snapshot-value">{{ number_format($snapshot['pending_payments'] ?? 0) }}</div>
          <span class="pill {{ ($snapshot['pending_payments'] ?? 0) > 0 ? 'red' : 'green' }}">
            {{ ($snapshot['pending_payments'] ?? 0) > 0 ? 'Attention' : 'Clear' }}
          </span>
        </div>
        <div class="snapshot-card">
          <div class="snapshot-label">Unpaid Amount</div>
          <div class="snapshot-value">₹{{ number_format($snapshot['unpaid_amount'] ?? 0) }}</div>
        </div>
        <div class="snapshot-card">
          <div class="snapshot-label">Documents & Compliance</div>
          <div class="snapshot-value">
            <span class="pill {{ ($snapshot['documents_ok'] ?? false) ? 'green' : 'red' }}">
              {{ ($snapshot['documents_ok'] ?? false) ? 'Complete' : 'Pending' }}
            </span>
          </div>
        </div>
      </div>

      <div class="services">
        <div class="service-card telemedicine">
          <div class="service-header">
            <div class="service-icon">🎥</div>
            <h3>Telemedicine</h3>
          </div>
          <div class="metrics">
            <div class="metric">
              <div class="metric-label">Scheduled Today</div>
              <div class="metric-value">{{ number_format($tele['scheduled'] ?? 0) }}</div>
            </div>
            <div class="metric">
              <div class="metric-label">Ongoing</div>
              <div class="metric-value">{{ number_format($tele['ongoing'] ?? 0) }}</div>
            </div>
            <div class="metric">
              <div class="metric-label">Completed Today</div>
              <div class="metric-value">{{ number_format($tele['completed'] ?? 0) }}</div>
            </div>
          </div>
          <div class="funnel">
            <div class="funnel-label">Conversion Funnel</div>
            @php
              $inquiryPct = max(5, min(100, $funnel['inquiry_pct'] ?? 0));
              $scheduledPct = max(5, min(100, $funnel['scheduled_pct'] ?? 0));
              $paidPct = max(5, min(100, $funnel['paid_pct'] ?? 0));
            @endphp
            <div class="funnel-bar">
              <div class="funnel-segment inquiry" style="width: {{ $inquiryPct }}%;">{{ $funnel['inquiry'] ?? 0 }} Inquiry</div>
              <div class="funnel-segment scheduled" style="width: {{ $scheduledPct }}%;">{{ $funnel['scheduled'] ?? 0 }} Sched</div>
              <div class="funnel-segment paid" style="width: {{ $paidPct }}%;">{{ $funnel['paid'] ?? 0 }} Paid</div>
            </div>
          </div>
        </div>

        <div class="service-card appointments">
          <div class="service-header">
            <div class="service-icon">📅</div>
            <h3>Appointment Booking</h3>
          </div>
          <div class="metrics">
            <div class="metric">
              <div class="metric-label">Total Today</div>
              <div class="metric-value">{{ number_format($appointments['total'] ?? 0) }}</div>
            </div>
            <div class="metric">
              <div class="metric-label">No-shows</div>
              <div class="metric-value">{{ number_format($appointments['no_shows'] ?? 0) }}</div>
            </div>
            <div class="metric">
              <div class="metric-label">Upcoming</div>
              <div class="metric-value">{{ number_format($appointments['upcoming'] ?? 0) }}</div>
            </div>
            <div class="metric">
              <div class="metric-label">Completed</div>
              <div class="metric-value">{{ number_format($appointments['completed'] ?? 0) }}</div>
            </div>
          </div>
        </div>

        <div class="service-card walkins">
          <div class="service-header">
            <div class="service-icon">🚶</div>
            <h3>Walk-ins</h3>
          </div>
          <div class="metrics">
            <div class="metric">
              <div class="metric-label">New Walk-ins Today</div>
              <div class="metric-value">{{ number_format($walkins['today'] ?? 0) }}</div>
            </div>
            <div class="metric">
              <div class="metric-label">Avg Wait Time</div>
              <div class="metric-value">{{ number_format($walkins['avg_wait'] ?? 0) }}m</div>
            </div>
            <div class="metric">
              <div class="metric-label">Repeat Walk-ins</div>
              <div class="metric-value">{{ number_format($walkins['repeat'] ?? 0) }}</div>
            </div>
            <div class="metric">
              <div class="metric-label">Total Served</div>
              <div class="metric-value">{{ number_format($walkins['served'] ?? 0) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="kpis">
        <div class="kpi-card">
          <div class="kpi-label">Locked-in Pet Parents</div>
          <div class="kpi-value">{{ number_format(($appointments['total'] ?? 0) + ($tele['scheduled'] ?? 0)) }}</div>
          <div class="kpi-change">Active today</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Repeat Customers (Weekly)</div>
          <div class="kpi-value">{{ $repeatRate !== null ? $repeatRate.'%' : '—' }}</div>
          <div class="kpi-change">vs. last 7 days</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Telemedicine Revenue (7d)</div>
          <div class="kpi-value">₹{{ number_format($revenueTotal ?? 0) }}</div>
          <div class="kpi-change text-rose-600">Unpaid: ₹{{ number_format($unpaidAmount ?? 0) }}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Clinic Utilization Today</div>
          @php
            $utilDen = max(1, ($appointments['total'] ?? 0) + ($walkins['served'] ?? 0));
            $utilPct = min(100, round((($tele['scheduled'] ?? 0) + ($appointments['completed'] ?? 0)) / $utilDen * 100));
          @endphp
          <div class="kpi-value">{{ $utilPct }}%</div>
          <div class="kpi-change">Calls + visits completed</div>
        </div>
      </div>

      <div class="queue-panel">
        <div class="panel">
          <h3>Today's Appointment Queue</h3>
          <table>
            <thead>
              <tr>
                <th>Time</th>
                <th>Customer</th>
                <th>Doctor</th>
                <th>Service</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($queue as $item)
                @php $pill = $statusPill($item['status'] ?? ''); @endphp
                <tr>
                  <td>{{ $item['time'] }}</td>
                  <td>#{{ $item['customer_id'] ?? '—' }}</td>
                  <td>{{ $item['doctor'] ?? 'Any' }}</td>
                  <td>{{ $item['service'] ?? 'Appointment' }}</td>
                  <td><span class="status-badge {{ $pill }}">{{ ucfirst($item['status'] ?? 'scheduled') }}</span></td>
                  <td><a href="{{ route('doctor.bookings') }}" class="action-link">View</a></td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-sm text-gray-600">No bookings for today yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="panel">
          <h3>Live Telemedicine Pipeline</h3>
          <div class="pipeline-card">
            <div class="pipeline-label">Upcoming Consultations</div>
            @php $upcomingCalls = max(($tele['scheduled'] ?? 0) - ($tele['completed'] ?? 0) - ($tele['ongoing'] ?? 0), 0); @endphp
            <div class="pipeline-value">{{ number_format($upcomingCalls) }}</div>
          </div>
          <div class="pipeline-card">
            <div class="pipeline-label">In-Call Now</div>
            <div class="pipeline-value">{{ number_format($tele['ongoing'] ?? 0) }}</div>
          </div>
          <div class="pipeline-card">
            <div class="pipeline-label">Completed Today</div>
            <div class="pipeline-value">{{ number_format($tele['completed'] ?? 0) }}</div>
          </div>
          <div class="pipeline-card">
            <div class="pipeline-label">Avg Call Duration</div>
            <div class="pipeline-value">—</div>
          </div>
        </div>
      </div>

      <div class="charts">
        <div class="chart-card">
          <h3>Customer Cohort Analysis (Last 7 Days)</h3>
          <div class="chart-container">
            <canvas id="cohortChart"></canvas>
          </div>
        </div>

        <div class="chart-card">
          <h3>Clinic Revenue &amp; Utilization (7 Days)</h3>
          <div class="chart-container">
            <canvas id="revenueChart"></canvas>
          </div>
        </div>
      </div>

      <div class="footer">
        SnoutIQ Clinic Dashboard · Live metrics
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (window.Chart) {
        const cohortData = @json($cohortChart);
        const revenueData = @json($revenueChart);

        const cohortCtx = document.getElementById('cohortChart');
        if (cohortCtx && cohortData?.labels?.length) {
          new Chart(cohortCtx.getContext('2d'), {
            type: 'bar',
            data: {
              labels: cohortData.labels,
              datasets: [
                {
                  label: 'Returning Pet Parents',
                  data: cohortData.returning || [],
                  backgroundColor: '#3b82f6',
                  borderRadius: 6,
                },
                {
                  label: 'New Pet Parents',
                  data: cohortData.new || [],
                  backgroundColor: '#10b981',
                  borderRadius: 6,
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { legend: { display: true, position: 'bottom' } },
              scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } },
              },
            },
          });
        }

        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx && revenueData?.labels?.length) {
          new Chart(revenueCtx.getContext('2d'), {
            type: 'line',
            data: {
              labels: revenueData.labels,
              datasets: [
                {
                  label: 'Daily Revenue (₹)',
                  data: revenueData.series || [],
                  borderColor: '#3b82f6',
                  backgroundColor: 'rgba(59, 130, 246, 0.1)',
                  tension: 0.4,
                  fill: true,
                  pointRadius: 5,
                  pointBackgroundColor: '#3b82f6',
                  pointBorderColor: '#fff',
                  pointBorderWidth: 2,
                },
              ],
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: { legend: { display: true, position: 'bottom' } },
              scales: {
                x: { grid: { display: false } },
                y: {
                  beginAtZero: true,
                  ticks: {
                    callback: function (value) {
                      try { return '₹' + Number(value).toLocaleString(); } catch (e) { return value; }
                    },
                  },
                },
              },
            },
          });
        }
      }
    });
  </script>
@endsection
