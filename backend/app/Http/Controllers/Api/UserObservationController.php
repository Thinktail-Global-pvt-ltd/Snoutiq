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
        ]);

        $observedAt = $validated['timestamp'] ?? null;

        $observation = UserObservation::create([
            'user_id' => $petOwnerId ?: $userId,
            'pet_id' => $petId,
            'eating' => $validated['eating'] ?? null,
            'appetite' => $validated['appetite'] ?? null,
            'energy' => $validated['energy'] ?? null,
            'mood' => $validated['mood'] ?? null,
            'symptoms' => $validated['symptoms'] ?? [],
            'notes' => $validated['notes'] ?? null,
            'observed_at' => $observedAt ? Carbon::parse($observedAt) : now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->serializeObservation($observation),
        ], 201);
    }

    protected function serializeObservation(UserObservation $observation): array
    {
        return [
            'id' => $observation->id,
            'user_id' => $observation->user_id,
            'eating' => $observation->eating,
            'appetite' => $observation->appetite,
            'energy' => $observation->energy,
            'mood' => $observation->mood,
            'symptoms' => $observation->symptoms ?? [],
            'notes' => $observation->notes,
            'timestamp' => optional($observation->observed_at)->toIso8601String(),
            'created_at' => optional($observation->created_at)->toIso8601String(),
        ];
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
