<?php

namespace App\Http\Controllers\Api\Founder;

use App\Http\Requests\Founder\SalesIndexRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SalesController extends BaseController
{
    public function index(SalesIndexRequest $request): JsonResponse
    {
        [$from, $to] = $request->range();

        $baseQuery = Transaction::query()
            ->whereBetween('created_at', [$from, $to]);

        if ($status = $request->status()) {
            $baseQuery->where('status', $status);
        }

        $listQuery = (clone $baseQuery)
            ->with('clinic')
            ->orderBy('created_at', 'desc');

        $transactions = $listQuery
            ->paginate($request->limit())
            ->appends($request->query());

        $statusCounts = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $summary = [
            'countCompleted' => (int) ($statusCounts['completed'] ?? 0),
            'countPending' => (int) ($statusCounts['pending'] ?? 0),
            'countFailed' => (int) ($statusCounts['failed'] ?? 0),
        ];

        $summary['totalRevenuePaise'] = (int) (clone $baseQuery)
            ->where('status', 'completed')
            ->sum('amount_paise');

        $summary['avgTransactionPaise'] = $summary['countCompleted'] > 0
            ? (int) round($summary['totalRevenuePaise'] / max(1, $summary['countCompleted']))
            : 0;

        return $this->success([
            'summary' => $summary,
            'transactions' => TransactionResource::collection($transactions)->resolve(),
            'pagination' => [
                'currentPage' => $transactions->currentPage(),
                'totalPages' => $transactions->lastPage(),
                'totalResults' => $transactions->total(),
                'limit' => $transactions->perPage(),
                'hasNextPage' => $transactions->hasMorePages(),
                'hasPrevPage' => $transactions->currentPage() > 1,
            ],
            'filters' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
                'status' => $request->status() ?? 'all',
            ],
        ]);
    }
}

