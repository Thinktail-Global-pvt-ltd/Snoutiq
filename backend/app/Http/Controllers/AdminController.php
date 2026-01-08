<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Services\Ai\DogDiseaseSuggester;

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
        $pets = DB::select('SELECT * FROM pets WHERE user_id = ? ORDER BY id DESC', [$userId]);
        return response()->json(['status'=>'success','data'=>$pets]);
    }

    // get one pet (for edit)
    public function getPet(Request $request, $petId)
    {
        $row = DB::select('SELECT * FROM pets WHERE id = ? LIMIT 1', [$petId]);
        if (!$row) return response()->json(['status'=>'error','message'=>'Pet not found'], 404);
        return response()->json(['status'=>'success','data'=>$row[0]]);
    }

    /**
     * ADD PET:
     * 1) migrate legacy pet fields from users → pets ONCE (idempotent via UNIQUE key)
     * 2) insert requested pet with ON DUPLICATE KEY UPDATE (no duplicates)
     * Body: { name, breed, pet_age, pet_gender, weight?, pet_doc1?, pet_doc2? }
     */
    public function addPet(Request $request, $userId)
    {
        // minimal required checks
        foreach (['name','breed','pet_gender','pet_age'] as $f) {
            if (!$request->filled($f)) {
                return response()->json(['status'=>'error','message'=>"$f is required"], 422);
            }
        }
        $name       = $request->input('name');
        $breed      = $request->input('breed');
        $pet_age    = (int)$request->input('pet_age');
        $pet_gender = $request->input('pet_gender');
        $microchipNumber = $request->input('microchip_number');
        $mcdRegistration = $request->input('mcd_registration_number');
        $weight = $request->filled('weight') ? (float)$request->input('weight') : null;

        try {
            $isNeutered = $this->normalizeNeuteredFlag($request->input('is_neutered'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        $uploadedDoc1 = $this->storePetDocument($request, 'pet_doc1');
        $uploadedDoc2 = $this->storePetDocument($request, 'pet_doc2');

        $pet_doc1   = $uploadedDoc1 ?? $request->input('pet_doc1');
        $pet_doc2   = $uploadedDoc2 ?? $request->input('pet_doc2');

        return DB::transaction(function () use (
            $userId,
            $name,
            $breed,
            $pet_age,
            $pet_gender,
            $microchipNumber,
            $mcdRegistration,
            $weight,
            $isNeutered,
            $pet_doc1,
            $pet_doc2
        ) {

            // (1) migrate legacy user->pets once
            DB::statement(
                'INSERT INTO pets (user_id, name, breed, pet_age, pet_gender, pet_doc1, pet_doc2, created_at, updated_at)
                 SELECT u.id, u.pet_name, u.breed, u.pet_age, u.pet_gender, u.pet_doc1, u.pet_doc2, NOW(), NOW()
                 FROM users u
                 WHERE u.id = ?
                   AND (u.pet_name IS NOT NULL OR u.breed IS NOT NULL OR u.pet_age IS NOT NULL
                        OR u.pet_gender IS NOT NULL OR u.pet_doc1 IS NOT NULL OR u.pet_doc2 IS NOT NULL)
                 ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP',
                [$userId]
            );

            // (2) insert the new pet (idempotent)
            DB::statement(
                'INSERT INTO pets (user_id, name, breed, pet_age, pet_gender, microchip_number, mcd_registration_number, weight, is_neutered, pet_doc1, pet_doc2, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                  microchip_number = COALESCE(VALUES(microchip_number), microchip_number),
                  mcd_registration_number = COALESCE(VALUES(mcd_registration_number), mcd_registration_number),
                  weight = COALESCE(VALUES(weight), weight),
                  is_neutered = COALESCE(VALUES(is_neutered), is_neutered),
                   pet_doc1 = COALESCE(VALUES(pet_doc1), pet_doc1),
                   pet_doc2 = COALESCE(VALUES(pet_doc2), pet_doc2),
                   updated_at = CURRENT_TIMESTAMP',
                [
                    $userId,
                    $name,
                    $breed,
                    $pet_age,
                    $pet_gender,
                    $microchipNumber,
                    $mcdRegistration,
                    $weight,
                    $isNeutered,
                    $pet_doc1,
                    $pet_doc2,
                ]
            );

            // return the row
            $pet = DB::select(
                'SELECT * FROM pets WHERE user_id = ? AND name = ? AND breed = ? AND pet_age = ? AND pet_gender = ? LIMIT 1',
                [$userId, $name, $breed, $pet_age, $pet_gender]
            );

            return response()->json(['status'=>'success','data'=>$pet ? $pet[0] : null]);
        });
    }

    // update pet
    public function updatePet(Request $request, $petId)
    {
        $scalarCols = ['name','breed','pet_age','pet_gender','microchip_number','mcd_registration_number'];
        $sets = [];
        $params = [];
        foreach ($scalarCols as $c) {
            if ($request->has($c)) { $sets[] = "`$c` = ?"; $params[] = $request->input($c); }
        }

        if ($request->has('is_neutered')) {
            try {
                $neuteredFlag = $this->normalizeNeuteredFlag($request->input('is_neutered'));
            } catch (\InvalidArgumentException $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            $sets[] = "`is_neutered` = ?";
            $params[] = $neuteredFlag;
        }

        $doc1Upload = $this->storePetDocument($request, 'pet_doc1');
        if ($doc1Upload) {
            $sets[] = "`pet_doc1` = ?";
            $params[] = $doc1Upload;
        } elseif ($request->has('pet_doc1')) {
            $sets[] = "`pet_doc1` = ?";
            $params[] = $request->input('pet_doc1');
        }

        $doc2Upload = $this->storePetDocument($request, 'pet_doc2');
        if ($doc2Upload) {
            $sets[] = "`pet_doc2` = ?";
            $params[] = $doc2Upload;
        } elseif ($request->has('pet_doc2')) {
            $sets[] = "`pet_doc2` = ?";
            $params[] = $request->input('pet_doc2');
        }

        if (!$sets) return response()->json(['status'=>'error','message'=>'No fields to update'], 422);

        $sql = 'UPDATE pets SET '.implode(',', $sets).', updated_at = NOW() WHERE id = ?';
        $params[] = $petId;

        $n = DB::update($sql, $params);
        if (!$n) return response()->json(['status'=>'error','message'=>'Pet not found or unchanged'], 404);

        $row = DB::select('SELECT * FROM pets WHERE id = ? LIMIT 1', [$petId]);
        return response()->json(['status'=>'success','data'=>$row[0]]);
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
        $symptom = trim($payload['symptom']);

        try {
            $suggester = new DogDiseaseSuggester();
            $result = $suggester->suggest($symptom, [
                'name'       => $pet->name ?? null,
                'breed'      => $pet->breed ?? null,
                'pet_age'    => $pet->pet_age ?? null,
                'pet_gender' => $pet->pet_gender ?? null,
            ]);
            $diseaseName = $result['disease_name'] ?? 'Unknown dog disease';
            $category = strtolower($result['category'] ?? 'normal');
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not generate disease suggestion: '.$e->getMessage(),
            ], 500);
        }

        DB::update(
            'UPDATE pets SET reported_symptom = ?, suggested_disease = ?, health_state = ?, updated_at = NOW() WHERE id = ?',
            [$symptom, $diseaseName, in_array($category, ['normal','chronic'], true) ? $category : null, $petId]
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'pet_id' => $petId,
                'symptom_saved' => $symptom,
                'suggested_disease' => $diseaseName,
                'category' => in_array($category, ['normal','chronic'], true) ? $category : 'normal',
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
