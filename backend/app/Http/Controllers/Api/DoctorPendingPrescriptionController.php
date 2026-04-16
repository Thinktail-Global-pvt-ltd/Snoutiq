<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DoctorPendingPrescriptionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $userId = $this->positiveInt($request->query('user_id') ?? $request->query('id'));
        $transaction = $userId ? $this->findTransaction($userId) : null;
        $prescription = $userId ? $this->findPrescription($userId) : null;
        $transactionDone = $transaction !== null && $this->isSuccessfulTransactionStatus($transaction->status ?? null);
        $prescriptionSubmitted = $prescription !== null;

        return response()->json([
            'payment_status' => $transactionDone ? 'paid' : ($transaction ? 'pending' : 'not_found'),
            'prescription_required' => $transactionDone,
            'prescription_status' => $prescriptionSubmitted ? 'submitted' : 'pending',
            'lock_until_submit' => $transactionDone && !$prescriptionSubmitted,
        ]);
    }

    private function findTransaction(int $userId): ?Transaction
    {
        if (
            !Schema::hasTable('transactions')
            || !Schema::hasColumn('transactions', 'user_id')
        ) {
            return null;
        }

        return Transaction::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();
    }

    private function findPrescription(int $userId): ?Prescription
    {
        if (
            !Schema::hasTable('prescriptions')
            || !Schema::hasColumn('prescriptions', 'user_id')
        ) {
            return null;
        }

        return Prescription::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();
    }

    private function positiveInt($value): ?int
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }

    private function isSuccessfulTransactionStatus(?string $status): bool
    {
        return in_array(strtolower(trim((string) $status)), [
            'captured',
            'paid',
            'success',
            'successful',
            'completed',
            'settled',
            'verified',
        ], true);
    }
}
