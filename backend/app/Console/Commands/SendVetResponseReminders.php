<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

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
                ->where('type', 'video_consult')
                ->whereIn('status', ['pending', 'initiated', 'created', 'authorized', 'captured', 'paid', 'success', 'successful'])
                ->whereRaw("COALESCE(JSON_EXTRACT(metadata, '$.vet_response_reminder_sent_at'), '') = ''")
                // No time window filter: allow manual txn_id targeting or full scan
                ;

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
}
