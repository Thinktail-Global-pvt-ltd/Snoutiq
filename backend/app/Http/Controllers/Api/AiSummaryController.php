<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ChatRoom;

class AiSummaryController extends Controller
{
    // GET /api/ai/summary?pet_id= (preferred) or ?user_id= (fallback)
    // Builds a plain text summary from the pet row
    public function summary(Request $request)
    {
        $petId = (int) ($request->query('pet_id') ?? 0);
        $userId = (int) ($request->query('user_id') ?? 0);
        if ($petId <= 0 && $userId <= 0 && method_exists($request, 'hasSession') && $request->hasSession()) {
            $userId = (int) ($request->session()->get('user_id') ?? 0);
        }

        if ($petId <= 0 && $userId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Provide pet_id (preferred) or user_id to locate a pet',
            ], 422);
        }

        if ($petId > 0) {
            $pet = DB::table('pets')->where('id', $petId)->first();
        } else {
            $pet = DB::table('pets')
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->first();
        }

        if (!$pet) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found',
            ], 404);
        }

        $summary = $this->buildPetRowSummary($pet);

        return response()->json([
            'success' => true,
            'pet_id' => $pet->id ?? $petId,
            'user_id' => $pet->user_id ?? ($userId ?: null),
            'summary' => $summary,
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

    private function buildPetRowSummary(object $pet): string
    {
        $lines = [];
        foreach (get_object_vars($pet) as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            }

            $value = is_string($value) ? trim($value) : $value;
            if ($value === '' || $value === null) {
                continue;
            }

            $label = ucwords(str_replace('_', ' ', (string) $key));
            $lines[] = $label . ': ' . $value;
        }

        return trim(implode("\n", $lines));
    }
}
