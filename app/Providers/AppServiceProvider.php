<?php

namespace App\Providers;




use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public array $bindings = [

        \App\Interfaces\RepositoryInterface::class => \App\Repositories\Repository::class,
        \App\Interfaces\Config\ModulosRepositoryInterface::class => \App\Repositories\Config\ModuloRepository::class,
        \App\Interfaces\Config\VistaRepositoryInterface::class => \App\Repositories\Config\VistaRepository::class,
        \App\Interfaces\Config\StockRepositoryInterface::class => \App\Repositories\Config\StockRepository::class,
        \App\Interfaces\Config\UserRepositoryInterface::class => \App\Repositories\Config\UserRepository::class,
        \App\Interfaces\Config\RolesRepositoryInterface::class => \App\Repositories\Config\RolesRepository::class,
        \App\Interfaces\Config\UserEstadoRepositoryInterface::class => \App\Repositories\Config\UserEstadoRepository::class,
        \App\Interfaces\Config\PermisoService::class => \App\Repositories\Config\PermisoRepository::class,
        \App\Interfaces\Config\TipoTiempoService::class => \App\Repositories\Config\TipoTiempoRepository::class,
        \App\Interfaces\Config\AccionesVistaService::class => \App\Repositories\Config\AccionesVistaRepository::class,
        \App\Interfaces\Config\DeviceEstadoService::class => \App\Repositories\Config\DeviceEstadoRepository::class,
        \App\Interfaces\Config\DeviceService::class => \App\Repositories\Config\DeviceRepository::class,

    ];

    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
