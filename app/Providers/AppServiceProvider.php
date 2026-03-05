<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public array $bindings = [
        //-------------------------------- CONFIG ---------------------------------
        \App\Interfaces\RepositoryInterface::class => \App\Repositories\Repository::class,
        \App\Interfaces\Config\ModulosRepositoryInterface::class => \App\Repositories\Config\ModuloRepository::class,
        \App\Interfaces\Config\VistaRepositoryInterface::class => \App\Repositories\Config\VistaRepository::class,
        \App\Interfaces\Config\VistaEstadosService::class => \App\Repositories\Config\VistaEstadosRepository::class,
        \App\Interfaces\Config\StockRepositoryInterface::class => \App\Repositories\Config\StockRepository::class,
        \App\Interfaces\Config\StockEstadoService::class => \App\Repositories\Config\StockEstadoRepository::class,
        \App\Interfaces\Config\UserRepositoryInterface::class => \App\Repositories\Config\UserRepository::class,
        \App\Interfaces\Config\RolesRepositoryInterface::class => \App\Repositories\Config\RolesRepository::class,
        \App\Interfaces\Config\UserEstadoRepositoryInterface::class => \App\Repositories\Config\UserEstadoRepository::class,
        \App\Interfaces\Config\PermisoService::class => \App\Repositories\Config\PermisoRepository::class,
        \App\Interfaces\Config\TipoTiempoService::class => \App\Repositories\Config\TipoTiempoRepository::class,
        \App\Interfaces\Config\AccionesVistaService::class => \App\Repositories\Config\AccionesVistaRepository::class,
        \App\Interfaces\Config\DeviceEstadoService::class => \App\Repositories\Config\DeviceEstadoRepository::class,
        \App\Interfaces\Config\DeviceService::class => \App\Repositories\Config\DeviceRepository::class,
        \App\Interfaces\Config\MatchTokensService::class => \App\Repositories\Config\MatchTokensRepository::class,

        //-------------------------------- AUTH ---------------------------------
        \App\Interfaces\Auth\AuthService::class => \App\Repositories\Auth\AuthRepository::class,

        //-------------------------------- PROMOS ---------------------------------
        \App\Interfaces\Promos\PromoEstadosService::class => \App\Repositories\promos\PromoEstadosRepository::class,
        \App\Interfaces\Promos\PromocionesService::class =>  \App\Repositories\promos\PromosRepository::class,
        \App\Interfaces\Promos\TicketService::class =>  \App\Repositories\promos\TicketRepository::class,

            //-------------------------------- CLIENTES ---------------------------------
        \App\Interfaces\Clientes\ClienteService::class => \App\Repositories\Cliente\ClienteRepository::class,
        //-------------------------------- UBICACION ---------------------------------
        \App\Interfaces\Ubicacion\DepartamentosService::class => \App\Repositories\Ubicacion\DepartamentosRepository::class,
        \App\Interfaces\Ubicacion\MunicipiosService::class => \App\Repositories\Ubicacion\MunicipiosRepository::class,


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

     /*   //log consultas a la debase de datos
         DB::listen(function($query) {
            Log::info(
                $query->sql,
                $query->bindings,

            );
        }); */
    }
}
