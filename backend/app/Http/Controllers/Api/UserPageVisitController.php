<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class UserPageVisitController extends Controller
{
    public function enter(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'page_name' => ['required', 'string', 'max:255'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'route_path' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'metadata' => ['nullable', 'array'],
            'entered_at' => ['nullable', 'date'],
        ]);

        $visit = UserPageVisit::create([
            'user_id' => (int) $data['user_id'],
            'page_name' => $data['page_name'],
            'session_id' => $data['session_id'] ?? null,
            'route_path' => $data['route_path'] ?? null,
            'url' => $data['url'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'entered_at' => isset($data['entered_at']) ? Carbon::parse($data['entered_at']) : now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page visit started.',
            'data' => [
                'visit_id' => $visit->id,
                'user_id' => $visit->user_id,
                'page_name' => $visit->page_name,
                'entered_at' => optional($visit->entered_at)->toIso8601String(),
            ],
        ], 201);
    }

    public function exit(Request $request)
    {
        $data = $request->validate([
            'visit_id' => ['nullable', 'integer', 'exists:user_page_visits,id'],
            'user_id' => ['required_without:visit_id', 'integer', 'exists:users,id'],
            'page_name' => ['required_without:visit_id', 'string', 'max:255'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'exited_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ]);

        $visit = $this->resolveOpenVisit($data);

        if (!$visit) {
            throw ValidationException::withMessages([
                'visit_id' => 'No open page visit found for the provided details.',
            ]);
        }

        $exitedAt = isset($data['exited_at']) ? Carbon::parse($data['exited_at']) : now();
        $enteredAt = $visit->entered_at instanceof Carbon
            ? $visit->entered_at
            : Carbon::parse($visit->entered_at);

        if ($exitedAt->lessThan($enteredAt)) {
            throw ValidationException::withMessages([
                'exited_at' => 'Exit time cannot be before enter time.',
            ]);
        }

        $visit->fill([
            'exited_at' => $exitedAt,
            'duration_seconds' => (int) $enteredAt->diffInSeconds($exitedAt),
        ]);

        if (array_key_exists('metadata', $data)) {
            $visit->metadata = array_merge($visit->metadata ?? [], $data['metadata'] ?? []);
        }

        $visit->save();

        return response()->json([
            'success' => true,
            'message' => 'Page visit ended.',
            'data' => [
                'visit_id' => $visit->id,
                'user_id' => $visit->user_id,
                'page_name' => $visit->page_name,
                'entered_at' => optional($visit->entered_at)->toIso8601String(),
                'exited_at' => optional($visit->exited_at)->toIso8601String(),
                'duration_seconds' => $visit->duration_seconds,
            ],
        ]);
    }

    private function resolveOpenVisit(array $data): ?UserPageVisit
    {
        if (!empty($data['visit_id'])) {
            return UserPageVisit::query()
                ->where('id', (int) $data['visit_id'])
                ->whereNull('exited_at')
                ->first();
        }

        $query = UserPageVisit::query()
            ->where('user_id', (int) $data['user_id'])
            ->where('page_name', $data['page_name'])
            ->whereNull('exited_at');

        if (!empty($data['session_id'])) {
            $query->where('session_id', $data['session_id']);
        }

        return $query
            ->orderByDesc('entered_at')
            ->orderByDesc('id')
            ->first();
    }
}
