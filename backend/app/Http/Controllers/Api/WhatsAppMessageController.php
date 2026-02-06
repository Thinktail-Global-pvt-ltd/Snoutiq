<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pet;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RuntimeException;

class WhatsAppMessageController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsApp)
    {
    }

    /**
     * POST /api/whatsapp/vet-opened-case
     * Params: user_id (required), pet_id (nullable), clinic_id (nullable), vet_name (optional override), language (optional)
     * Sends PP_VET_OPENED_CASE template to pet parent.
     */
    public function vetOpenedCase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'pet_id' => ['nullable', 'integer'],
            'clinic_id' => ['nullable', 'integer'],
            'vet_name' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:10'],
        ]);

        if (!$this->whatsApp->isConfigured()) {
            return response()->json(['message' => 'WhatsApp credentials are not configured.'], 503);
        }

        $user = User::find($data['user_id']);
        if (!$user || empty($user->phone)) {
            return response()->json(['message' => 'User not found or missing phone'], 422);
        }

        // Resolve pet name
        $petName = null;
        if (!empty($data['pet_id'])) {
            $petName = Pet::where('id', $data['pet_id'])->value('name');
        }
        if (!$petName) {
            $petName = Pet::where('user_id', $user->id)->orderByDesc('id')->value('name');
        }

        // Resolve vet/clinic name
        $vetName = $data['vet_name'] ?? null;
        if (!$vetName && !empty($data['clinic_id'])) {
            $vetName = \DB::table('vet_registerations_temp')->where('id', $data['clinic_id'])->value('name');
        }
        if (!$vetName) {
            $vetName = 'Vet';
        }

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $vetName],          // {{1}} VetName
                    ['type' => 'text', 'text' => $petName ?: 'pet'], // {{2}} PetName
                ],
            ],
        ];

        try {
            $result = $this->whatsApp->sendTemplateWithResult(
                $user->phone,
                'pp_vet_opened_case',
                $components,
                $data['language'] ?? 'en'
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json([
            'message' => 'Message queued for delivery.',
            'data' => [
                'to' => $user->phone,
                'type' => 'template',
                'template' => 'pp_vet_opened_case',
                'message_id' => $result['messages'][0]['id'] ?? null,
                'vet_name' => $vetName,
                'pet_name' => $petName,
            ],
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile_number' => ['required', 'string', 'max:20'],
            'type' => ['nullable', Rule::in(['text', 'template'])],
            'message' => ['required_if:type,text', 'string', 'max:1000'],
            'template_name' => ['required_if:type,template', 'string', 'max:255'],
            'components' => ['nullable', 'array'],
            'language' => ['nullable', 'string', 'max:10'],
        ]);

        $type = $data['type'] ?? 'template';

        if (!$this->whatsApp->isConfigured()) {
            return response()->json([
                'message' => 'WhatsApp credentials are not configured.',
            ], 503);
        }

        try {
            if ($type === 'text') {
                $result = $this->whatsApp->sendTextWithResult($data['mobile_number'], $data['message']);
            } else {
                $result = $this->whatsApp->sendTemplateWithResult(
                    $data['mobile_number'],
                    $data['template_name'] ?? null,
                    $data['components'] ?? [],
                    $data['language'] ?? null
                );
            }
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'message' => 'Message queued for delivery.',
            'data' => [
                'to' => $data['mobile_number'],
                'type' => $type,
                'message_id' => $result['messages'][0]['id'] ?? null,
            ],
        ]);
    }

    public function broadcastToUsers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['nullable', Rule::in(['text', 'template'])],
            'message' => ['required_if:type,text', 'string', 'max:1000'],
            'template_name' => ['required_if:type,template', 'string', 'max:255'],
            'components' => ['nullable', 'array'],
            'language' => ['nullable', 'string', 'max:10'],
        ]);

        $type = $data['type'] ?? 'text';

        if (!$this->whatsApp->isConfigured()) {
            return response()->json([
                'message' => 'WhatsApp credentials are not configured.',
            ], 503);
        }

        $seenPhones = [];
        $result = [
            'queued' => 0,
            'skipped' => 0,
            'failed' => [],
        ];

        User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$seenPhones, &$result, $type, $data) {
                foreach ($users as $user) {
                    $phone = trim($user->phone);

                    if ($phone === '' || isset($seenPhones[$phone])) {
                        $result['skipped']++;
                        continue;
                    }

                    $seenPhones[$phone] = true;

                    try {
                        if ($type === 'text') {
                            $this->whatsApp->sendText($phone, $data['message']);
                        } else {
                            $this->whatsApp->sendTemplate(
                                $phone,
                                $data['template_name'] ?? null,
                                $data['components'] ?? [],
                                $data['language'] ?? null
                            );
                        }

                        $result['queued']++;
                    } catch (RuntimeException $e) {
                        Log::error('whatsapp.broadcast.failed', [
                            'phone' => $phone,
                            'error' => $e->getMessage(),
                        ]);
                        $result['failed'][] = $phone;
                    }
                }
            });

        return response()->json([
            'message' => 'Broadcast initiated.',
            'data' => [
                'queued' => $result['queued'],
                'failed' => $result['failed'],
                'skipped' => $result['skipped'],
                'unique_recipients' => count($seenPhones),
            ],
        ]);
    }

    /**
     * Send the New Year 2025 template to a single user, pulling variables from DB.
     */
    public function sendNewYearTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'pet_id' => ['nullable', 'integer'],
            'language' => ['nullable', 'string', 'max:10'],
        ]);

        $user = User::find($data['user_id']);
        if (!$user || empty($user->phone)) {
            return response()->json([
                'message' => 'User not found or user has no phone number.',
            ], 422);
        }

        $petName = null;
        if (!empty($data['pet_id'])) {
            $petName = Pet::where('id', $data['pet_id'])->value('name');
        }
        if (!$petName) {
            $petName = Pet::where('user_id', $user->id)->orderBy('id')->value('name');
        }

        $components = [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $user->name ?? 'Pet Parent'],
                    ['type' => 'text', 'text' => $petName ?: 'your pet'],
                ],
            ],
        ];

        try {
            $result = $this->whatsApp->sendTemplateWithResult(
                $user->phone,
                'new_year_2025',
                $components,
                $data['language'] ?? null
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'message' => 'Message queued for delivery.',
            'data' => [
                'to' => $user->phone,
                'type' => 'template',
                'message_id' => $result['messages'][0]['id'] ?? null,
                'user_id' => $user->id,
                'pet_name' => $petName,
            ],
        ]);
    }

    /**
     * Broadcast the new_year_2025 template to all users with a phone number.
     * Fills placeholders with user name and their first pet name (fallback provided).
     */
    public function broadcastNewYearTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'language' => ['nullable', 'string', 'max:10'],
            'fallback_pet_name' => ['nullable', 'string', 'max:120'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'min:1'],
        ]);

        if (!$this->whatsApp->isConfigured()) {
            return response()->json([
                'message' => 'WhatsApp credentials are not configured.',
            ], 503);
        }

        $stats = [
            'queued' => 0,
            'skipped' => 0,
            'failed' => [],
        ];

        $query = User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        if (!empty($data['user_ids'])) {
            $query->whereIn('id', $data['user_ids']);
        }

        $query->orderBy('id')
            ->chunkById(200, function ($users) use (&$stats, $data) {
                $petNames = Pet::query()
                    ->whereIn('user_id', $users->pluck('id'))
                    ->orderBy('id')
                    ->get(['user_id', 'name'])
                    ->groupBy('user_id');

                foreach ($users as $user) {
                    $phone = trim($user->phone ?? '');
                    if ($phone === '') {
                        $stats['skipped']++;
                        continue;
                    }

                    $petName = $petNames[$user->id][0]->name ?? $data['fallback_pet_name'] ?? 'your pet';
                    $components = [
                        [
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $user->name ?? 'Pet Parent'],
                                ['type' => 'text', 'text' => $petName],
                            ],
                        ],
                    ];

                    try {
                        $this->whatsApp->sendTemplate(
                            $phone,
                            'new_year_2025',
                            $components,
                            $data['language'] ?? null
                        );
                        $stats['queued']++;
                    } catch (RuntimeException $e) {
                        Log::error('whatsapp.broadcast.new_year.failed', [
                            'phone' => $phone,
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                        $stats['failed'][] = [
                            'user_id' => $user->id,
                            'phone' => $phone,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            });

        return response()->json([
            'message' => 'Broadcast initiated.',
            'data' => $stats,
        ]);
    }
}
