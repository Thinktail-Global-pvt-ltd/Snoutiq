<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\WeatherLog;

class FetchWeatherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Run: php artisan weather:fetch {lat} {lon}
     */
    protected $signature = 'weather:fetch {lat} {lon}';

    /**
     * The console command description.
     */
    protected $description = 'Fetch weather data from wttr.in using latitude and longitude and save to DB';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $lat = $this->argument('lat');
        $lon = $this->argument('lon');

        $url = "https://wttr.in/{$lat},{$lon}?format=j1";

        try {
            $resp = Http::withHeaders([
                'Accept'     => 'application/json',
                'User-Agent' => 'SnoutIQ-Weather/1.0 (+https://snoutiq.com)',
            ])->timeout(10)->get($url);

            if (!$resp->successful()) {
                $this->error("API Error: " . $resp->status());
                return self::FAILURE;
            }

            $data = $resp->json();
            $cur  = $data['current_condition'][0] ?? null;

            if (!$cur) {
                $this->error("Invalid response payload.");
                return self::FAILURE;
            }

            $weatherInfo = [
                'lat'        => $lat,
                'lon'        => $lon,
                'time'       => Carbon::now('Asia/Kolkata')->toDateTimeString(),
                'temperature'=> $cur['temp_C'] ?? null,
                'feels_like' => $cur['FeelsLikeC'] ?? null,
                'humidity'   => $cur['humidity'] ?? null,
                'weather'    => $cur['weatherDesc'][0]['value'] ?? null,
            ];

            // ✅ Save to DB
            WeatherLog::create($weatherInfo);

            // Log bhi karega
            Log::info('Weather fetch success', $weatherInfo);

            $this->info("✅ Weather saved to DB: " . json_encode($weatherInfo));

            return self::SUCCESS;

        } catch (\Throwable $e) {
            Log::error("Weather fetch failed", ['error' => $e->getMessage()]);
            $this->error("❌ Exception: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
