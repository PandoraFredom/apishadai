<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\WorkLunchResource;
use App\Models\WorkLunch;
use Illuminate\Http\Request;
use Tests\TestCase;

class WorkLunchResourceTest extends TestCase
{
    public function test_it_serializes_the_header_contract_without_related_models(): void
    {
        $session = (new WorkLunch)->forceFill([
            'id' => 15,
            'usuario' => 7,
            'device' => 3,
            'work_date' => '2026-07-29',
            'wkstart_time' => '2026-07-29 08:00:00',
            'wkend_time' => null,
            'lunch_start_time' => '2026-07-29 12:00:00',
            'lunch_end_time' => null,
        ]);

        $data = (new WorkLunchResource($session))->toArray(Request::create('/'));

        $this->assertSame(15, $data['id']);
        $this->assertSame('working', $data['work_status']);
        $this->assertSame('on_lunch', $data['lunch_status']);
        $this->assertArrayNotHasKey('usuario', $data);
        $this->assertArrayNotHasKey('device', $data);
    }

    public function test_it_reports_completed_states(): void
    {
        $session = (new WorkLunch)->forceFill([
            'id' => 15,
            'wkstart_time' => '2026-07-29 08:00:00',
            'wkend_time' => '2026-07-29 17:00:00',
            'lunch_start_time' => '2026-07-29 12:00:00',
            'lunch_end_time' => '2026-07-29 13:00:00',
        ]);

        $data = (new WorkLunchResource($session))->toArray(Request::create('/'));

        $this->assertSame('completed', $data['work_status']);
        $this->assertSame('completed', $data['lunch_status']);
    }
}
