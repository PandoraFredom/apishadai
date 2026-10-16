<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $module = DB::table('modulos')->where('codigo', 'pos')->value('id');
            $view = DB::table('vistas')->where('codigo', 'dcp')->lockForUpdate()->first();
            if ($module === null || $view === null || (string) $view->modulo !== (string) $module) {
                throw new LogicException('Falta la vista de reglas de descuentos del módulo POS.');
            }
            $action = DB::table('actionsvistas')->where('codigo', 'dcpbtn06')->lockForUpdate()->first();
            if ($action !== null && (string) $action->vista !== (string) $view->id) {
                throw new LogicException('La acción dcpbtn06 pertenece a otra vista.');
            }
            if ($action === null) {
                DB::table('actionsvistas')->insert(['codigo' => 'dcpbtn06', 'nombre' => 'Eliminar reglas de descuentos',
                    'vista' => $view->id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }, 3);
    }

    public function down(): void
    {
        throw new LogicException('Conservar permisos e historial de reglas POS.');
    }
};
