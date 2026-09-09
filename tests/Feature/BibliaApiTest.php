<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BibliaApiTest extends TestCase
{
    private const BIBLE_URL = 'https://bible.example.test/random';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        config([
            'services.biblia.url' => self::BIBLE_URL,
            'services.biblia.ca_bundle' => null,
            'services.biblia.connect_timeout' => 1,
            'services.biblia.timeout' => 2,
        ]);
    }

    public function test_it_returns_the_contract_expected_by_shapi(): void
    {
        Http::fake([
            self::BIBLE_URL => Http::response($this->externalPayload()),
        ]);

        $this->getJson('/api/utils/bible/random')
            ->assertOk()
            ->assertExactJson([
                'message' => 'ok',
                'code' => 200,
                'data' => [
                    'reference' => 'rvr 1TI 4:12',
                    'version' => 'rvr',
                    'count' => 1,
                    'verses' => [[
                        'bookId' => '1TI',
                        'bookName' => '1 Timoteo',
                        'chapter' => 4,
                        'verse' => 12,
                        'text' => 'Ninguno tenga en poco tu juventud.',
                    ]],
                ],
            ]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === self::BIBLE_URL
            && $request->hasHeader('Accept', 'application/json'));
    }

    public function test_it_rejects_an_invalid_external_contract(): void
    {
        $payload = $this->externalPayload();
        unset($payload['verses'][0]['book_name']);

        Http::fake([
            self::BIBLE_URL => Http::response($payload),
        ]);

        $this->getJson('/api/utils/bible/random')
            ->assertStatus(502)
            ->assertExactJson([
                'message' => 'No se pudo obtener el versículo bíblico.',
                'code' => 502,
                'data' => false,
            ]);
    }

    public function test_it_maps_an_upstream_error_to_bad_gateway(): void
    {
        Http::fake([
            self::BIBLE_URL => Http::response(['error' => 'unavailable'], 503),
        ]);

        $this->getJson('/api/utils/bible/random')
            ->assertStatus(502)
            ->assertJsonPath('code', 502)
            ->assertJsonPath('data', false);
    }

    private function externalPayload(): array
    {
        return [
            'reference' => 'rvr 1TI 4:12',
            'version' => 'rvr',
            'count' => 1,
            'verses' => [[
                'book_id' => '1TI',
                'book_name' => '1 Timoteo',
                'chapter' => 4,
                'verse' => 12,
                'text' => 'Ninguno tenga en poco tu juventud.',
            ]],
        ];
    }
}
