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
        $title = 'Prescription';
        $parentName = $user?->name ?? 'Pet Parent';
        $petName = $pet?->name ?? 'Pet';
        $petType = $pet?->pet_type ?? $pet?->type ?? $pet?->breed ?? '';
        $doctorName = $doctor?->doctor_name ?? 'Doctor';

        $meds = [];
        if (is_array($prescription->medications_json)) {
            foreach ($prescription->medications_json as $idx => $med) {
                $label = $med['name'] ?? ('Medicine '.($idx + 1));
                $dose = $med['dosage'] ?? $med['dose'] ?? null;
                $freq = $med['frequency'] ?? null;
                $note = $med['note'] ?? null;
                $parts = array_filter([$label, $dose, $freq]);
                $meds[] = implode(' - ', $parts) . ($note ? (' ('.$note.')') : '');
            }
        }

        $style = <<<CSS
        body { font-family: DejaVu Sans, sans-serif; color: #111; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        h2 { font-size: 16px; margin: 16px 0 8px; }
        .meta { font-size: 12px; margin-bottom: 8px; }
        .card { border: 1px solid #ddd; padding: 12px; border-radius: 6px; margin-bottom: 12px; }
        .label { font-weight: 600; }
        ul { margin: 6px 0 0 18px; padding: 0; }
        CSS;

        $visit = array_filter([
            $prescription->visit_category,
            $prescription->case_severity,
        ]);

        $vitals = array_filter([
            $prescription->temperature ? ('Temp: '.$prescription->temperature.($prescription->temperature_unit ?: '')) : null,
            $prescription->weight ? ('Weight: '.$prescription->weight.' kg') : null,
            $prescription->heart_rate ? ('Heart: '.$prescription->heart_rate.' bpm') : null,
        ]);

        $follow = array_filter([
            $prescription->follow_up_date ? ('Date: '.$prescription->follow_up_date) : null,
            $prescription->follow_up_type ? ('Type: '.$prescription->follow_up_type) : null,
            $prescription->follow_up_notes ? ('Notes: '.$prescription->follow_up_notes) : null,
        ]);

        $home = $prescription->home_care ?: '';
        $notes = $prescription->visit_notes ?: $prescription->content_html ?: '';

        $medList = $meds ? '<ul><li>'.implode('</li><li>', array_map('htmlspecialchars', $meds)).'</li></ul>' : '<p>—</p>';
        $followHtml = $follow ? '<ul><li>'.implode('</li><li>', array_map('htmlspecialchars', $follow)).'</li></ul>' : '<p>—</p>';
        $vitalsHtml = $vitals ? '<ul><li>'.implode('</li><li>', array_map('htmlspecialchars', $vitals)).'</li></ul>' : '<p>—</p>';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>{$style}</style>
</head>
<body>
  <h1>{$title}</h1>
  <div class="meta">
    <div><span class="label">Pet parent:</span> {$this->e($parentName)}</div>
    <div><span class="label">Pet:</span> {$this->e($petName)} {$this->e($petType)}</div>
    <div><span class="label">Doctor:</span> {$this->e($doctorName)}</div>
  </div>

  <div class="card">
    <div class="label">Visit</div>
    <div>{$this->e(implode(' | ', $visit) ?: '—')}</div>
  </div>

  <div class="card">
    <div class="label">Notes</div>
    <div>{$this->e($notes)}</div>
  </div>

  <div class="card">
    <div class="label">Vitals</div>
    {$vitalsHtml}
  </div>

  <div class="card">
    <div class="label">Diagnosis</div>
    <div>{$this->e($prescription->diagnosis ?: '—')}</div>
  </div>

  <div class="card">
    <div class="label">Treatment plan</div>
    <div>{$this->e($prescription->treatment_plan ?: '—')}</div>
  </div>

  <div class="card">
    <div class="label">Medicines</div>
    {$medList}
  </div>

  <div class="card">
    <div class="label">Home care</div>
    <div>{$this->e($home ?: '—')}</div>
  </div>

  <div class="card">
    <div class="label">Follow-up</div>
    {$followHtml}
  </div>
</body>
</html>
HTML;
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
