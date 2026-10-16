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
                throw new RuntimeException('Falta el módulo POS o el estado ACTIVO de vistas.');
            }
            foreach (['usp' => 'Usuarios POS', 'rsp' => 'Roles y permisos POS', 'cop' => 'Catálogos de operación POS'] as $code => $name) {
                $view = DB::table('vistas')->where('codigo', $code)->first();
                if ($view !== null && (int) $view->modulo !== (int) $module) {
                    throw new RuntimeException('El código de vista POS ya pertenece a otro módulo.');
                }
                $id = $view?->id ?? DB::table('vistas')->insertGetId(['codigo' => $code, 'nombre' => $name,
                    'modulo' => $module, 'estado' => $state, 'created_at' => now(), 'updated_at' => now()]);
                $actions = ['listar', 'crear', 'buscar', 'actualizar', 'desactivar'];
                if ($code === 'usp') {
                    $actions[] = 'registrar clave pública POS';
                }
                foreach ($actions as $index => $actionName) {
                    $actionCode = $code.'btn0'.($index + 1);
                    $action = DB::table('actionsvistas')->where('codigo', $actionCode)->first();
                    if ($action !== null && (int) $action->vista !== (int) $id) {
                        throw new RuntimeException('El código de acción POS ya pertenece a otra vista.');
                    }
                    if ($action === null) {
                        DB::table('actionsvistas')->insert(['codigo' => $actionCode, 'nombre' => $actionName,
                            'vista' => $id, 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Existing grants are preserved; removing a deployed management view is explicit.
    }
};
