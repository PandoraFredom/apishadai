<?php

namespace App\Providers;




use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public array $bindings = [
        \App\Interfaces\Device\DeviceRepositoryInterface::class => \App\Repositories\DeviceRepository::class,
        \App\Interfaces\Device\DeviceServiceInterface::class => \App\Services\Device\DeviceService::class,

        \App\Interfaces\RepositoryInterface::class => \App\Repositories\Repository::class,
        \App\Interfaces\Config\ModulosRepositoryInterface::class => \App\Repositories\Config\ModuloRepository::class,
        \App\Interfaces\Config\VistaRepositoryInterface::class => \App\Repositories\Config\VistaRepository::class,
        \App\Interfaces\Config\StockRepositoryInterface::class => \App\Repositories\Config\StockRepository::class,

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
