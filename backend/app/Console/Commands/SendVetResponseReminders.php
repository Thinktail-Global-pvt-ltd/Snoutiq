<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendVetResponseReminders extends Command
{
    protected $signature = 'notifications:vet-response-reminders {--txn_id=}';
    protected $description = 'Send WhatsApp SLA reminders to vets when response window is 5 minutes away.';

    public function __construct(private readonly WhatsAppService $whatsApp)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = now();

        if (! $this->whatsApp->isConfigured()) {
            Log::warning('vet_response.reminder.whatsapp_not_configured');
            return self::SUCCESS;
        }

        $forceTxnId = $this->option('txn_id');

        if ($forceTxnId) {
            $rows = Transaction::query()
                ->where('id', (int) $forceTxnId)
                ->limit(1)
                ->get();
        } else {
            $responseWindowMinutes = max((int) config('app.video_consult_response_minutes', 15), 5);
            $triggerAgeMinutes = max($responseWindowMinutes - 5, 0);

            $query = Transaction::query()
                ->whereIn('type', ['video_consult', 'excell_export_campaign'])
                ->whereIn('status', ['pending', 'initiated', 'created', 'authorized', 'captured', 'paid', 'success', 'successful'])
                ->whereRaw("COALESCE(JSON_EXTRACT(metadata, '$.vet_response_reminder_sent_at'), '') = ''")
                ->where('created_at', '<=', $now->copy()->subMinutes($triggerAgeMinutes))
                ->where('created_at', '>', $now->copy()->subMinutes($triggerAgeMinutes + 1));

            $rows = $query->limit(200)->get();
        }

        Log::info('vet_response.reminder.run_start', [
            'candidates' => $rows->count(),
            'at' => $now->toDateTimeString(),
            'forced_txn_id' => $forceTxnId ?? null,
        ]);

        $sent = 0;
        foreach ($rows as $txn) {
            $doctor = $txn->doctor;
            $user = $txn->user;
            $pet = $txn->pet;

            $petName = $txn->pet?->name ?? 'your pet';
            $petBreed = $pet?->breed ?? $pet?->pet_type ?? $pet?->type ?? 'Pet';
            $vetName = $doctor?->doctor_name ?? 'Doctor';
            $parentName = $user?->name ?? 'Pet Parent';
            $remaining = 5; // reminder is sent 5 minutes before SLA

            // Target doctor instead of user
            $phone = null;
            if ($doctor) {
                $phone = $doctor->doctor_mobile
                    ?? ($doctor->doctor_phone ?? null)
                    ?? ($doctor->phone ?? null);
            }

            if (! $phone) {
                Log::warning('vet_response.reminder.skip_no_doctor_phone', ['txn_id' => $txn->id, 'doctor_id' => $txn->doctor_id]);
                $this->logReminder($txn, 'skipped_no_phone', null, null, null, 'doctor phone missing');
                $this->markSent($txn, $now, 'skipped_no_phone');
                continue;
            }

            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $vetName],
                        ['type' => 'text', 'text' => $parentName],
                        ['type' => 'text', 'text' => $petName],
                        ['type' => 'text', 'text' => $petBreed],
                        ['type' => 'text', 'text' => (string) $remaining],
                    ],
                ],
            ];

            $templateCandidates = array_values(array_filter([
                config('services.whatsapp.templates.vet_sla_reminder') ?? null,
                config('services.whatsapp.templates.vet_response_reminder') ?? null,
                'vet_sla_reminder',
                'VET_SLA_REMINDER',
                'VET_RESPONSE_REMINDER',
                'vet_response_reminder',
            ]));

            $languageCandidates = ['en', 'en_US', 'en_GB'];
            $lastError = null;
            $sentThis = false;

            foreach ($templateCandidates as $tpl) {
                foreach ($languageCandidates as $lang) {
                    try {
                        $this->whatsApp->sendTemplate($phone, $tpl, $components, $lang);
                        $sentThis = true;
                        $this->logReminder($txn, 'sent', $tpl, $lang, $phone, null);
                        break 2;
                    } catch (\RuntimeException $ex) {
                        $lastError = $ex->getMessage();
                    }
                }
            }

            if ($sentThis) {
                $sent++;
                Log::info('vet_response.reminder.sent', [
                    'txn_id' => $txn->id,
                    'user_id' => $txn->user_id,
                    'pet_id' => $txn->pet_id,
                    'phone' => $phone,
                ]);
                $this->markSent($txn, $now);
            } else {
                Log::error('vet_response.reminder.failed', [
                    'txn_id' => $txn->id,
                    'error' => $lastError,
                ]);
                $this->logReminder($txn, 'failed', $templateCandidates[0] ?? null, $languageCandidates[0] ?? null, $phone, $lastError);
                $this->markSent($txn, $now, 'failed');
            }
        }

        Log::info('vet_response.reminder.run_finish', [
            'sent' => $sent,
            'at' => now()->toDateTimeString(),
        ]);

        return self::SUCCESS;
    }

    private function markSent(Transaction $txn, $ts, string $status = 'sent'): void
    {
        $meta = $txn->metadata ?? [];
        $meta['vet_response_reminder_sent_at'] = $ts->toIso8601String();
        $meta['vet_response_reminder_status'] = $status;
        $txn->metadata = $meta;
        $txn->save();
    }

    private function logReminder(Transaction $txn, string $status, ?string $template, ?string $language, ?string $phone, ?string $error): void
    {
        try {
            DB::table('vet_response_reminder_logs')->insert([
                'transaction_id' => $txn->id,
                'user_id' => $txn->user_id,
                'pet_id' => $txn->pet_id,
                'doctor_id' => $txn->doctor_id,
                'phone' => $phone,
                'template' => $template,
                'language' => $language,
                'status' => $status,
                'error' => $error,
                'meta' => json_encode([
                    'amount_paise' => $txn->amount_paise,
                    'created_at' => optional($txn->created_at)->toIso8601String(),
                    'doctor_id' => $txn->doctor_id,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('vet_response.reminder.log_failed', ['txn_id' => $txn->id, 'error' => $e->getMessage()]);
        }
    }
}
