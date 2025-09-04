<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Chat;
use App\Models\ChatRoom;

class GeminiChatController extends Controller
{
    /** CREATE ROOM: DB row + token */
    public function newRoom(Request $request)
    {
        //dd($request->all());
        $data = $request->validate([
            'user_id'        => 'required|integer',
            'title'          => 'nullable|string',
            'pet_name'       => 'nullable|string',
            'pet_breed'      => 'nullable|string',
            'pet_age'        => 'nullable|string',
            'pet_location'   => 'nullable|string',
        ]);

        $chatRoomToken = 'room_' . Str::uuid()->toString();

        $room = ChatRoom::create([
            'user_id'        => (int) $data['user_id'],
            'chat_room_token'=> $chatRoomToken,
            'name'           => $data['title'] ?? null,
        ]);

        return response()->json([
            'status'          => 'success',
            'chat_room_id'    => $room->id,
            'chat_room_token' => $room->chat_room_token,
            'name'            => $room->name,
            'note' => 'Use this chat_room_token in /api/gemini/send for all messages in this room.',
        ]);
    }

    /** SEND MESSAGE: Requires existing room; refresh room name each message */
    public function sendMessage(Request $request)
    {
       
        $request->validate([
            'user_id'         => 'required|integer',
            'chat_room_token' => 'required|string',
            'chat_room_id'    => 'required_without:chat_room_token|integer',
            'question'        => 'required|string',
            'context_token'   => 'nullable|string',
            'title'           => 'nullable|string', // if you want to override name from frontend
            'pet_name'        => 'nullable|string',
            'pet_breed'       => 'nullable|string',
            'pet_age'         => 'nullable|string',
            'pet_location'    => 'nullable|string',
        ]);

        $userId  = (int) $request->user_id;

        // Find room by id or token, ensuring it belongs to user
        if ($request->filled('chat_room_id')) {
            $room = ChatRoom::where('id', $request->chat_room_id)
                ->where('user_id', $userId)
                ->firstOrFail();
        } else {
          //dd($request->chat_room_token);
            $room = ChatRoom::where('chat_room_token', $request->chat_room_token)
              //  ->where('user_id', $userId)
                ->firstOrFail();
             
        }
        
        


        $contextToken = $request->context_token ?: Str::uuid()->toString();

        // Last chat in SAME room (for short context)
        $lastChat = Chat::where('chat_room_id', $room->id)
            ->latest()
            ->first();

        // Build Gemini payload (systemInstruction + contents)
        $payload = $this->buildGeminiPayload($request->all(), $lastChat);

        // Emergency level
        $level = $this->detectEmergencyLevel((string) $request->question);

        // Call Gemini
        $apiKey = 'AIzaSyALZDZm-pEK3mtcK9PG9ftz6xyGemEHQ3k';
        if (!$apiKey) {
            return response()->json(['error' => 'GEMINI_API_KEY missing in .env'], 500);
        }

        $resp = Http::withHeaders([
            'Content-Type'   => 'application/json',
            'X-goog-api-key' => $apiKey,
        ])->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent',
            $payload
        );

        if (!$resp->successful()) {
            return response()->json([
                'error'   => 'Gemini API call failed',
                'details' => $resp->json(),
            ], 500);
        }

        $answerRaw = $resp->json('candidates.0.content.parts.0.text') ?? "No response.";
        $answerRaw = $this->enforceSnoutBrand($answerRaw);
        $answer    = $this->stripMarkdownToPlain($answerRaw);

        // Save message
        $chat = Chat::create([
            'user_id'         => $userId,
            'chat_room_id'    => $room->id,               // 🔵 FK
            'chat_room_token' => $room->chat_room_token,  // (optional legacy)
            'context_token'   => $contextToken,
            'question'        => $request->question,
            'answer'          => $answer,
            'pet_name'        => $request->pet_name,
            'pet_breed'       => $request->pet_breed,
            'pet_age'         => $request->pet_age,
            'pet_location'    => $request->pet_location,
        ]);

        // 🔄 Refresh room name on every question
        $newName = $request->title ?: $this->autoTitleFromQuestion($request->question);
        $room->name = $newName;
        $room->touch(); // updates updated_at
        $room->save();

        return response()->json([
            'status'           => 'success',
            'chat_room_id'     => $room->id,
            'chat_room_token'  => $room->chat_room_token,
            'room_name'        => $room->name,
            'context_token'    => $contextToken,
            'emergency_status' => $level,
            'chat'             => $chat,
        ]);
    }

    /** List rooms for a user (from chat_rooms) */
    public function listRooms(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
        ]);

        $rooms = ChatRoom::where('user_id', $data['user_id'])
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'chat_room_token', 'name', 'created_at', 'updated_at']);

        return response()->json([
            'status' => 'success',
            'rooms'  => $rooms,
        ]);
    }

    /** History of one room */
    public function history(Request $request)
    {
        $data = $request->validate([
            'user_id'         => 'required|integer',
            'chat_room_token' => 'required_without:chat_room_id|string',
            'chat_room_id'    => 'required_without:chat_room_token|integer',
            'sort'            => 'nullable|in:asc,desc',
        ]);

        $sort = $data['sort'] ?? 'asc';

        if (!empty($data['chat_room_id'])) {
            $room = ChatRoom::where('id', $data['chat_room_id'])
                ->where('user_id', $data['user_id'])
                ->firstOrFail();
        } else {
            $room = ChatRoom::where('chat_room_token', $data['chat_room_token'])
                ->where('user_id', $data['user_id'])
                ->firstOrFail();
        }

        $rows = Chat::where('chat_room_id', $room->id)
            ->orderBy('created_at', $sort)
            ->get();

        return response()->json([
            'status' => 'success',
            'room'   => [
                'id'              => $room->id,
                'chat_room_token' => $room->chat_room_token,
                'name'            => $room->name,
            ],
            'count'  => $rows->count(),
            'chats'  => $rows,
        ]);
    }

    // ---------------- Helpers ----------------

    private function autoTitleFromQuestion(string $q): string
    {
        // First 6–8 words as title
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($q)));
        $words = explode(' ', $clean);
        $take  = array_slice($words, 0, 8);
        $title = implode(' ', $take);
        if (count($words) > 8) $title .= '…';
        return $title ?: 'New chat';
    }

    private function buildGeminiPayload(array $req, ?Chat $lastChat): array
    {
        $petContextBlock = "";
        if (!empty($req['pet_name']) || !empty($req['pet_breed']) || !empty($req['pet_age']) || !empty($req['pet_location'])) {
            $petContextBlock =
                "Pet Profile:\n" .
                "- Pet Name: " . ($req['pet_name'] ?? 'Not specified') . "\n" .
                "- Breed: " . ($req['pet_breed'] ?? 'Mixed/Unknown breed') . "\n" .
                "- Age: " . ($req['pet_age'] ?? 'Age not specified') . " years old\n" .
                "- Location: " . ($req['pet_location'] ?? 'India (general advice)');
        }

        $lastQnA = "";
        if ($lastChat) {
            $lastQnA =
                "Previous Exchange:\n" .
                "- Last Question: " . trim($lastChat->question) . "\n" .
                "- Last Answer: " . mb_substr(trim($lastChat->answer), 0, 800) . (mb_strlen($lastChat->answer) > 800 ? "..." : "");
        }

        $system = <<<SYS
Role: Act as SnoutAI Assistant, an empathetic guide for Indian pet parents. You are NOT a vet and cannot diagnose or treat. Your role is to comfort worried pet parents and guide them to appropriate care.

Response Structure:
1) Acknowledge emotion first (e.g., "I understand how worrying this must be...")
2) Safety first: "It's important to have a veterinarian examine the pet for this."
3) Short educational context
4) Natural guidance (no sales tone)

Safety Rules:
- Never diagnose or prescribe treatment/medicines
- For symptoms: emotion → vet recommendation → context → guidance
- For emergencies: be urgent but compassionate

Branding:
- Never call yourself Gemini/Google
- Introduce as "SnoutAI Assistant" only

Output Formatting:
- PLAIN TEXT only. No Markdown, *, **, headings, or code blocks.
- If needed, use simple numbered points like "1)".
SYS;

        $userParts = array_filter([
            $petContextBlock,
            $lastQnA,
            "Current Question: " . ($req['question'] ?? '')
        ]);
        $userPrompt = implode("\n\n", $userParts);

        return [
            "systemInstruction" => [
                "role"  => "system",
                "parts" => [ [ "text" => $system ] ],
            ],
            "contents" => [
                [
                    "role"  => "user",
                    "parts" => [ [ "text" => $userPrompt ] ],
                ],
            ],
            "generationConfig" => [
                "temperature"      => 0.7,
                "topK"             => 40,
                "topP"             => 0.95,
                "maxOutputTokens"  => 800,
                "stopSequences"    => [],
            ],
        ];
    }

    private function detectEmergencyLevel(string $question): string
    {
        $map = [
            '🚨 CRITICAL' => ['unconscious','not breathing','severe bleeding','seizure','collapse'],
            '⚠️ URGENT'   => ['chocolate','vomiting blood','poisoned','ate poison','toxic'],
            '🟡 PRIORITY' => ['difficulty breathing','vomiting repeatedly','severe pain',"won't eat",'lethargic'],
        ];
        $q = strtolower($question);
        foreach ($map as $tag => $list) {
            foreach ($list as $kw) {
                if (str_contains($q, $kw)) {
                    return "$tag - Seek immediate veterinary care!";
                }
            }
        }
        return "ℹ️ Routine inquiry";
    }

    private function stripMarkdownToPlain(string $text): string
    {
        $out = preg_replace('/[*_`>#-]+/', ' ', $text);
        $out = preg_replace('/[ \t]+/', ' ', $out);
        $out = preg_replace('/\n{3,}/', "\n\n", $out);
        return trim($out ?? $text);
    }

    private function enforceSnoutBrand(string $text): string
    {
        $out = preg_replace('/\b(Google\s+Gemini|Gemini|Google Assistant)\b/i', 'SnoutAI Assistant', $text);
        return $out ?? $text;
    }

    public function getRoomChats(Request $request, string $chat_room_token)
{
    // ✅ Validate query inputs
    $data = $request->validate([
        'user_id' => 'required|integer',
        'sort'    => 'nullable|in:asc,desc',   // optional
    ]);

    $sort = $data['sort'] ?? 'asc';

    // ✅ Find room owned by this user
    $room = ChatRoom::where('chat_room_token', $chat_room_token)
        ->where('user_id', $data['user_id'])
        ->firstOrFail();

    // ✅ Fetch all chats for this room (oldest → newest by default)
    $chats = Chat::where('chat_room_id', $room->id)
        ->orderBy('created_at', $sort)
        ->get([
            'id','user_id','chat_room_id','context_token','question','answer',
            'pet_name','pet_breed','pet_age','pet_location','created_at'
        ]);

    return response()->json([
        'status' => 'success',
        'room'   => [
            'id'              => $room->id,
            'chat_room_token' => $room->chat_room_token,
            'name'            => $room->name,
            'updated_at'      => $room->updated_at,
        ],
        'count'  => $chats->count(),
        'chats'  => $chats,
    ]);
}

public function deleteRoom(Request $request, string $chat_room_token)
{
    // ✅ Validate
    $data = $request->validate([
        'user_id' => 'required|integer',
    ]);

    // ✅ Room must belong to this user
    $room = ChatRoom::where('chat_room_token', $chat_room_token)
        ->where('user_id', $data['user_id'])
        ->first();

    if (!$room) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Room not found for this user',
        ], 404);
    }

    // ✅ Delete with transaction (delete chats first, then room)
    \DB::transaction(function () use ($room, &$deletedChats) {
        $deletedChats = Chat::where('chat_room_id', $room->id)->delete();
        $room->delete();
    });

    return response()->json([
        'status' => 'success',
        'message' => 'Chat room deleted successfully',
        'deleted' => [
            'chat_room_id' => $room->id,
            'chat_room_token' => $room->chat_room_token,
            'chats_deleted' => $deletedChats ?? 0,
        ],
    ]);
}


}
