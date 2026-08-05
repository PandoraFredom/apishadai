<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lotes')) {
            return;
        }

        Schema::table('lotes', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotes', 'costo')) {
                $table->decimal('costo', 10, 2)->nullable()->after('cantidad');
            }

            if (! Schema::hasColumn('lotes', 'isv')) {
                $table->boolean('isv')->nullable()->after('costo');
            }
        });

        $this->backfillFromPurchaseDetails();
        $this->synchronizeDistributionTax();
    }

    public function down(): void
    {
        if (! Schema::hasTable('lotes')) {
            return;
        }

        Schema::table('lotes', function (Blueprint $table): void {
            if (Schema::hasColumn('lotes', 'isv')) {
                $table->dropColumn('isv');
            }

            if (Schema::hasColumn('lotes', 'costo')) {
                $table->dropColumn('costo');
            }
        });
    }

    private function backfillFromPurchaseDetails(): void
    {
        if (! Schema::hasTable('compra_detalle')) {
            return;
        }

        DB::table('lotes')->orderBy('id')->chunkById(100, function ($lots): void {
            foreach ($lots as $lot) {
                $details = DB::table('compra_detalle')
                    ->where('producto', $lot->producto)
                    ->where('lote', $lot->lote)
                    ->when($lot->compra !== null, fn ($query) => $query->where('compra', $lot->compra))
                    ->when($lot->fecha_elab !== null, fn ($query) => $query->whereDate('fecha_elaboracion', $lot->fecha_elab))
                    ->when($lot->fecha_exp !== null, fn ($query) => $query->whereDate('fecha_expiracion', $lot->fecha_exp))
                    ->limit(2)
                    ->get(['compra', 'costo', 'isv']);

                if ($details->count() !== 1) {
                    continue;
                }

                $detail = $details->first();
                DB::table('lotes')->where('id', $lot->id)->update([
                    'compra' => $lot->compra ?? $detail->compra,
                    'costo' => $detail->costo,
                    'isv' => $detail->isv,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    private function synchronizeDistributionTax(): void
    {
        if (! Schema::hasTable('distribucion')) {
            return;
        }

        DB::table('distribucion')->orderBy('id')->chunkById(100, function ($distributions): void {
            foreach ($distributions as $distribution) {
                $tax = DB::table('lotes')->where('id', $distribution->lote)->value('isv');

                if ($tax !== null) {
                    DB::table('distribucion')->where('id', $distribution->id)->update([
                        'isv' => (bool) $tax,
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
};
