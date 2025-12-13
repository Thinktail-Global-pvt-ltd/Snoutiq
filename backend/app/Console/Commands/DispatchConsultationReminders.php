<?php

namespace App\Console\Commands;

use App\Services\Notifications\AppointmentReminderService;
use Illuminate\Console\Command;

class DispatchConsultationReminders extends Command
{
    protected $signature = 'notifications:consult-reminders';

    protected $description = 'Send due consultation pre-reminders';

    public function handle(AppointmentReminderService $service): int
    {
        $count = $service->dispatch();
        $this->info(sprintf('Dispatched %d consultation reminders.', $count));

        return self::SUCCESS;
    }
}
