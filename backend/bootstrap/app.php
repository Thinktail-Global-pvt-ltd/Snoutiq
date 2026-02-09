<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\SendVaccineReminders;
use App\Console\Commands\SendVetResponseReminders;
use App\Console\Commands\SendMedicalRecordCreatedReminders;
use App\Console\Commands\SendUserCreatedReminders;
use App\Console\Commands\SendUserContinuityReminders;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        SendVaccineReminders::class,
        SendVetResponseReminders::class,
        SendMedicalRecordCreatedReminders::class,
        SendUserCreatedReminders::class,
        SendUserContinuityReminders::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.api_token' => \App\Http\Middleware\EnsureApiToken::class,
        ]);

        $middleware->prependToGroup('api', [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        $middleware->appendToGroup('api', [
            \App\Http\Middleware\FormatJsonResponse::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Heartbeat to verify scheduler is running
        $schedule->call(function () {
            Log::info('Scheduler heartbeat', [
                'ts' => now()->toDateTimeString(),
                'env' => app()->environment(),
            ]);
        })->everyMinute();

        // Notification / push pipelines
        $schedule->command('push:process-scheduled')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('notifications:consult-reminders')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('notifications:vet-response-reminders')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('vaccines:send-reminders')
            ->everyMinute()
            ->withoutOverlapping();

        // Pet parent records-created reminder (medical records)
        $schedule->command('notifications:pp-records-created')
            ->everyMinute()
            ->withoutOverlapping();

        // Pet parent user-created reminder (2h after user is created)
        $schedule->command('notifications:pp-user-created')
            ->everyMinute()
            ->withoutOverlapping();

        // Pet parent continuity reminder (+24h after template 1)
        $schedule->command('notifications:pp-user-continuity')
            ->everyMinute()
            ->withoutOverlapping();

        // Weather fetch every 4 hours
        $schedule->command('weather:fetch 28.6139 77.2090')->everyFourHours();
    })
    ->withExceptions()
    ->create();
