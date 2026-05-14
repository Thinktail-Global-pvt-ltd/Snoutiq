<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class PublicCapturedTransactionInvoiceController extends Controller
{
    public function __invoke(Request $request, Transaction $transaction)
    {
        $transaction->loadMissing('user');

        abort_unless($this->isEligibleTransaction($transaction), 404);

        $match = $this->resolvePriceMatch((int) ($transaction->amount_paise ?? 0));
        abort_unless($match !== null, 404);

        $breakup = $this->resolveInvoiceBreakup((int) ($transaction->amount_paise ?? 0), $match);
        $invoiceNumber = 'PUB-TXN-' . $transaction->id;
        $invoiceDate = optional($transaction->created_at)->timezone('Asia/Kolkata')->format('d M Y')
            ?: now('Asia/Kolkata')->format('d M Y');

        $html = $this->buildInvoiceHtml($transaction, $invoiceNumber, $invoiceDate, $breakup);
        $pdf = $this->renderPdf($html);
        $filename = strtolower($invoiceNumber) . '.pdf';
        $download = filter_var($request->query('download', false), FILTER_VALIDATE_BOOL);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function isEligibleTransaction(Transaction $transaction): bool
    {
        return strtolower(trim((string) ($transaction->status ?? ''))) === 'captured'
            && (int) ($transaction->amount_paise ?? 0) !== 100
            && !empty($transaction->user_id)
            && $transaction->user !== null;
    }

    private function resolvePriceMatch(int $amountPaise): ?array
    {
        $priceTolerancePaise = 200;
        $gstIncludedPricePaiseOptions = [49900, 76600, 47100, 58900];
        $gstIncludedBaseOverridesPaise = [
            47100 => 39900,
            58900 => 49900,
        ];
        $gstNotAddedPricePaiseOptions = [64800, 37100, 66600, 48900, 65000, 50000, 40000, 39900, 60000];
        $bestMatch = null;

        foreach ([
            'included' => $gstIncludedPricePaiseOptions,
            'not_added' => $gstNotAddedPricePaiseOptions,
        ] as $gstMode => $expectedPricePaiseOptions) {
            foreach ($expectedPricePaiseOptions as $expectedPricePaise) {
                $deltaPaise = abs($amountPaise - $expectedPricePaise);

                if ($deltaPaise > $priceTolerancePaise) {
                    continue;
                }

                if ($bestMatch === null || $deltaPaise < $bestMatch['absolute_delta_paise']) {
                    $bestMatch = [
                        'expected_paise' => $expectedPricePaise,
                        'gst_mode' => $gstMode,
                        'base_override_paise' => $gstMode === 'included'
                            ? ($gstIncludedBaseOverridesPaise[$expectedPricePaise] ?? null)
                            : null,
                        'absolute_delta_paise' => $deltaPaise,
                    ];
                }
            }
        }

        return $bestMatch;
    }

    private function resolveInvoiceBreakup(int $paidPaise, array $match): array
    {
        $gstMode = (string) ($match['gst_mode'] ?? '');
        $expectedPaise = (int) ($match['expected_paise'] ?? $paidPaise);

        if ($gstMode === 'included') {
            $grossPaise = $expectedPaise;
            $basePaise = is_numeric($match['base_override_paise'] ?? null)
                ? (int) $match['base_override_paise']
                : (int) round($grossPaise * 100 / 118);
            $gstPaise = max($grossPaise - $basePaise, 0);

            return [
                'mode' => 'GST added',
                'gross_paise' => $grossPaise,
                'base_paise' => $basePaise,
                'gst_paise' => $gstPaise,
                'paid_paise' => $paidPaise,
            ];
        }

        // For red rows, split the paid amount itself into actual amount + GST.
        $grossPaise = $paidPaise;
        $gstPaise = (int) round($grossPaise * 18 / 118);
        $basePaise = max($grossPaise - $gstPaise, 0);

        return [
            'mode' => 'GST deducted from paid amount',
            'gross_paise' => $grossPaise,
            'base_paise' => $basePaise,
            'gst_paise' => $gstPaise,
            'paid_paise' => $paidPaise,
        ];
    }

    private function buildInvoiceHtml(Transaction $transaction, string $invoiceNumber, string $invoiceDate, array $breakup): string
    {
        $format = fn (int $paise): string => number_format($paise / 100, 2);
        $user = $transaction->user;
        $customerName = trim((string) ($user->name ?? 'Customer')) ?: 'Customer';
        $customerPhone = trim((string) ($user->phone ?? ''));
        $customerEmail = trim((string) ($user->email ?? ''));
        $serviceLabel = trim((string) ($transaction->type ?? 'Service')) ?: 'Service';

        return '<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 13px; }
        .invoice { padding: 28px; }
        .header { display: table; width: 100%; margin-bottom: 28px; }
        .header > div { display: table-cell; vertical-align: top; }
        .right { text-align: right; }
        h1 { margin: 0; font-size: 28px; letter-spacing: 1px; }
        .muted { color: #6b7280; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 11px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f9fafb; font-weight: 700; }
        .amount { text-align: right; }
        .total td { font-size: 15px; font-weight: 700; border-bottom: 0; }
        .badge { display: inline-block; padding: 5px 8px; border-radius: 999px; background: #dcfce7; color: #166534; font-weight: 700; }
    </style>
</head>
<body>
<div class="invoice">
    <div class="header">
        <div>
            <h1>Invoice</h1>
            <div class="muted">SnoutIQ</div>
        </div>
        <div class="right">
            <div><strong>Invoice No:</strong> ' . $this->e($invoiceNumber) . '</div>
            <div><strong>Date:</strong> ' . $this->e($invoiceDate) . '</div>
            <div><strong>Transaction:</strong> #' . $this->e((string) $transaction->id) . '</div>
        </div>
    </div>

    <div class="box">
        <strong>Invoice to</strong><br>
        ' . $this->e($customerName) . '<br>
        ' . ($customerPhone !== '' ? $this->e($customerPhone) . '<br>' : '') . '
        ' . ($customerEmail !== '' ? $this->e($customerEmail) . '<br>' : '') . '
    </div>

    <div class="box">
        <span class="badge">' . $this->e($breakup['mode']) . '</span>
        <div class="muted" style="margin-top: 8px;">Paid amount: INR ' . $format((int) $breakup['paid_paise']) . '</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . $this->e($serviceLabel) . ' - Actual amount</td>
                <td class="amount">INR ' . $format((int) $breakup['base_paise']) . '</td>
            </tr>
            <tr>
                <td>GST @ 18%</td>
                <td class="amount">INR ' . $format((int) $breakup['gst_paise']) . '</td>
            </tr>
            <tr class="total">
                <td>Total</td>
                <td class="amount">INR ' . $format((int) $breakup['gross_paise']) . '</td>
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>';
    }

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
