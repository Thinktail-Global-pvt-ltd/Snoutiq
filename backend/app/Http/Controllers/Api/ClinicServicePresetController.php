<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicServicePreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClinicServicePresetController extends Controller
{
    private const DEFAULT_PRESETS = [
        'Vaccination',
        'Deworming',
        'Neutering',
    ];

    public function index(Request $request)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id missing',
            ], 422);
        }

        if (!Schema::hasTable('groomer_services')) {
            return response()->json([
                'status' => true,
                'data' => [],
            ]);
        }

        if (!Schema::hasColumn('groomer_services', 'user_id') || !Schema::hasColumn('groomer_services', 'name')) {
            return response()->json([
                'status' => true,
                'data' => [],
            ]);
        }

        $query = DB::table('groomer_services')
            ->selectRaw('id, user_id as clinic_id, TRIM(name) as name')
            ->where('user_id', $clinicId)
            ->whereNotNull('name')
            ->whereRaw("TRIM(name) <> ''");

        $presets = $query
            ->orderByDesc('id')
            ->get()
            ->map(static function ($row) {
                return [
                    'id' => isset($row->id) ? (int) $row->id : null,
                    'clinic_id' => isset($row->clinic_id) ? (int) $row->clinic_id : null,
                    'name' => $row->name ?? null,
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'data' => $presets,
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('clinic_service_presets')) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_service_presets table missing',
            ], 503);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id missing',
            ], 422);
        }

        $name = trim((string) $validated['name']);
        if ($name === '') {
            return response()->json([
                'status' => false,
                'message' => 'Name is required',
            ], 422);
        }

        $normalized = strtolower($name);
        $defaultNames = array_map(static function ($presetName) {
            return strtolower(trim((string) $presetName));
        }, self::DEFAULT_PRESETS);

        if (in_array($normalized, $defaultNames, true)) {
            return response()->json([
                'status' => true,
                'message' => 'Preset already exists',
                'data' => [
                    'id' => null,
                    'clinic_id' => $clinicId,
                    'name' => $name,
                    'is_default' => true,
                ],
            ]);
        }

        $existing = ClinicServicePreset::query()
            ->where('clinic_id', $clinicId)
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->first();

        if ($existing) {
            return response()->json([
                'status' => true,
                'message' => 'Preset already exists',
                'data' => $existing,
            ]);
        }

        $preset = ClinicServicePreset::create([
            'clinic_id' => $clinicId,
            'name' => $name,
        ]);

        return response()->json([
            'status' => true,
            'data' => $preset,
        ], 201);
    }

    private function resolveClinicId(Request $request): ?int
    {
        $directClinicId = $this->firstPositiveInt($request, [
            'clinic_id',
            'clinicId',
            'vet_id',
            'vetId',
            'vet_registeration_id',
            'vetRegisterationId',
            'vet_registerations_temp_id',
            'vetRegisterationsTempId',
        ]);
        if ($directClinicId) {
            return $directClinicId;
        }

        $userId = $this->firstPositiveInt($request, ['user_id', 'userId']);
        if ($userId) {
            return $this->resolveClinicIdFromUserIdentifier($userId);
        }

        $slug = $request->query('vet_slug')
            ?? $request->query('clinic_slug')
            ?? $request->query('vetSlug')
            ?? $request->query('clinicSlug')
            ?? $request->input('vet_slug')
            ?? $request->input('clinic_slug')
            ?? $request->input('vetSlug')
            ?? $request->input('clinicSlug');

        if ($slug) {
            if (Schema::hasTable('vet_registerations_temp')) {
                $row = DB::table('vet_registerations_temp')
                    ->select('id')
                    ->whereRaw('LOWER(slug) = ?', [strtolower($slug)])
                    ->first();
                if ($row) {
                    return (int) $row->id;
                }
            }
            return null;
        }

        $headerClinicId = $this->firstPositiveHeaderInt($request, [
            'X-Clinic-Id',
            'X-Vet-Id',
            'X-Vet-Registeration-Id',
            'X-Vet-Registerations-Temp-Id',
        ]);
        if ($headerClinicId) {
            return $headerClinicId;
        }

        $headerUserId = $this->firstPositiveHeaderInt($request, [
            'X-User-Id',
            'X-Acting-User',
            'X-Session-User',
        ]);
        if ($headerUserId) {
            return $this->resolveClinicIdFromUserIdentifier($headerUserId);
        }

        $role = session('role')
            ?? data_get(session('user'), 'role')
            ?? data_get(session('auth_full'), 'role');

        if ($role === 'doctor' || $role === 'receptionist') {
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

        $sessionClinicId = $this->parsePositiveInt(
            session('clinic_id')
                ?? data_get(session('user'), 'clinic_id')
                ?? data_get(session('auth_full'), 'clinic_id')
                ?? data_get(session('auth_full'), 'user.clinic_id')
                ?? data_get(session('auth_full'), 'vet_registeration_id')
                ?? data_get(session('auth_full'), 'vet_registerations_temp_id')
        );
        if ($sessionClinicId) {
            return $sessionClinicId;
        }

        $sessionId = session('user_id') ?? data_get(session('user'), 'id');
        $sessionUserId = $this->parsePositiveInt($sessionId);
        if ($sessionUserId) {
            return $this->resolveClinicIdFromUserIdentifier($sessionUserId);
        }

        return null;
    }

    private function resolveClinicIdFromUserIdentifier(int $userId): int
    {
        if (Schema::hasTable('vet_registerations_temp')) {
            $clinicExists = DB::table('vet_registerations_temp')->where('id', $userId)->exists();
            if ($clinicExists) {
                return $userId;
            }

            if (Schema::hasColumn('vet_registerations_temp', 'owner_user_id')) {
                $ownerMatch = DB::table('vet_registerations_temp')
                    ->select('id')
                    ->where('owner_user_id', $userId)
                    ->orderBy('id')
                    ->first();
                if ($ownerMatch) {
                    return (int) $ownerMatch->id;
                }
            }
        }

        if (Schema::hasTable('doctors') && Schema::hasColumn('doctors', 'vet_registeration_id')) {
            $doctorClinic = DB::table('doctors')
                ->where('id', $userId)
                ->value('vet_registeration_id');
            $doctorClinicId = $this->parsePositiveInt($doctorClinic);
            if ($doctorClinicId) {
                return $doctorClinicId;
            }
        }

        if (Schema::hasTable('receptionists') && Schema::hasColumn('receptionists', 'vet_registeration_id')) {
            $receptionistClinic = DB::table('receptionists')
                ->where('id', $userId)
                ->value('vet_registeration_id');
            $receptionistClinicId = $this->parsePositiveInt($receptionistClinic);
            if ($receptionistClinicId) {
                return $receptionistClinicId;
            }
        }

        return $userId;
    }

    private function normalizedDefaultPresetNames(): array
    {
        return array_map(static function ($presetName) {
            return strtolower(trim((string) $presetName));
        }, self::DEFAULT_PRESETS);
    }

    private function firstPositiveInt(Request $request, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = $request->input($key);
            $parsed = $this->parsePositiveInt($value);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    private function firstPositiveHeaderInt(Request $request, array $headerNames): ?int
    {
        foreach ($headerNames as $headerName) {
            $parsed = $this->parsePositiveInt($request->header($headerName));
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    private function parsePositiveInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || !preg_match('/^\d+$/', $trimmed)) {
                return null;
            }

            $number = (int) $trimmed;
            return $number > 0 ? $number : null;
        }

        if (is_float($value)) {
            $number = (int) $value;
            return ($number > 0 && (float) $number === $value) ? $number : null;
        }

        return null;
    }
}
