<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Services\PetDiseaseInferenceService;
use Carbon\Carbon;

class AdminController extends Controller
{

    // list all users
    public function getUsers(Request $request)
    {
        $rows = DB::select('SELECT * FROM users ORDER BY id DESC');
        return response()->json(['status'=>'success','data'=>$rows]);
    }

    // get one user
    public function getUser(Request $request, $id)
    {
        $row = DB::select('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        if (!$row) return response()->json(['status'=>'error','message'=>'User not found'], 404);
        return response()->json(['status'=>'success','data'=>$row[0]]);
    }

    public function updateUser(Request $request, $id)
{
    try {
        $allowed = ['name','phone','role','summary','latitude','longitude','password'];
        $sets = [];
        $params = [];

        foreach ($allowed as $col) {
            if ($request->has($col)) {
                $val = $request->input($col);
                if ($col === 'password' && !empty($val)) {
                    $val = Hash::make($val);
                }
                $sets[] = "`$col` = ?";
                $params[] = $val;
            }
        }

        if (empty($sets)) {
            return response()->json(['status'=>'error','message'=>'No fields to update'], 422);
        }

        $sql = 'UPDATE users SET '.implode(',', $sets).', updated_at = NOW() WHERE id = ?';
        $params[] = $id;

        $affected = DB::update($sql, $params);

        if ($affected === 0) {
            return response()->json(['status'=>'error','message'=>'Nothing updated or user not found'], 404);
        }

        $row = DB::select('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        return response()->json(['status'=>'success','data'=>$row[0]]);

    } catch (\Throwable $e) {
        // Debug: Return the actual error in response
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine()
        ], 500);
    }
}


    // update user (dynamic SET)
    public function updateUser_old(Request $request, $id)
    {
        $allowed = ['name','phone','role','summary','latitude','longitude','password'];
        $sets = [];
        $params = [];

        foreach ($allowed as $col) {
            if ($request->has($col)) {
                $val = $request->input($col);
                if ($col === 'password') {
                    if ($val === null || $val === '') continue;
                    $val = Hash::make($val);
                }
                $sets[] = "`$col` = ?";
                $params[] = $val;
            }
        }
        if (empty($sets)) {
            return response()->json(['status'=>'error','message'=>'No fields to update'], 422);
        }

        $sql = 'UPDATE users SET '.implode(',', $sets).', updated_at = NOW() WHERE id = ?';
        $params[] = $id;

        $affected = DB::update($sql, $params);
        if ($affected === 0) return response()->json(['status'=>'error','message'=>'Nothing updated or user not found'], 404);

        $row = DB::select('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
        return response()->json(['status'=>'success','data'=>$row[0]]);
    }

    // delete user (pets cascade by FK)
    public function deleteUser(Request $request, $id)
    {
        $deleted = DB::delete('DELETE FROM users WHERE id = ?', [$id]);
        if (!$deleted) return response()->json(['status'=>'error','message'=>'User not found'], 404);
        return response()->json(['status'=>'success','message'=>'User deleted']);
    }

    /**
     * Delete user(s) by phone query parameter.
     * Example: DELETE /api/users/by-phone?phone=9999999999
     */
    public function deleteUserByPhone(Request $request)
    {
        $phone = trim((string) $request->query('phone', ''));
        if ($phone === '') {
            return response()->json(['status' => 'error', 'message' => 'phone is required'], 422);
        }

        $deleted = DB::delete('DELETE FROM users WHERE phone = ?', [$phone]);

        if (!$deleted) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted',
            'deleted' => $deleted,
        ]);
    }

    /* ============== VETS (raw SQL) ============== */

    public function getVets(Request $request)
    {
        $vets = DB::select('SELECT * FROM vet_registerations_temp ORDER BY id DESC');
        return response()->json(['status'=>'success','data'=>$vets]);
    }

    /* ============== PETS (raw SQL) ============== */

    // list pets for a user
    public function listPets(Request $request, $userId)
    {
        $pets = DB::table('pets')
            ->select($this->safePetSelectColumns())
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get()
            ->map(function ($pet) {
                $pet->pet_doc2_blob_url = $this->petDoc2BlobUrl((int) $pet->id);
                return $pet;
            })
            ->values();

        return response()->json(['status' => 'success', 'data' => $pets]);
    }

    // get one pet (for edit)
    public function getPet(Request $request, $petId)
    {
        $pet = DB::table('pets')
            ->select($this->safePetSelectColumns())
            ->where('id', $petId)
            ->first();

        if (! $pet) {
            return response()->json(['status' => 'error', 'message' => 'Pet not found'], 404);
        }

        $pet->pet_doc2_blob_url = $this->petDoc2BlobUrl((int) $pet->id);

        return response()->json(['status' => 'success', 'data' => $pet]);
    }

    /**
     * ADD PET:
     * Supports both legacy pet fields and register-style payload keys.
     */
    public function addPet(Request $request, $userId)
    {
        if (! Schema::hasTable('pets')) {
            return response()->json(['status' => 'error', 'message' => 'pets table not found'], 500);
        }

        $effectiveUserId = $this->resolvePetUserId($request, $userId);
        if ($effectiveUserId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'user_id is required'], 422);
        }

        $petName = $this->firstNonEmptyInput($request, ['pet_name', 'name']);
        $breed = $request->input('breed');
        $petGender = $request->input('pet_gender');
        $petAgeRaw = $request->input('pet_age');

        if ($petName === null || $petName === '') {
            return response()->json(['status' => 'error', 'message' => 'pet_name or name is required'], 422);
        }
        if ($breed === null || $breed === '') {
            return response()->json(['status' => 'error', 'message' => 'breed is required'], 422);
        }
        if ($petGender === null || $petGender === '') {
            return response()->json(['status' => 'error', 'message' => 'pet_gender is required'], 422);
        }
        if ($petAgeRaw === null || $petAgeRaw === '') {
            return response()->json(['status' => 'error', 'message' => 'pet_age is required'], 422);
        }

        $petAge = (int) $petAgeRaw;
        $petType = $request->filled('pet_type') ? $request->input('pet_type') : null;
        $petDob = $request->has('pet_dob') ? $this->normalizeDateInput($request->input('pet_dob')) : null;
        $microchipNumber = $request->input('microchip_number');
        $mcdRegistration = $request->input('mcd_registration_number');
        $weightRaw = $request->filled('pet_weight') ? $request->input('pet_weight') : $request->input('weight');
        $weight = ($weightRaw !== null && $weightRaw !== '' && is_numeric($weightRaw)) ? (float) $weightRaw : null;

        $isNeutered = $this->normalizeNullableBoolInt($request->input('is_nuetered', $request->input('is_neutered')));
        $dewormingYesNo = $this->normalizeNullableBoolInt($request->input('deworming_yes_no', $request->input('deworming')));
        $lastDewormingDate = $this->normalizeDateInput($request->input('last_deworming_date'));

        $blobSourceField = $request->hasFile('pet_doc2') ? 'pet_doc2' : ($request->hasFile('pet_doc1') ? 'pet_doc1' : null);
        [$petDocBlob, $petDocMime] = $blobSourceField ? $this->extractPetDocumentBlob($request, $blobSourceField) : [null, null];
        $blobColumnsReady = $this->petDoc2BlobColumnsReady();
        $uploadedDoc1 = $this->storePetDocument($request, 'pet_doc1');
        $uploadedDoc2 = $this->storePetDocument($request, 'pet_doc2');

        $petDoc1 = $uploadedDoc1 ?? $request->input('pet_doc1');
        $petDoc2 = $uploadedDoc2 ?? $request->input('pet_doc2');
        if (($petDoc2 === null || $petDoc2 === '') && ($petDoc1 !== null && $petDoc1 !== '')) {
            $petDoc2 = $petDoc1;
        }

        return DB::transaction(function () use (
            $request,
            $effectiveUserId,
            $petName,
            $breed,
            $petAge,
            $petGender,
            $petType,
            $petDob,
            $microchipNumber,
            $mcdRegistration,
            $weight,
            $isNeutered,
            $dewormingYesNo,
            $lastDewormingDate,
            $petDoc1,
            $petDoc2,
            $blobColumnsReady,
            $petDocBlob,
            $petDocMime
        ) {
            $petColumns = $this->tableColumns('pets');
            $petPayload = [];

            if (isset($petColumns['user_id'])) {
                $petPayload['user_id'] = $effectiveUserId;
            } elseif (isset($petColumns['owner_id'])) {
                $petPayload['owner_id'] = $effectiveUserId;
            }

            $this->setColumnValue($petPayload, $petColumns, 'name', $petName, true);
            $this->setColumnValue($petPayload, $petColumns, 'breed', $breed, true);
            $this->setColumnValue($petPayload, $petColumns, 'pet_age', $petAge, true);
            $this->setColumnValue($petPayload, $petColumns, 'pet_gender', $petGender, true);
            $this->setColumnValue($petPayload, $petColumns, 'pet_type', $petType);
            $this->setColumnValue($petPayload, $petColumns, 'type', $petType);
            $this->setColumnValue($petPayload, $petColumns, 'pet_dob', $petDob);
            $this->setColumnValue($petPayload, $petColumns, 'dob', $petDob);
            $this->setColumnValue($petPayload, $petColumns, 'microchip_number', $microchipNumber);
            $this->setColumnValue($petPayload, $petColumns, 'mcd_registration_number', $mcdRegistration);
            $this->setColumnValue($petPayload, $petColumns, 'weight', $weight);
            $this->setColumnValue($petPayload, $petColumns, 'pet_doc1', $petDoc1);
            $this->setColumnValue($petPayload, $petColumns, 'pet_doc2', $petDoc2);
            $this->setColumnValue($petPayload, $petColumns, 'deworming_yes_no', $dewormingYesNo);
            $this->setColumnValue($petPayload, $petColumns, 'last_deworming_date', $lastDewormingDate);
            $this->setColumnValue($petPayload, $petColumns, 'role', $request->input('role'));

            if ($isNeutered !== null) {
                if (isset($petColumns['is_nuetered'])) {
                    $petPayload['is_nuetered'] = $this->neuteredValueForPetColumn('is_nuetered', $isNeutered);
                }
                if (isset($petColumns['is_neutered'])) {
                    $petPayload['is_neutered'] = $this->neuteredValueForPetColumn('is_neutered', $isNeutered);
                }
            }

            $existingPetId = null;
            $hasUserKey = isset($petColumns['user_id']) || isset($petColumns['owner_id']);
            $canLookupDuplicate = $hasUserKey
                && isset($petColumns['name'], $petColumns['breed'], $petColumns['pet_age'], $petColumns['pet_gender']);

            if ($canLookupDuplicate) {
                $duplicateQuery = DB::table('pets');
                if (isset($petColumns['user_id'])) {
                    $duplicateQuery->where('user_id', $effectiveUserId);
                } else {
                    $duplicateQuery->where('owner_id', $effectiveUserId);
                }
                $duplicateQuery
                    ->where('name', $petName)
                    ->where('breed', $breed)
                    ->where('pet_age', $petAge)
                    ->where('pet_gender', $petGender);

                $existingPetId = $duplicateQuery->orderByDesc('id')->value('id');
            }

            if ($existingPetId) {
                if (isset($petColumns['updated_at'])) {
                    $petPayload['updated_at'] = now();
                }
                DB::table('pets')->where('id', (int) $existingPetId)->update($petPayload);
                $petId = (int) $existingPetId;
            } else {
                if (isset($petColumns['created_at'])) {
                    $petPayload['created_at'] = now();
                }
                if (isset($petColumns['updated_at'])) {
                    $petPayload['updated_at'] = now();
                }
                $petId = (int) DB::table('pets')->insertGetId($petPayload);
            }

            if ($blobColumnsReady && $petDocBlob !== null) {
                DB::table('pets')->where('id', $petId)->update([
                    'pet_doc2_blob' => $petDocBlob,
                    'pet_doc2_mime' => $petDocMime,
                    'updated_at' => now(),
                ]);
            }

            $this->syncUserFromRegisterPetFields($effectiveUserId, [
                'pet_owner_name' => $request->input('pet_owner_name'),
                'pet_name' => $petName,
                'pet_gender' => $petGender,
                'pet_age' => $petAge,
                'breed' => $breed,
                'role' => $request->input('role'),
                'pet_doc1' => $petDoc1,
                'pet_doc2' => $petDoc2,
            ]);

            $pet = DB::table('pets')
                ->select($this->safePetSelectColumns())
                ->where('id', $petId)
                ->first();

            if ($pet) {
                $pet->pet_doc2_blob_url = $this->petDoc2BlobUrl((int) $pet->id);
            }

            return response()->json(['status' => 'success', 'data' => $pet]);
        });
    }

    // update pet
    public function updatePet(Request $request, $petId)
    {
        if (! Schema::hasTable('pets')) {
            return response()->json(['status' => 'error', 'message' => 'pets table not found'], 500);
        }

        $pet = DB::table('pets')
            ->select($this->safePetSelectColumns())
            ->where('id', $petId)
            ->first();

        if (! $pet) {
            return response()->json(['status' => 'error', 'message' => 'Pet not found'], 404);
        }

        $petColumns = $this->tableColumns('pets');
        $updatePayload = [];

        if ($request->has('pet_name') || $request->has('name')) {
            $petName = $this->firstNonEmptyInput($request, ['pet_name', 'name']);
            if ($petName !== null) {
                $this->setColumnValue($updatePayload, $petColumns, 'name', $petName, true);
            }
        }
        if ($request->has('breed')) {
            $this->setColumnValue($updatePayload, $petColumns, 'breed', $request->input('breed'));
        }
        if ($request->has('pet_age')) {
            $petAgeRaw = $request->input('pet_age');
            if ($petAgeRaw !== null && $petAgeRaw !== '') {
                $this->setColumnValue($updatePayload, $petColumns, 'pet_age', (int) $petAgeRaw, true);
            }
        }
        if ($request->has('pet_gender')) {
            $this->setColumnValue($updatePayload, $petColumns, 'pet_gender', $request->input('pet_gender'));
        }
        if ($request->has('pet_type')) {
            $petType = $request->input('pet_type');
            $this->setColumnValue($updatePayload, $petColumns, 'pet_type', $petType);
            $this->setColumnValue($updatePayload, $petColumns, 'type', $petType);
        }
        if ($request->has('pet_dob')) {
            $petDobRaw = $request->input('pet_dob');
            $petDob = $this->normalizeDateInput($petDobRaw);
            if ($petDob !== null || $petDobRaw === null || trim((string) $petDobRaw) === '') {
                $this->setColumnValue($updatePayload, $petColumns, 'pet_dob', $petDob, true);
                $this->setColumnValue($updatePayload, $petColumns, 'dob', $petDob, true);
            }
        }
        if ($request->has('microchip_number')) {
            $this->setColumnValue($updatePayload, $petColumns, 'microchip_number', $request->input('microchip_number'));
        }
        if ($request->has('mcd_registration_number')) {
            $this->setColumnValue($updatePayload, $petColumns, 'mcd_registration_number', $request->input('mcd_registration_number'));
        }
        if ($request->has('weight') || $request->has('pet_weight')) {
            $weightRaw = $request->has('pet_weight') ? $request->input('pet_weight') : $request->input('weight');
            if ($weightRaw === null || trim((string) $weightRaw) === '') {
                $this->setColumnValue($updatePayload, $petColumns, 'weight', null, true);
            } elseif (is_numeric($weightRaw)) {
                $this->setColumnValue($updatePayload, $petColumns, 'weight', (float) $weightRaw, true);
            }
        }
        if ($request->has('deworming_yes_no') || $request->has('deworming')) {
            $dewormingYesNo = $this->normalizeNullableBoolInt($request->input('deworming_yes_no', $request->input('deworming')));
            if ($dewormingYesNo !== null) {
                $this->setColumnValue($updatePayload, $petColumns, 'deworming_yes_no', $dewormingYesNo, true);
            }
        }
        if ($request->has('last_deworming_date')) {
            $lastDewormingRaw = $request->input('last_deworming_date');
            $lastDewormingDate = $this->normalizeDateInput($lastDewormingRaw);
            if ($lastDewormingDate !== null || $lastDewormingRaw === null || trim((string) $lastDewormingRaw) === '') {
                $this->setColumnValue($updatePayload, $petColumns, 'last_deworming_date', $lastDewormingDate, true);
            }
        }

        if ($request->has('is_nuetered') || $request->has('is_neutered')) {
            $isNeutered = $this->normalizeNullableBoolInt($request->input('is_nuetered', $request->input('is_neutered')));
            if ($isNeutered !== null) {
                if (isset($petColumns['is_nuetered'])) {
                    $updatePayload['is_nuetered'] = $this->neuteredValueForPetColumn('is_nuetered', $isNeutered);
                }
                if (isset($petColumns['is_neutered'])) {
                    $updatePayload['is_neutered'] = $this->neuteredValueForPetColumn('is_neutered', $isNeutered);
                }
            }
        }

        if ($request->has('role')) {
            $this->setColumnValue($updatePayload, $petColumns, 'role', $request->input('role'));
        }

        $blobSourceField = $request->hasFile('pet_doc2') ? 'pet_doc2' : ($request->hasFile('pet_doc1') ? 'pet_doc1' : null);
        [$petDocBlob, $petDocMime] = $blobSourceField ? $this->extractPetDocumentBlob($request, $blobSourceField) : [null, null];
        $doc1Upload = $this->storePetDocument($request, 'pet_doc1');
        $doc2Upload = $this->storePetDocument($request, 'pet_doc2');

        $petDoc1Value = null;
        $petDoc2Value = null;

        if ($doc1Upload) {
            $petDoc1Value = $doc1Upload;
        } elseif ($request->has('pet_doc1')) {
            $petDoc1Value = $request->input('pet_doc1');
        }

        if ($doc2Upload) {
            $petDoc2Value = $doc2Upload;
        } elseif ($request->has('pet_doc2')) {
            $petDoc2Value = $request->input('pet_doc2');
        } elseif ($petDoc1Value !== null && $petDoc1Value !== '') {
            $petDoc2Value = $petDoc1Value;
        }

        if ($petDoc1Value !== null) {
            $this->setColumnValue($updatePayload, $petColumns, 'pet_doc1', $petDoc1Value, true);
        }
        if ($petDoc2Value !== null) {
            $this->setColumnValue($updatePayload, $petColumns, 'pet_doc2', $petDoc2Value, true);
        }
        if ($this->petDoc2BlobColumnsReady() && $petDocBlob !== null) {
            $updatePayload['pet_doc2_blob'] = $petDocBlob;
            $updatePayload['pet_doc2_mime'] = $petDocMime;
        }

        $userIdFromPet = isset($pet->user_id) ? (int) $pet->user_id : 0;
        if ($userIdFromPet <= 0 && isset($pet->owner_id)) {
            $userIdFromPet = (int) $pet->owner_id;
        }
        $effectiveUserId = $this->resolvePetUserId($request, $userIdFromPet);

        $userUpdated = false;
        if ($effectiveUserId > 0) {
            $userUpdated = $this->syncUserFromRegisterPetFields($effectiveUserId, [
                'pet_owner_name' => $request->input('pet_owner_name'),
                'pet_name' => $this->firstNonEmptyInput($request, ['pet_name', 'name']),
                'pet_gender' => $request->input('pet_gender'),
                'pet_age' => $request->input('pet_age'),
                'breed' => $request->input('breed'),
                'role' => $request->input('role'),
                'pet_doc1' => $petDoc1Value,
                'pet_doc2' => $petDoc2Value,
            ]);
        }

        if (!$updatePayload && ! $userUpdated) {
            return response()->json(['status' => 'error', 'message' => 'No fields to update'], 422);
        }

        return DB::transaction(function () use ($petId, $updatePayload) {
            if ($updatePayload) {
                if (Schema::hasColumn('pets', 'updated_at')) {
                    $updatePayload['updated_at'] = now();
                }
                DB::table('pets')->where('id', $petId)->update($updatePayload);
            }

            $updatedPet = DB::table('pets')
                ->select($this->safePetSelectColumns())
                ->where('id', $petId)
                ->first();

            if ($updatedPet) {
                $updatedPet->pet_doc2_blob_url = $this->petDoc2BlobUrl((int) $updatedPet->id);
            }

            return response()->json(['status' => 'success', 'data' => $updatedPet]);
        });
    }

    // delete pet
    public function deletePet(Request $request, $petId)
    {
        $deleted = DB::delete('DELETE FROM pets WHERE id = ?', [$petId]);
        if (!$deleted) return response()->json(['status'=>'error','message'=>'Pet not found'], 404);
        return response()->json(['status'=>'success','message'=>'Pet deleted']);
    }

    /**
     * AI-assisted disease spell correction/suggestion for dogs.
     * Stores the reported symptom and returns a corrected dog disease name.
     */
    public function suggestDogDisease(Request $request, $petId)
    {
        $payload = $request->validate([
            'symptom' => 'required|string|max:500',
        ]);

        $petRows = DB::select('SELECT id, name, breed, pet_age, pet_gender FROM pets WHERE id = ? LIMIT 1', [$petId]);
        if (!$petRows) {
            return response()->json(['status' => 'error', 'message' => 'Pet not found'], 404);
        }

        $pet = $petRows[0];
        $symptom = trim($payload['symptom']) ?: null;
        $inference = app(PetDiseaseInferenceService::class)->syncFromReportedSymptom(
            petId: (int) $petId,
            reportedSymptom: $symptom,
            contextOverrides: [
                'name' => $pet->name ?? null,
                'breed' => $pet->breed ?? null,
                'pet_age' => $pet->pet_age ?? null,
                'pet_gender' => $pet->pet_gender ?? null,
            ],
            source: 'api.admin.pets.dog-disease'
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'pet_id' => $petId,
                'symptom_saved' => $symptom,
                'suggested_disease' => $inference['suggested_disease'] ?? 'Unknown dog disease',
                'category' => $inference['category'] ?? 'normal',
            ],
        ]);
    }

    /**
     * Build AI summary of a pet using pet fields, uploaded docs, and prescriptions.
     */
    public function summarizePet(Request $request, $petId)
    {
        $petRows = DB::select('SELECT * FROM pets WHERE id = ? LIMIT 1', [$petId]);
        if (!$petRows) {
            return response()->json(['status' => 'error', 'message' => 'Pet not found'], 404);
        }
        $pet = $petRows[0];

        $prescriptions = DB::select(
            'SELECT id, doctor_id, content_html, image_path, visit_notes, exam_notes, diagnosis, treatment_plan, home_care, follow_up_notes, created_at
             FROM prescriptions
             WHERE pet_id = ?
             ORDER BY id DESC
             LIMIT 20',
            [$petId]
        );

        $prompt = $this->buildPetSummaryPrompt($pet, $prescriptions);
        try {
            $summary = $this->callGeminiForSummary($prompt);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not generate summary: '.$e->getMessage(),
            ], 500);
        }

        DB::update(
            'UPDATE pets SET ai_summary = ?, updated_at = NOW() WHERE id = ?',
            [$summary, $petId]
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'pet_id' => $petId,
                'ai_summary' => $summary,
            ],
        ]);
    }
    // Fetch all users
    public function getUsers_old(Request $request)
    {
        if ($request->email !== 'adminsnoutiq@gmail.com') {
            return response()->json(['status' => 'error', 'message' => 'Invalid user'], 403);
        }

        $users = User::all();
        return response()->json(['status' => 'success', 'data' => $users]);
    }

    private function resolvePetUserId(Request $request, $routeUserId): int
    {
        $routeId = is_numeric($routeUserId) ? (int) $routeUserId : 0;
        $bodyId = $request->filled('user_id') && is_numeric($request->input('user_id'))
            ? (int) $request->input('user_id')
            : 0;

        if ($routeId > 0) {
            return $routeId;
        }

        return $bodyId;
    }

    private function firstNonEmptyInput(Request $request, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! $request->has($key)) {
                continue;
            }
            $value = $request->input($key);
            if ($value === null) {
                continue;
            }
            $text = trim((string) $value);
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function normalizeNullableBoolInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 1 : 0;
        }

        $normalized = strtolower(trim((string) $value, " \t\n\r\0\x0B\"'"));
        if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
            return 1;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
            return 0;
        }

        return null;
    }

    private function normalizeDateInput($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function tableColumns(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $columns = Schema::getColumnListing($table);
        return array_fill_keys($columns, true);
    }

    private function setColumnValue(array &$payload, array $columns, string $column, $value, bool $allowNull = false): void
    {
        if (! isset($columns[$column])) {
            return;
        }
        if (! $allowNull && ($value === null || $value === '')) {
            return;
        }
        $payload[$column] = $value;
    }

    private function petColumnUsesEnumStyle(string $column): bool
    {
        if (! Schema::hasTable('pets') || ! Schema::hasColumn('pets', $column)) {
            return false;
        }

        $columnType = null;
        try {
            $columnType = Schema::getColumnType('pets', $column);
        } catch (\Throwable $e) {
            $columnType = null;
        }

        $columnTypeNormalized = strtolower(trim((string) $columnType));
        if (str_contains($columnTypeNormalized, 'enum')
            || in_array($columnTypeNormalized, ['string', 'char', 'varchar'], true)) {
            return true;
        }

        if ($columnTypeNormalized === '' || $columnTypeNormalized === 'unknown') {
            try {
                $columnMeta = DB::selectOne("SHOW COLUMNS FROM `pets` LIKE ?", [$column]);
                $rawType = strtolower((string) ($columnMeta->Type ?? $columnMeta->type ?? ''));
                if (str_contains($rawType, 'enum(')) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Use non-enum fallback when metadata lookup fails.
            }
        }

        return false;
    }

    private function neuteredValueForPetColumn(string $column, int $value)
    {
        if ($this->petColumnUsesEnumStyle($column)) {
            return $value === 1 ? 'Y' : 'N';
        }

        return $value === 1 ? 1 : 0;
    }

    private function syncUserFromRegisterPetFields(int $userId, array $data): bool
    {
        if ($userId <= 0 || ! Schema::hasTable('users')) {
            return false;
        }

        $userColumns = $this->tableColumns('users');
        if (! $userColumns) {
            return false;
        }

        $updates = [];

        if (isset($userColumns['name']) && ! empty($data['pet_owner_name'])) {
            $updates['name'] = trim((string) $data['pet_owner_name']);
        }
        if (isset($userColumns['pet_name']) && ! empty($data['pet_name'])) {
            $updates['pet_name'] = trim((string) $data['pet_name']);
        }
        if (isset($userColumns['pet_gender']) && ! empty($data['pet_gender'])) {
            $updates['pet_gender'] = trim((string) $data['pet_gender']);
        }
        if (isset($userColumns['pet_age']) && ($data['pet_age'] ?? null) !== null && $data['pet_age'] !== '') {
            $updates['pet_age'] = (int) $data['pet_age'];
        }
        if (isset($userColumns['breed']) && ! empty($data['breed'])) {
            $updates['breed'] = trim((string) $data['breed']);
        }
        if (isset($userColumns['role']) && ! empty($data['role'])) {
            $updates['role'] = trim((string) $data['role']);
        }
        if (isset($userColumns['pet_doc1']) && ! empty($data['pet_doc1'])) {
            $updates['pet_doc1'] = trim((string) $data['pet_doc1']);
        }
        if (isset($userColumns['pet_doc2']) && ! empty($data['pet_doc2'])) {
            $updates['pet_doc2'] = trim((string) $data['pet_doc2']);
        }

        if (! $updates) {
            return false;
        }

        if (isset($userColumns['updated_at'])) {
            $updates['updated_at'] = now();
        }

        DB::table('users')->where('id', $userId)->update($updates);
        return true;
    }

    private function normalizeNeuteredFlag($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = strtoupper((string)$value);

        $truthy = ['Y', 'YES', 'TRUE', '1'];
        $falsy  = ['N', 'NO', 'FALSE', '0'];

        if (in_array($value, $truthy, true)) {
            return 'Y';
        }

        if (in_array($value, $falsy, true)) {
            return 'N';
        }

        throw new \InvalidArgumentException('Invalid value for is_neutered. Use Y or N.');
    }

    // Fetch all vets
    public function getVets_old(Request $request)
    {
        if ($request->email !== 'adminsnoutiq@gmail.com') {
            return response()->json(['status' => 'error', 'message' => 'Invalid user'], 403);
        }

        $vets = DB::table('vet_registerations_temp')->get();
        return response()->json(['status' => 'success', 'data' => $vets]);
    }

    private function storePetDocument(Request $request, string $field): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        if (!$file || !$file->isValid()) {
            return null;
        }

        $uploadPath = public_path('uploads/pet_docs');
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true, true);
        }

        $docName = time().'_'.uniqid().'_'.$file->getClientOriginalName();
        $file->move($uploadPath, $docName);

        return 'backend/uploads/pet_docs/'.$docName;
    }

    private function extractPetDocumentBlob(Request $request, string $field): array
    {
        if (! $request->hasFile($field)) {
            return [null, null];
        }

        $file = $request->file($field);
        if (! $file || ! $file->isValid()) {
            return [null, null];
        }

        return [
            $file->get(),
            $file->getMimeType() ?: ($file->getClientMimeType() ?: 'application/octet-stream'),
        ];
    }

    private function petDoc2BlobColumnsReady(): bool
    {
        return Schema::hasTable('pets')
            && Schema::hasColumn('pets', 'pet_doc2_blob')
            && Schema::hasColumn('pets', 'pet_doc2_mime');
    }

    private function petDoc2BlobUrl(int $petId): ?string
    {
        if (! $this->petDoc2BlobColumnsReady()) {
            return null;
        }

        $hasBlob = DB::table('pets')
            ->where('id', $petId)
            ->whereNotNull('pet_doc2_blob')
            ->exists();

        if (! $hasBlob) {
            return null;
        }

        return route('api.pets.pet-doc2-blob', ['pet' => $petId]);
    }

    private function safePetSelectColumns(): array
    {
        if (! Schema::hasTable('pets')) {
            return ['id', 'user_id', 'name', 'breed', 'pet_age', 'pet_gender', 'pet_doc1', 'pet_doc2', 'created_at', 'updated_at'];
        }

        $columns = Schema::getColumnListing('pets');
        $columns = array_values(array_filter($columns, fn (string $column) => $column !== 'pet_doc2_blob'));

        return $columns ?: ['id'];
    }

    private function buildPetSummaryPrompt(object $pet, array $prescriptions): string
    {
        $petDetails = [
            'Name' => $pet->name ?? null,
            'Breed' => $pet->breed ?? null,
            'Age' => $pet->pet_age ?? null,
            'Age (months)' => $pet->pet_age_months ?? null,
            'Gender' => $pet->pet_gender ?? null,
            'Weight' => $pet->weight ?? null,
            'Temperature' => $pet->temprature ?? null,
            'Vaccinated' => $pet->vaccenated_yes_no ?? null,
            'Reported symptom' => $pet->reported_symptom ?? null,
            'Suggested disease' => $pet->suggested_disease ?? null,
            'Docs' => implode(', ', array_filter([$pet->pet_doc1 ?? null, $pet->pet_doc2 ?? null, $pet->pet_doc ?? null])),
        ];

        $prescSummaries = [];
        foreach ($prescriptions as $p) {
            $prescSummaries[] = [
                'id' => $p->id,
                'doctor_id' => $p->doctor_id,
                'created_at' => $p->created_at,
                'diagnosis' => $p->diagnosis,
                'visit_notes' => $p->visit_notes,
                'exam_notes' => $p->exam_notes,
                'treatment_plan' => $p->treatment_plan,
                'home_care' => $p->home_care,
                'follow_up_notes' => $p->follow_up_notes,
                'image_path' => $p->image_path,
                'content_html' => $p->content_html,
            ];
        }

        $petBlock = json_encode($petDetails, JSON_PRETTY_PRINT);
        $prescBlock = json_encode($prescSummaries, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are a veterinary medical summarizer. Using the patient details and related prescriptions, write a concise paragraph (3-6 sentences) for clinicians.
- Capture species/breed, age, sex, key symptoms, notable history, working/suggested diagnoses, and recent care plans.
- If images are referenced (image_path), mention that imagery is available but do not fabricate interpretations.
- Avoid hallucinations; only use provided data. If something is missing, omit it.
Return only the paragraph text (no bullets, no JSON).

Pet details:
{$petBlock}

Prescriptions (most recent first):
{$prescBlock}
PROMPT;
    }

    private function callGeminiForSummary(string $prompt): string
    {
        $apiKey = trim((string) (config('services.gemini.api_key') ?? env('GEMINI_API_KEY') ?? \App\Support\GeminiConfig::apiKey()));
        if ($apiKey === '') {
            throw new \RuntimeException('Gemini API key is not configured.');
        }

        $model = \App\Support\GeminiConfig::chatModel() ?: \App\Support\GeminiConfig::defaultModel();
        $endpoint = sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $model);

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature' => 0.35,
                'topP' => 0.9,
                'topK' => 32,
                'maxOutputTokens' => 300,
            ],
        ];

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-goog-api-key' => $apiKey,
        ])->post($endpoint, $payload);

        if (!$response->successful()) {
            $message = data_get($response->json(), 'error.message') ?: 'Gemini API error';
            throw new \RuntimeException($message);
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        if (!$text) {
            throw new \RuntimeException('Gemini returned an empty response.');
        }

        return trim($text);
    }
}
