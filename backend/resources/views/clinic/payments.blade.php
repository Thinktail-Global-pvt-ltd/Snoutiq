{{-- resources/views/clinic/payments.blade.php --}}
@extends('layouts.snoutiq-dashboard')

@section('title','Clinic Payments')
@section('page_title','Clinic Payments')

@section('content')
@php
  $clinicName = $vet->name ?? ('Clinic #'.($vetId ?? ''));
  $count      = $payments->count();
  $totalPaise = (int) $payments->sum(function($p){ return (int) ($p->amount ?? 0); });
  $totalInr   = $totalPaise / 100;
  $lastAt     = optional($payments->first())->created_at;
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
            <th class="px-4 py-2">Contact</th>
            <th class="px-4 py-2">Email</th>
            <th class="px-4 py-2">Service</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($payments as $p)
            @php
              $inr = number_format(((int) ($p->amount ?? 0))/100, 2);
              $svc = is_array($p->notes) ? ($p->notes['service_id'] ?? null) : null;
            @endphp
            <tr>
              <td class="px-4 py-2">{{ optional($p->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
              <td class="px-4 py-2 font-mono">{{ $p->razorpay_payment_id }}</td>
              <td class="px-4 py-2 font-mono">{{ $p->razorpay_order_id }}</td>
              <td class="px-4 py-2">₹{{ $inr }}</td>
              <td class="px-4 py-2">
                <span class="px-2 py-0.5 rounded-full text-xs {{ ($p->status==='captured' || $p->status==='authorized' || $p->status==='verified') ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700' }}">{{ strtoupper($p->status ?? '-') }}</span>
              </td>
              <td class="px-4 py-2">{{ strtoupper($p->method ?? '-') }}</td>
              <td class="px-4 py-2">{{ $p->contact ?? '-' }}</td>
              <td class="px-4 py-2">{{ $p->email ?? '-' }}</td>
              <td class="px-4 py-2">{{ $svc ? '#'.$svc : '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="px-6 py-8 text-center text-gray-500">No payments found for this clinic yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

