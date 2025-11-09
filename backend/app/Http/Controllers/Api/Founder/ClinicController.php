<?php

namespace App\Http\Controllers\Api\Founder;

use App\Http\Requests\Founder\ClinicIndexRequest;
use App\Http\Resources\ClinicResource;
use App\Models\Clinic;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class ClinicController extends BaseController
{
    public function index(ClinicIndexRequest $request): JsonResponse
    {
        [$sortField, $sortDirection] = $this->resolveSort($request->sort());

        $query = Clinic::query();

        if ($status = $request->status()) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%");
            });
        }

        $query->withCount([
            'transactions as total_transactions' => function ($q) {
                $q->completed();
            },
        ])->withSum([
            'transactions as lifetime_revenue_paise' => function ($q) {
                $q->completed();
            },
        ], 'amount_paise');

        $query->addSelect([
            'last_transaction_at' => Transaction::query()
                ->selectRaw('MAX(created_at)')
                ->whereColumn('clinic_id', 'clinics.id'),
        ]);

        $clinics = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate($request->limit())
            ->appends($request->query());

        $summary = [
            'total' => (int) Clinic::count(),
            'active' => (int) Clinic::query()->active()->count(),
            'inactive' => (int) Clinic::query()->where('status', 'inactive')->count(),
            'totalRevenuePaise' => (int) Transaction::query()->completed()->sum('amount_paise'),
        ];

        $pagination = [
            'currentPage' => $clinics->currentPage(),
            'totalPages' => $clinics->lastPage(),
            'totalResults' => $clinics->total(),
            'limit' => $clinics->perPage(),
            'hasNextPage' => $clinics->hasMorePages(),
            'hasPrevPage' => $clinics->currentPage() > 1,
        ];

        return $this->success([
            'clinics' => ClinicResource::collection($clinics)->resolve(),
            'summary' => $summary,
            'pagination' => $pagination,
        ]);
    }

    public function show(Clinic $clinic): JsonResponse
    {
        $clinic->loadCount([
            'transactions as total_transactions' => function ($q) {
                $q->completed();
            },
        ])->loadSum([
            'transactions as lifetime_revenue_paise' => function ($q) {
                $q->completed();
            },
        ], 'amount_paise');

        $lastTransaction = Transaction::query()
            ->where('clinic_id', $clinic->getKey())
            ->max('created_at');

        $clinic->setAttribute(
            'last_transaction_at',
            $lastTransaction ? Carbon::parse($lastTransaction) : null
        );

        return $this->success([
            'clinic' => ClinicResource::make($clinic)->resolve(),
        ]);
    }

    private function resolveSort(array $sort): array
    {
        [$field, $direction] = $sort;
        $allowed = ['created_at', 'name', 'status', 'city'];

        if (! in_array($field, $allowed, true)) {
            $field = 'created_at';
        }

        return [$field, $direction === 'asc' ? 'asc' : 'desc'];
    }
}

