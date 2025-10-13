<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ChatRoom;

class AiSummaryController extends Controller
{
    // GET /api/ai/summary?user_id= (optional)
    // Builds a plain text Q/A summary from chats for the user
    public function summary(Request $request)
    {
        // Prefer user-based scope; optionally narrow to a specific chat_room_token if explicitly provided
        $uid = (int) ($request->query('user_id') ?? 0);
        if ($uid <= 0 && method_exists($request, 'hasSession') && $request->hasSession()) {
            $uid = (int) ($request->session()->get('user_id') ?? 0);
        }
        $chatRoomToken = (string) $request->query('chat_room_token', '');

        if ($uid <= 0 && !$chatRoomToken) {
            return response()->json([
                'success' => false,
                'message' => 'Provide user_id (or set in session) or chat_room_token'
            ], 422);
        }

        $limit = (int) $request->query('limit', 5);
        $q = DB::table('chats')->orderBy('id', 'desc')->limit($limit);

        // Always constrain by user when available to avoid cross-user leakage
        if ($uid > 0) {
            $q->where('user_id', $uid);
        }
        // Only filter by room if caller explicitly asked for it
        if ($chatRoomToken !== '') {
            $q->where('chat_room_token', $chatRoomToken);
        }
        $chats = $q->get(['question','answer','created_at']);

        if ($chats->isEmpty()) {
            return response()->json([
                'success' => true,
                'user_id' => $uid,
                'summary' => ''
            ]);
        }

        $lines = [];
        foreach ($chats->reverse() as $row) { // chronological
            if (!empty($row->question)) {
                $lines[] = 'Q: ' . trim($row->question);
            }
            if (!empty($row->answer)) {
                $lines[] = 'A: ' . trim($row->answer);
            }
        }
        $transcript = trim(implode("\n", $lines));

        // If format=paragraph (or summarize=1), use Gemini to turn Q/A into a paragraph summary
        $format = (string) $request->query('format', 'qa');
        $summarize = (string) $request->query('summarize', '0');
        if ($format === 'paragraph' || $summarize === '1') {
            $client = new \App\Services\Ai\GeminiClient();
            $summary = $client->summarizeTranscript($transcript) ?? $transcript;
        } else {
            $summary = $transcript;
        }

        return response()->json([
            'success' => true,
            'user_id' => $uid,
            'chat_room_token' => $chatRoomToken,
            'summary' => $summary,
            'count'   => $chats->count(),
        ]);
    }

    // POST /api/ai/send-summary { booking_id }
    // Builds Gemini summary from chats of the booking's user and stores into bookings.ai_summary
    public function sendToDoctor(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer|min:1',
        ]);

        $booking = DB::table('bookings')->where('id', $validated['booking_id'])->first();
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $uid = (int) $booking->user_id;
        $doctorId = (int) ($booking->assigned_doctor_id ?? 0);

        // Prefer the latest chat_room for the user and pull the exact chat_room_token
        $latestRoom = ChatRoom::where('user_id', $uid)->orderByDesc('updated_at')->first();
        $q = DB::table('chats')->orderBy('id', 'desc')->limit(50);
        if ($latestRoom) {
            $q->where('chat_room_token', $latestRoom->chat_room_token);
        } else {
            $q->where('user_id', $uid);
        }
        $chats = $q->get(['question','answer']);

        $lines = [];
        foreach ($chats->reverse() as $row) {
            if (!empty($row->question)) { $lines[] = 'Q: ' . trim($row->question); }
            if (!empty($row->answer))   { $lines[] = 'A: ' . trim($row->answer); }
        }
        $transcript = trim(implode("\n", $lines));

        // Generate with Gemini
        $client = new \App\Services\Ai\GeminiClient();
        $summary = $client->summarizeTranscript($transcript);

        // Store on booking
        DB::table('bookings')->where('id', $booking->id)->update([
            'ai_summary' => $summary,
            'updated_at' => now(),
        ]);

        // Placeholder: notify doctor (extend to email/WhatsApp)
        // For now, return payload; doctors list view will display ai_summary from booking

        return response()->json([
            'success' => true,
            'booking_id' => $booking->id,
            'assigned_doctor_id' => $doctorId,
            'ai_summary' => $summary,
        ]);
    }
}
