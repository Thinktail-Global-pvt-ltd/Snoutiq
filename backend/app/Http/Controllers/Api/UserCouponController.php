<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserCouponController extends Controller
{
    private const COUPON_CODE = 'FIRST_VIDEO_FREE';
    private const USER_COUPON_FLAG_COLUMN = 'has_used_free_video_consult_coupon';

    public function apply(Request $request)
    {
        $data = $request->validate([
            'coupon_code' => ['nullable', 'required_without:couponCode', 'string', 'max:100'],
            'couponCode' => ['nullable', 'required_without:coupon_code', 'string', 'max:100'],
            'user_id' => ['nullable', 'required_without:userId', 'integer', 'exists:users,id'],
            'userId' => ['nullable', 'required_without:user_id', 'integer', 'exists:users,id'],
        ]);

        $couponCode = strtoupper(trim((string) ($data['coupon_code'] ?? $data['couponCode'] ?? '')));
        $userId = (int) ($data['user_id'] ?? $data['userId'] ?? 0);

        if ($couponCode !== self::COUPON_CODE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code.',
                'coupon_code' => self::COUPON_CODE,
            ], 422);
        }

        if (!Schema::hasTable('users') || !Schema::hasColumn('users', self::USER_COUPON_FLAG_COLUMN)) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon flag column missing on users table.',
            ], 500);
        }

        $result = DB::transaction(function () use ($userId) {
            $user = User::query()
                ->whereKey($userId)
                ->lockForUpdate()
                ->first(['id', self::USER_COUPON_FLAG_COLUMN]);

            if (! $user) {
                return ['status' => 'user_not_found'];
            }

            if ((int) ($user->{self::USER_COUPON_FLAG_COLUMN} ?? 0) === 1) {
                return ['status' => 'coupon_already_used'];
            }

            $user->{self::USER_COUPON_FLAG_COLUMN} = 1;
            $user->save();

            return ['status' => 'coupon_applied'];
        });

        if (($result['status'] ?? null) === 'user_not_found') {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        if (($result['status'] ?? null) === 'coupon_already_used') {
            return response()->json([
                'success' => false,
                'message' => 'coupon already used',
                'coupon_code' => self::COUPON_CODE,
                'user_id' => $userId,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'coupon successfully applied',
            'coupon_code' => self::COUPON_CODE,
            'user_id' => $userId,
            'has_used_free_video_consult_coupon' => 1,
        ]);
    }
}
