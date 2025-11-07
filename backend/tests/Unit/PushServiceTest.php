<?php

namespace Tests\Unit;

use App\Models\DeviceToken;
use App\Models\PushRun;
use App\Models\PushRunDelivery;
use App\Models\ScheduledPushNotification;
use App\Services\Push\FcmService;
use App\Services\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PushServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_broadcast_records_counts_and_samples(): void
    {
        config(['push.batch_size' => 50]);

        $schedule = ScheduledPushNotification::factory()->oneMinute()->create([
            'data' => ['foo' => 'bar'],
        ]);

        $tokens = DeviceToken::factory()->count(2)->create();

        $responsePayload = [
            'success' => 1,
            'failure' => 1,
            'results' => [
                $tokens[0]->token => ['ok' => true],
                $tokens[1]->token => ['ok' => false, 'code' => 'unregistered', 'error' => 'Token expired'],
            ],
        ];

        $mock = Mockery::mock(FcmService::class);
        $mock->shouldReceive('sendMulticast')
            ->once()
            ->andReturn($responsePayload);

        $this->app->instance(FcmService::class, $mock);

        $service = $this->app->make(PushService::class);

        $run = $service->broadcast(
            $schedule,
            $schedule->title,
            $schedule->body ?? '',
            'scheduled',
            'test'
        );

        $this->assertSame(2, $run->targeted_count);
        $this->assertSame(1, $run->failure_count);
        $this->assertSame(1, $run->success_count);
        $this->assertNotEmpty($run->sample_device_ids);
        $this->assertNotEmpty($run->sample_errors);
        $this->assertNotNull($run->log_file);

        $this->assertEquals(1, PushRun::count());
        $this->assertEquals(2, PushRunDelivery::count());

        $this->assertDatabaseHas('push_run_deliveries', [
            'push_run_id' => $run->id,
            'status' => 'failed',
        ]);
    }
}
