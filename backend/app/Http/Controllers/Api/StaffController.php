<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Receptionist;
use App\Models\VetRegisterationTemp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    private const EDITABLE_ROLES = ['doctor', 'receptionist'];

    private function resolveClinicId(Request $request): ?int
    {
        $directKeys = [
            'clinic_id',
            'vet_registeration_id',
            'vet_id',
            'user_id',
        ];
        foreach ($directKeys as $key) {
            $value = $request->input($key) ?? $request->query($key);
            if ($value !== null && $value !== '') {
                return (int) $value;
            }
        }

        $headers = [
            $request->header('X-Clinic-Id'),
            $request->header('X-Vet-Id'),
            $request->header('X-User-Id'),
            $request->header('X-Acting-User'),
            $request->header('X-Session-User'),
        ];
        foreach ($headers as $header) {
            if ($header !== null && $header !== '') {
                return (int) $header;
            }
        }

        $slug = $request->input('vet_slug')
            ?? $request->query('vet_slug')
            ?? $request->query('clinic_slug');
        if ($slug) {
            $clinicId = DB::table('vet_registerations_temp')
                ->whereRaw('LOWER(slug) = ?', [strtolower($slug)])
                ->value('id');
            if ($clinicId) {
                return (int) $clinicId;
            }
        }

        $role = session('role')
            ?? data_get(session('user'), 'role')
            ?? data_get(session('auth_full'), 'role');

        if (in_array($role, ['doctor', 'receptionist'], true)) {
            $clinicId = session('clinic_id')
                ?? session('vet_registerations_temp_id')
                ?? session('vet_registeration_id')
                ?? session('vet_id')
                ?? data_get(session('user'), 'clinic_id')
                ?? data_get(session('auth_full'), 'clinic_id')
                ?? data_get(session('auth_full'), 'user.clinic_id');
            if ($clinicId) {
                return (int) $clinicId;
            }
        }

        $sessionUserId = session('user_id') ?? data_get(session('user'), 'id');
        return $sessionUserId ? (int) $sessionUserId : null;
    }

    public function index(Request $request)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id or vet_slug is required',
            ], 422);
        }

        $clinic = VetRegisterationTemp::find($clinicId);
        if (!$clinic) {
            return response()->json([
                'status' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        $doctors = Doctor::query()
            ->where('vet_registeration_id', $clinicId)
            ->orderBy('doctor_name')
            ->get()
            ->map(function (Doctor $doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->doctor_name,
                    'email' => $doc->doctor_email,
                    'phone' => $doc->doctor_mobile,
                    'role' => $doc->staff_role ?: 'doctor',
                    'type' => 'doctor',
                    'image' => $doc->doctor_image,
                    'license' => $doc->doctor_license,
                    'created_at' => optional($doc->created_at)->toIso8601String(),
                ];
            });

        $receptionists = Receptionist::query()
            ->where('vet_registeration_id', $clinicId)
            ->orderBy('name')
            ->get()
            ->map(function (Receptionist $rec) {
                return [
                    'id' => $rec->id,
                    'name' => $rec->name,
                    'email' => $rec->email,
                    'phone' => $rec->phone,
                    'role' => $rec->role ?: 'receptionist',
                    'type' => 'receptionist',
                    'created_at' => optional($rec->created_at)->toIso8601String(),
                ];
            });

        return response()->json([
            'status' => true,
            'data' => [
                'clinic_admin' => [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'email' => $clinic->email,
                    'phone' => $clinic->mobile,
                    'role' => 'clinic_admin',
                    'type' => 'clinic',
                    'image' => $clinic->image,
                ],
                'doctors' => $doctors,
                'receptionists' => $receptionists,
                'editable_roles' => self::EDITABLE_ROLES,
            ],
        ]);
    }

    public function storeReceptionist(Request $request)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id or vet_slug is required',
            ], 422);
        }

        $clinic = VetRegisterationTemp::find($clinicId);
        if (!$clinic) {
            return response()->json([
                'status' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', 'string', Rule::in(self::EDITABLE_ROLES)],
        ]);

        $receptionist = Receptionist::create([
            'vet_registeration_id' => $clinicId,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'] ?? 'receptionist',
            'status' => 'active',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Staff member added',
            'data' => $receptionist,
        ], 201);
    }

    public function updateRole(Request $request, string $type, int $id)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id or vet_slug is required',
            ], 422);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(self::EDITABLE_ROLES)],
        ]);

        $role = $validated['role'];

        if ($type === 'doctor') {
            $doctor = Doctor::where('vet_registeration_id', $clinicId)->find($id);
            if (!$doctor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Doctor not found for this clinic',
                ], 404);
            }

            $doctor->staff_role = $role;
            $doctor->save();

            return response()->json([
                'status' => true,
                'message' => 'Doctor role updated',
                'data' => [
                    'id' => $doctor->id,
                    'role' => $doctor->staff_role,
                ],
            ]);
        }

        if ($type === 'receptionist') {
            $receptionist = Receptionist::where('vet_registeration_id', $clinicId)->find($id);
            if (!$receptionist) {
                return response()->json([
                    'status' => false,
                    'message' => 'Receptionist not found for this clinic',
                ], 404);
            }

            $receptionist->role = $role;
            $receptionist->save();

            return response()->json([
                'status' => true,
                'message' => 'Receptionist role updated',
                'data' => [
                    'id' => $receptionist->id,
                    'role' => $receptionist->role,
                ],
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unsupported staff type. Allowed: doctor or receptionist.',
        ], 422);
    }
}
