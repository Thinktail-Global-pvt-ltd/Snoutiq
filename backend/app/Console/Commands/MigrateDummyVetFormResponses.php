<?php

namespace App\Console\Commands;

use App\Models\Doctor;
use App\Models\VetRegisterationTemp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class MigrateDummyVetFormResponses extends Command
{
    protected $signature = 'data:migrate-dummy-vets {--chunk=200} {--limit=} {--dry-run}';
    protected $description = 'Copy rows from dummy_vet_form_responses into vet_registerations_temp and doctors tables.';

    public function handle(): int
    {
        if (! Schema::hasTable('dummy_vet_form_responses')) {
            $this->error('Table dummy_vet_form_responses does not exist.');
            return self::FAILURE;
        }

        $chunkSize = (int) $this->option('chunk') ?: 200;
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');

        $counters = [
            'rows_seen' => 0,
            'vets_created' => 0,
            'vets_updated' => 0,
            'doctors_created' => 0,
            'doctors_updated' => 0,
        ];

        $query = DB::table('dummy_vet_form_responses')->orderBy('id');
        if ($limit) {
            $query->limit($limit);
        }

        $this->info(sprintf(
            'Starting migration%s, chunk=%d%s',
            $dryRun ? ' (dry-run)' : '',
            $chunkSize,
            $limit ? ", limit={$limit}" : ''
        ));

        $query->chunk($chunkSize, function ($rows) use (&$counters, $dryRun) {
            foreach ($rows as $row) {
                $counters['rows_seen']++;
                if ($dryRun) {
                    $this->line($this->formatRowPreview($row));
                    continue;
                }

                DB::transaction(function () use ($row, &$counters) {
                    $vet = $this->findOrCreateVet($row, $counters);
                    $this->findOrCreateDoctor($row, $vet->id, $counters);
                });
            }
        });

        $this->info(sprintf(
            'Finished. rows=%d, vets: +%d/%d updated, doctors: +%d/%d updated',
            $counters['rows_seen'],
            $counters['vets_created'],
            $counters['vets_updated'],
            $counters['doctors_created'],
            $counters['doctors_updated'],
        ));

        return self::SUCCESS;
    }

    private function findOrCreateVet(object $row, array &$counters): VetRegisterationTemp
    {
        $email = $this->clean($row->email_address_2 ?? null);
        $mobile = $this->clean($row->whatsapp_number_active ?? null);
        $name = $this->clean($row->clinic_name ?? null) ?: $this->clean($row->vet_full_name ?? null) ?: 'Clinic';

        $vet = VetRegisterationTemp::query()
            ->where(function ($q) use ($email, $mobile, $name) {
                if ($email) {
                    $q->orWhere('email', $email);
                }
                if ($mobile) {
                    $q->orWhere('mobile', $mobile);
                }
                if ($name) {
                    $q->orWhere('name', $name);
                }
            })
            ->first();

        if (! $vet) {
            $vet = new VetRegisterationTemp();
            $vet->name = $name;
            $vet->email = $email;
            $vet->mobile = $mobile;
            $this->setVetPassword($vet);
            $vet->save();
            $counters['vets_created']++;
        } else {
            $updated = false;
            if ($email && ! $vet->email) {
                $vet->email = $email;
                $updated = true;
            }
            if ($mobile && ! $vet->mobile) {
                $vet->mobile = $mobile;
                $updated = true;
            }
            if ($name && ! $vet->name) {
                $vet->name = $name;
                $updated = true;
            }
            if ($updated) {
                $vet->save();
                $counters['vets_updated']++;
            }
        }

        // Always mark imported rows as coming from excel dump
        if (Schema::hasColumn('vet_registerations_temp', 'exported_from_excell')) {
            $vet->exported_from_excell = 1;
        }

        $this->setVetPassword($vet, false);
        $vet->save();

        return $vet;
    }

    private function findOrCreateDoctor(object $row, int $vetId, array &$counters): void
    {
        $email = $this->clean($row->email_address_2 ?? null);
        $mobile = $this->clean($row->whatsapp_number_active ?? null);
        $name = $this->clean($row->vet_full_name ?? null) ?: 'Doctor';

        $doctor = Doctor::query()
            ->where('vet_registeration_id', $vetId)
            ->where(function ($q) use ($email, $mobile, $name) {
                if ($email) {
                    $q->orWhere('doctor_email', $email);
                }
                if ($mobile) {
                    $q->orWhere('doctor_mobile', $mobile);
                }
                $q->orWhere('doctor_name', $name);
            })
            ->first();

        if (! $doctor) {
            $doctor = new Doctor();
            $doctor->vet_registeration_id = $vetId;
            $doctor->doctor_name = $name;
            $doctor->doctor_email = $email;
            $doctor->doctor_mobile = $mobile;
            $this->setDoctorPassword($doctor);
            $counters['doctors_created']++;
        } else {
            $counters['doctors_updated']++;
        }

        $this->assignDoctorFields($doctor, $row);
        $doctor->save();
    }

    private function assignDoctorFields(Doctor $doctor, object $row): void
    {
        $map = [
            'doctor_name' => $row->vet_full_name ?? null,
            'doctor_email' => $row->email_address_2 ?? null,
            'doctor_mobile' => $row->whatsapp_number_active ?? null,
            'degree' => $row->degree ?? null,
            'years_of_experience' => $row->years_of_experience ?? null,
            'specialization_select_all_that_apply' => $row->specialization_select_all_that_apply ?? null,
            'response_time_for_online_consults_day' => $row->response_time_for_online_consults_day ?? null,
            'response_time_for_online_consults_night' => $row->response_time_for_online_consults_night ?? null,
            'break_do_not_disturb_time_example_2_4_pm' => $row->break_do_not_disturb_time_example_2_4_pm ?? null,
            'do_you_offer_a_free_follow_up_within_3_days_after_a_consulta' => $row->do_you_offer_a_free_follow_up_within_3_days_after_a_consulta ?? null,
            'commission_and_agreement' => $row->commission_and_agreement ?? null,
            'exported_from_excell' => 1,
        ];

        foreach ($map as $field => $value) {
            $cleaned = $this->clean($value);
            if ($cleaned !== null && $cleaned !== '') {
                $doctor->{$field} = $cleaned;
            }
        }

        $videoDay = $this->money($row->video_consultation_price_day_time ?? null);
        $videoNight = $this->money($row->video_consultation_price_night_time ?? null);
        if ($videoDay !== null) {
            $doctor->video_day_rate = $videoDay;
        }
        if ($videoNight !== null) {
            $doctor->video_night_rate = $videoNight;
        }

        $this->setDoctorPassword($doctor, false);
    }

    private function clean($value): ?string
    {
        if ($value === null) {
            return null;
        }
        return trim((string) $value) === '' ? null : trim((string) $value);
    }

    private function money($value): ?float
    {
        if ($value === null) {
            return null;
        }
        $clean = preg_replace('/[^\d.]/', '', (string) $value);
        return $clean === '' ? null : round((float) $clean, 2);
    }

    private function setVetPassword(VetRegisterationTemp $vet, bool $onlyWhenMissing = true): void
    {
        if (! Schema::hasColumn('vet_registerations_temp', 'password')) {
            return;
        }
        if ($onlyWhenMissing && $vet->password) {
            return;
        }
        $vet->password = '123456';
    }

    private function setDoctorPassword(Doctor $doctor, bool $onlyWhenMissing = true): void
    {
        $hasPassword = Schema::hasColumn('doctors', 'password');
        $hasDoctorPassword = Schema::hasColumn('doctors', 'doctor_password');

        if (! $hasPassword && ! $hasDoctorPassword) {
            return;
        }

        $targetPassword = '123456';

        if ($hasPassword && (! $onlyWhenMissing || empty($doctor->password))) {
            $doctor->password = $targetPassword;
        }

        if ($hasDoctorPassword && (! $onlyWhenMissing || empty($doctor->doctor_password))) {
            $doctor->doctor_password = $targetPassword;
        }
    }

    private function formatRowPreview(object $row): string
    {
        return sprintf(
            '[id:%s] clinic:%s doctor:%s email:%s mobile:%s',
            $row->id ?? 'n/a',
            $this->clean($row->clinic_name ?? '') ?? '-',
            $this->clean($row->vet_full_name ?? '') ?? '-',
            $this->clean($row->email_address_2 ?? '') ?? '-',
            $this->clean($row->whatsapp_number_active ?? '') ?? '-',
        );
    }
}
