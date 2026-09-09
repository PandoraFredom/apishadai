<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Filters\ClientesFilterRequest;
use App\Http\Requests\Filters\StocksFilterRequest;
use App\Http\Requests\Filters\TicketfilterRequest;
use App\Http\Requests\Filters\UserFilterRequest;
use App\Http\Requests\Promos\PromoFilterRequest;
use App\Http\Requests\Util\DefaultFilterRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FilterRequestTest extends TestCase
{
    public function test_ticket_fields_use_only_the_operator_configured_by_the_server(): void
    {
        $request = TicketfilterRequest::create('/api/reportes/sorteos/filter', 'GET', [
            'filterItems' => [
                [
                    'key' => 'promocion',
                    'value' => 12,
                ],
                [
                    'key' => 'ntiket',
                    'value' => 'TK-25',
                ],
            ],
        ]);

        [$promotion, $ticketNumber] = $request->toFilterModel()->getFilterItems();

        $this->assertSame('=', $promotion->getOperator());
        $this->assertSame('12', $promotion->getValue());
        $this->assertSame('AND', $promotion->getLogicalOperator());
        $this->assertSame('LIKE', $ticketNumber->getOperator());
        $this->assertSame('%TK-25%', $ticketNumber->getValue());
    }

    #[DataProvider('textFilterProvider')]
    public function test_text_fields_apply_their_configured_search_operator(
        string $requestClass,
        string $key
    ): void {
        $request = $requestClass::create('/filter', 'GET', [
            'filterItem' => [
                [
                    'key' => $key,
                    'value' => 'texto',
                ],
            ],
        ]);

        $item = $request->toFilterModel()->getFilterItems()[0];

        $this->assertSame('LIKE', $item->getOperator());
        $this->assertSame('%texto%', $item->getValue());
    }

    public static function textFilterProvider(): array
    {
        return [
            'default client name' => [DefaultFilterRequest::class, 'pnombre'],
            'report customer document' => [ClientesFilterRequest::class, 'docid'],
            'stock description' => [StocksFilterRequest::class, 'descripcion'],
            'user name' => [UserFilterRequest::class, 'nombre'],
            'promotion name' => [PromoFilterRequest::class, 'nombre'],
        ];
    }

    public function test_allowed_keys_are_derived_from_the_operator_map(): void
    {
        $request = new TicketfilterRequest;

        $this->assertSame(
            ['promocion', 'cliente', 'usuario', 'stock', 'ntiket'],
            $request->getAllowedKeys()
        );
        $this->assertSame(['=', 'AND'], $request->getAllowedOperators()['promocion']);
    }

    #[DataProvider('serverSideFieldsProvider')]
    public function test_contract_rejects_server_side_configuration_from_the_client(
        string $field,
        string $value
    ): void
    {
        $request = new TicketfilterRequest;
        $validator = Validator::make([
            'filterItems' => [[
                'key' => 'promocion',
                'value' => '12',
                $field => $value,
            ]],
        ], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Cada filtro solo puede contener key y value',
            $validator->errors()->first('filterItems.0')
        );
    }

    public static function serverSideFieldsProvider(): array
    {
        return [
            'sql operator' => ['operator', 'LIKE_ALL'],
            'logical connector' => ['logicalOperator', 'OR'],
        ];
    }
}
