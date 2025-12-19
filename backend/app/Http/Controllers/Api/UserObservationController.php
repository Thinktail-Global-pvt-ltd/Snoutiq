<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserObservation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserObservationController extends Controller
{
    public function index(Request $request)
    {
        $userId = $this->resolveUserId($request);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'user_id is required',
            ], 422);
        }

        if ($request->user() && (int) $request->user()->id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot view observations for another user.',
            ], 403);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $limit = (int) $request->query('limit', 50);
        $limit = $limit > 0 ? min($limit, 200) : 50;

        $observations = UserObservation::query()
            ->where('user_id', $userId)
            ->orderByDesc('observed_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (UserObservation $observation) => $this->serializeObservation($observation));

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $userId,
                'observations' => $observations,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $userId = $this->resolveUserId($request);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'user_id is required',
            ], 422);
        }

        if ($request->user() && (int) $request->user()->id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot save observations for another user.',
            ], 403);
        }

        $validated = $request->validate([
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

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $observedAt = $validated['timestamp'] ?? null;

        $observation = UserObservation::create([
            'user_id' => $userId,
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

    protected function resolveUserId(Request $request): ?int
    {
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
                return (int) $candidate;
            }
        }

        return null;
    }
}
