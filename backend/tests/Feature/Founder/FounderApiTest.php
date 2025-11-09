<?php

namespace Tests\Feature\Founder;

use App\Models\Alert;
use App\Models\Clinic;
use App\Models\FounderSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FounderApiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/founder/dashboard')->assertStatus(401);
    }

    public function test_dashboard_returns_kpis_and_charts(): void
    {
        $this->authenticate();
        $clinic = Clinic::factory()->create(['status' => 'active']);
        Transaction::factory()->for($clinic)->create([
            'status' => 'completed',
            'amount_paise' => 25000000,
        ]);
        Alert::factory()->count(2)->create(['type' => 'critical']);

        $response = $this->getJson('/api/founder/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.kpis.totalClinics', 1)
            ->assertJsonPath('data.alerts.summary.critical', 2);
    }

    public function test_clinics_index_applies_filters(): void
    {
        $this->authenticate();
        $active = Clinic::factory()->create(['name' => 'Happy Tails', 'status' => 'active', 'city' => 'Mumbai']);
        $inactive = Clinic::factory()->create(['name' => 'Calm Paws', 'status' => 'inactive', 'city' => 'Delhi']);
        Transaction::factory()->count(2)->for($active)->create(['status' => 'completed', 'amount_paise' => 500000]);

        $response = $this->getJson('/api/founder/clinics?status=active&search=happy');

        $response->assertOk()
            ->assertJsonPath('data.summary.active', 1)
            ->assertJsonPath('data.clinics.0.name', 'Happy Tails');

        $this->getJson('/api/founder/clinics/'.$inactive->id)
            ->assertOk()
            ->assertJsonPath('data.clinic.name', 'Calm Paws');
    }

    public function test_sales_endpoint_returns_summary_and_transactions(): void
    {
        $this->authenticate();
        $clinic = Clinic::factory()->create();
        Transaction::factory()->for($clinic)->create([
            'status' => 'completed',
            'amount_paise' => 100000,
            'created_at' => now()->subDay(),
        ]);
        Transaction::factory()->for($clinic)->create([
            'status' => 'failed',
            'amount_paise' => 50000,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/founder/sales?status=all');

        $response->assertOk()
            ->assertJsonPath('data.summary.countCompleted', 1)
            ->assertJsonPath('data.transactions.0.status', 'failed');
    }

    public function test_revenue_endpoint_groups_buckets(): void
    {
        $this->authenticate();
        $clinic = Clinic::factory()->create();
        Transaction::factory()->count(2)->for($clinic)->create([
            'status' => 'completed',
            'amount_paise' => 75000,
            'created_at' => now()->subMonths(2),
        ]);

        $response = $this->getJson('/api/founder/revenue?group=month');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.buckets'));
    }

    public function test_alerts_list_and_mark_read(): void
    {
        $this->authenticate();
        $alert = Alert::factory()->create(['type' => 'warning', 'is_read' => false]);
        Alert::factory()->create(['type' => 'success', 'is_read' => true]);

        $this->getJson('/api/founder/alerts')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 2);

        $this->patchJson("/api/founder/alerts/{$alert->id}/read")
            ->assertOk()
            ->assertJsonPath('data.read', true);

        $this->patchJson('/api/founder/alerts/read-all')
            ->assertOk()
            ->assertJsonPath('data.message', 'All alerts marked as read');
    }

    public function test_settings_show_and_update(): void
    {
        $user = $this->authenticate();
        FounderSetting::factory()->create([
            'user_id' => $user->id,
            'data' => ['notifications' => ['enabled' => true], 'theme' => 'light'],
        ]);

        $this->getJson('/api/founder/settings')
            ->assertOk()
            ->assertJsonPath('data.preferences.notifications.enabled', true);

        $this->patchJson('/api/founder/settings', [
            'notifications' => ['enabled' => false],
            'theme' => 'dark',
        ])->assertOk()
            ->assertJsonPath('data.preferences.theme', 'dark');
    }
}
