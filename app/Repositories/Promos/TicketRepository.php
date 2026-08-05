<?php

namespace App\Repositories\Promos;


use App\Interfaces\Clientes\ClienteService;
use App\Interfaces\Promos\PromocionesService;
use App\Interfaces\Promos\TicketService;
use App\Interfaces\Ubicacion\DepartamentosService;
use App\Interfaces\Ubicacion\MunicipiosService;

use App\Models\tikets;
use App\Models\Utils\Filter\FilterModel;
use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;

class TicketRepository extends Repository implements TicketService
{


    public function __construct(
        tikets $model,
        private readonly ClienteService $clienteService,
        private readonly PromocionesService $promocionesService,
        private readonly DepartamentosService $departamentosService,
        private readonly MunicipiosService $municipiosService,
    ) {
        parent::__construct($model);
        $this->defaultRelations = [
            'promocion',
            'cliente',
            'usuario',
            'stock',
        ];
        $this->perPage = 20;
        $this->orderBy = ['id', 'DESC'];
    }

    public function getList()
    {
        return $this->joinWhereList(
            conditions: [
                'promoestado.descripcion' => 'ACTIVO'
            ],
            tables: [
                [
                    'table' => 'promociones',
                    'first' => 'tikets.promocion',
                    'operator' => '=',
                    'second' => 'promociones.id'
                ],
                [
                    'table' => 'promoestado',
                    'first' => 'promociones.estado',
                    'operator' => '=',
                    'second' => 'promoestado.id'
                ]
            ],
            selects: [
                'tikets.*'
            ],
            usePagination: true
        );
    }


    public function getActivePromo()
    {
        return $this->promocionesService->get_promoActive();
    }

    public function get_clientesList()
    {
        return $this->clienteService->paginate();
    }

    public function activephone(string $phone, int $id): bool
    {
        return $this->clienteService->activephone($id);
    }

    public function create_cliente(array $data)
    {
        return $this->clienteService->create($data);
    }

    public function update_phone_cliente(int $id, array $data)
    {
        return $this->clienteService->update($id, $data);
    }

    public function get_departamentosList()
    {
        return $this->departamentosService->getAll();
    }
    public function get_municipiosList(int $departamento_id)
    {
        return $this->municipiosService->getByDepartamento($departamento_id);
    }


    /**
     * @inheritDoc
     */
    public function filter_clientes(FilterModel $filterModel)
    {

        return $this->clienteService->filterAll($filterModel);
    }

    public function createTicket(array $data): tikets
    {
        return DB::transaction(function () use ($data) {
            $counter = DB::table('ticket_counters')
                ->where('name', 'tikets')
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                DB::table('ticket_counters')->insert([
                    'name' => 'tikets',
                    'current_value' => (int) $this->model->newQuery()->max('ntiket'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $counter = DB::table('ticket_counters')
                    ->where('name', 'tikets')
                    ->lockForUpdate()
                    ->first();
            }

            $nextNumber = ((int) $counter->current_value) + 1;

            DB::table('ticket_counters')
                ->where('name', 'tikets')
                ->update([
                    'current_value' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return $this->model->newQuery()->create([
                'promocion' => $data['promocion'],
                'cliente' => $data['cliente'],
                'ntiket' => $nextNumber,
                'usuario' => $data['usuario'],
                'stock' => $data['stock'],
            ]);
        }, 3);
    }
}
