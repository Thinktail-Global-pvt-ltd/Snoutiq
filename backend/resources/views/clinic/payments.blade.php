{{-- resources/views/clinic/payments.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Clinic Payments')
@section('page_title','Clinic Payments')

@section('content')
@php
  $transactions = $transactions ?? collect();
  $clinicName = $vet->name ?? ('Clinic #'.($vetId ?? ''));
  $count      = $transactions->count();
  $totalPaise = (int) $transactions->sum('amount_paise');
  $totalInr   = $totalPaise / 100;
  $lastAt     = optional($transactions->first())->created_at;

@endphp

<div class="max-w-6xl mx-auto space-y-6">
  <div class="bg-white rounded-xl shadow p-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <div class="text-xs text-gray-500">Clinic</div>
        <div class="font-semibold">{{ $clinicName }}</div>
        <div class="text-xs text-gray-500">Slug: {{ $slug ?: '-' }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Payments</div>
        <div class="font-semibold">{{ number_format($count) }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Collected</div>
        <div class="font-semibold">₹{{ number_format($totalInr, 2) }}</div>
      </div>
      <div>
        <div class="text-xs text-gray-500">Last Payment</div>
        <div class="font-semibold">{{ $lastAt ? $lastAt->timezone('Asia/Kolkata')->format('d M Y, h:i A') : '-' }}</div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-4 border-b flex items-center justify-between">
      <div class="font-semibold">Recent Payments</div>
      <div class="text-sm text-gray-500">Showing up to 300 latest</div>
    </div>
    <div class="overflow-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-700">
          <tr class="text-left">
            <th class="px-4 py-2">Date</th>
            <th class="px-4 py-2">Payment ID</th>
            <th class="px-4 py-2">Order ID</th>
            <th class="px-4 py-2">Amount</th>
            <th class="px-4 py-2">Status</th>
            <th class="px-4 py-2">Method</th>
            <th class="px-4 py-2">Doctor / Clinic</th>
            <th class="px-4 py-2">User</th>
            <th class="px-4 py-2">Pet Details</th>
            <th class="px-4 py-2">Service</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($transactions as $txn)
            @php
              $paymentId = $txn->reference ?? ('TXN#'.$txn->id);
              $orderId = data_get($txn->metadata, 'order_id')
                ?? data_get($txn->metadata, 'razorpay_order_id')
                ?? '-';
              $amount = number_format(((int) ($txn->amount_paise ?? 0)) / 100, 2);
              $statusKey = strtolower((string) ($txn->status ?? ''));
              $successfulStatuses = ['captured','authorized','verified','completed','paid','success','successful','settled'];
              $isSuccess = in_array($statusKey, $successfulStatuses, true);
              $methodLabel = strtoupper($txn->payment_method ?? $txn->type ?? '-');
              $doctor = $txn->doctor;
              $doctorName = $doctor
                ? ($doctor->doctor_name ?? $doctor->name ?? 'Doctor #'.$doctor->id)
                : '-';
              $clinicLabel = $doctor?->clinic?->name ?? '-';
              $user = $txn->user;
              $serviceLabel = data_get($txn->metadata, 'service_name')
                ?? data_get($txn->metadata, 'service')
                ?? $txn->type
                ?? '-';
              $petCollection = $user ? ($user->pets ?? collect()) : collect();
              $petEntries = $petCollection;
            @endphp
            <tr>
              <td class="px-4 py-2">{{ optional($txn->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
              <td class="px-4 py-2 font-mono">{{ $paymentId }}</td>
              <td class="px-4 py-2 font-mono">{{ $orderId }}</td>
              <td class="px-4 py-2">₹{{ $amount }}</td>
              <td class="px-4 py-2">
                <span class="px-2 py-0.5 rounded-full text-xs {{ $isSuccess ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700' }}">{{ strtoupper($txn->status ?? '-') }}</span>
              </td>
              <td class="px-4 py-2">{{ $methodLabel }}</td>
              <td class="px-4 py-2">
                <div class="font-semibold">{{ $doctorName }}</div>
                <div class="text-xs text-gray-500">{{ $clinicLabel }}</div>
              </td>
              @php
                $userName = $user?->name ?? data_get($txn->metadata, 'user_name') ?? '-';
                $summaryText = $user?->summary ?? data_get($txn->metadata, 'user_summary');
              @endphp
              <td class="px-4 py-2 space-y-1">
                <div class="font-semibold">{{ $userName }}</div>
                <button
                  type="button"
                  data-summary="{{ e($summaryText ?? 'Summary not available') }}"
                  class="text-xs text-indigo-600 hover:text-indigo-700 focus:outline-none focus:underline"
                >
                  View Summary
                </button>
              </td>
              <td class="px-4 py-2 space-y-2">
                @if($petEntries->isEmpty())
                  <div class="text-xs text-gray-500">No pet data</div>
                @else
                  @foreach($petEntries as $pet)
                    @php
                      $petName = $pet->name ?: ('Pet #'.$pet->id);
                    @endphp
                    <div class="border border-gray-100 rounded-lg p-2 bg-gray-50 space-y-1">
                      <div class="font-semibold">{{ $petName }}</div>
                    </div>
                  @endforeach
                @endif
              </td>
              <td class="px-4 py-2">{{ $serviceLabel }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="px-6 py-8 text-center text-gray-500">No payments found for this clinic yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
<div
  id="summary-modal"
  class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
  aria-hidden="true"
>
  <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 p-6 relative">
    <button
      type="button"
      class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 focus:outline-none"
      data-summary-close
    >
      ×
    </button>
    <h3 class="text-lg font-semibold mb-4">Patient Summary</h3>
    <p id="summary-modal-text" class="text-sm text-gray-700 whitespace-pre-line"></p>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('summary-modal');
    if (!modal) {
      return;
    }
    var textEl = document.getElementById('summary-modal-text');
    var closeBtn = modal.querySelector('[data-summary-close]');
    var openButtons = document.querySelectorAll('button[data-summary]');

    function closeModal() {
      modal.classList.add('hidden');
      modal.setAttribute('aria-hidden', 'true');
      textEl.textContent = '';
    }

    function openModal(summary) {
      textEl.textContent = summary || 'Summary not available';
      modal.classList.remove('hidden');
      modal.setAttribute('aria-hidden', 'false');
    }

    openButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        openModal(btn.dataset.summary);
      });
    });

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });
  });
</script>
@endsection
