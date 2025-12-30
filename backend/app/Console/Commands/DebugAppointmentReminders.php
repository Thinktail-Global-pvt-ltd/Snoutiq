<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\Notifications\AppointmentReminderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DebugAppointmentReminders extends Command
{
    protected $signature = 'notifications:debug-reminders';
    protected $description = 'Debug appointment reminders to see why they are not being sent';

    public function handle(): int
    {
        $this->info('=== Debugging Appointment Reminders ===');
        $this->newLine();

        $now = now();
        $this->info("Current time: {$now->toDateTimeString()} ({$now->timezone->getName()})");
        $this->newLine();

        // Get all upcoming appointments
        $appointments = Appointment::query()
            ->whereIn('status', ['confirmed', 'rescheduled'])
            ->whereDate('appointment_date', '>=', $now->copy()->subDay()->toDateString())
            ->whereNull('reminder_30m_sent_at')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $this->info("Found {$appointments->count()} appointments to check");
        $this->newLine();

        if ($appointments->isEmpty()) {
            $this->warn('No appointments found matching criteria.');
            $this->info('Criteria: status in [confirmed, rescheduled], date >= yesterday, reminder_30m_sent_at is null');
            return self::SUCCESS;
        }

        foreach ($appointments as $appointment) {
            $this->info("--- Appointment ID: {$appointment->id} ---");
            $this->line("Date: {$appointment->appointment_date}");
            $this->line("Time: {$appointment->appointment_time}");
            $this->line("Status: {$appointment->status}");
            $this->line("Notes: " . ($appointment->notes ?? 'null'));

            // Resolve start time
            if (!$appointment->appointment_date || !$appointment->appointment_time) {
                $this->error("  ❌ Missing date or time");
                $this->newLine();
                continue;
            }

            $dateTime = $appointment->appointment_date . ' ' . $appointment->appointment_time;
            try {
                $startTime = Carbon::createFromFormat('Y-m-d H:i', $dateTime, config('app.timezone', 'UTC'));
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to parse datetime: {$e->getMessage()}");
                $this->newLine();
                continue;
            }

            $this->line("Start Time: {$startTime->toDateTimeString()} ({$startTime->timezone->getName()})");

            // Check 30m reminder
            $reminderAt = $startTime->copy()->subMinutes(30);
            $this->line("30m Reminder Time: {$reminderAt->toDateTimeString()}");
            $this->line("Time until reminder: " . $now->diffForHumans($reminderAt, true));

            // Check conditions
            $isBeforeReminder = $now->lt($reminderAt);
            $isAfterStart = $now->gte($startTime);
            $shouldSend = !$isBeforeReminder && !$isAfterStart;

            $this->line("Current < Reminder Time: " . ($isBeforeReminder ? 'YES (too early)' : 'NO'));
            $this->line("Current >= Start Time: " . ($isAfterStart ? 'YES (too late)' : 'NO'));
            $this->line("Should send: " . ($shouldSend ? '✅ YES' : '❌ NO'));

            // Check patient_user_id
            $userId = null;
            if (!empty($appointment->patient_user_id)) {
                $userId = (int) $appointment->patient_user_id;
                $this->line("Patient User ID (from field): {$userId}");
            } else {
                $notes = $appointment->notes;
                if ($notes) {
                    $decoded = json_decode($notes, true);
                    if (is_array($decoded) && isset($decoded['patient_user_id'])) {
                        $userId = (int) $decoded['patient_user_id'];
                        $this->line("Patient User ID (from notes): {$userId}");
                    } else {
                        $this->error("  ❌ patient_user_id not found in notes JSON");
                    }
                } else {
                    $this->error("  ❌ No notes field, cannot get patient_user_id");
                }
            }

            if (!$userId) {
                $this->error("  ❌ Cannot send: No patient_user_id found");
            } else {
                // Check if user has device tokens
                $tokenCount = \App\Models\DeviceToken::where('user_id', $userId)->count();
                $this->line("Device tokens for user {$userId}: {$tokenCount}");
                if ($tokenCount === 0) {
                    $this->warn("  ⚠️  User has no device tokens - notification will fail");
                }
            }

            $this->newLine();
        }

        // Now run the actual service
        $this->info('=== Running AppointmentReminderService ===');
        $service = app(AppointmentReminderService::class);
        $count = $service->dispatch();
        $this->info("Service dispatched {$count} reminders");

        return self::SUCCESS;
    }
}















