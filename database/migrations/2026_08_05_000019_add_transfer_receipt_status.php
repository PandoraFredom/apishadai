<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transferencias') || ! Schema::hasTable('transferencia_estado')) {
            return;
        }

        $now = now();
        DB::table('transferencia_estado')->updateOrInsert(
            ['descripcion' => 'PENDIENTE'],
            ['updated_at' => $now, 'created_at' => $now],
        );

        if (! Schema::hasColumn('transferencias', 'estado_recepcion')) {
            Schema::table('transferencias', function (Blueprint $table): void {
                $table->integer('estado_recepcion')->nullable()->after('estado');
            });
        }

        $pendingStatus = DB::table('transferencia_estado')->where('descripcion', 'PENDIENTE')->value('id');
        $receivedStatus = DB::table('transferencia_estado')->where('descripcion', 'RECIBIDA')->value('id');

        DB::table('transferencias')->whereNull('usuario_recibe')->update([
            'estado_recepcion' => $pendingStatus,
        ]);
        DB::table('transferencias')->whereNotNull('usuario_recibe')->update([
            'estado_recepcion' => $receivedStatus,
        ]);

        Schema::table('transferencias', function (Blueprint $table): void {
            $table->foreign('estado_recepcion', 'fk_transferencias_estado_recepcion')
                ->references('id')
                ->on('transferencia_estado')
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transferencias') || ! Schema::hasColumn('transferencias', 'estado_recepcion')) {
            return;
        }

        Schema::table('transferencias', function (Blueprint $table): void {
            $table->dropForeign('fk_transferencias_estado_recepcion');
            $table->dropColumn('estado_recepcion');
        });
    }
};
