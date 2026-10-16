<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_transferencias_centrales', function (Blueprint $table): void {
            $table->uuid('solicitud_anulacion')->nullable()->unique();
            $table->char('huella_anulacion', 64)->nullable();
            $table->unsignedBigInteger('usuario_anula_pos')->nullable();
            $table->string('motivo_anulacion', 500)->nullable();
            $table->timestamp('anulacion_solicitada_at')->nullable();
            $table->timestamp('anulada_at')->nullable();
        });
    }

    public function down(): void
    {
        throw new LogicException('Conservar las solicitudes de anulación y su historial.');
    }
};
