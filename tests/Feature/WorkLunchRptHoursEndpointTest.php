<?php

namespace Tests\Feature;

use App\Interfaces\Config\HorasLabService;
use App\Interfaces\Reportes\WorkLunchRptService;
use App\Models\HorasLab;
use Mockery;
use Tests\TestCase;

class WorkLunchRptHoursEndpointTest extends TestCase
{
    public function test_it_returns_the_working_hours_for_a_user(): void
    {
        $this->withoutMiddleware();

        $schedule = (new HorasLab)->forceFill([
            'id' => 15,
            'usuario' => 7,
            'horas_lab' => 8,
            'horas_lunch' => 60,
        ]);

        $hoursService = Mockery::mock(HorasLabService::class);
        $hoursService->shouldReceive('findByUser')
            ->once()
            ->with(7)
            ->andReturn($schedule);

        $this->app->instance(HorasLabService::class, $hoursService);
        $this->app->instance(
            WorkLunchRptService::class,
            Mockery::mock(WorkLunchRptService::class),
        );

        $this->getJson('/api/reportes/worklunch/horas_lab/7')
            ->assertOk()
            ->assertExactJson([
                'message' => 'ok',
                'code' => 200,
                'data' => [
                    'id' => 15,
                    'usuario' => 7,
                    'horas_lab' => 8,
                    'horas_lunch' => 60,
                    'created_at' => null,
                    'updated_at' => null,
                ],
            ]);
    }

    public function test_it_returns_an_empty_schedule_when_the_user_has_no_configuration(): void
    {
        $this->withoutMiddleware();

        $hoursService = Mockery::mock(HorasLabService::class);
        $hoursService->shouldReceive('findByUser')
            ->once()
            ->with(9)
            ->andReturnNull();

        $this->app->instance(HorasLabService::class, $hoursService);
        $this->app->instance(
            WorkLunchRptService::class,
            Mockery::mock(WorkLunchRptService::class),
        );

        $this->getJson('/api/reportes/worklunch/horas_lab/9')
            ->assertOk()
            ->assertExactJson([
                'message' => 'empty',
                'code' => 200,
                'data' => [
                    'id' => null,
                    'usuario' => 9,
                    'horas_lab' => 0,
                    'horas_lunch' => 0,
                    'created_at' => null,
                    'updated_at' => null,
                ],
            ]);
    }
}
