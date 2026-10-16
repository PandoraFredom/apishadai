<?php

namespace Tests\Feature\Farma;

use App\Http\Middleware\DeviceSecurityMiddleware;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\JWTAuthenticationMiddleware;
use App\Http\Middleware\MatchTokenMiddleware;
use App\Models\Farma\TransferenciaTipo;
use App\Models\SyncChange;
use App\Models\User;
use App\Services\Headquarters\OutboxSender;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransferTypeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['modulo_estados', 'vista_estados'] as $table) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->string('descripcion');
            });
            DB::table($table)->insert(['id' => 1, 'descripcion' => 'ACTIVO']);
        }
        Schema::create('modulos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo');
            $table->string('nombre');
            $table->unsignedBigInteger('estado');
            $table->timestamps();
        });
        Schema::create('vistas', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo');
            $table->string('nombre');
            $table->unsignedBigInteger('modulo');
            $table->unsignedBigInteger('estado');
            $table->timestamps();
        });
        Schema::create('actionsvistas', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo');
            $table->string('nombre');
            $table->unsignedBigInteger('vista');
            $table->timestamps();
        });
        Schema::create('permisos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario');
            $table->unsignedBigInteger('modulo');
            $table->unsignedBigInteger('vista');
            $table->unsignedBigInteger('actionvista');
            $table->timestamp('lifetime')->nullable();
        });
        Schema::create('transferencias_tipo', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
            $table->timestamps();
        });
        Schema::create('transferencias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tipo');
        });
        (require database_path('migrations/2026_10_07_000003_register_transfer_type_permissions.php'))->up();
        $this->withoutMiddleware([DeviceSecurityMiddleware::class, IdempotencyMiddleware::class, JWTAuthenticationMiddleware::class, MatchTokenMiddleware::class]);
        $this->actingAs((new User)->forceFill(['id' => 7]));
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_crud_and_server_search(): void
    {
        $this->grantAll();
        $this->postJson('/api/farma/transferencias/tipos/crear', ['descripcion' => ' <b>Devolución</b> '], ['Action-Btn' => 'tftbtn02'])
            ->assertCreated()->assertJsonPath('data.descripcion', 'Devolución');
        $id = TransferenciaTipo::firstOrFail()->id;
        $this->getJson('/api/farma/transferencias/tipos/buscar/'.$id, ['Action-Btn' => 'tftbtn03'])
            ->assertOk()->assertJsonPath('data.descripcion', 'Devolución');
        $this->putJson('/api/farma/transferencias/tipos/actualizar', ['id' => $id, 'descripcion' => 'Traslado'], ['Action-Btn' => 'tftbtn04'])->assertOk();
        TransferenciaTipo::create(['descripcion' => 'Devolución']);
        $this->getJson('/api/farma/transferencias/tipos/listar?search=Traslado&per_page=1', ['Action-Btn' => 'tftbtn01'])
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.descripcion', 'Traslado');
        $this->deleteJson('/api/farma/transferencias/tipos/eliminar/'.$id, [], ['Action-Btn' => 'tftbtn05'])->assertOk();
        $this->getJson('/api/farma/transferencias/tipos/buscar/'.$id, ['Action-Btn' => 'tftbtn03'])->assertNotFound();
    }

    public function test_descriptions_are_required_unique_and_limited(): void
    {
        $this->grantAll();
        TransferenciaTipo::create(['descripcion' => 'Traslado']);
        foreach ([' <b> </b> ', 'Traslado', str_repeat('a', 256)] as $description) {
            $this->postJson('/api/farma/transferencias/tipos/crear', ['descripcion' => $description], ['Action-Btn' => 'tftbtn02'])
                ->assertStatus(422)->assertJsonStructure(['data' => ['errors' => ['descripcion']]]);
        }
        $this->putJson('/api/farma/transferencias/tipos/actualizar', ['id' => 1, 'descripcion' => 'Traslado'], ['Action-Btn' => 'tftbtn04'])->assertOk();
    }

    public function test_used_types_cannot_be_deleted(): void
    {
        $this->grant('tftbtn05');
        $type = TransferenciaTipo::create(['descripcion' => 'Traslado']);
        DB::table('transferencias')->insert(['tipo' => $type->id]);
        $this->deleteJson('/api/farma/transferencias/tipos/eliminar/'.$type->id, [], ['Action-Btn' => 'tftbtn05'])->assertStatus(409);
        $this->assertNotNull($type->fresh());
    }

    public function test_each_route_requires_its_own_permission_and_action_header(): void
    {
        $this->grant('tftbtn01');
        $this->getJson('/api/farma/transferencias/tipos/listar', ['Action-Btn' => 'tftbtn02'])->assertForbidden();
        $this->getJson('/api/farma/transferencias/tipos/listar', ['Action-Btn' => 'tftbtn01'])->assertOk();
        $this->postJson('/api/farma/transferencias/tipos/crear', ['descripcion' => 'Traslado'], ['Action-Btn' => 'tftbtn01'])->assertForbidden();
        $this->getJson('/api/farma/transferencias/tipos/buscar/1', ['Action-Btn' => 'tftbtn01'])->assertForbidden();
        $this->putJson('/api/farma/transferencias/tipos/actualizar', ['id' => 1, 'descripcion' => 'Traslado'], ['Action-Btn' => 'tftbtn01'])->assertForbidden();
        $this->deleteJson('/api/farma/transferencias/tipos/eliminar/1', [], ['Action-Btn' => 'tftbtn01'])->assertForbidden();
        $this->assertSame(0, TransferenciaTipo::count());
    }

    public function test_expired_other_user_and_inactive_permissions_are_denied(): void
    {
        $this->grant('tftbtn01');
        foreach ([['usuario' => 8], ['usuario' => 7, 'lifetime' => now()->subMinute()]] as $values) {
            DB::table('permisos')->update($values);
            $this->getJson('/api/farma/transferencias/tipos/listar', ['Action-Btn' => 'tftbtn01'])->assertForbidden();
        }
        DB::table('permisos')->update(['lifetime' => null]);
        foreach (['modulo_estados', 'vista_estados'] as $table) {
            DB::table($table)->where('id', 1)->update(['descripcion' => 'INACTIVO']);
            $this->getJson('/api/farma/transferencias/tipos/listar', ['Action-Btn' => 'tftbtn01'])->assertForbidden();
            DB::table($table)->where('id', 1)->update(['descripcion' => 'ACTIVO']);
        }
    }

    public function test_migration_is_idempotent_and_does_not_assign_permissions(): void
    {
        (require database_path('migrations/2026_10_07_000003_register_transfer_type_permissions.php'))->up();
        $this->assertSame(1, DB::table('vistas')->where('codigo', 'tft')->count());
        $this->assertSame(5, DB::table('actionsvistas')->count());
        $this->assertSame(0, DB::table('permisos')->count());
    }

    public function test_crud_records_and_delivers_all_changes_to_headquarters(): void
    {
        $this->grantAll();
        (require database_path('migrations/2026_10_01_000024_create_sync_changes_table.php'))->up();
        (require database_path('migrations/2026_10_01_000030_create_headquarters_outbox.php'))->up();
        Schema::table('sync_entity_versions', fn (Blueprint $table) => $table->json('stock_ids')->nullable());
        Schema::table('sync_changes', function (Blueprint $table): void {
            $table->json('stock_ids')->nullable();
            $table->json('removed_stock_ids')->nullable();
        });
        config(['headquarters.publishing_enabled' => true, 'headquarters.send_immediately' => false,
            'headquarters.url' => 'https://hq.example.test/api', 'headquarters.secret' => str_repeat('a', 64)]);
        Http::preventStrayRequests();
        Http::fake(['hq.example.test/api/internal/apishadai/changes' => fn ($request) => Http::response([
            'data' => ['accepted' => array_column($request['changes'], 'uuid')],
        ])]);
        $this->postJson('/api/farma/transferencias/tipos/crear', ['descripcion' => 'Traslado'], ['Action-Btn' => 'tftbtn02'])->assertCreated();
        $id = TransferenciaTipo::firstOrFail()->id;
        $this->putJson('/api/farma/transferencias/tipos/actualizar', ['id' => $id, 'descripcion' => 'Devolución'], ['Action-Btn' => 'tftbtn04'])->assertOk();
        $this->deleteJson('/api/farma/transferencias/tipos/eliminar/'.$id, [], ['Action-Btn' => 'tftbtn05'])->assertOk();
        $changes = SyncChange::orderBy('id')->get();
        $this->assertSame(['CREATE', 'UPDATE', 'DELETE'], $changes->pluck('action')->all());
        $this->assertSame([1, 2, 3], $changes->pluck('version')->all());
        $this->assertSame('transferencias_tipo', $changes[0]->entity);
        $this->assertSame('Devolución', $changes[1]->payload['descripcion']);
        $this->assertNull($changes[2]->payload);
        $this->assertSame([], $changes[0]->stock_ids);
        $this->assertSame(['sent' => 3, 'pending' => 0], app(OutboxSender::class)->send());
    }

    private function grantAll(): void
    {
        foreach (range(1, 5) as $number) {
            $this->grant('tftbtn0'.$number);
        }
    }

    private function grant(string $action): void
    {
        DB::table('permisos')->insert([
            'usuario' => 7,
            'modulo' => DB::table('modulos')->where('codigo', 'frm')->value('id'),
            'vista' => DB::table('vistas')->where('codigo', 'tft')->value('id'),
            'actionvista' => DB::table('actionsvistas')->where('codigo', $action)->value('id'),
            'lifetime' => null,
        ]);
    }
}
