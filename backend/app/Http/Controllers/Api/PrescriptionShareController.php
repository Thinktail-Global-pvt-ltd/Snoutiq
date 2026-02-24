<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\Pet;
use App\Models\Doctor;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;

class PrescriptionShareController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

    /**
     * POST /api/consultation/prescription/send-doc
     * Body: user_id (required), pet_id (required)
     */
    public function sendLatest(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'pet_id' => ['required', 'integer'],
        ]);

        $prescription = Prescription::query()
            ->where('user_id', $data['user_id'])
            ->where('pet_id', $data['pet_id'])
            ->orderByDesc('id')
            ->first();

        if (! $prescription) {
            return response()->json(['success' => false, 'error' => 'prescription_not_found'], 404);
        }

        $user = User::find($data['user_id']);
        $pet = Pet::find($data['pet_id']);
        $doctor = $prescription->doctor_id ? Doctor::find($prescription->doctor_id) : null;

        $phone = $user?->phone;
        if (! $phone) {
            return response()->json(['success' => false, 'error' => 'user_phone_missing'], 422);
        }

        // Pick a document to send: prefer stored medical record file; else prescription image_path
        $docUrl = null;
        $docName = 'Prescription.pdf';

        if ($prescription->medical_record_id) {
            $record = MedicalRecord::find($prescription->medical_record_id);
            if ($record && $record->file_path) {
                $docUrl = $this->publicUrl($record->file_path);
                $docName = $record->file_name ?: $docName;
            }
        }

        if (! $docUrl && $prescription->image_path) {
            $docUrl = $this->publicUrl($prescription->image_path);
        }

        // If no existing document, generate a PDF from prescription text
        if (! $docUrl) {
            $generated = $this->generatePdf($prescription, $user, $pet, $doctor);
            if (!$generated) {
                return response()->json(['success' => false, 'error' => 'document_missing'], 404);
            }
            [$docPath, $docName] = $generated;
            $docUrl = $this->publicUrl($docPath);
        }

        try {
            $sendResult = $this->whatsApp->sendDocument($phone, $docUrl, $docName);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => 'send_failed', 'message' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'user_id' => $data['user_id'],
            'pet_id' => $data['pet_id'],
            'doctor_id' => $doctor?->id,
            'sent_to' => $phone,
            'document_url' => $docUrl,
            'whatsapp' => $sendResult,
        ]);
    }

    /**
     * GET /api/consultation/prescription/pdf
     * Query: prescription_id
     * Streams the selected prescription as a PDF.
     */
    public function downloadLatest(Request $request)
    {
        try {
            $data = $request->validate([
                'prescription_id' => ['required', 'integer'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => 'validation_failed', 'message' => $e->getMessage()], 422);
        }

        try {
            $prescription = Prescription::query()
                ->where('id', (int) $data['prescription_id'])
                ->first();
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => 'query_failed', 'message' => $e->getMessage()], 500);
        }

        if (! $prescription) {
            return response()->json(['success' => false, 'error' => 'prescription_not_found'], 404);
        }

        $user = $prescription->user_id ? User::find($prescription->user_id) : null;
        $pet = $prescription->pet_id ? Pet::find($prescription->pet_id) : null;
        $doctor = $prescription->doctor_id ? Doctor::find($prescription->doctor_id) : null;

        // Always generate a fresh PDF from prescription text (ignore stored docs)
        try {
            $docName = 'Prescription.pdf';
            $html = $this->buildHtml($prescription, $user, $pet, $doctor);
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            // use a basic sans font for consistent PDF output
            $options->set('defaultFont', 'sans-serif');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();
            return response()->streamDownload(function () use ($output) {
                echo $output;
            }, $docName, ['Content-Type' => 'application/pdf']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => 'pdf_generation_failed', 'message' => $e->getMessage()], 500);
        }
    }

    private function generatePdf($prescription, $user, $pet, $doctor): ?array
    {
        try {
            $html = $this->buildHtml($prescription, $user, $pet, $doctor);
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $output = $dompdf->output();

            $dir = 'prescriptions';
            $filename = 'prescription-'.$prescription->id.'.pdf';
            Storage::disk('public')->makeDirectory($dir);
            $path = $dir.'/'.$filename;
            Storage::disk('public')->put($path, $output);

            return [$path, $filename];
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function buildHtml($prescription, $user, $pet, $doctor): string
    {
        $clinic = $doctor?->clinic;
        $clinicName = trim((string) ($clinic->name ?? 'Snoutiq Veterinary Care'));
        $clinicAddress = trim((string) ($clinic->address ?? ''));
        $clinicCity = trim((string) ($clinic->city ?? ''));
        $clinicPhone = trim((string) ($clinic->mobile ?? ''));

        $parentName = trim((string) ($user?->name ?? 'Pet Parent'));
        $parentPhone = trim((string) ($user?->phone ?? ''));
        $parentEmail = trim((string) ($user?->email ?? ''));

        $petName = trim((string) ($pet?->name ?? 'Pet'));
        $petType = trim((string) ($pet?->pet_type ?? $pet?->type ?? ''));
        $petBreed = trim((string) ($pet?->breed ?? ''));
        $petGender = trim((string) ($pet?->pet_gender ?? $pet?->gender ?? ''));
        $petIsNeutered = $this->yesNoUnknown($pet?->is_neutered ?? null);
        $petVaccinated = $this->yesNoUnknown($pet?->vaccenated_yes_no ?? null);

        $doctorName = trim((string) ($doctor?->doctor_name ?? 'Doctor'));
        $doctorLicense = trim((string) ($doctor?->doctor_license ?? ''));

        $rxNumber = 'RX-' . str_pad((string) $prescription->id, 6, '0', STR_PAD_LEFT);
        $issuedOn = $this->formatDate($prescription->created_at, 'd M Y, h:i A');
        $followUpDate = $this->formatDate($prescription->follow_up_date, 'd M Y');
        $nextMedicineDate = $this->formatDate($prescription->next_medicine_day, 'd M Y');
        $nextVisitDate = $this->formatDate($prescription->next_visit_day, 'd M Y');

        $visitSummary = implode(' | ', array_values(array_filter([
            $this->plainText($prescription->visit_category),
            $this->plainText($prescription->case_severity),
            $this->plainText($prescription->diagnosis_status),
        ])));

        $medicationRows = $this->buildMedicationRows($prescription->medications_json);
        $consultNotes = $this->textToHtml($prescription->visit_notes ?: $prescription->content_html);
        $doctorTreatment = $this->textToHtml($prescription->doctor_treatment ?? null);
        $examNotes = $this->textToHtml($prescription->exam_notes);
        $diagnosis = $this->textToHtml($prescription->diagnosis);
        $disease = $this->textToHtml($prescription->disease_name);
        $treatment = $this->textToHtml($prescription->treatment_plan);
        $homeCare = $this->textToHtml($prescription->home_care);
        $followUpNotes = $this->textToHtml($prescription->follow_up_notes);

        $clinicLine = trim(implode(', ', array_values(array_filter([$clinicAddress, $clinicCity]))));

        $style = <<<CSS
@page { margin: 20px 24px; }
body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; line-height: 1.45; }
.header { border: 1px solid #dbe4f0; background: #f8fbff; padding: 14px 16px; margin-bottom: 14px; }
.header-table { width: 100%; border-collapse: collapse; }
.header-table td { vertical-align: top; }
.title { margin: 0; font-size: 22px; color: #123a68; }
.subtle { color: #64748b; font-size: 11px; }
.doc-chip { display: inline-block; padding: 3px 10px; border: 1px solid #9db7d9; color: #123a68; font-size: 10px; border-radius: 12px; }
.section { border: 1px solid #e2e8f0; margin-bottom: 10px; }
.section-title { background: #f1f5f9; color: #0f172a; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; padding: 7px 10px; border-bottom: 1px solid #e2e8f0; }
.section-body { padding: 10px; }
.meta-table, .med-table { width: 100%; border-collapse: collapse; }
.meta-table td { padding: 4px 4px; vertical-align: top; }
.key { color: #64748b; width: 130px; }
.value { color: #0f172a; }
.two-col { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
.two-col td { width: 50%; vertical-align: top; padding-right: 6px; }
.med-table th { background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; padding: 7px; font-size: 10px; text-transform: uppercase; }
.med-table td { border: 1px solid #e2e8f0; padding: 8px; vertical-align: top; }
.muted-row { color: #64748b; text-align: center; font-style: italic; }
.footer { margin-top: 12px; font-size: 10px; color: #64748b; }
.signature { margin-top: 24px; text-align: right; }
.signature-line { width: 220px; border-top: 1px solid #94a3b8; margin-left: auto; margin-bottom: 4px; }
CSS;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>{$style}</style>
</head>
<body>
  <div class="header">
    <table class="header-table">
      <tr>
        <td>
          <h1 class="title">Veterinary Prescription</h1>
          <div><strong>{$this->e($clinicName)}</strong></div>
          <div class="subtle">{$this->e($clinicLine !== '' ? $clinicLine : 'Address not available')}</div>
          <div class="subtle">{$this->e($clinicPhone !== '' ? $clinicPhone : 'Contact not available')}</div>
        </td>
        <td style="text-align: right;">
          <span class="doc-chip">PRESCRIPTION</span>
          <div style="margin-top: 8px;"><strong>Prescription #</strong> {$this->e($rxNumber)}</div>
          <div><strong>Issued On</strong> {$this->e($issuedOn)}</div>
          <div><strong>Call Session</strong> {$this->e($this->plainText($prescription->call_session) ?: '—')}</div>
        </td>
      </tr>
    </table>
  </div>

  <table class="two-col">
    <tr>
      <td>
        <div class="section">
          <div class="section-title">Pet Parent Details</div>
          <div class="section-body">
            <table class="meta-table">
              <tr><td class="key">Name</td><td class="value">{$this->e($parentName)}</td></tr>
              <tr><td class="key">Phone</td><td class="value">{$this->e($parentPhone !== '' ? $parentPhone : '—')}</td></tr>
              <tr><td class="key">Email</td><td class="value">{$this->e($parentEmail !== '' ? $parentEmail : '—')}</td></tr>
            </table>
          </div>
        </div>
      </td>
      <td>
        <div class="section">
          <div class="section-title">Doctor Details</div>
          <div class="section-body">
            <table class="meta-table">
              <tr><td class="key">Doctor</td><td class="value">{$this->e($doctorName)}</td></tr>
              <tr><td class="key">License</td><td class="value">{$this->e($doctorLicense !== '' ? $doctorLicense : '—')}</td></tr>
            </table>
          </div>
        </div>
      </td>
    </tr>
  </table>

  <div class="section">
    <div class="section-title">Pet Details</div>
    <div class="section-body">
      <table class="meta-table">
        <tr><td class="key">Pet Name</td><td class="value">{$this->e($petName)}</td><td class="key">Type</td><td class="value">{$this->e($petType !== '' ? $petType : '—')}</td></tr>
        <tr><td class="key">Breed</td><td class="value">{$this->e($petBreed !== '' ? $petBreed : '—')}</td><td class="key">Gender</td><td class="value">{$this->e($petGender !== '' ? $petGender : '—')}</td></tr>
        <tr><td class="key">Neutered</td><td class="value">{$this->e($petIsNeutered)}</td><td class="key">Vaccinated</td><td class="value">{$this->e($petVaccinated)}</td></tr>
        <tr><td class="key">Visit Summary</td><td class="value" colspan="3">{$this->e($visitSummary !== '' ? $visitSummary : '—')}</td></tr>
      </table>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Clinical Notes</div>
    <div class="section-body">
      <table class="meta-table">
        <tr><td class="key">Notes</td><td class="value">{$consultNotes}</td></tr>
        <tr><td class="key">Doctor Treatment</td><td class="value">{$doctorTreatment}</td></tr>
        <tr><td class="key">Examination</td><td class="value">{$examNotes}</td></tr>
        <tr><td class="key">Diagnosis</td><td class="value">{$diagnosis}</td></tr>
        <tr><td class="key">Disease</td><td class="value">{$disease}</td></tr>
        <tr><td class="key">Chronic Case</td><td class="value">{$this->e($prescription->is_chronic ? 'Yes' : 'No')}</td></tr>
        <tr><td class="key">Treatment Plan</td><td class="value">{$treatment}</td></tr>
      </table>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Medication Plan</div>
    <div class="section-body">
      <table class="med-table">
        <thead>
          <tr>
            <th style="width: 6%;">#</th>
            <th style="width: 26%;">Medicine</th>
            <th style="width: 16%;">Dosage</th>
            <th style="width: 18%;">Frequency</th>
            <th style="width: 14%;">Duration</th>
            <th style="width: 20%;">Instructions</th>
          </tr>
        </thead>
        <tbody>
          {$medicationRows}
        </tbody>
      </table>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Home Care & Follow-up</div>
    <div class="section-body">
      <table class="meta-table">
        <tr><td class="key">Home Care</td><td class="value">{$homeCare}</td></tr>
        <tr><td class="key">Next Medicine Date</td><td class="value">{$this->e($nextMedicineDate)}</td></tr>
        <tr><td class="key">Next Visit Date</td><td class="value">{$this->e($nextVisitDate)}</td></tr>
        <tr><td class="key">Follow-up Date</td><td class="value">{$this->e($followUpDate)}</td></tr>
        <tr><td class="key">Follow-up Type</td><td class="value">{$this->e($this->plainText($prescription->follow_up_type) ?: '—')}</td></tr>
        <tr><td class="key">Follow-up Notes</td><td class="value">{$followUpNotes}</td></tr>
      </table>
      <div class="signature">
        <div class="signature-line"></div>
        <div><strong>{$this->e($doctorName)}</strong></div>
        <div class="subtle">License: {$this->e($doctorLicense !== '' ? $doctorLicense : '—')}</div>
        <div class="subtle">Authorized Veterinarian</div>
      </div>
    </div>
  </div>

  <div class="footer">
    This document was generated digitally by Snoutiq. Please contact your veterinarian for any urgent concerns.
  </div>
</body>
</html>
HTML;
    }

    private function buildMedicationRows($medications): string
    {
        if (!is_array($medications) || empty($medications)) {
            return '<tr><td colspan="6" class="muted-row">No medications prescribed.</td></tr>';
        }

        $rows = '';
        $index = 1;
        foreach ($medications as $medication) {
            if (!is_array($medication)) {
                $value = $this->plainText((string) $medication);
                $rows .= '<tr>'
                    . '<td>' . $index . '</td>'
                    . '<td>' . $this->e($value !== '' ? $value : 'Medicine ' . $index) . '</td>'
                    . '<td>—</td><td>—</td><td>—</td><td>—</td>'
                    . '</tr>';
                $index++;
                continue;
            }

            $name = $this->plainText($medication['name'] ?? $medication['medicine'] ?? $medication['drug'] ?? ('Medicine ' . $index));
            $dosage = $this->plainText($medication['dosage'] ?? $medication['dose'] ?? $medication['strength'] ?? '');
            $frequency = $this->plainText($medication['frequency'] ?? $medication['timing'] ?? '');
            $duration = $this->plainText($medication['duration'] ?? $medication['days'] ?? '');
            $instructions = $this->plainText($medication['note'] ?? $medication['notes'] ?? $medication['instructions'] ?? '');

            $rows .= '<tr>'
                . '<td>' . $index . '</td>'
                . '<td>' . $this->e($name !== '' ? $name : ('Medicine ' . $index)) . '</td>'
                . '<td>' . $this->e($dosage !== '' ? $dosage : '—') . '</td>'
                . '<td>' . $this->e($frequency !== '' ? $frequency : '—') . '</td>'
                . '<td>' . $this->e($duration !== '' ? $duration : '—') . '</td>'
                . '<td>' . $this->e($instructions !== '' ? $instructions : '—') . '</td>'
                . '</tr>';
            $index++;
        }

        return $rows;
    }

    private function formatDate($value, string $format): string
    {
        if (empty($value)) {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return $this->plainText((string) $value) ?: '—';
        }
    }

    private function textToHtml(?string $text): string
    {
        $plain = $this->plainText($text);
        if ($plain === '') {
            return '—';
        }

        return nl2br($this->e($plain));
    }

    private function plainText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $decoded = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $stripped = strip_tags($decoded);
        $normalized = preg_replace("/\r\n|\r/u", "\n", $stripped);

        return trim((string) $normalized);
    }

    private function yesNoUnknown($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_int($value) || is_float($value)) {
            return ((int) $value) === 1 ? 'Yes' : 'No';
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'y', 'yes', 'true'], true)) {
            return 'Yes';
        }
        if (in_array($normalized, ['0', 'n', 'no', 'false'], true)) {
            return 'No';
        }

        return '—';
    }

    private function e(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }

    private function publicUrl(string $path): string
    {
        // If already absolute URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Try storage disk
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        // Fallback: prepend app.url/backend
        $base = rtrim((string) config('app.url'), '/');
        if (! str_ends_with($base, '/backend')) {
            $base .= '/backend';
        }
        return $base . '/' . ltrim($path, '/');
    }
}
