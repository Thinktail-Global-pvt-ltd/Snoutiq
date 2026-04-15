<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\PrescriptionMedicationReminderController;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SendPrescriptionMedicationReminders extends Command
{
    protected $signature = 'notifications:prescription-medication-reminders {--prescription_id=} {--dry}';
    protected $description = 'Send recurring WhatsApp medication reminders from prescriptions.medications_json.';

    public function handle(): int
    {
        if (!Schema::hasTable('prescriptions')) {
            $this->error('Missing prescriptions table.');
            return self::FAILURE;
        }

        $now = now();
        $forcedId = $this->option('prescription_id');
        $dryRun = (bool) $this->option('dry');
        $query = Prescription::query()
            ->select($this->selectColumns())
            ->whereNotNull('medications_json');

        if ($forcedId) {
            $query->whereKey((int) $forcedId);
        } else {
            $query->where('created_at', '<=', $now)
                ->where('created_at', '>=', $now->copy()->subDays(180))
                ->where(function ($q) use ($now) {
                    $q->whereNull('follow_up_date')
                        ->orWhereDate('follow_up_date', '>=', $now->toDateString());
                });
        }

        $candidates = $query->orderBy('id')->limit($forcedId ? 1 : 500)->get();

        Log::info('prescription_medication_reminders.run_start', [
            'candidates' => $candidates->count(),
            'forced_prescription_id' => $forcedId,
            'dry_run' => $dryRun,
            'at' => $now->toDateTimeString(),
        ]);

        $controller = app(PrescriptionMedicationReminderController::class);
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidates as $prescription) {
            if (!$forcedId && !$this->isDue($prescription, $now)) {
                $skipped++;
                continue;
            }

            $request = Request::create(
                '/api/prescriptions/'.$prescription->id.'/medication-reminder',
                'POST',
                ['dry_run' => $dryRun ? 1 : 0]
            );

            try {
                $response = $controller->send($request, (int) $prescription->id);
                $payload = $response->getData(true);
                $data = is_array($payload) ? ($payload['data'] ?? []) : [];
                $statusCode = $response->getStatusCode();

                if ($statusCode >= 200 && $statusCode < 300 && !empty($data['sent'])) {
                    $sent++;
                    continue;
                }

                if ($statusCode >= 200 && $statusCode < 300) {
                    $skipped++;
                    continue;
                }

                $failed++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('prescription_medication_reminders.failed', [
                    'prescription_id' => $prescription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('prescription_medication_reminders.run_finish', [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'at' => now()->toDateTimeString(),
        ]);

        $this->info("Medication reminders complete: sent={$sent}, skipped={$skipped}, failed={$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isDue(Prescription $prescription, Carbon $now): bool
    {
        if (!$prescription->created_at) {
            return false;
        }

        $createdAt = Carbon::parse($prescription->created_at)->timezone(config('app.timezone'));
        $now = $now->copy()->timezone(config('app.timezone'));

        if ($now->lt($createdAt)) {
            return false;
        }

        if ($createdAt->format('H:i') !== $now->format('H:i')) {
            return false;
        }

        $intervalDays = $this->resolveIntervalDays($prescription);
        $daysSinceStart = $createdAt->copy()->startOfDay()->diffInDays($now->copy()->startOfDay());

        if ($daysSinceStart < 0 || $daysSinceStart % $intervalDays !== 0) {
            return false;
        }

        if ($prescription->follow_up_date) {
            return $now->copy()->startOfDay()->lte(Carbon::parse($prescription->follow_up_date)->startOfDay());
        }

        $occurrenceNumber = intdiv($daysSinceStart, $intervalDays) + 1;

        return $occurrenceNumber <= 3;
    }

    private function resolveIntervalDays(Prescription $prescription): int
    {
        foreach ($this->intervalColumns() as $column) {
            $days = $this->parseIntervalDays($prescription->getAttribute($column));
            if ($days !== null) {
                return $days;
            }
        }

        $medications = $prescription->medications_json;
        if (is_string($medications)) {
            $decoded = json_decode($medications, true);
            $medications = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (is_array($medications)) {
            foreach ($medications as $medication) {
                if (!is_array($medication)) {
                    continue;
                }

                foreach (['reminder_interval_days', 'medication_reminder_interval_days', 'interval_days', 'interval', 'reminder_interval'] as $key) {
                    $days = $this->parseIntervalDays($medication[$key] ?? null);
                    if ($days !== null) {
                        return $days;
                    }
                }
            }
        }

        return 1;
    }

    private function parseIntervalDays($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return max(1, min(30, (int) $value));
        }

        $text = strtolower(trim((string) $value));
        if ($text === '') {
            return null;
        }

        if (str_contains($text, 'alternate') || str_contains($text, 'every other')) {
            return 2;
        }

        if (str_contains($text, 'weekly')) {
            return 7;
        }

        if (preg_match('/(\d+)/', $text, $match)) {
            return max(1, min(30, (int) $match[1]));
        }

        if (str_contains($text, 'daily') || str_contains($text, 'every day')) {
            return 1;
        }

        return null;
    }

    private function selectColumns(): array
    {
        return array_values(array_unique(array_merge([
            'id',
            'medical_record_id',
            'doctor_id',
            'user_id',
            'pet_id',
            'medications_json',
            'follow_up_date',
            'created_at',
            'updated_at',
        ], $this->intervalColumns())));
    }

    private function intervalColumns(): array
    {
        $columns = [
            'medication_reminder_interval_days',
            'medicine_reminder_interval_days',
            'reminder_interval_days',
            'interval_days',
        ];

        return array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('prescriptions', $column)));
    }
}
