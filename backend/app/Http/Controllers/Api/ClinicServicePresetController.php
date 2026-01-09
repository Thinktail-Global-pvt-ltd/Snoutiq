<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicServicePreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClinicServicePresetController extends Controller
{
    public function index(Request $request)
    {
        $clinicId = $this->resolveClinicId($request);
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'message' => 'clinic_id missing',
            ], 422);
        }

        $presets = ClinicServicePreset::query()
            ->where('clinic_id', $clinicId)
            ->orderBy('name')
            ->get(['id', 'clinic_id', 'name']);

        return response()->json([
            'status' => true,
            'data' => $presets,
        ]);
    }

    public function store(Request $request)
    {
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
        $direct = $request->input('clinic_id')
            ?? $request->query('clinic_id')
            ?? $request->input('user_id')
            ?? $request->query('user_id');
        if ($direct !== null && $direct !== '') {
            return (int) $direct;
        }

        $slug = $request->query('vet_slug')
            ?? $request->query('clinic_slug')
            ?? $request->input('vet_slug')
            ?? $request->input('clinic_slug');

        if ($slug) {
            $row = DB::table('vet_registerations_temp')
                ->select('id')
                ->whereRaw('LOWER(slug) = ?', [strtolower($slug)])
                ->first();
            if ($row) {
                return (int) $row->id;
            }
            return null;
        }

        $header = $request->header('X-User-Id')
            ?? $request->header('X-Acting-User')
            ?? $request->header('X-Session-User');
        if ($header) {
            return (int) $header;
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

        $sessionId = session('user_id') ?? data_get(session('user'), 'id');
        return $sessionId ? (int) $sessionId : null;
    }
}
