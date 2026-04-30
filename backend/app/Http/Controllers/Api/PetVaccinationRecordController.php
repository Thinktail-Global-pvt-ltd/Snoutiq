<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\GeminiConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PetVaccinationRecordController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('pet_vaccination_records')) {
            return response()->json([
                'success' => false,
                'message' => 'pet_vaccination_records table is missing. Please run migrations.',
            ], 500);
        }

        $filters = $request->validate([
            'pet_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = DB::table('pet_vaccination_records')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($filters['pet_id'])) {
            $query->where('pet_id', $filters['pet_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $records = $query
            ->limit((int) ($filters['limit'] ?? 50))
            ->get()
            ->map(function ($record) {
                if (isset($record->recommendations) && is_string($record->recommendations)) {
                    $decoded = json_decode($record->recommendations, true);
                    $record->recommendations = is_array($decoded) ? $decoded : $record->recommendations;
                }

                return $record;
            });

        return response()->json([
            'success' => true,
            'data' => $records,
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('pet_vaccination_records')) {
            return response()->json([
                'success' => false,
                'message' => 'pet_vaccination_records table is missing. Please run migrations.',
            ], 500);
        }

        $data = $request->validate([
            'pet_id' => ['required', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'vet_registeration_id' => ['nullable', 'integer'],
            'life_stage' => ['nullable', 'string', 'max:40'],
            'date_of_birth' => ['nullable', 'date'],
            'as_of_date' => ['nullable', 'date'],
            'age_days' => ['nullable', 'integer', 'min:0'],
            'age_weeks' => ['nullable', 'integer', 'min:0'],
            'age_months' => ['nullable', 'integer', 'min:0'],
            'age_years' => ['nullable', 'integer', 'min:0'],
            'age_display' => ['nullable', 'string', 'max:60'],
            'recommendations' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'recorded_by' => ['nullable', 'string', 'max:255'],
        ]);

        $insert = $this->filterColumns('pet_vaccination_records', [
            'pet_id' => $data['pet_id'],
            'user_id' => $data['user_id'] ?? null,
            'vet_registeration_id' => $data['vet_registeration_id'] ?? null,
            'life_stage' => $data['life_stage'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'as_of_date' => $data['as_of_date'] ?? now()->toDateString(),
            'age_days' => $data['age_days'] ?? null,
            'age_weeks' => $data['age_weeks'] ?? null,
            'age_months' => $data['age_months'] ?? null,
            'age_years' => $data['age_years'] ?? null,
            'age_display' => $data['age_display'] ?? null,
            'recommendations' => isset($data['recommendations'])
                ? json_encode($data['recommendations'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : null,
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $data['recorded_by'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = DB::table('pet_vaccination_records')->insertGetId($insert);

        return response()->json([
            'success' => true,
            'data' => DB::table('pet_vaccination_records')->where('id', $id)->first(),
        ], 201);
    }

    public function analyzeDocument(Request $request)
    {
        $data = $request->validate([
            'pet_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'document' => ['nullable', 'file', 'max:10240'],
            'image' => ['nullable', 'file', 'max:10240'],
            'document_base64' => ['nullable', 'string'],
            'mime_type' => ['nullable', 'string', 'max:120'],
            'save_record' => ['nullable', 'boolean'],
            'update_pet_payload' => ['nullable', 'boolean'],
        ]);

        [$base64, $mimeType, $fileName] = $this->resolveDocumentPayload($request, $data);
        if ($base64 === null) {
            throw ValidationException::withMessages([
                'document' => ['Upload document/image or provide document_base64.'],
            ]);
        }

        $analysis = $this->analyzeVaccinationDocumentWithGemini($base64, $mimeType);
        $analysis = $this->normalizeAnalysis($analysis);

        $recordId = null;
        if (! empty($data['save_record']) && ! empty($data['pet_id']) && Schema::hasTable('pet_vaccination_records')) {
            $recordId = $this->storeAnalysisRecord($data, $analysis, $fileName);
        }

        if (! empty($data['update_pet_payload']) && ! empty($data['pet_id']) && Schema::hasTable('pets')) {
            $this->mergeAnalysisIntoPetPayload((int) $data['pet_id'], (int) ($data['user_id'] ?? 0), $analysis);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pet_id' => $data['pet_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'record_id' => $recordId,
                'document' => [
                    'file_name' => $fileName,
                    'mime_type' => $mimeType,
                ],
                'analysis' => $analysis,
            ],
        ]);
    }

    private function analyzeVaccinationDocumentWithGemini(string $base64, string $mimeType): array
    {
        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? GeminiConfig::apiKey()));
        if ($apiKey === '') {
            return [
                'raw_text' => null,
                'vaccinations' => [],
                'next_due_vaccination' => null,
                'status' => 'unknown',
                'warnings' => ['Gemini API key is not configured.'],
            ];
        }

        $prompt = $this->vaccinationPrompt();
        $models = array_values(array_unique(array_filter([
            'gemini-2.5-flash',
            GeminiConfig::chatModel(),
            config('services.gemini.chat_model'),
            config('services.gemini.model'),
            GeminiConfig::defaultModel(),
        ])));
        $warnings = [];

        foreach ($models as $model) {
            $result = $this->callGeminiVisionApiCurl($model, $apiKey, $prompt, $base64, $mimeType);
            if (! $result['ok']) {
                $warnings[] = $result['error'];
                continue;
            }

            $text = $result['text'];
            $decoded = $this->decodeGeminiJson($text);
            if (is_array($decoded)) {
                $decoded['model'] = $model;
                return $decoded;
            }

            $warnings[] = "Gemini {$model} returned non-JSON text: " . substr(trim($text), 0, 180);
        }

        return [
            'raw_text' => null,
            'vaccinations' => [],
            'next_due_vaccination' => null,
            'status' => 'unknown',
            'warnings' => ! empty($warnings)
                ? $warnings
                : ['Gemini could not extract structured vaccination data from this document.'],
        ];
    }

    private function callGeminiVisionApiCurl(string $model, string $apiKey, string $prompt, string $base64, string $mimeType): array
    {
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $model,
            urlencode($apiKey)
        );

        $payload = json_encode([
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64]],
                    ['text' => $prompt],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'topP' => 0.8,
                'topK' => 32,
                'maxOutputTokens' => 2400,
                'responseMimeType' => 'application/json',
            ],
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 45,
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
            Log::warning('pet_vaccination_records.gemini_curl_failed', [
                'model' => $model,
                'error' => $err,
                'info' => $info,
            ]);

            return [
                'ok' => false,
                'text' => '',
                'error' => "Gemini {$model} cURL error: {$err}",
            ];
        }

        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http < 200 || $http >= 300) {
            $message = $this->extractGeminiErrorMessage($resp, $http);
            Log::warning('pet_vaccination_records.gemini_http_failed', [
                'model' => $model,
                'status' => $http,
                'message' => $message,
                'body' => substr($resp, 0, 500),
            ]);

            return [
                'ok' => false,
                'text' => '',
                'error' => "Gemini {$model} HTTP {$http}: {$message}",
            ];
        }

        $json = json_decode($resp, true);
        $text = (string) ($json['candidates'][0]['content']['parts'][0]['text'] ?? '');
        if ($text === '') {
            return [
                'ok' => false,
                'text' => '',
                'error' => "Gemini {$model} returned an empty response.",
            ];
        }

        return [
            'ok' => true,
            'text' => $text,
            'error' => null,
        ];
    }

    private function extractGeminiErrorMessage(string $body, int $status): string
    {
        $decoded = json_decode($body, true);
        if (isset($decoded['error']['message']) && $decoded['error']['message'] !== '') {
            return $decoded['error']['message'];
        }

        return "HTTP {$status}";
    }

    private function vaccinationPrompt(): string
    {
        $today = now()->toDateString();

        return <<<PROMPT
You are a veterinary vaccination-card reader. Extract vaccination records from the attached pet document/image.

Return ONLY valid JSON. No markdown, no commentary.

Use this schema:
{
  "raw_text": "short OCR summary of visible important text",
  "vaccinations": [
    {
      "date_given": "YYYY-MM-DD or null",
      "vaccine_name": "visible vaccine/product name",
      "batch_no": "visible batch/serial/lot number or null",
      "next_due": "YYYY-MM-DD or null",
      "confidence": 0.0
    }
  ],
  "next_due_vaccination": {
    "vaccine_name": "name",
    "due_date": "YYYY-MM-DD",
    "date_given": "YYYY-MM-DD or null",
    "days_until_due": 0,
    "status": "upcoming|due_today|overdue|unknown",
    "confidence": 0.0
  },
  "status": "upcoming|due_today|overdue|unknown",
  "warnings": []
}

Rules:
- Today's date is {$today}.
- Read handwritten dates carefully. In Indian vaccination cards, dates are usually DD/MM/YYYY.
- If there are multiple next-due dates, choose the earliest date that is today or in the future.
- If all visible next-due dates are in the past, choose the most recent past next-due date and mark status "overdue".
- If a year is written with two digits, infer 20YY for modern pet records.
- Do not invent vaccine names, dates, or batch numbers. Use null when not visible.
- confidence is a number between 0 and 1.
PROMPT;
    }

    private function normalizeAnalysis(array $analysis): array
    {
        $vaccinations = [];
        foreach (($analysis['vaccinations'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $nextDue = $this->normalizeDate($row['next_due'] ?? null);
            $dateGiven = $this->normalizeDate($row['date_given'] ?? null);

            $vaccinations[] = [
                'date_given' => $dateGiven,
                'vaccine_name' => $this->cleanNullableString($row['vaccine_name'] ?? null),
                'batch_no' => $this->cleanNullableString($row['batch_no'] ?? null),
                'next_due' => $nextDue,
                'confidence' => $this->normalizeConfidence($row['confidence'] ?? null),
            ];
        }

        usort($vaccinations, function (array $a, array $b) {
            return strcmp((string) ($a['next_due'] ?? '9999-12-31'), (string) ($b['next_due'] ?? '9999-12-31'));
        });

        $nextDue = $this->selectNextDueVaccination($vaccinations);

        return [
            'raw_text' => $this->cleanNullableString($analysis['raw_text'] ?? null),
            'vaccinations' => $vaccinations,
            'next_due_vaccination' => $nextDue,
            'status' => $nextDue['status'] ?? 'unknown',
            'warnings' => array_values(array_filter(array_map('strval', $analysis['warnings'] ?? []))),
            'model' => $analysis['model'] ?? null,
        ];
    }

    private function selectNextDueVaccination(array $vaccinations): ?array
    {
        $today = now()->startOfDay();
        $dated = array_values(array_filter($vaccinations, fn (array $row) => ! empty($row['next_due'])));
        if (empty($dated)) {
            return null;
        }

        $future = array_values(array_filter($dated, function (array $row) use ($today) {
            return Carbon::parse($row['next_due'])->startOfDay()->greaterThanOrEqualTo($today);
        }));

        if (! empty($future)) {
            usort($future, fn (array $a, array $b) => strcmp($a['next_due'], $b['next_due']));
            $selected = $future[0];
        } else {
            usort($dated, fn (array $a, array $b) => strcmp($b['next_due'], $a['next_due']));
            $selected = $dated[0];
        }

        $dueDate = Carbon::parse($selected['next_due'])->startOfDay();
        $days = (int) $today->diffInDays($dueDate, false);
        $status = $days < 0 ? 'overdue' : ($days === 0 ? 'due_today' : 'upcoming');

        return [
            'vaccine_name' => $selected['vaccine_name'] ?? null,
            'due_date' => $selected['next_due'],
            'date_given' => $selected['date_given'] ?? null,
            'days_until_due' => $days,
            'status' => $status,
            'confidence' => $selected['confidence'] ?? null,
        ];
    }

    private function resolveDocumentPayload(Request $request, array $data): array
    {
        $file = $request->file('document') ?: $request->file('image');
        if ($file instanceof UploadedFile) {
            if (! $file->isValid()) {
                throw ValidationException::withMessages([
                    'document' => [$file->getErrorMessage() ?: 'File upload failed.'],
                ]);
            }

            $contents = $file->get();
            if ($contents === false || $contents === null) {
                throw ValidationException::withMessages([
                    'document' => ['Unable to read uploaded file.'],
                ]);
            }

            return [
                base64_encode($contents),
                $file->getClientMimeType() ?: ($file->getMimeType() ?: 'application/octet-stream'),
                $file->getClientOriginalName(),
            ];
        }

        $base64 = trim((string) ($data['document_base64'] ?? ''));
        if ($base64 === '') {
            return [null, 'application/octet-stream', null];
        }

        $mime = trim((string) ($data['mime_type'] ?? 'image/jpeg'));
        if (preg_match('/^data:([^;]+);base64,(.+)$/s', $base64, $matches)) {
            $mime = $matches[1];
            $base64 = $matches[2];
        }

        return [$base64, $mime !== '' ? $mime : 'image/jpeg', null];
    }

    private function decodeGeminiJson(string $text): ?array
    {
        $clean = trim($text);
        $clean = preg_replace('/^\xEF\xBB\xBF/', '', $clean) ?? $clean;
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;

        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $json = $this->extractBalancedJsonObject($clean);
        if ($json === null) {
            return null;
        }

        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $repaired = preg_replace('/,\s*([}\]])/', '$1', $json) ?? $json;
        $decoded = json_decode($repaired, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function extractBalancedJsonObject(string $text): ?string
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    private function storeAnalysisRecord(array $data, array $analysis, ?string $fileName): int
    {
        $insert = $this->filterColumns('pet_vaccination_records', [
            'pet_id' => $data['pet_id'],
            'user_id' => $data['user_id'] ?? null,
            'as_of_date' => now()->toDateString(),
            'recommendations' => json_encode($analysis, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'notes' => $fileName ? "Gemini vaccination document analysis: {$fileName}" : 'Gemini vaccination document analysis',
            'recorded_by' => 'gemini_document_api',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('pet_vaccination_records')->insertGetId($insert);
    }

    private function mergeAnalysisIntoPetPayload(int $petId, int $userId, array $analysis): void
    {
        if (! Schema::hasColumn('pets', 'dog_disease_payload')) {
            return;
        }

        $pet = DB::table('pets')->where('id', $petId)->first(['id', 'user_id', 'dog_disease_payload']);
        if (! $pet) {
            return;
        }

        if ($userId > 0 && ! empty($pet->user_id) && (int) $pet->user_id !== $userId) {
            return;
        }

        $payload = [];
        if (! empty($pet->dog_disease_payload)) {
            $decoded = json_decode((string) $pet->dog_disease_payload, true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $vaccination = $payload['vaccination'] ?? [];
        if (! is_array($vaccination)) {
            $vaccination = [];
        }

        foreach (($analysis['vaccinations'] ?? []) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['vaccine_name'] ?? 'Vaccination'));
            $dateGiven = $row['date_given'] ?? null;
            $key = $this->buildGeminiVaccinationKey($row, $index);

            $vaccination[$key] = [
                'status' => $dateGiven ? 'done' : 'unknown',
                'date' => $dateGiven,
                'last_date' => $dateGiven,
                'next_due' => $row['next_due'] ?? null,
                'dose_number' => 'document_' . ((int) $index + 1),
                'vaccine_name' => $name !== '' ? $name : 'Vaccination',
                'batch_no' => $row['batch_no'] ?? null,
                'confidence' => $row['confidence'] ?? null,
                'source' => 'gemini_document_api',
            ];
        }

        $payload['vaccination'] = $vaccination;
        $payload['vaccination_document_analysis'] = [
            'next_due_vaccination' => $analysis['next_due_vaccination'] ?? null,
            'status' => $analysis['status'] ?? 'unknown',
            'updated_at' => now()->toIso8601String(),
        ];

        DB::table('pets')->where('id', $petId)->update([
            'dog_disease_payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    private function buildGeminiVaccinationKey(array $row, int $index): string
    {
        $name = $this->slugForKey((string) ($row['vaccine_name'] ?? 'vaccination'));
        $date = $this->slugForKey((string) ($row['date_given'] ?? 'unknown_date'));
        $nextDue = $this->slugForKey((string) ($row['next_due'] ?? 'no_next_due'));

        return sprintf(
            'gemini|dog|%s|document|%d|%s|%s',
            $name !== '' ? $name : 'vaccination',
            $index + 1,
            $date !== '' ? $date : 'unknown_date',
            $nextDue !== '' ? $nextDue : 'no_next_due'
        );
    }

    private function slugForKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
        $value = trim($value, '_');

        return $value;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '' || strtolower($raw) === 'null') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y', 'j-n-Y', 'j.n.Y', 'd/m/y', 'd-m-y', 'j/n/y'];
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $raw);
                if ($date !== false) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeConfidence(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $confidence = (float) $value;
        if ($confidence > 1) {
            $confidence = $confidence / 100;
        }

        return max(0, min(1, round($confidence, 2)));
    }

    private function cleanNullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || strtolower($value) === 'null' ? null : $value;
    }

    private function filterColumns(string $table, array $payload): array
    {
        return array_filter(
            $payload,
            fn (string $column) => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY
        );
    }
}
