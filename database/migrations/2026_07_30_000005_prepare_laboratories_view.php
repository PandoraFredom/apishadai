<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private const ACTIONS = [
        'lbtbtn01' => 'listar',
        'lbtbtn02' => 'crear',
        'lbtbtn03' => 'buscar',
        'lbtbtn04' => 'actualizar',
        'lbtbtn05' => 'eliminar',
        'lbtbtn06' => 'imagen',
    ];

    public function up(): void
    {
        if (Schema::hasColumn('laboratorios', 'imagen')) {
            match (DB::connection()->getDriverName()) {
                'mysql', 'mariadb' => DB::statement(
                    'ALTER TABLE laboratorios MODIFY imagen MEDIUMBLOB NULL',
                ),
                'sqlsrv' => DB::statement(
                    'ALTER TABLE laboratorios ALTER COLUMN imagen VARBINARY(MAX) NULL',
                ),
                default => null,
            };
        }

        if (! Schema::hasTable('modulos') || ! Schema::hasTable('vistas') || ! Schema::hasTable('actionsvistas')) {
            return;
        }

        $moduleId = DB::table('modulos')->where('codigo', 'frm')->value('id');

        if ($moduleId === null) {
            $moduleId = DB::table('modulos')->insertGetId([
                'codigo' => 'frm',
                'nombre' => 'farma',
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('vistas')->updateOrInsert(
            ['codigo' => 'lbt'],
            [
                'modulo' => $moduleId,
                'nombre' => 'laboratorios',
                'estado' => 1,
                'updated_at' => now(),
            ],
        );

        $viewId = DB::table('vistas')->where('codigo', 'lbt')->value('id');

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
    }
};
