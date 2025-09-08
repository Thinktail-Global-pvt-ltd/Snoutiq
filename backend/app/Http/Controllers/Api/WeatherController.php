<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WeatherController extends Controller
{
    /** Optional synonyms map */
    private array $citySynonyms = [
        'bangalore' => 'Bengaluru',
        'gurgaon'   => 'Gurugram',
    ];

    /** GET /api/weather/cities?cities=Delhi,Mumbai,Bangalore */
    public function cities(Request $request)
    {
        $citiesParam = $request->query('cities');
        $cities = $citiesParam
            ? array_filter(array_map('trim', explode(',', $citiesParam)))
            : ['Delhi','Mumbai','Bangalore','Chennai','Gurugram'];

        $out = [];
        foreach ($cities as $c) {
            $city = $this->normalizeCity($c);
            $data = $this->fetchWttr($city);

            if (!$data['ok']) {
                $out[] = [
                    'city' => $city,
                    'ok'   => false,
                    'error'=> $data['error'] ?? 'upstream error',
                ];
                continue;
            }

            $cur = $data['json']['current_condition'][0] ?? null;
            $feels = $cur ? (int)($cur['FeelsLikeC'] ?? 0) : null;

            $out[] = [
                'city'        => $city,
                'ok'          => true,
                'timestamp_ist' => Carbon::now('Asia/Kolkata')->toIso8601String(),
                'temperatureC'=> $cur['temp_C'] ?? null,
                'feelsLikeC'  => $cur['FeelsLikeC'] ?? null,
                'humidity'    => $cur['humidity'] ?? null,
                'weather'     => $cur['weatherDesc'][0]['value'] ?? null,
                'pet_safety'  => $this->quickPetSafety($feels),
            ];
        }

        return response()->json([
            'status' => 'success',
            'result_count' => count($out),
            'results' => $out,
        ]);
    }

    /** GET /api/weather/detail?city=Delhi */
    public function detail(Request $request)
    {
        $request->validate([
            'city' => 'nullable|string',
        ]);
        $city = $this->normalizeCity($request->query('city', 'Delhi'));

        $data = $this->fetchWttr($city);
        if (!$data['ok']) {
            return response()->json(['status'=>'error','city'=>$city,'message'=>$data['error'] ?? 'upstream error'], 502);
        }

        $json = $data['json'];
        $cur  = $json['current_condition'][0] ?? [];
        $today= $json['weather'][0] ?? [];

        $temp       = isset($cur['temp_C']) ? (int)$cur['temp_C'] : null;
        $feels      = isset($cur['FeelsLikeC']) ? (int)$cur['FeelsLikeC'] : null;
        $humidity   = isset($cur['humidity']) ? (int)$cur['humidity'] : null;

        return response()->json([
            'status'        => 'success',
            'city'          => $city,
            'timestamp_ist' => Carbon::now('Asia/Kolkata')->toIso8601String(),
            'current' => [
                'temperatureC' => $cur['temp_C'] ?? null,
                'feelsLikeC'   => $cur['FeelsLikeC'] ?? null,
                'humidity'     => $cur['humidity'] ?? null,
                'weather'      => $cur['weatherDesc'][0]['value'] ?? null,
                'windspeedKmph'=> $cur['windspeedKmph'] ?? null,
                'winddirDegree'=> $cur['winddirDegree'] ?? null,
                'visibilityKm' => $cur['visibility'] ?? null,
                'uvIndex'      => $cur['uvIndex'] ?? null,
                'pet_safety'   => $this->quickPetSafety($feels),
            ],
            'today_forecast' => [
                'maxTempC' => $today['maxtempC'] ?? null,
                'minTempC' => $today['mintempC'] ?? null,
                'sunrise'  => $today['astronomy'][0]['sunrise'] ?? null,
                'sunset'   => $today['astronomy'][0]['sunset'] ?? null,
            ],
            'pet_care_recommendations' => $this->petCareRecommendations($temp, $feels, $humidity),
            'raw' => $request->boolean('include_raw') ? $json : null, // optional debug
        ]);
    }

    /** GET /api/weather/hourly-schedule?city=Delhi */
    public function hourlySchedule(Request $request)
    {
        $request->validate([
            'city' => 'nullable|string',
        ]);
        $city = $this->normalizeCity($request->query('city', 'Delhi'));

        $data = $this->fetchWttr($city);
        if (!$data['ok']) {
            return response()->json(['status'=>'error','city'=>$city,'message'=>$data['error'] ?? 'upstream error'], 502);
        }

        $json  = $data['json'];
        $today = $json['weather'][0] ?? [];
        $hours = $today['hourly'] ?? [];

        $schedule = [];
        foreach ($hours as $h) {
            $time24     = $h['time'] ?? '0';             // e.g., "0","300","600"
            $temp       = isset($h['tempC']) ? (int)$h['tempC'] : null;
            $feels      = isset($h['FeelsLikeC']) ? (int)$h['FeelsLikeC'] : null;
            $desc       = $h['weatherDesc'][0]['value'] ?? '';
            $activity   = $this->activityForHour($feels, $desc);

            $schedule[] = [
                'time_local'  => $this->formatWttrTimeTo12h($time24),
                'temperatureC'=> $temp,
                'feelsLikeC'  => $feels,
                'weather'     => $desc,
                'recommend'   => $activity,
            ];
        }

        return response()->json([
            'status'        => 'success',
            'city'          => $city,
            'date_ist'      => Carbon::now('Asia/Kolkata')->toDateString(),
            'schedule'      => $schedule,
        ]);
    }

    /* ==================== Helpers ==================== */

    private function normalizeCity(string $city): string
    {
        $c = trim($city);
        $lc = strtolower($c);
        return $this->citySynonyms[$lc] ?? $c;
    }

    /** Call wttr.in (cached 5 min) */
    private function fetchWttr(string $city): array
    {
        $cacheKey = 'wttr:' . md5($city);
        return Cache::remember($cacheKey, 300, function () use ($city) {
            try {
                $url = 'https://wttr.in/' . urlencode($city) . '?format=j1';
                $resp = Http::withHeaders([
                    'Accept'     => 'application/json',
                    'User-Agent' => 'SnoutIQ-Weather/1.0 (+https://snoutiq.com)',
                ])->timeout(10)->get($url);

                if (!$resp->successful()) {
                    return ['ok'=>false, 'error'=>"upstream status {$resp->status()}"];
                }
                $json = $resp->json();
                if (!is_array($json) || empty($json['current_condition'][0])) {
                    return ['ok'=>false, 'error'=>'unexpected upstream payload'];
                }
                return ['ok'=>true, 'json'=>$json];
            } catch (\Throwable $e) {
                return ['ok'=>false, 'error'=>$e->getMessage()];
            }
        });
    }

    /** quick flag for multiple-cities endpoint */
    private function quickPetSafety(?int $feels): array
    {
        if (!is_int($feels)) return ['level'=>'unknown','note'=>'n/a'];
        if ($feels >= 32) return ['level'=>'danger', 'note'=>'Too hot for pets'];
        if ($feels >= 28) return ['level'=>'caution','note'=>'Caution for pets'];
        return ['level'=>'ok','note'=>'OK for pets'];
    }

    /** detailed pet-care recommendations like your notebook */
    private function petCareRecommendations(?int $temp, ?int $feels, ?int $humidity): array
    {
        $tips = [];

        if (is_int($feels)) {
            if ($feels >= 35) {
                $tips[] = "🚨 EXTREME HEAT ALERT: Keep pets indoors with cooling; no outdoor walks; plenty of fresh water; monitor for heat stroke.";
            } elseif ($feels >= 30) {
                $tips[] = "⚠️ HOT WEATHER: Walk early morning (before 7 AM) or late evening (after 7 PM); limit outdoor time to 15–20 minutes; check pavement heat.";
            } elseif ($feels >= 25) {
                $tips[] = "✅ WARM WEATHER (CAUTION): Prefer cooler parts of day; carry water; watch for overheating.";
            } elseif ($feels <= 10) {
                $tips[] = "🥶 COLD WEATHER: Consider clothing for small/short-haired pets; limit outdoor time; protect paw pads.";
            } else {
                $tips[] = "✅ COMFORTABLE: Normal outdoor activity is fine.";
            }
        }

        if (is_int($humidity)) {
            if ($humidity >= 80) {
                $tips[] = "💧 High humidity — pets cool down slower. Reduce exertion and ensure ventilation.";
            } elseif ($humidity <= 30) {
                $tips[] = "🏜️ Low humidity — ensure extra hydration.";
            }
        }

        return $tips;
    }

    private function activityForHour(?int $feelsLike, string $weatherDesc): string
    {
        $w = strtolower($weatherDesc);
        if (strpos($w, 'rain') !== false || strpos($w, 'storm') !== false) {
            return "🌧️ INDOOR time";
        }
        if (is_int($feelsLike)) {
            if ($feelsLike <= 25) return "✅ GOOD for walks";
            if ($feelsLike <= 30) return "⚠️ SHORT walks only";
            return "🚨 TOO HOT - stay inside";
        }
        return "ℹ️ Check conditions";
    }

    /** Convert wttr 'time' like "0","300","600" to "HH:MM AM/PM" */
    private function formatWttrTimeTo12h(string $t): string
    {
        $n = (int)$t; // e.g., 0, 300, 600, 900, ...
        $hour = intdiv($n, 100);
        $dt = Carbon::now('Asia/Kolkata')->setTime($hour, 0, 0);
        return $dt->format('h:i A');
    }

    public function byCoords(Request $request)
{
 //   dd($request->all());
    $request->validate([
        'lat' => 'required|numeric',
        'lon' => 'required|numeric',
    ]);

    $lat = $request->query('lat');
    $lon = $request->query('lon');

    try {
        // wttr.in API with coords
        $url = "https://wttr.in/{$lat},{$lon}?format=j1";
        $resp = Http::withHeaders([
            'Accept'     => 'application/json',
            'User-Agent' => 'SnoutIQ-Weather/1.0 (+https://snoutiq.com)',
        ])->timeout(10)->get($url);

        if (!$resp->successful()) {
            return response()->json([
                'status'  => 'error',
                'message' => "Upstream error " . $resp->status(),
            ], 502);
        }

        $json = $resp->json();
      //  dd($json);
        $cur  = $json['current_condition'][0] ?? null;

        return response()->json([
            'status'        => 'success',
            'lat'           => $lat,
            'lon'           => $lon,
            'timestamp_ist' => now('Asia/Kolkata')->toIso8601String(),
            'current' => [
                'temperatureC' => $cur['temp_C'] ?? null,
                'feelsLikeC'   => $cur['FeelsLikeC'] ?? null,
                'humidity'     => $cur['humidity'] ?? null,
                'weather'      => $cur['weatherDesc'][0]['value'] ?? null,
                'windspeedKmph'=> $cur['windspeedKmph'] ?? null,
                'winddirDegree'=> $cur['winddirDegree'] ?? null,
            ],
            'forecast_today'=> $json['weather'][0] ?? null,
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
}

}
