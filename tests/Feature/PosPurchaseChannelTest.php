<?php

namespace Tests\Feature;

use App\Models\Farma\Lote;
use App\Models\SyncChange;
use App\Services\Headquarters\ChangeRecorder;
use App\Services\Headquarters\EntityContract;
use App\Services\Headquarters\PosPurchaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PosPurchaseChannelTest extends TestCase
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
        DB::table('modulospos')->insert(['id' => 1, 'codigo' => 'compras', 'activo' => true]);
        DB::table('vistaspos')->insert(['id' => 1, 'modulo_id' => 1, 'codigo' => 'compras.documentos', 'activo' => true]);
        DB::table('botonespos')->insert(['id' => 1, 'vista_id' => 1, 'codigo' => 'compra.recibir', 'activo' => true]);
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
        (require database_path('migrations/2026_10_14_000004_pos_purchase_cancellations.php'))->up();
        Schema::create('distribucion', fn (Blueprint $t) => [$t->id(), $t->integer('lote')]);
        Schema::create('trasferencia_detalle', fn (Blueprint $t) => [$t->id(), $t->integer('lote'), $t->integer('transferencia_id')]);
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
        return ['version' => 1, 'request_id' => (string) Str::uuid(), 'purchase_uuid' => (string) Str::uuid(), 'stock_id' => '1', 'usuario_pos_id' => '7', 'supplier_id' => '3', 'equipment' => 'TEST-POS', 'invoice_cai' => '001234', 'document_date' => '2026-10-11', 'lines' => [['line_id' => (string) Str::uuid(), 'product_id' => '1', 'lot' => 'TEST-LOT', 'manufactured' => '2026-01-01', 'expires' => '2027-01-01', 'quantity' => '2', 'purchase_factor' => '100', 'base_quantity' => '200', 'cost' => '100.1234', 'base_cost' => '1.0012', 'has_tax' => true]]];
    }

    private function receive(array $p): array
    {
        return app(PosPurchaseService::class)->execute(1, self::CLIENT, 'recibir', $p);
    }

    public function test_packs_create_central_lot_once_and_replay_after_revocation(): void
    {
        $p = $this->payload();
        $first = $this->receive($p);
        $this->assertTrue($first['accepted']);
        $this->assertSame('200', $first['lines'][0]['base_quantity']);
        $this->assertDatabaseHas('lotes', ['compra' => null, 'cantidad' => 200, 'costo' => 1.0012]);
        DB::table('permisosusuariospos')->delete();
        $this->assertSame($first, $this->receive($p));
        $this->assertDatabaseCount('lotes', 1);
        $this->assertDatabaseCount('pos_compras_centrales', 1);
    }

    public function test_same_lot_is_reused_without_rewriting_master_cost_and_duplicate_purchase_rejected(): void
    {
        $p = $this->payload();
        $first = $this->receive($p);
        $next = $this->payload();
        $next['invoice_cai'] = '001235';
        $next['lines'][0]['cost'] = '900';
        $next['lines'][0]['base_cost'] = '9';
        $this->assertSame($first['lines'][0]['lote_id'], $this->receive($next)['lines'][0]['lote_id']);
        $this->assertDatabaseCount('lotes', 1);
        $this->assertDatabaseHas('lotes', ['costo' => 1.0012]);
        $p['request_id'] = (string) Str::uuid();
        $this->assertSame('COMPRA_RECIBIDA', $this->receive($p)['code']);
    }

    public function test_missing_or_changed_unit_and_permissions_reject_without_creating_lots(): void
    {
        $p = $this->payload();
        $p['lines'][0]['purchase_factor'] = '1';
        $this->assertSame('UNIDAD_CAMBIO', $this->receive($p)['code']);
        DB::table('permisosusuariospos')->delete();
        $this->assertSame('SIN_ACCESO', $this->receive($this->payload())['code']);
        $this->assertDatabaseCount('lotes', 0);
    }

    public function test_invalid_later_line_leaves_no_partial_lot_creation(): void
    {
        $p = $this->payload();
        $next = $p['lines'][0];
        $next['line_id'] = (string) Str::uuid();
        $next['base_quantity'] = '2';
        $p['lines'][] = $next;
        $this->assertSame('DETALLE_INVALIDO', $this->receive($p)['code']);
        $this->assertDatabaseCount('lotes', 0);
    }

    public function test_changed_idempotent_request_is_rejected(): void
    {
        $p = $this->payload();
        $this->receive($p);
        $p['invoice_cai'] = '999';
        $this->expectException(HttpException::class);
        $this->receive($p);
    }

    public function test_lots_are_a_master_entity_dependent_on_products(): void
    {
        $this->assertContains('lotes', EntityContract::orderedEntities());
        $this->assertSame(['productos' => ['producto']], EntityContract::DEPENDENCIES['lotes']);
        $this->assertSame(Lote::class, ChangeRecorder::MODELS['lotes']);
    }

    public function test_new_lot_is_published_transactionally_without_inventing_sale_prices(): void
    {
        (require database_path('migrations/2026_10_01_000024_create_sync_changes_table.php'))->up();
        (require database_path('migrations/2026_10_01_000030_create_headquarters_outbox.php'))->up();
        Schema::table('sync_entity_versions', fn (Blueprint $t) => $t->json('stock_ids')->nullable());
        Schema::table('sync_changes', function (Blueprint $t) {
            $t->json('stock_ids')->nullable();
            $t->json('removed_stock_ids')->nullable();
        });
        DB::table('sync_entity_versions')->insert(['entity' => 'productos', 'entity_id' => 1, 'version' => 1]);
        config(['headquarters.publishing_enabled' => true, 'headquarters.send_immediately' => false]);
        app()->instance(ChangeRecorder::class, new ChangeRecorder);
        $this->assertTrue($this->receive($this->payload())['accepted']);
        $lot = SyncChange::where('entity', 'lotes')->firstOrFail()->payload;
        $this->assertSame('1.0012', $lot['costo']);
        $this->assertNull($lot['venta']);
        $this->assertDatabaseCount('hq_outbox', 1);
    }

    public function test_multiple_lines_share_one_new_lot_with_all_base_quantities(): void
    {
        $p = $this->payload();
        $next = $p['lines'][0];
        $next['line_id'] = (string) Str::uuid();
        $p['lines'][] = $next;
        $result = $this->receive($p);
        $this->assertTrue($result['accepted']);
        $this->assertSame($result['lines'][0]['lote_id'], $result['lines'][1]['lote_id']);
        $this->assertDatabaseCount('lotes', 1);
        $this->assertDatabaseHas('lotes', ['cantidad' => 400]);
    }

    private function allowDeletion(): void
    {
        DB::table('botonespos')->insert(['id' => 2, 'vista_id' => 1, 'codigo' => 'compra.eliminar', 'activo' => true]);
        DB::table('permisosusuariospos')->insert(['usuario' => 7, 'modulo' => 1, 'vista' => 1, 'actionvista' => 2]);
    }

    private function cancellation(array $receipt): array
    {
        return ['version' => 1, 'request_id' => (string) Str::uuid(), 'receipt_request_id' => $receipt['request_id'], 'deletion_request_id' => (string) Str::uuid(),
            'purchase_uuid' => $receipt['purchase_uuid'], 'stock_id' => '1', 'usuario_pos_id' => '7', 'equipment' => 'TEST-POS'];
    }

    public function test_cancellation_before_receipt_prevents_late_lot_creation(): void
    {
        $this->allowDeletion();
        $receipt = $this->payload();
        $cancel = $this->cancellation($receipt);

        $result = app(PosPurchaseService::class)->execute(1, self::CLIENT, 'cancelar', $cancel);

        $this->assertSame('CANCELADA', $result['code']);
        $this->assertTrue($result['accepted']);
        $this->assertSame('COMPRA_CANCELADA', $this->receive($receipt)['code']);
        $this->assertDatabaseCount('lotes', 0);
        $this->assertDatabaseCount('pos_compras_centrales', 0);
        $this->assertDatabaseCount('pos_compra_cancelaciones', 1);
    }

    public function test_cancelled_receipt_releases_supplier_invoice_and_keeps_original_response(): void
    {
        $this->allowDeletion();
        $receipt = $this->payload();
        $this->receive($receipt);
        $original = DB::table('pos_compras_centrales')->where('request_id', $receipt['request_id'])->value('respuesta');

        app(PosPurchaseService::class)->execute(1, self::CLIENT, 'cancelar', $this->cancellation($receipt));

        $this->assertDatabaseHas('pos_compras_centrales', ['request_id' => $receipt['request_id'], 'compra_aceptada_uuid' => null, 'documento_aceptado' => null, 'respuesta' => $original]);
        $replacement = $this->payload();
        $this->assertTrue($this->receive($replacement)['accepted']);
        $this->assertDatabaseCount('lotes', 1);
        $this->assertDatabaseCount('pos_compra_cancelaciones', 1);
    }

    public function test_cancellation_replay_after_permission_revocation_keeps_original_ack(): void
    {
        $this->allowDeletion();
        $receipt = $this->payload();
        $cancel = $this->cancellation($receipt);
        $first = app(PosPurchaseService::class)->execute(1, self::CLIENT, 'cancelar', $cancel);
        DB::table('permisosusuariospos')->delete();

        $this->assertSame($first, app(PosPurchaseService::class)->execute(1, self::CLIENT, 'cancelar', $cancel));
        $this->assertDatabaseCount('pos_compra_cancelaciones', 1);
        $this->assertDatabaseCount('lotes', 0);
    }

    public function test_receive_permission_does_not_authorize_cancellation(): void
    {
        $cancel = $this->cancellation($this->payload());

        try {
            app(PosPurchaseService::class)->execute(1, self::CLIENT, 'cancelar', $cancel);
            $this->fail('Eliminar requiere permiso propio.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }

        $this->assertDatabaseCount('pos_compra_cancelaciones', 0);
        $this->assertDatabaseCount('lotes', 0);
    }

    public function test_cancellation_cannot_target_another_stock(): void
    {
        $this->allowDeletion();
        $cancel = $this->cancellation($this->payload());
        $cancel['stock_id'] = '2';

        try {
            app(PosPurchaseService::class)->execute(1, self::CLIENT, 'cancelar', $cancel);
            $this->fail('Stock ajeno.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }

        $this->assertDatabaseCount('pos_compra_cancelaciones', 0);
    }

    public function test_cancellation_with_wrong_purchase_uuid_preserves_accepted_invoice(): void
    {
        $this->allowDeletion();
        $receipt = $this->payload();
        $this->receive($receipt);
        $cancel = $this->cancellation($receipt);
        $cancel['purchase_uuid'] = (string) Str::uuid();

        try {
            app(PosPurchaseService::class)->execute(1, self::CLIENT, 'cancelar', $cancel);
            $this->fail('Compra ajena.');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }

        $this->assertDatabaseHas('pos_compras_centrales', ['request_id' => $receipt['request_id'], 'compra_aceptada_uuid' => $receipt['purchase_uuid']]);
        $this->assertDatabaseCount('pos_compra_cancelaciones', 0);
    }

    public function test_cancellation_request_cannot_change_content_on_replay(): void
    {
        $this->allowDeletion();
        $cancel = $this->cancellation($this->payload());
        app(PosPurchaseService::class)->execute(1, self::CLIENT, 'cancelar', $cancel);
        $cancel['equipment'] = 'OTHER';

        try {
            app(PosPurchaseService::class)->execute(1,self::CLIENT,'cancelar',$cancel);
            $this->fail('Contenido cambiado.');
        } catch (HttpException $error) {
            $this->assertSame(409,$error->getStatusCode());
        }

        $this->assertDatabaseCount('pos_compra_cancelaciones',1);
    }
}
