<?php

namespace Tests\Feature;

use App\Interfaces\Config\HorasLabService;
use App\Models\HorasLab;
use App\Repositories\Config\HorasLabRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HorasLabRepositoryTest extends TestCase
{
    private HorasLabRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::create('horas_lab', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedBigInteger('usuario');
            $table->integer('horas_lab');
            $table->integer('horas_lunch');
            $table->timestamps();
            $table->foreign('usuario')->references('id')->on('users');
        });

        DB::table('users')->insert([
            ['id' => 7, 'nombre' => 'Usuario de prueba'],
            ['id' => 8, 'nombre' => 'Segundo usuario'],
        ]);

        $this->repository = new HorasLabRepository(new HorasLab);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('horas_lab');
        Schema::dropIfExists('users');
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_the_service_is_bound_to_the_repository(): void
    {
        $this->assertInstanceOf(
            HorasLabRepository::class,
            $this->app->make(HorasLabService::class),
        );
    }

    public function test_it_creates_and_finds_a_schedule_by_user(): void
    {
        $created = $this->repository->createSchedule([
            'usuario' => 7,
            'horas_lab' => 8,
            'horas_lunch' => 60,
        ]);

        $found = $this->repository->findByUser(7);

        $this->assertSame($created->id, $found?->id);
        $this->assertSame(8, $found?->horas_lab);
        $this->assertSame(60, $found?->horas_lunch);
        $this->assertSame('Usuario de prueba', $found?->User?->nombre);
    }

    public function test_it_prevents_more_than_one_schedule_per_user(): void
    {
        $this->repository->createSchedule([
            'usuario' => 7,
            'horas_lab' => 8,
            'horas_lunch' => 60,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('El usuario ya tiene una configuración de horas.');

        $this->repository->createSchedule([
            'usuario' => 7,
            'horas_lab' => 6,
            'horas_lunch' => 45,
        ]);
    }

    public function test_it_updates_the_duration_without_reassigning_the_user(): void
    {
        $created = $this->repository->createSchedule([
            'usuario' => 8,
            'horas_lab' => 8,
            'horas_lunch' => 60,
        ]);

        $updated = $this->repository->updateSchedule($created->id, [
            'horas_lab' => 6,
            'horas_lunch' => 90,
        ]);

        $this->assertSame(8, $updated?->usuario);
        $this->assertSame(6, $updated?->horas_lab);
        $this->assertSame(90, $updated?->horas_lunch);
    }
}
