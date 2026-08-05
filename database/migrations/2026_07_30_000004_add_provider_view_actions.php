<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const ACTIONS = [
        'prvbtn01' => 'listar',
        'prvbtn02' => 'crear',
        'prvbtn03' => 'buscar',
        'prvbtn04' => 'actualizar',
        'prvbtn05' => 'eliminar',
        'prvbtn06' => 'imagen',
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
            ['codigo' => 'prv'],
            [
                'modulo' => $moduleId,
                'nombre' => 'proveedores',
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $viewId = DB::table('vistas')->where('codigo', 'prv')->value('id');

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
            $assignedIds = DB::table('permisos')
                ->whereIn('actionvista', $actionIds)
                ->pluck('actionvista');
            $actionIds = $actionIds->diff($assignedIds);
        }

        DB::table('actionsvistas')->whereIn('id', $actionIds)->delete();
    }
};
