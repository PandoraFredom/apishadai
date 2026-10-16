<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $module = DB::table('modulos')->where('codigo', 'pos')->value('id');
            $state = DB::table('vista_estados')->where('descripcion', 'ACTIVO')->value('id');
            if ($module === null || $state === null) {
                throw new LogicException('Falta el módulo POS o su estado de vistas.');
            }
            $view = DB::table('vistas')->where('codigo', 'cpp')->lockForUpdate()->first();
            if ($view !== null && (string) $view->modulo !== (string) $module) {
                throw new LogicException('La vista cpp pertenece a otro módulo.');
            }
            $id = $view?->id ?? DB::table('vistas')->insertGetId(['codigo' => 'cpp', 'nombre' => 'Compras POS', 'modulo' => $module, 'estado' => $state, 'created_at' => now(), 'updated_at' => now()]);
            foreach (['cppbtn01' => 'Listar compras POS', 'cppbtn02' => 'Filtrar compras POS', 'cppbtn03' => 'Ver compras POS'] as $code => $name) {
                $action = DB::table('actionsvistas')->where('codigo', $code)->lockForUpdate()->first();
                if ($action !== null && (string) $action->vista !== (string) $id) {
                    throw new LogicException('La acción '.$code.' pertenece a otra vista.');
                }
                if ($action === null) {
                    DB::table('actionsvistas')->insert(['codigo' => $code, 'nombre' => $name, 'vista' => $id, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            // Only register the portal catalog. Do not assign grants or alter POS receipt permissions.
        }, 3);
    }

    public function down(): void
    {
        throw new LogicException('Conservar las asignaciones de permisos y el historial de compras POS.');
    }
};
