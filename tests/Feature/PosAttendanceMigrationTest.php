<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PosAttendanceMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'headquarters.publishing_enabled' => false]);
        DB::purge('sqlite');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_resumes_partial_ddl_preserving_portal_history_without_granting_permissions(): void
    {
        foreach (['usuariospos', 'rolespos', 'devices', 'permisosusuariospos'] as $name) {
            Schema::create($name, fn (Blueprint $table) => $table->id());
        }
        foreach (['modulospos', 'vistaspos', 'botonespos'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->string('codigo')->unique();
                $table->string('nombre');
                $table->boolean('activo');
                $table->timestamps();
                if ($name === 'vistaspos') {
                    $table->unsignedBigInteger('modulo_id');
                }
                if ($name === 'botonespos') {
                    $table->unsignedBigInteger('vista_id');
                }
            });
        }
        Schema::create('worklunch', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('usuario')->nullable();
            $table->date('work_date');
            // MySQL may have added this column before an interrupted FK alteration.
            $table->unsignedBigInteger('usuario_pos')->nullable();
        });
        DB::table('worklunch')->insert(['usuario' => 9, 'work_date' => '2026-10-01']);
        $migration = require database_path('migrations/2026_10_11_000001_add_pos_attendance.php');
        $migration->up();
        $migration->up();
        $this->assertDatabaseHas('worklunch', ['usuario' => 9, 'usuario_pos' => null]);
        $this->assertDatabaseCount('worklunch', 1);
        $this->assertTrue(Schema::hasIndex('worklunch', 'worklunch_unique_pos_work_date'));
        $this->assertTrue(collect(Schema::getForeignKeys('worklunch'))->contains(fn (array $key) => $key['columns'] === ['usuario_pos']));
        $this->assertDatabaseCount('pos_asistencia_solicitudes', 0);
        $this->assertDatabaseCount('permisosusuariospos', 0);
        $this->assertDatabaseCount('modulospos', 1);
        $this->assertDatabaseCount('vistaspos', 1);
        $this->assertDatabaseCount('botonespos', 2);
    }

    public function test_missing_prerequisite_fails_before_any_ddl(): void
    {
        Schema::create('worklunch', fn (Blueprint $table) => $table->id());
        $migration = require database_path('migrations/2026_10_11_000001_add_pos_attendance.php');
        try {
            $migration->up();
            $this->fail('Missing POS prerequisites must block DDL.');
        } catch (\LogicException $error) {
            $this->assertStringContainsString('usuariospos', $error->getMessage());
        }
        $this->assertFalse(Schema::hasColumn('worklunch', 'usuario_pos'));
        $this->assertFalse(Schema::hasTable('pos_asistencia_solicitudes'));
    }
}
