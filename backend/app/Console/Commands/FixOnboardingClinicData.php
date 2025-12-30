<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Doctor;
use App\Models\VetRegisterationTemp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOnboardingClinicData extends Command
{
    /**
     * Move video/clinic/emergency onboarding data from one clinic/doctor to another.
     *
     * Usage:
     *  php artisan onboarding:move-hours source-slug dest-slug --src-doctor=<id|email> --dst-doctor=<id|email> [--dry-run]
     */
    protected $signature = 'onboarding:move-hours
        {source-slug : Slug for the clinic that currently has the correct hours}
        {dest-slug : Slug for the clinic that should receive the hours}
        {--src-doctor= : Optional doctor id/email in source clinic (defaults to the only doctor)}
        {--dst-doctor= : Optional doctor id/email in destination clinic (defaults to the only doctor)}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Move video consultation hours, clinic hours, and emergency coverage between clinics';

    public function handle(): int
    {
        $srcSlug = (string) $this->argument('source-slug');
        $dstSlug = (string) $this->argument('dest-slug');

        /** @var VetRegisterationTemp $srcClinic */
        $srcClinic = VetRegisterationTemp::where('slug', $srcSlug)->firstOrFail();
        /** @var VetRegisterationTemp $dstClinic */
        $dstClinic = VetRegisterationTemp::where('slug', $dstSlug)->firstOrFail();

        $srcDoctor = $this->resolveDoctor($srcClinic->id, (string) $this->option('src-doctor'), 'source');
        $dstDoctor = $this->resolveDoctor($dstClinic->id, (string) $this->option('dst-doctor'), 'destination');

        $this->info("Source clinic: {$srcClinic->id} ({$srcClinic->slug})");
        $this->info("Destination clinic: {$dstClinic->id} ({$dstClinic->slug})");
        $this->info("Source doctor: {$srcDoctor->id} (".($srcDoctor->doctor_email ?: $srcDoctor->doctor_name).')');
        $this->info("Destination doctor: {$dstDoctor->id} (".($dstDoctor->doctor_email ?: $dstDoctor->doctor_name).')');

        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Dry-run mode: no data will be updated.');
        }

        $results = [
            'doctor_video_availability' => 0,
            'doctor_availability' => 0,
            'clinic_emergency_hours' => 0,
        ];

        // Count rows ahead of time
        $results['doctor_video_availability'] = DB::table('doctor_video_availability')
            ->where('doctor_id', $srcDoctor->id)
            ->count();
        $results['doctor_availability'] = DB::table('doctor_availability')
            ->where('doctor_id', $srcDoctor->id)
            ->whereIn('service_type', ['video', 'in_clinic'])
            ->count();
        $results['clinic_emergency_hours'] = DB::table('clinic_emergency_hours')
            ->where('clinic_id', $srcClinic->id)
            ->exists() ? 1 : 0;

        if (!$dryRun) {
            DB::transaction(function () use ($srcClinic, $dstClinic, $srcDoctor, $dstDoctor) {
                DB::table('doctor_video_availability')
                    ->where('doctor_id', $srcDoctor->id)
                    ->update([
                        'doctor_id' => $dstDoctor->id,
                        'updated_at' => now(),
                    ]);

                DB::table('doctor_availability')
                    ->where('doctor_id', $srcDoctor->id)
                    ->whereIn('service_type', ['video', 'in_clinic'])
                    ->update([
                        'doctor_id' => $dstDoctor->id,
                        'updated_at' => now(),
                    ]);

                $emergencyRow = DB::table('clinic_emergency_hours')
                    ->where('clinic_id', $srcClinic->id)
                    ->first();

                if ($emergencyRow) {
                    $doctorIds = [];
                    if (!empty($emergencyRow->doctor_ids)) {
                        $decoded = json_decode($emergencyRow->doctor_ids, true);
                        $doctorIds = is_array($decoded) ? array_values(array_filter($decoded)) : [];
                    }

                    $doctorIds = array_map(
                        fn ($id) => (int) $id === (int) $srcDoctor->id ? $dstDoctor->id : (int) $id,
                        $doctorIds
                    );
                    $doctorIds[] = $dstDoctor->id; // ensure present
                    $doctorIds = array_values(array_unique(array_filter($doctorIds, fn ($id) => (int) $id > 0)));

                    DB::table('clinic_emergency_hours')
                        ->where('clinic_id', $srcClinic->id)
                        ->update([
                            'clinic_id' => $dstClinic->id,
                            'doctor_ids' => json_encode($doctorIds),
                            'updated_at' => now(),
                        ]);
                }
            }, 5);
        }

        $this->line('');
        $this->info('Updates (rows touched):');
        $this->line(' - doctor_video_availability: '.$results['doctor_video_availability']);
        $this->line(' - doctor_availability (video + in_clinic): '.$results['doctor_availability']);
        $this->line(' - clinic_emergency_hours: '.$results['clinic_emergency_hours']);

        if ($dryRun) {
            $this->warn('Dry-run complete. Re-run without --dry-run to apply.');
        } else {
            $this->info('Done. Reload the onboarding panel to verify.');
        }

        return Command::SUCCESS;
    }

    /**
     * Resolve a doctor by id or email within a clinic, or pick the only doctor.
     */
    protected function resolveDoctor(int $clinicId, ?string $option, string $side): Doctor
    {
        $query = Doctor::where('vet_registeration_id', $clinicId);

        if ($option !== null && $option !== '') {
            if (ctype_digit($option)) {
                $doctor = (clone $query)->where('id', (int) $option)->first();
            } else {
                $doctor = (clone $query)->where('doctor_email', $option)->first();
            }

            if (!$doctor) {
                $this->error("Could not find {$side} doctor '{$option}' in clinic {$clinicId}.");
                exit(Command::FAILURE);
            }

            return $doctor;
        }

        $doctors = $query->get();
        if ($doctors->count() === 1) {
            return $doctors->first();
        }

        $this->error("Clinic {$clinicId} has {$doctors->count()} doctors. Use --{$side}-doctor=<id|email>.");
        $this->line('Doctors: '.$doctors->pluck('id', 'doctor_email')->toJson());
        exit(Command::FAILURE);
    }
}
