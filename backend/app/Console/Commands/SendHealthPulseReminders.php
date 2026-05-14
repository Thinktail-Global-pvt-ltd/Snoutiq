<?php

namespace App\Console\Commands;

use App\Models\HealthPulseEntry;
use App\Models\Pet;
use App\Services\HealthPulseNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SendHealthPulseReminders extends Command
{
    protected $signature = 'health-pulse:send-reminders {--pet_id=} {--dry}';
    protected $description = 'Send Daily Health Pulse reminder pushes for missing or lapsed entries.';

    public function __construct(private readonly HealthPulseNotificationService $notifications)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!Schema::hasTable('health_pulse_entries') || !Schema::hasTable('pets')) {
            $this->warn('health_pulse_entries or pets table is missing.');
            return self::SUCCESS;
        }

        $now = Carbon::now('Asia/Kolkata');
        $hour = (int) $now->format('G');
        $today = $now->toDateString();
        $forcedPetId = $this->option('pet_id');
        $dry = (bool) $this->option('dry');
        $sent = 0;

        $pets = Pet::query()
            ->whereNotNull('user_id')
            ->when($forcedPetId, fn ($query) => $query->whereKey((int) $forcedPetId))
            ->orderBy('id')
            ->limit($forcedPetId ? 1 : 1000)
            ->get();

        foreach ($pets as $pet) {
            if ($this->hasEntryOn((int) $pet->id, $today)) {
                continue;
            }

            foreach ($this->triggersForPet($pet, $now, $hour, (bool) $forcedPetId) as $trigger) {
                if (!$dry) {
                    $notification = $this->notifications->sendReminder(
                        $pet,
                        $trigger['key'],
                        $trigger['title'],
                        $trigger['body']
                    );
                    if ($notification === null) {
                        continue;
                    }
                }
                $sent++;
                $this->line("{$trigger['key']} -> pet {$pet->id}");
            }
        }

        Log::info('health_pulse.reminders.finished', [
            'sent' => $sent,
            'hour_ist' => $hour,
            'forced_pet_id' => $forcedPetId,
            'dry' => $dry,
        ]);

        return self::SUCCESS;
    }

    private function triggersForPet(Pet $pet, Carbon $now, int $hour, bool $forced): array
    {
        $createdAt = $pet->created_at ? Carbon::parse($pet->created_at)->timezone('Asia/Kolkata') : null;
        $lastEntryDate = $this->lastEntryDate((int) $pet->id);
        $petName = $pet->name ?: 'Your pet';
        $triggers = [];

        if ($createdAt && ($forced || $now->diffInMinutes($createdAt) >= 240) && !$lastEntryDate) {
            $triggers[] = [
                'key' => 'install_4h_no_entry',
                'title' => 'Daily Health Pulse',
                'body' => "{$petName}'s first daily health check is pending.",
            ];
        }

        if ($createdAt && ($forced || ($hour === 9 && $createdAt->isSameDay($now->copy()->subDay())))) {
            $triggers[] = [
                'key' => 'day_2_9am_no_entry',
                'title' => 'Daily Health Pulse',
                'body' => "Log {$petName}'s quick health pulse for today.",
            ];
        }

        if ($createdAt && ($forced || ($hour === 19 && $createdAt->isSameDay($now->copy()->subDays(2))))) {
            $triggers[] = [
                'key' => 'day_3_7pm_no_entry',
                'title' => 'Daily Health Pulse',
                'body' => "{$petName}'s daily health check is still pending.",
            ];
        }

        if ($lastEntryDate) {
            $daysSince = $lastEntryDate->diffInDays($now->copy()->startOfDay());
            foreach ([
                [3, 9, 'lapse_3d_9am', "{$petName}'s health pulse has a 3-day gap."],
                [7, 20, 'lapse_7d_8pm', "{$petName}'s health timeline is missing recent entries."],
                [30, 19, 'silence_30d_7pm', "Restart {$petName}'s daily health pulse habit."],
            ] as [$days, $triggerHour, $key, $body]) {
                if ($forced || ($daysSince >= $days && $hour === $triggerHour)) {
                    $triggers[] = [
                        'key' => $key,
                        'title' => 'Daily Health Pulse',
                        'body' => $body,
                    ];
                }
            }
        }

        return $triggers;
    }

    private function hasEntryOn(int $petId, string $date): bool
    {
        return HealthPulseEntry::query()
            ->where('pet_id', $petId)
            ->whereDate('entry_date', $date)
            ->exists();
    }

    private function lastEntryDate(int $petId): ?Carbon
    {
        $date = HealthPulseEntry::query()
            ->where('pet_id', $petId)
            ->max('entry_date');

        return $date ? Carbon::parse($date)->startOfDay() : null;
    }
}
