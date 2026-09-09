<?php

namespace Tests\Feature;

use App\Interfaces\Reportes\SorteosRptService;
use App\Models\Utils\Filter\FilterModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RptSorteosFilterEndpointTest extends TestCase
{
    public function test_it_searches_customer_document_or_name(): void
    {
        $this->withoutMiddleware();
        Log::spy();

        $service = Mockery::mock(SorteosRptService::class);
        $service->shouldReceive('filterClientes')
            ->once()
            ->with(Mockery::on(function (FilterModel $filter): bool {
                [$document, $firstName] = $filter->getFilterItems();

                $this->assertSame('docid', $document->getKey());
                $this->assertSame('LIKE', $document->getOperator());
                $this->assertSame('%ANA%', $document->getValue());
                $this->assertSame('AND', $document->getLogicalOperator());
                $this->assertSame('pnombre', $firstName->getKey());
                $this->assertSame('LIKE', $firstName->getOperator());
                $this->assertSame('%ANA%', $firstName->getValue());
                $this->assertSame('OR', $firstName->getLogicalOperator());

                return true;
            }))
            ->andReturn(new LengthAwarePaginator([
                (object) [
                    'id' => 2,
                    'docid' => '0801199217329',
                    'pnombre' => 'DANIEL',
                    'snombre' => 'EDU',
                    'papellido' => 'RIVER',
                    'spaellido' => 'DURON',
                ],
            ], 1, 20));
        $this->app->instance(SorteosRptService::class, $service);

        $this->json('GET', '/api/reportes/sorteos/filter_clientes', [
            'name' => 'filterclientes',
            'filterItems' => [
                ['key' => 'pnombre', 'value' => 'ANA'],
                ['key' => 'docid', 'value' => 'ANA'],
            ],
        ])->assertOk()
            ->assertJsonPath('data.0.docid', '0801199217329')
            ->assertJsonPath('data.0.pnombre', 'DANIEL EDU')
            ->assertJsonPath('meta.total', 1);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Filtro de clientes recibido', Mockery::on(function (array $context): bool {
                $this->assertSame('filterclientes', $context['request']['name']);
                $this->assertSame('pnombre', $context['request']['filterItems'][0]['key']);
                $this->assertSame('docid', $context['request']['filterItems'][1]['key']);
                $this->assertSame('docid', $context['filter']['filterItems'][0]['key']);
                $this->assertSame('LIKE', $context['filter']['filterItems'][0]['operator']);
                $this->assertSame('%ANA%', $context['filter']['filterItems'][0]['value']);
                $this->assertSame('pnombre', $context['filter']['filterItems'][1]['key']);
                $this->assertSame('OR', $context['filter']['filterItems'][1]['logicalOperator']);

                return true;
            }));
    }

    #[DataProvider('filterItems')]
    public function test_it_converts_the_client_filter_to_a_partial_name_search(array $item): void
    {
        $this->withoutMiddleware();

        $service = Mockery::mock(SorteosRptService::class);
        $service->shouldReceive('filterSorteos')
            ->once()
            ->with(Mockery::on(function (FilterModel $filter): bool {
                $this->assertSame('filterSorteos', $filter->getName());
                $this->assertCount(1, $filter->getFilterItems());
                $item = $filter->getFilterItems()[0];
                $this->assertSame('nombre', $item->getKey());
                $this->assertSame('LIKE', $item->getOperator());
                $this->assertSame('%SORTEOS%', $item->getValue());
                $this->assertSame('AND', $item->getLogicalOperator());

                return true;
            }))
            ->andReturn(new LengthAwarePaginator([
                (object) [
                    'id' => 12,
                    'nombre' => 'GRAN SORTEOS NAVIDAD',
                    'fecha_inicio' => '2026-09-01',
                    'Estado' => (object) ['descripcion' => 'ACTIVO'],
                ],
            ], 1, 20));
        $this->app->instance(SorteosRptService::class, $service);

        $this->json('GET', '/api/reportes/sorteos/filter_sorteos', [
            'name' => 'filterSorteos',
            'filterItems' => [$item],
        ])->assertOk()
            ->assertJsonPath('data.0.id', 12)
            ->assertJsonPath('data.0.nombre', 'GRAN SORTEOS NAVIDAD')
            ->assertJsonPath('meta.total', 1);
    }

    public static function filterItems(): array
    {
        return [
            'client payload' => [['key' => 'nombre', 'value' => 'SORTEOS']],
            'reordered properties and whitespace' => [['value' => ' SORTEOS ', 'key' => 'nombre']],
        ];
    }
}
