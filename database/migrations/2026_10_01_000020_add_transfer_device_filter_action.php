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

        $viewId = DB::table('vistas')->where('codigo', 'trf')->value('id');

        if ($viewId !== null) {
            DB::table('actionsvistas')->updateOrInsert(['codigo' => 'trfbtn07'], [
                'vista' => $viewId,
                'nombre' => 'filtrar por dispositivo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('actionsvistas')) {
            return;
        }

        $actionId = DB::table('actionsvistas')->where('codigo', 'trfbtn07')->value('id');

        if ($actionId !== null && (! Schema::hasTable('permisos') || ! DB::table('permisos')->where('actionvista', $actionId)->exists())) {
            DB::table('actionsvistas')->where('id', $actionId)->delete();
        }
    }
};
