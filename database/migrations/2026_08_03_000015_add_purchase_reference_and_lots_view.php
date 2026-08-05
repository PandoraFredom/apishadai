<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private const ACTIONS = [
        'ltsbtn01' => 'listar productos con lotes',
        'ltsbtn02' => 'ver lotes del producto',
    ];

    public function up(): void
    {
        if (Schema::hasTable('lotes') && ! Schema::hasColumn('lotes', 'compra')) {
            Schema::table('lotes', function (Blueprint $table): void {
                $table->integer('compra')->nullable()->after('id')->index();
                $table->foreign('compra')->references('id')->on('compras')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('modulos') || ! Schema::hasTable('vistas') || ! Schema::hasTable('actionsvistas')) {
            return;
        }

        $moduleId = DB::table('modulos')->where('codigo', 'frm')->value('id');

        if ($moduleId === null) {
            return;
        }

        DB::table('vistas')->updateOrInsert(
            ['codigo' => 'lts'],
            [
                'modulo' => $moduleId,
                'nombre' => 'lotes',
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $viewId = DB::table('vistas')->where('codigo', 'lts')->value('id');

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
        if (Schema::hasTable('actionsvistas')) {
            $actionIds = DB::table('actionsvistas')->whereIn('codigo', array_keys(self::ACTIONS))->pluck('id');

            if (Schema::hasTable('permisos')) {
                $assigned = DB::table('permisos')->whereIn('actionvista', $actionIds)->pluck('actionvista');
                $actionIds = $actionIds->diff($assigned);
            }

            DB::table('actionsvistas')->whereIn('id', $actionIds)->delete();
        }

        if (Schema::hasTable('vistas')) {
            $view = DB::table('vistas')->where('codigo', 'lts')->first();

            if ($view !== null && ! DB::table('actionsvistas')->where('vista', $view->id)->exists()) {
                $assigned = Schema::hasTable('permisos') && DB::table('permisos')->where('vista', $view->id)->exists();

                if (! $assigned) {
                    DB::table('vistas')->where('id', $view->id)->delete();
                }
            }
        }

        if (Schema::hasTable('lotes') && Schema::hasColumn('lotes', 'compra')) {
            Schema::table('lotes', function (Blueprint $table): void {
                $table->dropForeign(['compra']);
                $table->dropIndex(['compra']);
                $table->dropColumn('compra');
            });
        }
    }
};
