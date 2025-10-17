<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Video;

use App\Http\Controllers\Controller;
use App\Services\SlotPublisherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function publish(Request $request, SlotPublisherService $publisher): JsonResponse
    {
        // Optional date=YYYY-MM-DD (IST)
        $date = (string) $request->query('date', '');
        $target = $date !== '' ? Carbon::createFromFormat('Y-m-d', $date, 'Asia/Kolkata') : Carbon::now('Asia/Kolkata')->startOfDay();
        $publisher->publishNightSlots($target);
        return response()->json(['status' => 'ok', 'published_for' => $target->toDateString()]);
    }

    // POST /api/video/admin/reset
    public function reset(): JsonResponse
    {
        DB::transaction(function () {
            // Clear commitments first to avoid FK issues, then slots
            try { DB::table('doctor_commitments')->delete(); } catch (\Throwable $e) { /* ignore table-missing */ }
            try { DB::table('video_slots')->delete(); } catch (\Throwable $e) { /* ignore table-missing */ }
        });
        return response()->json(['status' => 'ok', 'message' => 'Video slots and commitments reset']);
    }
}
