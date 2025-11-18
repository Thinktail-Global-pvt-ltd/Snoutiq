<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnboardingProgressService
{
    public function getStatusForRequest(Request $request): array
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return [];
        }

        return $this->getStatusForClinic($clinicId);
    }

    public function getStatusForClinic(int $clinicId): array
    {
        $doctorIds = $this->getDoctorIds($clinicId);

        $hasServices = DB::table('groomer_services')
            ->where('user_id', $clinicId)
            ->exists();

        $hasVideo = false;
        if ($doctorIds) {
            $hasVideo = DB::table('doctor_video_availability')
                ->where('is_active', 1)
                ->whereIn('doctor_id', $doctorIds)
                ->exists();

            // Fallback to legacy storage in doctor_availability (service_type = video)
            if (!$hasVideo) {
                $hasVideo = DB::table('doctor_availability')
                    ->where('service_type', 'video')
                    ->whereIn('doctor_id', $doctorIds)
                    ->exists();
            }
        }

        $hasClinicHours = $doctorIds
            ? DB::table('doctor_availability')
                ->where('service_type', 'in_clinic')
                ->whereIn('doctor_id', $doctorIds)
                ->exists()
            : false;

        $hasEmergency = DB::table('clinic_emergency_hours')
            ->where('clinic_id', $clinicId)
            ->exists();

        $clinicDoc = DB::table('vet_registerations_temp')
            ->where('id', $clinicId)
            ->select('license_no', 'license_document')
            ->first();
        $hasClinicDocs = $clinicDoc && (!empty($clinicDoc->license_no) || !empty($clinicDoc->license_document));

        $hasDoctorDocs = DB::table('doctors')
            ->where('vet_registeration_id', $clinicId)
            ->whereNotNull('doctor_license')
            ->where('doctor_license', '<>', '')
            ->whereNotNull('doctor_document')
            ->where('doctor_document', '<>', '')
            ->exists();

        return [
            'services' => $hasServices,
            'video' => $hasVideo,
            'clinic_hours' => $hasClinicHours,
            'emergency' => $hasEmergency,
            'documents' => ($hasClinicDocs && $hasDoctorDocs),
        ];
    }

    public function resolveClinicId(Request $request): ?int
    {
        $session = $request->session();
        $role = $session->get('role')
            ?? data_get($session->get('auth_full'), 'role')
            ?? data_get($session->get('user'), 'role');

        $candidates = [
            $session->get('clinic_id'),
            $session->get('vet_registerations_temp_id'),
            $session->get('vet_registeration_id'),
            $session->get('vet_id'),
            data_get($session->get('auth_full'), 'clinic_id'),
            data_get($session->get('auth_full'), 'user.clinic_id'),
            data_get($session->get('auth_full'), 'user.vet_registeration_id'),
            data_get($session->get('user'), 'clinic_id'),
            data_get($session->get('user'), 'vet_registeration_id'),
        ];

        if ($role === 'doctor') {
            array_unshift(
                $candidates,
                data_get($session->get('user'), 'id'),
                $session->get('user_id'),
                data_get($session->get('auth_full'), 'user.id')
            );
        } else {
            array_unshift(
                $candidates,
                data_get($session->get('user'), 'id'),
                $session->get('user_id')
            );
        }

        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }
            $num = (int) $candidate;
            if ($num > 0) {
                return $num;
            }
        }

        return null;
    }

    protected function getDoctorIds(int $clinicId): array
    {
        return DB::table('doctors')
            ->where('vet_registeration_id', $clinicId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
