<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Chat;
use App\Models\ChatRoom;
use Illuminate\Support\Facades\Cache;

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

//        private function buildGeminiPayload(array $req, ?Chat $lastChat): array
//     {
//         $petContextBlock = "";
//         if (!empty($req['pet_name']) || !empty($req['pet_breed']) || !empty($req['pet_age']) || !empty($req['pet_location'])) {
//             $petContextBlock =
//                 "Pet Profile:\n" .
//                 "- Pet Name: " . ($req['pet_name'] ?? 'Not specified') . "\n" .
//                 "- Breed: " . ($req['pet_breed'] ?? 'Mixed/Unknown breed') . "\n" .
//                 "- Age: " . ($req['pet_age'] ?? 'Age not specified') . " years old\n" .
//                 "- Location: " . ($req['pet_location'] ?? 'India (general advice)');
//         }

//         $lastQnA = "";
//         if ($lastChat) {
//             $lastQnA =
//                 "Previous Exchange:\n" .
//                 "- Last Question: " . trim($lastChat->question) . "\n" .
//                 "- Last Answer: " . mb_substr(trim($lastChat->answer), 0, 800) . (mb_strlen($lastChat->answer) > 800 ? "..." : "");
//         }

//         $system = <<<SYS
// Role: Act as SnoutAI Assistant, an empathetic guide for Indian pet parents. You are NOT a vet and cannot diagnose or treat. Your role is to comfort worried pet parents and guide them to appropriate care.

// Response Structure:
// 1) Acknowledge emotion first (e.g., "I understand how worrying this must be...")
// 2) Safety first: "It's important to have a veterinarian examine the pet for this."
// 3) Short educational context
// 4) Natural guidance (no sales tone)

// Safety Rules:
// - Never diagnose or prescribe treatment/medicines
// - For symptoms: emotion → vet recommendation → context → guidance
// - For emergencies: be urgent but compassionate

// Branding:
// - Never call yourself Gemini/Google
// - Introduce as "SnoutAI Assistant" only

// Output Formatting:
// - PLAIN TEXT only. No Markdown, *, **, headings, or code blocks.
// - If needed, use simple numbered points like "1)".
// SYS;

//         $userParts = array_filter([
//             $petContextBlock,
//             $lastQnA,
//             "Current Question: " . ($req['question'] ?? '')
//         ]);
//         $userPrompt = implode("\n\n", $userParts);

//         return [
//             "systemInstruction" => [
//                 "role"  => "system",
//                 "parts" => [ [ "text" => $system ] ],
//             ],
//             "contents" => [
//                 [
//                     "role"  => "user",
//                     "parts" => [ [ "text" => $userPrompt ] ],
//                 ],
//             ],
//             "generationConfig" => [
//                 "temperature"      => 0.7,
//                 "topK"             => 40,
//                 "topP"             => 0.95,
//                 "maxOutputTokens"  => 800,
//                 "stopSequences"    => [],
//             ],
//         ];
//     }

//     private function detectEmergencyLevel(string $question): string
//     {
//         $map = [
//             '🚨 CRITICAL' => ['unconscious','not breathing','severe bleeding','seizure','collapse'],
//             '⚠️ URGENT'   => ['chocolate','vomiting blood','poisoned','ate poison','toxic'],
//             '🟡 PRIORITY' => ['difficulty breathing','vomiting repeatedly','severe pain',"won't eat",'lethargic'],
//         ];
//         $q = strtolower($question);
//         foreach ($map as $tag => $list) {
//             foreach ($list as $kw) {
//                 if (str_contains($q, $kw)) {
//                     return "$tag - Seek immediate veterinary care!";
//                 }
//             }
//         }
//         return "ℹ️ Routine inquiry";
//     }

//     /** SEND MESSAGE: Requires existing room; refresh room name each message */
//     public function sendMessage(Request $request)
//     {
       
//         $request->validate([
//             'user_id'         => 'required|integer',
//             'chat_room_token' => 'required|string',
//             'chat_room_id'    => 'required_without:chat_room_token|integer',
//             'question'        => 'required|string',
//             'context_token'   => 'nullable|string',
//             'title'           => 'nullable|string', // if you want to override name from frontend
//             'pet_name'        => 'nullable|string',
//             'pet_breed'       => 'nullable|string',
//             'pet_age'         => 'nullable|string',
//             'pet_location'    => 'nullable|string',
//         ]);

//         $userId  = (int) $request->user_id;

//         // Find room by id or token, ensuring it belongs to user
//         if ($request->filled('chat_room_id')) {
//             $room = ChatRoom::where('id', $request->chat_room_id)
//                 ->where('user_id', $userId)
//                 ->firstOrFail();
//         } else {
//           //dd($request->chat_room_token);
//             $room = ChatRoom::where('chat_room_token', $request->chat_room_token)
//               //  ->where('user_id', $userId)
//                 ->firstOrFail();
             
//         }
        
        


//         $contextToken = $request->context_token ?: Str::uuid()->toString();

//         // Last chat in SAME room (for short context)
//         $lastChat = Chat::where('chat_room_id', $room->id)
//             ->latest()
//             ->first();

//         // Build Gemini payload (systemInstruction + contents)
//         $payload = $this->buildGeminiPayload($request->all(), $lastChat);

//         // Emergency level
//         $level = $this->detectEmergencyLevel((string) $request->question);

//         // Call Gemini
//         $apiKey = env('GEMINI_API_KEY');
//         if (!$apiKey) {
//             return response()->json(['error' => 'GEMINI_API_KEY missing in .env'], 500);
//         }

//         $resp = Http::withHeaders([
//             'Content-Type'   => 'application/json',
//             'X-goog-api-key' => $apiKey,
//         ])->post(
//             'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent',
//             $payload
//         );

//         if (!$resp->successful()) {
//             return response()->json([
//                 'error'   => 'Gemini API call failed',
//                 'details' => $resp->json(),
//             ], 500);
//         }

//         $answerRaw = $resp->json('candidates.0.content.parts.0.text') ?? "No response.";
//         $answerRaw = $this->enforceSnoutBrand($answerRaw);
//         $answer    = $this->stripMarkdownToPlain($answerRaw);

//         // Save message
//         $chat = Chat::create([
//             'user_id'         => $userId,
//             'chat_room_id'    => $room->id,               // 🔵 FK
//             'chat_room_token' => $room->chat_room_token,  // (optional legacy)
//             'context_token'   => $contextToken,
//             'question'        => $request->question,
//             'answer'          => $answer,
//             'pet_name'        => $request->pet_name,
//             'pet_breed'       => $request->pet_breed,
//             'pet_age'         => $request->pet_age,
//             'pet_location'    => $request->pet_location,
//         ]);

//         // 🔄 Refresh room name on every question
//         $newName = $request->title ?: $this->autoTitleFromQuestion($request->question);
//         $room->name = $newName;
//         $room->touch(); // updates updated_at
//         $room->save();

//         return response()->json([
//             'status'           => 'success',
//             'chat_room_id'     => $room->id,
//             'chat_room_token'  => $room->chat_room_token,
//             'room_name'        => $room->name,
//             'context_token'    => $contextToken,
//             'emergency_status' => $level,
//             'chat'             => $chat,
//         ]);
//     }




 /** ---------- NEW: Helpers ported from Python version ---------- */

    private array $cityCoords = [
        'delhi'     => ['lat' => 28.6139, 'lon' => 77.2090],
        'mumbai'    => ['lat' => 19.0760, 'lon' => 72.8777],
        'bangalore' => ['lat' => 12.9716, 'lon' => 77.5946],
        'bengaluru' => ['lat' => 12.9716, 'lon' => 77.5946],
        'gurgaon'   => ['lat' => 28.4595, 'lon' => 77.0266],
        'gurugram'  => ['lat' => 28.4595, 'lon' => 77.0266],
        'pune'      => ['lat' => 18.5204, 'lon' => 73.8567],
        'chennai'   => ['lat' => 13.0827, 'lon' => 80.2707],
        'hyderabad' => ['lat' => 17.3850, 'lon' => 78.4867],
        'kolkata'   => ['lat' => 22.5726, 'lon' => 88.3639],
    ];

    private array $breedAdvisories = [
        'high_maintenance_heat' => ['Siberian Husky','German Shepherd','Golden Retriever','Saint Bernard','Persian'],
        'heat_sensitive'        => ['Bulldog','Pug','Boxer','British Shorthair','Himalayan Sheepdog'],
        'monsoon_prone'         => ['Cocker Spaniel','Beagle','Persian','Maine Coon'],
        'hardy_breeds'          => ['Indian Pariah Dog','Rajapalayam','Indian Street Cat'],
    ];

    private function getCurrentSeason(): array
    {
        // months: Jan=1..Dec=12
        $m = (int) now()->month;
        if (in_array($m, [3,4,5,6], true))  return ['summer',       ['temp' => '35-45°C','humidity'=>'high','risks'=>'heat stroke, dehydration']];
        if (in_array($m, [7,8,9], true))    return ['monsoon',      ['temp' => '25-35°C','humidity'=>'very high','risks'=>'skin infections, fungal issues']];
        if ($m === 10)                      return ['post_monsoon', ['temp' => '20-30°C','humidity'=>'moderate','risks'=>'allergies, vector diseases']];
        /* 11,12,1,2 */
        return ['winter', ['temp'=>'5-25°C','humidity'=>'low','risks'=>'dry skin, respiratory issues']];
    }

    private function getWeatherData(?string $location): array
    {
        $locKey = strtolower(trim($location ?? 'delhi'));
        $coords = $this->cityCoords[$locKey] ?? $this->cityCoords['delhi'];

        $cacheKey = "wx_{$locKey}_" . now()->format('YmdH');
        return Cache::remember($cacheKey, 3600, function () use ($coords) {
            try {
                $resp = Http::timeout(8)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude'  => $coords['lat'],
                    'longitude' => $coords['lon'],
                    'hourly'    => 'temperature_2m,relative_humidity_2m,apparent_temperature',
                    'forecast_days' => 1,
                ]);
                if ($resp->successful()) {
                    $d = $resp->json();
                    $hourly = $d['hourly'] ?? [];
                    // Take the first slot as a reasonable proxy; can be refined to nearest to now.
                    $t  = $hourly['temperature_2m'][0]          ?? null;
                    $rh = $hourly['relative_humidity_2m'][0]    ?? null;
                    $feels = $hourly['apparent_temperature'][0] ?? null;

                    $desc = $this->wxDescription((float) $t, (float) $rh);
                    return [
                        'temp'       => is_null($t) ? null : round((float) $t, 1),
                        'humidity'   => is_null($rh) ? null : (int) round((float) $rh),
                        'feels_like' => is_null($feels) ? null : round((float) $feels, 1),
                        'description'=> $desc,
                        'pressure'   => 1013,
                    ];
                }
            } catch (\Throwable $e) {}
            [$season] = $this->getCurrentSeason();
            return ['temp'=>30,'humidity'=>65,'feels_like'=>32,'description'=>"typical {$season} weather",'pressure'=>1013];
        });
    }

    private function wxDescription(float $t, float $rh): string
    {
        if ($t > 35)  return $rh > 80 ? 'hot and humid' : ($rh > 60 ? 'hot and muggy' : 'hot and dry');
        if ($t > 25)  return $rh > 80 ? 'warm and humid' : ($rh > 60 ? 'warm and pleasant' : 'warm and dry');
        return $rh > 80 ? 'cool and humid' : 'cool and pleasant';
    }

    private function getAirQualityData(?string $location): array
    {
        $locKey = trim($location ?? 'Delhi');
        $cacheKey = "aqi_{$locKey}_" . now()->format('YmdH');

        return Cache::remember($cacheKey, 3600, function () use ($locKey) {
            try {
                $resp = Http::timeout(6)->get('https://api.openaq.org/v2/latest', [
                    'city'      => $locKey,
                    'country'   => 'IN',
                    'parameter' => 'pm25',
                    'limit'     => 1,
                ]);
                if ($resp->successful()) {
                    $d = $resp->json();
                    if (!empty($d['results'][0]['measurements'][0]['value'])) {
                        $pm25 = (float) $d['results'][0]['measurements'][0]['value'];
                        $aqi  = (int) round($pm25 * 2); // rough surrogate only
                        return [
                            'aqi'            => $aqi,
                            'main_pollutant' => 'pm25',
                            'level'          => $this->aqiLevel($aqi),
                        ];
                    }
                }
            } catch (\Throwable $e) {}
            return ['aqi'=>85,'main_pollutant'=>'pm25','level'=>'moderate'];
        });
    }

    private function aqiLevel(int $aqi): string
    {
        if ($aqi <= 50)  return 'good';
        if ($aqi <= 100) return 'moderate';
        if ($aqi <= 150) return 'unhealthy_for_sensitive';
        if ($aqi <= 200) return 'unhealthy';
        return 'hazardous';
    }

    private function generateClimateAlerts(array $pet, array $wx, array $aqi): array
    {
        $alerts = [];
        $breed  = (string) ($pet['breed'] ?? '');
        $loc    = (string) ($pet['location'] ?? '');

        $temp = $wx['temp'] ?? null;
        $hum  = $wx['humidity'] ?? null;

        if (!is_null($temp)) {
            if ($temp > 35 && in_array($breed, $this->breedAdvisories['heat_sensitive'], true)) {
                $alerts[] = "🌡️ HIGH HEAT WARNING: {$temp}°C in {$loc} - {$breed}s are at risk!";
            } elseif ($temp > 30 && in_array($breed, $this->breedAdvisories['high_maintenance_heat'], true)) {
                $alerts[] = "⚠️ HEAT ALERT: {$temp}°C - Extra cooling needed for {$breed}";
            }
        }
        if (!is_null($hum) && $hum > 80 && in_array($breed, $this->breedAdvisories['monsoon_prone'], true)) {
            $alerts[] = "💧 HIGH HUMIDITY: {$hum}% - Increase grooming for {$breed}";
        }
        if (($aqi['level'] ?? 'moderate') === 'unhealthy' || ($aqi['level'] ?? '') === 'hazardous') {
            $name = $pet['name'] ?? 'your pet';
            $alerts[] = "🏭 POOR AIR QUALITY: Keep {$name} indoors today";
        }
        if (in_array($breed, $this->breedAdvisories['hardy_breeds'], true)) {
            $alerts[] = "✅ HARDY BREED: {$breed} handles {$loc}'s climate well";
        }
        $desc = $wx['description'] ?? 'current conditions';
        $alerts[] = "📊 CURRENT: " . ucfirst($desc) . " in {$loc}";
        return $alerts;
    }

    /** Expanded emergency detection to match Python list (incl. “blood in vomit”). */
    private function detectEmergencyKeyword(?string $message): bool
    {
        $msg = strtolower($message ?? '');
        $keywords = [
            "not breathing","can't breathe","cant breathe","difficulty breathing",
            "unconscious","unresponsive","collapsed","collapse","seizure","convulsing",
            "bleeding heavily","lots of blood","poisoning","ate poison","toxic","hit by car",
            "vomiting blood","blood in vomit","blood in stool"
        ];
        foreach ($keywords as $kw) {
            if (str_contains($msg, $kw)) return true;
        }
        return false;
    }

    /** Keep your older triage map, but return a concise label used in responses. */
    private function detectEmergencyLevel(string $question): string
    {
        $map = [
            '🚨 CRITICAL' => ['unconscious','not breathing','severe bleeding','seizure','collapse','blood in vomit','vomiting blood'],
            '⚠️ URGENT'   => ['chocolate','poisoned','ate poison','toxic'],
            '🟡 PRIORITY' => ['difficulty breathing','vomiting repeatedly','severe pain',"won't eat",'wont eat','lethargic'],
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

    private function parseSystemTagFromModel(string $aiText): string
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($aiText));
        $last  = strtoupper(trim(end($lines) ?: ''));
        $valid = ['EMERGENCY','URGENT','ROUTINE','INFO'];
        if (in_array($last, $valid, true)) {
            return $last;
        }
        foreach ($valid as $v) {
            if (stripos($aiText, $v) !== false) return $v;
        }
        return 'INFO';
    }

    private function bookingOptions(string $tag): string
    {
        return match (strtoupper($tag)) {
            'EMERGENCY' => "🚨 Emergency Consultation Available\n- Immediate video consultation: ₹1500\n- Emergency clinic visit: ₹2500",
            'URGENT'    => "⚡ Urgent Care Options\n- Same-day video consultation: ₹800\n- Clinic visit today: ₹1200",
            'ROUTINE'   => "📅 Schedule Check-up\n- Video consultation: ₹500\n- Clinic visit: ₹800",
            'INFO'      => "💡 Optional Consultation\n- Video consultation: ₹500\n- General check-up: ₹800",
            default     => "Book a consultation for personalized care!",
        };
    }

    /** ---------- UPDATED: Build Gemini payload with climate + last 3 turns ---------- */
    private function buildGeminiPayload(array $req, ?Chat $lastChat): array
    {
        // Pet block
        $petContextBlock = "";
        if (!empty($req['pet_name']) || !empty($req['pet_breed']) || !empty($req['pet_age']) || !empty($req['pet_location']) || !empty($req['pet_type'])) {
            $petContextBlock =
                "Pet Profile:\n" .
                "- Pet Name: " . ($req['pet_name'] ?? 'Not specified') . "\n" .
                "- Type: " . ($req['pet_type'] ?? 'Dog/Cat (unspecified)') . "\n" .
                "- Breed: " . ($req['pet_breed'] ?? 'Mixed/Unknown breed') . "\n" .
                "- Age: " . ($req['pet_age'] ?? 'Age not specified') . " years old\n" .
                "- Location: " . ($req['pet_location'] ?? 'India (general advice)');
        }

        // Climate snapshot (pulled here for prompt context)
        $wx  = $this->getWeatherData($req['pet_location'] ?? null);
        $aqi = $this->getAirQualityData($req['pet_location'] ?? null);
        [$season] = $this->getCurrentSeason();

        $climateSnapshot =
            "Climate Snapshot:\n" .
            "- Season: " . ucfirst($season) . "\n" .
            "- Weather: " . ucfirst($wx['description'] ?? 'n/a') .
              ", Temp: " . ($wx['temp'] ?? 'n/a') . "°C, Feels: " . ($wx['feels_like'] ?? 'n/a') . "°C, Humidity: " . ($wx['humidity'] ?? 'n/a') . "%\n" .
            "- Air Quality: " . ucfirst(str_replace('_',' ',$aqi['level'] ?? 'moderate')) . " (AQI " . ($aqi['aqi'] ?? 'n/a') . ")";

        // Last up to 3 exchanges in this room
        $roomId = isset($lastChat) ? $lastChat->chat_room_id : null;
        $historyBlock = "";
        if ($roomId) {
            $recent = Chat::where('chat_room_id', $roomId)->orderByDesc('id')->limit(3)->get()->reverse();
            if ($recent->count()) {
                $lines = ["Previous conversation (last up to 3 exchanges):"];
                foreach ($recent as $c) {
                    $lines[] = "User: " . trim($c->question);
                    $lines[] = "AI: "   . mb_substr(trim($c->answer), 0, 800) . (mb_strlen($c->answer) > 800 ? "..." : "");
                }
                $historyBlock = implode("\n", $lines);
            }
        } elseif ($lastChat) {
            $historyBlock =
                "Previous Exchange:\n" .
                "- Last Question: " . trim($lastChat->question) . "\n" .
                "- Last Answer: " . mb_substr(trim($lastChat->answer), 0, 800) . (mb_strlen($lastChat->answer) > 800 ? "..." : "");
        }

        // Climate-aware, brand-safe system prompt (PLAIN TEXT output, one tag at end)
        $system = <<<SYS
You are SnoutAI Assistant, an empathetic guide for Indian pet parents. You are NOT a vet and must not diagnose or prescribe medicines. Your job is to comfort, educate briefly, and guide to appropriate care.

Response Rules:
1) Acknowledge emotion first.
2) Safety first: "It's important to have a veterinarian examine the pet for this."
3) Give short, practical educational context (climate and air quality may matter).
4) Offer natural guidance to next steps (no salesy tone).
5) Output PLAIN TEXT only (no Markdown, bullets allowed as "1)", "2)").
6) End with EXACTLY ONE tag on a new last line: EMERGENCY, URGENT, ROUTINE, or INFO.
7) Always end the main text before the tag with: "⚠️ AI advice. Consult veterinarian for health concerns."
8) If the user has had 5 or more exchanges in this room, suggest: "For continued detailed guidance, please start a new conversation or book a video consultation with our veterinarians."

Branding:
- Never call yourself Gemini/Google.
- Introduce as "SnoutAI Assistant" if needed.

Tag meanings:
- EMERGENCY: life-threatening (not breathing, unconscious, severe bleeding, vomiting blood, blood in stool)
- URGENT: needs vet within 24–48h
- ROUTINE: can wait a few days
- INFO: general care info
SYS;

        $userParts = array_filter([
            $petContextBlock,
            $climateSnapshot,
            $historyBlock,
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
                "temperature"     => 0.7,
                "topK"            => 40,
                "topP"            => 0.95,
                "maxOutputTokens" => 800,
                "stopSequences"   => [],
            ],
        ];
    }

    /** ---------- UPDATED: sendMessage with climate/AQI, limit, tags ---------- */
    public function sendMessage(Request $request)
    {
 
        $request->validate([
            'user_id'         => 'required|integer',
          //  'chat_room_token' => 'required_without:chat_room_id|string',
           // 'chat_room_id'    => 'required_without:chat_room_token|integer',
            'question'        => 'required|string',
            'context_token'   => 'nullable|string',
            'title'           => 'nullable|string',
            'pet_name'        => 'nullable|string',
            'pet_type'        => 'nullable|string',   // NEW: Dog/Cat
            'pet_breed'       => 'nullable|string',
            'pet_age'         => 'nullable|string',
            'pet_location'    => 'nullable|string',
        ]);

        $userId  = (int) $request->user_id;

        // Find room by id or token (keep your relaxed token-based access if needed)
        if ($request->filled('chat_room_id')) {
            $room = ChatRoom::where('id', $request->chat_room_id)
                ->where('user_id', $userId)
                ->firstOrFail();
        } else {
            $room = ChatRoom::where('chat_room_token', $request->chat_room_token)
                // ->where('user_id', $userId) // keep commented if you want token-based share
                ->firstOrFail();
        }
       //dd($request->all());
        $contextToken = $request->context_token ?: Str::uuid()->toString();

        // Last chat in this room (for context) + exchange count
        $lastChat   = Chat::where('chat_room_id', $room->id)->latest()->first();
        $exchanges  = Chat::where('chat_room_id', $room->id)->count();

        // Climate data for response payload
        $wx  = $this->getWeatherData($request->pet_location ?? null);
        $aqi = $this->getAirQualityData($request->pet_location ?? null);
        [$season] = $this->getCurrentSeason();
        $climateAlerts = $this->generateClimateAlerts([
            'name'     => $request->pet_name,
            'breed'    => $request->pet_breed,
            'location' => $request->pet_location,
        ], $wx, $aqi);

        // 5-exchange limit behavior (like Python)
        if ($exchanges >= 5) {
            $limitMsg = "For continued detailed guidance, please start a new conversation or book a video consultation with our veterinarians for personalized care.\n\n⚠️ AI advice. Consult veterinarian for health concerns.";
            $systemTag = 'INFO';
            $level     = $this->detectEmergencyLevel((string) $request->question);

            $chat = Chat::create([
                'user_id'         => $userId,
                'chat_room_id'    => $room->id,
                'chat_room_token' => $room->chat_room_token,
                'context_token'   => $contextToken,
                'question'        => $request->question,
                'answer'          => $this->stripMarkdownToPlain($this->enforceSnoutBrand($limitMsg)),
                'pet_name'        => $request->pet_name,
                'pet_breed'       => $request->pet_breed,
                'pet_age'         => $request->pet_age,
                'pet_location'    => $request->pet_location,
                'response_tag'    => $systemTag,
            ]);

            $room->name = $request->title ?: $this->autoTitleFromQuestion($request->question);
            $room->touch();
            $room->save();

            return response()->json([
                'status'           => 'success',
                'chat_room_id'     => $room->id,
                'chat_room_token'  => $room->chat_room_token,
                'room_name'        => $room->name,
                'context_token'    => $contextToken,
                'emergency_status' => $level,
                'system_tag'       => $systemTag,
                'booking_options'  => $this->bookingOptions($systemTag),
                'climate_alerts'   => $climateAlerts,
                'weather'          => $wx,
                'air_quality'      => $aqi,
                'season'           => $season,
                'chat'             => $chat,
            ]);
        }

        // Emergency short-circuit (keyword)
        $level = $this->detectEmergencyLevel((string) $request->question);
        if ($this->detectEmergencyKeyword($request->question)) {
            $name = $request->pet_name ?: 'your pet';
            $loc  = $request->pet_location ?: 'your area';
            $emergencyResponse =
                "This sounds like an emergency with {$name}!\n\n" .
                "Please contact an emergency vet immediately:\n" .
                "- Search \"emergency vet near {$loc}\"\n" .
                "- Call your regular vet's emergency line\n" .
                "- Go to nearest animal hospital\n\n" .
                "This cannot wait - seek professional help now!\n\n" .
                "⚠️ AI advice. Contact emergency veterinarian immediately.\n\n" .
                "EMERGENCY";

            $answer = $this->stripMarkdownToPlain($this->enforceSnoutBrand($emergencyResponse));
            $chat = Chat::create([
                'user_id'         => $userId,
                'chat_room_id'    => $room->id,
                'chat_room_token' => $room->chat_room_token,
                'context_token'   => $contextToken,
                'question'        => $request->question,
                'answer'          => $answer,
                'pet_name'        => $request->pet_name,
                'pet_breed'       => $request->pet_breed,
                'pet_age'         => $request->pet_age,
                'pet_location'    => $request->pet_location,
                'response_tag'    => 'EMERGENCY',
            ]);

            $room->name = $request->title ?: $this->autoTitleFromQuestion($request->question);
            $room->touch();
            $room->save();

            return response()->json([
                'status'           => 'success',
                'chat_room_id'     => $room->id,
                'chat_room_token'  => $room->chat_room_token,
                'room_name'        => $room->name,
                'context_token'    => $contextToken,
                'emergency_status' => $level,
                'system_tag'       => 'EMERGENCY',
                'booking_options'  => $this->bookingOptions('EMERGENCY'),
                'climate_alerts'   => $climateAlerts,
                'weather'          => $wx,
                'air_quality'      => $aqi,
                'season'           => $season,
                'chat'             => $chat,
            ]);
        }

        // Build Gemini payload (now includes climate + last 3 turns)
        $payload = $this->buildGeminiPayload($request->all(), $lastChat);

        // Call Gemini
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'GEMINI_API_KEY missing in .env'], 500);
        }

        $resp = Http::withHeaders([
            'Content-Type'   => 'application/json',
            'X-goog-api-key' => $apiKey,
        ])->timeout(30)->post(
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
        $answerTxt = $this->stripMarkdownToPlain($answerRaw);
        $systemTag = $this->parseSystemTagFromModel($answerRaw);

        // Save message
        $chat = Chat::create([
            'user_id'         => $userId,
            'chat_room_id'    => $room->id,
            'chat_room_token' => $room->chat_room_token,
            'context_token'   => $contextToken,
            'question'        => $request->question,
            'answer'          => $answerTxt,
            'pet_name'        => $request->pet_name,
            'pet_breed'       => $request->pet_breed,
            'pet_age'         => $request->pet_age,
            'pet_location'    => $request->pet_location,
            'response_tag'    => $systemTag, // <- make sure column exists
        ]);

        // Refresh room name on every question
        $room->name = $request->title ?: $this->autoTitleFromQuestion($request->question);
        $room->touch();
        $room->save();

        return response()->json([
            'status'           => 'success',
            'chat_room_id'     => $room->id,
            'chat_room_token'  => $room->chat_room_token,
            'room_name'        => $room->name,
            'context_token'    => $contextToken,
            'emergency_status' => $level,
            'system_tag'       => $systemTag,
            'booking_options'  => $this->bookingOptions($systemTag),
            'climate_alerts'   => $climateAlerts,
            'weather'          => $wx,
            'air_quality'      => $aqi,
            'season'           => $season,
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

//     private function buildGeminiPayload(array $req, ?Chat $lastChat): array
//     {
//         $petContextBlock = "";
//         if (!empty($req['pet_name']) || !empty($req['pet_breed']) || !empty($req['pet_age']) || !empty($req['pet_location'])) {
//             $petContextBlock =
//                 "Pet Profile:\n" .
//                 "- Pet Name: " . ($req['pet_name'] ?? 'Not specified') . "\n" .
//                 "- Breed: " . ($req['pet_breed'] ?? 'Mixed/Unknown breed') . "\n" .
//                 "- Age: " . ($req['pet_age'] ?? 'Age not specified') . " years old\n" .
//                 "- Location: " . ($req['pet_location'] ?? 'India (general advice)');
//         }

//         $lastQnA = "";
//         if ($lastChat) {
//             $lastQnA =
//                 "Previous Exchange:\n" .
//                 "- Last Question: " . trim($lastChat->question) . "\n" .
//                 "- Last Answer: " . mb_substr(trim($lastChat->answer), 0, 800) . (mb_strlen($lastChat->answer) > 800 ? "..." : "");
//         }

//         $system = <<<SYS
// Role: Act as SnoutAI Assistant, an empathetic guide for Indian pet parents. You are NOT a vet and cannot diagnose or treat. Your role is to comfort worried pet parents and guide them to appropriate care.

// Response Structure:
// 1) Acknowledge emotion first (e.g., "I understand how worrying this must be...")
// 2) Safety first: "It's important to have a veterinarian examine the pet for this."
// 3) Short educational context
// 4) Natural guidance (no sales tone)

// Safety Rules:
// - Never diagnose or prescribe treatment/medicines
// - For symptoms: emotion → vet recommendation → context → guidance
// - For emergencies: be urgent but compassionate

// Branding:
// - Never call yourself Gemini/Google
// - Introduce as "SnoutAI Assistant" only

// Output Formatting:
// - PLAIN TEXT only. No Markdown, *, **, headings, or code blocks.
// - If needed, use simple numbered points like "1)".
// SYS;

//         $userParts = array_filter([
//             $petContextBlock,
//             $lastQnA,
//             "Current Question: " . ($req['question'] ?? '')
//         ]);
//         $userPrompt = implode("\n\n", $userParts);

//         return [
//             "systemInstruction" => [
//                 "role"  => "system",
//                 "parts" => [ [ "text" => $system ] ],
//             ],
//             "contents" => [
//                 [
//                     "role"  => "user",
//                     "parts" => [ [ "text" => $userPrompt ] ],
//                 ],
//             ],
//             "generationConfig" => [
//                 "temperature"      => 0.7,
//                 "topK"             => 40,
//                 "topP"             => 0.95,
//                 "maxOutputTokens"  => 800,
//                 "stopSequences"    => [],
//             ],
//         ];
//     }

//     private function detectEmergencyLevel(string $question): string
//     {
//         $map = [
//             '🚨 CRITICAL' => ['unconscious','not breathing','severe bleeding','seizure','collapse'],
//             '⚠️ URGENT'   => ['chocolate','vomiting blood','poisoned','ate poison','toxic'],
//             '🟡 PRIORITY' => ['difficulty breathing','vomiting repeatedly','severe pain',"won't eat",'lethargic'],
//         ];
//         $q = strtolower($question);
//         foreach ($map as $tag => $list) {
//             foreach ($list as $kw) {
//                 if (str_contains($q, $kw)) {
//                     return "$tag - Seek immediate veterinary care!";
//                 }
//             }
//         }
//         return "ℹ️ Routine inquiry";
//     }

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
