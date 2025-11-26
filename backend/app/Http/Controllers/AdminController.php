<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

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
     * Body: { name, breed, pet_age, pet_gender, pet_doc1?, pet_doc2? }
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

        $uploadedDoc1 = $this->storePetDocument($request, 'pet_doc1');
        $uploadedDoc2 = $this->storePetDocument($request, 'pet_doc2');

        $pet_doc1   = $uploadedDoc1 ?? $request->input('pet_doc1');
        $pet_doc2   = $uploadedDoc2 ?? $request->input('pet_doc2');

        return DB::transaction(function () use ($userId, $name, $breed, $pet_age, $pet_gender, $pet_doc1, $pet_doc2) {

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
                'INSERT INTO pets (user_id, name, breed, pet_age, pet_gender, pet_doc1, pet_doc2, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                   pet_doc1 = COALESCE(VALUES(pet_doc1), pet_doc1),
                   pet_doc2 = COALESCE(VALUES(pet_doc2), pet_doc2),
                   updated_at = CURRENT_TIMESTAMP',
                [$userId, $name, $breed, $pet_age, $pet_gender, $pet_doc1, $pet_doc2]
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
        $cols = ['name','breed','pet_age','pet_gender'];
        $sets = [];
        $params = [];
        foreach ($cols as $c) {
            if ($request->has($c)) { $sets[] = "`$c` = ?"; $params[] = $request->input($c); }
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
    // Fetch all users
    public function getUsers_old(Request $request)
    {
        if ($request->email !== 'adminsnoutiq@gmail.com') {
            return response()->json(['status' => 'error', 'message' => 'Invalid user'], 403);
        }

        $users = User::all();
        return response()->json(['status' => 'success', 'data' => $users]);
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
}
