<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClinicFinancialsController extends Controller
{
    /**
     * Return Financials data (KPIs, charts, and transactions) for a clinic/vet.
     */
    public function show(Request $request)
    {
        $clinicId = $request->input('clinic_id');
        $vetId    = $request->input('vet_id') ?: $clinicId; // allow vet_id or fallback to clinic_id

        if ((!$clinicId && !$vetId) || (isset($clinicId) && (!is_numeric($clinicId) || (int) $clinicId <= 0))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing clinic_id/vet_id',
            ], 422);
        }

        $clinicId = $clinicId ? (int) $clinicId : null;
        $vetId    = $vetId ? (int) $vetId : null;

        $fromDate = $this->parseDate($request->input('from'));
        $toDate   = $this->parseDate($request->input('to'));
        $status   = $this->cleanLower($request->input('status'));
        $type     = $this->cleanLower($request->input('type'));
        $search   = $this->cleanLower($request->input('search'));

        $successfulStatuses = ['captured','authorized','verified','completed','paid','success','successful','settled'];
        $pendingStatuses    = ['created','pending','processing','initiated','in_progress'];

        // Base query - fetch recent transactions for this clinic/vet
        $query = Transaction::query()
            ->with(['doctor.clinic', 'user.pets'])
            ->orderByDesc('created_at')
            ->limit(600);

        if ($clinicId || $vetId) {
            $query->where(function ($q) use ($clinicId, $vetId) {
                if ($clinicId) {
                    $q->orWhere('clinic_id', $clinicId);
                }
                if ($vetId) {
                    $q->orWhereHas('doctor', function ($qq) use ($vetId) {
                        $qq->where('vet_registeration_id', $vetId);
                    });
                }
            });
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate->toDateString());
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate->toDateString());
        }

        $transactions = $query->get();

        // Transform rows for API consumers
        $normalized = $transactions->map(function (Transaction $txn) {
            $createdAt   = optional($txn->created_at)->timezone('Asia/Kolkata');
            $type        = $this->normalizeType($txn);
            $gross       = ((int) ($txn->amount_paise ?? 0)) / 100;
            $commissionPct = $this->numeric(data_get($txn->metadata, 'commission_pct') ?? data_get($txn->metadata, 'commission_percent'));
            $commission  = round($gross * ($commissionPct / 100), 2);
            $net         = round($gross - $commission, 2);

            $user   = $txn->user;
            $pet    = data_get($user, 'pets.0.name') ?? data_get($txn->metadata, 'pet_name') ?? '-';
            $doctor = $txn->doctor;
            $doctorName = $doctor?->doctor_name ?? $doctor?->name ?? '-';
            $clinicName = data_get($doctor, 'clinic.name') ?? data_get($txn->metadata, 'clinic_name') ?? '-';

            return [
                'id'             => $txn->id,
                'payment_id'     => $txn->reference ?? ('TXN#' . $txn->id),
                'order_id'       => data_get($txn->metadata, 'order_id') ?? data_get($txn->metadata, 'razorpay_order_id'),
                'date_iso'       => $createdAt?->toIso8601String(),
                'type'           => $type,
                'pet'            => $pet,
                'owner'          => $user?->name ?? data_get($txn->metadata, 'user_name') ?? '-',
                'doctor'         => $doctorName,
                'clinic'         => $clinicName,
                'gross'          => $gross,
                'commission_pct' => $commissionPct,
                'commission'     => $commission,
                'net'            => $net,
                'status'         => strtolower((string) ($txn->status ?? '')),
                'status_label'   => strtoupper($txn->status ?? '-'),
                'payment_mode'   => strtoupper($txn->payment_method ?? $txn->type ?? '-'),
                'notes'          => data_get($txn->metadata, 'notes'),
                'service'        => data_get($txn->metadata, 'service_name')
                    ?? data_get($txn->metadata, 'service')
                    ?? $txn->type
                    ?? '-',
            ];
        });

        // Apply in-memory filters for lightweight searches/labels
        $filtered = $normalized->filter(function (array $txn) use ($status, $type, $search) {
            if ($status && $txn['status'] !== $status) {
                return false;
            }
            if ($type && $txn['type'] !== $type) {
                return false;
            }

            if ($search) {
                $haystack = $this->cleanLower(
                    implode(' ', [
                        $txn['payment_id'],
                        $txn['order_id'],
                        $txn['pet'],
                        $txn['owner'],
                        $txn['doctor'],
                        $txn['clinic'],
                    ])
                );
                if (!Str::contains($haystack, $search)) {
                    return false;
                }
            }

            return true;
        })->values();

        $now           = Carbon::now('Asia/Kolkata');
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $recent        = $filtered->filter(function ($txn) use ($thirtyDaysAgo) {
            return $txn['date_iso'] && Carbon::parse($txn['date_iso'])->gte($thirtyDaysAgo);
        });

        $kpi = [
            'total'    => round($recent->sum('gross'), 2),
            'telemed'  => round($recent->where('type', 'telemed')->sum('gross'), 2),
            'inclinic' => round($recent->where('type', 'inclinic')->sum('gross'), 2),
            'orders'   => round($recent->where('type', 'order')->sum('gross'), 2),
        ];

        $lineLabels = [];
        $lineValues = [];
        $lineBase   = $now->copy()->startOfDay();
        for ($i = 9; $i >= 0; $i--) {
            $day = $lineBase->copy()->subDays($i);
            $lineLabels[] = $day->format('M j');
            $lineValues[] = round(
                $filtered
                    ->filter(fn ($txn) => $txn['date_iso'] && Carbon::parse($txn['date_iso'])->isSameDay($day))
                    ->sum('gross'),
                2
            );
        }

        $donut = [
            'telemed'  => round($filtered->where('type', 'telemed')->sum('gross'), 2),
            'inclinic' => round($filtered->where('type', 'inclinic')->sum('gross'), 2),
            'order'    => round($filtered->where('type', 'order')->sum('gross'), 2),
            'other'    => round(
                $filtered
                    ->reject(fn ($t) => in_array($t['type'], ['telemed', 'inclinic', 'order'], true))
                    ->sum('gross'),
                2
            ),
        ];

        $lastSuccess = $filtered->first(function ($txn) use ($successfulStatuses) {
            return in_array($txn['status'], $successfulStatuses, true);
        });

        $response = [
            'success'       => true,
            'clinic_id'     => $clinicId,
            'vet_id'        => $vetId,
            'filters'       => [
                'from'   => $fromDate?->toDateString(),
                'to'     => $toDate?->toDateString(),
                'status' => $status ?: 'all',
                'type'   => $type ?: 'all',
                'search' => $search,
            ],
            'kpi'           => $kpi,
            'charts'        => [
                'revenue_last_10_days' => [
                    'labels' => $lineLabels,
                    'values' => $lineValues,
                ],
                'breakdown' => $donut,
            ],
            'settlement'    => [
                'last_payout_date' => $lastSuccess && $lastSuccess['date_iso']
                    ? Carbon::parse($lastSuccess['date_iso'])->format('d M Y')
                    : null,
                'next_payout_date' => $now->copy()->addDays(7)->format('d M Y'),
                'pending_payouts'  => round(
                    $filtered->filter(fn ($txn) => in_array($txn['status'], $pendingStatuses, true))->sum('gross'),
                    2
                ),
                'currency'         => 'INR',
            ],
            'transactions'  => $filtered,
            'counts'        => [
                'total'    => $filtered->count(),
                'payments' => $normalized->count(),
            ],
        ];

        return response()->json($response);
    }

    private function parseDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function cleanLower($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : strtolower($value);
    }

    private function numeric($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function normalizeType(Transaction $txn): string
    {
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
    }
}

