<?php
namespace Tests\Feature\Pos;

use Database\Seeders\KardexPermissionCatalogSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class KardexPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'headquarters.publishing_enabled' => false]);
        DB::purge('sqlite');
        foreach (['modulospos', 'vistaspos', 'botonespos'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->string('codigo')->unique();
                $table->string('nombre');
                $table->boolean('activo');
                if ($name === 'vistaspos') $table->unsignedBigInteger('modulo_id');
                if ($name === 'botonespos') $table->unsignedBigInteger('vista_id');
                $table->timestamps();
            });
        }
        Schema::create('permisosusuariospos', fn (Blueprint $table) => $table->id());
    }
    protected function tearDown(): void
    { DB::purge('sqlite');parent::tearDown(); }

    public function test_registers_exact_hierarchy_idempotently_without_user_grants(): void
    {
        $migration = require database_path('migrations/2026_10_14_000003_pos_kardex_permissions.php');
        $migration->up();
        $ids = DB::table('botonespos')->orderBy('id')->pluck('id')->all();
        $migration->up();
        $this->assertSame($ids, DB::table('botonespos')->orderBy('id')->pluck('id')->all());
        $module = DB::table('modulospos')->where('codigo', 'inventario')->first();
        $view = DB::table('vistaspos')->where('codigo', 'inventario.kardex')->first();
        $this->assertSame($module->id, $view->modulo_id);
        $this->assertSame(array_keys(KardexPermissionCatalogSeeder::ACTIONS),
            DB::table('botonespos')->where('vista_id', $view->id)->orderBy('id')->pluck('codigo')->all());
        $this->assertSame(0, DB::table('permisosusuariospos')->count());
        $migration->down();
        $this->assertSame(4, DB::table('botonespos')->count());
    }

    public function test_wrong_parent_rolls_back_partial_catalog_creation(): void
    {
        $other = DB::table('modulospos')->insertGetId(['codigo' => 'TEST', 'nombre' => 'TEST', 'activo' => true]);
        DB::table('vistaspos')->insert(['codigo' => 'inventario.kardex', 'nombre' => 'TEST', 'activo' => true, 'modulo_id' => $other]);
        try {
            (new KardexPermissionCatalogSeeder)->run();
            $this->fail('Expected wrong-parent rejection.');
        } catch (LogicException) {
            $this->assertFalse(DB::table('modulospos')->where('codigo', 'inventario')->exists());
            $this->assertSame(0, DB::table('botonespos')->count());
        }
    }

    public function test_new_module_access_preserves_both_report_actions_and_assignments(): void
    {
        (new \Database\Seeders\SalesReportPermissionCatalogSeeder)->run();
        (new \Database\Seeders\ProfitReportPermissionCatalogSeeder)->run();
        $before = DB::table('botonespos')->orderBy('id')->get()->all();
        $views = DB::table('vistaspos')->orderBy('id')->get()->all();
        (new KardexPermissionCatalogSeeder)->run();
        $this->assertEquals($before, DB::table('botonespos')->whereIn('id', array_column($before, 'id'))->orderBy('id')->get()->all());
        $this->assertEquals($views, DB::table('vistaspos')->whereIn('id', array_column($views, 'id'))->orderBy('id')->get()->all());
        $this->assertSame(8, DB::table('botonespos')->count());
        $this->assertSame(0, DB::table('permisosusuariospos')->count());
    }

    public function test_existing_inactive_metadata_and_ids_are_preserved(): void
    {
        (new KardexPermissionCatalogSeeder)->run();
        DB::table('botonespos')->where('codigo', 'kardex.ver')->update(['activo' => false]);
        $ids = DB::table('botonespos')->pluck('id')->all();
        (new KardexPermissionCatalogSeeder)->run();
        $this->assertSame($ids, DB::table('botonespos')->pluck('id')->all());
        $this->assertEquals(0, DB::table('botonespos')->where('codigo', 'kardex.ver')->value('activo'));
    }
}
