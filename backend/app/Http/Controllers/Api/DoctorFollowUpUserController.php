<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Pet;
use App\Models\Prescription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DoctorFollowUpUserController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'integer', 'min:1'],
        ]);

        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'last_vet_id')) {
            return response()->json([
                'success' => true,
                'count' => 0,
                'data' => [],
            ]);
        }

        $users = User::query()
            ->where('last_vet_id', (int) $validated['doctor_id'])
            ->orderByDesc('id')
            ->get();
        $petsByUserId = $this->petsByUserId($users->pluck('id')->all());
        $data = $users->map(function (User $user) use ($petsByUserId): array {
            $row = $user->toArray();
            $row['pets'] = $petsByUserId[(int) $user->id] ?? [];

            return $row;
        })->values();

        return response()->json([
            'success' => true,
            'count' => $data->count(),
            'data' => $data,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'integer', 'min:1'],
        ]);
        $doctorId = (int) $validated['doctor_id'];

        if (!$this->hasRequiredColumns()) {
            return response()->json([
                'success' => true,
                'count' => 0,
                'matched_user_count' => 0,
                'vet_registeration_id' => null,
                'data' => [],
            ]);
        }

        $vetRegistrationId = $this->resolveVetRegistrationId($doctorId);
        if ($vetRegistrationId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found or clinic not linked.',
            ], 404);
        }

        $matchedUserIds = User::query()
            ->where('last_vet_id', $vetRegistrationId)
            ->orderByDesc('id')
            ->pluck('id');

        if ($matchedUserIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'count' => 0,
                'matched_user_count' => 0,
                'vet_registeration_id' => $vetRegistrationId,
                'total_earnings_sum' => $this->totalEarningsSum($doctorId),
                'data' => [],
            ]);
        }

        $latestFollowUpPrescriptionByUser = Prescription::query()
            ->selectRaw('MAX(id) as latest_prescription_id, user_id')
            ->whereNotNull('follow_up_date')
            ->whereNotNull('user_id')
            ->where('doctor_id', $doctorId)
            ->whereIn('user_id', $matchedUserIds->all())
            ->groupBy('user_id');

        $rows = User::query()
            ->select($this->selectColumns())
            ->joinSub($latestFollowUpPrescriptionByUser, 'latest_follow_up_prescriptions', function ($join): void {
                $join->on('latest_follow_up_prescriptions.user_id', '=', 'users.id');
            })
            ->join('prescriptions as p', 'p.id', '=', 'latest_follow_up_prescriptions.latest_prescription_id')
            ->orderBy('p.follow_up_date')
            ->orderByDesc('p.id')
            ->get();

        $data = $rows->map(fn (User $user) => $this->formatRow($user))->values();

        return response()->json([
            'success' => true,
            'count' => $data->count(),
            'matched_user_count' => $matchedUserIds->count(),
            'vet_registeration_id' => $vetRegistrationId,
            'total_earnings_sum' => $this->totalEarningsSum($doctorId),
            'data' => $data,
        ]);
    }

    private function hasRequiredColumns(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'last_vet_id')
            && Schema::hasTable('doctors')
            && Schema::hasColumn('doctors', 'vet_registeration_id')
            && Schema::hasTable('prescriptions')
            && Schema::hasColumn('prescriptions', 'id')
            && Schema::hasColumn('prescriptions', 'doctor_id')
            && Schema::hasColumn('prescriptions', 'user_id')
            && Schema::hasColumn('prescriptions', 'follow_up_date');
    }

    private function resolveVetRegistrationId(int $doctorId): ?int
    {
        if ($doctorId <= 0) {
            return null;
        }

        $doctor = Doctor::query()
            ->select(['id', 'vet_registeration_id'])
            ->find($doctorId);

        if (!$doctor || !is_numeric($doctor->vet_registeration_id ?? null)) {
            return null;
        }

        $vetRegistrationId = (int) $doctor->vet_registeration_id;

        return $vetRegistrationId > 0 ? $vetRegistrationId : null;
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

    private function petsByUserId(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(
            array_map('intval', $userIds),
            fn (int $id) => $id > 0
        )));

        if (
            empty($userIds)
            || !Schema::hasTable('pets')
            || !Schema::hasColumn('pets', 'user_id')
        ) {
            return [];
        }

        return Pet::query()
            ->whereIn('user_id', $userIds)
            ->orderBy('name')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($pets) => $pets->map(fn (Pet $pet) => $pet->toArray())->values()->all())
            ->all();
    }

    private function totalEarningsSum(int $doctorId): float|int
    {
        if (
            !Schema::hasTable('transactions')
            || !Schema::hasColumn('transactions', 'doctor_id')
        ) {
            return 0;
        }

        $query = Transaction::query()->where('doctor_id', $doctorId);

        if (Schema::hasColumn('transactions', 'amount')) {
            return (float) $query->sum('amount');
        }

        if (Schema::hasColumn('transactions', 'amount_paise')) {
            return round(((int) $query->sum('amount_paise')) / 100, 2);
        }

        return 0;
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
