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

            $moduleId = DB::table('modulos')->where('codigo', 'frm')->value('id');
            if ($moduleId === null) {
                $moduleId = DB::table('modulos')->insertGetId(['codigo' => 'frm', 'nombre' => 'frm', 'estado' => $moduleState, 'created_at' => now(), 'updated_at' => now()]);
            }
            $view = DB::table('vistas')->where('codigo', 'tft')->first();
            if ($view !== null && (int) $view->modulo !== (int) $moduleId) {
                throw new RuntimeException('El código de vista tft ya pertenece a otro módulo.');
            }
            $viewId = $view?->id ?? DB::table('vistas')->insertGetId(['codigo' => 'tft', 'nombre' => 'tipos de transferencia', 'modulo' => $moduleId, 'estado' => $viewState, 'created_at' => now(), 'updated_at' => now()]);

            foreach (['listar', 'crear', 'buscar', 'actualizar', 'eliminar'] as $index => $name) {
                $code = 'tftbtn0'.($index + 1);
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
        $viewId = DB::table('vistas')->where('codigo', 'tft')->value('id');
        if ($viewId === null || DB::table('permisos')->where('vista', $viewId)->exists()) {
            return;
        }
        DB::table('actionsvistas')->where('vista', $viewId)->whereIn('codigo', ['tftbtn01', 'tftbtn02', 'tftbtn03', 'tftbtn04', 'tftbtn05'])->delete();
        if (DB::table('actionsvistas')->where('vista', $viewId)->exists()) {
            return;
        }
        DB::table('vistas')->where('id', $viewId)->delete();
    }
};
