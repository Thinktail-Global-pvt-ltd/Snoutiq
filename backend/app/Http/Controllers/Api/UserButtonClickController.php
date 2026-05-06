<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserButtonClick;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserButtonClickController extends Controller
{
    public function analyze(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'page_visit_id' => ['nullable', 'integer', 'exists:user_page_visits,id'],
            'page_name' => ['required', 'string', 'max:255'],
            'button_name' => ['required', 'string', 'max:255'],
            'button_id' => ['nullable', 'string', 'max:255'],
            'button_text' => ['nullable', 'string', 'max:255'],
            'action_name' => ['nullable', 'string', 'max:255'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'route_path' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'metadata' => ['nullable', 'array'],
            'clicked_at' => ['nullable', 'date'],
        ]);

        $click = UserButtonClick::create([
            'user_id' => (int) $data['user_id'],
            'page_visit_id' => $data['page_visit_id'] ?? null,
            'page_name' => $data['page_name'],
            'button_name' => $data['button_name'],
            'button_id' => $data['button_id'] ?? null,
            'button_text' => $data['button_text'] ?? null,
            'action_name' => $data['action_name'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'route_path' => $data['route_path'] ?? null,
            'url' => $data['url'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'clicked_at' => isset($data['clicked_at']) ? Carbon::parse($data['clicked_at']) : now(),
        ]);

        $analysis = [
            'total_user_clicks' => UserButtonClick::query()
                ->where('user_id', $click->user_id)
                ->count(),
            'total_page_clicks' => UserButtonClick::query()
                ->where('page_name', $click->page_name)
                ->count(),
            'total_button_clicks_on_page' => UserButtonClick::query()
                ->where('page_name', $click->page_name)
                ->where('button_name', $click->button_name)
                ->count(),
            'user_button_clicks_on_page' => UserButtonClick::query()
                ->where('user_id', $click->user_id)
                ->where('page_name', $click->page_name)
                ->where('button_name', $click->button_name)
                ->count(),
            'top_buttons_on_page' => $this->topButtonsOnPage($click->page_name),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Button click captured.',
            'data' => [
                'click_id' => $click->id,
                'user_id' => $click->user_id,
                'page_visit_id' => $click->page_visit_id,
                'page_name' => $click->page_name,
                'button_name' => $click->button_name,
                'clicked_at' => optional($click->clicked_at)->toIso8601String(),
                'analysis' => $analysis,
            ],
        ], 201);
    }

    private function topButtonsOnPage(string $pageName)
    {
        return UserButtonClick::query()
            ->select('button_name', DB::raw('COUNT(*) as clicks'))
            ->where('page_name', $pageName)
            ->groupBy('button_name')
            ->orderByDesc('clicks')
            ->limit(5)
            ->get()
            ->map(fn (UserButtonClick $item) => [
                'button_name' => $item->button_name,
                'clicks' => (int) $item->clicks,
            ])
            ->values();
    }
}
