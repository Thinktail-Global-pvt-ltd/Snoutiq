<?php

namespace App\Services;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\Pet;
use App\Models\Transaction;
use App\Models\User;
use App\Support\GeminiConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LeadAiMarketingPushService
{
    public function send(User $user, bool $skipExisting = false): array
    {
        $eligibility = $this->eligibility($user, $skipExisting);
        if (!$eligibility['eligible']) {
            return [
                'sent' => false,
                'reason' => $eligibility['reason'],
                'message' => $eligibility['message'],
                'notification' => null,
            ];
        }

        $pets = $this->petsForUser((int) $user->id);
        $copy = $this->generateCopy($user, $pets);

        try {
            $notification = Notification::create([
                'user_id' => (int) $user->id,
                'pet_id' => $pets->first()?->id,
                'type' => 'ai_marketing_no_payment',
                'title' => $copy['title'],
                'body' => $copy['body'],
                'payload' => [
                    'type' => 'ai_marketing_no_payment',
                    'user_id' => (string) $user->id,
                    'pet_id' => (string) ($pets->first()?->id ?? ''),
                    'pet_names' => $pets->pluck('name')->filter()->values()->all(),
                    'reported_symptoms' => $pets->pluck('reported_symptom')->filter()->values()->all(),
                    'deepLink' => 'snoutiq://videocall-appointment',
                    'deep_link' => 'snoutiq://videocall-appointment',
                    'deeplink' => 'snoutiq://videocall-appointment',
                    'source' => 'lead_management_ai_marketing_push',
                ],
                'status' => Notification::STATUS_PENDING,
                'channel' => Notification::CHANNEL_PUSH,
            ]);

            SendNotificationJob::dispatchSync($notification->id);
            $notification->refresh();

            return [
                'sent' => $notification->status === Notification::STATUS_SENT,
                'reason' => $notification->status === Notification::STATUS_SENT ? null : 'push_not_sent',
                'message' => $notification->status === Notification::STATUS_SENT
                    ? 'AI marketing push sent.'
                    : 'Push was created but not sent. Check logs/device token.',
                'notification' => $notification,
            ];
        } catch (\Throwable $e) {
            Log::error('lead_management.ai_marketing_push.failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'reason' => 'send_failed',
                'message' => 'Failed to send AI marketing push: '.$e->getMessage(),
                'notification' => null,
            ];
        }
    }

    public function eligibility(User $user, bool $skipExisting = false): array
    {
        $userId = (int) $user->id;

        if (!$user->created_at || $user->created_at->gt(now()->subDays(10))) {
            return [
                'eligible' => false,
                'reason' => 'user_not_older_than_10_days',
                'message' => 'User is not older than 10 days yet.',
            ];
        }

        if (
            Schema::hasTable('transactions')
            && Schema::hasColumn('transactions', 'user_id')
            && Schema::hasColumn('transactions', 'status')
            && Transaction::query()
                ->where('user_id', $userId)
                ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'captured'")
                ->exists()
        ) {
            return [
                'eligible' => false,
                'reason' => 'captured_payment_exists',
                'message' => 'User already has a captured payment.',
            ];
        }

        if (
            !Schema::hasTable('device_tokens')
            || !Schema::hasColumn('device_tokens', 'user_id')
            || !Schema::hasColumn('device_tokens', 'token')
        ) {
            return [
                'eligible' => false,
                'reason' => 'device_tokens_unavailable',
                'message' => 'Device token table is not available.',
            ];
        }

        $hasToken = DB::table('device_tokens')
            ->where('user_id', $userId)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->exists();

        if (!$hasToken) {
            return [
                'eligible' => false,
                'reason' => 'no_fcm_token',
                'message' => 'User has no FCM token, so push cannot be sent.',
            ];
        }

        if ($skipExisting && $this->hasExistingMarketingPush($userId)) {
            return [
                'eligible' => false,
                'reason' => 'already_sent',
                'message' => 'AI marketing push already exists for this user.',
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
            'message' => 'Eligible.',
        ];
    }

    private function hasExistingMarketingPush(int $userId): bool
    {
        if (!Schema::hasTable('notifications')) {
            return false;
        }

        return Notification::query()
            ->where('user_id', $userId)
            ->where('type', 'ai_marketing_no_payment')
            ->whereIn('status', [
                Notification::STATUS_PENDING,
                Notification::STATUS_SENT,
                Notification::STATUS_DELIVERED,
            ])
            ->exists();
    }

    private function petsForUser(int $userId): Collection
    {
        if (!Schema::hasTable('pets') || !Schema::hasColumn('pets', 'user_id')) {
            return collect();
        }

        $petColumns = ['id', 'user_id', 'name'];
        foreach (['reported_symptom', 'breed', 'pet_type', 'type'] as $column) {
            if (Schema::hasColumn('pets', $column)) {
                $petColumns[] = $column;
            }
        }

        return Pet::query()
            ->select(array_unique($petColumns))
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(5)
            ->get();
    }

    private function generateCopy(User $user, Collection $pets): array
    {
        $fallbackPetName = trim((string) ($pets->first()?->name ?? 'your pet')) ?: 'your pet';
        $fallbackSymptom = trim((string) ($pets->pluck('reported_symptom')->filter()->first() ?? 'health concern'));
        $fallback = [
            'title' => "A vet can help {$fallbackPetName}",
            'body' => "Still worried about {$fallbackPetName}'s {$fallbackSymptom}? Book a Snoutiq vet consult and get clear next steps today.",
        ];

        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? GeminiConfig::apiKey()));
        if ($apiKey === '') {
            return $fallback;
        }

        $petContext = $pets
            ->map(function (Pet $pet): array {
                return [
                    'name' => trim((string) ($pet->name ?? '')),
                    'reported_symptom' => trim((string) ($pet->reported_symptom ?? '')),
                    'breed' => trim((string) ($pet->breed ?? '')),
                    'type' => trim((string) (($pet->pet_type ?? '') ?: ($pet->type ?? ''))),
                ];
            })
            ->values()
            ->all();

        $prompt = "Create one high-converting FCM push notification for a pet parent who installed Snoutiq but has not paid after 10+ days.\n"
            ."Use the pet's reported symptom naturally. Be empathetic, specific, and urgent without sounding scary. Do not diagnose. Do not mention discounts.\n"
            ."Return only JSON with keys title and body. Limits: title <= 45 chars, body <= 135 chars.\n\n"
            ."User name: ".trim((string) ($user->name ?? 'Pet Parent'))."\n"
            ."Pets JSON: ".json_encode($petContext, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $model = GeminiConfig::chatModel() ?: GeminiConfig::defaultModel();
        $endpoint = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $model);

        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $apiKey,
                ])
                ->post($endpoint, [
                    'contents' => [[
                        'parts' => [[
                            'text' => $prompt,
                        ]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.75,
                        'maxOutputTokens' => 160,
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('lead_management.ai_marketing_push.gemini_failed', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $fallback;
            }

            $text = trim((string) $response->json('candidates.0.content.parts.0.text'));
            $decoded = $this->decodeGeminiJsonObject($text);
            $title = trim((string) ($decoded['title'] ?? ''));
            $body = trim((string) ($decoded['body'] ?? ''));

            if ($title === '' || $body === '') {
                return $fallback;
            }

            return [
                'title' => mb_substr($title, 0, 45),
                'body' => mb_substr($body, 0, 135),
            ];
        } catch (\Throwable $e) {
            Log::warning('lead_management.ai_marketing_push.gemini_exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }

    private function decodeGeminiJsonObject(string $text): array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;

        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $clean, $match)) {
            $decoded = json_decode($match[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
