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
    protected $signature = 'health-pulse:send-reminders {--pet_id=} {--dry} {--repeat}';
    protected $description = 'Send Daily Health Check reminders for pets missing today\'s entry.';

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
        $repeat = (bool) $this->option('repeat');
        $sent = 0;

        $pets = Pet::query()
            ->whereNotNull('user_id')
            ->when($forcedPetId, fn ($query) => $query->whereKey((int) $forcedPetId))
            ->orderBy('id')
            ->limit($forcedPetId ? 1 : 1000)
            ->get();

        if ($pets->isEmpty()) {
            $this->warn('No matching pets found.');
        }

        foreach ($pets as $pet) {
            $hasTodayEntry = $this->hasEntryOn((int) $pet->id, $today);
            if ($hasTodayEntry && !$forcedPetId) {
                continue;
            }

            if ($hasTodayEntry && $forcedPetId) {
                $this->warn("pet {$pet->id} has today's entry; continuing because --pet_id is test mode.");
            }

            $triggers = $this->triggersForPet($pet, $today);
            if (empty($triggers)) {
                $this->warn("No reminder triggers matched for pet {$pet->id}.");
            }

            foreach ($triggers as $trigger) {
                if (!$dry) {
                    $delivered = $this->notifications->sendReminder(
                        $pet,
                        $trigger['key'],
                        $trigger['title'],
                        $trigger['body'],
                        $repeat
                    );
                    if (!$delivered) {
                        $this->warn("{$trigger['key']} -> pet {$pet->id} skipped or failed. Check device_tokens and fcm_notifications.");
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
            'repeat' => $repeat,
        ]);

        return self::SUCCESS;
    }

    private function triggersForPet(Pet $pet, string $today): array
    {
        $petName = $pet->name ?: 'your pet';

        return [[
            'key' => "missing_today_{$today}",
            'title' => 'Daily Health Check',
            'body' => "How is {$petName} feeling today? A quick check-in helps us spot small changes early.",
        ]];
    }

    private function hasEntryOn(int $petId, string $date): bool
    {
        return HealthPulseEntry::query()
            ->where('pet_id', $petId)
            ->whereDate('entry_date', $date)
            ->exists();
    }

}
