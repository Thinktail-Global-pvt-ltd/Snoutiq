<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Pet;
use App\Services\Notifications\NotificationChannelService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class SendVaccineReminders extends Command
{
    private const PRE_NOTICE_DAYS = 7;
    private const WEEKS_PER_MONTH = 4.345;

    /**
     * Age-based puppy milestones expressed in weeks.
     *
     * @var array<int,array<string,mixed>>
     */
    private const PUPPY_MILESTONES = [
        ['key' => 'dhp_6_8w', 'label' => 'DHP (first dose)', 'start_weeks' => 6.0, 'end_weeks' => 8.0],
        ['key' => 'dhppil_8_10w', 'label' => 'DHPPiL (first combo dose)', 'start_weeks' => 8.0, 'end_weeks' => 10.0],
        ['key' => 'dhppil_10_12w', 'label' => 'DHPPiL (booster)', 'start_weeks' => 10.0, 'end_weeks' => 12.0],
        ['key' => 'rabies_12w', 'label' => 'Rabies (first dose)', 'start_weeks' => 12.0, 'end_weeks' => 13.0],
        ['key' => 'rabies_14_16w', 'label' => 'Rabies (booster)', 'start_weeks' => 14.0, 'end_weeks' => 16.0],
        ['key' => 'dhppil_16w', 'label' => 'DHPPiL (booster)', 'start_weeks' => 16.0, 'end_weeks' => 17.0],
        ['key' => 'dhppil_6m', 'label' => 'DHPPiL (6 month booster)', 'start_weeks' => 26.0, 'end_weeks' => 28.0],
        ['key' => 'dhppil_rabies_12m', 'label' => 'DHPPiL + Rabies (12 month booster)', 'start_weeks' => 52.0, 'end_weeks' => 56.0],
    ];

    protected $signature = 'vaccines:send-reminders {--pet_id=}';

    protected $description = 'Send age-based puppy/adult vaccination reminders to pet owners.';

    public function handle(NotificationChannelService $channelService): int
    {
        $sent = 0;
        $skipped = 0;
        $errors = 0;
        $missingAge = 0;
        $duplicates = 0;
        $sentSamples = [];
        $errorSamples = [];

        $query = Pet::query()
            ->whereNotNull('user_id')
            ->orderBy('id');

        $petId = $this->option('pet_id');
        if ($petId) {
            $query->whereKey((int) $petId);
        }

        $total = (clone $query)->count();

        Log::info('vaccination.reminder.run_start', [
            'pet_filter' => $petId ? (int) $petId : null,
            'total_candidates' => $total,
        ]);

        $query->chunkById(200, function ($pets) use ($channelService, &$sent, &$skipped, &$errors, &$missingAge, &$duplicates, &$sentSamples, &$errorSamples) {
            /** @var Pet $pet */
            foreach ($pets as $pet) {
                $ageWeeks = $this->calculateAgeInWeeks($pet);
                if ($ageWeeks === null) {
                    $skipped++;
                    $missingAge++;
                    continue;
                }

                $events = $this->collectEventsForPet($pet, $ageWeeks, Carbon::now());
                foreach ($events as $event) {
                    if ($this->alreadyNotified($pet->id, $event['milestone'], $event['stage'])) {
                        $skipped++;
                        $duplicates++;
                        continue;
                    }

                    Log::info('vaccination.reminder.event', [
                        'pet_id' => $pet->id,
                        'user_id' => $pet->user_id,
                        'milestone' => $event['milestone'],
                        'stage' => $event['stage'],
                        'pet_age_weeks' => $event['payload']['pet_age_weeks'] ?? null,
                        'vaccination_date' => $event['payload']['vaccination_date'] ?? null,
                        'due_date' => $event['payload']['due_date'] ?? null,
                    ]);

                    $notification = Notification::create([
                        'user_id' => $pet->user_id,
                        'pet_id' => $pet->id,
                        'type' => 'vaccination_milestone',
                        'title' => $event['title'],
                        'body' => $event['body'],
                        'payload' => $event['payload'],
                        'status' => Notification::STATUS_PENDING,
                    ]);

                    try {
                        $channelService->send($notification);
                        $sent++;
                        Log::info('vaccination.reminder.push_result', [
                            'notification_id' => $notification->id,
                            'pet_id' => $pet->id,
                            'user_id' => $pet->user_id,
                            'milestone' => $event['milestone'],
                            'stage' => $event['stage'],
                            'status' => 'sent',
                        ]);
                        $this->markReminderSent($pet, $event['milestone'], $event['stage']);
                        if (count($sentSamples) < 5) {
                            $sentSamples[] = [
                                'notification_id' => $notification->id,
                                'pet_id' => $pet->id,
                                'user_id' => $pet->user_id,
                                'milestone' => $event['milestone'],
                                'stage' => $event['stage'],
                            ];
                        }
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::warning('vaccination.reminder.push_result', [
                            'notification_id' => $notification->id ?? null,
                            'pet_id' => $pet->id,
                            'user_id' => $pet->user_id,
                            'milestone' => $event['milestone'],
                            'stage' => $event['stage'],
                            'status' => 'error',
                            'error' => $e->getMessage(),
                        ]);
                        if (count($errorSamples) < 5) {
                            $errorSamples[] = [
                                'notification_id' => $notification->id ?? null,
                                'pet_id' => $pet->id,
                                'user_id' => $pet->user_id,
                                'milestone' => $event['milestone'],
                                'stage' => $event['stage'],
                                'error' => $e->getMessage(),
                            ];
                        }
                        $this->error(sprintf(
                            'Failed to send notification for pet %d (milestone %s): %s',
                            $pet->id,
                            $event['milestone'],
                            $e->getMessage()
                        ));
                    }
                }
            }
        });

        $this->info(sprintf('Vaccination reminders — sent: %d, skipped: %d, errors: %d', $sent, $skipped, $errors));
        Log::info('vaccination.reminder.summary', [
            'sent' => $sent,
            'skipped' => $skipped,
            'errors' => $errors,
            'missing_age' => $missingAge,
            'duplicates' => $duplicates,
            'sent_samples' => $sentSamples,
            'error_samples' => $errorSamples,
        ]);

        return self::SUCCESS;
    }

    private function collectEventsForPet(Pet $pet, float $ageWeeks, Carbon $today): array
    {
        $events = [];

        foreach (self::PUPPY_MILESTONES as $milestone) {
            $windowStart = (float) $milestone['start_weeks'];
            $windowEnd = (float) $milestone['end_weeks'];
            $preStart = max(0, $windowStart - ($this->preNoticeWeeks()));

            if ($ageWeeks >= $preStart && $ageWeeks < $windowStart) {
                $events[] = $this->buildMilestoneEvent($pet, $milestone, 'upcoming', $ageWeeks, $today);
            }

            if ($ageWeeks >= $windowStart && $ageWeeks <= $windowEnd + 0.25) {
                $events[] = $this->buildMilestoneEvent($pet, $milestone, 'due', $ageWeeks, $today);
            }
        }

        if ($adultEvent = $this->buildAdultBoosterEvent($pet, $ageWeeks, $today)) {
            $events[] = $adultEvent;
        }

        return $events;
    }

    private function buildMilestoneEvent(Pet $pet, array $milestone, string $stage, float $ageWeeks, Carbon $today): array
    {
        $last = $this->lastVaccinationDate($pet);

        return [
            'milestone' => $milestone['key'],
            'stage' => $stage,
            'title' => sprintf('Vaccination reminder for %s', $this->petDisplayName($pet)),
            'body' => $this->buildMilestoneBody($pet, $milestone, $stage),
            'payload' => [
                'milestone' => $milestone['key'],
                'stage' => $stage,
                'window_start_weeks' => $milestone['start_weeks'],
                'window_end_weeks' => $milestone['end_weeks'],
                'pet_age_weeks' => round($ageWeeks, 1),
                'vaccination_date' => $last?->toDateString(),
                'generated_at' => $today->toIso8601String(),
            ],
        ];
    }

    private function buildMilestoneBody(Pet $pet, array $milestone, string $stage): string
    {
        $petName = $this->petDisplayName($pet);
        $window = $this->formatWindow((float) $milestone['start_weeks'], (float) $milestone['end_weeks']);
        $last = $this->lastVaccinationDate($pet)?->toDateString() ?? 'not recorded';
        $timing = $stage === 'due' ? 'is due' : 'is coming up';

        return sprintf(
            '%s %s for %s. Recommended timing: %s. Last vaccine on file: %s. Please consult your vet.',
            $petName,
            $timing,
            $milestone['label'],
            $window,
            $last
        );
    }

    private function buildAdultBoosterEvent(Pet $pet, float $ageWeeks, Carbon $today): ?array
    {
        if ($ageWeeks < 52) {
            return null;
        }

        $last = $this->lastVaccinationDate($pet);
        if (!$last && $ageWeeks < 60) {
            // Give the 12-month booster window a chance before we start adult reminders without history.
            return null;
        }

        $dueDate = $last ? $last->copy()->addYear() : $today->copy();
        $daysUntilDue = $today->diffInDays($dueDate, false);

        if ($daysUntilDue > self::PRE_NOTICE_DAYS) {
            return null;
        }

        $stage = $daysUntilDue > 0 ? 'upcoming' : 'due';
        $petName = $this->petDisplayName($pet);
        $lastText = $last?->toDateString() ?? 'not recorded';

        return [
            'milestone' => 'adult_booster',
            'stage' => $stage,
            'title' => sprintf('Annual booster for %s', $petName),
            'body' => sprintf(
                '%s %s for the annual booster. Last completed: %s. Next due around %s. Please book with your vet.',
                $petName,
                $stage === 'due' ? 'is due' : 'is approaching the window',
                $lastText,
                $dueDate->toDateString()
            ),
            'payload' => [
                'milestone' => 'adult_booster',
                'stage' => $stage,
                'due_date' => $dueDate->toDateString(),
                'last_completed_on' => $lastText,
                'pet_age_weeks' => round($ageWeeks, 1),
                'generated_at' => $today->toIso8601String(),
            ],
        ];
    }

    private function preNoticeWeeks(): float
    {
        return self::PRE_NOTICE_DAYS / 7;
    }

    private function calculateAgeInWeeks(Pet $pet): ?float
    {
        $years = $this->toInt($pet->pet_age);
        $months = $this->toInt($pet->pet_age_months);
        $totalMonths = $years * 12 + $months;

        if ($totalMonths <= 0) {
            return null;
        }

        return $totalMonths * self::WEEKS_PER_MONTH;
    }

    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function petDisplayName(Pet $pet): string
    {
        return $pet->name ?: 'your pet';
    }

    private function lastVaccinationDate(Pet $pet): ?Carbon
    {
        if ($pet->vaccination_date instanceof Carbon) {
            return $pet->vaccination_date;
        }

        if (!empty($pet->vaccination_date)) {
            return Carbon::parse($pet->vaccination_date);
        }

        if (!empty($pet->last_vaccenated_date)) {
            return Carbon::parse($pet->last_vaccenated_date);
        }

        return null;
    }

    private function formatWindow(float $startWeeks, float $endWeeks): string
    {
        if (abs($startWeeks - $endWeeks) < 0.01) {
            return sprintf('week %.1f', $startWeeks);
        }

        return sprintf('weeks %.1f-%.1f', $startWeeks, $endWeeks);
    }

    private function alreadyNotified(int $petId, string $milestone, string $stage): bool
    {
        return Notification::query()
            ->where('pet_id', $petId)
            ->where('type', 'vaccination_milestone')
            ->where('payload->milestone', $milestone)
            ->where('payload->stage', $stage)
            ->whereIn('status', [
                Notification::STATUS_PENDING,
                Notification::STATUS_SENT,
                Notification::STATUS_DELIVERED,
                Notification::STATUS_FAILED,
            ])
            ->exists();
    }

    private function markReminderSent(Pet $pet, string $milestone, string $stage): void
    {
        $key = $stage === 'due' ? 'due_sent' : 'upcoming_sent';
        $status = $pet->vaccine_reminder_status ?? [];

        if (!is_array($status)) {
            $status = [];
        }

        $status[$milestone] = array_merge(
            [
                'upcoming_sent' => false,
                'due_sent' => false,
            ],
            Arr::only($status[$milestone] ?? [], ['upcoming_sent', 'due_sent']),
            [$key => true]
        );

        $pet->forceFill(['vaccine_reminder_status' => $status])->saveQuietly();
    }
}
