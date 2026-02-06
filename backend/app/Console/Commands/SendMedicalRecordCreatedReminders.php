<?php

namespace App\Console\Commands;

use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendMedicalRecordCreatedReminders extends Command
{
    protected $signature = 'notifications:pp-records-created {--record_id=}';
    protected $description = 'Send WhatsApp to pet parent when new medical records are created (0–2h window).';

    public function __construct(private readonly WhatsAppService $whatsApp)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->whatsApp->isConfigured()) {
            Log::warning('pp_records_created.whatsapp_not_configured');
            return self::SUCCESS;
        }

        $now = now();
        $forcedId = $this->option('record_id');
        $windowStart = $now->copy()->subHours(2);
        $windowEnd = $now;

        $records = MedicalRecord::query()
            ->when($forcedId, fn($q) => $q->where('id', (int) $forcedId))
            ->when(! $forcedId, fn($q) => $q->whereBetween('created_at', [$windowStart, $windowEnd]))
            ->whereNotNull('doctor_id')
            ->whereNotNull('vet_registeration_id')
            ->whereNotNull('pet_id')
            ->whereNotNull('user_id')
            ->whereDoesntHave('reminderLogs')
            ->limit(200)
            ->get();

        Log::info('pp_records_created.run_start', [
            'candidates' => $records->count(),
            'at' => $now->toDateTimeString(),
            'forced_record_id' => $forcedId,
        ]);

        $sent = 0;
        foreach ($records as $record) {
            $user = User::find($record->user_id);
            $phone = $user?->phone;
            $petName = $record->pet?->name ?? 'your pet';
            $doctorName = $record->doctor?->doctor_name ?? null;
            $clinicName = VetRegisterationTemp::where('id', $record->vet_registeration_id)->value('name');

            if (! $phone || ! $doctorName || ! $petName) {
                $this->log($record->id, 'skipped', $phone, null, null, 'missing_fields');
                continue;
            }

            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $doctorName], // {{1}}
                        ['type' => 'text', 'text' => $petName],    // {{2}}
                    ],
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => [
                        ['type' => 'text', 'text' => 'https://play.google.com/store/apps/details?id=com.petai.snoutiq'],
                    ],
                ],
            ];

            $templates = array_values(array_filter([
                config('services.whatsapp.templates.snq_pp_records_created') ?? null,
                'SNQ_PP_RECORDS_CREATED',
                'snq_pp_records_created',
            ]));

            $languages = ['en_US', 'en_GB', 'en'];
            $ok = false; $last = null;
            foreach ($templates as $tpl) {
                foreach ($languages as $lang) {
                    try {
                        $this->whatsApp->sendTemplate($phone, $tpl, $components, $lang);
                        $this->log($record->id, 'sent', $phone, $tpl, $lang, null);
                        $ok = true;
                        break 2;
                    } catch (\RuntimeException $e) {
                        $last = $e->getMessage();
                    }
                }
            }

            if ($ok) {
                $sent++;
            } else {
                $this->log($record->id, 'failed', $phone, $templates[0] ?? null, $languages[0] ?? null, $last);
            }
        }

        Log::info('pp_records_created.run_finish', [
            'sent' => $sent,
            'at' => now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }

    private function log(int $recordId, string $status, ?string $phone, ?string $template, ?string $language, ?string $error): void
    {
        try {
            DB::table('vet_response_reminder_logs')->insert([
                'transaction_id' => null,
                'user_id' => null,
                'pet_id' => null,
                'phone' => $phone,
                'template' => $template,
                'language' => $language,
                'status' => $status,
                'error' => $error,
                'meta' => json_encode([
                    'record_id' => $recordId,
                    'type' => 'pp_records_created',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('pp_records_created.log_failed', ['record_id' => $recordId, 'error' => $e->getMessage()]);
        }
    }
}
