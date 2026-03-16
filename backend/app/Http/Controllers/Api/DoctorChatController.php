<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Doctor;
use App\Models\DoctorChatMessage;
use App\Models\DoctorChatRoom;
use App\Models\User;
use App\Services\Push\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class DoctorChatController extends Controller
{
    public function storeRoom(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
        ]);

        $room = DoctorChatRoom::firstOrCreate([
            'user_id' => $data['user_id'],
            'doctor_id' => $data['doctor_id'],
        ]);

        $room->load([
            'user:id,name,email,phone',
            'doctor:id,doctor_name,doctor_email,doctor_mobile',
            'latestMessage',
        ]);

        return response()->json([
            'success' => true,
            'message' => $room->wasRecentlyCreated ? 'Chat room created.' : 'Chat room already exists.',
            'data' => $this->formatRoom($room),
        ], $room->wasRecentlyCreated ? 201 : 200);
    }

    public function indexRooms(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'actor_type' => ['nullable', 'string'],
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $actor = $this->resolveActor($request);
        if (!$actor) {
            if (!empty($data['user_id']) && empty($data['doctor_id'])) {
                $actor = ['type' => 'user', 'id' => (int) $data['user_id']];
            } elseif (!empty($data['doctor_id']) && empty($data['user_id'])) {
                $actor = ['type' => 'doctor', 'id' => (int) $data['doctor_id']];
            }
        }

        $query = DoctorChatRoom::query()
            ->with([
                'user:id,name,email,phone',
                'doctor:id,doctor_name,doctor_email,doctor_mobile',
                'latestMessage',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($actor) {
            if ($actor['type'] === 'user') {
                $query->where('user_id', $actor['id']);
            } else {
                $query->where('doctor_id', $actor['id']);
            }
        } else {
            if (!empty($data['user_id'])) {
                $query->where('user_id', (int) $data['user_id']);
            }
            if (!empty($data['doctor_id'])) {
                $query->where('doctor_id', (int) $data['doctor_id']);
            }
        }

        $hasAnyFilter = !empty($query->getQuery()->wheres);
        if (!$hasAnyFilter) {
            return response()->json([
                'success' => false,
                'message' => 'Provide actor token or at least one filter (user_id/doctor_id).',
            ], 422);
        }

        $limit = (int) ($data['limit'] ?? 20);
        $rooms = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $rooms->map(fn (DoctorChatRoom $room) => $this->formatRoom($room, $actor))->values(),
        ]);
    }

    public function messages(Request $request, DoctorChatRoom $room): JsonResponse
    {
        $data = $request->validate([
            'actor_type' => ['nullable', 'string'],
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'sender_type' => ['nullable', 'string'],
            'sender_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'before_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $actor = $this->resolveActor($request);
        if (!$actor) {
            return response()->json([
                'success' => false,
                'message' => 'Actor is required. Pass Bearer token or actor_type + actor_id.',
            ], 401);
        }

        if (!$this->roomBelongsToActor($room, $actor)) {
            return response()->json([
                'success' => false,
                'message' => 'Not allowed to access this room.',
            ], 403);
        }

        $limit = (int) ($data['limit'] ?? 50);

        $query = $room->messages()->orderByDesc('id');
        if (!empty($data['before_id'])) {
            $query->where('id', '<', (int) $data['before_id']);
        }

        $messages = $query->limit($limit)->get()->reverse()->values();

        return response()->json([
            'success' => true,
            'room_id' => $room->id,
            'data' => $messages->map(fn (DoctorChatMessage $message) => $this->formatMessage($message))->values(),
        ]);
    }

    public function storeMessage(Request $request, DoctorChatRoom $room): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'sender_type' => ['nullable', 'string'],
            'sender_id' => ['nullable', 'integer', 'min:1'],
            'actor_type' => ['nullable', 'string'],
            'actor_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $actor = $this->resolveActor($request);
        if (!$actor) {
            return response()->json([
                'success' => false,
                'message' => 'Sender is required. Pass Bearer token or actor_type + actor_id.',
            ], 422);
        }

        if (!$this->roomBelongsToActor($room, $actor)) {
            return response()->json([
                'success' => false,
                'message' => 'Sender is not part of this room.',
            ], 403);
        }

        $message = DoctorChatMessage::create([
            'doctor_chat_room_id' => $room->id,
            'sender_type' => $actor['type'],
            'sender_id' => $actor['id'],
            'message' => trim($data['message']),
            'read_at' => null,
        ]);

        $room->update(['last_message_at' => $message->created_at]);
        $push = $this->sendRecipientPushViaTestApi($room, $message, $actor);

        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
            'data' => $this->formatMessage($message),
            'push' => $push,
        ], 201);
    }

    public function markRead(Request $request, DoctorChatRoom $room): JsonResponse
    {
        $request->validate([
            'actor_type' => ['nullable', 'string'],
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'sender_type' => ['nullable', 'string'],
            'sender_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $actor = $this->resolveActor($request);
        if (!$actor) {
            return response()->json([
                'success' => false,
                'message' => 'Actor is required. Pass Bearer token or actor_type + actor_id.',
            ], 401);
        }

        if (!$this->roomBelongsToActor($room, $actor)) {
            return response()->json([
                'success' => false,
                'message' => 'Not allowed to modify read status for this room.',
            ], 403);
        }

        $updatedCount = $room->messages()
            ->where('sender_type', '!=', $actor['type'])
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read.',
            'updated_count' => $updatedCount,
        ]);
    }

    private function sendRecipientPushViaTestApi(DoctorChatRoom $room, DoctorChatMessage $message, array $sender): array
    {
        $recipient = $this->resolveRecipient($room, $sender);
        if (!$recipient) {
            return [
                'sent' => false,
                'reason' => 'recipient_not_found',
            ];
        }

        $tokens = $this->recipientTokens($recipient['type'], $recipient['id']);
        if (empty($tokens)) {
            return [
                'sent' => false,
                'reason' => 'token_missing',
                'recipient_type' => $recipient['type'],
                'recipient_id' => $recipient['id'],
                'token_count' => 0,
            ];
        }

        $senderName = $this->senderDisplayName($sender['type'], $sender['id']);
        $preview = Str::limit(trim((string) $message->message), 120, '...');

        $title = $sender['type'] === 'doctor'
            ? ('Message from Dr ' . $senderName)
            : 'New message from pet parent';
        $body = $sender['type'] === 'doctor'
            ? $preview
            : ($senderName . ': ' . $preview);

        $data = [
            'type' => 'doctor_chat_message',
            'room_id' => (string) $room->id,
            'message_id' => (string) $message->id,
            'sender_type' => (string) $sender['type'],
            'sender_id' => (string) $sender['id'],
            'recipient_type' => (string) $recipient['type'],
            'recipient_id' => (string) $recipient['id'],
            'created_at' => optional($message->created_at)->toIso8601String() ?? '',
        ];

        $fcm = app(FcmService::class);
        $controller = app(PushController::class);

        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($tokens as $token) {
            $pushRequest = Request::create('/api/push/test', 'POST', [
                'token' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);

            try {
                $response = $controller->testToToken($pushRequest, $fcm);
                $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 500;
                $payload = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
                $ok = $status >= 200 && $status < 300 && (bool) ($payload['sent'] ?? $payload['success'] ?? false);

                if ($ok) {
                    $successCount++;
                    continue;
                }

                $failureCount++;
                $errors[] = [
                    'status' => $status,
                    'details' => $payload['error'] ?? $payload['details'] ?? 'unknown_error',
                ];
            } catch (Throwable $e) {
                $failureCount++;
                $errors[] = [
                    'status' => 500,
                    'details' => $e->getMessage(),
                ];
            }
        }

        return [
            'sent' => $successCount > 0,
            'recipient_type' => $recipient['type'],
            'recipient_id' => $recipient['id'],
            'token_count' => count($tokens),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => array_slice($errors, 0, 5),
        ];
    }

    private function resolveRecipient(DoctorChatRoom $room, array $sender): ?array
    {
        if (($sender['type'] ?? null) === 'user') {
            return [
                'type' => 'doctor',
                'id' => (int) $room->doctor_id,
            ];
        }

        if (($sender['type'] ?? null) === 'doctor') {
            return [
                'type' => 'user',
                'id' => (int) $room->user_id,
            ];
        }

        return null;
    }

    private function recipientTokens(string $recipientType, int $recipientId): array
    {
        $ownerModel = $recipientType === 'doctor' ? Doctor::class : User::class;

        $query = DeviceToken::query()
            ->where('user_id', $recipientId)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->where(function ($inner) use ($ownerModel, $recipientType) {
                $inner->where('meta->owner_model', $ownerModel)
                    ->orWhere('meta->owner_model', $recipientType);
            })
            ->orderByRaw('COALESCE(last_seen_at, updated_at, created_at) DESC')
            ->limit(20);

        $tokens = $query->pluck('token')
            ->filter(fn ($token) => is_string($token) && trim($token) !== '')
            ->map(fn ($token) => trim((string) $token))
            ->unique()
            ->values()
            ->all();

        if (!empty($tokens) || $recipientType === 'doctor') {
            return $tokens;
        }

        // Backward compatibility for older user tokens that may not carry owner_model.
        return DeviceToken::query()
            ->where('user_id', $recipientId)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->where(function ($inner) {
                $inner->whereNull('meta')
                    ->orWhereNull('meta->owner_model');
            })
            ->orderByRaw('COALESCE(last_seen_at, updated_at, created_at) DESC')
            ->limit(20)
            ->pluck('token')
            ->filter(fn ($token) => is_string($token) && trim($token) !== '')
            ->map(fn ($token) => trim((string) $token))
            ->unique()
            ->values()
            ->all();
    }

    private function senderDisplayName(string $senderType, int $senderId): string
    {
        if ($senderType === 'doctor') {
            $name = Doctor::query()->whereKey($senderId)->value('doctor_name');
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }

            return 'Doctor';
        }

        $name = User::query()->whereKey($senderId)->value('name');
        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        return 'User';
    }

    private function resolveActor(Request $request): ?array
    {
        $tokenActor = $this->resolveActorFromBearer($request);
        if ($tokenActor) {
            return $tokenActor;
        }

        $type = strtolower((string) ($request->input('actor_type') ?? $request->input('sender_type') ?? ''));
        $id = $request->input('actor_id', $request->input('sender_id'));

        if (!in_array($type, ['user', 'doctor'], true)) {
            return null;
        }

        if (!is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        return ['type' => $type, 'id' => (int) $id];
    }

    private function resolveActorFromBearer(Request $request): ?array
    {
        $auth = $request->header('Authorization');
        if (!$auth || !preg_match('/^Bearer\\s+(.+)$/i', $auth, $matches)) {
            return null;
        }

        $hash = hash('sha256', $matches[1]);

        $user = $this->findActorByToken(User::class, $hash);
        if ($user) {
            return ['type' => 'user', 'id' => $user->id];
        }

        $doctor = $this->findActorByToken(Doctor::class, $hash);
        if ($doctor) {
            return ['type' => 'doctor', 'id' => $doctor->id];
        }

        return null;
    }

    private function findActorByToken(string $modelClass, string $hash): ?object
    {
        $model = new $modelClass();
        $table = $model->getTable();

        if (!Schema::hasColumn($table, 'api_token_hash')) {
            return null;
        }

        $query = $modelClass::query()->where('api_token_hash', $hash);

        if (Schema::hasColumn($table, 'api_token_expires_at')) {
            $query->where(function ($inner) {
                $inner->whereNull('api_token_expires_at')
                    ->orWhere('api_token_expires_at', '>', now());
            });
        }

        return $query->first(['id']);
    }

    private function roomBelongsToActor(DoctorChatRoom $room, array $actor): bool
    {
        if ($actor['type'] === 'user') {
            return (int) $room->user_id === (int) $actor['id'];
        }

        return (int) $room->doctor_id === (int) $actor['id'];
    }

    private function formatRoom(DoctorChatRoom $room, ?array $actor = null): array
    {
        $unreadCount = null;
        if ($actor) {
            $unreadCount = $room->messages()
                ->whereNull('read_at')
                ->where('sender_type', '!=', $actor['type'])
                ->count();
        }

        return [
            'id' => $room->id,
            'user' => [
                'id' => $room->user?->id,
                'name' => $room->user?->name,
                'email' => $room->user?->email,
                'phone' => $room->user?->phone,
            ],
            'doctor' => [
                'id' => $room->doctor?->id,
                'name' => $room->doctor?->doctor_name,
                'email' => $room->doctor?->doctor_email,
                'mobile' => $room->doctor?->doctor_mobile,
            ],
            'last_message_at' => optional($room->last_message_at)->toIso8601String(),
            'last_message' => $room->latestMessage ? $this->formatMessage($room->latestMessage) : null,
            'unread_count' => $unreadCount,
            'created_at' => optional($room->created_at)->toIso8601String(),
            'updated_at' => optional($room->updated_at)->toIso8601String(),
        ];
    }

    private function formatMessage(DoctorChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'room_id' => $message->doctor_chat_room_id,
            'sender_type' => $message->sender_type,
            'sender_id' => $message->sender_id,
            'message' => $message->message,
            'read_at' => optional($message->read_at)->toIso8601String(),
            'created_at' => optional($message->created_at)->toIso8601String(),
            'updated_at' => optional($message->updated_at)->toIso8601String(),
        ];
    }
}
