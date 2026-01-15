<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\GeminiConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\ChatRoom;

class AiSummaryController extends Controller
{
    // GET /api/ai/summary?pet_id= (preferred) or ?user_id= (fallback)
    // Builds a 2-line Gemini summary from the pet row
    public function summary(Request $request)
    {
        $petId = (int) ($request->query('pet_id') ?? 0);
        $userId = (int) ($request->query('user_id') ?? 0);
        if ($petId <= 0 && $userId <= 0 && method_exists($request, 'hasSession') && $request->hasSession()) {
            $userId = (int) ($request->session()->get('user_id') ?? 0);
        }

        if ($petId <= 0 && $userId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Provide pet_id (preferred) or user_id to locate a pet',
            ], 422);
        }

        if ($petId > 0) {
            $pet = DB::table('pets')->where('id', $petId)->first();
        } else {
            $pet = DB::table('pets')
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->first();
        }

        if (!$pet) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found',
            ], 404);
        }

        $rawContext = $this->buildPetRowSummary($pet);
        $format = strtolower((string) $request->query('format', ''));
        $summarize = (string) $request->query('summarize', '');
        $forceRaw = $format === 'raw' || $summarize === '0';

        if ($forceRaw) {
            $summary = $rawContext;
        } else {
            $summary = $this->summarizePetWithGemini($rawContext) ?? $rawContext;
        }

        return response()->json([
            'success' => true,
            'pet_id' => $pet->id ?? $petId,
            'user_id' => $pet->user_id ?? ($userId ?: null),
            'summary' => $summary,
        ]);
    }

    // POST /api/ai/send-summary { booking_id }
    // Builds Gemini summary from chats of the booking's user and stores into bookings.ai_summary
    public function sendToDoctor(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer|min:1',
        ]);

        $booking = DB::table('bookings')->where('id', $validated['booking_id'])->first();
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $uid = (int) $booking->user_id;
        $doctorId = (int) ($booking->assigned_doctor_id ?? 0);

        // Prefer the latest chat_room for the user and pull the exact chat_room_token
        $latestRoom = ChatRoom::where('user_id', $uid)->orderByDesc('updated_at')->first();
        $q = DB::table('chats')->orderBy('id', 'desc')->limit(50);
        if ($latestRoom) {
            $q->where('chat_room_token', $latestRoom->chat_room_token);
        } else {
            $q->where('user_id', $uid);
        }
        $chats = $q->get(['question','answer']);

        $lines = [];
        foreach ($chats->reverse() as $row) {
            if (!empty($row->question)) { $lines[] = 'Q: ' . trim($row->question); }
            if (!empty($row->answer))   { $lines[] = 'A: ' . trim($row->answer); }
        }
        $transcript = trim(implode("\n", $lines));

        // Generate with Gemini
        $client = new \App\Services\Ai\GeminiClient();
        $summary = $client->summarizeTranscript($transcript);

        // Store on booking
        DB::table('bookings')->where('id', $booking->id)->update([
            'ai_summary' => $summary,
            'updated_at' => now(),
        ]);

        // Placeholder: notify doctor (extend to email/WhatsApp)
        // For now, return payload; doctors list view will display ai_summary from booking

        return response()->json([
            'success' => true,
            'booking_id' => $booking->id,
            'assigned_doctor_id' => $doctorId,
            'ai_summary' => $summary,
        ]);
    }

    private function buildPetRowSummary(object $pet): string
    {
        $lines = [];
        foreach (get_object_vars($pet) as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            }

            $value = is_string($value) ? trim($value) : $value;
            if ($value === '' || $value === null) {
                continue;
            }

            $label = ucwords(str_replace('_', ' ', (string) $key));
            $lines[] = $label . ': ' . $value;
        }

        return trim(implode("\n", $lines));
    }

    private function summarizePetWithGemini(string $petContext): ?string
    {
        $context = trim($petContext);
        if ($context === '') {
            return null;
        }

        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? GeminiConfig::apiKey()));
        if ($apiKey === '') {
            return null;
        }

        if (strlen($context) > 8000) {
            $context = substr($context, 0, 8000);
        }

        $prompt = "You are a veterinary assistant. Based ONLY on the pet record and document references below, write a concise 2-line summary for a doctor.\n" .
                  "Rules:\n" .
                  "- Return exactly two lines, each line one short sentence.\n" .
                  "- Use only provided facts; do not guess or add new details.\n" .
                  "- Mention if documents/images are available when present.\n" .
                  "Pet record:\n" . $context;

        $models = array_values(array_unique(array_filter(array_merge(
            [GeminiConfig::chatModel()],
            GeminiConfig::summaryModels(),
            [GeminiConfig::defaultModel()]
        ))));

        foreach ($models as $model) {
            $endpoint = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $model);
            $payload = [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'topP' => 0.9,
                    'topK' => 24,
                    'maxOutputTokens' => 160,
                ],
            ];

            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $apiKey,
                ])->post($endpoint, $payload);
            } catch (\Throwable $e) {
                continue;
            }

            if (!$response->successful()) {
                if ($response->status() === 404) {
                    continue;
                }
                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            $normalized = $this->normalizeTwoLineSummary((string) $text);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeTwoLineSummary(string $text): string
    {
        $clean = trim(str_replace("\r\n", "\n", $text));
        if ($clean === '') {
            return '';
        }

        $lines = array_values(array_filter(array_map('trim', preg_split("/\n+/", $clean))));
        if (count($lines) >= 2) {
            return $lines[0] . "\n" . $lines[1];
        }

        $sentenceParts = preg_split('/(?<=[.!?])\s+/', $clean);
        $sentenceParts = array_values(array_filter(array_map('trim', $sentenceParts)));
        if (count($sentenceParts) >= 2) {
            return $sentenceParts[0] . "\n" . $sentenceParts[1];
        }

        return $clean;
    }
}
