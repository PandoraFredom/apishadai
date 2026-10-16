<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pos_descuento_solicitudes')) {
            Schema::create('pos_descuento_solicitudes', function (Blueprint $table): void {
                $table->engine('InnoDB');
                $table->uuid('solicitud_id')->primary();
                $table->unsignedBigInteger('usuario_id');
                $table->char('huella', 64);
                $table->longText('respuesta');
                $table->timestamp('created_at');
            });
        }
        if (! Schema::hasTable('pos_descuento_control')) {
            Schema::create('pos_descuento_control', function (Blueprint $table): void {
                $table->engine('InnoDB');
                $table->unsignedTinyInteger('id')->primary();
            });
            DB::table('pos_descuento_control')->insert(['id' => 1]);
        }
        DB::transaction(function (): void {
            $module = DB::table('modulos')->where('codigo', 'pos')->lockForUpdate()->value('id');
            $state = DB::table('vista_estados')->where('descripcion', 'ACTIVO')->value('id');
            if ($module === null || $state === null) {
                throw new LogicException('Falta el módulo POS o el estado de vistas.');
            }
            $view = DB::table('vistas')->where('codigo', 'dcp')->lockForUpdate()->first();
            if ($view !== null && (string) $view->modulo !== (string) $module) {
                throw new LogicException('La vista dcp pertenece a otro módulo.');
            }
            $id = $view?->id ?? DB::table('vistas')->insertGetId(['codigo' => 'dcp', 'nombre' => 'Reglas de descuentos POS',
                'modulo' => $module, 'estado' => $state, 'created_at' => now(), 'updated_at' => now()]);
            foreach ([1 => 'Listar reglas de descuentos', 2 => 'Crear reglas de descuentos', 3 => 'Ver reglas de descuentos',
                4 => 'Modificar y reactivar reglas de descuentos', 5 => 'Desactivar reglas de descuentos'] as $number => $name) {
                $code = 'dcpbtn0'.$number;
                $action = DB::table('actionsvistas')->where('codigo', $code)->lockForUpdate()->first();
                if ($action !== null && (string) $action->vista !== (string) $id) {
                    throw new LogicException('La acción '.$code.' pertenece a otra vista.');
                }
                if ($action === null) {
                    DB::table('actionsvistas')->insert(['codigo' => $code, 'nombre' => $name, 'vista' => $id, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }, 3);
    }

    public function down(): void
    {
        throw new LogicException('Conservar reglas, permisos, solicitudes e historial POS.');
    }
};
