<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pos_compras_centrales', 'cancelado_at')) {
            Schema::table('pos_compras_centrales', fn (Blueprint $t) => $t->timestamp('cancelado_at')->nullable());
        }
        if (! Schema::hasTable('pos_compra_cancelaciones')) {
            Schema::create('pos_compra_cancelaciones', function (Blueprint $t): void {
                $t->engine('InnoDB');
                $t->uuid('receipt_request_id')->primary();
                $t->uuid('request_id')->unique();
                $t->uuid('purchase_uuid');
                $t->uuid('deletion_request_id');
                $t->unsignedBigInteger('device_id');
                $t->unsignedBigInteger('stock_id');
                $t->unsignedBigInteger('usuario_pos_id');
                $t->char('huella', 64);
                $t->longText('datos');
                $t->longText('respuesta');
                $t->timestamp('created_at');
            });
        }
    }

    public function down(): void
    {
        throw new LogicException('Las cancelaciones de compras conservan su historial.');
    }
};
