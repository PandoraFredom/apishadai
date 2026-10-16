<?php

namespace Tests\Feature\Pos;

use App\Http\Middleware\DeviceSecurityMiddleware;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\JWTAuthenticationMiddleware;
use App\Http\Middleware\MatchTokenMiddleware;
use App\Models\Pos\UsuarioPos;
use App\Models\User;
use App\Services\Headquarters\PosLocalCredentials;
use Database\Seeders\TransferPermissionCatalogSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PosManagementTest extends TestCase
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
        (require database_path('migrations/2026_10_01_000022_create_clientepos_table.php'))->up();
        (require database_path('migrations/2026_10_01_000023_register_clientepos_permissions.php'))->up();
        $this->withoutMiddleware([DeviceSecurityMiddleware::class, IdempotencyMiddleware::class, JWTAuthenticationMiddleware::class, MatchTokenMiddleware::class]);
        $this->actingAs((new User)->forceFill(['id' => 7]));

        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32)), 'headquarters.publishing_enabled' => true, 'headquarters.send_immediately' => false]);
        Schema::create('stocks', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('descripcion');
            $table->string('telefono')->nullable();
            $table->string('ubicacion')->nullable();
            $table->integer('estado')->nullable();
            $table->timestamps();
        });
        DB::table('stocks')->insert(['id' => 1, 'descripcion' => 'TEST stock']);
        (require database_path('migrations/2026_10_04_000001_create_pos_central_management.php'))->up();
        (require database_path('migrations/2026_10_04_000002_register_pos_management_views.php'))->up();
        foreach (['2026_10_01_000024_create_sync_changes_table.php', '2026_10_01_000030_create_headquarters_outbox.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::table('sync_entity_versions', fn (Blueprint $table) => $table->json('stock_ids')->nullable());
        Schema::table('sync_changes', function (Blueprint $table): void {
            $table->json('stock_ids')->nullable();
            $table->json('removed_stock_ids')->nullable();
        });
        (require database_path('migrations/2026_10_05_000001_create_pos_permission_hierarchy.php'))->up();
        Schema::create('tipos_tiempo', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->integer('cantidad');
            $table->string('unidad');
        });
        DB::table('tipos_tiempo')->insert(['id' => 1, 'nombre' => 'TEST permanente', 'cantidad' => 0, 'unidad' => 'permanente']);
        (require database_path('migrations/2026_10_05_000002_pos_permissions_belong_to_users.php'))->up();
        (require database_path('migrations/2026_10_05_000003_global_pos_accounts.php'))->up();
        (require database_path('migrations/2026_10_08_160932_add_invoice_seller_to_pos_users.php'))->up();
    }


    public function test_sales_report_permissions_are_visible_delegable_and_published_globally(): void
    {
        $lastChange = DB::table('sync_changes')->max('id') ?? 0;
        $migration = require database_path('migrations/2026_10_13_000001_pos_sales_report_permissions.php');
        $migration->up();
        $module = DB::table('modulospos')->where('codigo', 'administracion')->first();
        $view = DB::table('vistaspos')->where('codigo', 'administracion.reporte.ventas')->first();
        $changes = DB::table('sync_changes')->where('id', '>', $lastChange)->orderBy('id')->get();
        $this->assertSame(['modulospos', 'vistaspos', 'botonespos', 'botonespos'], $changes->pluck('entity')->all());
        foreach ($changes as $change) $this->assertSame([], json_decode($change->stock_ids, true));
        $this->assertDatabaseCount('permisosusuariospos', 0);
        $read = $this->permit('uspbtn03');
        $this->getJson('/api/pos/permisos/listarmodulos', $read)->assertOk()->assertJsonFragment(['nombre' => 'Administración']);
        $this->getJson('/api/pos/permisos/listarVistas/'.$module->id, $read)->assertOk()->assertJsonFragment(['nombre' => 'Reportes · Reporte de ventas']);
        $this->getJson('/api/pos/permisos/listarAcciones/'.$view->id, $read)->assertOk()->assertJsonFragment(['nombre' => 'Ver reporte de ventas'])->assertJsonFragment(['nombre' => 'Filtrar reporte de ventas']);
        $role = DB::table('rolespos')->insertGetId(['nombre' => 'TEST report', 'nivel' => 1, 'permisos' => '[]', 'activo' => true]);
        $this->syntheticUser(91, $role);$this->syntheticUser(92, $role);
        foreach (['reporte.ventas.ver', 'reporte.ventas.filtrar'] as $code) {
            $button = DB::table('botonespos')->where('codigo', $code)->first();
            $this->postJson('/api/pos/permisos/crearpermiso', [
                'usuario' => ['id' => 91], 'modulo' => ['id' => $module->id], 'vista' => ['id' => $view->id],
                'actionvista' => ['id' => $button->id], 'tipo_tiempo' => ['id' => 1],
            ], $this->permit('uspbtn04'))->assertCreated();
        }
        $group = $this->getJson('/api/pos/permisos/spermisos/91', $read)->assertOk()->json('data');
        $this->assertSame('administracion', $group[0]['modulo_l']['codigo']);
        $this->assertSame('administracion.reporte.ventas', $group[0]['vistas_l'][0]['vistai']['codigo']);
        $this->assertSame([['codigo' => 'reporte.ventas.ver'], ['codigo' => 'reporte.ventas.filtrar']], $group[0]['vistas_l'][0]['accionesi']);
        $this->getJson('/api/pos/permisos/spermisos/92', $read)->assertOk()->assertJsonPath('data', []);
        $migration->up();$this->assertDatabaseCount('permisosusuariospos', 2);
    }

    public function test_purchase_actions_are_independent_delegable_and_repeatable_without_automatic_grants(): void
    {
        $migration = require database_path('migrations/2026_10_11_000003_register_pos_purchase_permissions.php');
        $module = DB::table('modulospos')->where('codigo', 'compras')->first();
        $view = DB::table('vistaspos')->where('codigo', 'compras.documentos')->first();
        $lastChange = DB::table('sync_changes')->max('id') ?? 0;
        $migration->up();
        $codes = ['compra.crear', 'compra.modificar', 'compra.ver', 'compra.eliminar', 'compra.recibir'];
        $buttons = DB::table('botonespos')->where('vista_id', $view->id)->orderBy('id')->get();
        $this->assertSame($codes, $buttons->pluck('codigo')->all());
        $this->assertTrue($buttons->every(fn ($button) => (bool) $button->activo));
        $this->assertDatabaseCount('permisosusuariospos', 0);
        $this->assertDatabaseCount('permisospos', 0);
        $changes = DB::table('sync_changes')->where('id', '>', $lastChange)->orderBy('id')->get();
        $this->assertSame(['modulospos', 'vistaspos', 'botonespos', 'botonespos', 'botonespos', 'botonespos', 'botonespos'], $changes->pluck('entity')->all());
        foreach ($changes as $change) {
            $this->assertSame([], json_decode($change->stock_ids, true));
        }
        $migration->up();
        $this->assertSame($buttons->pluck('id', 'codigo')->all(), DB::table('botonespos')->where('vista_id', $view->id)->orderBy('id')->pluck('id', 'codigo')->all());
        $this->assertDatabaseCount('permisosusuariospos', 0);

        $role = DB::table('rolespos')->insertGetId(['nombre' => 'TEST purchases', 'nivel' => 1, 'permisos' => '[]', 'activo' => true]);
        $this->syntheticUser(91, $role);
        $this->syntheticUser(92, $role);
        $read = $this->permit('uspbtn03');
        $this->getJson('/api/pos/permisos/listarAcciones/'.$view->id, $read)->assertOk()->assertJsonCount(5, 'data');
        foreach ($buttons as $button) {
            $this->postJson('/api/pos/permisos/crearpermiso', [
                'usuario' => ['id' => 91], 'modulo' => ['id' => $module->id], 'vista' => ['id' => $view->id],
                'actionvista' => ['id' => $button->id], 'tipo_tiempo' => ['id' => 1],
            ], $this->permit('uspbtn04'))->assertCreated();
        }
        $group = $this->getJson('/api/pos/permisos/spermisos/91', $read)->assertOk()->json('data');
        $this->assertSame('compras', $group[0]['modulo_l']['codigo']);
        $this->assertSame('compras.documentos', $group[0]['vistas_l'][0]['vistai']['codigo']);
        $this->assertCount(5, $group[0]['vistas_l'][0]['accionesi']);
        $this->getJson('/api/pos/permisos/spermisos/92', $read)->assertOk()->assertJsonPath('data', []);
        $grants = DB::table('permisosusuariospos')->orderBy('id')->get()->toArray();
        $migration->up();
        $this->assertEquals($grants, DB::table('permisosusuariospos')->orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('permisospos', 0);
    }

    public function test_purchase_catalog_preserves_disabled_metadata(): void
    {
        $migration = require database_path('migrations/2026_10_11_000003_register_pos_purchase_permissions.php');
        $migration->up();
        $button = DB::table('botonespos')->where('codigo', 'compra.recibir')->first();
        DB::table('botonespos')->where('id', $button->id)->update(['activo' => false]);
        DB::table('vistaspos')->where('codigo', 'compras.documentos')->update(['activo' => false]);
        DB::table('modulospos')->where('codigo', 'compras')->update(['activo' => false]);
        $migration->up();
        $this->assertDatabaseHas('botonespos', ['id' => $button->id, 'activo' => false]);
        $this->assertDatabaseHas('vistaspos', ['codigo' => 'compras.documentos', 'activo' => false]);
        $this->assertDatabaseHas('modulospos', ['codigo' => 'compras', 'activo' => false]);
    }

    public function test_purchase_catalog_rolls_back_actions_and_publications_for_crossed_parents(): void
    {
        $view = DB::table('vistaspos')->where('codigo', 'pos.facturas')->first();
        DB::table('botonespos')->insert(['codigo' => 'compra.recibir', 'nombre' => 'TEST crossed action', 'vista_id' => $view->id, 'activo' => true]);
        $before = DB::table('botonespos')->orderBy('id')->get()->toArray();
        $changes = DB::table('sync_changes')->count();
        try {
            (require database_path('migrations/2026_10_11_000003_register_pos_purchase_permissions.php'))->up();
            $this->fail('Una acción vinculada a otra vista debe rechazarse.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('otra vista', $exception->getMessage());
        }
        $this->assertEquals($before, DB::table('botonespos')->orderBy('id')->get()->toArray());
        $this->assertSame($changes, DB::table('sync_changes')->count());
        DB::table('vistaspos')->where('codigo', 'compras.documentos')->update(['modulo_id' => $view->modulo_id]);
        try {
            (require database_path('migrations/2026_10_11_000003_register_pos_purchase_permissions.php'))->up();
            $this->fail('Una vista vinculada a otro módulo debe rechazarse.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('otro módulo', $exception->getMessage());
        }
        $this->assertEquals($before, DB::table('botonespos')->orderBy('id')->get()->toArray());
        $this->assertSame($changes, DB::table('sync_changes')->count());
    }


    public function test_invoice_view_action_is_available_separately_without_automatic_grants(): void
    {
        $migration = require database_path('migrations/2026_10_09_000001_add_invoice_view_permission.php');
        $before = DB::table('permisosusuariospos')->count();
        $migration->up();
        $view = DB::table('vistaspos')->where('codigo', 'pos.facturas')->first();
        $button = DB::table('botonespos')->where('codigo', 'factura.ver')->first();
        $this->assertSame($view->id, $button->vista_id);
        $this->assertSame('Ver facturas', $button->nombre);
        $this->assertTrue((bool) $button->activo);
        $id = $button->id;
        $migration->up();
        $this->assertSame($id, DB::table('botonespos')->where('codigo', 'factura.ver')->value('id'));
        $this->assertSame($before, DB::table('permisosusuariospos')->count());
        $this->assertDatabaseCount('permisospos', 0);
        $this->getJson('/api/pos/permisos/listarAcciones/'.$view->id, $this->permit('uspbtn03'))
            ->assertOk()->assertJsonFragment(['codigo' => 'factura.ver'])
            ->assertJsonFragment(['codigo' => 'factura.imprimir']);
        $publication = DB::table('sync_changes')->where('entity', 'botonespos')->where('entity_id', $id)->latest('id')->first();
        $this->assertSame([], json_decode($publication->stock_ids, true));
    }
    public function test_transfer_catalog_is_delegable_and_repeatable_without_automatic_grants(): void
    {
        $seeder = new TransferPermissionCatalogSeeder;
        (require database_path('migrations/2026_10_07_000002_complete_transfer_action_permissions.php'))->up();
        $before = DB::table('botonespos')->orderBy('id')->pluck('codigo', 'id')->all();
        $changeCount = DB::table('sync_changes')->count();
        $seeder->run();
        $this->assertSame($before, DB::table('botonespos')->orderBy('id')->pluck('codigo', 'id')->all());
        $this->assertSame($changeCount, DB::table('sync_changes')->count());
        $this->assertDatabaseCount('permisosusuariospos', 0);
        $this->assertDatabaseCount('permisospos', 0);

        $read = $this->permit('uspbtn03');
        $write = $this->permit('uspbtn04');
        $module = DB::table('modulospos')->where('codigo', 'transferencias')->first();
        $view = DB::table('vistaspos')->where('codigo', 'transferencias.enviadas')->first();
        $this->getJson('/api/pos/permisos/listarmodulos', $read)->assertOk()->assertJsonFragment(['codigo' => 'transferencias']);
        $this->getJson('/api/pos/permisos/listarVistas/'.$module->id, $read)->assertOk()->assertJsonFragment(['codigo' => 'transferencias.enviadas'])->assertJsonFragment(['codigo' => 'transferencias.recibidas'])->assertJsonMissing(['codigo' => 'transferencias.documentos']);
        $actions = $this->getJson('/api/pos/permisos/listarAcciones/'.$view->id, $read)->assertOk();
        $role = DB::table('rolespos')->insertGetId(['nombre' => 'TEST transfers', 'nivel' => 1, 'permisos' => '[]', 'activo' => true]);
        $this->syntheticUser(91, $role);
        $this->syntheticUser(92, $role);
        foreach (['transferencia.enviadas.ver', 'transferencia.enviadas.filtrar', 'transferencia.enviadas.enviar', 'transferencia.enviadas.anular', 'transferencia.enviadas.imprimir', 'transferencia.recibidas.ver', 'transferencia.recibidas.filtrar', 'transferencia.recibidas.recibir'] as $code) {
            $button = DB::table('botonespos')->where('codigo', $code)->first();
            $this->postJson('/api/pos/permisos/crearpermiso', [
                'usuario' => ['id' => 91], 'modulo' => ['id' => $module->id], 'vista' => ['id' => $button->vista_id],
                'actionvista' => ['id' => $button->id], 'tipo_tiempo' => ['id' => 1],
            ], $write)->assertCreated();
        }
        $group = $this->getJson('/api/pos/permisos/spermisos/91', $read)->assertOk()->json('data');
        $this->assertSame('transferencias', $group[0]['modulo_l']['codigo']);
        $this->assertSame('transferencias.enviadas', $group[0]['vistas_l'][0]['vistai']['codigo']);
        $this->assertCount(5, $group[0]['vistas_l'][0]['accionesi']);
        $this->assertSame('transferencias.recibidas', $group[0]['vistas_l'][1]['vistai']['codigo']);
        $this->assertCount(3, $group[0]['vistas_l'][1]['accionesi']);
        $this->getJson('/api/pos/permisos/spermisos/92', $read)->assertOk()->assertJsonPath('data', []);
        $this->assertDatabaseCount('permisospos', 0);
        $publication = DB::table('sync_changes')->where('entity', 'botonespos')->where('entity_id', DB::table('botonespos')->where('codigo', 'transferencia.enviadas.ver')->value('id'))->first();
        $this->assertSame('CREATE', $publication->action);
        $this->assertSame([], json_decode($publication->stock_ids, true));
    }

    public function test_split_transfer_catalog_keeps_old_assignments_without_granting_new_views(): void
    {
        $legacy = DB::table('vistaspos')->where('codigo', 'transferencias.documentos')->first();
        $button = DB::table('botonespos')->where('codigo', 'transferencia.enviar')->first();
        $role = DB::table('rolespos')->insertGetId(['nombre' => 'TEST history', 'nivel' => 1, 'permisos' => '[]', 'activo' => true]);
        $this->syntheticUser(99, $role);
        $assignment = DB::table('permisosusuariospos')->insertGetId([
            'usuario' => 99, 'modulo' => $legacy->modulo_id, 'vista' => $legacy->id,
            'actionvista' => $button->id, 'tipo_tiempo' => 1,
        ]);
        (require database_path('migrations/2026_10_07_000004_split_pos_transfer_views.php'))->up();
        $this->assertDatabaseHas('permisosusuariospos', ['id' => $assignment, 'vista' => $legacy->id, 'actionvista' => $button->id]);
        $this->assertDatabaseHas('vistaspos', ['id' => $legacy->id, 'activo' => false]);
        $this->assertDatabaseCount('permisosusuariospos', 1);
        $this->assertSame($button->id, DB::table('botonespos')->where('codigo', 'transferencia.enviar')->value('id'));
        $this->getJson('/api/pos/permisos/spermisos/99', $this->permit('uspbtn03'))->assertOk()->assertJsonPath('data', []);
    }

    public function test_transfer_catalog_preserves_disabled_actions_and_rejects_crossed_parents(): void
    {
        (new TransferPermissionCatalogSeeder)->run();
        $button = DB::table('botonespos')->where('codigo', 'transferencia.enviadas.enviar')->first();
        DB::table('botonespos')->where('id', $button->id)->update(['activo' => false]);
        $seeder = new TransferPermissionCatalogSeeder;
        $seeder->run();
        $this->assertDatabaseHas('botonespos', ['id' => $button->id, 'activo' => false]);
        DB::table('vistaspos')->where('codigo', 'transferencias.documentos')->update([
            'modulo_id' => DB::table('modulospos')->where('codigo', 'pos')->value('id'),
        ]);
        $changeCount = DB::table('sync_changes')->count();
        try {
            $seeder->run();
            $this->fail('Una jerarquía incompatible debe rechazarse.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('otro módulo', $exception->getMessage());
        }
        $this->assertSame($changeCount, DB::table('sync_changes')->count());
    }

    public function test_rejects_ambiguous_or_non_list_role_assignments_without_writes(): void
    {
        $headers = $this->permit('rspbtn02');
        foreach ([['permisos' => [], 'botones' => []], ['permisos' => ['configurar'], 'botones' => []], ['permisos' => ['named' => 'configurar']]] as $assignments) {
            $this->postJson('/api/pos/roles/crear', ['nombre' => 'TEST inválido', 'nivel' => 1, 'activo' => true] + $assignments, $headers)->assertUnprocessable();
        }
        $this->assertDatabaseCount('rolespos', 0);
        $this->assertDatabaseCount('permisospos', 0);
    }

    private function syntheticUser(int $id, int $role): void
    {
        DB::table('usuariospos')->insert(['id' => $id, 'rol_id' => $role, 'nombre' => 'TEST operador '.$id,
            'usuario_lookup' => hash('sha256', 'synthetic'.$id), 'credenciales' => json_encode(PosLocalCredentials::create('synthetic'.$id, 'Synthetic-password-2026')), 'activo' => true]);
    }

    private function assignment(int $user): array
    {
        $button = DB::table('botonespos')->where('codigo', 'configurar')->first();
        $view = DB::table('vistaspos')->where('id', $button->vista_id)->first();

        return ['usuario' => ['id' => $user], 'modulo' => ['id' => $view->modulo_id], 'vista' => ['id' => $view->id],
            'actionvista' => ['id' => $button->id], 'tipo_tiempo' => ['id' => 1]];
    }

    public function test_user_permissions_follow_portal_format_without_role_inheritance_and_publish_globally(): void
    {
        $write = $this->permit('uspbtn04');
        $read = $this->permit('uspbtn03');
        $roleWrite = $this->permit('rspbtn04');
        $role = $this->postJson('/api/pos/roles/crear', ['nombre' => 'TEST nivel', 'nivel' => 2, 'activo' => true], $this->permit('rspbtn02'))
            ->assertCreated()->assertJsonPath('data.nivel', 2)->assertJsonMissingPath('data.permisos')->json('data.id');
        $this->syntheticUser(91, $role);
        $this->syntheticUser(92, $role);
        $portalCount = DB::table('permisos')->count();
        $this->postJson('/api/pos/permisos/crearpermiso', $this->assignment(91), $write)->assertCreated();
        $group = [['modulo_l' => ['codigo' => 'configuracion'], 'vistas_l' => [['vistai' => ['codigo' => 'configuracion.establecimiento'], 'accionesi' => [['codigo' => 'configurar']]]]]];
        $this->getJson('/api/pos/permisos/spermisos/91', $read)->assertOk()->assertJsonPath('data', $group);
        $this->getJson('/api/pos/permisos/spermisos/92', $read)->assertOk()->assertJsonPath('data', []);
        $this->putJson('/api/pos/roles/actualizar/'.$role, ['nombre' => 'TEST nivel', 'nivel' => 8, 'activo' => true], $roleWrite)->assertOk();
        $this->getJson('/api/pos/permisos/spermisos/91', $read)->assertJsonPath('data', $group);
        $this->getJson('/api/pos/permisos/listarpermisos/91', $read)->assertJsonPath('data.0.usuario', 91)->assertJsonPath('data.0.actionvista.nombre', 'Guardar configuración');
        $change = DB::table('sync_changes')->where('entity', 'permisosusuariospos')->first();
        $payload = json_decode($change->payload, true);
        $this->assertSame(91, $payload['usuario']);
        $this->assertArrayNotHasKey('stock_id', $payload);
        $this->assertSame([], json_decode($change->stock_ids, true));
        $this->assertArrayNotHasKey('rol_id', $payload);
        $this->assertSame($portalCount, DB::table('permisos')->count());
        $id = DB::table('permisosusuariospos')->value('id');
        $this->deleteJson('/api/pos/permisos/eliminarpermiso/'.$id, [], $write)->assertOk();
        $this->getJson('/api/pos/permisos/spermisos/91', $read)->assertJsonPath('data', []);
        $this->assertSame('DELETE', DB::table('sync_changes')->where('entity', 'permisosusuariospos')->orderByDesc('id')->value('action'));
    }

    public function test_rejects_crossed_or_inactive_pos_hierarchy_and_expired_permissions(): void
    {
        $role = DB::table('rolespos')->insertGetId(['nombre' => 'TEST nivel', 'nivel' => 1, 'permisos' => '[]', 'activo' => 1]);
        $this->syntheticUser(91, $role);
        $data = $this->assignment(91);
        $write = $this->permit('uspbtn04');
        $read = $this->permit('uspbtn03');
        $bad = $data;
        $bad['modulo']['id'] = DB::table('modulospos')->where('id', '!=', $data['modulo']['id'])->value('id');
        $this->postJson('/api/pos/permisos/crearpermiso', $bad, $write)->assertUnprocessable();
        $bad = $data;
        $bad['actionvista']['id'] = 999999;
        $this->postJson('/api/pos/permisos/crearpermiso', $bad, $write)->assertUnprocessable();
        DB::table('botonespos')->where('id', $data['actionvista']['id'])->update(['activo' => 0]);
        $this->postJson('/api/pos/permisos/crearpermiso', $data, $write)->assertUnprocessable();
        DB::table('botonespos')->where('id', $data['actionvista']['id'])->update(['activo' => 1]);
        $this->postJson('/api/pos/permisos/crearpermiso', $data, $write)->assertCreated();
        $this->postJson('/api/pos/permisos/crearpermiso', $data, $write)->assertConflict();
        DB::table('permisosusuariospos')->update(['lifetime' => now()->subSecond()->timestamp]);
        $this->getJson('/api/pos/permisos/spermisos/91', $read)->assertJsonPath('data', []);
        $this->assertDatabaseCount('permisosusuariospos', 1);
    }

    public function test_migration_copies_existing_access_once_and_new_users_do_not_inherit_it(): void
    {
        (require database_path('migrations/2026_10_05_000002_pos_permissions_belong_to_users.php'))->down();
        $role = DB::table('rolespos')->insertGetId(['nombre' => 'TEST anterior', 'permisos' => '["configurar"]', 'activo' => 1]);
        $this->syntheticUser(91, $role);
        $data = $this->assignment(91);
        DB::table('permisospos')->insert(['rol_id' => $role, 'modulo_id' => $data['modulo']['id'], 'vista_id' => $data['vista']['id'], 'boton_id' => $data['actionvista']['id'], 'activo' => 1]);
        (require database_path('migrations/2026_10_05_000002_pos_permissions_belong_to_users.php'))->up();
        $this->syntheticUser(92, $role);
        $this->assertDatabaseHas('permisosusuariospos', ['usuario' => 91, 'actionvista' => $data['actionvista']['id']]);
        $this->assertDatabaseMissing('permisosusuariospos', ['usuario' => 92]);
        $this->assertDatabaseHas('rolespos', ['id' => $role, 'nivel' => 0]);
    }

    public function test_global_migration_preserves_verifiers_and_ids_and_rejects_cross_stock_name_collisions(): void
    {
        Schema::table('usuariospos', function (Blueprint $table): void {
            $table->dropUnique(['usuario_lookup']);
            $table->integer('stock_id')->nullable();
            $table->foreign('stock_id')->references('id')->on('stocks');
            $table->text('secreto')->nullable();
            $table->unique(['stock_id', 'usuario_lookup']);
        });
        Schema::create('clavespos', function (Blueprint $table): void {
            $table->id();
        });
        DB::table('stocks')->insert(['id' => 2, 'descripcion' => 'TEST otro stock']);
        $role = DB::table('rolespos')->insertGetId(['nombre' => 'TEST migración', 'nivel' => 2, 'permisos' => '[]', 'activo' => true]);
        $credentials = PosLocalCredentials::create('test.old', 'Synthetic-password-2026');
        $secret = ['usuario' => 'test.old', 'purpose' => 'ShPOS', 'hash' => $credentials['hash'], 'salt' => $credentials['salt'], 'iterations' => $credentials['iterations']];
        foreach ([91 => 1, 92 => 2] as $id => $stock) {
            DB::table('usuariospos')->insert(['id' => $id, 'stock_id' => $stock, 'rol_id' => $role, 'nombre' => 'TEST migración',
                'usuario_lookup' => hash('sha256', 'legacy'.$id), 'secreto' => Crypt::encryptString(json_encode($secret)), 'credenciales' => '{}', 'activo' => true]);
        }
        $migration = require database_path('migrations/2026_10_05_000003_global_pos_accounts.php');
        try {
            $migration->up();
            $this->fail('La migración debe rechazar cuentas repetidas entre stocks.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('repetidos entre stocks', $exception->getMessage());
        }
        $this->assertTrue(Schema::hasColumn('usuariospos', 'secreto'));
        $this->assertTrue(Schema::hasTable('clavespos'));
        $secret['usuario'] = 'test.other';
        DB::table('usuariospos')->where('id', 92)->update(['secreto' => Crypt::encryptString(json_encode($secret))]);

        $migration->up();

        $this->assertSame([91, 92], UsuarioPos::orderBy('id')->pluck('id')->all());
        $this->assertSame($credentials['hash'], UsuarioPos::findOrFail(91)->credenciales['hash']);
        $this->assertSame($credentials['salt'], UsuarioPos::findOrFail(91)->credenciales['salt']);
        $this->assertSame($credentials['iterations'], UsuarioPos::findOrFail(91)->credenciales['iterations']);
        $this->assertSame($credentials['hash'], UsuarioPos::findOrFail(92)->credenciales['hash']);
        $this->assertSame('test.other', base64_decode(UsuarioPos::findOrFail(92)->credenciales['usuario'], true));
        $this->assertFalse(Schema::hasColumn('usuariospos', 'stock_id'));
        $this->assertFalse(Schema::hasTable('clavespos'));
        $this->assertSame([], json_decode(DB::table('sync_changes')->where('entity', 'usuariospos')->value('stock_ids'), true));
    }

    private function permit(string $code): array
    {
        $action = DB::table('actionsvistas')->where('codigo', $code)->first();
        DB::table('permisos')->insert(['usuario' => 7, 'modulo' => DB::table('modulos')->where('codigo', 'pos')->value('id'), 'vista' => $action->vista, 'actionvista' => $action->id, 'lifetime' => null]);

        return ['Action-Btn' => $code];
    }

    public function test_global_pos_credentials_use_base64_verifiers_preserve_updates_and_never_become_api_accounts(): void
    {
        $role = $this->postJson('/api/pos/roles/crear', ['nombre' => 'TEST configurador', 'nivel' => 2, 'activo' => true], $this->permit('rspbtn02'))->assertCreated()->json('data.id');
        $data = ['vendedor' => 'TEST vendedor', 'nombre' => 'TEST José', 'rol_id' => $role, 'usuario' => 'test.operator', 'password' => 'Test!6', 'activo' => true];
        $headers = $this->permit('uspbtn02');
        $this->postJson('/api/pos/usuarios/crear', array_diff_key($data, ['vendedor' => true]), $headers)->assertUnprocessable();
        $this->postJson('/api/pos/usuarios/crear', [...$data, 'password' => 'short'], $headers)->assertUnprocessable();
        $this->assertDatabaseCount('usuariospos', 0);
        $response = $this->postJson('/api/pos/usuarios/crear', $data, $headers)->assertCreated();
        $user = UsuarioPos::firstOrFail();
        $this->getJson('/api/pos/usuarios/listar', $this->permit('uspbtn01'))->assertOk()
            ->assertJsonPath('data.0.rol_nombre', 'TEST configurador')->assertJsonPath('data.0.nivel', 2)
            ->assertJsonMissingPath('data.0.stock_id')->assertJsonMissingPath('data.0.credenciales');
        $this->assertNotInstanceOf(Authenticatable::class, $user);
        $this->assertStringNotContainsString('credenciales', $response->getContent());
        $change = DB::table('sync_changes')->where('entity', 'usuariospos')->first();
        $payload = json_decode($change->payload, true);
        $this->assertSame([], json_decode($change->stock_ids, true));
        $this->assertSame('TEST José', base64_decode($payload['nombre'], true));
        $this->assertArrayNotHasKey('stock_id', $payload);
        $this->assertSame('TEST vendedor', $payload['vendedor']);
        $this->assertSame('TEST vendedor', $response->json('data.vendedor'));
        $credentials = $payload['credenciales'];
        $this->assertSame(2, $credentials['version']);
        $this->assertSame('test.operator', base64_decode($credentials['usuario'], true));
        $this->assertSame(hash_pbkdf2('sha512', $data['password'], base64_decode($credentials['salt']), 210000, 64, true), base64_decode($credentials['hash']));
        $this->assertStringNotContainsString($data['password'], $change->payload);
        $this->assertArrayNotHasKey('key_id', $credentials);
        $this->postJson('/api/pos/usuarios/crear', $data, ['Action-Btn' => 'uspbtn02'])->assertUnprocessable();
        $this->assertDatabaseCount('usuariospos', 1);
        unset($data['password'], $data['usuario']);
        $data['nombre'] = 'TEST actualizado';
        $data['vendedor'] = 'TEST vendedor actualizado';
        $this->putJson('/api/pos/usuarios/actualizar/'.$user->id, $data, $this->permit('uspbtn04'))->assertOk();
        $this->assertSame($credentials, $user->fresh()->credenciales);
        $this->assertSame('TEST vendedor actualizado', $user->fresh()->vendedor);
        $data['usuario'] = 'test.renamed';
        $this->putJson('/api/pos/usuarios/actualizar/'.$user->id, $data, ['Action-Btn' => 'uspbtn04'])->assertOk();
        $this->assertSame($credentials['hash'], $user->fresh()->credenciales['hash']);
        $this->assertSame('test.renamed', base64_decode($user->fresh()->credenciales['usuario'], true));
        $data['password'] = 'Reset6';
        $this->putJson('/api/pos/usuarios/actualizar/'.$user->id, $data, ['Action-Btn' => 'uspbtn04'])->assertOk();
        $this->assertNotSame($credentials['hash'], $user->fresh()->credenciales['hash']);
        $this->deleteJson('/api/pos/usuarios/desactivar/'.$user->id, [], $this->permit('uspbtn05'))->assertOk();
        $this->assertFalse($user->fresh()->activo);
        $this->assertFalse(Schema::hasTable('clavespos'));
        $this->assertFalse(Schema::hasColumn('usuariospos', 'secreto'));
    }

    public function test_missing_permission_key_and_unsupported_permissions_are_rejected(): void
    {
        $this->getJson('/api/pos/usuarios/listar', ['Action-Btn' => 'uspbtn01'])->assertForbidden();
        $this->postJson('/api/pos/roles/crear', ['nombre' => 'TEST', 'permisos' => ['invented.permission'], 'activo' => true], $this->permit('rspbtn02'))->assertUnprocessable();
        $role = $this->postJson('/api/pos/roles/crear', ['nombre' => 'TEST', 'nivel' => 0, 'activo' => true], ['Action-Btn' => 'rspbtn02'])->assertCreated()->json('data.id');
        $this->postJson('/api/pos/usuarios/crear', ['nombre' => 'TEST', 'stock_id' => 1, 'rol_id' => $role, 'usuario' => 'test', 'password' => 'Synthetic-password-2026', 'activo' => true], $this->permit('uspbtn02'))->assertUnprocessable();
        $this->assertSame(0, UsuarioPos::count());
    }

    public function test_catalog_identity_is_unique_and_immutable_and_booleans_are_canonical(): void
    {
        $headers = $this->permit('copbtn02');
        $data = ['stock_id' => 1, 'tipo' => 'MEDIO_PAGO', 'codigo' => 'TEST-CASH', 'activo' => 1, 'datos' => ['codigo' => 'TEST-CASH', 'descripcion' => 'TEST', 'es_efectivo' => 1, 'requiere_referencia' => 0]];
        $id = $this->postJson('/api/pos/catalogos/crear', $data, $headers)->assertCreated()->assertJsonPath('data.datos.es_efectivo', true)->json('data.id');
        $data['codigo'] = 'OTHER';
        $this->postJson('/api/pos/catalogos/crear', $data, $headers)->assertUnprocessable();
        $data['datos']['codigo'] = 'OTHER';
        $this->putJson('/api/pos/catalogos/actualizar/'.$id, $data, $this->permit('copbtn04'))->assertUnprocessable();
        $this->assertSame(1, DB::table('catalogospos')->count());
    }

    public function test_price_catalog_publishes_product_and_lot_without_a_distribution_record(): void
    {
        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo');
            $table->string('descripcion');
            foreach (['categoria', 'laboratorio', 'unidad', 'familia', 'estado'] as $field) {
                $table->integer($field)->nullable();
            }
            $table->string('codigobar')->nullable();
            $table->timestamps();
        });
        Schema::create('principal_activos', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('prod_activo', function (Blueprint $table): void {
            $table->integer('producto');
            $table->integer('pactivo');
        });
        Schema::create('lotes', function (Blueprint $table): void {
            $table->id();
            $table->integer('producto');
            $table->string('lote');
            $table->date('fecha_elab')->nullable();
            $table->date('fecha_exp')->nullable();
            $table->timestamps();
        });
        DB::table('productos')->insert(['id' => 1, 'codigo' => 'TEST', 'descripcion' => 'TEST product']);
        DB::table('lotes')->insert(['id' => 2, 'producto' => 1, 'lote' => 'TEST-L', 'fecha_exp' => '2099-12-31']);
        $headers = $this->permit('copbtn02');
        $unit = $this->postJson('/api/pos/catalogos/crear', ['stock_id' => 1, 'tipo' => 'UNIDAD', 'codigo' => 'TEST-U', 'activo' => true, 'datos' => ['producto_id' => 1, 'abreviatura' => 'TEST', 'factor_inventario' => 1]], $headers)->assertCreated()->json('data.id');
        $this->postJson('/api/pos/catalogos/crear', ['stock_id' => 1, 'tipo' => 'PRECIO', 'codigo' => 'TEST-P', 'activo' => true, 'datos' => ['producto_id' => 1, 'lote_id' => 2, 'unidad_id' => $unit, 'precio' => '21.5000', 'incluye_isv' => false]], $headers)->assertCreated();
        $payload = json_decode(DB::table('sync_changes')->where('entity', 'catalogospos')->orderByDesc('id')->value('payload'), true);
        $this->assertSame(2, $payload['lote_datos']['id']);
        $this->assertSame(1, $payload['lote_datos']['producto']);
        $this->assertSame('2099-12-31', $payload['lote_datos']['fecha_exp']);
        $this->assertSame(1, DB::table('sync_changes')->where('entity', 'productos')->count());
    }
}
