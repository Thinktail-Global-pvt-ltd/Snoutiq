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

    /** ---- Public Endpoint ---- */
    public function sendMessage(Request $request)
    {
       //dd($request->all());
        $request->validate([
            'user_id'         => 'required|integer',
           // 'chat_room_token' => 'required_without:chat_room_id|string',
           // 'chat_room_id'    => 'required_without:chat_room_token|integer',
            'question'        => 'required|string',
            'context_token'   => 'nullable|string',
            'title'           => 'nullable|string',
            'pet_name'        => 'nullable|string',
            'pet_type'        => 'nullable|string', // Dog/Cat
            'pet_breed'       => 'nullable|string',
            'pet_age'         => 'nullable|string',
            'pet_location'    => 'nullable|string',
        ]);
       

        $userId = (int) $request->user_id;

        // Find room by id or token (token-based share supported)
        if ($request->filled('chat_room_id')) {
            $room = ChatRoom::where('id', $request->chat_room_id)
                ->where('user_id', $userId)
                ->firstOrFail();
        } else {
            $room = ChatRoom::where('chat_room_token', $request->chat_room_token)->firstOrFail();
        }

        $contextToken = $request->context_token ?: Str::uuid()->toString();

        // Previous context
        $lastChat  = Chat::where('chat_room_id', $room->id)->latest()->first();
        $exchanges = Chat::where('chat_room_id', $room->id)->count();

        // Climate snapshot for prompt + response
        $wx       = $this->getWeatherData($request->pet_location ?? null);
        $aqi      = $this->getAirQualityData($request->pet_location ?? null);
        [$season] = $this->getCurrentSeason();
        $climateAlerts = $this->generateClimateAlerts([
            'name'     => $request->pet_name,
            'breed'    => $request->pet_breed,
            'location' => $request->pet_location,
        ], $wx, $aqi);

        // Build payload (single request expected to return JSON {answer, diagnosis, tag})
        $payload = $this->buildGeminiPayload($request->all(), $lastChat, $exchanges);

        // Call Gemini
        $apiKey = 'AIzaSyCIB0yfzSQGGwpVUruqy_sd2WqujTLa1Rk';
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

        // Parse single-line JSON result
        $raw = $resp->json('candidates.0.content.parts.0.text') ?? '{}';
        
        [$answerTxt, $diagnosisTxt, $systemTag] = $this->parseModelJson($raw);
       $answerTxt = $this->formatStarsToPoints($answerTxt); // ← add this line

        // Save chat with diagnosis + emergency_status
        $emergencyStatus = $systemTag; // EMERGENCY | URGENT | ROUTINE | INFO

        $chat = Chat::create([
            'user_id'         => $userId,
            'chat_room_id'    => $room->id,
            'chat_room_token' => $room->chat_room_token,
            'context_token'   => $contextToken,
            'question'        => $request->question,
            'answer'          => $answerTxt,
            'diagnosis'       => $diagnosisTxt,
            'pet_name'        => $request->pet_name,
            'pet_breed'       => $request->pet_breed,
            'pet_age'         => $request->pet_age,
            'pet_location'    => $request->pet_location,
            'response_tag'    => $systemTag,
            'emergency_status'=> $emergencyStatus,
        ]);

        // Update room name + last emergency status
        $room->last_emergency_status = $emergencyStatus;
        $room->name = $request->title ?: $this->autoTitleFromQuestion($request->question);
        $room->touch();
        $room->save();

        return response()->json([
            'status'           => 'success',
            'chat_room_id'     => $room->id,
            'chat_room_token'  => $room->chat_room_token,
            'room_name'        => $room->name,
            'context_token'    => $contextToken,
            'system_tag'       => $systemTag,
            'emergency_status' => $emergencyStatus,
            'diagnosis'        => $diagnosisTxt,
           // 'booking_options'  => $this->bookingOptions($systemTag),
            'climate_alerts'   => $climateAlerts,
            'weather'          => $wx,
            'air_quality'      => $aqi,
            'season'           => $season,
            'chat'             => $chat,
        ]);
    }


    /**
 * Convert inline or line-start '***' stars into bullet-point lines.
 * Preserves the disclaimer line at the end of the answer.
 */
private function formatStarsToPoints(string $txt): string
{
    $t = trim($txt);
    if ($t === '') return $txt;

    // Try to protect the disclaimer (usually the last line)
    $lines = preg_split('/\R/', $t);
    $disclaimer = '';
    if (!empty($lines)) {
        $maybeLast = trim(end($lines));
        if (preg_match('/⚠️\s*AI advice|Consult veterinarian/i', $maybeLast)) {
            array_pop($lines);
            $disclaimer = $maybeLast;
            $t = implode("\n", $lines);
        }
    }

    $out = $t;

    // Case A: inline separators like "Intro *** point1 *** point2"
    if (strpos($t, '***') !== false) {
        $chunks = preg_split('/\*{3,}/', $t);
        $chunks = array_map('trim', $chunks);
        $chunks = array_filter($chunks, function ($c) { return $c !== ''; });

        if (count($chunks) > 1) {
            // Keep an optional intro (first chunk) and bullet the rest
            $head = array_shift($chunks);
            $bullets = '• ' . implode("\n• ", $chunks);
            $out = (strlen($head) ? ($head . "\n\n" . $bullets) : $bullets);
        }
    }

    // Case B: lines that *start* with *** (convert to bullets)
    $out = preg_replace('/^\s*\*{3,}\s*/m', '• ', $out);

    // Re-attach disclaimer if we pulled it off
    if ($disclaimer !== '') {
        $out = rtrim($out) . "\n\n" . $disclaimer;
    }

    return $out;
}


    /** ---- Payload builder (JSON-only output) ---- */
    private function buildGeminiPayload(array $req, ?Chat $lastChat, int $exchanges): array
    {
        // Pet context
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

        // Climate snapshot
        $wx  = $this->getWeatherData($req['pet_location'] ?? null);
        $aqi = $this->getAirQualityData($req['pet_location'] ?? null);
        [$season] = $this->getCurrentSeason();

        $climateSnapshot =
            "Climate Snapshot:\n" .
            "- Season: " . ucfirst($season) . "\n" .
            "- Weather: " . ucfirst($wx['description'] ?? 'n/a') .
              ", Temp: " . ($wx['temp'] ?? 'n/a') . "°C, Feels: " . ($wx['feels_like'] ?? 'n/a') . "°C, Humidity: " . ($wx['humidity'] ?? 'n/a') . "%\n" .
            "- Air Quality: " . ucfirst(str_replace('_',' ',$aqi['level'] ?? 'moderate')) . " (AQI " . ($aqi['aqi'] ?? 'n/a') . ")";

        // Last up to 3 exchanges
        $historyBlock = "";
        if ($lastChat) {
            $recent = Chat::where('chat_room_id', $lastChat->chat_room_id)->orderByDesc('id')->limit(3)->get()->reverse();
            if ($recent->count()) {
                $lines = ["Previous conversation (last up to 3 exchanges):"];
                foreach ($recent as $c) {
                    $lines[] = "User: " . trim($c->question);
                    $lines[] = "AI: "   . mb_substr(trim($c->answer), 0, 800) . (mb_strlen($c->answer) > 800 ? "..." : "");
                }
                $historyBlock = implode("\n", $lines);
            }
        }

        // Strict JSON output system prompt
        $system = <<<SYS
You are SnoutAI Assistant for Indian pet parents. Do NOT diagnose or prescribe medicines. Be empathetic, short, practical, and consider climate/air quality context.

Return ONLY a single-line JSON object with exactly these keys:
{
  "answer": "plain text guidance with empathy + 'It's important to have a veterinarian examine the pet for this.' + the line: '⚠️ AI advice. Consult veterinarian for health concerns.'",
  "diagnosis": "plain text NON-DIAGNOSTIC possible causes and what a vet might check (no medicines).",
  "tag": "EMERGENCY|URGENT|ROUTINE|INFO"
}
No extra text, no code fences, no Markdown.

Rules:
1) Acknowledge emotion first.
2) Include: "It's important to have a veterinarian examine the pet for this."
3) Keep concise and practical.
4) Use climate/air-quality when relevant.
5) Brand-safe: never say Gemini/Google; use "SnoutAI Assistant" if needed.
6) If prior exchanges in this room are >= 5, append to 'answer':
   "For continued detailed guidance, please start a new conversation or book a video consultation with our veterinarians."
SYS;

        $userParts = array_filter([
            $petContextBlock,
            $climateSnapshot,
            "Prior exchange count: {$exchanges}",
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

    /** ---- Model JSON parser (answer, diagnosis, tag) ---- */
   private function parseModelJson(string $raw): array
{
    // 0) Never halt here:
    // dd($parsed); // ❌ REMOVE THIS

    // 1) Clean obvious wrappers / BOM / code fences
    $clean = trim($raw);
    // Remove UTF-8 BOM if present
    $clean = preg_replace('/^\xEF\xBB\xBF/', '', $clean);
    // Strip ```json ... ``` fences (any language fence)
    $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $clean);

    // Normalize curly quotes → straight quotes (helps JSON)
    $clean = str_replace(["\u{201C}", "\u{201D}"], '"', $clean); // “ ”
    $clean = str_replace(["\u{2018}", "\u{2019}"], "'", $clean); // ‘ ’

    // 2) Try strict decode as-is
    $parsed = json_decode($clean, true);
    if (is_array($parsed)) {
        return $this->finalizeParsed($parsed, $raw);
    }

    // 3) Try extracting FIRST balanced {...} block and decode that
    if ($json = $this->extractFirstJsonObject($clean)) {
        $parsed = json_decode($json, true);
        if (is_array($parsed)) {
            return $this->finalizeParsed($parsed, $raw);
        }

        // Light fixes for common LLM mistakes (single-quoted keys/values, trailing commas)
        $fixed = $this->attemptJsonFixes($json);
        $parsed = json_decode($fixed, true);
        if (is_array($parsed)) {
            return $this->finalizeParsed($parsed, $raw);
        }
    }

    // 4) Regex fallback: pull fields even if it's not valid JSON
    $answer = null; $diagnosis = null; $tag = 'INFO';

    // answer: "..."  OR '...'  OR answer: ...
    if (preg_match('/["\']?answer["\']?\s*:\s*("|\')(.*?)\1/s', $clean, $m)) {
        $answer = $m[2];
    }
    if (!$answer && preg_match('/\banswer\s*:\s*(.+?)(?:,["\']?(diagnosis|tag)["\']?\s*:|}$)/si', $clean, $m)) {
        $answer = trim($m[1]);
    }

    if (preg_match('/["\']?diagnosis["\']?\s*:\s*("|\')(.*?)\1/s', $clean, $m)) {
        $diagnosis = $m[2];
    } elseif (preg_match('/\bdiagnosis\s*:\s*(.+?)(?:,["\']?tag["\']?\s*:|}$)/si', $clean, $m)) {
        $diagnosis = trim($m[1]);
    }

    if (preg_match('/["\']?tag["\']?\s*:\s*("|\')(EMERGENCY|URGENT|ROUTINE|INFO)\1/i', $clean, $m)) {
        $tag = strtoupper($m[2]);
    } else {
        $tag = $this->parseSystemTagFromModel($raw); // last-line fallback
    }

    // If still no answer, use the raw body (not ideal, but better than empty)
    if (!$answer) $answer = $clean;

    // Branding + plain text + disclaimer
    $answer    = $this->ensureDisclaimer($this->stripMarkdownToPlain($this->enforceSnoutBrand($answer)));
    $diagnosis = $diagnosis ? $this->stripMarkdownToPlain($this->enforceSnoutBrand($diagnosis)) : null;

    if (!in_array($tag, ['EMERGENCY','URGENT','ROUTINE','INFO'], true)) $tag = 'INFO';

    return [$answer, $diagnosis, $tag];
}
/**
 * After decode, normalize fields and apply branding/disclaimer.
 */
private function finalizeParsed(array $parsed, string $raw): array
{
    $answer    = (string)($parsed['answer'] ?? '');
    $diagnosis = isset($parsed['diagnosis']) ? (string)$parsed['diagnosis'] : null;
    $tag       = strtoupper((string)($parsed['tag'] ?? 'INFO'));

    if (!in_array($tag, ['EMERGENCY','URGENT','ROUTINE','INFO'], true)) {
        $tag = $this->parseSystemTagFromModel($raw);
        if (!in_array($tag, ['EMERGENCY','URGENT','ROUTINE','INFO'], true)) $tag = 'INFO';
    }

    $answer    = $this->ensureDisclaimer($this->stripMarkdownToPlain($this->enforceSnoutBrand($answer)));
    $diagnosis = $diagnosis ? $this->stripMarkdownToPlain($this->enforceSnoutBrand($diagnosis)) : null;

    return [$answer, $diagnosis, $tag];
}

/**
 * Extract the first balanced JSON object from a string.
 * Handles braces inside quoted strings.
 */
private function extractFirstJsonObject(string $s): ?string
{
    $len = strlen($s);
    $start = strpos($s, '{');
    if ($start === false) return null;

    $depth = 0; $inStr = false; $esc = false;
    for ($i = $start; $i < $len; $i++) {
        $ch = $s[$i];

        if ($inStr) {
            if ($esc) {
                $esc = false;
            } elseif ($ch === '\\') {
                $esc = true;
            } elseif ($ch === '"') {
                $inStr = false;
            }
        } else {
            if ($ch === '"') {
                $inStr = true;
            } elseif ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($s, $start, $i - $start + 1);
                }
            }
        }
    }
    return null; // not balanced
}

/**
 * Try fixing common LLM JSON issues: trailing commas, single-quoted keys/values (best-effort).
 */
private function attemptJsonFixes(string $json): string
{
    $s = $json;

    // Remove trailing commas before } or ]
    $s = preg_replace('/,\s*([}\]])/', '$1', $s);

    // Convert single-quoted KEYS to double-quoted: {'answer': ...} -> {"answer": ...}
    $s = preg_replace('/([{,\s])\'([A-Za-z0-9_\-]+)\'\s*:/', '$1"$2":', $s);

    // Convert single-quoted simple string VALUES to double quotes (no inner quotes)
    $s = preg_replace('/:\s*\'([^\'"\\\r\n]*)\'\s*([,}])/', ':"$1"$2', $s);

    // Ensure true/false/null are lowercase (usually fine)
    // $s = preg_replace('/\b(True|False|Null)\b/', strtolower('$1'), $s);

    return $s;
}

    /** ---- Branding & formatting helpers ---- */
    private function stripMarkdownToPlain(string $txt): string
    {
        $txt = preg_replace('/\*\*(.*?)\*\*/s', '$1', $txt);
        $txt = preg_replace('/\*(.*?)\*/s', '$1', $txt);
        $txt = preg_replace('/^#{1,6}\s*/m', '', $txt);
        $txt = preg_replace('/^\s*[\*\-•]\s+/m', "• ", $txt);
        $txt = preg_replace('/`{1,3}(.*?)`{1,3}/s', '$1', $txt);
        $txt = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $txt);
        return trim($txt ?? '');
    }

    private function enforceSnoutBrand(string $txt): string
    {
        // prevent external brand mention
        $txt = str_ireplace(['gemini','google ai','bard'], 'SnoutAI Assistant', $txt);
        return $txt;
    }

    private function ensureDisclaimer(string $txt): string
    {
        $line = '⚠️ AI advice. Consult veterinarian for health concerns.';
        return (stripos($txt, $line) === false) ? rtrim($txt) . "\n\n{$line}" : $txt;
    }

    private function autoTitleFromQuestion(string $q): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($q)));
        $words = explode(' ', $clean);
        $take  = array_slice($words, 0, 8);
        $title = implode(' ', $take);
        if (count($words) > 8) $title .= '…';
        return $title ?: 'New chat';
    }

    private function parseSystemTagFromModel(string $aiText): string
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($aiText));
        $last  = strtoupper(trim(end($lines) ?: ''));
        $valid = ['EMERGENCY','URGENT','ROUTINE','INFO'];
        if (in_array($last, $valid, true)) return $last;
        foreach ($valid as $v) {
            if (stripos($aiText, $v) !== false) return $v;
        }
        return 'INFO';
    }

    /** ---- Climate / AQI ---- */
    private function getCurrentSeason(): array
    {
        $m = (int) now()->month;
        if (in_array($m, [3,4,5,6], true))  return ['summer',       ['temp' => '35-45°C','humidity'=>'high','risks'=>'heat stroke, dehydration']];
        if (in_array($m, [7,8,9], true))    return ['monsoon',      ['temp' => '25-35°C','humidity'=>'very high','risks'=>'skin infections, fungal issues']];
        if ($m === 10)                      return ['post_monsoon', ['temp' => '20-30°C','humidity'=>'moderate','risks'=>'allergies, vector diseases']];
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
                    $h = $d['hourly'] ?? [];
                    $t     = $h['temperature_2m'][0]          ?? null;
                    $rh    = $h['relative_humidity_2m'][0]    ?? null;
                    $feels = $h['apparent_temperature'][0]    ?? null;
                    $desc  = $this->wxDescription((float)$t, (float)$rh);
                    return [
                        'temp'       => is_null($t) ? null : round((float)$t, 1),
                        'humidity'   => is_null($rh) ? null : (int) round((float)$rh),
                        'feels_like' => is_null($feels) ? null : round((float)$feels, 1),
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
                        $aqi  = (int) round($pm25 * 2); // rough surrogate
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
        $name   = $pet['name'] ?? 'your pet';

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
        $lvl = $aqi['level'] ?? 'moderate';
        if ($lvl === 'unhealthy' || $lvl === 'hazardous') {
            $alerts[] = "🏭 POOR AIR QUALITY: Keep {$name} indoors today";
        }
        if (in_array($breed, $this->breedAdvisories['hardy_breeds'], true)) {
            $alerts[] = "✅ HARDY BREED: {$breed} handles {$loc}'s climate well";
        }
        $desc = $wx['description'] ?? 'current conditions';
        $alerts[] = "📊 CURRENT: " . ucfirst($desc) . " in {$loc}";
        return $alerts;
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


// public function getUserChats($user_id)
//     {
//         // check user exists
//         $user = DB::table('users')->where('id', $user_id)->first();
//         if (!$user) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'User not found'
//             ], 404);
//         }

//         // fetch chats with chat_rooms
//         $data = DB::table('chats as c')
//             ->leftJoin('chat_rooms as cr', 'c.chat_room_id', '=', 'cr.id')
//             ->where('c.user_id', $user_id)
//             ->select(
//                 'c.id as chat_id',
//                 'c.question',
//                 'c.answer',
//                 'c.response_tag',
//                 'c.pet_name',
//                 'c.pet_breed',
//                 'c.pet_age',
//                 'c.pet_location',
//                 'c.diagnosis',
//                 'c.emergency_status',
//                 'c.created_at as chat_created_at',
//                 'cr.id as room_id',
//                 'cr.chat_room_token',
//                 'cr.name as room_name',
//                 'cr.last_emergency_status',
//                 'cr.created_at as room_created_at'
//             )
//             ->orderBy('cr.id')
//             ->orderBy('c.created_at')
//             ->get();
            

//         return response()->json([
//             'success' => true,
//             'user' => $user,
//             'chats' => $data
//         ]);
//     }




public function getUserChats($user_id)
{
    $user = DB::table('users')->where('id', $user_id)->first();
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found'
        ], 404);
    }

    $chats = DB::table('chats as c')
        ->leftJoin('chat_rooms as cr', 'c.chat_room_id', '=', 'cr.id')
        ->where('c.user_id', $user_id)
        ->select(
            'c.id as chat_id',
            'c.question',
            'c.answer',
            'c.chat_room_id',
            'cr.name as room_name'
        )
        ->orderBy('c.chat_room_id')
        ->orderBy('c.created_at')
        ->get();

    if ($chats->isEmpty()) {
        return response()->json([
            'success' => true,
            'user' => $user,
            'rooms' => []
        ]);
    }

    // group by room
    $grouped = $chats->groupBy('chat_room_id');
    $roomsData = [];

    foreach ($grouped as $roomId => $roomChats) {
        $conversation = $roomChats->map(fn($c) => "Q: {$c->question}\nA: {$c->answer}")->implode("\n\n");

        // send to Gemini for summary
        $apiKey = 'AIzaSyCIB0yfzSQGGwpVUruqy_sd2WqujTLa1Rk';
        $payload = [
            "contents" => [[
                "parts" => [[
                    "text" => "Summarize this pet consultation chat for doctor:\n\n{$conversation}"
                ]]
            ]]
        ];

        $resp = Http::withHeaders([
            'Content-Type'   => 'application/json',
            'X-goog-api-key' => $apiKey,
        ])->timeout(30)->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent',
            $payload
        );

        $summary = null;
        if ($resp->ok()) {
            $json = $resp->json();
            $summary = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        // save summary in DB
        if ($summary) {
            DB::table('chat_rooms')->where('id', $roomId)->update(['summary' => $summary]);
        }

        $roomsData[] = [
            'room_id'   => $roomId,
            'room_name' => $roomChats->first()->room_name,
            'summary'   => $summary,
            'chats'     => $roomChats->map(fn($c) => [
                'chat_id'  => $c->chat_id,
                'question' => $c->question,
                'answer'   => $c->answer,
            ])
        ];
    }

    return response()->json([
        'success' => true,
        'user'    => $user,
        'rooms'   => $roomsData
    ]);
}




}
