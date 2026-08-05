<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array{name: string, actions: array<string, string>}> */
    private const VIEWS = [
        'cmp' => [
            'name' => 'compras',
            'actions' => [
                'cmpbtn01' => 'listar', 'cmpbtn02' => 'crear', 'cmpbtn03' => 'buscar',
                'cmpbtn04' => 'actualizar', 'cmpbtn05' => 'eliminar',
                'cmpbtn06' => 'documento', 'cmpbtn07' => 'opciones',
            ],
        ],
        'ctp' => [
            'name' => 'tipos de compra',
            'actions' => [
                'ctpbtn01' => 'listar', 'ctpbtn02' => 'crear', 'ctpbtn03' => 'buscar',
                'ctpbtn04' => 'actualizar', 'ctpbtn05' => 'eliminar',
            ],
        ],
        'ctr' => [
            'name' => 'transacciones de compra',
            'actions' => [
                'ctrbtn01' => 'listar', 'ctrbtn02' => 'crear', 'ctrbtn03' => 'buscar',
                'ctrbtn04' => 'actualizar', 'ctrbtn05' => 'eliminar',
                'ctrbtn06' => 'documento', 'ctrbtn07' => 'opciones',
            ],
        ],
        'ttp' => [
            'name' => 'tipos de transaccion',
            'actions' => [
                'ttpbtn01' => 'listar', 'ttpbtn02' => 'crear', 'ttpbtn03' => 'buscar',
                'ttpbtn04' => 'actualizar', 'ttpbtn05' => 'eliminar',
            ],
        ],
    ];

    public function up(): void
    {
        if (Schema::hasTable('compra_tipo')) {
            $foreignKey = 'fk_compras_compra_tipo_1';
            $hasForeignKey = Schema::hasTable('compras') && DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'compras')
                ->where('CONSTRAINT_NAME', $foreignKey)
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists();

            if ($hasForeignKey) {
                DB::statement("ALTER TABLE compras DROP FOREIGN KEY {$foreignKey}");
            }

            try {
                DB::statement('ALTER TABLE compra_tipo MODIFY `int` INT NOT NULL AUTO_INCREMENT');
            } finally {
                if ($hasForeignKey) {
                    DB::statement("ALTER TABLE compras ADD CONSTRAINT {$foreignKey} FOREIGN KEY (`tipo`) REFERENCES compra_tipo (`int`)");
                }
            }

            DB::table('compra_tipo')->updateOrInsert(['int' => 1], ['descripcion' => 'Contado', 'updated_at' => now(), 'created_at' => now()]);
            DB::table('compra_tipo')->updateOrInsert(['int' => 2], ['descripcion' => 'Crédito', 'updated_at' => now(), 'created_at' => now()]);
        }

        if (Schema::hasTable('compra_transc')) {
            DB::statement('ALTER TABLE compra_transc MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if (Schema::hasTable('transc_tipo')) {
            foreach ([1 => 'Pago al contado', 2 => 'Abono', 3 => 'Nota de crédito'] as $id => $description) {
                DB::table('transc_tipo')->updateOrInsert(['id' => $id], ['descripcion' => $description, 'updated_at' => now(), 'created_at' => now()]);
            }
        }

        if (! Schema::hasTable('modulos') || ! Schema::hasTable('vistas') || ! Schema::hasTable('actionsvistas')) {
            return;
        }

        DB::table('modulos')->updateOrInsert(['codigo' => 'frm'], [
            'nombre' => 'farma', 'estado' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $moduleId = DB::table('modulos')->where('codigo', 'frm')->value('id');

        foreach (self::VIEWS as $viewCode => $definition) {
            DB::table('vistas')->updateOrInsert(['codigo' => $viewCode], [
                'modulo' => $moduleId,
                'nombre' => $definition['name'],
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $viewId = DB::table('vistas')->where('codigo', $viewCode)->value('id');

            foreach ($definition['actions'] as $code => $name) {
                DB::table('actionsvistas')->updateOrInsert(['codigo' => $code], [
                    'vista' => $viewId, 'nombre' => $name, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('actionsvistas')) {
            return;
        }

        foreach (self::VIEWS as $viewCode => $definition) {
            $actionIds = DB::table('actionsvistas')->whereIn('codigo', array_keys($definition['actions']))->pluck('id');

            if (Schema::hasTable('permisos')) {
                $actionIds = $actionIds->diff(DB::table('permisos')->whereIn('actionvista', $actionIds)->pluck('actionvista'));
            }

            DB::table('actionsvistas')->whereIn('id', $actionIds)->delete();
            $view = DB::table('vistas')->where('codigo', $viewCode)->first();

            if ($view !== null && ! DB::table('actionsvistas')->where('vista', $view->id)->exists()) {
                $assigned = Schema::hasTable('permisos') && DB::table('permisos')->where('vista', $view->id)->exists();

                if (! $assigned) {
                    DB::table('vistas')->where('id', $view->id)->delete();
                }
            }
        }
    }
};
