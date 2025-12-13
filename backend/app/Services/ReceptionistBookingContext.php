<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Receptionist;

class ReceptionistBookingContext
{
    public static function resolve(string $viewMode = 'create'): array
    {
        $sessionRole = session('role')
            ?? data_get(session('auth_full'), 'role')
            ?? data_get(session('user'), 'role');

        $sessionClinicId = session('clinic_id')
            ?? session('vet_registerations_temp_id')
            ?? session('vet_registeration_id')
            ?? session('vet_id')
            ?? data_get(session('user'), 'clinic_id')
            ?? data_get(session('user'), 'vet_registeration_id')
            ?? data_get(session('auth_full'), 'clinic_id')
            ?? data_get(session('auth_full'), 'user.clinic_id')
            ?? data_get(session('auth_full'), 'user.vet_registeration_id')
            ?? null;

        $receptionistClinicId = null;
        if ($sessionRole === 'receptionist') {
            $receptionistRecord = Receptionist::find(session('receptionist_id'));
            if ($receptionistRecord?->vet_registeration_id) {
                $receptionistClinicId = (int) $receptionistRecord->vet_registeration_id;
                $sessionClinicId = $sessionClinicId ?: $receptionistClinicId;
            }
        }

        $doctorList = [];
        if ($sessionClinicId) {
            $doctorList = Doctor::where('vet_registeration_id', $sessionClinicId)
                ->orderBy('doctor_name')
                ->get(['id', 'doctor_name'])
                ->toArray();
        }

        return [
            'viewMode' => $viewMode,
            'sessionRole' => $sessionRole,
            'sessionClinicId' => $sessionClinicId,
            'receptionistClinicId' => $receptionistClinicId,
            'doctorList' => $doctorList,
        ];
    }
}
