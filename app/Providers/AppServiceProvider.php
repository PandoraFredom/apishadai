<?php

namespace App\Providers;



use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

  public array $bindings = [
        \App\Interfaces\Device\DeviceRepositoryInterface::class => \App\Repositories\DeviceRepository::class,
        \App\Interfaces\Device\DeviceServiceInterface::class => \App\Services\Device\DeviceService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);


    }
}
