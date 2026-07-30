<?php

namespace Tests\Feature;

use App\Http\Controllers\API\PromocionesController;
use App\Http\Requests\Promos\PromoFilterRequest;
use App\Interfaces\Promos\PromocionesService;
use App\Models\Utils\Filter\FilterModel;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class PromocionesFilterTest extends TestCase
{
    public function test_filter_converts_the_request_to_a_filter_model(): void
    {
        $service = Mockery::mock(PromocionesService::class);
        $service->shouldReceive('filterAll')
            ->once()
            ->with(Mockery::type(FilterModel::class), true)
            ->andReturn(new Collection);

        $request = PromoFilterRequest::create('/api/sorteos/sorteo/filter', 'GET', [
            'name' => 'lista-sorteos',
            'filterItems' => [
                [
                    'key' => 'nombre',
                    'value' => '%Navidad%',
                    'operator' => 'LIKE',
                    'logicalOperator' => 'OR',
                ],
            ],
        ]);

        $response = (new PromocionesController($service))->filter($request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('No se encontraron sorteos.', $response->getData(true)['message']);
    }
}
