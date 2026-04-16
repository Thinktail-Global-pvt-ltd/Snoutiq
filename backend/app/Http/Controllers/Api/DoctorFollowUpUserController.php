<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DoctorFollowUpUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'integer', 'min:1'],
        ]);

        if (!$this->hasRequiredColumns()) {
            return response()->json([
                'success' => true,
                'count' => 0,
                'data' => [],
            ]);
        }

        $latestFollowUpPrescriptionByUser = Prescription::query()
            ->selectRaw('MAX(id) as latest_prescription_id, user_id')
            ->whereNotNull('follow_up_date')
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $rows = User::query()
            ->select($this->selectColumns())
            ->joinSub($latestFollowUpPrescriptionByUser, 'latest_follow_up_prescriptions', function ($join): void {
                $join->on('latest_follow_up_prescriptions.user_id', '=', 'users.id');
            })
            ->join('prescriptions as p', 'p.id', '=', 'latest_follow_up_prescriptions.latest_prescription_id')
            ->where('users.last_vet_id', (int) $validated['doctor_id'])
            ->orderBy('p.follow_up_date')
            ->orderByDesc('p.id')
            ->get();

        $data = $rows->map(fn (User $user) => $this->formatRow($user))->values();

        return response()->json([
            'success' => true,
            'count' => $data->count(),
            'data' => $data,
        ]);
    }

    private function hasRequiredColumns(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'last_vet_id')
            && Schema::hasTable('prescriptions')
            && Schema::hasColumn('prescriptions', 'id')
            && Schema::hasColumn('prescriptions', 'user_id')
            && Schema::hasColumn('prescriptions', 'follow_up_date');
    }

    private function selectColumns(): array
    {
        $columns = ['users.*', 'p.id as follow_up_prescription_id'];

        foreach ([
            'medical_record_id',
            'doctor_id',
            'pet_id',
            'follow_up_required',
            'follow_up_date',
            'follow_up_type',
            'follow_up_notes',
            'created_at',
            'updated_at',
        ] as $column) {
            if (Schema::hasColumn('prescriptions', $column)) {
                $columns[] = "p.{$column} as follow_up_prescription_{$column}";
            }
        }

        return $columns;
    }

    private function formatRow(User $user): array
    {
        $row = $user->toArray();
        $prescriptionKeys = array_filter(
            array_keys($row),
            fn (string $key) => str_starts_with($key, 'follow_up_prescription_')
        );

        $prescription = [
            'id' => $row['follow_up_prescription_id'] ?? null,
        ];

        foreach ($prescriptionKeys as $key) {
            $field = substr($key, strlen('follow_up_prescription_'));
            if ($field !== 'id') {
                $prescription[$field] = $row[$key];
            }
            unset($row[$key]);
        }

        $row['follow_up_prescription'] = $prescription;

        return $row;
    }
}
