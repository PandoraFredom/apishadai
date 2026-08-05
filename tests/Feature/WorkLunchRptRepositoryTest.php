<?php

namespace Tests\Feature;

use App\DTOs\Reportes\WorkLunchReportFilterDTO;
use App\Interfaces\Reportes\WorkLunchRptService;
use App\Models\WorkLunch;
use App\Repositories\Reportes\WorkLunchRptRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkLunchRptRepositoryTest extends TestCase
{
    private WorkLunchRptRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
        });
        Schema::create('stocks', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
        });
        Schema::create('devices', function (Blueprint $table): void {
            $table->id();
            $table->string('displayname');
            $table->unsignedBigInteger('stock');
        });
        Schema::create('worklunch', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario');
            $table->unsignedBigInteger('device');
            $table->dateTime('wkstart_time')->nullable();
            $table->date('work_date')->nullable();
            $table->dateTime('wkend_time')->nullable();
            $table->dateTime('lunch_start_time')->nullable();
            $table->dateTime('lunch_end_time')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            ['id' => 7, 'nombre' => 'Usuario reportado'],
            ['id' => 8, 'nombre' => 'Otro usuario'],
        ]);
        DB::table('stocks')->insert([
            'id' => 3,
            'descripcion' => 'Sucursal Centro',
        ]);
        DB::table('devices')->insert([
            'id' => 4,
            'displayname' => 'Caja 1',
            'stock' => 3,
        ]);

        $this->insertSession(7, '2026-07-01');
        $this->insertSession(7, '2026-07-31');
        $this->insertSession(7, '2026-06-30');
        $this->insertSession(8, '2026-07-15');

        $this->repository = new WorkLunchRptRepository(new WorkLunch);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('worklunch');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('users');
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_the_report_service_is_bound_to_its_repository(): void
    {
        $this->assertInstanceOf(
            WorkLunchRptRepository::class,
            $this->app->make(WorkLunchRptService::class),
        );
    }

    public function test_it_filters_the_user_by_an_inclusive_work_date_range(): void
    {
        $report = $this->repository->filter(new WorkLunchReportFilterDTO(
            usuario: 7,
            desde: '2026-07-01',
            hasta: '2026-07-31',
        ));

        $this->assertSame(2, $report->total());
        $this->assertSame(
            ['2026-07-31', '2026-07-01'],
            $report->getCollection()->pluck('work_date')->all(),
        );
        $this->assertTrue($report->getCollection()->every(
            fn (WorkLunch $session): bool => $session->usuario === 7,
        ));
        $this->assertSame(
            'Sucursal Centro',
            $report->getCollection()->first()?->Device?->Stock?->descripcion,
        );
    }

    private function insertSession(int $userId, string $date): void
    {
        DB::table('worklunch')->insert([
            'usuario' => $userId,
            'device' => 4,
            'wkstart_time' => $date.' 08:00:00',
            'work_date' => $date,
            'wkend_time' => $date.' 17:00:00',
            'lunch_start_time' => $date.' 12:00:00',
            'lunch_end_time' => $date.' 13:00:00',
        ]);
    }
}
