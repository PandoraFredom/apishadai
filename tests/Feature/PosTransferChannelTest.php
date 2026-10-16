<?php

namespace Tests\Feature;

use App\Http\Middleware\DeviceSecurityMiddleware;
use App\Http\Middleware\JWTAuthenticationMiddleware;
use App\Http\Middleware\MatchTokenMiddleware;
use Database\Seeders\TransferPermissionCatalogSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PosTransferChannelTest extends TestCase
{
    private const CLIENT_UUID = '10000000-0000-4000-8000-000000000001';

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'headquarters.secret' => str_repeat('t', 64), 'headquarters.pos_sender_ips' => ['127.0.0.1']]);
        DB::purge('sqlite');
        $this->freezeTime();
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    private function arrange(): void
    {
        foreach (['stock_estado', 'device_estado'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->string('descripcion');
                $table->timestamps();
            });
            DB::table($name)->insert(['id' => 1, 'descripcion' => 'ACTIVO']);
        }
        Schema::create('stocks', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
            $table->integer('estado');
            $table->timestamps();
        });
        DB::table('stocks')->insert([['id' => 1, 'descripcion' => 'TEST origin', 'estado' => 1], ['id' => 2, 'descripcion' => 'TEST destination', 'estado' => 1], ['id' => 3, 'descripcion' => 'TEST third', 'estado' => 1]]);
        Schema::create('devices', function (Blueprint $table): void {
            $table->id();
            $table->string('hq_client_uuid');
            $table->integer('stock');
            $table->integer('estado');
            $table->timestamps();
        });
        DB::table('devices')->insert(['id' => 1, 'hq_client_uuid' => self::CLIENT_UUID, 'stock' => 1, 'estado' => 1]);
        foreach (['modulospos', 'vistaspos', 'botonespos', 'rolespos'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->string('nombre');
                $table->boolean('activo');
                $table->timestamps();
                if ($name !== 'rolespos') {
                    $table->string('codigo')->unique();
                }
                if ($name === 'vistaspos') {
                    $table->integer('modulo_id');
                }
                if ($name === 'botonespos') {
                    $table->integer('vista_id');
                }
            });
        }
        DB::table('modulospos')->insert(['id' => 1, 'nombre' => 'TEST transfers', 'codigo' => 'transferencias', 'activo' => true]);
        DB::table('rolespos')->insert(['id' => 1, 'nombre' => 'TEST role', 'activo' => true]);
        Schema::create('usuariospos', function (Blueprint $table): void {
            $table->id();
            $table->integer('rol_id');
            $table->boolean('activo');
        });
        DB::table('usuariospos')->insert(['id' => 7, 'rol_id' => 1, 'activo' => true]);
        Schema::create('permisosusuariospos', function (Blueprint $table): void {
            $table->id();
            $table->integer('usuario');
            $table->integer('modulo');
            $table->integer('vista');
            $table->integer('actionvista');
            $table->integer('lifetime')->nullable();
        });
        Schema::create('lotes', function (Blueprint $table): void {
            $table->id();
            $table->integer('producto');
            $table->integer('cantidad');
        });
        DB::table('lotes')->insert(['id' => 123, 'producto' => 9, 'cantidad' => 40]);
        (require database_path('migrations/2026_10_07_000001_create_pos_transfer_channel.php'))->up();
        (require database_path('migrations/2026_10_07_000005_pos_transfer_cancellations.php'))->up();
        (new TransferPermissionCatalogSeeder)->run();
        foreach (DB::table('botonespos')->get() as $button) {
            DB::table('permisosusuariospos')->insert(['usuario' => 7, 'modulo' => 1, 'vista' => $button->vista_id, 'actionvista' => $button->id]);
        }
        Schema::create('transferencia_estado', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
        });
        Schema::create('transferencias', function (Blueprint $table): void {
            $table->id();
            $table->integer('stock_de');
            $table->integer('stock_para');
            $table->integer('estado');
            $table->integer('estado_recepcion');
        });
    }

    private function payload(): array
    {
        return ['version' => 1, 'unidad' => 'BASE', 'request_id' => '20000000-0000-4000-8000-000000000001',
            'transferencia_uuid' => '30000000-0000-4000-8000-000000000001', 'stock_de' => '1', 'stock_para' => '2',
            'usuario_pos_id' => '7', 'equipo' => 'TEST-POS', 'terminal_nombre' => 'TEST origin-Caja1', 'referencia' => 'TEST-TR',
            'lineas' => [['lote_id' => '123', 'producto_id' => '9', 'numero_lote' => 'TEST lot', 'vencimiento' => '2030-01-01', 'cantidad' => '1.2500']]];
    }

    private function signed(string $action, array $payload, ?string $nonce = null): TestResponse
    {
        $path = '/api/headquarters/pos/transferencias/'.$action;
        $body = json_encode(['device_id' => 1, 'client_uuid' => self::CLIENT_UUID, 'payload' => $payload], JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $nonce ??= (string) Str::uuid();
        $signature = hash_hmac('sha256', "POST\n$path\n$timestamp\n$nonce\n".hash('sha256', $body), str_repeat('t', 64));

        return $this->call('POST', $path, [], [], [], $this->transformHeadersToServerVars([
            'Accept' => 'application/json', 'Content-Type' => 'application/json', 'X-HQ-Key-Id' => 'apishadai',
            'X-HQ-Timestamp' => $timestamp, 'X-HQ-Nonce' => $nonce, 'X-HQ-Signature' => $signature,
        ]), $body);
    }

    public function test_hq_sends_without_a_portal_session_and_records_the_pos_operator(): void
    {
        $this->arrange();
        $this->mock(JWTAuthenticationMiddleware::class)->shouldNotReceive('handle');
        $this->mock(MatchTokenMiddleware::class)->shouldNotReceive('handle');
        $this->mock(DeviceSecurityMiddleware::class)->shouldNotReceive('handle');

        $this->signed('enviar', $this->payload())->assertOk()->assertJsonPath('data.id', '1');
        $this->assertDatabaseHas('pos_transferencias_centrales', [
            'id' => 1, 'stock_de' => 1, 'stock_para' => 2, 'usuario_envia_pos' => 7,
        ]);
    }

    public function test_send_retries_keep_one_document_and_do_not_decrement_central_lots(): void
    {
        $this->arrange();
        $this->signed('enviar', $this->payload())->assertOk()->assertJsonPath('data.id', '1');
        $this->signed('enviar', $this->payload())->assertOk()->assertJsonPath('data.id', '1');
        $this->assertDatabaseCount('pos_transferencias_centrales', 1);
        $this->assertDatabaseHas('lotes', ['id' => 123, 'cantidad' => 40]);
        $saved = json_decode(DB::table('pos_transferencias_centrales')->value('datos'), true);
        $this->assertSame('1.2500', $saved['lineas'][0]['cantidad']);
    }

    public function test_changed_content_with_the_same_uuid_returns_409(): void
    {
        $this->arrange();
        $payload = $this->payload();
        $this->signed('enviar', $payload)->assertOk();
        $payload['lineas'][0]['cantidad'] = '2.0000';
        $this->signed('enviar', $payload)->assertStatus(409);
        $this->assertDatabaseCount('pos_transferencias_centrales', 1);
    }

    public function test_only_the_destination_stock_can_receive_and_retries_are_idempotent(): void
    {
        $this->arrange();
        $this->signed('enviar', $this->payload())->assertOk();
        $receipt = ['version' => 1, 'request_id' => '40000000-0000-4000-8000-000000000001',
            'transferencia_uuid' => $this->payload()['transferencia_uuid'], 'central_id' => '1', 'stock_para' => '2',
            'usuario_pos_id' => '7', 'equipo' => 'TEST-POS', 'terminal_nombre' => 'TEST destination-Caja1'];
        $this->signed('recibir', $receipt)->assertStatus(403);
        DB::table('devices')->where('id', 1)->update(['stock' => 2]);
        $this->signed('recibir', $receipt)->assertOk();
        $this->signed('recibir', $receipt)->assertOk();
        $this->assertDatabaseHas('pos_transferencias_centrales', ['id' => 1, 'estado' => 'RECIBIDA', 'usuario_recibe_pos' => 7]);
        $receipt['request_id'] = '50000000-0000-4000-8000-000000000001';
        $this->signed('recibir', $receipt)->assertStatus(409);
    }

    public function test_foreign_stock_cannot_download_and_origin_cannot_be_forged(): void
    {
        $this->arrange();
        $this->signed('enviar', $this->payload())->assertOk();
        DB::table('devices')->where('id', 1)->update(['stock' => 3]);
        $this->signed('listar', [])->assertOk()->assertJsonPath('data.records', []);
        $this->signed('enviar', $this->payload())->assertStatus(403);
    }

    public function test_sql_server_uppercase_uuid_receipt_keeps_destination_permissions_and_original_retry_hash(): void
    {
        $this->arrange();
        $sent = $this->payload();
        $sent['transferencia_uuid'] = 'abcdabcd-abcd-4bcd-abcd-abcdabcdabcd';
        $this->signed('enviar', $sent)->assertOk();
        $receipt = ['version' => 1, 'request_id' => 'ef12ef12-ef12-4f12-af12-ef12ef12ef12',
            'transferencia_uuid' => strtoupper($sent['transferencia_uuid']), 'central_id' => '1', 'stock_para' => '2',
            'usuario_pos_id' => '7', 'equipo' => 'TEST-POS', 'terminal_nombre' => 'TEST destination-Caja1'];
        $receipt['request_id'] = strtoupper($receipt['request_id']);
        $this->signed('recibir', $receipt)->assertStatus(403);
        DB::table('devices')->where('id', 1)->update(['stock' => 2]);
        $wrong = $receipt;
        $wrong['transferencia_uuid'] = 'ABCDABCD-ABCD-4BCD-ABCD-ABCDABCDABCE';
        $this->signed('recibir', $wrong)->assertStatus(403);
        DB::table('botonespos')->where('codigo', 'transferencia.recibidas.recibir')->update(['activo' => false]);
        $this->signed('recibir', $receipt)->assertStatus(403);
        $this->assertDatabaseHas('pos_transferencias_centrales', ['id' => 1, 'estado' => 'PENDIENTE']);
        DB::table('botonespos')->where('codigo', 'transferencia.recibidas.recibir')->update(['activo' => true]);

        $this->signed('recibir', $receipt)->assertOk()->assertJsonPath('data.transferencia_uuid', $sent['transferencia_uuid']);
        $this->signed('recibir', $receipt)->assertOk();
        $this->assertDatabaseCount('pos_transferencias_centrales', 1);
        $this->assertDatabaseHas('pos_transferencias_centrales', ['id' => 1, 'estado' => 'RECIBIDA',
            'solicitud_recepcion' => $receipt['request_id'],
            'huella_recepcion' => hash('sha256', json_encode($receipt, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))]);
        $this->assertDatabaseHas('lotes', ['id' => 123, 'cantidad' => 40]);
        $changed = $receipt;
        $changed['terminal_nombre'] = 'TEST changed';
        $this->signed('recibir', $changed)->assertStatus(409);
    }

    public function test_expired_individual_permission_returns_403(): void
    {
        $this->arrange();
        DB::table('permisosusuariospos')->update(['lifetime' => now()->timestamp - 1]);
        $this->signed('enviar', $this->payload())->assertStatus(403);
        $this->assertDatabaseCount('pos_transferencias_centrales', 0);
    }

    public function test_missing_signature_and_replayed_nonce_return_401(): void
    {
        $this->arrange();
        $this->postJson('/api/headquarters/pos/transferencias/enviar', [])->assertStatus(401);
        $nonce = '60000000-0000-4000-8000-000000000001';
        $this->signed('enviar', $this->payload(), $nonce)->assertOk();
        $this->signed('enviar', $this->payload(), $nonce)->assertStatus(401);
    }

    public function test_duplicate_lots_and_fifth_decimal_are_rejected_with_422(): void
    {
        $this->arrange();
        $payload = $this->payload();
        $payload['lineas'][] = $payload['lineas'][0];
        $this->signed('enviar', $payload)->assertStatus(422);
        $payload = $this->payload();
        $payload['lineas'][0]['cantidad'] = '0.00001';
        $this->signed('enviar', $payload)->assertStatus(422);
        $this->assertDatabaseCount('pos_transferencias_centrales', 0);
    }

    public function test_legacy_h_q_quantities_get_one_persistent_uuid_and_keep_their_snapshot(): void
    {
        $this->arrange();
        Schema::table('lotes', function (Blueprint $table): void {
            $table->string('lote')->nullable();
            $table->date('fecha_exp')->nullable();
        });
        Schema::table('transferencias', function (Blueprint $table): void {
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('recibido_at')->nullable();
            $table->timestamps();
        });
        Schema::create('trasferencia_detalle', function (Blueprint $table): void {
            $table->id();
            $table->integer('transferencia_id');
            $table->integer('lote');
            $table->integer('cantidad');
        });
        DB::table('transferencia_estado')->insert([['id' => 1, 'descripcion' => 'ENVIADA'], ['id' => 2, 'descripcion' => 'PENDIENTE'], ['id' => 3, 'descripcion' => 'RECIBIDA']]);
        DB::table('lotes')->where('id', 123)->update(['lote' => 'TEST original', 'fecha_exp' => '2030-01-01']);
        DB::table('transferencias')->insert(['id' => 90, 'stock_de' => 1, 'stock_para' => 2, 'estado' => 1, 'estado_recepcion' => 2]);
        DB::table('trasferencia_detalle')->insert(['transferencia_id' => 90, 'lote' => 123, 'cantidad' => 3]);
        DB::table('devices')->where('id', 1)->update(['stock' => 2]);
        $first = $this->signed('listar', [])->assertOk()->json('data.records.0');
        $this->assertSame('90', $first['hq_transferencia_id']);
        $snapshot = json_decode($first['datos'], true);
        $this->assertSame('3', $snapshot['lineas'][0]['cantidad']);
        DB::table('lotes')->where('id', 123)->update(['lote' => 'TEST later']);
        $second = $this->signed('listar', [])->assertOk()->json('data.records.0');
        $this->assertNotEmpty($first['enviado_at']);
        $this->assertSame($first['enviado_at'], $second['enviado_at']);
        $this->assertSame($first['datos'], $second['datos']);
        $this->assertDatabaseCount('pos_transferencias_centrales', 1);
        $this->signed('recibir', ['version' => 1, 'request_id' => '40000000-0000-4000-8000-000000000001',
            'transferencia_uuid' => strtoupper($snapshot['transferencia_uuid']), 'central_id' => '1', 'stock_para' => '2',
            'usuario_pos_id' => '7', 'equipo' => 'TEST-POS', 'terminal_nombre' => 'TEST destination-Caja1'])->assertOk();
        $this->assertDatabaseHas('transferencias', ['id' => 90, 'estado_recepcion' => 3]);
        $this->assertDatabaseHas('pos_transferencias_centrales', ['id' => 1, 'usuario_recibe_pos' => 7, 'estado' => 'RECIBIDA']);
    }

    public function test_typed_pos_shipment_preserves_its_type_and_can_retry_after_catalog_removal(): void
    {
        $this->arrange();
        Schema::create('transferencias_tipo', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
        });
        DB::table('transferencias_tipo')->insert(['id' => 4, 'descripcion' => 'TEST type']);
        $payload = $this->payload() + ['tipo_id' => '4', 'tipo_nombre' => 'TEST type'];
        $this->signed('enviar', $payload)->assertOk();
        $stored = json_decode(DB::table('pos_transferencias_centrales')->value('datos'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($payload, $stored);
        DB::table('transferencias_tipo')->where('id', 4)->delete();
        $this->signed('enviar', $payload)->assertOk();
        $this->assertSame(1, DB::table('pos_transferencias_centrales')->count());
        $this->assertSame(40, DB::table('lotes')->value('cantidad'));
    }

    public function test_unknown_transfer_type_rejects_the_entire_pos_shipment(): void
    {
        $this->arrange();
        Schema::create('transferencias_tipo', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
        });
        $this->signed('enviar', $this->payload() + ['tipo_id' => '999', 'tipo_nombre' => 'TEST missing'])
            ->assertUnprocessable();
        $this->assertSame(0, DB::table('pos_transferencias_centrales')->count());
    }

    private function cancellation(): array
    {
        return ['version' => 1, 'request_id' => '70000000-0000-4000-8000-000000000001',
            'transferencia_uuid' => $this->payload()['transferencia_uuid'], 'stock_de' => '1',
            'usuario_pos_id' => '7', 'equipo' => 'TEST-POS', 'terminal_nombre' => 'TEST origin-Caja1', 'motivo' => 'TEST cancellation'];
    }

    private function cancellationVote(): array
    {
        return ['version' => 1, 'request_id' => $this->cancellation()['request_id'],
            'transferencia_uuid' => $this->payload()['transferencia_uuid'], 'central_id' => '1', 'stock_para' => '2'];
    }

    private function receipt(): array
    {
        return ['version' => 1, 'request_id' => '40000000-0000-4000-8000-000000000001',
            'transferencia_uuid' => $this->payload()['transferencia_uuid'], 'central_id' => '1', 'stock_para' => '2',
            'usuario_pos_id' => '7', 'equipo' => 'TEST-POS', 'terminal_nombre' => 'TEST destination-Caja1'];
    }

    public function test_cancellation_waits_for_destination_and_preserves_original_payload_on_retries(): void
    {
        $this->arrange();
        $this->signed('enviar', $this->payload())->assertOk();
        $original = DB::table('pos_transferencias_centrales')->value('datos');
        $this->signed('anular', $this->cancellation())->assertOk()->assertJsonPath('data.estado', 'ANULANDO');
        $this->signed('anular', $this->cancellation())->assertOk()->assertJsonPath('data.estado', 'ANULANDO');
        $this->assertDatabaseHas('pos_transferencias_centrales', ['estado' => 'ANULANDO', 'datos' => $original, 'usuario_anula_pos' => 7]);
        $this->signed('confirmar-anulacion', $this->cancellationVote())->assertForbidden();
        DB::table('devices')->where('id', 1)->update(['stock' => 2]);
        $this->signed('listar', [])->assertOk()->assertJsonPath('data.records.0.solicitud_anulacion', $this->cancellation()['request_id']);
        $wrong = $this->cancellationVote();
        $wrong['request_id'] = '70000000-0000-4000-8000-000000000002';
        $this->signed('confirmar-anulacion', $wrong)->assertConflict();
        $this->signed('confirmar-anulacion', $this->cancellationVote())->assertOk()->assertJsonPath('data.estado', 'ANULADA');
        $this->signed('confirmar-anulacion', $this->cancellationVote())->assertOk()->assertJsonPath('data.estado', 'ANULADA');
        $this->signed('recibir', $this->receipt())->assertConflict();
        DB::table('devices')->where('id', 1)->update(['stock' => 1]);
        $this->signed('anular', $this->cancellation())->assertOk()->assertJsonPath('data.estado', 'ANULADA');
        $changed = $this->cancellation();
        $changed['motivo'] = 'TEST changed';
        $this->signed('anular', $changed)->assertConflict();
        $this->assertDatabaseCount('pos_transferencias_centrales', 1);
        $this->assertDatabaseHas('lotes', ['id' => 123, 'cantidad' => 40]);
    }

    public function test_local_receipt_waiting_for_publication_vetoes_cancellation(): void
    {
        $this->arrange();
        $this->signed('enviar', $this->payload())->assertOk();
        $this->signed('anular', $this->cancellation())->assertOk()->assertJsonPath('data.estado', 'ANULANDO');
        DB::table('devices')->where('id', 1)->update(['stock' => 2]);
        $this->signed('recibir', $this->receipt())->assertOk()->assertJsonPath('data.estado', 'RECIBIDA');
        $this->signed('confirmar-anulacion', $this->cancellationVote())->assertOk()->assertJsonPath('data.estado', 'RECIBIDA');
        DB::table('devices')->where('id', 1)->update(['stock' => 1]);
        $this->signed('anular', $this->cancellation())->assertOk()->assertJsonPath('data.estado', 'RECIBIDA');
        $this->assertDatabaseHas('pos_transferencias_centrales', ['estado' => 'RECIBIDA', 'anulada_at' => null]);
    }

    public function test_already_received_transfer_rejects_refund_and_cancel_requires_its_own_permission(): void
    {
        $this->arrange();
        $this->signed('enviar', $this->payload())->assertOk();
        DB::table('devices')->where('id', 1)->update(['stock' => 2]);
        $this->signed('recibir', $this->receipt())->assertOk();
        $this->signed('anular', $this->cancellation())->assertForbidden();
        DB::table('devices')->where('id', 1)->update(['stock' => 1]);
        DB::table('botonespos')->where('codigo', 'transferencia.enviadas.anular')->update(['activo' => false]);
        $this->signed('anular', $this->cancellation())->assertForbidden();
        DB::table('botonespos')->where('codigo', 'transferencia.enviadas.anular')->update(['activo' => true]);
        $this->signed('anular', $this->cancellation())->assertOk()->assertJsonPath('data.estado', 'RECIBIDA');
        $this->assertDatabaseHas('pos_transferencias_centrales', ['estado' => 'RECIBIDA', 'anulada_at' => null]);
    }
}
