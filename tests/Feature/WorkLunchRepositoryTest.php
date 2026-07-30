<?php

namespace Tests\Feature;

use App\Jobs\SendWorkLunchAlertJob;
use App\Models\WorkLunch;
use App\Repositories\WorkLunch\WorkLunchRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkLunchRepositoryTest extends TestCase
{
    private WorkLunchRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        Schema::create('worklunch', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario');
            $table->unsignedBigInteger('device');
            $table->dateTime('wkstart_time');
            $table->date('work_date')->nullable();
            $table->dateTime('wkend_time')->nullable();
            $table->dateTime('lunch_start_time')->nullable();
            $table->dateTime('lunch_end_time')->nullable();
            $table->timestamps();
            $table->unique(['usuario', 'work_date']);
        });

        Bus::fake([SendWorkLunchAlertJob::class]);
        $this->repository = new WorkLunchRepository(new WorkLunch);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('worklunch');
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_it_returns_only_the_users_session_for_the_requested_day(): void
    {
        $today = $this->repository->workService(7, '2026-07-29 08:00:00', 3);
        $this->repository->workService(8, '2026-07-29 09:00:00', 4);

        $found = $this->repository->todayForUser(7, '2026-07-29');

        $this->assertSame($today->id, $found?->id);
        $this->assertNull($this->repository->todayForUser(7, '2026-07-30'));
    }

    public function test_it_completes_the_work_and_lunch_sequence(): void
    {
        $this->repository->workService(7, '2026-07-29 08:00:00', 3);
        $this->repository->lunchService(7, '2026-07-29 12:00:00');
        $this->repository->lunchService(7, '2026-07-29 13:00:00');
        $completed = $this->repository->workService(7, '2026-07-29 17:00:00', 3);

        $this->assertSame('2026-07-29 08:00:00', $completed->wkstart_time);
        $this->assertSame('2026-07-29 12:00:00', $completed->lunch_start_time);
        $this->assertSame('2026-07-29 13:00:00', $completed->lunch_end_time);
        $this->assertSame('2026-07-29 17:00:00', $completed->wkend_time);

        foreach (['work_started', 'lunch_started', 'lunch_ended', 'work_ended'] as $event) {
            Bus::assertDispatched(
                SendWorkLunchAlertJob::class,
                fn (SendWorkLunchAlertJob $job): bool => $job->event === $event
                    && $job->queue === null,
            );
        }
    }

    public function test_it_prevents_work_end_while_lunch_is_open(): void
    {
        $this->repository->workService(7, '2026-07-29 08:00:00', 3);
        $this->repository->lunchService(7, '2026-07-29 12:00:00');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Finaliza el almuerzo antes de registrar la salida.');

        $this->repository->workService(7, '2026-07-29 12:05:00', 3);
    }

    public function test_it_prevents_lunch_after_work_has_ended(): void
    {
        $this->repository->workService(7, '2026-07-29 08:00:00', 3);
        $this->repository->workService(7, '2026-07-29 17:00:00', 3);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('La jornada laboral ya finalizó.');

        $this->repository->lunchService(7, '2026-07-29 17:05:00');
    }
}
