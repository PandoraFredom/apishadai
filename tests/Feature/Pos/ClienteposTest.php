<?php

namespace Tests\Feature\Pos;

use App\Http\Middleware\DeviceSecurityMiddleware;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\JWTAuthenticationMiddleware;
use App\Http\Middleware\MatchTokenMiddleware;
use App\Models\Pos\Clientepos;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClienteposTest extends TestCase
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
        (require database_path('migrations/2026_10_12_000004_clientepos_documents.php'))->up();
        (require database_path('migrations/2026_10_01_000023_register_clientepos_permissions.php'))->up();
        $this->withoutMiddleware([DeviceSecurityMiddleware::class, IdempotencyMiddleware::class, JWTAuthenticationMiddleware::class, MatchTokenMiddleware::class]);
        $this->actingAs((new User)->forceFill(['id' => 7]));
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_crud_uses_the_api_envelope_and_preserves_optional_fields_and_leading_zeroes(): void
    {
        $this->grantAll();
        $this->postJson('/api/pos/clientepos/crear', ['nombre' => ' <b>Ana López</b> ', 'tipo_documento' => 'RTN', 'rtn' => '0801-1999-001234'], ['Action-Btn' => 'clpbtn02'])
            ->assertCreated()->assertJsonPath('data', true);
        $client = Clientepos::query()->firstOrFail();
        $this->assertSame('Ana López', $client->nombre);
        $this->assertSame('08011999001234', $client->rtn);
        $this->assertNull($client->telefono);

        $this->getJson('/api/pos/clientepos/buscar/'.$client->id, ['Action-Btn' => 'clpbtn03'])
            ->assertOk()->assertJsonPath('data.rtn', '08011999001234');
        $this->putJson('/api/pos/clientepos/actualizar/'.$client->id, ['id' => 999, 'nombre' => 'Ana López', 'rtn' => $client->rtn, 'telefono' => '9999-0000', 'direccion' => 'Centro'], ['Action-Btn' => 'clpbtn04'])
            ->assertOk();
        $this->putJson('/api/pos/clientepos/actualizar/'.$client->id, ['nombre' => 'Ana López', 'rtn' => ''], ['Action-Btn' => 'clpbtn04'])->assertUnprocessable();
        $this->putJson('/api/pos/clientepos/actualizar/'.$client->id, ['nombre' => 'Ana López', 'rtn' => $client->rtn, 'telefono' => '', 'direccion' => ''], ['Action-Btn' => 'clpbtn04'])->assertOk();
        $this->assertSame('08011999001234', $client->fresh()->rtn);
        $this->assertSame('RTN', $client->fresh()->tipo_documento);
        $this->assertNull($client->fresh()->telefono);
        $this->assertNull($client->fresh()->direccion);
        $this->deleteJson('/api/pos/clientepos/eliminar/'.$client->id, [], ['Action-Btn' => 'clpbtn05'])->assertOk();
        $this->getJson('/api/pos/clientepos/buscar/'.$client->id, ['Action-Btn' => 'clpbtn03'])->assertNotFound();
    }

    public function test_documents_are_unique_after_normalization_and_names_may_repeat(): void
    {
        $this->grantAll();
        Clientepos::create(['nombre' => 'Ana', 'rtn' => '08011999001234']);
        $this->postJson('/api/pos/clientepos/crear', ['nombre' => 'Otro cliente', 'tipo_documento' => 'RTN', 'rtn' => '0801-1999-001234'], ['Action-Btn' => 'clpbtn02'])->assertUnprocessable();
        foreach ([1, 2] as $unused) {
            $this->postJson('/api/pos/clientepos/crear', ['nombre' => 'Ana', 'tipo_documento' => 'RTN', 'rtn' => '0000199900001'.$unused], ['Action-Btn' => 'clpbtn02'])->assertCreated();
        }
        $this->postJson('/api/pos/clientepos/crear', ['nombre' => '<b></b>'], ['Action-Btn' => 'clpbtn02'])->assertUnprocessable();
    }

    public function test_search_filters_all_records_before_pagination(): void
    {
        $this->grantAll();
        foreach (range(1, 35) as $number) {
            Clientepos::create(['nombre' => 'Cliente '.$number, 'telefono' => $number === 1 ? '9999-1234' : null]);
        }
        $this->getJson('/api/pos/clientepos/listar?search=9999-1234', ['Action-Btn' => 'clpbtn01'])
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.nombre', 'Cliente 1');
        $this->getJson('/api/pos/clientepos/listar?page=2', ['Action-Btn' => 'clpbtn01'])
            ->assertOk()->assertJsonPath('meta.total', 35)->assertJsonPath('meta.current_page', 2)->assertJsonCount(5, 'data');
    }

    public function test_each_route_checks_its_own_permission_and_expiration(): void
    {
        $this->getJson('/api/pos/clientepos/listar', ['Action-Btn' => 'clpbtn01'])->assertForbidden();
        $this->grant('clpbtn01');
        $this->getJson('/api/pos/clientepos/listar', ['Action-Btn' => 'clpbtn01'])->assertOk();
        $this->postJson('/api/pos/clientepos/crear', ['nombre' => 'Ana'], ['Action-Btn' => 'clpbtn01'])->assertForbidden();
        $this->postJson('/api/pos/clientepos/crear', ['nombre' => 'Ana'], ['Action-Btn' => 'clpbtn02'])->assertForbidden();
        DB::table('permisos')->update(['lifetime' => now()->subDay()]);
        $this->getJson('/api/pos/clientepos/listar', ['Action-Btn' => 'clpbtn01'])->assertForbidden();
        $this->assertSame(0, Clientepos::count());
    }

    public function test_permission_registration_is_idempotent_and_does_not_grant_access(): void
    {
        (require database_path('migrations/2026_10_01_000023_register_clientepos_permissions.php'))->up();
        $this->assertSame(1, DB::table('modulos')->where('codigo', 'pos')->count());
        $this->assertSame(1, DB::table('vistas')->where('codigo', 'clp')->count());
        $this->assertSame(5, DB::table('actionsvistas')->count());
        $this->assertSame(0, DB::table('permisos')->count());
    }

    public function test_permissions_from_another_user_or_an_inactive_module_are_denied(): void
    {
        $this->grant('clpbtn01');
        DB::table('permisos')->update(['usuario' => 8]);
        $this->getJson('/api/pos/clientepos/listar', ['Action-Btn' => 'clpbtn01'])->assertForbidden();
        DB::table('permisos')->update(['usuario' => 7]);
        DB::table('modulo_estados')->insert(['id' => 2, 'descripcion' => 'INACTIVO']);
        DB::table('modulos')->update(['estado' => 2]);
        $this->getJson('/api/pos/clientepos/listar', ['Action-Btn' => 'clpbtn01'])->assertForbidden();
    }

    public function test_identity_requires_birth_date_and_serializes_it_for_hq_without_using_registration_year(): void
    {
        $this->grantAll();
        $this->postJson('/api/pos/clientepos/crear', ['nombre' => 'TEST paciente', 'tipo_documento' => 'IDENTIDAD', 'rtn' => '0000-1999-00001', 'fecha_nacimiento' => '1946-10-12'], ['Action-Btn' => 'clpbtn02'])->assertCreated();
        $client = Clientepos::query()->firstOrFail();
        $this->assertSame('0000199900001', $client->rtn);
        $this->assertSame('1946-10-12', $client->attributesToArray()['fecha_nacimiento']);
        $this->getJson('/api/pos/clientepos/buscar/'.$client->id, ['Action-Btn' => 'clpbtn03'])->assertOk()
            ->assertJsonPath('data.tipo_documento', 'IDENTIDAD')->assertJsonPath('data.fecha_nacimiento', '1946-10-12');
    }

    public function test_document_choice_lengths_ascii_and_birth_date_are_validated_before_writing(): void
    {
        $this->grantAll();
        foreach ([
            ['rtn' => '0000199900001'],
            ['tipo_documento' => 'RTN', 'rtn' => '0000199900001'],
            ['tipo_documento' => 'IDENTIDAD', 'rtn' => '00001999000011', 'fecha_nacimiento' => '1946-10-12'],
            ['tipo_documento' => 'IDENTIDAD', 'rtn' => '0000199900001'],
            ['tipo_documento' => 'IDENTIDAD', 'rtn' => '００００１９９９００００１', 'fecha_nacimiento' => '1946-10-12'],
            ['tipo_documento' => 'RTN', 'rtn' => '00000000000000'],
            ['tipo_documento' => 'IDENTIDAD', 'rtn' => '0000199900001', 'fecha_nacimiento' => '2099-01-01'],
            ['tipo_documento' => 'IDENTIDAD', 'rtn' => '0000199900001', 'fecha_nacimiento' => '1946-02-30'],
        ] as $input) {
            $this->postJson('/api/pos/clientepos/crear', ['nombre' => 'TEST paciente', ...$input], ['Action-Btn' => 'clpbtn02'])->assertUnprocessable();
        }
        $this->assertSame(0, Clientepos::count());
    }

    public function test_legacy_final_customer_is_preserved_until_document_is_explicitly_changed(): void
    {
        $this->grantAll();
        $client = Clientepos::create(['nombre' => 'TEST final', 'rtn' => '00']);
        $this->putJson('/api/pos/clientepos/actualizar/'.$client->id, ['nombre' => 'TEST final', 'rtn' => '00', 'telefono' => 'TEST'], ['Action-Btn' => 'clpbtn04'])->assertOk();
        $this->assertSame('00', $client->fresh()->rtn);
        $this->assertNull($client->fresh()->tipo_documento);
        $this->assertNull($client->fresh()->fecha_nacimiento);
        $this->putJson('/api/pos/clientepos/actualizar/'.$client->id, ['nombre' => 'TEST final', 'rtn' => '0000199900001'], ['Action-Btn' => 'clpbtn04'])->assertUnprocessable();
        $this->assertSame('00', $client->fresh()->rtn);
    }

    public function test_contact_updates_do_not_normalize_a_legacy_document_until_classification_is_explicit(): void
    {
        $this->grantAll();
        $client = Clientepos::create(['nombre' => 'TEST legado', 'rtn' => '0000-1999-00001']);
        $this->putJson('/api/pos/clientepos/actualizar/'.$client->id, ['nombre' => 'TEST legado', 'rtn' => $client->rtn, 'direccion' => 'TEST nueva dirección'], ['Action-Btn' => 'clpbtn04'])->assertOk();
        $this->assertSame('0000-1999-00001', $client->fresh()->rtn);
        $this->assertNull($client->fresh()->tipo_documento);
        $this->putJson('/api/pos/clientepos/actualizar/'.$client->id, ['nombre' => 'TEST legado', 'rtn' => $client->rtn, 'tipo_documento' => 'IDENTIDAD', 'fecha_nacimiento' => '1946-10-12'], ['Action-Btn' => 'clpbtn04'])->assertOk();
        $this->assertSame('0000199900001', $client->fresh()->rtn);
        $this->assertSame('IDENTIDAD', $client->fresh()->tipo_documento);
    }

    private function grantAll(): void
    {
        foreach (range(1, 5) as $number) {
            $this->grant('clpbtn0'.$number);
        }
    }

    private function grant(string $action): void
    {
        DB::table('permisos')->insert([
            'usuario' => 7,
            'modulo' => DB::table('modulos')->where('codigo', 'pos')->value('id'),
            'vista' => DB::table('vistas')->where('codigo', 'clp')->value('id'),
            'actionvista' => DB::table('actionsvistas')->where('codigo', $action)->value('id'),
            'lifetime' => null,
        ]);
    }
}
