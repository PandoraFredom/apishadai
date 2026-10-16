<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compra_detalle') && ! Schema::hasIndex('compra_detalle', 'compra_detalle_producto_compra_costo_index')) {
            Schema::table('compra_detalle', function (Blueprint $table): void {
                $table->index(['producto', 'compra', 'id', 'costo'], 'compra_detalle_producto_compra_costo_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compra_detalle') && Schema::hasIndex('compra_detalle', 'compra_detalle_producto_compra_costo_index')) {
            Schema::table('compra_detalle', function (Blueprint $table): void {
                $table->dropIndex('compra_detalle_producto_compra_costo_index');
            });
        }
    }
};
