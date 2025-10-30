<?php

namespace App\Providers;

use App\Events\CallRequested;
use App\Listeners\SendIncomingCallPushNotification;
use App\Services\WebPushService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WebPushService::class, function ($app) {
            $config = $app['config']->get('webpush');

            return new WebPushService(
                (string) ($config['public_key'] ?? ''),
                (string) ($config['private_key'] ?? ''),
                (string) ($config['subject'] ?? 'mailto:admin@snoutiq.com'),
                (int) ($config['ttl'] ?? 60),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            CallRequested::class,
            SendIncomingCallPushNotification::class,
        );
    }
}
