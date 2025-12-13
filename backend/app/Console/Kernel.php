<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Commands\DispatchConsultationReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        DispatchConsultationReminders::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Heartbeat to verify scheduler is running
        $schedule->call(function () {
            Log::info('Scheduler heartbeat', [
                'ts' => now()->toDateTimeString(),
                'env' => app()->environment(),
            ]);
        })->everyMinute();

        // 12:05 IST daily: publishNightSlots(today IST)
        $schedule->command('video:publish-tonight')
            ->timezone('Asia/Kolkata')
            ->dailyAt('12:05');

        // Hourly at H:05 IST: autoPromoteBenchIfPrimaryNoShow(current hour)
        $schedule->call(function () {
            $nowIST = \Carbon\Carbon::now('Asia/Kolkata');
            $utc = $nowIST->copy()->setTimezone('UTC');
            $date = $utc->toDateString();
            $hour = (int)$utc->format('G');
            $primaries = \App\Models\VideoSlot::query()
                ->where('slot_date', $date)
                ->where('hour_24', $hour)
                ->where('role', 'primary')
                ->where('status', 'committed')
                ->get();
            $svc = app(\App\Services\CommitmentService::class);
            foreach ($primaries as $p) {
                $svc->autoPromoteBenchIfPrimaryNoShow($p);
            }
        })->timezone('Asia/Kolkata')->hourlyAt(5);

        // Every 15m: reopen expired HELD slots and bump scarcity for near-start OPEN slots
        $schedule->call(function () {
            $now = now('UTC');
            $slots = \App\Models\VideoSlot::query()
                ->whereIn('status', ['held','open'])
                ->get();
            foreach ($slots as $s) {
                $exp = $s->meta['hold_expires_at'] ?? null;
                if ($exp && $now->greaterThan(new \Carbon\Carbon($exp))) {
                    $meta = $s->meta ?? [];
                    unset($meta['held_by'], $meta['hold_expires_at']);
                    $s->meta = $meta;
                    $s->status = 'open';
                    $s->save();
                }
                // Scarcity bump: if slot starts within next 120 minutes
                $slotStartUtc = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $s->slot_date.' '.str_pad((string)$s->hour_24,2,'0',STR_PAD_LEFT).':00:00', 'UTC');
                $diffMin = $now->diffInMinutes($slotStartUtc, false);
                if ($s->status === 'open' && $diffMin >= 0 && $diffMin <= 120) {
                    $s->demand_score = min(1.40, max((float)$s->demand_score, 1.10));
                    $s->save();
                }
            }
        })->everyFifteenMinutes();

        // 06:59 IST: placeholder for reliability updates
        $schedule->call(function () {
            // Aggregations can be added if needed
        })->timezone('Asia/Kolkata')->dailyAt('06:59');

        $schedule->command('push:process-scheduled')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('notifications:consult-reminders')
            ->everyMinute()
            ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
