<?php

namespace App\Repositories\Promos;

use App\DTOs\PromosDTO;
use App\Http\Requests\Util\FilterRequest;
use App\Interfaces\Promos\PromocionesService;
use App\Interfaces\Promos\PromoEstadosService;
use App\Models\Promociones;
use App\Repositories\Repository;

class PromosRepository extends Repository implements PromocionesService
{

    public function __construct(
        Promociones $model,
        private PromoEstadosService $promoEstadosService

    ) {
        parent::__construct($model);
        $this->defaultRelations = ['estado'];
    }

    public function filterPromos(FilterRequest $request)
    {
        return $this->whereListWithFilter($request->toFilterModel());
    }
    public function get_estadosList()
    {
        return $this->promoEstadosService->getAll();
    }
    public function get_promoActive()
    {
        $data = $this->joinWhereFirst(
            conditions: [
                'promoestado.descripcion' => 'ACTIVO'
            ],
            tables: [
                [
                    'table' => 'promoestado',
                    'first' => 'promociones.estado',
                    'operator' => '=',
                    'second' => 'promoestado.id'
                ]
            ],
            selects: [
                'promociones.id',
                'promociones.nombre',
                'promociones.fecha_fin',
                'promociones.valor',
                'promociones.impresiones',
            ]
        );

        return $data;
    }

    public function another_active_promo(int $current_promo_id): bool
    {
        $data = $this->joinWhereFirst(
            conditions: [
                ['promociones.id', '!=', $current_promo_id],
                ['promoestado.descripcion', '=', 'ACTIVO']
            ],
            tables: [
                [
                    'table' => 'promoestado',
                    'first' => 'promociones.estado',
                    'operator' => '=',
                    'second' => 'promoestado.id'
                ]
            ],
            selects: [
                'promociones.id',
                'promociones.nombre'
            ]
        );

        return $data !== null;
    }
}
