<?php

namespace Tests\Feature;

use App\Models\PushRun;
use App\Models\ScheduledPushNotification;
use App\Services\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PushSchedulerRunNowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_run_now_dispatches_immediate_push(): void
    {
        $schedule = ScheduledPushNotification::factory()->oneMinute()->create([
            'is_active' => true,
        ]);

        $run = PushRun::factory()->make();

        $mock = Mockery::mock(PushService::class);
        $mock->shouldReceive('broadcast')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->is($schedule)),
                $schedule->title,
                $schedule->body,
                'run_now',
                Mockery::type('string')
            )
            ->andReturn($run);

        $this->app->instance(PushService::class, $mock);

        $response = $this->post(route('dev.push-scheduler.run-now'), [
            'schedule_id' => $schedule->id,
        ]);

        $response->assertRedirect(route('dev.push-scheduler'));
        $response->assertSessionHas('status');
    }
}
