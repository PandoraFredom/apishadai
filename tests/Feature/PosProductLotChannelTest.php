<?php

namespace Tests\Feature;

use App\Services\Headquarters\ChangeRecorder;
use App\Services\Headquarters\PosProductLotService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PosProductLotChannelTest extends TestCase
{
    private const CLIENT = '10000000-0000-4000-8000-000000000001';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['device_estado', 'stock_estado'] as $name) {
            Schema::create($name, fn (Blueprint $t) => [$t->id(), $t->string('descripcion')]);
            DB::table($name)->insert(['id' => 1, 'descripcion' => 'ACTIVO']);
        }
        Schema::create('stocks', fn (Blueprint $t) => [$t->id(), $t->integer('estado')]);
        DB::table('stocks')->insert(['id' => 1, 'estado' => 1]);
        Schema::create('devices', fn (Blueprint $t) => [$t->id(), $t->integer('stock'), $t->integer('estado'), $t->string('hq_client_uuid')]);
        DB::table('devices')->insert(['id' => 1, 'stock' => 1, 'estado' => 1, 'hq_client_uuid' => self::CLIENT]);
        foreach (['rolespos', 'modulospos', 'vistaspos', 'botonespos', 'usuariospos'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->id();
                $t->boolean('activo');
                $t->string('codigo')->nullable();
                if ($name === 'usuariospos') {
                    $t->integer('rol_id');
                }if ($name === 'vistaspos') {
                    $t->integer('modulo_id');
                }if ($name === 'botonespos') {
                    $t->integer('vista_id');
                }
            });
        }
        DB::table('rolespos')->insert(['id' => 1, 'activo' => true]);
        DB::table('usuariospos')->insert(['id' => 7, 'rol_id' => 1, 'activo' => true]);
        DB::table('modulospos')->insert(['id' => 1, 'codigo' => 'pos', 'activo' => true]);
        DB::table('vistaspos')->insert(['id' => 1, 'modulo_id' => 1, 'codigo' => 'pos.facturas', 'activo' => true]);
        DB::table('botonespos')->insert(['id' => 1, 'vista_id' => 1, 'codigo' => 'factura.emitir', 'activo' => true]);
        Schema::create('permisosusuariospos', fn (Blueprint $t) => [$t->id(), $t->integer('usuario'), $t->integer('modulo'), $t->integer('vista'), $t->integer('actionvista'), $t->integer('lifetime')->nullable()]);
        DB::table('permisosusuariospos')->insert(['usuario' => 7, 'modulo' => 1, 'vista' => 1, 'actionvista' => 1]);
        Schema::create('proveedores', fn (Blueprint $t) => $t->id());
        DB::table('proveedores')->insert(['id' => 3]);
        Schema::create('prod_unidades', fn (Blueprint $t) => [$t->id(), $t->integer('cantidad_c')]);
        DB::table('prod_unidades')->insert(['id' => 1, 'cantidad_c' => 100]);
        Schema::create('productos', fn (Blueprint $t) => [$t->id(), $t->integer('unidad')]);
        DB::table('productos')->insert(['id' => 1, 'unidad' => 1]);
        Schema::create('lotes', function (Blueprint $t) {
            $t->id();
            $t->integer('compra')->nullable();
            $t->integer('producto');
            $t->string('lote');
            $t->date('fecha_elab');
            $t->date('fecha_exp');
            $t->decimal('cantidad', 19, 4)->nullable();
            $t->decimal('costo', 19, 4)->nullable();
            $t->boolean('isv');
            $t->timestamps();
        });
        $migration = require base_path('database/migrations/2026_10_11_000004_pos_purchase_receipts.php');
        $migration->up();
        Schema::create('distribucion', fn (Blueprint $t) => [$t->id(), $t->integer('lote')]);
        Schema::create('trasferencia_detalle', fn (Blueprint $t) => [$t->id(), $t->integer('lote'), $t->integer('transferencia_id')]);
        Schema::table('productos', fn (Blueprint $t) => $t->integer('producto_padre_id')->nullable());
        Schema::table('prod_unidades', fn (Blueprint $t) => $t->integer('cantidad_v')->nullable());
        DB::table('prod_unidades')->where('id', 1)->update(['cantidad_c' => 1, 'cantidad_v' => 1]);
        DB::table('prod_unidades')->insert(['id' => 2, 'cantidad_c' => 10, 'cantidad_v' => 1]);
        DB::table('productos')->insert(['id' => 2, 'unidad' => 2, 'producto_padre_id' => 1]);
        Schema::create('producto_relacion_mutex', fn (Blueprint $t) => $t->integer('id')->primary());
        DB::table('producto_relacion_mutex')->insert(['id' => 1]);
        (require database_path('migrations/2026_10_11_000007_product_child_lots.php'))->up();
        DB::table('lotes')->insert(['id' => 1, 'producto' => 1, 'lote' => 'TEST-BOX', 'fecha_elab' => '2026-01-01', 'fecha_exp' => '2099-12-31', 'costo' => 100, 'cantidad' => 1, 'isv' => true]);
        $this->mock(ChangeRecorder::class, function ($mock) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('record')->byDefault()->andReturnNull();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    private function payload(): array
    {
        return ['version' => 1, 'request_id' => (string) Str::uuid(), 'stock_id' => '1', 'usuario_pos_id' => '7', 'product_id' => '2', 'parent_id' => '1', 'equipment' => 'TEST-POS', 'purchase_parent' => '1', 'sale_parent' => '1', 'purchase_child' => '10', 'sale_child' => '1', 'lines' => [['parent_lot_id' => '1', 'lot' => 'TEST-BOX', 'manufactured' => '2026-01-01', 'expires' => '2099-12-31']]];
    }

    private function prepare(array $p): array
    {
        return app(PosProductLotService::class)->execute(1, self::CLIENT, 'preparar', $p);
    }

    public function test_creates_master_lot_preserving_dates_and_cost_without_stock_tax_or_sale_prices(): void
    {
        $r = $this->prepare($this->payload());
        $this->assertTrue($r['accepted']);
        $lot = DB::table('lotes')->where('producto', 2)->sole();
        $this->assertSame('TEST-BOX', $lot->lote);
        $this->assertSame('2026-01-01', $lot->fecha_elab);
        $this->assertSame('2099-12-31', $lot->fecha_exp);
        $this->assertEquals(10, $lot->costo);
        $this->assertNull($lot->cantidad);
        $this->assertNull($lot->isv);
        $this->assertDatabaseCount('distribucion', 0);
        $this->assertDatabaseCount('trasferencia_detalle', 0);
        $this->assertSame('1', $r['lines'][0]['parent_lot_id']);
        $this->assertSame((string) $lot->id, $r['lines'][0]['lote_id']);
    }

    public function test_retries_replay_after_permission_revocation_and_another_request_reuses_the_master(): void
    {
        $p = $this->payload();
        $first = $this->prepare($p);
        $this->assertSame($first['lines'], $this->prepare($this->payload())['lines']);
        $this->assertDatabaseCount('lotes', 2);
        DB::table('permisosusuariospos')->delete();
        $this->assertSame($first, $this->prepare($p));
        $this->assertSame('SIN_ACCESO', $this->prepare($this->payload())['code']);
    }

    public function test_changed_content_with_the_same_request_id_is_rejected(): void
    {
        $p = $this->payload();
        $this->prepare($p);
        $p['lines'][0]['lot'] = 'TEST-CHANGED';
        $this->expectException(HttpException::class);
        $this->prepare($p);
    }

    public function test_relations_factors_dates_and_permission_parents_are_revalidated(): void
    {
        $p = $this->payload();
        $p['parent_id'] = '3';
        $this->assertSame('RELACION_CAMBIO', $this->prepare($p)['code']);
        $p = $this->payload();
        $p['purchase_child'] = '11';
        $this->assertSame('UNIDAD_CAMBIO', $this->prepare($p)['code']);
        $p = $this->payload();
        $p['lines'][0]['expires'] = '2098-12-31';
        $this->assertSame('LOTE_CAMBIO', $this->prepare($p)['code']);
        DB::table('vistaspos')->update(['modulo_id' => 99]);
        $this->assertSame('SIN_ACCESO', $this->prepare($this->payload())['code']);
        $this->assertDatabaseCount('lotes', 1);
    }

    public function test_unknown_dates_and_cost_stay_unknown(): void
    {
        DB::table('lotes')->where('id', 1)->update(['fecha_elab' => null, 'fecha_exp' => null, 'costo' => null]);
        $p = $this->payload();
        $p['lines'][0]['manufactured'] = null;
        $p['lines'][0]['expires'] = null;
        $this->assertTrue($this->prepare($p)['accepted']);
        $lot = DB::table('lotes')->where('producto', 2)->sole();
        $this->assertNull($lot->fecha_elab);
        $this->assertNull($lot->fecha_exp);
        $this->assertNull($lot->costo);
    }

    public function test_a_later_bad_parent_lot_does_not_create_any_partial_master_lots(): void
    {
        $p = $this->payload();
        $p['lines'][] = ['parent_lot_id' => '99', 'lot' => 'TEST-MISSING', 'manufactured' => null, 'expires' => null];
        $this->assertSame('LOTE_AUSENTE', $this->prepare($p)['code']);
        $this->assertDatabaseCount('lotes', 1);
    }

    public function test_duplicate_existing_master_lots_are_rejected_without_changing_them(): void
    {
        $values = ['producto' => 2, 'lote' => 'TEST-BOX', 'fecha_elab' => '2026-01-01', 'fecha_exp' => '2099-12-31', 'isv' => false];
        DB::table('lotes')->insert([$values, $values]);
        $this->assertSame('LOTE_AMBIGUO', $this->prepare($this->payload())['code']);
        $this->assertDatabaseCount('lotes', 3);
    }

    public function test_publication_failure_rolls_back_master_and_request(): void
    {
        $this->mock(ChangeRecorder::class, function ($m) {
            $m->shouldReceive('enabled')->andReturn(true);
            $m->shouldReceive('record')->andThrow(new \RuntimeException('TEST'));
        });
        try {
            $this->prepare($this->payload());
            $this->fail('Expected publisher failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('TEST',$e->getMessage());
        }
        $this->assertDatabaseCount('lotes',1);
        $this->assertDatabaseCount('pos_lotes_hijos_centrales',0);
    }
}
