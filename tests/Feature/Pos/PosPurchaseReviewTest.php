<?php

namespace Tests\Feature\Pos;

use App\Http\Middleware\DeviceSecurityMiddleware;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\JWTAuthenticationMiddleware;
use App\Http\Middleware\MatchTokenMiddleware;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosPurchaseReviewTest extends TestCase
{
    private string $requestId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware([DeviceSecurityMiddleware::class, IdempotencyMiddleware::class, JWTAuthenticationMiddleware::class, MatchTokenMiddleware::class]);
        $this->actingAs((new User)->forceFill(['id' => 7]));
        foreach (['modulo_estados', 'vista_estados'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->string('descripcion');
            });
            DB::table($name)->insert(['id' => 1, 'descripcion' => 'ACTIVO']);
        }
        Schema::create('modulos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo');
            $t->string('nombre');
            $t->integer('estado');
            $t->timestamps();
        });
        Schema::create('vistas', function (Blueprint $t) {
            $t->id();
            $t->string('codigo');
            $t->string('nombre');
            $t->integer('modulo');
            $t->integer('estado');
            $t->timestamps();
        });
        Schema::create('actionsvistas', function (Blueprint $t) {
            $t->id();
            $t->string('codigo');
            $t->string('nombre');
            $t->integer('vista');
            $t->timestamps();
        });
        Schema::create('permisos', function (Blueprint $t) {
            $t->id();
            $t->integer('usuario');
            $t->integer('modulo');
            $t->integer('vista');
            $t->integer('actionvista');
            $t->timestamp('lifetime')->nullable();
        });
        DB::table('modulos')->insert(['id' => 1, 'codigo' => 'pos', 'nombre' => 'POS', 'estado' => 1]);
        (require database_path('migrations/2026_10_11_000005_register_pos_purchase_review.php'))->up();
        foreach (['proveedores' => 'nombre', 'stocks' => 'descripcion', 'usuariospos' => 'nombre'] as $name => $label) {
            Schema::create($name, function (Blueprint $t) use ($label) {
                $t->id();
                $t->string($label);
            });
            DB::table($name)->insert(['id' => 1, $label => 'TEST '.$name]);
        }
        Schema::create('productos', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('codigo');
            $t->string('codigobar');
            $t->string('descripcion');
        });
        DB::table('productos')->insert(['id' => '10000000000000000001', 'codigo' => 'TEST-001', 'codigobar' => 'TEST-BAR', 'descripcion' => 'TEST producto']);
        Schema::create('pos_compras_centrales', function (Blueprint $t) {
            $t->uuid('request_id')->primary();
            $t->uuid('purchase_uuid');
            $t->uuid('compra_aceptada_uuid')->nullable();
            $t->integer('stock_id');
            $t->integer('supplier_id');
            $t->integer('usuario_pos_id');
            $t->string('huella');
            $t->text('datos');
            $t->text('respuesta');
            $t->timestamp('created_at');
        });
        (require database_path('migrations/2026_10_14_000004_pos_purchase_cancellations.php'))->up();
        $this->requestId = $this->receiptFixture(true);
    }

    protected function tearDown(): void
    {
        Schema::dropAllTables();
        DB::purge('sqlite');
        parent::tearDown();
    }

    private function receiptFixture(bool $accepted, string $date = '2026-10-11'): string
    {
        $id = (string) Str::uuid();
        $purchase = (string) Str::uuid();
        $payload = ['invoice_cai' => '00001998', 'document_date' => $date, 'equipment' => 'TEST-PC', 'lines' => [[
            'line_id' => (string) Str::uuid(), 'product_id' => '10000000000000000001', 'quantity' => '2.0000', 'cost' => '100.1234',
            'purchase_factor' => '100.0000', 'base_quantity' => '200.0000', 'base_cost' => '1.0012',
            'lot' => 'TEST-LOT', 'manufactured' => '2026-01-01', 'expires' => '2027-01-01', 'has_tax' => true,
        ]]];
        DB::table('pos_compras_centrales')->insert(['request_id' => $id, 'purchase_uuid' => $purchase,
            'compra_aceptada_uuid' => $accepted ? $purchase : null, 'stock_id' => 1, 'supplier_id' => 1, 'usuario_pos_id' => 1,
            'huella' => 'private-hash', 'datos' => json_encode($payload), 'respuesta' => json_encode(['accepted' => $accepted, 'code' => $accepted ? 'ACEPTADA' : 'UNIDAD_CAMBIO']), 'created_at' => now()]);

        return $id;
    }

    private function permit(string $action): array
    {
        $record = DB::table('actionsvistas')->where('codigo', $action)->first();
        DB::table('permisos')->insert(['usuario' => 7, 'modulo' => 1, 'vista' => $record->vista, 'actionvista' => $record->id]);

        return ['Action-Btn' => $action];
    }

    public function test_portal_catalog_is_idempotent_and_never_assigns_permissions(): void
    {
        (require database_path('migrations/2026_10_11_000005_register_pos_purchase_review.php'))->up();
        $this->assertSame(1, DB::table('vistas')->where('codigo', 'cpp')->count());
        $this->assertSame(3, DB::table('actionsvistas')->count());
        $this->assertSame(0, DB::table('permisos')->count());
    }

    public function test_list_and_detail_use_independent_portal_permissions_and_hide_internal_payloads(): void
    {
        $this->getJson('/api/pos/compras/listar', ['Action-Btn' => 'cppbtn01'])->assertForbidden();
        $this->getJson('/api/pos/compras/listar', $this->permit('cppbtn01'))->assertOk()->assertJsonPath('data.0.invoice_cai', '00001998')
            ->assertJsonPath('data.0.total', '230.29')->assertJsonMissingPath('data.0.lines')->assertJsonMissingPath('data.0.huella')->assertJsonMissingPath('data.0.datos');
        $this->getJson('/api/pos/compras/buscar/'.$this->requestId, ['Action-Btn' => 'cppbtn03'])->assertForbidden();
        $this->getJson('/api/pos/compras/buscar/'.$this->requestId, $this->permit('cppbtn03'))->assertOk()
            ->assertJsonPath('data.lines.0.purchase_factor', '100.0000')->assertJsonPath('data.lines.0.base_quantity', '200.0000')
            ->assertJsonPath('data.lines.0.cost', '100.1234')->assertJsonPath('data.tax', '30.04')->assertJsonPath('data.lines.0.code', 'TEST-001');
        $this->getJson('/api/pos/compras/listar', ['Action-Btn' => 'cppbtn03'])->assertForbidden();
    }

    public function test_combined_filters_include_full_documents_and_preserve_twenty_digit_ids(): void
    {
        $this->receiptFixture(false);
        $this->receiptFixture(true, '2026-10-10');
        $headers = $this->permit('cppbtn02');
        $query = http_build_query(['stock_id' => '1', 'supplier_id' => '1', 'product_id' => '10000000000000000001', 'date_from' => '2026-10-11', 'date_to' => '2026-10-11', 'status' => 'accepted']);
        $this->getJson('/api/pos/compras/filtrar?'.$query, $headers)->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.total', '230.29');
        $this->getJson('/api/pos/compras/filtrar?product_id=999', $headers)->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/pos/compras/filtrar?date_to=2026-10-11', $headers)->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/api/pos/compras/filtrar?date_from=2026-10-11&date_to=2026-10-10', $headers)->assertUnprocessable();
        $this->getJson('/api/pos/compras/opciones/products?search=TEST-BAR', $headers)->assertOk()->assertJsonPath('data.0.id', '10000000000000000001');
    }

    public function test_expiry_missing_record_and_rejections_are_safe_and_read_only(): void
    {
        $id = $this->receiptFixture(false);
        $headers = $this->permit('cppbtn03');
        $before = DB::table('pos_compras_centrales')->count();
        $this->getJson('/api/pos/compras/buscar/'.$id, $headers)->assertOk()->assertJsonPath('data.status', 'rejected')->assertJsonPath('data.reason', 'Revisa la unidad de compra del producto.');
        $this->getJson('/api/pos/compras/buscar/'.Str::uuid(), $headers)->assertNotFound();
        DB::table('permisos')->update(['lifetime' => now()->subMinute()]);
        $this->getJson('/api/pos/compras/buscar/'.$id, $headers)->assertForbidden();
        $this->postJson('/api/pos/compras/crear', [])->assertNotFound();
        $this->assertSame($before, DB::table('pos_compras_centrales')->count());
    }

    public function test_pagination_preserves_order_and_counts_all_receipts(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->receiptFixture(true);
        }
        $headers = $this->permit('cppbtn01');
        $first = $this->getJson('/api/pos/compras/listar?page=1', $headers)->assertOk()->assertJsonPath('meta.total', 31)->assertJsonPath('meta.last_page', 2)->json('data');
        $second = $this->getJson('/api/pos/compras/listar?page=2', $headers)->assertOk()->json('data');
        $this->assertCount(30, $first);
        $this->assertCount(1, $second);
        $this->assertCount(31, array_unique(array_column([...$first, ...$second], 'id')));
    }

    public function test_cancelled_purchase_is_identified_and_excluded_from_accepted_filter(): void
    {
        DB::table('pos_compras_centrales')->where('request_id', $this->requestId)->update(['cancelado_at' => now(), 'compra_aceptada_uuid' => null]);

        $this->getJson('/api/pos/compras/buscar/'.$this->requestId, $this->permit('cppbtn03'))->assertOk()->assertJsonPath('data.status', 'cancelled')->assertJsonPath('data.status_label', 'Cancelada');
        $headers = $this->permit('cppbtn02');
        $this->getJson('/api/pos/compras/filtrar?status=accepted', $headers)->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/pos/compras/filtrar?status=cancelled', $headers)->assertOk()->assertJsonPath('meta.total', 1);
    }
}
