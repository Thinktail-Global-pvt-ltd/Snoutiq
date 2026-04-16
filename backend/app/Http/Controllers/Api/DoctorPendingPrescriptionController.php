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
        $validated = $request->validate([
            'channel_name' => ['nullable', 'string', 'max:255'],
            'call_session' => ['nullable', 'string', 'max:255'],
        ]);

        $channelName = trim((string) ($validated['channel_name'] ?? $validated['call_session'] ?? ''));
        $transaction = $channelName !== '' ? $this->findTransaction($channelName) : null;
        $prescription = $channelName !== '' ? $this->findPrescription($channelName) : null;
        $transactionDone = $transaction !== null && $this->isSuccessfulTransactionStatus($transaction->status ?? null);
        $prescriptionSubmitted = $prescription !== null;

        return response()->json([
            'payment_status' => $transactionDone ? 'paid' : ($transaction ? 'pending' : 'not_found'),
            'prescription_required' => $transactionDone,
            'prescription_status' => $prescriptionSubmitted ? 'submitted' : 'pending',
            'lock_until_submit' => $transactionDone && !$prescriptionSubmitted,
        ]);
    }

    private function findTransaction(string $channelName): ?Transaction
    {
        if (
            !Schema::hasTable('transactions')
            || !Schema::hasColumn('transactions', 'channel_name')
        ) {
            return null;
        }

        return Transaction::query()
            ->where('channel_name', $channelName)
            ->orderByDesc('id')
            ->first();
    }

    private function findPrescription(string $channelName): ?Prescription
    {
        if (
            !Schema::hasTable('prescriptions')
            || !Schema::hasColumn('prescriptions', 'call_session')
        ) {
            return null;
        }

        return Prescription::query()
            ->where('call_session', $channelName)
            ->orderByDesc('id')
            ->first();
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
