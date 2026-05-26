<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Support\GeminiConfig;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PublicPrescriptionDiagnosisUsersController extends Controller
{
    private const HIDDEN_PRESCRIPTION_IDS = [
        399, 400, 401, 403, 312, 309, 308, 169, 162, 161, 160, 152, 151, 121, 120,
    ];

    public function __invoke(Request $request)
    {
        $hasRequiredTables = Schema::hasTable('prescriptions') && Schema::hasTable('users');
        $diagnosisColumn = $this->diagnosisColumn();
        $hasRequiredColumns = $hasRequiredTables
            && Schema::hasColumn('prescriptions', 'user_id')
            && $diagnosisColumn !== null;

        $prescriptions = collect();
        $metrics = [
            'prescriptions' => 0,
            'unique_users' => 0,
        ];

        if ($hasRequiredColumns) {
            $query = $this->baseQuery($diagnosisColumn);

            $metrics = [
                'prescriptions' => (clone $query)->count(),
                'unique_users' => (clone $query)->distinct('user_id')->count('user_id'),
            ];

            $prescriptions = $query
                ->orderByDesc(Schema::hasColumn('prescriptions', 'created_at') ? 'created_at' : 'id')
                ->orderByDesc('id')
                ->paginate(100)
                ->withQueryString();
        }

        return view('public.prescription-diagnosis-users', [
            'prescriptions' => $prescriptions,
            'metrics' => $metrics,
            'hasRequiredTables' => $hasRequiredTables,
            'hasRequiredColumns' => $hasRequiredColumns,
            'diagnosisColumn' => $diagnosisColumn,
        ]);
    }

    public function aiAnalysis(Request $request, Prescription $prescription)
    {
        $diagnosisColumn = $this->diagnosisColumn();
        if ($diagnosisColumn === null || in_array((int) $prescription->id, self::HIDDEN_PRESCRIPTION_IDS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Prescription is not available for this report.',
            ], 404);
        }

        $prescription->loadMissing([
            'user' => fn ($query) => $query->select($this->userColumns()),
            'pet' => fn ($query) => $query->select($this->petColumns()),
            'doctor' => fn ($query) => $query->select($this->doctorColumns()),
        ]);

        $diagnosis = trim((string) ($prescription->{$diagnosisColumn} ?? ''));
        if ($diagnosis === '' || $prescription->user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Prescription diagnosis or user is missing.',
            ], 404);
        }

        $payload = $this->aiDiagnosisPayloadFor($prescription);

        return response()->json($payload, ($payload['success'] ?? false) ? 200 : 502);
    }

    public function pdf(Request $request)
    {
        $diagnosisColumn = $this->diagnosisColumn();
        abort_unless(
            Schema::hasTable('prescriptions')
            && Schema::hasTable('users')
            && Schema::hasColumn('prescriptions', 'user_id')
            && $diagnosisColumn !== null,
            404
        );

        $prescriptions = $this->baseQuery($diagnosisColumn)
            ->orderByDesc(Schema::hasColumn('prescriptions', 'created_at') ? 'created_at' : 'id')
            ->orderByDesc('id')
            ->get();

        $rows = $this->pdfRows($prescriptions, $diagnosisColumn, true);
        $html = view('public.prescription-diagnosis-users-pdf', [
            'rows' => $rows,
            'generatedAt' => now('Asia/Kolkata'),
        ])->render();
        $pdf = $this->renderPdf($html);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="prescription-ai-vs-doctor-diagnosis.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function baseQuery(string $diagnosisColumn)
    {
        $query = Prescription::query()
            ->select($this->prescriptionColumns($diagnosisColumn))
            ->whereNotIn('id', self::HIDDEN_PRESCRIPTION_IDS)
            ->whereNotNull('user_id')
            ->whereNotNull($diagnosisColumn)
            ->where($diagnosisColumn, '!=', '')
            ->whereHas('user')
            ->with([
                'user' => fn ($query) => $query->select($this->userColumns()),
            ]);

        if (Schema::hasTable('pets') && Schema::hasColumn('prescriptions', 'pet_id')) {
            $query->with(['pet' => fn ($query) => $query->select($this->petColumns())]);
        }

        if (Schema::hasTable('doctors') && Schema::hasColumn('prescriptions', 'doctor_id')) {
            $query->with(['doctor' => fn ($query) => $query->select($this->doctorColumns())]);
        }

        return $query;
    }

    private function diagnosisColumn(): ?string
    {
        if (! Schema::hasTable('prescriptions')) {
            return null;
        }

        foreach (['diagnosis', 'diagnosys'] as $column) {
            if (Schema::hasColumn('prescriptions', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function prescriptionColumns(string $diagnosisColumn): array
    {
        $columns = ['id', 'user_id', $diagnosisColumn];
        foreach ([
            'pet_id',
            'doctor_id',
            'call_session',
            'disease_name',
            'diagnosis_status',
            'created_at',
        ] as $column) {
            if (Schema::hasColumn('prescriptions', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function userColumns(): array
    {
        $columns = ['id'];
        foreach (['name', 'email', 'phone', 'city'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function petColumns(): array
    {
        $columns = ['id'];
        foreach (['user_id', 'name', 'breed', 'pet_type', 'type', 'pet_age', 'pet_gender', 'reported_symptom', 'pet_doc2_blob', 'pet_doc2_mime'] as $column) {
            if (Schema::hasColumn('pets', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function doctorColumns(): array
    {
        $columns = ['id'];
        foreach (['doctor_name', 'doctor_email', 'doctor_mobile', 'degree'] as $column) {
            if (Schema::hasColumn('doctors', $column)) {
                $columns[] = $column;
            }
        }

        return array_values(array_unique($columns));
    }

    private function reportedSymptomFor(Prescription $prescription): string
    {
        if ($prescription->pet && Schema::hasColumn('pets', 'reported_symptom')) {
            return trim((string) ($prescription->pet->reported_symptom ?? ''));
        }

        return '';
    }

    private function aiDiagnosisPayloadFor(Prescription $prescription): array
    {
        $reportedSymptom = $this->reportedSymptomFor($prescription);
        $imagePart = $this->petDoc2BlobPart($prescription);
        if ($reportedSymptom === '' && $imagePart === null) {
            return [
                'success' => true,
                'data' => [
                    'ai_diagnosis' => 'Unable to diagnose: reported symptom and pet_doc2_blob are both missing.',
                    'model' => null,
                ],
            ];
        }

        $cacheKey = 'prescription_diagnosis_users.ai_diagnosis.' . sha1(json_encode([
            'version' => 3,
            'prescription_id' => $prescription->id,
            'reported_symptom' => $reportedSymptom,
            'image_signature' => $imagePart['signature'] ?? null,
        ]));

        return Cache::remember($cacheKey, now()->addDay(), function () use ($prescription, $reportedSymptom, $imagePart) {
            return $this->generateAiDiagnosis($prescription, $reportedSymptom, $imagePart);
        });
    }

    private function pdfRows($prescriptions, string $diagnosisColumn, bool $includeAi): array
    {
        return $prescriptions->map(function (Prescription $prescription) use ($diagnosisColumn, $includeAi) {
            $payload = $includeAi ? $this->aiDiagnosisPayloadFor($prescription) : null;

            return [
                'prescription' => $prescription,
                'user' => $prescription->user,
                'pet' => $prescription->pet,
                'doctor' => $prescription->doctor,
                'doctor_diagnosis' => trim((string) ($prescription->{$diagnosisColumn} ?? '')),
                'reported_symptom' => $this->reportedSymptomFor($prescription),
                'ai_diagnosis' => is_array($payload) && ($payload['success'] ?? false)
                    ? (string) ($payload['data']['ai_diagnosis'] ?? 'AI diagnosis unavailable')
                    : 'AI diagnosis unavailable',
            ];
        })->all();
    }

    private function petDoc2BlobPart(Prescription $prescription): ?array
    {
        if (! $prescription->pet || ! Schema::hasColumn('pets', 'pet_doc2_blob')) {
            return null;
        }

        $blob = $prescription->pet->getRawOriginal('pet_doc2_blob');
        if (! is_string($blob) || $blob === '') {
            return null;
        }

        if (strlen($blob) > 8 * 1024 * 1024) {
            return null;
        }

        $mime = $this->detectBlobMimeType($blob) ?: ($prescription->pet->pet_doc2_mime ?? 'image/jpeg');
        if (! str_starts_with((string) $mime, 'image/')) {
            return null;
        }

        return [
            'mime_type' => $mime,
            'data' => base64_encode($blob),
            'signature' => strlen($blob) . ':' . sha1(substr($blob, 0, 4096)),
        ];
    }

    private function generateAiDiagnosis(Prescription $prescription, string $reportedSymptom, ?array $imagePart): array
    {
        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? GeminiConfig::apiKey()));
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'Gemini API key is not configured.',
            ];
        }

        $model = 'gemini-2.5-flash';
        $prompt = $this->aiDiagnosisPrompt($prescription, $reportedSymptom, $imagePart !== null);
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $model,
            urlencode($apiKey)
        );
        $parts = [];
        if ($imagePart !== null) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $imagePart['mime_type'],
                    'data' => $imagePart['data'],
                ],
            ];
        }
        $parts[] = ['text' => $prompt];

        $payload = json_encode([
            'contents' => [[
                'role' => 'user',
                'parts' => $parts,
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'topP' => 0.8,
                'topK' => 32,
                'maxOutputTokens' => 400,
                'responseMimeType' => 'application/json',
            ],
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            Log::warning('prescription_diagnosis_users.ai_analysis.gemini_curl_failed', [
                'prescription_id' => $prescription->id,
                'error' => $err,
                'info' => $info,
            ]);

            return [
                'success' => false,
                'message' => "Gemini {$model} cURL error: {$err}",
            ];
        }

        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http < 200 || $http >= 300) {
            Log::warning('prescription_diagnosis_users.ai_analysis.gemini_http_failed', [
                'prescription_id' => $prescription->id,
                'status' => $http,
                'body' => substr($resp, 0, 500),
            ]);

            return [
                'success' => false,
                'message' => "Gemini {$model} returned HTTP {$http}.",
            ];
        }

        $json = json_decode($resp, true);
        $text = trim((string) ($json['candidates'][0]['content']['parts'][0]['text'] ?? ''));
        $decoded = $this->decodeGeminiJson($text);

        return [
            'success' => true,
            'data' => $this->normalizeAiDiagnosis($decoded, $reportedSymptom),
        ];
    }

    private function aiDiagnosisPrompt(Prescription $prescription, string $reportedSymptom, bool $hasImage): string
    {
        $pet = $prescription->pet;
        $imageLine = $hasImage
            ? 'pet_doc2_blob: attached image is provided in this request.'
            : 'pet_doc2_blob: missing or not readable.';

        return <<<PROMPT
You are replacing the doctor's diagnosis for this internal report. Give the best-fit veterinary diagnosis using only:
1. pets.reported_symptom
2. pets.pet_doc2_blob attached image, if provided

Context:
- prescription_id: {$prescription->id}
- pet_name: {$pet?->name}
- pet_type: {$pet?->pet_type}
- pet_breed: {$pet?->breed}
- pet_age: {$pet?->pet_age}
- pet_gender: {$pet?->pet_gender}
- reported_symptom: {$reportedSymptom}
- {$imageLine}

Return valid JSON only:
{
  "ai_diagnosis": "single concise veterinary diagnosis"
}

Rules:
- Do not output N/A.
- Do not compare with the doctor's diagnosis.
- Do not explain your reasoning.
- Do not include analysis, confidence, basis, comparison, recommendations, or next steps.
- Do not invent symptoms not present in reported_symptom.
- If certainty is limited, still provide the most likely diagnosis label. Do not prefix it with cautious, possible, likely, suspected, or differential.
- Keep ai_diagnosis under 160 characters.
PROMPT;
    }

    private function normalizeAiDiagnosis(?array $decoded, string $reportedSymptom): array
    {
        $diagnosis = trim((string) ($decoded['ai_diagnosis'] ?? $decoded['diagnosis'] ?? $decoded['ai_diagnosys'] ?? ''));
        if ($diagnosis === '' || in_array(strtolower($diagnosis), ['n/a', 'na', 'unknown', 'none'], true)) {
            $diagnosis = $this->fallbackDiagnosisFromSymptom($reportedSymptom);
        }

        $diagnosis = preg_replace('/^(cautious|possible|likely|suspected)\s+(differential\s+)?(diagnosis\s*:\s*)?/i', '', $diagnosis);
        $diagnosis = preg_replace('/^differential\s*:\s*/i', '', (string) $diagnosis);

        return [
            'ai_diagnosis' => mb_substr(trim((string) $diagnosis), 0, 180),
            'model' => 'gemini-2.5-flash',
        ];
    }

    private function fallbackDiagnosisFromSymptom(string $reportedSymptom): string
    {
        $text = mb_strtolower($reportedSymptom);

        if (str_contains($text, 'anxiety') || str_contains($text, 'aggress') || str_contains($text, 'bark')) {
            return 'Canine anxiety-related behavioural disorder with escalating aggression';
        }

        if (str_contains($text, 'anal gland') || str_contains($text, 'anal sac') || str_contains($text, 'scoot')) {
            return 'Anal sac disease with anal gland swelling';
        }

        if (str_contains($text, 'vaccination record') || str_contains($text, 'vaccine record') || str_contains($text, 'vaccination update')) {
            return 'Vaccination record assessment';
        }

        if (str_contains($text, 'need to consult') || str_contains($text, 'consult')) {
            return 'Undifferentiated veterinary consultation case';
        }

        if (str_contains($text, 'vomit') || str_contains($text, 'loose stool') || str_contains($text, 'diarr') || str_contains($text, 'not eaten') || str_contains($text, 'appetite')) {
            return 'Acute gastroenteritis with anorexia';
        }

        if (str_contains($text, 'fpv') || str_contains($text, 'panleukopenia')) {
            return 'Feline panleukopenia virus exposure risk';
        }

        if (str_contains($text, 'itch') || str_contains($text, 'skin') || str_contains($text, 'rash')) {
            return 'Allergic dermatitis';
        }

        if (str_contains($text, 'limp') || str_contains($text, 'pain') || str_contains($text, 'leg')) {
            return 'Musculoskeletal pain or lameness';
        }

        if (str_contains($text, 'cough') || str_contains($text, 'sneez') || str_contains($text, 'breath')) {
            return 'Respiratory tract disease';
        }

        return $reportedSymptom !== ''
            ? 'Undifferentiated clinical presentation'
            : 'Image-based veterinary abnormality';
    }

    private function detectBlobMimeType(string $blob): ?string
    {
        if ($blob === '') {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($blob);

        return is_string($mime) && $mime !== '' ? $mime : null;
    }

    private function decodeGeminiJson(string $text): ?array
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', (string) $text);
            $text = trim((string) $text);
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
