<?php

namespace Tests\Feature;

use App\Interfaces\Reportes\WorkLunchRptService;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class WorkLunchRptUsersEndpointTest extends TestCase
{
    public function test_it_returns_only_the_user_id_and_name(): void
    {
        $this->withoutMiddleware();

        $users = new Collection([
            (new User)->forceFill([
                'id' => 7,
                'nombre' => 'Usuario reportado',
                'email' => 'private@example.com',
            ]),
        ]);

        $service = Mockery::mock(WorkLunchRptService::class);
        $service->shouldReceive('getUsuariosList')
            ->once()
            ->andReturn($users);
        $this->app->instance(WorkLunchRptService::class, $service);

        $this->getJson('/api/reportes/worklunch/listUsuarios')
            ->assertOk()
            ->assertExactJson([
                'message' => 'ok',
                'code' => 200,
                'data' => [
                    [
                        'id' => 7,
                        'nombre' => 'Usuario reportado',
                    ],
                ],
            ]);
    }
}
