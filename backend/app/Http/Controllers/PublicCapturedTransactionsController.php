<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\Transaction;
use App\Support\GeminiConfig;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PublicCapturedTransactionsController extends Controller
{
    public function __invoke(Request $request)
    {
        $hasRequiredTables = Schema::hasTable('transactions') && Schema::hasTable('users');
        $hasRequiredColumns = $hasRequiredTables
            && Schema::hasColumn('transactions', 'user_id')
            && Schema::hasColumn('transactions', 'status')
            && Schema::hasColumn('transactions', 'amount_paise');

        $transactions = collect();
        $metrics = [
            'total_transactions' => 0,
            'total_amount_paise' => 0,
            'unique_users' => 0,
        ];

        if ($hasRequiredColumns) {
            $userColumns = ['id'];
            foreach (['name', 'email', 'phone'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $userColumns[] = $column;
                }
            }

            $query = Transaction::query()
                ->with(['user' => fn ($query) => $query->select(array_unique($userColumns))])
                ->where('status', 'captured')
                ->where('amount_paise', '!=', 100)
                ->whereNotNull('user_id')
                ->whereHas('user')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            if ($this->canLoadPrescriptionDiagnoses()) {
                $diagnosisColumn = $this->prescriptionDiagnosisColumn();
                $query->with(['prescriptions' => function ($query) use ($diagnosisColumn) {
                    $query->select(array_unique(array_filter([
                        'id',
                        'user_id',
                        'pet_id',
                        'call_session',
                        $diagnosisColumn,
                        Schema::hasColumn('prescriptions', 'disease_name') ? 'disease_name' : null,
                        'created_at',
                    ])))->orderByDesc('created_at')->orderByDesc('id');
                }]);
            }

            $metricsQuery = clone $query;

            $metrics = [
                'total_transactions' => (clone $metricsQuery)->count(),
                'total_amount_paise' => (int) (clone $metricsQuery)->sum('amount_paise'),
                'unique_users' => (clone $metricsQuery)->distinct('user_id')->count('user_id'),
            ];

            $transactions = $query->paginate(100)->withQueryString();
        }

        return view('public.captured-transactions', [
            'transactions' => $transactions,
            'metrics' => $metrics,
            'hasRequiredTables' => $hasRequiredTables,
            'hasRequiredColumns' => $hasRequiredColumns,
        ]);
    }

    public function diagnosisReport(Request $request)
    {
        $transactionIds = [855, 866];
        $hasRequiredTables = Schema::hasTable('transactions') && Schema::hasTable('users');
        $hasRequiredColumns = $hasRequiredTables
            && Schema::hasColumn('transactions', 'user_id')
            && Schema::hasColumn('transactions', 'amount_paise');

        $transactions = collect();
        $metrics = [
            'total_transactions' => 0,
            'total_amount_paise' => 0,
            'unique_users' => 0,
        ];

        if ($hasRequiredColumns) {
            $transactions = $this->baseTransactionReportQuery()
                ->whereIn('id', $transactionIds)
                ->get()
                ->sortBy(fn ($transaction) => array_search((int) $transaction->id, $transactionIds, true))
                ->values();

            $metrics = [
                'total_transactions' => $transactions->count(),
                'total_amount_paise' => (int) $transactions->sum('amount_paise'),
                'unique_users' => $transactions->pluck('user_id')->filter()->unique()->count(),
            ];
        }
        $reportRows = $this->diagnosisReportRows($transactions, false);

        return view('public.diagnosis-comparison-report', [
            'transactions' => new LengthAwarePaginator(
                $transactions,
                $transactions->count(),
                max(1, $transactions->count()),
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            ),
            'metrics' => $metrics,
            'hasRequiredTables' => $hasRequiredTables,
            'hasRequiredColumns' => $hasRequiredColumns,
            'reportRows' => $reportRows,
        ]);
    }

    public function diagnosisReportPdf(Request $request)
    {
        $transactionIds = [855, 866];
        abort_unless(Schema::hasTable('transactions') && Schema::hasTable('users'), 404);

        $transactions = $this->baseTransactionReportQuery()
            ->whereIn('id', $transactionIds)
            ->get()
            ->sortBy(fn ($transaction) => array_search((int) $transaction->id, $transactionIds, true))
            ->values();

        $rows = $this->diagnosisReportRows($transactions, true);
        $html = view('public.diagnosis-comparison-report-pdf', [
            'rows' => $rows,
            'generatedAt' => now('Asia/Kolkata'),
        ])->render();
        $pdf = $this->renderPdf($html);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="diagnosis-comparison-855-866.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function diagnosisComparison(Request $request, Transaction $transaction)
    {
        if (! $this->transactionCanBeCompared($transaction)) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not available for this diagnosis report.',
            ], 404);
        }

        $prescriptions = $this->prescriptionsForTransaction($transaction);
        $pet = $this->resolvePetForTransaction($transaction);

        if (! $pet) {
            return response()->json([
                'success' => false,
                'message' => 'No related pet found for this transaction user.',
            ], 404);
        }

        $diagnosisColumn = $this->prescriptionDiagnosisColumn();
        $prescriptionDiagnoses = $prescriptions
            ->map(fn ($prescription) => trim((string) ($prescription->{$diagnosisColumn} ?? $prescription->diagnosis ?? '')))
            ->filter()
            ->values();

        $reportedSymptom = Schema::hasColumn('pets', 'reported_symptom')
            ? trim((string) ($pet->reported_symptom ?? ''))
            : '';

        $imageParts = $this->geminiImagePartsForPet($pet);
        if ($reportedSymptom === '' && empty($imageParts)) {
            return response()->json([
                'success' => false,
                'message' => 'No reported symptom or pet document image is available for AI diagnosis.',
            ], 422);
        }

        $cacheKey = 'captured_transactions.diagnosis_comparison.' . sha1(json_encode([
            'normalizer_version' => 2,
            'transaction_id' => $transaction->id,
            'pet_id' => $pet->id,
            'symptom' => $reportedSymptom,
            'prescriptions' => $prescriptionDiagnoses->all(),
            'image_signatures' => array_map(fn ($part) => $part['signature'], $imageParts),
        ]));

        $payload = Cache::remember($cacheKey, now()->addDay(), function () use ($transaction, $pet, $reportedSymptom, $prescriptionDiagnoses, $imageParts) {
            return $this->generateDiagnosisComparison(
                $transaction,
                $pet,
                $reportedSymptom,
                $prescriptionDiagnoses->all(),
                $imageParts
            );
        });

        return response()->json($payload, ($payload['success'] ?? false) ? 200 : 502);
    }

    private function canLoadPrescriptionDiagnoses(): bool
    {
        return Schema::hasTable('prescriptions')
            && Schema::hasColumn('transactions', 'channel_name')
            && Schema::hasColumn('prescriptions', 'call_session')
            && $this->prescriptionDiagnosisColumn() !== null;
    }

    private function baseTransactionReportQuery()
    {
        $userColumns = ['id'];
        foreach (['name', 'email', 'phone'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $userColumns[] = $column;
            }
        }

        $query = Transaction::query()
            ->with([
                'user' => fn ($query) => $query->select(array_unique($userColumns)),
                'doctor' => fn ($query) => $query->select($this->doctorColumns()),
            ])
            ->whereNotNull('user_id')
            ->whereHas('user');

        if ($this->canLoadPrescriptionDiagnoses()) {
            $diagnosisColumn = $this->prescriptionDiagnosisColumn();
            $query->with(['prescriptions' => function ($query) use ($diagnosisColumn) {
                $query->select(array_unique(array_filter([
                    'id',
                    'user_id',
                    'pet_id',
                    'call_session',
                    $diagnosisColumn,
                    Schema::hasColumn('prescriptions', 'disease_name') ? 'disease_name' : null,
                    'created_at',
                ])))->orderByDesc('created_at')->orderByDesc('id');
            }]);
        }

        return $query;
    }

    private function doctorColumns(): array
    {
        $columns = ['id'];
        foreach (['doctor_name', 'doctor_email', 'doctor_mobile', 'degree', 'doctor_license'] as $column) {
            if (Schema::hasColumn('doctors', $column)) {
                $columns[] = $column;
            }
        }

        return array_unique($columns);
    }

    private function diagnosisReportRows($transactions, bool $includeAi): array
    {
        $diagnosisColumn = $this->prescriptionDiagnosisColumn();

        return $transactions->map(function (Transaction $transaction) use ($diagnosisColumn, $includeAi) {
            $pet = $this->resolvePetForTransaction($transaction);
            $prescriptions = $transaction->relationLoaded('prescriptions')
                ? $transaction->prescriptions
                : $this->prescriptionsForTransaction($transaction);
            $doctorDiagnoses = $diagnosisColumn
                ? $prescriptions
                    ->map(fn ($prescription) => trim((string) ($prescription->{$diagnosisColumn} ?? '')))
                    ->filter()
                    ->values()
                    ->all()
                : [];
            $imageParts = $pet ? $this->geminiImagePartsForPet($pet) : [];
            $reportedSymptom = $pet && Schema::hasColumn('pets', 'reported_symptom')
                ? trim((string) ($pet->reported_symptom ?? ''))
                : '';
            $comparisonPayload = null;

            if ($includeAi && $pet && ($reportedSymptom !== '' || ! empty($imageParts))) {
                $comparisonPayload = $this->generateDiagnosisComparison(
                    $transaction,
                    $pet,
                    $reportedSymptom,
                    $doctorDiagnoses,
                    $imageParts
                );
            }

            return [
                'transaction' => $transaction,
                'user' => $transaction->user,
                'doctor' => $transaction->doctor,
                'pet' => $pet,
                'doctor_diagnoses' => $doctorDiagnoses,
                'reported_symptom' => $reportedSymptom,
                'image_documents' => $this->imageDocumentsForReport($imageParts),
                'comparison_payload' => $comparisonPayload,
            ];
        })->all();
    }

    private function imageDocumentsForReport(array $imageParts): array
    {
        return array_map(function (array $part) {
            return [
                'label' => $part['column'] === 'pet_doc2_blob_new' ? 'Latest uploaded pet image/report' : 'Original pet image/report',
                'column' => $part['column'],
                'mime_type' => $part['mime_type'],
                'data_uri' => str_starts_with((string) $part['mime_type'], 'image/')
                    ? 'data:' . $part['mime_type'] . ';base64,' . $part['data']
                    : null,
            ];
        }, $imageParts);
    }

    private function prescriptionDiagnosisColumn(): ?string
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

    private function transactionIsVisibleOnReport(Transaction $transaction): bool
    {
        return strtolower(trim((string) ($transaction->status ?? ''))) === 'captured'
            && (int) ($transaction->amount_paise ?? 0) !== 100
            && ! empty($transaction->user_id)
            && Schema::hasTable('users')
            && \App\Models\User::query()->whereKey($transaction->user_id)->exists();
    }

    private function transactionCanBeCompared(Transaction $transaction): bool
    {
        return ! empty($transaction->user_id)
            && Schema::hasTable('users')
            && \App\Models\User::query()->whereKey($transaction->user_id)->exists();
    }

    private function prescriptionsForTransaction(Transaction $transaction)
    {
        if (! $this->canLoadPrescriptionDiagnoses()) {
            return collect();
        }

        $channelName = trim((string) ($transaction->channel_name ?? ''));
        if ($channelName === '') {
            return collect();
        }

        $diagnosisColumn = $this->prescriptionDiagnosisColumn();

        return \App\Models\Prescription::query()
            ->select(array_unique(array_filter([
                'id',
                'user_id',
                'pet_id',
                'call_session',
                $diagnosisColumn,
                Schema::hasColumn('prescriptions', 'disease_name') ? 'disease_name' : null,
                'created_at',
            ])))
            ->where('call_session', $channelName)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    private function resolvePetForTransaction(Transaction $transaction): ?Pet
    {
        if (! Schema::hasTable('pets') || ! Schema::hasColumn('pets', 'user_id')) {
            return null;
        }

        $columns = ['id', 'user_id'];
        foreach ([
            'name',
            'breed',
            'pet_type',
            'type',
            'pet_age',
            'pet_age_months',
            'pet_gender',
            'reported_symptom',
            'pet_doc2_blob',
            'pet_doc2_blob_new',
            'pet_doc2_mime',
            'updated_at',
        ] as $column) {
            if (Schema::hasColumn('pets', $column)) {
                $columns[] = $column;
            }
        }

        $query = Pet::query()->select(array_unique($columns));

        if (Schema::hasColumn('transactions', 'pet_id') && ! empty($transaction->pet_id)) {
            $pet = (clone $query)
                ->whereKey($transaction->pet_id)
                ->where('user_id', $transaction->user_id)
                ->first();
            if ($pet) {
                return $pet;
            }
        }

        return $query
            ->where('user_id', $transaction->user_id)
            ->orderByRaw(Schema::hasColumn('pets', 'reported_symptom') ? 'reported_symptom IS NULL, reported_symptom = ""' : 'id DESC')
            ->orderByDesc('id')
            ->first();
    }

    private function geminiImagePartsForPet(Pet $pet): array
    {
        $parts = [];

        foreach (['pet_doc2_blob_new', 'pet_doc2_blob'] as $column) {
            if (! Schema::hasColumn('pets', $column)) {
                continue;
            }

            $blob = $pet->getRawOriginal($column);
            if (! is_string($blob) || $blob === '') {
                continue;
            }

            if (strlen($blob) > 8 * 1024 * 1024) {
                continue;
            }

            $mime = $this->detectBlobMimeType($blob) ?: ($pet->pet_doc2_mime ?? 'image/jpeg');
            if (! str_starts_with((string) $mime, 'image/') && $mime !== 'application/pdf') {
                continue;
            }

            $parts[] = [
                'column' => $column,
                'mime_type' => $mime,
                'data' => base64_encode($blob),
                'signature' => $column . ':' . strlen($blob) . ':' . sha1(substr($blob, 0, 4096)),
            ];
        }

        return $parts;
    }

    private function generateDiagnosisComparison(Transaction $transaction, Pet $pet, string $reportedSymptom, array $prescriptionDiagnoses, array $imageParts): array
    {
        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? GeminiConfig::apiKey()));
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'Gemini API key is not configured.',
            ];
        }

        $model = 'gemini-2.5-flash';
        $prompt = $this->diagnosisComparisonPrompt($transaction, $pet, $reportedSymptom, $prescriptionDiagnoses, $imageParts);
        $parts = array_map(fn ($part) => [
            'inline_data' => [
                'mime_type' => $part['mime_type'],
                'data' => $part['data'],
            ],
        ], $imageParts);
        $parts[] = ['text' => $prompt];

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $model,
            urlencode($apiKey)
        );

        $payload = json_encode([
            'contents' => [[
                'role' => 'user',
                'parts' => $parts,
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'topP' => 0.8,
                'topK' => 32,
                'maxOutputTokens' => 1600,
                'responseMimeType' => 'application/json',
            ],
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 50,
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
            Log::warning('captured_transactions.diagnosis_comparison.gemini_curl_failed', [
                'transaction_id' => $transaction->id,
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
            Log::warning('captured_transactions.diagnosis_comparison.gemini_http_failed', [
                'transaction_id' => $transaction->id,
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
        $comparison = $this->normalizeDiagnosisComparison(
            $this->decodeGeminiJson($text),
            $text,
            $reportedSymptom,
            $prescriptionDiagnoses,
            $imageParts
        );

        return [
            'success' => true,
            'model' => $model,
            'transaction_id' => $transaction->id,
            'pet' => [
                'id' => $pet->id,
                'name' => $pet->name ?? null,
                'breed' => $pet->breed ?? null,
                'type' => $pet->pet_type ?? $pet->type ?? null,
                'age' => $pet->pet_age ?? null,
                'gender' => $pet->pet_gender ?? null,
            ],
            'reported_symptom' => $reportedSymptom,
            'prescription_diagnoses' => array_values($prescriptionDiagnoses),
            'documents_used' => array_map(fn ($part) => $part['column'], $imageParts),
            'comparison' => $comparison,
        ];
    }

    private function diagnosisComparisonPrompt(Transaction $transaction, Pet $pet, string $reportedSymptom, array $prescriptionDiagnoses, array $imageParts): string
    {
        $prescriptionJson = json_encode(array_values($prescriptionDiagnoses), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $documentColumns = json_encode(array_map(fn ($part) => $part['column'], $imageParts), JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You are a veterinary clinical assistant reviewing one completed Snoutiq transaction.

Known context:
- transaction_id: {$transaction->id}
- pet_id: {$pet->id}
- pet_name: {$pet->name}
- pet_type: {$pet->pet_type}
- pet_breed: {$pet->breed}
- pet_age: {$pet->pet_age}
- pet_gender: {$pet->pet_gender}
- reported_symptom: {$reportedSymptom}
- prescription_diagnoses: {$prescriptionJson}
- attached_pet_document_columns: {$documentColumns}

Use the reported symptom and attached pet document image(s) to infer an AI diagnosis or differential diagnosis. Then compare it with the prescription diagnosis.

Return valid JSON only using this schema:
{
  "ai_diagnosis": "short diagnosis or differential",
  "confidence": "low|medium|high",
  "basis": ["short evidence points"],
  "prescription_diagnosis": "combined prescription diagnosis text or null",
  "match_status": "matches|partially_matches|differs|insufficient_data",
  "comparison_summary": "brief plain-English comparison",
  "recommended_review": "short note on whether a vet should review the difference"
}

Rules:
- ai_diagnosis must never be "N/A", "NA", "unknown", empty, or null.
- Use the exact JSON key "ai_diagnosis"; do not use "ai_diagnosys".
- If evidence is limited, provide a cautious differential such as "limited-evidence differential: wellness consult / exposure risk assessment" using available context.
- Do not invent facts not supported by the symptom text or attached document.
- If image content is unreadable, say so in basis and lower confidence.
- This is for internal comparison only, not a final medical diagnosis.
- Keep the output concise.
PROMPT;
    }

    private function normalizeDiagnosisComparison(?array $decoded, string $rawText, string $reportedSymptom, array $prescriptionDiagnoses, array $imageParts): array
    {
        $comparison = is_array($decoded) ? $decoded : [];
        $rawText = trim($rawText);
        $doctorDiagnosis = trim(implode('; ', array_filter(array_map('strval', $prescriptionDiagnoses))));
        $fallbackDiagnosis = $this->fallbackAiDiagnosis($reportedSymptom, $doctorDiagnosis, $imageParts, $rawText);

        $aiDiagnosis = trim((string) ($comparison['ai_diagnosis'] ?? $comparison['ai_diagnosys'] ?? $comparison['diagnosis'] ?? ''));
        if ($this->isEmptyAiValue($aiDiagnosis)) {
            $aiDiagnosis = $fallbackDiagnosis;
        }

        $basis = $comparison['basis'] ?? null;
        if (! is_array($basis)) {
            $basis = [];
        }
        $basis = array_values(array_filter(array_map(
            fn ($value) => trim((string) $value),
            $basis
        ), fn ($value) => ! $this->isEmptyAiValue($value)));
        if (empty($basis)) {
            if ($reportedSymptom !== '') {
                $basis[] = 'Reported symptom/context: ' . $reportedSymptom;
            }
            if (! empty($imageParts)) {
                $basis[] = 'Pet image/report was available for visual context.';
            }
        if ($rawText !== '' && ! $this->isEmptyAiValue($rawText) && ! $this->looksLikeJsonPayload($rawText)) {
            $basis[] = 'Gemini returned unstructured text; summarized as a cautious differential.';
        }
        }

        $comparisonSummary = trim((string) ($comparison['comparison_summary'] ?? ''));
        if ($this->isEmptyAiValue($comparisonSummary)) {
            $comparisonSummary = $doctorDiagnosis !== ''
                ? "AI impression: {$aiDiagnosis}. Doctor diagnosis: {$doctorDiagnosis}."
                : "AI impression: {$aiDiagnosis}. No doctor diagnosis was available for direct matching.";
        }

        $matchStatus = strtolower(trim((string) ($comparison['match_status'] ?? '')));
        if (! in_array($matchStatus, ['matches', 'partially_matches', 'differs', 'insufficient_data'], true)) {
            $matchStatus = $doctorDiagnosis !== '' ? 'partially_matches' : 'insufficient_data';
        }

        return [
            'ai_diagnosis' => $aiDiagnosis,
            'confidence' => $this->normalizeConfidence($comparison['confidence'] ?? null),
            'basis' => $basis,
            'prescription_diagnosis' => $doctorDiagnosis !== '' ? $doctorDiagnosis : null,
            'match_status' => $matchStatus,
            'comparison_summary' => $comparisonSummary,
            'recommended_review' => trim((string) ($comparison['recommended_review'] ?? '')) ?: 'A veterinarian should review this AI impression before any clinical use.',
        ];
    }

    private function fallbackAiDiagnosis(string $reportedSymptom, string $doctorDiagnosis, array $imageParts, string $rawText): string
    {
        if ($rawText !== '' && ! $this->isEmptyAiValue($rawText) && ! $this->looksLikeJsonPayload($rawText)) {
            return mb_substr(preg_replace('/\s+/', ' ', $rawText), 0, 220);
        }

        $doctorLower = mb_strtolower($doctorDiagnosis);
        if (str_contains($doctorLower, 'fpv') || str_contains($doctorLower, 'panleukopenia')) {
            return 'Limited-evidence differential: feline panleukopenia virus exposure risk assessment';
        }

        if (str_contains($doctorLower, 'positive') && str_contains($doctorLower, 'cat')) {
            return 'Limited-evidence differential: infectious exposure risk assessment';
        }

        $symptom = mb_strtolower(trim($reportedSymptom));
        if ($symptom !== '' && ! in_array($symptom, ['need to consult', 'consult', 'need consult', 'need consultation'], true)) {
            return 'Cautious differential based on reported symptoms: ' . mb_substr($reportedSymptom, 0, 160);
        }

        if ($doctorDiagnosis !== '') {
            return 'Limited-evidence AI impression: review for ' . mb_substr($doctorDiagnosis, 0, 160);
        }

        if (! empty($imageParts)) {
            return 'Limited-evidence AI impression: visual pet wellness review from uploaded image/report';
        }

        return 'Limited-evidence AI impression: veterinary consultation recommended for further assessment';
    }

    private function looksLikeJsonPayload(string $value): bool
    {
        $trimmed = ltrim($value);

        return str_starts_with($trimmed, '{')
            || str_starts_with($trimmed, '[')
            || str_starts_with($trimmed, '```');
    }

    private function normalizeConfidence($value): string
    {
        $confidence = strtolower(trim((string) $value));

        return in_array($confidence, ['low', 'medium', 'high'], true) ? $confidence : 'low';
    }

    private function isEmptyAiValue(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return $normalized === ''
            || in_array($normalized, ['n/a', 'na', 'n.a.', 'none', 'null', 'unknown', 'not available'], true);
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

        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
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

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
