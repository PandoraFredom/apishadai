<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\TicketPrintResource;
use App\Models\Clientes;
use App\Models\Promociones;
use App\Models\Stocks;
use App\Models\tikets;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TicketPrintResourceTest extends TestCase
{
    public function test_it_serializes_only_the_official_ticket_print_data(): void
    {
        $ticket = new tikets([
            'ntiket' => 23,
        ]);
        $ticket->forceFill([
            'id' => 108,
            'created_at' => Carbon::parse('2026-07-28 10:30:00', 'America/Tegucigalpa'),
        ]);
        $ticket->setRelation('Promocion', (new Promociones)->forceFill([
            'id' => 15,
            'nombre' => 'SORTEO AMISTOSO',
            'valor' => 50,
        ]));
        $ticket->setRelation('Cliente', (new Clientes)->forceFill([
            'id' => 32,
            'pnombre' => 'GESTOR',
            'papellido' => 'SAGASTUME',
            'docid' => '0801199217327',
            'telefono' => '33250022',
        ]));
        $ticket->setRelation('Stock', (new Stocks)->forceFill([
            'id' => 7,
            'descripcion' => 'STOCK DEV',
        ]));

        $data = (new TicketPrintResource($ticket))->toArray(Request::create('/'));

        $this->assertSame(108, $data['id']);
        $this->assertSame(23, $data['numero']);
        $this->assertSame(50.0, $data['valor']);
        $this->assertSame(1, $data['template_version']);
        $this->assertSame('SORTEO AMISTOSO', $data['promocion']['nombre']);
        $this->assertSame('GESTOR SAGASTUME', $data['cliente']['nombre']);
        $this->assertSame('0801199217327', $data['cliente']['identidad']);
        $this->assertSame('33250022', $data['cliente']['telefono']);
        $this->assertSame('STOCK DEV', $data['stock']['descripcion']);
        $this->assertArrayNotHasKey('base64', $data);
        $this->assertArrayNotHasKey('mime', $data);
        $this->assertArrayNotHasKey('print_html_base64', $data);
    }
}
