<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\Reportes\WorkLunchRptResource;
use App\Models\Device;
use App\Models\Stocks;
use App\Models\User;
use App\Models\WorkLunch;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class WorkLunchRptResourceTest extends TestCase
{
    public function test_it_serializes_marks_and_calculates_work_and_lunch_times(): void
    {
        $user = (new User)->forceFill([
            'id' => 7,
            'nombre' => 'Usuario reportado',
        ]);
        $stock = (new Stocks)->forceFill([
            'id' => 3,
            'descripcion' => 'Sucursal Centro',
        ]);
        $device = (new Device)->forceFill([
            'id' => 4,
            'displayname' => 'Caja 1',
            'stock' => 3,
        ]);
        $device->setRelation('Stock', $stock);

        $session = (new WorkLunch)->forceFill([
            'id' => 10,
            'usuario' => 7,
            'device' => 4,
            'work_date' => '2026-07-30',
            'wkstart_time' => '2026-07-30 08:00:00',
            'wkend_time' => '2026-07-30 17:00:00',
            'lunch_start_time' => '2026-07-30 12:00:00',
            'lunch_end_time' => '2026-07-30 13:00:00',
        ]);
        $session->setRelation('User', $user);
        $session->setRelation('Device', $device);

        $data = (new WorkLunchRptResource($session))
            ->toArray(Request::create('/'));

        $this->assertSame('2026-07-30', $data['work_date']);
        $this->assertSame('Usuario reportado', $data['usuario']['nombre']);
        $this->assertSame('Sucursal Centro', $data['device']['stock']['descripcion']);
        $this->assertSame('2026-07-30 08:00:00', $data['wkstart_time']);
        $this->assertSame('2026-07-30 17:00:00', $data['wkend_time']);
        $this->assertSame(540, $data['work_minutes']);
        $this->assertSame(60, $data['lunch_minutes']);
        $this->assertSame(480, $data['net_work_minutes']);
        $this->assertSame('09:00:00', $data['work_duration']);
        $this->assertSame('01:00:00', $data['lunch_duration']);
        $this->assertSame('08:00:00', $data['net_work_duration']);
    }
}
