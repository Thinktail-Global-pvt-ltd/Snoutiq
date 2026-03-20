<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\Pet;
use App\Services\Push\FcmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SendPetNeuteringReminders extends Command
{
    protected $signature = 'notifications:pet-neutering-reminders {--pet_id=}';

    protected $description = 'Send FCM reminders for pets marked as not neutered.';

    public function __construct(private readonly FcmService $fcm)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! Schema::hasTable('pets')) {
            Log::error('pets.neutering_reminder.config_error', [
                'message' => 'Missing table: pets',
            ]);
            $this->error('Missing table: pets');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('pets', 'user_id')) {
            Log::error('pets.neutering_reminder.config_error', [
                'message' => 'Missing column: pets.user_id',
            ]);
            $this->error('Missing column: pets.user_id');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('pets', 'neutering_reminder_sent_at')) {
            Log::error('pets.neutering_reminder.config_error', [
                'message' => 'Missing column: pets.neutering_reminder_sent_at',
            ]);
            $this->error('Missing column: pets.neutering_reminder_sent_at. Run migrations first.');

            return self::FAILURE;
        }

        $neuteredColumn = $this->resolveNeuteredColumn();
        if ($neuteredColumn === null) {
            Log::error('pets.neutering_reminder.config_error', [
                'message' => 'Missing column: pets.is_neutered (or pets.is_nuetered)',
            ]);
            $this->error('Missing column: pets.is_neutered (or pets.is_nuetered)');

            return self::FAILURE;
        }

        $today = now()->toDateString();
        $query = Pet::query()
            ->whereNotNull('user_id')
            ->where(function ($builder) use ($neuteredColumn) {
                $builder->where($neuteredColumn, 'N')
                    ->orWhere($neuteredColumn, 'n')
                    ->orWhere($neuteredColumn, '0')
                    ->orWhere($neuteredColumn, 0);
            })
            ->where(function ($builder) use ($today) {
                $builder->whereNull('neutering_reminder_sent_at')
                    ->orWhereDate('neutering_reminder_sent_at', '<', $today);
            })
            ->select([
                'id',
                'user_id',
                'name',
                'neutering_reminder_sent_at',
            ]);

        $forcedPetId = $this->option('pet_id');
        if ($forcedPetId) {
            $query->whereKey((int) $forcedPetId);
        }

        $totalCandidates = (clone $query)->count();
        Log::info('pets.neutering_reminder.run_start', [
            'date' => $today,
            'forced_pet_id' => $forcedPetId ? (int) $forcedPetId : null,
            'pending_count' => $totalCandidates,
        ]);

        if ($totalCandidates === 0) {
            Log::info('pets.neutering_reminder.no_candidates', [
                'date' => $today,
                'forced_pet_id' => $forcedPetId ? (int) $forcedPetId : null,
            ]);
            $this->info('No pets pending neutering reminders.');

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

                $tokens = DeviceToken::query()
                    ->where('user_id', $userId)
                    ->pluck('token')
                    ->filter()
                    ->values()
                    ->all();

                if (empty($tokens)) {
                    Log::warning('pets.neutering_reminder.skipped_no_tokens', [
                        'pet_id' => $pet->id,
                        'user_id' => $userId,
                    ]);
                    $skipped++;
                    continue;
                }

                $petName = trim((string) ($pet->name ?? ''));
                if ($petName === '') {
                    $petName = 'your pet';
                }

                $sendResult = $this->sendToTokens(
                    $tokens,
                    'Neutering Reminder',
                    sprintf('%s is marked as not neutered. Please consult your vet for neutering guidance.', $petName),
                    [
                        'type' => 'pet_neutering_reminder',
                        'pet_id' => (string) $pet->id,
                        'user_id' => (string) $userId,
                        'pet_name' => $petName,
                        'is_neutered' => 'N',
                        'reminder_date' => $today,
                    ]
                );

                if ($sendResult['success_count'] <= 0) {
                    Log::error('pets.neutering_reminder.push_failed', [
                        'pet_id' => $pet->id,
                        'user_id' => $userId,
                        'failure_count' => $sendResult['failure_count'],
                        'errors' => $sendResult['errors'],
                    ]);
                    $failed++;
                    continue;
                }

                Pet::query()
                    ->whereKey($pet->id)
                    ->update([
                        'neutering_reminder_sent_at' => now(),
                    ]);

                $sent++;
            }
        });

        Log::info('pets.neutering_reminder.run_finish', [
            'date' => $today,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
        ]);

        $this->info("Pet neutering reminders complete: sent={$sent}, failed={$failed}, skipped={$skipped}.");

        return self::SUCCESS;
    }

    private function resolveNeuteredColumn(): ?string
    {
        if (Schema::hasColumn('pets', 'is_neutered')) {
            return 'is_neutered';
        }

        if (Schema::hasColumn('pets', 'is_nuetered')) {
            return 'is_nuetered';
        }

        return null;
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
