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

        $invoiceNumber = 'INV-TXN-' . $transaction->id;
        $invoiceDate = optional($transaction->created_at)->format('d M Y, h:i A') ?: now()->format('d M Y, h:i A');

        $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
        $payout = is_array($metadata['payout_breakup'] ?? null) ? $metadata['payout_breakup'] : [];

        $grossPaise = $this->resolveGrossPaise($transaction, $payout);
        $taxablePaise = $this->resolveTaxablePaise($grossPaise, $payout);
        $gstPaise = $this->resolveGstPaise($grossPaise, $taxablePaise, $payout);

        $lineItemLabel = $this->resolveLineItemLabel((string) ($transaction->type ?? ''));
        $status = strtoupper((string) ($transaction->status ?? 'pending'));
        $reference = (string) ($transaction->reference ?? '');

        $html = $this->buildHtml(
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

        $pdf = $this->renderPdf($html);
        $filename = strtolower($invoiceNumber) . '.pdf';
        $download = filter_var($request->query('download', false), FILTER_VALIDATE_BOOL);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
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

    protected function buildHtml(
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

    protected function formatInr(int $paise): string
    {
        return 'INR ' . number_format(max($paise, 0) / 100, 2, '.', ',');
    }

    protected function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
