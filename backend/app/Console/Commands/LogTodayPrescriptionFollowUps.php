<?php

namespace App\Console\Commands;

use App\Models\Prescription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LogTodayPrescriptionFollowUps extends Command
{
    protected $signature = 'notifications:prescription-followups-today';

    protected $description = 'Log prescriptions whose follow_up_date is today.';

    public function handle(): int
    {
        if (! Schema::hasColumn('prescriptions', 'follow_up_notification_sent_at')) {
            Log::error('prescriptions.follow_up_today.missing_column', [
                'column' => 'follow_up_notification_sent_at',
                'table' => 'prescriptions',
            ]);

            $this->error('Missing column prescriptions.follow_up_notification_sent_at. Add it before running this command.');

            return self::FAILURE;
        }

        $today = now()->toDateString();

        $query = Prescription::query()
            ->whereDate('follow_up_date', $today)
            ->whereNull('follow_up_notification_sent_at')
            ->select([
                'id',
                'doctor_id',
                'user_id',
                'pet_id',
                'medical_record_id',
                'follow_up_date',
                'follow_up_notification_sent_at',
            ]);

        $count = (clone $query)->count();

        Log::info('prescriptions.follow_up_today.run_start', [
            'date' => $today,
            'count' => $count,
        ]);

        if ($count === 0) {
            $this->info("No prescriptions found with follow_up_date {$today}.");
            Log::info('prescriptions.follow_up_today.run_finish', [
                'date' => $today,
                'logged' => 0,
            ]);

            return self::SUCCESS;
        }

        $processed = 0;

        $query->orderBy('id')->chunkById(200, function ($prescriptions) use (&$processed) {
            foreach ($prescriptions as $prescription) {
                $updated = Prescription::query()
                    ->whereKey($prescription->id)
                    ->whereNull('follow_up_notification_sent_at')
                    ->update([
                        'follow_up_notification_sent_at' => now(),
                    ]);

                if ($updated === 0) {
                    continue;
                }

                Log::info('prescriptions.follow_up_today.match', [
                    'prescription_id' => $prescription->id,
                    'medical_record_id' => $prescription->medical_record_id,
                    'doctor_id' => $prescription->doctor_id,
                    'user_id' => $prescription->user_id,
                    'pet_id' => $prescription->pet_id,
                    'follow_up_date' => optional($prescription->follow_up_date)->toDateString(),
                    'follow_up_notification_sent_at' => now()->toDateTimeString(),
                ]);

                $processed++;
            }
        });

        Log::info('prescriptions.follow_up_today.run_finish', [
            'date' => $today,
            'logged' => $processed,
        ]);

        $this->info("Logged {$processed} prescriptions with follow_up_date {$today}.");

        return self::SUCCESS;
    }
}
