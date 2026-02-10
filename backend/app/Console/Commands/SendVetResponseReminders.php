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
    protected $description = 'Send WhatsApp reminders to pet parents when vet has not opened the video consult case in time.';

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
            $query = Transaction::query()
                ->whereIn('type', ['video_consult', 'excell_export_campaign'])
                ->whereIn('status', ['pending', 'initiated', 'created', 'authorized', 'captured', 'paid', 'success', 'successful'])
                ->whereRaw("COALESCE(JSON_EXTRACT(metadata, '$.vet_response_reminder_sent_at'), '') = ''")
                ->where('created_at', '<=', $now->copy()->subMinutes(15))
                ->where('created_at', '>=', $now->copy()->subMinutes(20));

            $rows = $query->limit(200)->get();
        }

        Log::info('vet_response.reminder.run_start', [
            'candidates' => $rows->count(),
            'at' => $now->toDateTimeString(),
            'forced_txn_id' => $forceTxnId ?? null,
        ]);

        $sent = 0;
        foreach ($rows as $txn) {
            $petName = $txn->pet?->name ?? 'your pet';
            $remaining = 3; // minutes left window
            $phone = $txn->user?->phone;

            if (! $phone) {
                Log::warning('vet_response.reminder.skip_no_phone', ['txn_id' => $txn->id, 'user_id' => $txn->user_id]);
                $this->logReminder($txn, 'skipped_no_phone', null, null, null, 'user phone missing');
                $this->markSent($txn, $now, 'skipped_no_phone');
                continue;
            }

            $components = [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $petName],
                        ['type' => 'text', 'text' => (string) $remaining],
                    ],
                ],
            ];

            $templateCandidates = array_values(array_filter([
                config('services.whatsapp.templates.vet_response_reminder') ?? null,
                'VET_RESPONSE_REMINDER',
                'vet_response_reminder',
            ]));

            $languageCandidates = ['en_US', 'en_GB', 'en'];
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
                'phone' => $phone,
                'template' => $template,
                'language' => $language,
                'status' => $status,
                'error' => $error,
                'meta' => json_encode([
                    'amount_paise' => $txn->amount_paise,
                    'created_at' => optional($txn->created_at)->toIso8601String(),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('vet_response.reminder.log_failed', ['txn_id' => $txn->id, 'error' => $e->getMessage()]);
        }
    }
}
