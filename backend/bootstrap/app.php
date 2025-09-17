<?php

use Illuminate\Foundation\Application;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware()
    ->withSchedule(function (Schedule $schedule) {
        // ✅ Har minute test ke liye
        $schedule->command('weather:fetch 28.6139 77.2090')->everyFourHours();
    })
    ->withExceptions()
    ->create();
