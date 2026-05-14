<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PublicCapturedTransactionsController extends Controller
{
    public function __invoke(Request $request)
    {
        $hasRequiredTables = Schema::hasTable('transactions') && Schema::hasTable('users');
        $hasRequiredColumns = $hasRequiredTables
            && Schema::hasColumn('transactions', 'user_id')
            && Schema::hasColumn('transactions', 'status')
            && Schema::hasColumn('transactions', 'amount_paise');

        $transactions = collect();
        $metrics = [
            'total_transactions' => 0,
            'total_amount_paise' => 0,
            'unique_users' => 0,
        ];

        if ($hasRequiredColumns) {
            $userColumns = ['id'];
            foreach (['name', 'email', 'phone'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $userColumns[] = $column;
                }
            }

            $query = Transaction::query()
                ->with(['user' => fn ($query) => $query->select(array_unique($userColumns))])
                ->where('status', 'captured')
                ->where('amount_paise', '!=', 100)
                ->whereNotNull('user_id')
                ->whereHas('user')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            $metricsQuery = clone $query;

            $metrics = [
                'total_transactions' => (clone $metricsQuery)->count(),
                'total_amount_paise' => (int) (clone $metricsQuery)->sum('amount_paise'),
                'unique_users' => (clone $metricsQuery)->distinct('user_id')->count('user_id'),
            ];

            $transactions = $query->paginate(100)->withQueryString();
        }

        return view('public.captured-transactions', [
            'transactions' => $transactions,
            'metrics' => $metrics,
            'hasRequiredTables' => $hasRequiredTables,
            'hasRequiredColumns' => $hasRequiredColumns,
        ]);
    }
}
