<?php

namespace App\Console\Commands;

use App\Models\Prescription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LogTodayPrescriptionFollowUps extends Command
{
    protected $signature = 'notifications:prescription-followups-today';

    protected $description = 'Log prescriptions whose follow_up_date is today.';

    public function handle(): int
    {
        $today = now()->toDateString();

        $query = Prescription::query()
            ->whereDate('follow_up_date', $today)
            ->select([
                'id',
                'doctor_id',
                'user_id',
                'pet_id',
                'medical_record_id',
                'follow_up_date',
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

        $logged = 0;

        $query->orderBy('id')->chunkById(200, function ($prescriptions) use (&$logged) {
            foreach ($prescriptions as $prescription) {
                Log::info('prescriptions.follow_up_today.match', [
                    'prescription_id' => $prescription->id,
                    'medical_record_id' => $prescription->medical_record_id,
                    'doctor_id' => $prescription->doctor_id,
                    'user_id' => $prescription->user_id,
                    'pet_id' => $prescription->pet_id,
                    'follow_up_date' => optional($prescription->follow_up_date)->toDateString(),
                ]);

                $logged++;
            }
        });

        Log::info('prescriptions.follow_up_today.run_finish', [
            'date' => $today,
            'logged' => $logged,
        ]);

        $this->info("Logged {$logged} prescriptions with follow_up_date {$today}.");

        return self::SUCCESS;
    }
}
