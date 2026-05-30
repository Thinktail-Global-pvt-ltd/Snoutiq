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

        $phoneVariants = $this->phoneVariants($digits);
        $user = $this->findUserByPhone($phone, $phoneVariants);

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

    private function findUserByPhone(string $phone, array $phoneVariants): ?User
    {
        $user = User::query()
            ->where('phone', $phone)
            ->orWhereIn('phone', $phoneVariants)
            ->first();

        if ($user) {
            return $user;
        }

        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            return User::query()
                ->whereIn(DB::raw("REGEXP_REPLACE(phone, '[^0-9]', '')"), $phoneVariants)
                ->first();
        }

        return User::query()
            ->whereNotNull('phone')
            ->get()
            ->first(fn (User $candidate) => count(array_intersect(
                $this->phoneVariants($this->normalizePhone($candidate->phone)),
                $phoneVariants
            )) > 0);
    }

    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?: '';
    }

    private function phoneVariants(string $digits): array
    {
        $variants = [$digits];

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $variants[] = substr($digits, 2);
        }

        if (strlen($digits) === 10) {
            $variants[] = '91'.$digits;
        }

        return array_values(array_unique($variants));
    }
}
