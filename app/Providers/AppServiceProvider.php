<?php

namespace App\Providers;

use App\Interfaces\Auth\AuthService;
use App\Interfaces\Clientes\ClienteService;
use App\Interfaces\Farma\DistributionService;
use App\Interfaces\Farma\LotService;
use App\Interfaces\Farma\ProductActiveService;
use App\Interfaces\Farma\ProductCatalogService;
use App\Interfaces\Farma\ProductCatalogRegistryService;
use App\Interfaces\Farma\ProductService;
use App\Interfaces\Farma\PurchaseCatalogService;
use App\Interfaces\Farma\PurchaseKardexService;
use App\Interfaces\Farma\PurchaseService;
use App\Interfaces\Farma\PurchaseTransactionService;
use App\Interfaces\Farma\TransferService;
use App\Interfaces\Config\AccionesVistaService;
use App\Interfaces\Config\AppConfigService;
use App\Interfaces\Config\DeviceEstadoService;
use App\Interfaces\Config\DeviceInfoService;
use App\Interfaces\Config\DeviceService;
use App\Interfaces\Config\HorasLabService;
use App\Interfaces\Config\MatchTokensService;
use App\Interfaces\Config\ModuloEstadoService;
use App\Interfaces\Config\ModulosRepositoryInterface;
use App\Interfaces\Config\PermisoService;
use App\Interfaces\Config\RolesRepositoryInterface;
use App\Interfaces\Config\StockEstadoService;
use App\Interfaces\Config\StockRepositoryInterface;
use App\Interfaces\Config\TipoTiempoService;
use App\Interfaces\Config\UserEstadoRepositoryInterface;
use App\Interfaces\Config\UserRepositoryInterface;
use App\Interfaces\Config\VistaEstadosService;
use App\Interfaces\Config\VistaRepositoryInterface;
use App\Interfaces\Laboratorios\LaboratorioService;
use App\Interfaces\Promos\PromocionesService;
use App\Interfaces\Promos\PromoEstadosService;
use App\Interfaces\Promos\TicketService;
use App\Interfaces\Proveedores\ProveedoresService;
use App\Interfaces\Reportes\SorteosRptService;
use App\Interfaces\Reportes\WorkLunchRptService;
use App\Interfaces\RepositoryInterface;
use App\Interfaces\Ubicacion\DepartamentosService;
use App\Interfaces\Ubicacion\MunicipiosService;
use App\Interfaces\WorkLunch\WorkLunchService;
use App\Repositories\Auth\AuthRepository;
use App\Repositories\Cliente\ClienteRepository;
use App\Repositories\Config\AccionesVistaRepository;
use App\Repositories\Config\AppConfigRepository;
use App\Repositories\Config\DeviceEstadoRepository;
use App\Repositories\Config\DeviceRepository;
use App\Repositories\Config\HorasLabRepository;
use App\Repositories\Config\MatchTokensRepository;
use App\Repositories\Config\ModuloRepository;
use App\Repositories\Config\ModuloEstadoRepository;
use App\Repositories\Config\PermisoRepository;
use App\Repositories\Config\RolesRepository;
use App\Repositories\Config\StockEstadoRepository;
use App\Repositories\Config\StockRepository;
use App\Repositories\Config\TipoTiempoRepository;
use App\Repositories\Config\UserEstadoRepository;
use App\Repositories\Config\UserRepository;
use App\Repositories\Config\VistaEstadosRepository;
use App\Repositories\Config\VistaRepository;
use App\Repositories\Farma\DistributionRepository;
use App\Repositories\Farma\LotRepository;
use App\Repositories\Farma\ProductActiveRepository;
use App\Repositories\Farma\ProductCatalogRepository;
use App\Repositories\Farma\ProductRepository;
use App\Repositories\Farma\PurchaseCatalogRepository;
use App\Repositories\Farma\PurchaseKardexRepository;
use App\Repositories\Farma\PurchaseRepository;
use App\Repositories\Farma\PurchaseTransactionRepository;
use App\Repositories\Farma\TransferRepository;
use App\Repositories\Laboratorios\LaboratorioRepository;
use App\Repositories\Promos\PromoEstadosRepository;
use App\Repositories\Promos\PromosRepository;
use App\Repositories\Promos\TicketRepository;
use App\Repositories\Proveedores\ProveedoresRepository;
use App\Repositories\Reportes\SorteosRptRepository;
use App\Repositories\Reportes\WorkLunchRptRepository;
use App\Repositories\Repository;
use App\Repositories\Ubicacion\DepartamentosRepository;
use App\Repositories\Ubicacion\MunicipiosRepository;
use App\Repositories\WorkLunch\WorkLunchRepository;
use App\Services\Farma\ProductCatalogRegistry;
use App\Utils\DeviceUtility;
use App\Utils\Repositories\Base64UtilityRepository;
use App\Utils\Repositories\CryptoRepository;
use App\Utils\Repositories\SingleHashRepository;
use App\Utils\Services\Base64UtilityService;
use App\Utils\Services\CryptoService;
use App\Utils\Services\SingleHashService;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Address;

class AppServiceProvider extends ServiceProvider
{
    public array $bindings = [
        // -------------------------------- CONFIG ---------------------------------
        RepositoryInterface::class => Repository::class,
        ModulosRepositoryInterface::class => ModuloRepository::class,
        ModuloEstadoService::class => ModuloEstadoRepository::class,
        VistaRepositoryInterface::class => VistaRepository::class,
        VistaEstadosService::class => VistaEstadosRepository::class,
        StockRepositoryInterface::class => StockRepository::class,
        StockEstadoService::class => StockEstadoRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        RolesRepositoryInterface::class => RolesRepository::class,
        UserEstadoRepositoryInterface::class => UserEstadoRepository::class,
        PermisoService::class => PermisoRepository::class,
        TipoTiempoService::class => TipoTiempoRepository::class,
        AccionesVistaService::class => AccionesVistaRepository::class,
        DeviceEstadoService::class => DeviceEstadoRepository::class,
        DeviceInfoService::class => DeviceUtility::class,
        DeviceService::class => DeviceRepository::class,
        HorasLabService::class => HorasLabRepository::class,
        MatchTokensService::class => MatchTokensRepository::class,
        AppConfigService::class => AppConfigRepository::class,

        // -------------------------------- AUTH ---------------------------------
        AuthService::class => AuthRepository::class,
        WorkLunchService::class => WorkLunchRepository::class,
        // -------------------------------- SORTEOS ---------------------------------
        PromoEstadosService::class => PromoEstadosRepository::class,
        PromocionesService::class => PromosRepository::class,
        TicketService::class => TicketRepository::class,

        // -------------------------------- CLIENTES ---------------------------------
        ClienteService::class => ClienteRepository::class,
        // -------------------------------- UBICACION ---------------------------------
        DepartamentosService::class => DepartamentosRepository::class,
        MunicipiosService::class => MunicipiosRepository::class,

        // -------------------------------- REPORTES ---------------------------------
        SorteosRptService::class => SorteosRptRepository::class,
        WorkLunchRptService::class => WorkLunchRptRepository::class,

        // -------------------------------- FARMA ---------------------------------
        DistributionService::class => DistributionRepository::class,
        LotService::class => LotRepository::class,
        ProductActiveService::class => ProductActiveRepository::class,
        ProductCatalogService::class => ProductCatalogRepository::class,
        ProductCatalogRegistryService::class => ProductCatalogRegistry::class,
        ProductService::class => ProductRepository::class,
        PurchaseCatalogService::class => PurchaseCatalogRepository::class,
        PurchaseKardexService::class => PurchaseKardexRepository::class,
        PurchaseService::class => PurchaseRepository::class,
        PurchaseTransactionService::class => PurchaseTransactionRepository::class,
        TransferService::class => TransferRepository::class,
        ProveedoresService::class => ProveedoresRepository::class,
        LaboratorioService::class => LaboratorioRepository::class,

        // -------------------------------- UTILS ---------------------------------

        SingleHashService::class => SingleHashRepository::class,
        CryptoService::class => CryptoRepository::class,
        Base64UtilityService::class => Base64UtilityRepository::class,

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
        $this->registerMailTracing();

        /*   //log consultas a la debase de datos
         DB::listen(function($query) {
            Log::info(
                $query->sql,
                $query->bindings,

            );
        }); */
    }

    private function registerMailTracing(): void
    {
        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $message = $event->message;

            Log::info('Correo aceptado por el transporte SMTP.', [
                'message_id' => $event->sent->getMessageId(),
                'subject' => $message->getSubject(),
                'to_hashes' => array_map(
                    fn (Address $address): string => hash('sha256', strtolower($address->getAddress())),
                    $message->getTo(),
                ),
                'cc_count' => count($message->getCc()),
            ]);
        });
    }
}
