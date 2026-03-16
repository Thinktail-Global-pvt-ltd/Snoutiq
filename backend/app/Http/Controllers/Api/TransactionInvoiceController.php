<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class TransactionInvoiceController extends Controller
{
    /**
     * GET /api/transactions/{transaction}/invoice/pdf
     */
    public function show(Request $request, Transaction $transaction)
    {
        $transaction->loadMissing(['user', 'doctor', 'pet', 'clinic']);

        $isExcellExportCampaign = $this->isExcellExportCampaign($transaction);

        $invoiceNumber = $isExcellExportCampaign
            ? $this->formatThinktailInvoiceNumber((int) $transaction->id)
            : 'INV-TXN-' . $transaction->id;

        $invoiceDate = optional($transaction->created_at)->format(
            $isExcellExportCampaign ? 'F j, Y' : 'd M Y, h:i A'
        ) ?: now()->format($isExcellExportCampaign ? 'F j, Y' : 'd M Y, h:i A');

        $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
        $payout = is_array($metadata['payout_breakup'] ?? null) ? $metadata['payout_breakup'] : [];

        $grossPaise = $this->resolveGrossPaise($transaction, $payout);
        $taxablePaise = $this->resolveTaxablePaise($grossPaise, $payout);
        $gstPaise = $this->resolveGstPaise($grossPaise, $taxablePaise, $payout);

        if ($isExcellExportCampaign) {
            $breakup = $this->resolveExcellExportBreakup(
                transaction: $transaction,
                grossPaise: $grossPaise,
                taxablePaise: $taxablePaise,
                gstPaise: $gstPaise,
                payout: $payout
            );

            $html = $this->buildExcellExportCampaignHtml(
                transaction: $transaction,
                invoiceNumber: $invoiceNumber,
                invoiceDate: $invoiceDate,
                totalPaise: $breakup['total_paise'],
                taxablePaise: $breakup['taxable_paise'],
                cgstPaise: $breakup['cgst_paise'],
                sgstPaise: $breakup['sgst_paise']
            );
        } else {
            $lineItemLabel = $this->resolveLineItemLabel((string) ($transaction->type ?? ''));
            $status = strtoupper((string) ($transaction->status ?? 'pending'));
            $reference = (string) ($transaction->reference ?? '');

            $html = $this->buildDefaultHtml(
                transaction: $transaction,
                invoiceNumber: $invoiceNumber,
                invoiceDate: $invoiceDate,
                lineItemLabel: $lineItemLabel,
                status: $status,
                reference: $reference,
                grossPaise: $grossPaise,
                taxablePaise: $taxablePaise,
                gstPaise: $gstPaise
            );
        }

        $pdf = $this->renderPdf($html);
        $filename = strtolower($invoiceNumber) . '.pdf';
        $download = filter_var($request->query('download', false), FILTER_VALIDATE_BOOL);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * GET /api/transactions/invoice/pdf?pet_id=&transaction_id=&download=
     */
    public function showByPetAndTransaction(Request $request)
    {
        $data = $request->validate([
            'pet_id' => ['required', 'integer', 'exists:pets,id'],
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
            'download' => ['nullable', 'boolean'],
        ]);

        $transaction = Transaction::query()
            ->whereKey((int) $data['transaction_id'])
            ->where('pet_id', (int) $data['pet_id'])
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'No transaction found for the provided pet_id and transaction_id.',
            ], 404);
        }

        if (! $request->query->has('download')) {
            $request->query->set(
                'download',
                filter_var($data['download'] ?? true, FILTER_VALIDATE_BOOL) ? '1' : '0'
            );
        }

        return $this->show($request, $transaction);
    }

    protected function isExcellExportCampaign(Transaction $transaction): bool
    {
        return strtolower(trim((string) ($transaction->type ?? ''))) === 'excell_export_campaign';
    }

    protected function resolveGrossPaise(Transaction $transaction, array $payout): int
    {
        $gross = $transaction->actual_amount_paid_by_consumer_paise;
        if (!is_numeric($gross) || (int) $gross <= 0) {
            $gross = $transaction->amount_paise;
        }
        if ((!is_numeric($gross) || (int) $gross <= 0) && is_numeric($payout['actual_amount_paid_by_consumer_paise'] ?? null)) {
            $gross = (int) $payout['actual_amount_paid_by_consumer_paise'];
        }

        return max((int) $gross, 0);
    }

    protected function resolveTaxablePaise(int $grossPaise, array $payout): int
    {
        if (is_numeric($payout['amount_before_gst_paise'] ?? null)) {
            return max((int) $payout['amount_before_gst_paise'], 0);
        }
        if (is_numeric($payout['amount_after_gst_paise'] ?? null)) {
            return max((int) $payout['amount_after_gst_paise'], 0);
        }

        return $grossPaise;
    }

    protected function resolveGstPaise(int $grossPaise, int $taxablePaise, array $payout): int
    {
        if (is_numeric($payout['gst_paise'] ?? null)) {
            return max((int) $payout['gst_paise'], 0);
        }

        $gst = $grossPaise - $taxablePaise;
        return $gst > 0 ? $gst : 0;
    }

    protected function resolveExcellExportBreakup(
        Transaction $transaction,
        int $grossPaise,
        int $taxablePaise,
        int $gstPaise,
        array $payout
    ): array {
        $totalPaise = $grossPaise;
        if ($totalPaise <= 0 && is_numeric($transaction->amount_paise ?? null)) {
            $totalPaise = max((int) $transaction->amount_paise, 0);
        }

        $totalInr = (int) round(max($totalPaise, 0) / 100);

        // Preserve fixed campaign prices exactly as provided in the GST sample.
        if ($totalInr === 471) {
            return [
                'total_paise' => 47100,
                'taxable_paise' => 39900,
                'cgst_paise' => 3591,
                'sgst_paise' => 3591,
            ];
        }
        if ($totalInr === 589) {
            return [
                'total_paise' => 58900,
                'taxable_paise' => 49900,
                'cgst_paise' => 4491,
                'sgst_paise' => 4491,
            ];
        }

        if (is_numeric($payout['amount_before_gst_paise'] ?? null)) {
            $taxablePaise = max((int) $payout['amount_before_gst_paise'], 0);
        } elseif ($taxablePaise <= 0 && $totalPaise > 0) {
            $taxablePaise = (int) round(($totalPaise * 100) / 118);
        }

        if (is_numeric($payout['gst_paise'] ?? null)) {
            $gstPaise = max((int) $payout['gst_paise'], 0);
        } elseif ($gstPaise <= 0) {
            $gstPaise = max($totalPaise - $taxablePaise, 0);
        }

        $cgstPaise = (int) round($gstPaise / 2);
        $sgstPaise = max($gstPaise - $cgstPaise, 0);

        return [
            'total_paise' => max($totalPaise, 0),
            'taxable_paise' => max($taxablePaise, 0),
            'cgst_paise' => max($cgstPaise, 0),
            'sgst_paise' => max($sgstPaise, 0),
        ];
    }

    protected function resolveLineItemLabel(string $type): string
    {
        $normalized = strtolower(trim($type));
        if ($normalized === '') {
            return 'Consultation Fee';
        }

        return match ($normalized) {
            'video_consult', 'video_call', 'video call', 'appointment' => 'Video Consultation',
            'excell_export_campaign' => 'Consultation (Excel Export Campaign)',
            default => ucwords(str_replace(['_', '-'], ' ', $normalized)),
        };
    }

    protected function buildExcellExportCampaignHtml(
        Transaction $transaction,
        string $invoiceNumber,
        string $invoiceDate,
        int $totalPaise,
        int $taxablePaise,
        int $cgstPaise,
        int $sgstPaise
    ): string {
        $customerName = strtoupper(trim((string) ($transaction->user->name ?? 'PET PARENT NAME')));
        if ($customerName === '') {
            $customerName = 'PET PARENT NAME';
        }

        $customerPhone = trim((string) ($transaction->user->phone ?? ''));
        if ($customerPhone === '') {
            $customerPhone = 'N/A';
        }

        $placeOfSupply = 'Gurugram';
        $paymentMethod = $this->resolvePaymentMethodLabel((string) ($transaction->payment_method ?? ''));

        $taxableDisplay = 'INR ' . $this->formatInrWhole($taxablePaise);
        $cgstDisplay = 'INR ' . $this->formatInrTwoDecimals($cgstPaise);
        $sgstDisplay = 'INR ' . $this->formatInrTwoDecimals($sgstPaise);
        $totalDisplay = 'INR ' . $this->formatInrWhole($totalPaise);
        $amountWords = $this->amountInWords((int) round(max($totalPaise, 0) / 100));
        $logoDataUri = $this->imageDataUri(public_path('invoice-assets/thinktail-logo-crop.png'));
        $signatureDataUri = $this->imageDataUri(public_path('invoice-assets/thinktail-signature-crop.png'));
        $logoHtml = $logoDataUri !== null
            ? '<img class="logo-image" src="' . $this->e($logoDataUri) . '" alt="Snoutiq">'
            : '<div class="logo">SN<span class="logo-o">OO</span>TIQ</div>';
        $signatureHtml = $signatureDataUri !== null
            ? '<img class="signature-image" src="' . $this->e($signatureDataUri) . '" alt="Signature">'
            : '<div class="signature-mark">Authorised</div>';

        $style = <<<CSS
body {
    margin: 0;
    font-family: DejaVu Sans, sans-serif;
    color: #0f172a;
    background: #f3f5f8;
    font-size: 12.5px;
}
.sheet {
    padding: 26px;
}
.page {
    background: #ffffff;
    border: 1px solid #d9e0ea;
    padding: 32px 36px 30px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
.top td {
    vertical-align: middle;
}
.logo {
    font-size: 32px;
    font-weight: 800;
    letter-spacing: 0.8px;
}
.logo-image {
    width: 215px;
    height: auto;
}
.logo-o {
    color: #34a7dc;
}
.invoice-title {
    text-align: right;
    font-size: 32px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #111827;
}
.rule {
    border-top: 1.4px solid #1f2937;
    margin: 10px 0 12px;
}
.vendor td {
    vertical-align: top;
    line-height: 1.35;
}
.vendor-left {
    width: 68%;
    padding-right: 14px;
}
.vendor-right {
    width: 32%;
}
.label {
    font-weight: 700;
    color: #111827;
}
.bill {
    margin-top: 2px;
}
.bill td {
    vertical-align: top;
}
.bill-left {
    width: 63%;
}
.bill-right {
    width: 37%;
    padding-top: 2px;
}
.invoice-to {
    font-weight: 700;
    margin-bottom: 7px;
    color: #111827;
}
.party-name {
    font-size: 19px;
    font-weight: 800;
    margin-bottom: 8px;
    letter-spacing: 0.2px;
}
.meta-line {
    margin-top: 2px;
}
.date-underline {
    width: 40px;
    border-top: 1.3px solid #1f2937;
    margin-top: 8px;
}
.service-table {
    margin-top: 18px;
    border-top: 1.4px solid #1f2937;
    border-bottom: 1.4px solid #1f2937;
}
.service-table thead tr {
    background: #f8fafc;
    border-bottom: 1.3px solid #1f2937;
}
.service-table th {
    padding: 10px 12px;
    text-align: left;
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    letter-spacing: 0.3px;
}
.service-table th:last-child,
.service-table td:last-child {
    text-align: right;
}
.service-table td {
    padding: 15px 12px 28px;
    font-size: 13.5px;
}
.summary {
    margin-top: 22px;
}
.summary td {
    vertical-align: top;
}
.payment-col {
    width: 58%;
}
.payment-title {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 7px;
    color: #111827;
}
.payment-value {
    font-size: 14px;
}
.tax-col {
    width: 42%;
}
.tax-table {
    width: 100%;
    border-collapse: collapse;
}
.tax-table td {
    padding: 2px 0;
    font-size: 13.5px;
}
.tax-table td:last-child {
    text-align: right;
}
.tax-divider {
    border-top: 1.5px solid #1f2937;
    margin: 8px 0 6px;
}
.tax-total {
    width: 100%;
}
.tax-total td {
    font-size: 15px;
    font-weight: 800;
    color: #111827;
}
.tax-total td:last-child {
    text-align: right;
}
.words-title {
    margin-top: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #111827;
}
.words-value {
    margin-top: 2px;
    font-size: 13.5px;
}
.footer {
    margin-top: 40px;
}
.footer td {
    vertical-align: top;
}
.thanks {
    width: 58%;
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    padding-top: 7px;
}
.sign {
    width: 42%;
}
.signature-mark {
    font-family: "Times New Roman", serif;
    font-size: 30px;
    font-style: italic;
    color: #303050;
    line-height: 1;
    margin-bottom: 4px;
}
.signature-image {
    width: 146px;
    height: auto;
    margin-bottom: 7px;
}
.sign-title {
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    line-height: 1.2;
    color: #111827;
}
.sign-org {
    font-size: 12px;
    line-height: 1.35;
    color: #111827;
}
CSS;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>{$style}</style>
</head>
<body>
    <div class="sheet">
    <div class="page">
        <table class="top">
            <tr>
                <td>
                    {$logoHtml}
                </td>
                <td class="invoice-title">INVOICE</td>
            </tr>
        </table>

        <div class="rule"></div>

        <table class="vendor">
            <tr>
                <td class="vendor-left">
                    <div><span class="label">Address:</span> THINKTAIL GLOBAL PRIVATE LIMITED</div>
                    <div>Plot No.20/HIA/20, Sector-63, Noida, Noida,</div>
                    <div>Gautam Buddha Nagar- 201301, Uttar Pradesh</div>
                </td>
                <td class="vendor-right">
                    <div><span class="label">GSTIN :</span> 06AALCT9891J1ZG</div>
                    <div><span class="label">PAN:</span> AALCT9891J</div>
                    <div><span class="label">Phone:</span> +91 8588007466</div>
                </td>
            </tr>
        </table>

        <div class="rule"></div>

        <table class="bill">
            <tr>
                <td class="bill-left">
                    <div class="invoice-to">Invoice to :</div>
                    <div class="party-name">{$this->e($customerName)}</div>
                    <div class="meta-line"><span class="label">Phone:</span> {$this->e($customerPhone)}</div>
                    <div class="meta-line"><span class="label">Place of Supply:</span> {$this->e($placeOfSupply)}</div>
                </td>
                <td class="bill-right">
                    <div><span class="label">Invoice No :</span> {$this->e($invoiceNumber)}</div>
                    <div><span class="label">Date :</span> {$this->e($invoiceDate)}</div>
                    <div class="date-underline"></div>
                </td>
            </tr>
        </table>

        <table class="service-table">
            <thead>
                <tr>
                    <th>SERVICE</th>
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Online Vet Consultation</td>
                    <td>{$this->e($taxableDisplay)}</td>
                </tr>
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td class="payment-col">
                    <div class="payment-title">Payment Method :</div>
                    <div class="payment-value">{$this->e($paymentMethod)}</div>
                </td>
                <td class="tax-col">
                    <table class="tax-table">
                        <tr>
                            <td>Taxable Amount</td>
                            <td>{$this->e($taxableDisplay)}</td>
                        </tr>
                        <tr>
                            <td>CGST @9%</td>
                            <td>{$this->e($cgstDisplay)}</td>
                        </tr>
                        <tr>
                            <td>SGST @9%</td>
                            <td>{$this->e($sgstDisplay)}</td>
                        </tr>
                    </table>
                    <div class="tax-divider"></div>
                    <table class="tax-total">
                        <tr>
                            <td>Total :</td>
                            <td>{$this->e($totalDisplay)}</td>
                        </tr>
                    </table>
                    <div class="words-title">Total Amount (in words)</div>
                    <div class="words-value">{$this->e($amountWords)}</div>
                </td>
            </tr>
        </table>

        <table class="footer">
            <tr>
                <td class="thanks">Thank you for purchase!</td>
                <td class="sign">
                    {$signatureHtml}
                    <div class="sign-title">AUTHORISED SIGNATORY FOR</div>
                    <div class="sign-org">Thinktail Global pvt. ltd.<br>(Snoutiq)</div>
                </td>
            </tr>
        </table>
    </div>
    </div>
</body>
</html>
HTML;
    }

    protected function buildDefaultHtml(
        Transaction $transaction,
        string $invoiceNumber,
        string $invoiceDate,
        string $lineItemLabel,
        string $status,
        string $reference,
        int $grossPaise,
        int $taxablePaise,
        int $gstPaise
    ): string {
        $clinicName = trim((string) ($transaction->clinic->name ?? 'Snoutiq'));
        $clinicAddress = trim((string) ($transaction->clinic->address ?? ''));
        $clinicCity = trim((string) ($transaction->clinic->city ?? ''));
        $clinicContact = trim((string) ($transaction->clinic->mobile ?? ''));

        $userName = trim((string) ($transaction->user->name ?? 'Pet Parent'));
        $userEmail = trim((string) ($transaction->user->email ?? ''));
        $userPhone = trim((string) ($transaction->user->phone ?? ''));

        $doctorName = trim((string) ($transaction->doctor->doctor_name ?? ''));
        $petName = trim((string) ($transaction->pet->name ?? ''));
        $petBreed = trim((string) ($transaction->pet->breed ?? ''));
        $petType = trim((string) (($transaction->pet->pet_type ?? $transaction->pet->type ?? '') ?: ''));

        $amountInr = $this->formatInr($grossPaise);
        $taxableInr = $this->formatInr($taxablePaise);
        $gstInr = $this->formatInr($gstPaise);
        $showTaxBlock = $gstPaise > 0 || $taxablePaise !== $grossPaise;

        $style = <<<CSS
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; margin: 26px; }
.header { display: table; width: 100%; margin-bottom: 18px; }
.header-left, .header-right { display: table-cell; vertical-align: top; }
.header-right { text-align: right; }
h1 { margin: 0 0 6px; font-size: 22px; letter-spacing: 0.5px; }
.muted { color: #6b7280; }
.box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; }
.cols { display: table; width: 100%; border-spacing: 0 12px; }
.col { display: table-cell; width: 50%; vertical-align: top; }
.label { color: #6b7280; font-size: 11px; margin-bottom: 3px; text-transform: uppercase; }
table { width: 100%; border-collapse: collapse; margin-top: 8px; }
th { text-align: left; font-size: 11px; color: #4b5563; border-bottom: 1px solid #d1d5db; padding: 8px 6px; }
td { padding: 9px 6px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
.right { text-align: right; }
.total { font-size: 14px; font-weight: bold; }
.foot { margin-top: 18px; font-size: 11px; color: #6b7280; }
CSS;

        $summaryRows = '';
        if ($showTaxBlock) {
            $summaryRows .= '<tr><td colspan="3" class="right">Taxable Amount</td><td class="right">' . $this->e($taxableInr) . '</td></tr>';
            $summaryRows .= '<tr><td colspan="3" class="right">GST</td><td class="right">' . $this->e($gstInr) . '</td></tr>';
        }
        $summaryRows .= '<tr><td colspan="3" class="right total">Total Paid</td><td class="right total">' . $this->e($amountInr) . '</td></tr>';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>{$style}</style>
</head>
<body>
  <div class="header">
    <div class="header-left">
      <h1>Invoice</h1>
      <div><strong>{$this->e($clinicName)}</strong></div>
      <div class="muted">{$this->e(trim($clinicAddress . ($clinicCity !== '' ? ', ' . $clinicCity : '')))}</div>
      <div class="muted">{$this->e($clinicContact)}</div>
    </div>
    <div class="header-right">
      <div><strong>Invoice #</strong> {$this->e($invoiceNumber)}</div>
      <div><strong>Date</strong> {$this->e($invoiceDate)}</div>
      <div><strong>Transaction ID</strong> {$this->e((string) $transaction->id)}</div>
      <div><strong>Status</strong> {$this->e($status)}</div>
      <div><strong>Reference</strong> {$this->e($reference !== '' ? $reference : 'N/A')}</div>
    </div>
  </div>

  <div class="cols">
    <div class="col">
      <div class="box">
        <div class="label">Billed To</div>
        <div><strong>{$this->e($userName)}</strong></div>
        <div>{$this->e($userEmail !== '' ? $userEmail : '—')}</div>
        <div>{$this->e($userPhone !== '' ? $userPhone : '—')}</div>
      </div>
    </div>
    <div class="col">
      <div class="box">
        <div class="label">Consultation Details</div>
        <div><strong>Doctor:</strong> {$this->e($doctorName !== '' ? $doctorName : '—')}</div>
        <div><strong>Pet:</strong> {$this->e($petName !== '' ? $petName : '—')}</div>
        <div><strong>Pet Type:</strong> {$this->e($petType !== '' ? $petType : '—')}</div>
        <div><strong>Breed:</strong> {$this->e($petBreed !== '' ? $petBreed : '—')}</div>
      </div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th class="right">Qty</th>
        <th class="right">Rate</th>
        <th class="right">Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{$this->e($lineItemLabel)}</td>
        <td class="right">1</td>
        <td class="right">{$this->e($amountInr)}</td>
        <td class="right">{$this->e($amountInr)}</td>
      </tr>
      {$summaryRows}
    </tbody>
  </table>

  <div class="foot">
    This is a system-generated invoice for transaction {$this->e((string) $transaction->id)}.
  </div>
</body>
</html>
HTML;
    }

    protected function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    protected function formatThinktailInvoiceNumber(int $transactionId): string
    {
        $id = str_pad((string) max($transactionId, 0), 6, '0', STR_PAD_LEFT);
        return '0000-' . substr($id, 0, 3) . '-' . substr($id, 3, 3);
    }

    protected function resolvePlaceOfSupply(Transaction $transaction): string
    {
        return 'Gurugram';
    }

    protected function resolvePaymentMethodLabel(string $paymentMethod): string
    {
        $normalized = strtolower(trim($paymentMethod));
        if ($normalized === '') {
            return 'Prepaid Razorpay';
        }

        if ($normalized === 'razorpay') {
            return 'Prepaid Razorpay';
        }

        $normalized = str_replace(['_', '-'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return ucwords(trim($normalized));
    }

    protected function amountInWords(int $rupees): string
    {
        return ucfirst($this->numberToWords(max($rupees, 0))) . ' rupees';
    }

    protected function numberToWords(int $number): string
    {
        $ones = [
            0 => 'zero',
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
            return $ones[$number] ?? 'zero';
        }

        if ($number < 100) {
            $ten = intdiv($number, 10);
            $rem = $number % 10;
            return ($tens[$ten] ?? '') . ($rem > 0 ? ' ' . $ones[$rem] : '');
        }

        if ($number < 1000) {
            $hundreds = intdiv($number, 100);
            $rem = $number % 100;
            return $ones[$hundreds] . ' hundred' . ($rem > 0 ? ' ' . $this->numberToWords($rem) : '');
        }

        if ($number < 100000) {
            $thousands = intdiv($number, 1000);
            $rem = $number % 1000;
            return $this->numberToWords($thousands) . ' thousand' . ($rem > 0 ? ' ' . $this->numberToWords($rem) : '');
        }

        if ($number < 10000000) {
            $lakhs = intdiv($number, 100000);
            $rem = $number % 100000;
            return $this->numberToWords($lakhs) . ' lakh' . ($rem > 0 ? ' ' . $this->numberToWords($rem) : '');
        }

        $crores = intdiv($number, 10000000);
        $rem = $number % 10000000;
        return $this->numberToWords($crores) . ' crore' . ($rem > 0 ? ' ' . $this->numberToWords($rem) : '');
    }

    protected function formatInrWhole(int $paise): string
    {
        return number_format(max($paise, 0) / 100, 0, '.', ',');
    }

    protected function imageDataUri(string $path, string $mimeType = 'image/png'): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    protected function formatInrTwoDecimals(int $paise): string
    {
        return number_format(max($paise, 0) / 100, 2, '.', ',');
    }

    protected function formatInr(int $paise): string
    {
        return 'INR ' . number_format(max($paise, 0) / 100, 2, '.', ',');
    }

    protected function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
