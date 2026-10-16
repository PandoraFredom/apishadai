<?php

namespace Tests\Feature;

use App\Interfaces\Promos\TicketService;
use Mockery;
use Tests\TestCase;

class TicketPhoneVerificationTest extends TestCase
{
    public function test_active_phone_endpoint_passes_the_client_id_to_the_service(): void
    {
        $service = Mockery::mock(TicketService::class);
        $service->shouldReceive('activephone')->once()->with(7)->andReturn(true);
        $this->app->instance(TicketService::class, $service);

        $this->withoutMiddleware()
            ->getJson('/api/sorteos/ticket/activephone/7')
            ->assertOk()
            ->assertJsonPath('data', true);
    }
}
