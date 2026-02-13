<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserObservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserObservationController extends Controller
{
    public function index(Request $request)
    {
        [$userId, $petId] = $this->resolveUserAndPet($request);
        if (!$petId && !$userId) {
            return response()->json(['success' => false, 'message' => 'pet_id or user_id is required'], 422);
        }

        // Authorization: if auth user present, enforce ownership when possible
        if ($request->user() && $userId && (int) $request->user()->id !== $userId) {
            return response()->json(['success' => false, 'message' => 'You cannot view observations for another user.'], 403);
        }

        $limit = (int) $request->query('limit', 50);
        $limit = $limit > 0 ? min($limit, 200) : 50;

        $query = UserObservation::query();
        if ($petId) {
            $query->where('pet_id', $petId);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $observations = $query
            ->orderByDesc('observed_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (UserObservation $observation) => $this->serializeObservation($observation));

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'pet_id' => $petId,
                'observations' => $observations,
            ],
        ]);
    }

    public function store(Request $request)
    {
        [$userId, $petId, $petOwnerId] = $this->resolveUserAndPet($request, true);
        if (!$petId) {
            return response()->json(['success' => false, 'message' => 'pet_id is required'], 422);
        }
        if ($request->user() && $petOwnerId && (int) $request->user()->id !== $petOwnerId) {
            return response()->json(['success' => false, 'message' => 'You cannot save observations for another user\'s pet.'], 403);
        }

        $validated = $request->validate([
            'pet_id' => ['required', 'integer'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'eating' => ['nullable', 'integer', 'between:1,5'],
            'appetite' => ['nullable', 'string', 'max:50'],
            'energy' => ['nullable', 'string', 'max:50'],
            'mood' => ['nullable', 'string', 'max:50'],
            'symptoms' => ['nullable', 'array'],
            'symptoms.*' => ['string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'timestamp' => ['nullable', 'date'],
            'image' => ['nullable', 'file', 'image', 'max:10240'],
            'file' => ['nullable', 'file', 'image', 'max:10240'],
            'image_base64' => ['nullable', 'string'],
        ]);

        $observedAt = $validated['timestamp'] ?? null;
        $blobColumnsReady = $this->observationImageBlobColumnsReady();

        $imageBlob = null;
        $imageMime = null;
        $imageName = null;

        $imageFile = $request->file('image') ?: $request->file('file');
        if ($imageFile) {
            if (!$blobColumnsReady) {
                return response()->json([
                    'success' => false,
                    'message' => 'Observation image blob columns are missing. Please run migrations.',
                ], 500);
            }

            if (!$imageFile->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image upload.',
                ], 422);
            }

            $imageBlob = $imageFile->get();
            $imageMime = $imageFile->getMimeType() ?: ($imageFile->getClientMimeType() ?: 'image/jpeg');
            $imageName = $imageFile->getClientOriginalName() ?: null;
        } elseif (!empty($validated['image_base64'])) {
            if (!$blobColumnsReady) {
                return response()->json([
                    'success' => false,
                    'message' => 'Observation image blob columns are missing. Please run migrations.',
                ], 500);
            }

            [$decodedBlob, $decodedMime] = $this->extractBlobFromDataUri((string) $validated['image_base64']);
            if (!$decodedBlob || !$decodedMime) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image_base64 data URI.',
                ], 422);
            }

            $imageBlob = $decodedBlob;
            $imageMime = $decodedMime;
            $imageName = 'observation-image';
        }

        $payload = [
            'user_id' => $petOwnerId ?: $userId,
            'pet_id' => $petId,
            'eating' => $validated['eating'] ?? null,
            'appetite' => $validated['appetite'] ?? null,
            'energy' => $validated['energy'] ?? null,
            'mood' => $validated['mood'] ?? null,
            'symptoms' => $validated['symptoms'] ?? [],
            'notes' => $validated['notes'] ?? null,
            'observed_at' => $observedAt ? Carbon::parse($observedAt) : now(),
        ];

        if ($blobColumnsReady) {
            $payload['image_blob'] = $imageBlob;
            $payload['image_mime'] = $imageMime;
            $payload['image_name'] = $imageName;
        }

        $observation = UserObservation::create($payload);

        return response()->json([
            'success' => true,
            'data' => $this->serializeObservation($observation),
        ], 201);
    }

    public function image(Request $request, UserObservation $observation)
    {
        if ($request->user() && $observation->user_id && (int) $request->user()->id !== (int) $observation->user_id) {
            return response()->json(['success' => false, 'message' => 'You cannot view another user\'s observation image.'], 403);
        }

        if (!$this->observationImageBlobColumnsReady() || empty($observation->image_blob)) {
            return response()->json([
                'success' => false,
                'message' => 'Observation image not found.',
            ], 404);
        }

        return response($observation->image_blob, 200, [
            'Content-Type' => $observation->image_mime ?: 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    protected function serializeObservation(UserObservation $observation): array
    {
        $imageBlobUrl = $this->observationImageBlobColumnsReady() && !empty($observation->image_blob)
            ? route('api.user-per-observationss.image', ['observation' => $observation->id])
            : null;

        return [
            'id' => $observation->id,
            'user_id' => $observation->user_id,
            'pet_id' => $observation->pet_id,
            'eating' => $observation->eating,
            'appetite' => $observation->appetite,
            'energy' => $observation->energy,
            'mood' => $observation->mood,
            'symptoms' => $observation->symptoms ?? [],
            'notes' => $observation->notes,
            'image_mime' => $observation->image_mime,
            'image_name' => $observation->image_name,
            'image_blob_url' => $imageBlobUrl,
            'image_url' => $imageBlobUrl,
            'timestamp' => optional($observation->observed_at)->toIso8601String(),
            'created_at' => optional($observation->created_at)->toIso8601String(),
        ];
    }

    protected function observationImageBlobColumnsReady(): bool
    {
        return Schema::hasTable('user_observations')
            && Schema::hasColumn('user_observations', 'image_blob')
            && Schema::hasColumn('user_observations', 'image_mime');
    }

    protected function extractBlobFromDataUri(string $value): array
    {
        if ($value === '' || !str_starts_with($value, 'data:image')) {
            return [null, null];
        }

        if (!preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.*)$/s', $value, $matches)) {
            return [null, null];
        }

        $mime = strtolower(trim($matches[1]));
        $rawBase64 = str_replace(' ', '+', $matches[2]);
        $decoded = base64_decode($rawBase64, true);

        if ($decoded === false) {
            return [null, null];
        }

        return [$decoded, $mime];
    }

    protected function resolveUserAndPet(Request $request, bool $fetchPet = false): array
    {
        $petId = null;
        $userId = null;
        $petOwnerId = null;

        if ($request->filled('pet_id')) {
            $petId = (int) $request->input('pet_id');
        } elseif ($request->query('pet_id')) {
            $petId = (int) $request->query('pet_id');
        }

        if ($petId && $fetchPet) {
            $ownerColumn = 'user_id';
            if (Schema::hasTable('pets') && Schema::hasColumn('pets', 'owner_id')) {
                $ownerColumn = 'owner_id';
            }

            $selectCols = ['id'];
            if (Schema::hasTable('pets')) {
                if (Schema::hasColumn('pets', 'user_id')) {
                    $selectCols[] = 'user_id';
                }
                if ($ownerColumn === 'owner_id') {
                    $selectCols[] = 'owner_id';
                }
            }

            $petRow = DB::table('pets')->select($selectCols)->where('id', $petId)->first();
            if ($petRow) {
                $petOwnerId = $petRow->user_id ?? ($petRow->owner_id ?? null);
            }
        }

        $sessionUserId = null;
        if (method_exists($request, 'hasSession') && $request->hasSession()) {
            try {
                $sessionUserId = $request->session()->get('user_id');
            } catch (\RuntimeException $e) {
                $sessionUserId = null;
            }
        }

        $candidates = [
            optional($request->user())->id,
            $sessionUserId,
            $request->header('X-Session-User'),
            $request->input('user_id'),
            $request->query('user_id'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                $userId = (int) $candidate;
                break;
            }
        }

        return [$userId, $petId, $petOwnerId];
    }
}
