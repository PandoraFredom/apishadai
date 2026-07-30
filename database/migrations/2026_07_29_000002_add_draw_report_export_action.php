<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vistas') || ! Schema::hasTable('actionsvistas')) {
            return;
        }

        $viewId = DB::table('vistas')
            ->where('codigo', 'rptstr')
            ->value('id');

        if ($viewId === null) {
            return;
        }

        DB::table('actionsvistas')->updateOrInsert(
            ['codigo' => 'rptstrbtn10'],
            [
                'vista' => $viewId,
                'nombre' => 'exportar',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('actionsvistas')) {
            return;
        }

        DB::table('actionsvistas')
            ->where('codigo', 'rptstrbtn10')
            ->delete();
    }
};
