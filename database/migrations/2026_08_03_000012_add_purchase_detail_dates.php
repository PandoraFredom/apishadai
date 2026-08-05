<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compra_detalle')) {
            return;
        }

        Schema::table('compra_detalle', function (Blueprint $table): void {
            if (! Schema::hasColumn('compra_detalle', 'fecha_elaboracion')) {
                $table->date('fecha_elaboracion')->nullable()->after('lote');
            }

            if (! Schema::hasColumn('compra_detalle', 'fecha_expiracion')) {
                $table->date('fecha_expiracion')->nullable()->after('fecha_elaboracion');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('compra_detalle')) {
            return;
        }

        $columns = array_values(array_filter(
            ['fecha_elaboracion', 'fecha_expiracion'],
            static fn (string $column): bool => Schema::hasColumn('compra_detalle', $column),
        ));

        if ($columns !== []) {
            Schema::table('compra_detalle', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
