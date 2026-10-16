<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $moduleState = DB::table('modulo_estados')->where('descripcion', 'ACTIVO')->value('id');
            $viewState = DB::table('vista_estados')->where('descripcion', 'ACTIVO')->value('id');
            if ($moduleState === null || $viewState === null) {
                throw new RuntimeException('Faltan los estados ACTIVO de módulos o vistas.');
            }

            $moduleId = DB::table('modulos')->where('codigo', 'pos')->value('id');
            if ($moduleId === null) {
                $moduleId = DB::table('modulos')->insertGetId(['codigo' => 'pos', 'nombre' => 'pos', 'estado' => $moduleState, 'created_at' => now(), 'updated_at' => now()]);
            }
            $view = DB::table('vistas')->where('codigo', 'clp')->first();
            if ($view !== null && (int) $view->modulo !== (int) $moduleId) {
                throw new RuntimeException('El código de vista clp ya pertenece a otro módulo.');
            }
            $viewId = $view?->id ?? DB::table('vistas')->insertGetId(['codigo' => 'clp', 'nombre' => 'clientepos', 'modulo' => $moduleId, 'estado' => $viewState, 'created_at' => now(), 'updated_at' => now()]);

            foreach (['listar', 'crear', 'buscar', 'actualizar', 'eliminar'] as $index => $name) {
                $code = 'clpbtn0'.($index + 1);
                $action = DB::table('actionsvistas')->where('codigo', $code)->first();
                if ($action !== null && (int) $action->vista !== (int) $viewId) {
                    throw new RuntimeException('El código de acción '.$code.' ya pertenece a otra vista.');
                }
                if ($action === null) {
                    DB::table('actionsvistas')->insert(['codigo' => $code, 'nombre' => $name, 'vista' => $viewId, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });
    }

    public function down(): void
    {
        $viewId = DB::table('vistas')->where('codigo', 'clp')->value('id');
        if ($viewId === null || DB::table('permisos')->where('vista', $viewId)->exists()) {
            return;
        }
        DB::table('actionsvistas')->where('vista', $viewId)->whereIn('codigo', ['clpbtn01', 'clpbtn02', 'clpbtn03', 'clpbtn04', 'clpbtn05'])->delete();
        if (DB::table('actionsvistas')->where('vista', $viewId)->exists()) {
            return;
        }
        DB::table('vistas')->where('id', $viewId)->delete();
        $moduleId = DB::table('modulos')->where('codigo', 'pos')->value('id');
        if (! DB::table('vistas')->where('modulo', $moduleId)->exists() && ! DB::table('permisos')->where('modulo', $moduleId)->exists()) {
            DB::table('modulos')->where('id', $moduleId)->delete();
        }
    }
};
