<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VetRegistrationReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VetRegistrationReportController extends Controller
{
    public function summary(Request $request, VetRegistrationReportService $service): JsonResponse
    {
        $requestedMonth = $request->query('month');
        $summary = $service->monthlySummary();

        if ($summary->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'months' => [],
                    'selected_month' => null,
                    'requested_month' => $requestedMonth,
                    'selected_month_summary' => null,
                    'free_activations' => [],
                    'all' => [],
                ],
                'message' => 'No vet registrations found.',
            ]);
        }

        $selectedMonth = $service->resolveMonth($requestedMonth, $summary);
        $details = $service->monthDetails($selectedMonth);

        $monthSummary = $summary->firstWhere('month', $selectedMonth);

        return response()->json([
            'success' => true,
            'data' => [
                'months' => $summary,
                'selected_month' => $selectedMonth,
                'requested_month' => $requestedMonth,
                'selected_month_summary' => $monthSummary,
                'free_activations' => $details['free_activations']->map([$this, 'transformRow'])->values(),
                'all' => $details['all']->map([$this, 'transformRow'])->values(),
            ],
        ]);
    }

    public function transformRow($row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => $row->name ?: 'Unnamed clinic',
            'status' => $row->status,
            'owner_user_id' => $row->owner_user_id ? (int) $row->owner_user_id : null,
            'claimed_at' => $row->claimed_at ? $row->claimed_at->toDateTimeString() : null,
            'created_at' => $row->created_at ? $row->created_at->toDateTimeString() : null,
            'city' => $row->city,
            'pincode' => $row->pincode,
            'chat_price' => $row->chat_price !== null ? (float) $row->chat_price : null,
        ];
    }
}
