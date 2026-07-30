<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\DeviceSecurityMiddleware;
use App\Models\Device;
use App\Models\DeviceEstado;
use App\Utils\DeviceUtility;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class DeviceSecurityMiddlewareTest extends TestCase
{
    public function test_it_stores_the_authenticated_device_on_the_request(): void
    {
        $device = $this->activeDevice();
        $utility = Mockery::mock(DeviceUtility::class);
        $utility->shouldReceive('get_DeviceInfo')->once()->andReturn($device);
        $request = Request::create('/api/auth/worklunch/work', 'POST');

        $response = (new DeviceSecurityMiddleware($utility))->handle(
            $request,
            function (Request $request) use ($device) {
                $this->assertSame($device, $request->attributes->get('authenticated_device'));

                return response()->json(['ok' => true]);
            },
        );

        $this->assertTrue($response->getData(true)['ok']);
    }

    public function test_it_reuses_a_device_already_resolved_by_an_earlier_middleware(): void
    {
        $device = $this->activeDevice();
        $utility = Mockery::mock(DeviceUtility::class);
        $utility->shouldNotReceive('get_DeviceInfo');
        $request = Request::create('/api/auth/worklunch/work', 'POST');
        $request->attributes->set('authenticated_device', $device);

        $response = (new DeviceSecurityMiddleware($utility))->handle(
            $request,
            fn () => response()->json(['ok' => true]),
        );

        $this->assertTrue($response->getData(true)['ok']);
    }

    private function activeDevice(): Device
    {
        $device = (new Device)->forceFill(['id' => 3]);
        $device->setRelation(
            'Estado',
            (new DeviceEstado)->forceFill(['descripcion' => 'ACTIVO']),
        );

        return $device;
    }
}
