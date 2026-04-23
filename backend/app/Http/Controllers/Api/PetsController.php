<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetsController extends Controller
{
    public function storeForUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'pet_name' => ['required', 'string', 'max:120'],
            'pet_type' => ['nullable', 'string', 'max:120'],
            'pet_breed' => ['nullable', 'string', 'max:120'],
            'breed' => ['nullable', 'string', 'max:120'],
            'pet_gender' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'max:50'],
            'pet_age' => ['nullable', 'integer', 'min:0', 'max:255'],
            'pet_age_months' => ['nullable', 'integer', 'min:0', 'max:255'],
            'pet_dob' => ['nullable', 'date'],
            'dob' => ['nullable', 'date'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (!Schema::hasTable('pets')) {
            return response()->json([
                'success' => false,
                'message' => 'pets table is not available.',
            ], 500);
        }

        $userRefColumn = Schema::hasColumn('pets', 'user_id')
            ? 'user_id'
            : (Schema::hasColumn('pets', 'owner_id') ? 'owner_id' : null);

        if (!$userRefColumn) {
            return response()->json([
                'success' => false,
                'message' => 'pets table is missing user reference column.',
            ], 500);
        }

        $result = DB::transaction(function () use ($data, $userRefColumn) {
            $user = User::query()->findOrFail((int) $data['user_id']);
            $user->name = trim((string) $data['name']);
            $user->save();

            $petPayload = [
                $userRefColumn => $user->id,
            ];

            $this->setPetColumn($petPayload, 'name', $data['pet_name']);
            $this->setPetColumn($petPayload, 'breed', $data['pet_breed'] ?? $data['breed'] ?? null);
            $this->setPetColumn($petPayload, 'pet_age', $data['pet_age'] ?? null);
            $this->setPetColumn($petPayload, 'pet_age_months', $data['pet_age_months'] ?? null);
            $this->setPetColumn($petPayload, 'weight', $data['weight'] ?? null);

            $petType = $data['pet_type'] ?? null;
            $this->setPetColumn($petPayload, 'pet_type', $petType);
            $this->setPetColumn($petPayload, 'type', $petType);

            $petGender = $data['pet_gender'] ?? $data['gender'] ?? null;
            $this->setPetColumn($petPayload, 'pet_gender', $petGender);
            $this->setPetColumn($petPayload, 'gender', $petGender);

            $petDob = $data['pet_dob'] ?? $data['dob'] ?? null;
            $this->setPetColumn($petPayload, 'pet_dob', $petDob);
            $this->setPetColumn($petPayload, 'dob', $petDob);

            if (Schema::hasColumn('pets', 'created_at')) {
                $petPayload['created_at'] = now();
            }
            if (Schema::hasColumn('pets', 'updated_at')) {
                $petPayload['updated_at'] = now();
            }

            $petId = (int) DB::table('pets')->insertGetId($petPayload);
            $pet = Pet::query()->find($petId);

            return [
                'user' => $user->fresh(),
                'pet' => $pet,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $result['user'],
                'pet' => $result['pet'],
            ],
        ], 201);
    }

    public function updateReportedSymptom(Request $request, int $userId, int $petId): JsonResponse
    {
        $data = $request->validate([
            'reported_symptom' => ['required', 'string', 'max:5000'],
        ]);

        if (!Schema::hasTable('pets')) {
            return response()->json([
                'success' => false,
                'message' => 'pets table is not available.',
            ], 500);
        }

        $userRefColumn = Schema::hasColumn('pets', 'user_id')
            ? 'user_id'
            : (Schema::hasColumn('pets', 'owner_id') ? 'owner_id' : null);

        if (!$userRefColumn) {
            return response()->json([
                'success' => false,
                'message' => 'pets table is missing user reference column.',
            ], 500);
        }

        $reportedSymptom = trim((string) $data['reported_symptom']);
        if ($reportedSymptom === '') {
            return response()->json([
                'success' => false,
                'message' => 'reported_symptom is required.',
            ], 422);
        }

        $pet = Pet::query()
            ->whereKey($petId)
            ->where($userRefColumn, $userId)
            ->first();

        if (!$pet) {
            return response()->json([
                'success' => false,
                'message' => 'Pet not found for the provided user.',
            ], 404);
        }

        $pet->reported_symptom = $reportedSymptom;
        $pet->save();

        return response()->json([
            'success' => true,
            'message' => 'Reported symptom saved successfully.',
            'data' => [
                'user_id' => $userId,
                'pet' => [
                    'id' => $pet->id,
                    'user_id' => $userId,
                    'reported_symptom' => $pet->reported_symptom,
                    'updated_at' => optional($pet->updated_at)->toDateTimeString(),
                ],
            ],
        ]);
    }

    // GET /api/users/{id}/pets
    public function byUser(string $id)
    {
        $pets = DB::table('user_pets')
            ->where('user_id', (int) $id)
            ->select('id','name','type','breed')
            ->orderBy('id','desc')
            ->get();
        return response()->json(['pets' => $pets]);
    }

    private function setPetColumn(array &$payload, string $column, $value): void
    {
        if (!Schema::hasColumn('pets', $column)) {
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $payload[$column] = $value;
    }
}
