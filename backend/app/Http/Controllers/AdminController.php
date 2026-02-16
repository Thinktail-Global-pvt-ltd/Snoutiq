<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
     * Insert requested pet with ON DUPLICATE KEY UPDATE (no duplicates)
     * Body: { name, breed, pet_age, pet_gender, pet_type?, pet_dob?, microchip_number?, mcd_registration_number?, weight?, is_neutered?, pet_doc1?, pet_doc2? }
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
        $petType = $request->filled('pet_type') ? $request->input('pet_type') : null;
        $petDob = $request->filled('pet_dob') ? $request->input('pet_dob') : null;
        $microchipNumber = $request->input('microchip_number');
        $mcdRegistration = $request->input('mcd_registration_number');
        $weight = $request->filled('weight') ? (float)$request->input('weight') : null;

        try {
            $isNeutered = $this->normalizeNeuteredFlag($request->input('is_neutered'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        $blobSourceField = $request->hasFile('pet_doc2') ? 'pet_doc2' : ($request->hasFile('pet_doc1') ? 'pet_doc1' : null);
        [$petDocBlob, $petDocMime] = $blobSourceField ? $this->extractPetDocumentBlob($request, $blobSourceField) : [null, null];
        $blobColumnsReady = $this->petDoc2BlobColumnsReady();
        $uploadedDoc1 = $this->storePetDocument($request, 'pet_doc1');
        $uploadedDoc2 = $this->storePetDocument($request, 'pet_doc2');

        $pet_doc1   = $uploadedDoc1 ?? $request->input('pet_doc1');
        $pet_doc2   = $uploadedDoc2 ?? $request->input('pet_doc2');
        if (($pet_doc2 === null || $pet_doc2 === '') && ($pet_doc1 !== null && $pet_doc1 !== '')) {
            $pet_doc2 = $pet_doc1;
        }

        return DB::transaction(function () use (
            $userId,
            $name,
            $breed,
            $pet_age,
            $pet_gender,
            $petType,
            $petDob,
            $microchipNumber,
            $mcdRegistration,
            $weight,
            $isNeutered,
            $pet_doc1,
            $pet_doc2,
            $blobColumnsReady,
            $petDocBlob,
            $petDocMime
        ) {
            // Insert the new pet (idempotent)
            DB::statement(
                'INSERT INTO pets (user_id, name, breed, pet_age, pet_gender, pet_type, pet_dob, microchip_number, mcd_registration_number, weight, is_neutered, pet_doc1, pet_doc2, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                  name = VALUES(name),
                  breed = VALUES(breed),
                  pet_age = VALUES(pet_age),
                  pet_gender = VALUES(pet_gender),
                  pet_type = COALESCE(VALUES(pet_type), pet_type),
                  pet_dob = COALESCE(VALUES(pet_dob), pet_dob),
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
                    $petType,
                    $petDob,
                    $microchipNumber,
                    $mcdRegistration,
                    $weight,
                    $isNeutered,
                    $pet_doc1,
                    $pet_doc2,
                ]
            );

            $pet = DB::table('pets')
                ->select($this->safePetSelectColumns())
                ->where('user_id', $userId)
                ->where('name', $name)
                ->where('breed', $breed)
                ->where('pet_age', $pet_age)
                ->where('pet_gender', $pet_gender)
                ->orderByDesc('id')
                ->first();

            if ($pet && $blobColumnsReady && $petDocBlob !== null) {
                DB::table('pets')->where('id', (int) $pet->id)->update([
                    'pet_doc2_blob' => $petDocBlob,
                    'pet_doc2_mime' => $petDocMime,
                    'updated_at' => now(),
                ]);

                $pet = DB::table('pets')
                    ->select($this->safePetSelectColumns())
                    ->where('id', (int) $pet->id)
                    ->first();
            }

            if ($pet) {
                $pet->pet_doc2_blob_url = $this->petDoc2BlobUrl((int) $pet->id);
            }

            return response()->json(['status' => 'success', 'data' => $pet]);
        });
    }

    // update pet
    public function updatePet(Request $request, $petId)
    {
        $scalarCols = ['name','breed','pet_age','pet_gender','pet_type','pet_dob','microchip_number','mcd_registration_number','weight'];
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
            // Keep pet_doc2 in sync when only pet_doc1 is provided.
            $petDoc2Value = $petDoc1Value;
        }

        if ($petDoc1Value !== null) {
            $sets[] = "`pet_doc1` = ?";
            $params[] = $petDoc1Value;
        }

        if ($petDoc2Value !== null) {
            $sets[] = "`pet_doc2` = ?";
            $params[] = $petDoc2Value;
        }

        if ($this->petDoc2BlobColumnsReady() && $petDocBlob !== null) {
            $sets[] = "`pet_doc2_blob` = ?";
            $params[] = $petDocBlob;
            $sets[] = "`pet_doc2_mime` = ?";
            $params[] = $petDocMime;
        }

        if (!$sets) return response()->json(['status'=>'error','message'=>'No fields to update'], 422);

        $sql = 'UPDATE pets SET '.implode(',', $sets).', updated_at = NOW() WHERE id = ?';
        $params[] = $petId;

        $n = DB::update($sql, $params);
        if (!$n) return response()->json(['status'=>'error','message'=>'Pet not found or unchanged'], 404);

        $pet = DB::table('pets')
            ->select($this->safePetSelectColumns())
            ->where('id', $petId)
            ->first();

        if ($pet) {
            $pet->pet_doc2_blob_url = $this->petDoc2BlobUrl((int) $pet->id);
        }

        return response()->json(['status' => 'success', 'data' => $pet]);
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
