<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPageVisitApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_page_visit_enter_and_exit_records_duration(): void
    {
        $user = $this->makeUser();

        $enterResponse = $this->postJson('/api/page-visits/enter', [
            'user_id' => $user->id,
            'page_name' => 'DoctorDashboard',
            'session_id' => 'browser-session-1',
            'route_path' => '/doctor/dashboard',
            'entered_at' => '2026-05-06T10:00:00+05:30',
        ]);

        $enterResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'page_name' => 'DoctorDashboard',
                ],
            ]);

        $visitId = $enterResponse->json('data.visit_id');

        $exitResponse = $this->postJson('/api/page-visits/exit', [
            'visit_id' => $visitId,
            'exited_at' => '2026-05-06T10:02:05+05:30',
        ]);

        $exitResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'visit_id' => $visitId,
                    'user_id' => $user->id,
                    'page_name' => 'DoctorDashboard',
                    'duration_seconds' => 125,
                ],
            ]);

        $this->assertDatabaseHas('user_page_visits', [
            'id' => $visitId,
            'user_id' => $user->id,
            'page_name' => 'DoctorDashboard',
            'duration_seconds' => 125,
        ]);
    }

    public function test_exit_can_find_latest_open_visit_by_user_and_page(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/page-visits/enter', [
            'user_id' => $user->id,
            'page_name' => 'Home',
            'session_id' => 'browser-session-2',
            'entered_at' => '2026-05-06T11:00:00+05:30',
        ])->assertStatus(201);

        $response = $this->postJson('/api/page-visits/exit', [
            'user_id' => $user->id,
            'page_name' => 'Home',
            'session_id' => 'browser-session-2',
            'exited_at' => '2026-05-06T11:00:45+05:30',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.duration_seconds', 45);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Page Visit Test User',
            'email' => 'page-visit-user-' . uniqid() . '@example.com',
            'phone' => '8' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'role' => 'user',
            'password' => Hash::make('password123'),
        ]);
    }
}
