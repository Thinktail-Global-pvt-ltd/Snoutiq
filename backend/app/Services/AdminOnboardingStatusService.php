<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminOnboardingStatusService
{
    /**
     * Fetch clinics with core metadata.
     */
    protected function fetchClinics(): Collection
    {
        return DB::table('vet_registerations_temp as v')
            ->select(
                'v.id',
                DB::raw('COALESCE(v.name, v.slug, CONCAT("Clinic #", v.id)) as display_name'),
                'v.name',
                'v.slug',
                'v.city',
                'v.pincode',
                'v.email',
                'v.mobile',
                'v.chat_price',
                'v.bio',
                'v.clinic_profile',
                'v.hospital_profile',
                'v.business_status',
                'v.address',
                'v.license_no',
                'v.license_document',
                'v.created_at',
                'v.updated_at'
            )
            ->orderBy('display_name')
            ->get();
    }

    /**
     * Fetch doctors grouped by clinic id.
     */
    protected function fetchDoctorsByClinic(): Collection
    {
        return DB::table('doctors')
            ->select(
                'id',
                'doctor_name',
                'doctor_email',
                'doctor_mobile',
                'doctor_license',
                'doctor_document',
                'vet_registeration_id'
            )
            ->orderBy('doctor_name')
            ->get()
            ->groupBy('vet_registeration_id');
    }

    /**
     * Services completion data per clinic.
     */
    public function getServicesData(): array
    {
        $clinics = $this->fetchClinics();
        $doctorsByClinic = $this->fetchDoctorsByClinic();

        $clinicServiceRows = DB::table('groomer_services')
            ->select(
                'user_id',
                'name',
                'main_service',
                'status',
                'price',
                'duration',
                'created_at',
                'updated_at'
            )
            ->orderBy('name')
            ->get()
            ->groupBy('user_id');

        $serviceRows = DB::table('doctor_availability')
            ->where('service_type', 'home_visit')
            ->select(
                'doctor_id',
                'service_type',
                DB::raw('COUNT(*) as slot_count'),
                DB::raw('MAX(created_at) as last_created_at')
            )
            ->groupBy('doctor_id', 'service_type')
            ->get();

        $servicesMap = [];
        foreach ($serviceRows as $row) {
            $servicesMap[$row->doctor_id][] = [
                'service_type'    => $row->service_type,
                'slot_count'      => (int) $row->slot_count,
                'last_created_at' => $row->last_created_at ? (string) $row->last_created_at : null,
            ];
        }

        $payload = [];
        foreach ($clinics as $clinic) {
            $clinicDoctors = $doctorsByClinic->get($clinic->id) ?? collect();
            $clinicServices = $clinicServiceRows->get($clinic->id) ?? collect();
            $clinicServiceCount = $clinicServices->count();
            $doctorEntries = [];
            $doctorsWithServices = 0;

            foreach ($clinicDoctors as $doctor) {
                $services = $servicesMap[$doctor->id] ?? [];
                if (!empty($services)) {
                    $doctorsWithServices++;
                }

                $doctorEntries[] = [
                    'doctor_id'      => (int) $doctor->id,
                    'doctor_name'    => $doctor->doctor_name,
                    'doctor_email'   => $doctor->doctor_email,
                    'doctor_mobile'  => $doctor->doctor_mobile,
                    'doctor_license' => $doctor->doctor_license,
                    'services'       => $services,
                ];
            }

            $clinicServiceEntries = $clinicServices->map(fn ($service) => [
                'name'        => $service->name,
                'main'        => $service->main_service,
                'status'      => $service->status,
                'price'       => $service->price !== null ? (float) $service->price : null,
                'duration'    => $service->duration !== null ? (int) $service->duration : null,
                'created_at'  => $service->created_at ? (string) $service->created_at : null,
                'updated_at'  => $service->updated_at ? (string) $service->updated_at : null,
            ])->values()->all();

            $servicesInfoComplete = $doctorsWithServices > 0 || $clinicServiceCount > 0;

            $payload[] = [
                'clinic_id'              => (int) $clinic->id,
                'clinic_name'            => $clinic->display_name,
                'slug'                   => $clinic->slug,
                'city'                   => $clinic->city,
                'pincode'                => $clinic->pincode,
                'email'                  => $clinic->email,
                'mobile'                 => $clinic->mobile,
                'address'                => $clinic->address,
                'business_status'        => $clinic->business_status,
                'chat_price'             => $clinic->chat_price !== null ? (float) $clinic->chat_price : null,
                'bio'                    => $clinic->bio,
                'clinic_profile'         => $clinic->clinic_profile,
                'hospital_profile'       => $clinic->hospital_profile,
                'doctor_count'           => $clinicDoctors->count(),
                'doctors_with_services'  => $doctorsWithServices,
                'services_info_complete' => $servicesInfoComplete,
                'clinic_service_count'   => $clinicServiceCount,
                'clinic_services'        => $clinicServiceEntries,
                'last_updated_at'        => $clinic->updated_at ? (string) $clinic->updated_at : null,
                'created_at'             => $clinic->created_at ? (string) $clinic->created_at : null,
                'doctors'                => $doctorEntries,
            ];
        }

        return $payload;
    }

    /**
     * Video call configuration data per clinic.
     */
    public function getVideoData(): array
    {
        $clinics = $this->fetchClinics();
        $doctorsByClinic = $this->fetchDoctorsByClinic();

        $videoRows = DB::table('doctor_video_availability')
            ->select(
                'doctor_id',
                DB::raw('COUNT(*) as slot_count'),
                DB::raw('MAX(updated_at) as last_updated_at')
            )
            ->groupBy('doctor_id')
            ->get()
            ->keyBy('doctor_id');

        $payload = [];
        foreach ($clinics as $clinic) {
            $clinicDoctors = $doctorsByClinic->get($clinic->id) ?? collect();
            $doctorEntries = [];
            $configuredCount = 0;

            foreach ($clinicDoctors as $doctor) {
                $videoMeta = $videoRows->get($doctor->id);
                $slotCount = $videoMeta ? (int) $videoMeta->slot_count : 0;
                $hasData = $slotCount > 0;
                if ($hasData) {
                    $configuredCount++;
                }

                $doctorEntries[] = [
                    'doctor_id'      => (int) $doctor->id,
                    'doctor_name'    => $doctor->doctor_name,
                    'doctor_email'   => $doctor->doctor_email,
                    'doctor_mobile'  => $doctor->doctor_mobile,
                    'doctor_license' => $doctor->doctor_license,
                    'video'          => [
                        'has_data'        => $hasData,
                        'slot_count'      => $slotCount,
                        'last_updated_at' => $videoMeta && $videoMeta->last_updated_at ? (string) $videoMeta->last_updated_at : null,
                    ],
                ];
            }

            $payload[] = [
                'clinic_id'            => (int) $clinic->id,
                'clinic_name'          => $clinic->display_name,
                'slug'                 => $clinic->slug,
                'city'                 => $clinic->city,
                'doctor_count'         => $clinicDoctors->count(),
                'doctors_with_video'   => $configuredCount,
                'has_any_video_config' => $configuredCount > 0,
                'last_updated_at'      => $clinic->updated_at ? (string) $clinic->updated_at : null,
                'doctors'              => $doctorEntries,
            ];
        }

        return $payload;
    }

    /**
     * Clinic hour configuration data per clinic.
     */
    public function getClinicHoursData(): array
    {
        $clinics = $this->fetchClinics();
        $doctorsByClinic = $this->fetchDoctorsByClinic();

        $hourRows = DB::table('doctor_availability')
            ->where('service_type', 'in_clinic')
            ->select(
                'doctor_id',
                DB::raw('COUNT(*) as slot_count'),
                DB::raw('MAX(created_at) as last_created_at')
            )
            ->groupBy('doctor_id')
            ->get()
            ->keyBy('doctor_id');

        $payload = [];
        foreach ($clinics as $clinic) {
            $clinicDoctors = $doctorsByClinic->get($clinic->id) ?? collect();
            $doctorEntries = [];
            $configuredCount = 0;

            foreach ($clinicDoctors as $doctor) {
                $hourMeta = $hourRows->get($doctor->id);
                $slotCount = $hourMeta ? (int) $hourMeta->slot_count : 0;
                $hasData = $slotCount > 0;
                if ($hasData) {
                    $configuredCount++;
                }

                $doctorEntries[] = [
                    'doctor_id'      => (int) $doctor->id,
                    'doctor_name'    => $doctor->doctor_name,
                    'doctor_email'   => $doctor->doctor_email,
                    'doctor_mobile'  => $doctor->doctor_mobile,
                    'doctor_license' => $doctor->doctor_license,
                    'clinic_hours'   => [
                        'has_data'        => $hasData,
                        'slot_count'      => $slotCount,
                        'last_created_at' => $hourMeta && $hourMeta->last_created_at ? (string) $hourMeta->last_created_at : null,
                    ],
                ];
            }

            $payload[] = [
                'clinic_id'                 => (int) $clinic->id,
                'clinic_name'               => $clinic->display_name,
                'slug'                      => $clinic->slug,
                'city'                      => $clinic->city,
                'doctor_count'              => $clinicDoctors->count(),
                'doctors_with_clinic_hours' => $configuredCount,
                'has_any_clinic_hours'      => $configuredCount > 0,
                'doctors'                   => $doctorEntries,
            ];
        }

        return $payload;
    }

    /**
     * Emergency coverage data per clinic.
     */
    public function getEmergencyData(): array
    {
        $clinics = $this->fetchClinics();
        $doctorsByClinic = $this->fetchDoctorsByClinic();

        $emergencyRows = DB::table('clinic_emergency_hours')
            ->select('clinic_id', 'doctor_ids', 'night_slots', 'consultation_price', 'updated_at', 'created_at')
            ->get()
            ->keyBy('clinic_id');

        $payload = [];
        foreach ($clinics as $clinic) {
            $clinicDoctors = $doctorsByClinic->get($clinic->id) ?? collect();
            $doctorEntries = [];
            $configuredCount = 0;

            $emergencyMeta = $emergencyRows->get($clinic->id);
            $doctorIds = [];
            $nightSlots = [];
            $consultationPrice = null;
            $emergencyUpdatedAt = null;
            $emergencyCreatedAt = null;

            if ($emergencyMeta) {
                $doctorIds = array_values(array_filter(array_map('intval', (array) json_decode($emergencyMeta->doctor_ids ?? '[]', true))));
                $nightSlots = (array) json_decode($emergencyMeta->night_slots ?? '[]', true);
                $consultationPrice = $emergencyMeta->consultation_price !== null
                    ? (float) $emergencyMeta->consultation_price
                    : null;
                $emergencyUpdatedAt = $emergencyMeta->updated_at ? (string) $emergencyMeta->updated_at : null;
                $emergencyCreatedAt = $emergencyMeta->created_at ? (string) $emergencyMeta->created_at : null;
            }

            foreach ($clinicDoctors as $doctor) {
                $isListed = in_array((int) $doctor->id, $doctorIds, true);
                if ($isListed) {
                    $configuredCount++;
                }

                $doctorEntries[] = [
                    'doctor_id'      => (int) $doctor->id,
                    'doctor_name'    => $doctor->doctor_name,
                    'doctor_email'   => $doctor->doctor_email,
                    'doctor_mobile'  => $doctor->doctor_mobile,
                    'doctor_license' => $doctor->doctor_license,
                    'emergency'      => [
                        'is_listed' => $isListed,
                    ],
                ];
            }

            $payload[] = [
                'clinic_id'             => (int) $clinic->id,
                'clinic_name'           => $clinic->display_name,
                'slug'                  => $clinic->slug,
                'city'                  => $clinic->city,
                'doctor_count'          => $clinicDoctors->count(),
                'doctors_in_emergency'  => $configuredCount,
                'has_emergency_program' => $configuredCount > 0,
                'night_slots'           => $nightSlots,
                'consultation_price'    => $consultationPrice,
                'updated_at'            => $emergencyUpdatedAt,
                'created_at'            => $emergencyCreatedAt,
                'doctors'               => $doctorEntries,
            ];
        }

        return $payload;
    }

    /**
     * Documents and compliance data per clinic.
     */
    public function getDocumentsData(): array
    {
        $clinics = $this->fetchClinics();
        $doctorsByClinic = $this->fetchDoctorsByClinic();

        $payload = [];
        foreach ($clinics as $clinic) {
            $clinicDoctors = $doctorsByClinic->get($clinic->id) ?? collect();
            $doctorEntries = [];
            $completedCount = 0;

            foreach ($clinicDoctors as $doctor) {
                $hasLicense = trim((string) ($doctor->doctor_license ?? '')) !== '';
                $hasDocument = !empty($doctor->doctor_document);
                $isComplete = $hasLicense && $hasDocument;

                if ($isComplete) {
                    $completedCount++;
                }

                $doctorEntries[] = [
                    'doctor_id'      => (int) $doctor->id,
                    'doctor_name'    => $doctor->doctor_name,
                    'doctor_email'   => $doctor->doctor_email,
                    'doctor_mobile'  => $doctor->doctor_mobile,
                    'doctor_license' => $doctor->doctor_license,
                    'documents'      => [
                        'has_license'   => $hasLicense,
                        'has_document'  => $hasDocument,
                        'document_path' => $doctor->doctor_document,
                        'completed'     => $isComplete,
                    ],
                ];
            }

            $clinicHasLicense = trim((string) ($clinic->license_no ?? '')) !== '';
            $clinicHasDocument = !empty($clinic->license_document);
            $doctorCount = $clinicDoctors->count();

            $payload[] = [
                'clinic_id'               => (int) $clinic->id,
                'clinic_name'             => $clinic->display_name,
                'slug'                    => $clinic->slug,
                'city'                    => $clinic->city,
                'pincode'                 => $clinic->pincode,
                'email'                   => $clinic->email,
                'mobile'                  => $clinic->mobile,
                'address'                 => $clinic->address,
                'doctor_count'            => $doctorCount,
                'doctors_with_documents'  => $completedCount,
                'clinic_license_no'       => $clinic->license_no,
                'clinic_license_document' => $clinic->license_document,
                'clinic_has_license'      => $clinicHasLicense,
                'clinic_has_document'     => $clinicHasDocument,
                'documents_complete'      => $clinicHasLicense && $clinicHasDocument && ($doctorCount === 0 ? true : $completedCount === $doctorCount),
                'doctors'                 => $doctorEntries,
            ];
        }

        return $payload;
    }
}
