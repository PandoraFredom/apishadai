<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compras') && ! Schema::hasColumn('compras', 'descuento')) {
            Schema::table('compras', function (Blueprint $table): void {
                $table->decimal('descuento', 10, 2)->default(0)->after('subtotal');
            });
        }

        if (Schema::hasTable('compra_detalle') && ! Schema::hasColumn('compra_detalle', 'descuento')) {
            Schema::table('compra_detalle', function (Blueprint $table): void {
                $table->decimal('descuento', 10, 2)->default(0)->after('isv');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compra_detalle') && Schema::hasColumn('compra_detalle', 'descuento')) {
            Schema::table('compra_detalle', function (Blueprint $table): void {
                $table->dropColumn('descuento');
            });
        }

        if (Schema::hasTable('compras') && Schema::hasColumn('compras', 'descuento')) {
            Schema::table('compras', function (Blueprint $table): void {
                $table->dropColumn('descuento');
            });
        }
    }
};
