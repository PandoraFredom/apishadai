<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array{name: string, actions: array<string, string>}>
     */
    private const VIEWS = [
        'pud' => [
            'name' => 'unidades de producto',
            'actions' => [
                'pudbtn01' => 'listar',
                'pudbtn02' => 'crear',
                'pudbtn03' => 'buscar',
                'pudbtn04' => 'actualizar',
                'pudbtn05' => 'eliminar',
            ],
        ],
        'pes' => [
            'name' => 'estados de producto',
            'actions' => [
                'pesbtn01' => 'listar',
                'pesbtn02' => 'crear',
                'pesbtn03' => 'buscar',
                'pesbtn04' => 'actualizar',
                'pesbtn05' => 'eliminar',
            ],
        ],
        'pct' => [
            'name' => 'categorías de producto',
            'actions' => [
                'pctbtn01' => 'listar',
                'pctbtn02' => 'crear',
                'pctbtn03' => 'buscar',
                'pctbtn04' => 'actualizar',
                'pctbtn05' => 'eliminar',
            ],
        ],
        'fpr' => [
            'name' => 'presentaciones farmacéuticas',
            'actions' => [
                'fprbtn01' => 'listar',
                'fprbtn02' => 'crear',
                'fprbtn03' => 'buscar',
                'fprbtn04' => 'actualizar',
                'fprbtn05' => 'eliminar',
            ],
        ],
        'fad' => [
            'name' => 'vías de administración',
            'actions' => [
                'fadbtn01' => 'listar',
                'fadbtn02' => 'crear',
                'fadbtn03' => 'buscar',
                'fadbtn04' => 'actualizar',
                'fadbtn05' => 'eliminar',
            ],
        ],
        'fam' => [
            'name' => 'familias de producto',
            'actions' => [
                'fambtn01' => 'listar',
                'fambtn02' => 'crear',
                'fambtn03' => 'buscar',
                'fambtn04' => 'actualizar',
                'fambtn05' => 'eliminar',
            ],
        ],
        'cnc' => [
            'name' => 'concentraciones',
            'actions' => [
                'cncbtn01' => 'listar',
                'cncbtn02' => 'crear',
                'cncbtn03' => 'buscar',
                'cncbtn04' => 'actualizar',
                'cncbtn05' => 'eliminar',
            ],
        ],
        'pac' => [
            'name' => 'principios activos',
            'actions' => [
                'pacbtn01' => 'listar',
                'pacbtn02' => 'crear',
                'pacbtn03' => 'buscar',
                'pacbtn04' => 'actualizar',
                'pacbtn05' => 'eliminar',
            ],
        ],
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
                'updated_at' => now(),
            ],
        );

        $moduleId = DB::table('modulos')->where('codigo', 'frm')->value('id');

        foreach (self::VIEWS as $viewCode => $definition) {
            DB::table('vistas')->updateOrInsert(
                ['codigo' => $viewCode],
                [
                    'modulo' => $moduleId,
                    'nombre' => $definition['name'],
                    'estado' => 1,
                    'updated_at' => now(),
                ],
            );

            $viewId = DB::table('vistas')->where('codigo', $viewCode)->value('id');

            foreach ($definition['actions'] as $actionCode => $actionName) {
                DB::table('actionsvistas')->updateOrInsert(
                    ['codigo' => $actionCode],
                    [
                        'vista' => $viewId,
                        'nombre' => $actionName,
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('actionsvistas')) {
            return;
        }

        $actionCodes = collect(self::VIEWS)
            ->flatMap(fn (array $definition): array => array_keys($definition['actions']));
        $actionIds = DB::table('actionsvistas')
            ->whereIn('codigo', $actionCodes)
            ->pluck('id');

        if (Schema::hasTable('permisos')) {
            $assigned = DB::table('permisos')
                ->whereIn('actionvista', $actionIds)
                ->pluck('actionvista');
            $actionIds = $actionIds->diff($assigned);
        }

        DB::table('actionsvistas')->whereIn('id', $actionIds)->delete();
    }
};
