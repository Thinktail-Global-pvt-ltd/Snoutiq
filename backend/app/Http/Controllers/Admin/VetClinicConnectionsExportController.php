<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VetClinicConnectionsExportController extends Controller
{
    public function export(): StreamedResponse
    {
        $doctorLookup = $this->buildDoctorLookup();
        $petLookup = $this->buildPetLookup();

        $connections = DB::table('vet_registerations_temp as v')
            ->leftJoin('users as u', 'u.last_vet_id', '=', 'v.id')
            ->select([
                'v.id as vet_id',
                'v.name as clinic_name',
                'v.city as clinic_city',
                'v.pincode as clinic_pincode',
                'v.status as clinic_status',
                'v.email as clinic_email',
                'v.mobile as clinic_mobile',
                'v.created_at as clinic_created_at',
                'u.id as user_id',
                'u.name as user_name',
                'u.email as user_email',
                'u.phone as user_phone',
                'u.role as user_role',
                'u.updated_at as user_updated_at',
            ])
            ->orderBy('v.id')
            ->orderBy('u.id')
            ->get();

        $filename = 'vet-clinic-connections-'.now()->format('Ymd_His').'.csv';
        $formatDate = static fn ($value) => $value ? Carbon::parse($value)->toDateTimeString() : '';

        return response()->streamDownload(function () use ($connections, $doctorLookup, $petLookup, $formatDate) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Vet ID',
                'Clinic Name',
                'City',
                'Pincode',
                'Status',
                'Clinic Email',
                'Clinic Mobile',
                'Clinic Created At',
                'Doctor Count',
                'Doctor Details',
                'User ID',
                'User Name',
                'User Email',
                'User Phone',
                'User Role',
                'User Updated At',
                'Pet Count',
                'Pet Details',
            ]);

            foreach ($connections as $row) {
                $doctorMeta = $doctorLookup[$row->vet_id] ?? ['count' => 0, 'summary' => ''];
                $petMeta = $row->user_id
                    ? ($petLookup[$row->user_id] ?? ['count' => 0, 'summary' => ''])
                    : ['count' => 0, 'summary' => ''];

                fputcsv($out, [
                    $row->vet_id,
                    $row->clinic_name,
                    $row->clinic_city,
                    $row->clinic_pincode,
                    $row->clinic_status,
                    $row->clinic_email,
                    $row->clinic_mobile,
                    $formatDate($row->clinic_created_at),
                    $doctorMeta['count'],
                    $doctorMeta['summary'],
                    $row->user_id,
                    $row->user_name,
                    $row->user_email,
                    $row->user_phone,
                    $row->user_role,
                    $formatDate($row->user_updated_at),
                    $petMeta['count'],
                    $petMeta['summary'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function buildDoctorLookup(): array
    {
        $formatDoctor = static function ($doc) {
            $name = trim((string) $doc->doctor_name);
            if ($name === '') {
                $name = 'Doctor #'.$doc->id;
            }

            $meta = array_filter([
                trim((string) $doc->doctor_email),
                trim((string) $doc->doctor_mobile),
            ]);

            if ($meta) {
                $name .= ' ('.implode(' | ', $meta).')';
            }

            return $name;
        };

        return DB::table('doctors')
            ->select('id', 'vet_registeration_id', 'doctor_name', 'doctor_email', 'doctor_mobile')
            ->orderBy('vet_registeration_id')
            ->orderBy('doctor_name')
            ->get()
            ->groupBy('vet_registeration_id')
            ->map(static function ($docs) use ($formatDoctor) {
                return [
                    'count' => $docs->count(),
                    'summary' => $docs->map($formatDoctor)->implode(' | '),
                ];
            })
            ->all();
    }

    private function buildPetLookup(): array
    {
        $formatPet = function ($pet) {
            $name = trim((string) $pet->name);
            if ($name === '') {
                $name = 'Pet #'.$pet->id;
            }

            $meta = array_filter([
                $pet->pet_type ? ucfirst((string) $pet->pet_type) : null,
                $pet->pet_gender ? ucfirst((string) $pet->pet_gender) : null,
                $this->formatPetAge($pet),
            ]);

            if ($meta) {
                $name .= ' ('.implode(', ', $meta).')';
            }

            return $name;
        };

        return DB::table('pets')
            ->select('id', 'user_id', 'name', 'pet_type', 'pet_gender', 'pet_age', 'pet_age_months')
            ->orderBy('user_id')
            ->orderBy('name')
            ->get()
            ->groupBy('user_id')
            ->map(function ($pets) use ($formatPet) {
                return [
                    'count' => $pets->count(),
                    'summary' => $pets->map($formatPet)->implode(' | '),
                ];
            })
            ->all();
    }

    private function formatPetAge(object $pet): ?string
    {
        $years = $pet->pet_age !== null ? (int) $pet->pet_age : null;
        $months = $pet->pet_age_months !== null ? (int) $pet->pet_age_months : null;

        if ($years !== null && $years > 0) {
            if ($months !== null && $months > 0) {
                return $years.'y '.$months.'m';
            }

            return $years.'y';
        }

        if ($months !== null && $months > 0) {
            return $months.'m';
        }

        return null;
    }
}

