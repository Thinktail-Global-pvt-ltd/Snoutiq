<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Support\PublicCapturedTransactionInvoices;
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
        $invoiceNumber = $this->formatInvoiceNumber($transaction);
        $invoiceDate = optional($transaction->created_at)->timezone('Asia/Kolkata')->format('F j, Y')
            ?: now('Asia/Kolkata')->format('F j, Y');

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
        $format = fn (int $paise): string => $this->formatInr($paise);
        $user = $transaction->user;
        $customerName = strtoupper(trim((string) ($user->name ?? 'PET PARENT NAME')) ?: 'PET PARENT NAME');
        $customerPhone = trim((string) ($user->phone ?? '')) ?: 'N/A';
        $serviceLabel = 'Online Vet Consultation';
        $paymentMethod = trim((string) ($transaction->payment_method ?? 'Upi')) ?: 'Upi';
        $paymentMethod = ucfirst(strtolower($paymentMethod));
        $cgstPaise = (int) round(((int) $breakup['gst_paise']) / 2);
        $sgstPaise = max(((int) $breakup['gst_paise']) - $cgstPaise, 0);
        $logoDataUri = $this->imageDataUri(public_path('invoice-assets/thinktail-logo-crop.png'));
        $signatureDataUri = $this->imageDataUri(public_path('invoice-assets/thinktail-signature-crop.png'));
        $totalInWords = ucfirst($this->numberToWords((int) round(((int) $breakup['gross_paise']) / 100))) . ' rupees';
        $logoHtml = $logoDataUri !== null
            ? '<img class="logo" src="' . $logoDataUri . '" alt="SnoutIQ">'
            : '<div class="logo-text">SNOUTIQ</div>';
        $signatureHtml = $signatureDataUri !== null
            ? '<img class="signature" src="' . $signatureDataUri . '" alt="Signature">'
            : '<div class="signature-spacer"></div>';

        return '<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; size: A4 portrait; }
        body {
            margin: 0;
            background: #f4f6f8;
            font-family: DejaVu Sans, sans-serif;
            color: #171c2b;
            font-size: 13px;
            line-height: 1.45;
        }
        .page {
            padding: 54px 46px;
        }
        .invoice {
            background: #fff;
            border: 1px solid #d7dde6;
            padding: 36px 38px 34px;
            min-height: 760px;
        }
        .top {
            display: table;
            width: 100%;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }
        .top > div {
            display: table-cell;
            vertical-align: middle;
        }
        .logo {
            width: 178px;
            height: auto;
        }
        .logo-text {
            font-size: 34px;
            font-weight: 800;
            letter-spacing: 2px;
        }
        .title {
            text-align: right;
            font-size: 33px;
            font-weight: 800;
            letter-spacing: 3px;
        }
        .company {
            display: table;
            width: 100%;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company > div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .company-right {
            padding-left: 34px;
        }
        .bold { font-weight: 800; }
        .bill {
            display: table;
            width: 100%;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 18px;
            margin-bottom: 0;
        }
        .bill > div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .bill-right {
            padding-left: 4px;
        }
        .customer {
            font-size: 21px;
            font-weight: 800;
            margin: 4px 0 8px;
        }
        .invoice-meta-line {
            display: inline-block;
            min-width: 122px;
            border-bottom: 1px solid #1f2937;
            height: 8px;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 20px;
        }
        .items th {
            background: #f7f8fa;
            font-size: 15px;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 13px 12px;
            border-bottom: 2px solid #1f2937;
        }
        .items th:first-child,
        .items td:first-child {
            text-align: left;
        }
        .items td {
            padding: 19px 12px 28px;
            border-bottom: 2px solid #1f2937;
            font-size: 14px;
        }
        .right { text-align: right; }
        .bottom {
            display: table;
            width: 100%;
        }
        .bottom > div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .summary {
            margin-left: 28px;
        }
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }
        .summary-row > div {
            display: table-cell;
        }
        .summary-row .amount {
            text-align: right;
            white-space: nowrap;
        }
        .summary-total {
            border-top: 2px solid #1f2937;
            padding-top: 8px;
            margin-top: 8px;
            font-weight: 800;
            font-size: 15px;
        }
        .words-title {
            margin-top: 10px;
            font-weight: 800;
        }
        .thanks {
            margin-top: 56px;
            font-size: 20px;
            font-weight: 800;
        }
        .signature-block {
            margin-top: 42px;
            margin-left: 32px;
        }
        .signature {
            width: 150px;
            height: auto;
            display: block;
            margin-left: 22px;
            margin-bottom: 4px;
        }
        .signature-spacer {
            height: 55px;
        }
        .signatory {
            font-size: 11px;
            font-weight: 800;
        }
    </style>
</head>
<body>
<div class="page">
<div class="invoice">
    <div class="top">
        <div>
            ' . $logoHtml . '
        </div>
        <div class="title">INVOICE</div>
    </div>

    <div class="company">
        <div>
            <div><span class="bold">Address:</span> THINKTAIL GLOBAL PRIVATE LIMITED</div>
            <div>Plot No.20/HIA/20, Sector-63, Noida, Noida,</div>
            <div>Gautam Buddha Nagar- 201301, Uttar Pradesh</div>
        </div>
        <div class="company-right">
            <div><span class="bold">GSTIN :</span> 06AALCT9891J1ZG</div>
            <div><span class="bold">PAN:</span> AALCT9891J</div>
            <div><span class="bold">Phone:</span> +91 8588007466</div>
        </div>
    </div>

    <div class="bill">
        <div>
            <div class="bold">Invoice to :</div>
            <div class="customer">' . $this->e($customerName) . '</div>
            <div><span class="bold">Phone:</span> ' . $this->e($customerPhone) . '</div>
            <div><span class="bold">Place of Supply:</span> Gurugram</div>
        </div>
        <div class="bill-right">
            <div><span class="bold">Invoice No :</span> ' . $this->e($invoiceNumber) . '</div>
            <div><span class="bold">Date :</span> ' . $this->e($invoiceDate) . '</div>
            <div class="invoice-meta-line"></div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Service</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . $this->e($serviceLabel) . '</td>
                <td class="right">INR ' . $format((int) $breakup['gross_paise']) . '</td>
            </tr>
        </tbody>
    </table>

    <div class="bottom">
        <div>
            <div class="bold">Payment Method :</div>
            <div style="margin-top: 10px;">' . $this->e($paymentMethod) . '</div>
            <div class="thanks">Thank you for purchase!</div>
        </div>
        <div>
            <div class="summary">
                <div class="summary-row">
                    <div>Taxable Amount</div>
                    <div class="amount">INR ' . $format((int) $breakup['base_paise']) . '</div>
                </div>
                <div class="summary-row">
                    <div>CGST @9%</div>
                    <div class="amount">INR ' . $format($cgstPaise) . '</div>
                </div>
                <div class="summary-row">
                    <div>SGST @9%</div>
                    <div class="amount">INR ' . $format($sgstPaise) . '</div>
                </div>
                <div class="summary-row summary-total">
                    <div>Total :</div>
                    <div class="amount">INR ' . $format((int) $breakup['gross_paise']) . '</div>
                </div>
                <div class="words-title">Total Amount (in words)</div>
                <div>' . $this->e($totalInWords) . '</div>
            </div>

            <div class="signature-block">
                ' . $signatureHtml . '
                <div class="signatory">AUTHORISED SIGNATORY FOR</div>
                <div>Thinktail Global pvt. ltd.</div>
                <div>(Snoutiq)</div>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>';
    }

    private function imageDataUri(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $data = file_get_contents($path);

        if ($data === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    private function formatInvoiceNumber(Transaction $transaction): string
    {
        return PublicCapturedTransactionInvoices::invoiceNumber($transaction);
    }

    private function formatInr(int $paise): string
    {
        $amount = $paise / 100;

        if ($paise % 100 === 0) {
            return number_format($amount, 0);
        }

        return rtrim(rtrim(number_format($amount, 2), '0'), '.');
    }

    private function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }

        $ones = [
            0 => '',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
        ];
        $tens = [
            2 => 'twenty',
            3 => 'thirty',
            4 => 'forty',
            5 => 'fifty',
            6 => 'sixty',
            7 => 'seventy',
            8 => 'eighty',
            9 => 'ninety',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            return trim($tens[intdiv($number, 10)] . ' ' . $ones[$number % 10]);
        }

        if ($number < 1000) {
            return trim($ones[intdiv($number, 100)] . ' hundred ' . $this->numberToWords($number % 100));
        }

        if ($number < 100000) {
            return trim($this->numberToWords(intdiv($number, 1000)) . ' thousand ' . $this->numberToWords($number % 1000));
        }

        return (string) $number;
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
