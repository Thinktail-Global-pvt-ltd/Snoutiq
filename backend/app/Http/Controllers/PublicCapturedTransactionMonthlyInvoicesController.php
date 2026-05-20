<?php

namespace App\Http\Controllers;

use App\Support\PublicCapturedTransactionInvoices;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class PublicCapturedTransactionMonthlyInvoicesController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $start = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $validated['month'] . '-01 00:00:00', 'Asia/Kolkata');
        $end = $start->addMonth();

        $transactions = PublicCapturedTransactionInvoices::downloadableQuery()
            ->where('created_at', '>=', $start->setTimezone('UTC'))
            ->where('created_at', '<', $end->setTimezone('UTC'))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'created_at']);

        return response()->json([
            'success' => true,
            'month' => $validated['month'],
            'count' => $transactions->count(),
            'invoices' => $transactions->map(fn ($transaction) => [
                'transaction_id' => (int) $transaction->id,
                'invoice_number' => PublicCapturedTransactionInvoices::invoiceNumber($transaction),
                'download_url' => route('captured-transactions.invoice', [
                    'transaction' => $transaction->id,
                    'download' => 1,
                ]),
            ])->values(),
        ]);
    }
}
