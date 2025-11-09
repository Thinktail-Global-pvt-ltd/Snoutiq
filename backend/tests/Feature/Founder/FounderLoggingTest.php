<?php

namespace Tests\Feature\Founder;

use App\Jobs\SendScheduledPush;
use App\Models\Clinic;
use App\Models\PushRun;
use App\Models\ScheduledPushNotification;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class FounderLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dashboard_request_is_logged_to_founder_channel(): void
    {
        Log::spy();

        Sanctum::actingAs(User::factory()->create());
        $clinic = Clinic::factory()->create();
        Transaction::factory()->for($clinic)->create(['status' => 'completed', 'amount_paise' => 150000]);

        $this->getJson('/api/founder/dashboard')->assertOk();

        Log::shouldHaveReceived('channel')->with('founder')->atLeast()->once();
    }

    public function test_run_now_job_emits_founder_logs(): void
    {
        Log::spy();

        $schedule = ScheduledPushNotification::factory()->create();
        $job = new SendScheduledPush($schedule->id, 'scheduled');

        $pushRun = PushRun::factory()->make([
            'schedule_id' => $schedule->id,
            'success_count' => 5,
            'failure_count' => 1,
            'targeted_count' => 6,
        ]);

        $pushService = Mockery::mock(PushService::class);
        $pushService->shouldReceive('broadcast')->once()->andReturn($pushRun);

        $job->handle($pushService);

        Log::shouldHaveReceived('channel')->with('founder')->atLeast()->once();
    }
}
