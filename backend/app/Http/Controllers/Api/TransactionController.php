<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 50);
        $limit = max(1, min($limit, 200));

        $transactions = Transaction::query()
            ->with([
                'user' => fn ($q) => $q->select('id', 'name'),
                'user.deviceTokens:id,user_id,token',
                'user.pets:id,user_id,name',
            ])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $payload = $transactions->map(function (Transaction $tx) {
            $user = $tx->user;
            $deviceTokens = $user
                ? $user->deviceTokens->pluck('token')->filter()->unique()->values()->all()
                : [];
            $petNames = $user
                ? $user->pets->pluck('name')->filter()->unique()->values()->all()
                : [];

            return [
                'id' => $tx->id,
                'user_id' => $tx->user_id,
                'amount_paise' => $tx->amount_paise,
                'status' => $tx->status,
                'type' => $tx->type,
                'payment_method' => $tx->payment_method,
                'reference' => $tx->reference,
                'created_at' => optional($tx->created_at)->toIso8601String(),
                'updated_at' => optional($tx->updated_at)->toIso8601String(),
                'user_name' => $user->name ?? null,
                'device_tokens' => $deviceTokens,
                'pet_names' => $petNames,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $payload->count(),
            'data' => $payload,
        ]);
    }
}
