<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCouponApplyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_is_applied_when_user_has_not_used_it(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/coupon/apply', [
            'coupon_code' => 'FIRST_VIDEO_FREE',
            'user_id' => $user->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'coupon successfully applied',
                'coupon_code' => 'FIRST_VIDEO_FREE',
                'user_id' => $user->id,
                'has_used_free_video_consult_coupon' => 1,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'has_used_free_video_consult_coupon' => 1,
        ]);
    }

    public function test_coupon_returns_already_used_when_flag_is_one(): void
    {
        $user = $this->makeUser();
        $user->update(['has_used_free_video_consult_coupon' => 1]);

        $response = $this->postJson('/api/coupon/apply', [
            'coupon_code' => 'FIRST_VIDEO_FREE',
            'user_id' => $user->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'coupon already used',
                'coupon_code' => 'FIRST_VIDEO_FREE',
                'user_id' => $user->id,
            ]);
    }

    public function test_coupon_code_must_match_first_video_free(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/coupon/apply', [
            'coupon_code' => 'SOME_OTHER_COUPON',
            'user_id' => $user->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid coupon code.',
                'coupon_code' => 'FIRST_VIDEO_FREE',
            ]);
    }

    public function test_endpoint_accepts_camel_case_inputs_too(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/coupon/apply', [
            'couponCode' => 'FIRST_VIDEO_FREE',
            'userId' => $user->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'coupon successfully applied',
                'coupon_code' => 'FIRST_VIDEO_FREE',
                'user_id' => $user->id,
                'has_used_free_video_consult_coupon' => 1,
            ]);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Coupon Test User',
            'email' => 'coupon-user-' . uniqid() . '@example.com',
            'phone' => '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'role' => 'user',
            'password' => Hash::make('password123'),
        ]);
    }
}
