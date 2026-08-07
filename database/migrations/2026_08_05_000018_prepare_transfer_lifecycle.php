<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transferencias')
            || ! Schema::hasTable('transferencia_estado')
            || ! Schema::hasTable('transferencias_tipo')) {
            return;
        }

        Schema::table('transferencias', function (Blueprint $table): void {
            if (! Schema::hasColumn('transferencias', 'enviado_at')) {
                $table->timestamp('enviado_at')->nullable()->after('estado');
            }

            if (! Schema::hasColumn('transferencias', 'recibido_at')) {
                $table->timestamp('recibido_at')->nullable()->after('enviado_at');
            }
        });

        $now = now();

        foreach (['ENVIADA', 'RECIBIDA'] as $status) {
            DB::table('transferencia_estado')->updateOrInsert(
                ['descripcion' => $status],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }

        if (DB::table('transferencias_tipo')->doesntExist()) {
            DB::table('transferencias_tipo')->insert([
                'descripcion' => 'TRASLADO ENTRE STOCKS',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $sentStatus = DB::table('transferencia_estado')->where('descripcion', 'ENVIADA')->value('id');
        $receivedStatus = DB::table('transferencia_estado')->where('descripcion', 'RECIBIDA')->value('id');

        DB::table('transferencias')->whereNull('usuario_recibe')->update([
            'estado' => $sentStatus,
            'enviado_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
            'recibido_at' => null,
        ]);
        DB::table('transferencias')->whereNotNull('usuario_recibe')->update([
            'estado' => $receivedStatus,
            'enviado_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
            'recibido_at' => DB::raw('COALESCE(updated_at, created_at, CURRENT_TIMESTAMP)'),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('transferencias')) {
            return;
        }

        Schema::table('transferencias', function (Blueprint $table): void {
            if (Schema::hasColumn('transferencias', 'recibido_at')) {
                $table->dropColumn('recibido_at');
            }

            if (Schema::hasColumn('transferencias', 'enviado_at')) {
                $table->dropColumn('enviado_at');
            }
        });
    }
};
