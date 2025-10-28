<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetRegisterationTemp;
use App\Services\AdminOnboardingStatusService;

class AdminOnboardingStatusPageController extends Controller
{
    protected AdminOnboardingStatusService $statusService;

    public function __construct(AdminOnboardingStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function services()
    {
        $clinics = collect($this->statusService->getServicesData());

        $stats = [
            'total_clinics' => $clinics->count(),
            'clinics_with_info' => $clinics->where('services_info_complete', true)->count(),
            'total_doctors' => $clinics->sum('doctor_count'),
            'doctors_with_info' => $clinics->sum('doctors_with_services'),
        ];

        return view('admin.onboarding.services', compact('clinics', 'stats'));
    }

    public function video()
    {
        $clinics = collect($this->statusService->getVideoData());

        $stats = [
            'total_clinics' => $clinics->count(),
            'clinics_with_config' => $clinics->where('has_any_video_config', true)->count(),
            'total_doctors' => $clinics->sum('doctor_count'),
            'doctors_with_config' => $clinics->sum('doctors_with_video'),
        ];

        return view('admin.onboarding.video', compact('clinics', 'stats'));
    }

    public function clinicHours()
    {
        $clinics = collect($this->statusService->getClinicHoursData());

        $stats = [
            'total_clinics' => $clinics->count(),
            'clinics_with_config' => $clinics->where('has_any_clinic_hours', true)->count(),
            'total_doctors' => $clinics->sum('doctor_count'),
            'doctors_with_config' => $clinics->sum('doctors_with_clinic_hours'),
        ];

        return view('admin.onboarding.clinic-hours', compact('clinics', 'stats'));
    }

    public function emergency()
    {
        $clinics = collect($this->statusService->getEmergencyData());

        $stats = [
            'total_clinics' => $clinics->count(),
            'clinics_with_program' => $clinics->where('has_emergency_program', true)->count(),
            'total_doctors' => $clinics->sum('doctor_count'),
            'doctors_in_program' => $clinics->sum('doctors_in_emergency'),
        ];

        return view('admin.onboarding.emergency', compact('clinics', 'stats'));
    }

    public function panel()
    {
        $services = collect($this->statusService->getServicesData());
        $video = collect($this->statusService->getVideoData());
        $clinicHours = collect($this->statusService->getClinicHoursData());
        $emergency = collect($this->statusService->getEmergencyData());

        $summary = [
            'clinics' => $services->count(),
            'doctors' => $services->sum('doctor_count'),
            'video_ready' => $video->sum('doctors_with_video'),
            'emergency_ready' => $emergency->sum('doctors_in_emergency'),
        ];

        $stepLabels = [
            'services' => 'Services',
            'video' => 'Video Calling',
            'clinic_hours' => 'Clinic Hours',
            'emergency' => 'Emergency Cover',
        ];

        $doctorProgress = [];
        $datasets = [
            'services' => $services,
            'video' => $video,
            'clinic_hours' => $clinicHours,
            'emergency' => $emergency,
        ];

        foreach ($datasets as $type => $clinicCollection) {
            foreach ($clinicCollection as $clinic) {
                $clinicId = $clinic['clinic_id'];

                if (!isset($doctorProgress[$clinicId])) {
                    $doctorProgress[$clinicId] = [
                        'clinic_id' => $clinicId,
                        'clinic_name' => $clinic['clinic_name'],
                        'doctors' => [],
                    ];
                }

                foreach ($clinic['doctors'] as $doctor) {
                    $doctorId = $doctor['doctor_id'];

                    if (!isset($doctorProgress[$clinicId]['doctors'][$doctorId])) {
                        $doctorProgress[$clinicId]['doctors'][$doctorId] = [
                            'doctor_id' => $doctorId,
                            'doctor_name' => $doctor['doctor_name'],
                            'doctor_email' => $doctor['doctor_email'] ?? null,
                            'doctor_mobile' => $doctor['doctor_mobile'] ?? null,
                            'doctor_license' => $doctor['doctor_license'] ?? null,
                            'steps' => [
                                'services' => false,
                                'video' => false,
                                'clinic_hours' => false,
                                'emergency' => false,
                            ],
                        ];
                    }

                    if ($type === 'services') {
                        $hasServices = !empty($doctor['services']);
                        if (!$hasServices) {
                            $clinicServiceCount = (int) data_get($clinic, 'clinic_service_count', 0);
                            $hasServices = $clinicServiceCount > 0;
                        }
                        $doctorProgress[$clinicId]['doctors'][$doctorId]['steps']['services'] = $hasServices;
                    } elseif ($type === 'video') {
                        $doctorProgress[$clinicId]['doctors'][$doctorId]['steps']['video'] = (bool) data_get($doctor, 'video.has_data', false);
                    } elseif ($type === 'clinic_hours') {
                        $doctorProgress[$clinicId]['doctors'][$doctorId]['steps']['clinic_hours'] = (bool) data_get($doctor, 'clinic_hours.has_data', false);
                    } elseif ($type === 'emergency') {
                        $doctorProgress[$clinicId]['doctors'][$doctorId]['steps']['emergency'] = (bool) data_get($doctor, 'emergency.is_listed', false);
                    }
                }
            }
        }

        foreach ($doctorProgress as $clinicId => $clinicData) {
            $totalDoctors = count($clinicData['doctors']);
            $allStepsCount = 0;

            foreach ($clinicData['doctors'] as $doctorId => $doctorData) {
                $steps = $doctorData['steps'];
                $completed = [];
                $pending = [];

                foreach ($steps as $key => $done) {
                    $label = $stepLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                    if ($done) {
                        $completed[] = $label;
                    } else {
                        $pending[] = $label;
                    }
                }

                $allStepsComplete = empty($pending);
                if ($allStepsComplete) {
                    $allStepsCount++;
                }

                $doctorProgress[$clinicId]['doctors'][$doctorId]['all_steps_complete'] = $allStepsComplete;
                $doctorProgress[$clinicId]['doctors'][$doctorId]['completed_step_labels'] = $completed;
                $doctorProgress[$clinicId]['doctors'][$doctorId]['pending_step_labels'] = $pending;
            }

            $doctorProgress[$clinicId]['totals'] = [
                'total_doctors' => $totalDoctors,
                'all_steps_complete' => $allStepsCount,
            ];
        }

        $stats = [
            'services' => [
                'total_clinics' => $services->count(),
                'with_info' => $services->where('services_info_complete', true)->count(),
                'total_doctors' => $services->sum('doctor_count'),
                'doctors_ready' => $services->sum('doctors_with_services'),
            ],
            'video' => [
                'total_clinics' => $video->count(),
                'with_video' => $video->where('has_any_video_config', true)->count(),
                'total_doctors' => $video->sum('doctor_count'),
                'doctors_ready' => $video->sum('doctors_with_video'),
            ],
            'clinic_hours' => [
                'total_clinics' => $clinicHours->count(),
                'with_hours' => $clinicHours->where('has_any_clinic_hours', true)->count(),
                'total_doctors' => $clinicHours->sum('doctor_count'),
                'doctors_ready' => $clinicHours->sum('doctors_with_clinic_hours'),
            ],
            'emergency' => [
                'total_clinics' => $emergency->count(),
                'with_program' => $emergency->where('has_emergency_program', true)->count(),
                'total_doctors' => $emergency->sum('doctor_count'),
                'doctors_ready' => $emergency->sum('doctors_in_emergency'),
            ],
        ];

        $recentUsers = User::select(['id', 'name', 'email', 'phone', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $recentPets = Pet::select(['id', 'name', 'breed', 'pet_age', 'pet_gender', 'user_id', 'created_at'])
            ->with(['owner:id,name,email'])
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        $recentDoctors = Doctor::select(['id', 'doctor_name', 'doctor_email', 'doctor_mobile', 'doctor_license', 'vet_registeration_id', 'created_at'])
            ->with(['clinic:id,name,city'])
            ->orderBy('doctor_name')
            ->limit(25)
            ->get();

        $recentClinics = VetRegisterationTemp::select(['id', 'name', 'email', 'city', 'pincode', 'created_at'])
            ->withCount('doctors')
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        return view('admin.onboarding.panel', compact(
            'services',
            'video',
            'clinicHours',
            'emergency',
            'summary',
            'stats',
            'doctorProgress',
            'stepLabels',
            'recentUsers',
            'recentPets',
            'recentDoctors',
            'recentClinics'
        ));
    }
}
