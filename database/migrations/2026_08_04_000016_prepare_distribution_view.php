<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACTIONS = [
        'dstbtn01' => 'listar productos para distribucion',
        'dstbtn02' => 'ver lotes y precios del producto',
        'dstbtn03' => 'guardar precios y descuentos por lote',
    ];

    public function up(): void
    {
        if (Schema::hasTable('distribucion')) {
            $hasUniqueLot = collect(Schema::getIndexes('distribucion'))->contains(
                static fn (array $index): bool => ($index['unique'] ?? false) && ($index['columns'] ?? []) === ['lote'],
            );

            if (! $hasUniqueLot) {
                Schema::table('distribucion', function (Blueprint $table): void {
                    $table->unique('lote', 'distribucion_lote_unique');
                });
            }
        }

        if (! Schema::hasTable('modulos') || ! Schema::hasTable('vistas') || ! Schema::hasTable('actionsvistas')) {
            return;
        }

        $moduleId = DB::table('modulos')->where('codigo', 'frm')->value('id');

        if ($moduleId === null) {
            return;
        }

        DB::table('vistas')->updateOrInsert(
            ['codigo' => 'dst'],
            ['modulo' => $moduleId, 'nombre' => 'distribucion', 'estado' => 1, 'created_at' => now(), 'updated_at' => now()],
        );
        $viewId = DB::table('vistas')->where('codigo', 'dst')->value('id');

        foreach (self::ACTIONS as $code => $name) {
            DB::table('actionsvistas')->updateOrInsert(
                ['codigo' => $code],
                ['vista' => $viewId, 'nombre' => $name, 'created_at' => now(), 'updated_at' => now()],
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
            $view = DB::table('vistas')->where('codigo', 'dst')->first();

            if ($view !== null && ! DB::table('actionsvistas')->where('vista', $view->id)->exists()) {
                $assigned = Schema::hasTable('permisos') && DB::table('permisos')->where('vista', $view->id)->exists();

                if (! $assigned) {
                    DB::table('vistas')->where('id', $view->id)->delete();
                }
            }
        }

        if (Schema::hasTable('distribucion')) {
            $hasIndex = collect(Schema::getIndexes('distribucion'))->contains(
                static fn (array $index): bool => ($index['name'] ?? '') === 'distribucion_lote_unique',
            );

            if ($hasIndex) {
                Schema::table('distribucion', function (Blueprint $table): void {
                    $table->dropUnique('distribucion_lote_unique');
                });
            }
        }
    }
};
