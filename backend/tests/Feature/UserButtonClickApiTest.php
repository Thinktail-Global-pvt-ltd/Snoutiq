<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserButtonClickApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_button_click_is_captured_and_analysis_is_returned(): void
    {
        $user = $this->makeUser();

        $firstResponse = $this->postJson('/api/button-clicks/analyze', [
            'user_id' => $user->id,
            'page_name' => 'DoctorDashboard',
            'button_name' => 'book_consultation',
            'button_text' => 'Book Consultation',
            'session_id' => 'browser-session-1',
            'clicked_at' => '2026-05-06T10:01:00+05:30',
        ]);

        $firstResponse->assertStatus(201)
            ->assertJsonPath('data.analysis.total_user_clicks', 1)
            ->assertJsonPath('data.analysis.total_button_clicks_on_page', 1);

        $secondResponse = $this->postJson('/api/button-clicks/analyze', [
            'user_id' => $user->id,
            'page_name' => 'DoctorDashboard',
            'button_name' => 'book_consultation',
            'button_text' => 'Book Consultation',
            'session_id' => 'browser-session-1',
            'clicked_at' => '2026-05-06T10:02:00+05:30',
        ]);

        $secondResponse->assertStatus(201)
            ->assertJsonPath('data.analysis.total_user_clicks', 2)
            ->assertJsonPath('data.analysis.total_button_clicks_on_page', 2)
            ->assertJsonPath('data.analysis.user_button_clicks_on_page', 2);

        $this->assertDatabaseHas('user_button_clicks', [
            'user_id' => $user->id,
            'page_name' => 'DoctorDashboard',
            'button_name' => 'book_consultation',
        ]);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Button Click Test User',
            'email' => 'button-click-user-' . uniqid() . '@example.com',
            'phone' => '7' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'role' => 'user',
            'password' => Hash::make('password123'),
        ]);
    }
}
