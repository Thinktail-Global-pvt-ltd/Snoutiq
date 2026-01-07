<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClinicsController extends Controller
{
    // GET /api/clinics
    public function index()
    {
        // Ensure "name" is always populated for frontend sorting/display
        $rows = DB::table('vet_registerations_temp')
            ->select(
                'id',
                DB::raw('COALESCE(name, slug, CONCAT("Clinic #", id)) as name'),
                'slug',
                'address'
            )
            ->orderByRaw('COALESCE(name, slug) ASC')
            ->limit(500)
            ->get();

        return response()->json(['clinics' => $rows]);
    }
    // GET /api/clinics/{id}/doctors
    public function doctors(string $id)
    {
        $clinic = DB::table('vet_registerations_temp')
            ->select('id', DB::raw('COALESCE(name, slug, CONCAT("Clinic #", id)) as name'), 'slug', 'address')
            ->where('id', (int)$id)
            ->first();
        if (!$clinic) {
            return response()->json(['success' => false, 'error' => 'Clinic not found'], 404);
        }
        $doctors = DB::table('doctors')
            ->where('vet_registeration_id', (int)$id)
            ->select('id', 'doctor_name as name', 'doctor_email as email', 'doctor_mobile as phone')
            ->orderBy('doctor_name')
            ->get();
        return response()->json(['clinic' => $clinic, 'doctors' => $doctors]);
    }

    // GET /api/clinics/{id}/availability
    public function availability(string $id)
    {
        $clinic = DB::table('vet_registerations_temp')
            ->select('id', DB::raw('COALESCE(name, slug, CONCAT("Clinic #", id)) as name'), 'slug', 'address')
            ->where('id', (int)$id)
            ->first();
        if (!$clinic) {
            return response()->json(['success' => false, 'error' => 'Clinic not found'], 404);
        }
        $doctors = DB::table('doctors')
            ->where('vet_registeration_id', (int)$id)
            ->select('id', 'doctor_name as name')
            ->get();
        $doctorIds = $doctors->pluck('id')->all();
        $availability = [];
        if ($doctorIds) {
            $availability = DB::table('doctor_availability')
                ->whereIn('doctor_id', $doctorIds)
                ->orderBy('doctor_id')
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
        }
        return response()->json([
            'clinic' => $clinic,
            'doctors' => $doctors,
            'availability' => $availability,
        ]);
    }

    // GET /api/clinics/{id}/services
    public function services(Request $request, string $id)
    {
        $clinicId = (int) $id;

        $clinic = DB::table('vet_registerations_temp')
            ->select('id', DB::raw('COALESCE(name, slug, CONCAT("Clinic #", id)) as name'), 'slug', 'address')
            ->where('id', $clinicId)
            ->first();

        if (!$clinic) {
            return response()->json(['success' => false, 'error' => 'Clinic not found'], 404);
        }

        $columns = [
            'id',
            'name',
            'description',
            'pet_type',
            'price',
            'duration',
            'status',
            'service_pic',
        ];

        if (Schema::hasColumn('groomer_services', 'main_service')) {
            $columns[] = 'main_service';
        }
        if (Schema::hasColumn('groomer_services', 'groomer_service_category_id')) {
            $columns[] = 'groomer_service_category_id as category_id';
        }

        $servicesQuery = DB::table('groomer_services')
            ->where('user_id', $clinicId)
            ->select($columns)
            ->orderBy('name');

        if ($status = $request->query('status')) {
            $servicesQuery->where('status', $status);
        }

        $services = $servicesQuery->get();

        return response()->json([
            'clinic'   => $clinic,
            'services' => $services,
        ]);
    }

    // GET /api/clinics/services?clinic_id=123
    public function servicesByClinicId(Request $request)
    {
        $clinicId = (int) $request->query('clinic_id', 0);
        if ($clinicId <= 0) {
            $slug = strtolower(trim((string) $request->query('vet_slug', $request->query('clinic_slug', ''))));
            if ($slug !== '') {
                $clinicId = (int) DB::table('vet_registerations_temp')
                    ->whereRaw('LOWER(slug) = ?', [$slug])
                    ->value('id');
            }
        }

        if ($clinicId <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'clinic_id is required',
            ], 422);
        }

        return $this->services($request, (string) $clinicId);
    }

    // GET /api/clinics/patients?clinic_id=123
    public function patientsByClinicId(Request $request)
    {
        $clinicId = (int) $request->query('clinic_id', 0);

        if ($clinicId <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'clinic_id is required',
            ], 422);
        }

        return $this->patients((string) $clinicId);
    }

    // GET /api/clinics/{id}/patients
    public function patients(string $id)
    {
        $clinicId = (int) $id;
        $clinic = DB::table('vet_registerations_temp')
            ->select('id', DB::raw('COALESCE(name, slug, CONCAT("Clinic #", id)) as name'), 'slug', 'address')
            ->where('id', $clinicId)
            ->first();

        if (!$clinic) {
            return response()->json(['success' => false, 'error' => 'Clinic not found'], 404);
        }

        $recordStats = DB::table('medical_records')
            ->select(
                'user_id',
                DB::raw('COUNT(*) as total_records'),
                DB::raw('MAX(created_at) as last_record_at')
            )
            ->where('vet_registeration_id', $clinicId)
            ->groupBy('user_id');

        $patients = DB::table('users as u')
            ->leftJoinSub($recordStats, 'mr', function ($join) {
                $join->on('mr.user_id', '=', 'u.id');
            })
            ->where('u.last_vet_id', $clinicId)
            ->orderByDesc('u.updated_at')
            ->limit(500)
            ->select(
                'u.id',
                'u.name',
                'u.email',
                'u.phone',
                'u.pet_name',
                'u.pet_gender',
                'u.pet_age',
                'u.breed',
                'u.updated_at',
                DB::raw('COALESCE(mr.total_records, 0) as records_count'),
                'mr.last_record_at'
            )
            ->get();

        $petMap = collect();

        if ($patients->isNotEmpty()) {
            $userIds = $patients->pluck('id');
            $petRows = collect();

            if (Schema::hasTable('user_pets')) {
                $petColumns = ['id', 'user_id', 'name', 'type', 'breed'];
                if (Schema::hasColumn('user_pets', 'gender')) {
                    $petColumns[] = 'gender';
                }
                if (Schema::hasColumn('user_pets', 'dob')) {
                    $petColumns[] = 'dob';
                }

                $petRows = $petRows->merge(
                    DB::table('user_pets')
                        ->select($petColumns)
                        ->whereIn('user_id', $userIds)
                        ->orderBy('name')
                        ->get()
                );
            }

            if (Schema::hasTable('pets')) {
                $userColumn = Schema::hasColumn('pets', 'user_id')
                    ? 'user_id'
                    : (Schema::hasColumn('pets', 'owner_id') ? 'owner_id' : null);

                if ($userColumn) {
                    $petColumns = ['id', DB::raw("{$userColumn} as user_id"), 'name', 'breed'];
                    if (Schema::hasColumn('pets', 'type')) {
                        $petColumns[] = 'type';
                    }
                    if (Schema::hasColumn('pets', 'pet_age')) {
                        $petColumns[] = 'pet_age';
                    }
                    if (Schema::hasColumn('pets', 'pet_gender')) {
                        $petColumns[] = DB::raw('pet_gender as gender');
                    } elseif (Schema::hasColumn('pets', 'gender')) {
                        $petColumns[] = 'gender';
                    }

                    $petRows = $petRows->merge(
                        DB::table('pets')
                            ->select($petColumns)
                            ->whereIn($userColumn, $userIds)
                            ->orderBy('name')
                            ->get()
                    );
                }
            }

            $petMap = $petRows->groupBy('user_id');
        }

        $patients = $patients->map(function ($patient) use ($petMap) {
            $patient->pets = ($petMap[$patient->id] ?? collect())->values();
            return $patient;
        });

        return response()->json([
            'clinic' => $clinic,
            'patients' => $patients,
        ]);
    }

    // POST /api/clinics/{id}/doctors
    public function storeDoctor(string $id)
    {
        $clinicId = (int)$id;
        $clinic = DB::table('vet_registerations_temp')->where('id', $clinicId)->first();
        if (!$clinic) {
            return response()->json(['success' => false, 'error' => 'Clinic not found'], 404);
        }

        $name    = trim(request('doctor_name') ?? request('name') ?? '');
        $email   = trim(request('doctor_email') ?? request('email') ?? '');
        $mobile  = trim(request('doctor_mobile') ?? request('phone') ?? '');
        $license = trim(request('doctor_license') ?? request('license') ?? '');

        if ($name === '') {
            return response()->json(['success' => false, 'error' => 'doctor_name is required'], 422);
        }

        $idNew = DB::table('doctors')->insertGetId([
            'vet_registeration_id' => $clinicId,
            'doctor_name'          => $name,
            'doctor_email'         => $email ?: null,
            'doctor_mobile'        => $mobile ?: null,
            'doctor_license'       => $license ?: null,
            'toggle_availability'  => 1,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $row = DB::table('doctors')->select('id','doctor_name','doctor_email','doctor_mobile','doctor_license','vet_registeration_id')->where('id',$idNew)->first();
        return response()->json(['success'=>true,'doctor'=>$row], 201);
    }
}
