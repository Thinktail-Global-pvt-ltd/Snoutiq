<?php

namespace App\Http\Controllers\Api\Founder;

use App\Http\Requests\Founder\AlertIndexRequest;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AlertController extends BaseController
{
    public function index(AlertIndexRequest $request): JsonResponse
    {
        $query = Alert::query();

        if ($type = $request->type()) {
            $query->where('type', $type);
        }

        $readFilter = $request->readFilter();
        if ($readFilter !== null) {
            $query->where('is_read', $readFilter);
        }

        $alerts = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->limit())
            ->appends($request->query());

        $counts = Alert::query()
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();

        $summary = [
            'total' => (int) Alert::query()->count(),
            'unread' => (int) Alert::query()->where('is_read', false)->count(),
        ];

        foreach (Alert::TYPES as $type) {
            $summary[$type] = (int) ($counts[$type] ?? 0);
        }

        return $this->success([
            'summary' => $summary,
            'alerts' => AlertResource::collection($alerts)->resolve(),
            'pagination' => [
                'currentPage' => $alerts->currentPage(),
                'totalPages' => $alerts->lastPage(),
                'totalResults' => $alerts->total(),
                'limit' => $alerts->perPage(),
                'hasNextPage' => $alerts->hasMorePages(),
                'hasPrevPage' => $alerts->currentPage() > 1,
            ],
            'filters' => [
                'type' => $request->type() ?? 'all',
                'isRead' => $readFilter,
            ],
        ]);
    }

    public function markRead(Alert $alert): JsonResponse
    {
        if (! $alert->is_read) {
            $alert->forceFill(['is_read' => true])->save();
        }

        return $this->success([
            'id' => (string) $alert->id,
            'read' => true,
            'readAt' => now()->toIso8601String(),
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        $count = Alert::query()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $this->success([
            'markedCount' => (int) $count,
            'message' => 'All alerts marked as read',
        ]);
    }
}
