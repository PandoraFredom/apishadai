<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\HorasLabResource;
use App\Models\HorasLab;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class HorasLabResourceTest extends TestCase
{
    public function test_it_serializes_the_schedule_and_its_user(): void
    {
        $user = (new User)->forceFill([
            'id' => 7,
            'nombre' => 'Usuario de prueba',
        ]);

        $schedule = (new HorasLab)->forceFill([
            'id' => 10,
            'usuario' => 7,
            'horas_lab' => 8,
            'horas_lunch' => 60,
        ]);
        $schedule->setRelation('User', $user);

        $data = (new HorasLabResource($schedule))
            ->toArray(Request::create('/'));

        $this->assertSame(10, $data['id']);
        $this->assertSame([
            'id' => 7,
            'nombre' => 'Usuario de prueba',
        ], $data['usuario']);
        $this->assertSame(8, $data['horas_lab']);
        $this->assertSame(60, $data['horas_lunch']);
    }
}
