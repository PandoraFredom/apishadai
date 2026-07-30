<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\Reportes\TicketReportResource;
use App\Models\Clientes;
use App\Models\Promociones;
use App\Models\Stocks;
use App\Models\tikets;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TicketReportResourceTest extends TestCase
{
    public function test_it_includes_the_draw_and_client_needed_by_the_report_table(): void
    {
        $ticket = (new tikets)->forceFill([
            'id' => 15,
            'ntiket' => '1024',
            'created_at' => Carbon::parse('2026-07-29 10:30:00'),
        ]);
        $ticket->setRelation('Promocion', (new Promociones)->forceFill([
            'id' => 8,
            'nombre' => 'Sorteo de verano',
        ]));
        $ticket->setRelation('Cliente', (new Clientes)->forceFill([
            'id' => 21,
            'docid' => '0801199012345',
            'pnombre' => 'Ana',
            'snombre' => 'María',
            'papellido' => 'López',
            'spaellido' => 'Díaz',
        ]));
        $ticket->setRelation('Usuario', (new User)->forceFill([
            'id' => 4,
            'nombre' => 'Operador',
        ]));
        $ticket->setRelation('Stock', (new Stocks)->forceFill([
            'id' => 3,
            'descripcion' => 'Sucursal Centro',
        ]));

        $data = TicketReportResource::make($ticket)->resolve(request());

        $this->assertSame(15, $data['id']);
        $this->assertSame('Sorteo de verano', $data['promocion']['nombre']);
        $this->assertSame('0801199012345', $data['cliente']['docid']);
        $this->assertSame('Operador', $data['usuario']['nombre']);
        $this->assertSame('Sucursal Centro', $data['stock']['descripcion']);
        $this->assertSame('2026-07-29 10:30:00', $data['created_at']);
    }
}
