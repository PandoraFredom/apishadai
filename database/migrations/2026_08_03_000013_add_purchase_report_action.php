<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACTION_CODE = 'cmpbtn08';

    public function up(): void
    {
        if (! Schema::hasTable('vistas') || ! Schema::hasTable('actionsvistas')) {
            return;
        }

        $viewId = DB::table('vistas')->where('codigo', 'cmp')->value('id');

        if ($viewId === null) {
            return;
        }

        DB::table('actionsvistas')->updateOrInsert(
            ['codigo' => self::ACTION_CODE],
            [
                'vista' => $viewId,
                'nombre' => 'ver reporte',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('actionsvistas')) {
            return;
        }

        $actionId = DB::table('actionsvistas')->where('codigo', self::ACTION_CODE)->value('id');

        if ($actionId === null) {
            return;
        }

        $assigned = Schema::hasTable('permisos')
            && DB::table('permisos')->where('actionvista', $actionId)->exists();

        if (! $assigned) {
            DB::table('actionsvistas')->where('id', $actionId)->delete();
        }
    }
};
