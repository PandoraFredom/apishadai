<?php

use Database\Seeders\AttendancePermissionCatalogSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check prerequisites before DDL; MySQL may commit each alteration separately.
        foreach (['worklunch', 'usuariospos', 'rolespos', 'devices', 'modulospos', 'vistaspos', 'botonespos', 'permisosusuariospos'] as $name) {
            if (! Schema::hasTable($name)) {
                throw new LogicException('Falta el esquema central requerido para asistencia: '.$name);
            }
        }
        if (DB::getDriverName() === 'mysql') {
            // The POS identity must support the per-user lock across devices/stocks.
            foreach (['usuariospos', 'worklunch'] as $name) {
                $engine = DB::table('information_schema.tables')->where('table_schema', DB::getDatabaseName())
                    ->where('table_name', $name)->value('engine');
                if (strcasecmp((string) $engine, 'InnoDB') !== 0) {
                    if (strcasecmp((string) $engine, 'MyISAM') !== 0) {
                        throw new LogicException('Motor no reconocido para asistencia: '.$name);
                    }
                    $count = DB::table($name)->count();
                    DB::statement('ALTER TABLE `'.$name.'` ENGINE=InnoDB');
                    if (DB::table($name)->count() !== $count) {
                        throw new LogicException('Cambió el conteo durante la conversión: '.$name);
                    }
                }
            }
            // Preserve the actual legacy integer type and its existing FK.
            $column = collect(Schema::getColumns('worklunch'))->firstWhere('name', 'usuario');
            $type = $column['type'];
            if (! preg_match('/\A(?:tinyint|smallint|mediumint|int|bigint)(?:\([0-9]+\))?(?: unsigned)?\z/i', $type)) {
                throw new LogicException('Tipo legado de usuario WorkLunch no reconocido.');
            }
            if (! $column['nullable']) {
                DB::statement('ALTER TABLE worklunch MODIFY usuario '.$type.' NULL');
            }
        } else {
            Schema::table('worklunch', fn (Blueprint $table) => $table->unsignedBigInteger('usuario')->nullable()->change());
        }
        if (! Schema::hasColumn('worklunch', 'usuario_pos')) {
            Schema::table('worklunch', fn (Blueprint $table) => $table->unsignedBigInteger('usuario_pos')->nullable());
        }
        if (! collect(Schema::getForeignKeys('worklunch'))->contains(fn (array $key) => $key['columns'] === ['usuario_pos'])) {
            Schema::table('worklunch', fn (Blueprint $table) => $table->foreign('usuario_pos')->references('id')->on('usuariospos'));
        }
        if (! Schema::hasIndex('worklunch', 'worklunch_unique_pos_work_date')) {
            Schema::table('worklunch', fn (Blueprint $table) => $table->unique(['usuario_pos', 'work_date'], 'worklunch_unique_pos_work_date'));
        }
        if (! Schema::hasTable('pos_asistencia_solicitudes')) {
            Schema::create('pos_asistencia_solicitudes', function (Blueprint $table): void {
                $table->engine('InnoDB');
                $table->uuid('request_id')->primary();
                $table->unsignedBigInteger('device_id');
                $table->unsignedBigInteger('usuario_pos_id');
                $table->char('huella', 64);
                $table->longText('datos');
                $table->longText('respuesta');
                $table->timestamp('created_at');
            });
        }
        (new AttendancePermissionCatalogSeeder)->run();
    }

    public function down(): void
    {
        throw new LogicException('La asistencia y su historial no admiten reversión automática.');
    }
};
