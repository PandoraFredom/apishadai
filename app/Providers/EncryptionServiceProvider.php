<?php

namespace App\Providers;

use App\Services\EncryptionService;
use App\Utils\DeviceUtility;
use Illuminate\Support\ServiceProvider;

class EncryptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EncryptionService::class, function ($app) {
            return new EncryptionService($app->make(DeviceUtility::class));
        });
    }
}
