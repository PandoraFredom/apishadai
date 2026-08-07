<?php

namespace Tests\Feature\Farma;

use App\Http\Requests\Farma\Transferencias\TransferSendRequest;
use App\Http\Resources\Farma\TransferResource;
use App\Models\Device;
use App\Models\Farma\Transferencia;
use App\Models\User;
use App\Repositories\Farma\TransferRepository;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TransferDomainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->createSchema();
        $this->seedData();
    }

    protected function tearDown(): void
    {
        Schema::dropAllTables();
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_it_sends_a_transfer_with_server_calculated_totals_and_audit_data(): void
    {
        $transfer = $this->repository()->send(1, 1, [
            'tipo' => 1,
            'stock_para' => 2,
            'detalles' => [['lote' => 1, 'cantidad' => 2]],
        ]);

        $this->assertSame(1, $transfer->stock_de);
        $this->assertSame(2, $transfer->stock_para);
        $this->assertSame(1, $transfer->usuario_envia);
        $this->assertNull($transfer->usuario_recibe);
        $this->assertSame(2, $transfer->items);
        $this->assertSame('20.00', $transfer->subtotal);
        $this->assertSame('3.00', $transfer->isvtotal);
        $this->assertSame('23.00', $transfer->total);
        $this->assertSame('ENVIADA', $transfer->estadoDetalle->descripcion);
        $this->assertSame('PENDIENTE', $transfer->estadoRecepcionDetalle->descripcion);
        $this->assertNotNull($transfer->enviado_at);
        $this->assertNull($transfer->recibido_at);
        $this->assertDatabaseHas('trasferencia_detalle', [
            'transferencia_id' => $transfer->id,
            'lote' => 1,
            'cantidad' => 2,
            'subtotal' => 20,
            'isv' => 3,
            'total' => 23,
        ]);
    }

    public function test_only_another_user_at_the_destination_can_receive_it_once(): void
    {
        $transfer = $this->createTransfer();

        foreach ([[1, 1], [7, 2]] as [$stockId, $userId]) {
            try {
                $this->repository()->receive($transfer->id, $stockId, $userId);
                $this->fail('La API permitió una recepción no autorizada.');
            } catch (DomainException) {
                $this->assertDatabaseMissing('transferencias', ['id' => $transfer->id, 'usuario_recibe' => $userId]);
            }
        }

        $received = $this->repository()->receive($transfer->id, 2, 2);

        $this->assertSame(2, $received?->usuario_recibe);
        $this->assertSame('ENVIADA', $received?->estadoDetalle->descripcion);
        $this->assertSame('RECIBIDA', $received?->estadoRecepcionDetalle->descripcion);
        $this->assertNotNull($received?->recibido_at);

        $this->expectException(DomainException::class);
        $this->repository()->receive($transfer->id, 2, 2);
    }

    public function test_list_is_scoped_to_the_current_stock_and_direction(): void
    {
        $transfer = $this->createTransfer();

        $this->assertSame(1, $this->repository()->paginate(1)->total());
        $this->assertSame(1, $this->repository()->paginate(2, 30, ['direccion' => 'recibidas'])->total());
        $this->assertSame(1, $this->repository()->paginate(2, 30, ['estado_recepcion' => 2])->total());
        $this->assertSame(0, $this->repository()->paginate(2, 30, ['direccion' => 'enviadas'])->total());
        $this->assertSame(0, $this->repository()->paginate(7)->total());

        $request = Request::create('/api/farma/transferencias/listar');
        $request->attributes->set('authenticated_device', (new Device)->forceFill(['stock' => 2]));
        $request->setUserResolver(fn (): User => User::query()->findOrFail(2));
        $payload = TransferResource::make($this->repository()->findForStock($transfer->id, 2))->resolve($request);

        $this->assertSame('enviada', $payload['estado']);
        $this->assertSame('pendiente', $payload['estado_recepcion']);
        $this->assertSame('pendiente', $payload['estado_general']);
        $this->assertTrue($payload['puede_recibir']);
        $this->assertSame('Emisor', $payload['usuario_envia']['nombre']);
        $this->assertSame('Destino', $payload['stock_para']['descripcion']);
    }

    public function test_request_rejects_duplicate_lots_and_options_exclude_the_current_stock(): void
    {
        $request = TransferSendRequest::create('/api/farma/transferencias/enviar', 'POST', [
            'tipo' => 1,
            'stock_para' => 2,
            'detalles' => [
                ['lote' => 1, 'cantidad' => 1],
                ['lote' => 1, 'cantidad' => 1],
            ],
        ]);
        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('detalles.0.lote', $validator->errors()->toArray());
        $this->assertSame([2], $this->repository()->options(1)['stocks_destino']->pluck('id')->all());
        $this->assertSame(['PENDIENTE', 'RECIBIDA'], $this->repository()->options(1)['estados_recepcion']->pluck('descripcion')->all());
    }

    private function createTransfer(): Transferencia
    {
        return $this->repository()->send(1, 1, [
            'tipo' => 1,
            'stock_para' => 2,
            'detalles' => [['lote' => 1, 'cantidad' => 1]],
        ]);
    }

    private function repository(): TransferRepository
    {
        return new TransferRepository(new Transferencia);
    }

    private function seedData(): void
    {
        DB::table('stock_estado')->insert([
            ['id' => 1, 'descripcion' => 'ACTIVO'],
            ['id' => 2, 'descripcion' => 'INACTIVO'],
        ]);
        DB::table('stocks')->insert([
            ['id' => 1, 'descripcion' => 'Origen', 'telefono' => '1', 'ubicacion' => 'A', 'estado' => 1],
            ['id' => 2, 'descripcion' => 'Destino', 'telefono' => '2', 'ubicacion' => 'B', 'estado' => 1],
            ['id' => 7, 'descripcion' => 'Inactivo', 'telefono' => '7', 'ubicacion' => 'C', 'estado' => 2],
        ]);
        DB::table('users')->insert([
            ['id' => 1, 'nombre' => 'Emisor', 'name' => 'sender', 'email' => 'sender@example.test', 'password' => 'x'],
            ['id' => 2, 'nombre' => 'Receptor', 'name' => 'receiver', 'email' => 'receiver@example.test', 'password' => 'x'],
        ]);
        DB::table('productos')->insert(['id' => 1, 'codigo' => 'P-1', 'descripcion' => 'Producto']);
        DB::table('lotes')->insert([
            'id' => 1,
            'producto' => 1,
            'lote' => 'L-001',
            'fecha_elab' => '2026-01-01',
            'fecha_exp' => '2028-01-01',
            'cantidad' => 10,
            'costo' => 10,
            'isv' => true,
        ]);
        DB::table('transferencia_estado')->insert([
            ['id' => 1, 'descripcion' => 'ENVIADA'],
            ['id' => 2, 'descripcion' => 'PENDIENTE'],
            ['id' => 3, 'descripcion' => 'RECIBIDA'],
        ]);
        DB::table('transferencias_tipo')->insert(['id' => 1, 'descripcion' => 'TRASLADO ENTRE STOCKS']);
    }

    private function createSchema(): void
    {
        Schema::create('stock_estado', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
            $table->timestamps();
        });
        Schema::create('stocks', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
            $table->string('telefono');
            $table->string('ubicacion');
            $table->integer('estado');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo');
            $table->string('descripcion');
            $table->timestamps();
        });
        Schema::create('lotes', function (Blueprint $table): void {
            $table->id();
            $table->integer('compra')->nullable();
            $table->integer('producto');
            $table->string('lote');
            $table->date('fecha_elab');
            $table->date('fecha_exp');
            $table->integer('cantidad');
            $table->decimal('costo', 10, 2)->nullable();
            $table->boolean('isv')->nullable();
            $table->timestamps();
        });
        Schema::create('transferencia_estado', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
            $table->timestamps();
        });
        Schema::create('transferencias_tipo', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
            $table->timestamps();
        });
        Schema::create('transferencias', function (Blueprint $table): void {
            $table->id();
            $table->integer('tipo');
            $table->integer('stock_de');
            $table->integer('stock_para');
            $table->unsignedBigInteger('usuario_envia');
            $table->unsignedBigInteger('usuario_recibe')->nullable();
            $table->integer('items');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('isvtotal', 8, 2);
            $table->decimal('total', 10, 2);
            $table->integer('estado');
            $table->integer('estado_recepcion');
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('recibido_at')->nullable();
            $table->timestamps();
        });
        Schema::create('trasferencia_detalle', function (Blueprint $table): void {
            $table->id();
            $table->integer('transferencia_id');
            $table->integer('lote');
            $table->integer('cantidad');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('isv', 8, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }
}
