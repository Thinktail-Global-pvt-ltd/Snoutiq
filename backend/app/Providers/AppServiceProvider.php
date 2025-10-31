<?php

namespace App\Providers;

use App\Services\DoctorNotificationService;
use App\Services\WhatsAppService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsAppService::class, function () {
            $config = config('whatsapp');

            return new WhatsAppService(
                $config['phone_number_id'] ?? null,
                $config['access_token'] ?? null,
                $config['default_language'] ?? 'en_US',
                $config['default_template'] ?? 'hello_world'
            );
        });

        $this->app->singleton(DoctorNotificationService::class, function ($app) {
            return new DoctorNotificationService($app->make(WhatsAppService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
