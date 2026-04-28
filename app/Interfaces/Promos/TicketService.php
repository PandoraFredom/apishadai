<?php

namespace App\Interfaces\Promos;

use App\Http\Requests\Util\FilterRequest;
use App\Interfaces\RepositoryInterface;
use App\Models\Utils\Filter\FilterModel;

interface TicketService extends RepositoryInterface
{
    public function getActivePromo();

    public function getList();

    public function get_clientesList();

    public function filter_clientes(FilterModel $filterModel);

    public function filter(FilterRequest $filterModel);

    public function activephone(string $phone, int $id): bool;

    public function create_cliente(array $data);

    public function update_phone_cliente(int $id, array $data);

    public function get_departamentosList();

    public function get_municipiosList(int $departamento_id);

}
