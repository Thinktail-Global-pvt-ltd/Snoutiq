<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\Pet;
use App\Services\Push\FcmService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SendPetVaccinationUpcomingReminders extends Command
{
    protected $signature = 'notifications:pet-vaccination-upcoming-reminders {--pet_id=}';

    protected $description = 'Send FCM reminders when pet vaccination next_due is within 7 days.';

    public function __construct(private readonly FcmService $fcm)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! Schema::hasTable('pets')) {
            $this->error('Missing table: pets');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('pets', 'dog_disease_payload')) {
            $this->error('Missing column: pets.dog_disease_payload');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('pets', 'vaccination_upcoming_reminder_sent_at')
            || ! Schema::hasColumn('pets', 'vaccination_upcoming_reminder_due_date')) {
            $this->error('Missing reminder tracking columns on pets. Run migrations first.');

            return self::FAILURE;
        }

        $today = now()->startOfDay();
        $forcedPetId = $this->option('pet_id');

        $query = Pet::query()
            ->whereNotNull('user_id')
            ->whereNotNull('dog_disease_payload')
            ->select([
                'id',
                'user_id',
                'name',
                'dog_disease_payload',
                'vaccination_upcoming_reminder_sent_at',
                'vaccination_upcoming_reminder_due_date',
            ]);

        if ($forcedPetId) {
            $query->whereKey((int) $forcedPetId);
        }

        $totalCandidates = (clone $query)->count();
        Log::info('pets.vaccination_upcoming_reminder.run_start', [
            'date' => $today->toDateString(),
            'forced_pet_id' => $forcedPetId ? (int) $forcedPetId : null,
            'candidate_count' => $totalCandidates,
        ]);

        if ($totalCandidates === 0) {
            $this->info('No pets found for vaccination upcoming reminder scan.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        $query->orderBy('id')->chunkById(200, function ($pets) use (&$sent, &$failed, &$skipped, $today) {
            foreach ($pets as $pet) {
                $userId = (int) ($pet->user_id ?? 0);
                if ($userId <= 0) {
                    $skipped++;
                    continue;
                }

                $upcoming = $this->collectUpcomingVaccinations($pet->dog_disease_payload, $today);
                if (empty($upcoming)) {
                    $skipped++;
                    continue;
                }

                $targetDueDate = $upcoming[0]['due_date'];
                $storedDueDate = $this->toDateString($pet->vaccination_upcoming_reminder_due_date ?? null);
                $alreadySentForThisDueDate = $storedDueDate === $targetDueDate
                    && ! empty($pet->vaccination_upcoming_reminder_sent_at);

                if ($alreadySentForThisDueDate) {
                    $skipped++;
                    continue;
                }

                $tokens = DeviceToken::query()
                    ->where('user_id', $userId)
                    ->pluck('token')
                    ->filter()
                    ->values()
                    ->all();

                if (empty($tokens)) {
                    Log::warning('pets.vaccination_upcoming_reminder.skipped_no_tokens', [
                        'pet_id' => $pet->id,
                        'user_id' => $userId,
                        'due_date' => $targetDueDate,
                    ]);
                    $skipped++;
                    continue;
                }

                $petName = trim((string) ($pet->name ?? ''));
                if ($petName === '') {
                    $petName = 'your pet';
                }

                $vaccineNames = array_values(array_unique(array_map(
                    fn ($row) => (string) ($row['vaccine'] ?? ''),
                    $upcoming
                )));
                $vaccineList = implode(', ', array_filter($vaccineNames));

                $sendResult = $this->sendToTokens(
                    $tokens,
                    'Vaccination Reminder',
                    sprintf(
                        '%s has an upcoming vaccination due on %s%s. Please plan a vet visit.',
                        $petName,
                        $targetDueDate,
                        $vaccineList !== '' ? " ({$vaccineList})" : ''
                    ),
                    [
                        'type' => 'pet_vaccination_upcoming_reminder',
                        'pet_id' => (string) $pet->id,
                        'user_id' => (string) $userId,
                        'pet_name' => $petName,
                        'next_due' => $targetDueDate,
                        'vaccines' => $vaccineList,
                        'days_left' => (string) max(0, $today->diffInDays(Carbon::parse($targetDueDate), false)),
                    ]
                );

                if ($sendResult['success_count'] <= 0) {
                    Log::error('pets.vaccination_upcoming_reminder.push_failed', [
                        'pet_id' => $pet->id,
                        'user_id' => $userId,
                        'target_due_date' => $targetDueDate,
                        'failure_count' => $sendResult['failure_count'],
                        'errors' => $sendResult['errors'],
                    ]);
                    $failed++;
                    continue;
                }

                Pet::query()
                    ->whereKey($pet->id)
                    ->update([
                        'vaccination_upcoming_reminder_sent_at' => now(),
                        'vaccination_upcoming_reminder_due_date' => $targetDueDate,
                    ]);

                $sent++;
            }
        });

        Log::info('pets.vaccination_upcoming_reminder.run_finish', [
            'date' => $today->toDateString(),
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
        ]);

        $this->info("Pet vaccination upcoming reminders complete: sent={$sent}, failed={$failed}, skipped={$skipped}.");

        return self::SUCCESS;
    }

    /**
     * @return array<int,array{vaccine:string,due_date:string}>
     */
    private function collectUpcomingVaccinations(mixed $payload, Carbon $today): array
    {
        $payloadArray = $this->normalizePayload($payload);
        if (! is_array($payloadArray)) {
            return [];
        }

        $vaccination = $payloadArray['vaccination'] ?? null;
        if (! is_array($vaccination)) {
            return [];
        }

        $todayStart = $today->copy()->startOfDay();
        $weekEnd = $today->copy()->addDays(7)->endOfDay();
        $upcoming = [];

        foreach ($vaccination as $vaccineKey => $details) {
            if (! is_array($details)) {
                continue;
            }

            $nextDue = $this->parseDate($details['next_due'] ?? null);
            if (! $nextDue) {
                continue;
            }

            $dueDate = $nextDue->copy()->startOfDay();
            if ($dueDate->lt($todayStart) || $dueDate->gt($weekEnd)) {
                continue;
            }

            $upcoming[] = [
                'vaccine' => $this->normalizeVaccineName($vaccineKey),
                'due_date' => $dueDate->toDateString(),
            ];
        }

        usort($upcoming, function (array $a, array $b): int {
            return [$a['due_date'], $a['vaccine']] <=> [$b['due_date'], $b['vaccine']];
        });

        return $upcoming;
    }

    private function normalizePayload(mixed $payload): ?array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_string($payload) && trim($payload) !== '') {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $candidate = trim((string) $value);
        if ($candidate === '') {
            return null;
        }

        try {
            return Carbon::parse($candidate);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeVaccineName(mixed $value): string
    {
        $label = trim((string) $value);
        if ($label === '') {
            return 'Vaccine';
        }

        $label = str_replace(['_', '-'], ' ', $label);

        return ucwords($label);
    }

    private function toDateString(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        $parsed = $this->parseDate($value);
        if (! $parsed) {
            return null;
        }

        return $parsed->toDateString();
    }

    /**
     * @param array<int,string> $tokens
     * @param array<string,string> $data
     * @return array{success_count:int,failure_count:int,errors:array<int,array<string,mixed>>}
     */
    private function sendToTokens(array $tokens, string $title, string $body, array $data): array
    {
        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($tokens as $token) {
            try {
                $this->fcm->sendToToken((string) $token, $title, $body, $data);
                $successCount++;
            } catch (Throwable $e) {
                $failureCount++;
                $errors[] = [
                    'token' => $this->maskToken((string) $token),
                    'details' => $e->getMessage(),
                ];
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => array_slice($errors, 0, 5),
        ];
    }

    private function maskToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        if (strlen($token) <= 12) {
            return str_repeat('*', strlen($token));
        }

        return substr($token, 0, 6).'...'.substr($token, -6);
    }
}
