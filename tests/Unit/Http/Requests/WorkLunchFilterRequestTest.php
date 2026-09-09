<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Reportes\WorkLunchFilterRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class WorkLunchFilterRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
        DB::table('users')->insert(['id' => 7]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_it_accepts_an_integer_user_and_work_date_range(): void
    {
        $validator = Validator::make([
            'usuario' => 7,
            'work_date' => ['2026-07-01', '2026-07-31'],
        ], (new WorkLunchFilterRequest)->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_it_adapts_the_shapi_filter_model_contract(): void
    {
        $request = WorkLunchFilterRequest::create(
            '/api/reportes/worklunch/filter',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'worklunch_report',
                'filterItems' => [
                    [
                        'key' => 'usuario',
                        'value' => 7,
                    ],
                    [
                        'key' => 'work_date',
                        'value' => ['2026-07-01', '2026-07-31'],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        );
        $request->setContainer($this->app);
        $request->validateResolved();

        $this->assertSame(7, $request->validated('usuario'));
        $this->assertSame(
            ['2026-07-01', '2026-07-31'],
            $request->validated('work_date'),
        );
    }

    public function test_it_rejects_a_reversed_or_incomplete_range(): void
    {
        $reversed = Validator::make([
            'usuario' => 7,
            'work_date' => ['2026-07-31', '2026-07-01'],
        ], (new WorkLunchFilterRequest)->rules());

        $incomplete = Validator::make([
            'usuario' => 7,
            'work_date' => ['2026-07-01'],
        ], (new WorkLunchFilterRequest)->rules());

        $this->assertTrue($reversed->errors()->has('work_date.1'));
        $this->assertTrue($incomplete->errors()->has('work_date'));
    }

    public function test_it_rejects_an_unknown_user(): void
    {
        $validator = Validator::make([
            'usuario' => 99,
            'work_date' => ['2026-07-01', '2026-07-31'],
        ], (new WorkLunchFilterRequest)->rules());

        $this->assertTrue($validator->errors()->has('usuario'));
    }
}
