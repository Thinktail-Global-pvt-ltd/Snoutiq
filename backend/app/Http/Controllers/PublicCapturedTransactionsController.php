<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\Transaction;
use App\Support\GeminiConfig;
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
            ->with(['user' => fn ($query) => $query->select(array_unique($userColumns))])
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
        $comparison = $this->decodeGeminiJson($text) ?: ['raw_text' => $text];

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
- Do not invent facts not supported by the symptom text or attached document.
- If image content is unreadable, say so in basis and lower confidence.
- This is for internal comparison only, not a final medical diagnosis.
- Keep the output concise.
PROMPT;
    }

    private function decodeGeminiJson(string $text): ?array
    {
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

    private function detectBlobMimeType(string $blob): ?string
    {
        if ($blob === '') {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($blob);

        return is_string($mime) && $mime !== '' ? $mime : null;
    }
}
