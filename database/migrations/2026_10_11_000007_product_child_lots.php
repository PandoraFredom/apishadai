<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unknown tax/dates stay unknown; existing values and actual SQL types are retained.
        if (DB::getDriverName() === 'mysql') {
            $engine = DB::table('information_schema.tables')->where('table_schema', DB::getDatabaseName())->where('table_name', 'prod_unidades')->value('engine');
            if (strcasecmp((string) $engine, 'InnoDB') !== 0) {
                if (strcasecmp((string) $engine, 'MyISAM') !== 0) {
                    throw new LogicException('Motor de unidades no reconocido.');
                }
                $count = DB::table('prod_unidades')->count();
                DB::statement('ALTER TABLE prod_unidades ENGINE=InnoDB');
                if (DB::table('prod_unidades')->count() !== $count) {
                    throw new LogicException('Cambio el conteo de unidades.');
                }
            }
            foreach (['isv', 'fecha_elab', 'fecha_exp'] as $name) {
                $column = collect(Schema::getColumns('lotes'))->firstWhere('name', $name);
                $type = $column['type'] ?? '';
                if (! preg_match('/\A(?:tinyint|smallint|mediumint|int|bigint|date|datetime|timestamp)(?:\([0-9]+\))?(?: unsigned)?\z/i', $type)) {
                    throw new LogicException('Tipo de lote no reconocido.');
                }
                DB::statement('ALTER TABLE lotes MODIFY `'.$name.'` '.$type.' NULL');
            }
        } else {
            Schema::table('lotes', function (Blueprint $t): void {
                $t->boolean('isv')->nullable()->change();
                $t->date('fecha_elab')->nullable()->change();
                $t->date('fecha_exp')->nullable()->change();
            });
        }
        Schema::create('pos_lotes_hijos_centrales', function (Blueprint $t): void {
            $t->engine('InnoDB');
            $t->uuid('request_id')->primary();
            $t->unsignedBigInteger('device_id');
            $t->decimal('stock_id', 20, 0)->index();
            $t->decimal('usuario_pos_id', 20, 0);
            $t->decimal('producto_id', 20, 0);
            $t->char('huella', 64);
            $t->longText('datos');
            $t->longText('respuesta');
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        throw new LogicException('El diario de lotes hijos conserva su historial.');
    }
};
