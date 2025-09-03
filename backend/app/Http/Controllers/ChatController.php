<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Chat;
use Illuminate\Support\Facades\Http;

class GeminiChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        // ✅ Validation
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'question' => 'required|string',
            'context_token' => 'nullable|string',
            'pet_name' => 'nullable|string',
            'pet_breed' => 'nullable|string',
            'pet_age' => 'nullable|string',
            'pet_location' => 'nullable|string',
        ]);

        $userId = $request->user_id;
        $contextToken = $request->context_token ?? Str::uuid()->toString();

        // ✅ Last Chat fetch (if continuation)
        $lastChat = null;
        if ($request->context_token) {
            $lastChat = Chat::where('user_id', $userId)
                ->where('context_token', $contextToken)
                ->latest()
                ->first();
        }

        // ✅ Pet context
        $petContext = "
Pet Profile:
- Pet Name: " . ($request->pet_name ?? 'Not specified') . "
- Breed: " . ($request->pet_breed ?? 'Mixed/Unknown breed') . "
- Age: " . ($request->pet_age ?? 'Age not specified') . " years old
- Location: " . ($request->pet_location ?? 'India (general advice)') . "
";

        // ✅ Add last chat context
        $lastQnA = "";
        if ($lastChat) {
            $lastQnA = "
Last Question: {$lastChat->question}
Last Answer: {$lastChat->answer}
";
        }

        // ✅ Final input text
        $inputText = "<|system|>
You are an expert veterinary assistant specializing in pet care for Indian pet owners.
{$petContext}
{$lastQnA}
Current Question: {$request->question}
<|assistant|>";

        // ✅ Emergency keyword check
        $emergencyKeywords = [
            '🚨 CRITICAL' => ['unconscious', 'not breathing', 'severe bleeding', 'seizure', 'collapse'],
            '⚠️ URGENT'   => ['chocolate', 'vomiting blood', 'poisoned', 'ate poison', 'toxic'],
            '🟡 PRIORITY' => ['difficulty breathing', 'vomiting repeatedly', 'severe pain', "won't eat", 'lethargic'],
        ];

        $level = "ℹ️ Routine inquiry";
        $questionLower = strtolower($request->question);
        foreach ($emergencyKeywords as $tag => $keywords) {
            foreach ($keywords as $word) {
                if (str_contains($questionLower, $word)) {
                    $level = "$tag - Seek immediate veterinary care!";
                    break 2;
                }
            }
        }

        // ✅ Call Gemini API
        $apiKey = env('GEMINI_API_KEY');
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $inputText]
                    ]
                ]
            ]
        ]);

        $answer = $response->json('candidates.0.content.parts.0.text') ?? "⚠️ No response from AI.";

        // ✅ Save in DB
        $chat = Chat::create([
            'user_id' => $userId,
            'context_token' => $contextToken,
            'question' => $request->question,
            'answer' => $answer,
            'pet_name' => $request->pet_name,
            'pet_breed' => $request->pet_breed,
            'pet_age' => $request->pet_age,
            'pet_location' => $request->pet_location,
        ]);

        // ✅ Return response
        return response()->json([
            'status' => 'success',
            'context_token' => $contextToken,
            'emergency_status' => $level,
            'chat' => $chat,
        ]);
    }
}
