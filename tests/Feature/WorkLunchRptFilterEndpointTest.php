<?php

namespace Tests\Feature;

use App\DTOs\Reportes\WorkLunchReportFilterDTO;
use App\Interfaces\Reportes\WorkLunchRptService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class WorkLunchRptFilterEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
        DB::table('users')->insert(['id' => 7]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_it_accepts_the_shapi_filter_model_payload(): void
    {
        $this->withoutMiddleware();

        $service = Mockery::mock(WorkLunchRptService::class);
        $service->shouldReceive('filter')
            ->once()
            ->with(Mockery::on(
                fn (WorkLunchReportFilterDTO $filter): bool => $filter->usuario === 7
                    && $filter->desde === '2026-09-01'
                    && $filter->hasta === '2026-09-02'
            ))
            ->andReturn(new Collection);
        $this->app->instance(WorkLunchRptService::class, $service);

        $this->json('GET', '/api/reportes/worklunch/filter', [
            'name' => 'worklunch_report',
            'filterItems' => [
                [
                    'key' => 'usuario',
                    'value' => 7,
                ],
                [
                    'key' => 'work_date',
                    'value' => ['2026-09-01', '2026-09-02'],
                ],
            ],
        ])->assertOk()->assertExactJson([
            'message' => 'ok',
            'code' => 200,
            'data' => [],
        ]);
    }
}
