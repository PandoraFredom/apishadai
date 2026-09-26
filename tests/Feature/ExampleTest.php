<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_missing_api_routes_return_not_found(): void
    {
        $this->withoutMiddleware();
        $this->getJson('/api/ruta-inexistente')->assertNotFound()->assertJsonPath('code', 404);
    }

    public function test_unsupported_methods_do_not_expire_the_session(): void
    {
        $this->withoutMiddleware();
        $this->getJson('/api/auth/session/login')->assertStatus(405)->assertJsonPath('code', 405);
    }
}
