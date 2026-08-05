<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private const ACTIONS = [
        'prdbtn01' => 'listar',
        'prdbtn02' => 'crear',
        'prdbtn03' => 'buscar',
        'prdbtn04' => 'actualizar',
        'prdbtn05' => 'eliminar',
        'prdbtn06' => 'imagen',
        'prdbtn07' => 'opciones',
        'prdbtn08' => 'listar principios activos',
        'prdbtn09' => 'asociar principio activo',
        'prdbtn10' => 'buscar principio activo asociado',
        'prdbtn11' => 'actualizar principio activo asociado',
        'prdbtn12' => 'eliminar principio activo asociado',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('modulos') || ! Schema::hasTable('vistas') || ! Schema::hasTable('actionsvistas')) {
            return;
        }

        DB::table('modulos')->updateOrInsert(
            ['codigo' => 'frm'],
            [
                'nombre' => 'farma',
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $moduleId = DB::table('modulos')->where('codigo', 'frm')->value('id');

        DB::table('vistas')->updateOrInsert(
            ['codigo' => 'prd'],
            [
                'modulo' => $moduleId,
                'nombre' => 'productos',
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $viewId = DB::table('vistas')->where('codigo', 'prd')->value('id');

        foreach (self::ACTIONS as $code => $name) {
            DB::table('actionsvistas')->updateOrInsert(
                ['codigo' => $code],
                [
                    'vista' => $viewId,
                    'nombre' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('actionsvistas')) {
            return;
        }

        $actionIds = DB::table('actionsvistas')
            ->whereIn('codigo', array_keys(self::ACTIONS))
            ->pluck('id');

        if (Schema::hasTable('permisos')) {
            $assigned = DB::table('permisos')
                ->whereIn('actionvista', $actionIds)
                ->pluck('actionvista');
            $actionIds = $actionIds->diff($assigned);
        }

        DB::table('actionsvistas')->whereIn('id', $actionIds)->delete();

        if (! Schema::hasTable('vistas')) {
            return;
        }

        $view = DB::table('vistas')->where('codigo', 'prd')->first();

        if ($view !== null && ! DB::table('actionsvistas')->where('vista', $view->id)->exists()) {
            $hasPermissions = Schema::hasTable('permisos')
                && DB::table('permisos')->where('vista', $view->id)->exists();

            if (! $hasPermissions) {
                DB::table('vistas')->where('id', $view->id)->delete();
            }
        }
    }
};
