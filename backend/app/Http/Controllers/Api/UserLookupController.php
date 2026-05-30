<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserLookupController extends Controller
{
    public function byPhone(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $phone = trim($data['phone']);
        $digits = $this->normalizePhone($phone);

        if ($digits === '') {
            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'Phone number is invalid.',
            ], 422);
        }

        $user = $this->findUserByPhone($phone, $digits);

        if (! $user) {
            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'User not found for this phone number.',
                'data' => null,
            ], 404);
        }

        $user->load(['pets' => fn ($query) => $query->orderBy('id')]);

        return response()->json([
            'success' => true,
            'exists' => true,
            'message' => 'User found.',
            'data' => [
                'user' => $user,
                'pets' => $user->pets,
            ],
        ]);
    }

    private function findUserByPhone(string $phone, string $digits): ?User
    {
        $user = User::query()
            ->where('phone', $phone)
            ->orWhere('phone', $digits)
            ->first();

        if ($user) {
            return $user;
        }

        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            return User::query()
                ->whereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$digits])
                ->first();
        }

        return User::query()
            ->whereNotNull('phone')
            ->get()
            ->first(fn (User $candidate) => $this->normalizePhone($candidate->phone) === $digits);
    }

    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?: '';
    }
}
