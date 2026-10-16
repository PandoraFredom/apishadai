<?php

use App\Models\Pos\CatalogoPos;
use App\Services\Headquarters\ChangeRecorder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $rules = CatalogoPos::where('tipo', 'DESCUENTO')->get();
        if ($rules->groupBy('codigo')->contains(fn ($group): bool => $group->count() > 1)) {
            throw new RuntimeException('Hay reglas duplicadas entre stocks. Resuelve su identidad antes del despliegue global.');
        }
        foreach ($rules as $rule) {
            if (($rule->datos['version'] ?? 0) !== 2 || ! in_array($rule->datos['regla']['modo'] ?? '', ['HEREDAR', 'LOTE', 'EXCLUIR'], true)) {
                throw new RuntimeException('Selecciona la opción general de las reglas anteriores antes del despliegue global.');
            }
        }
        if ($rules->isNotEmpty() && ! app(ChangeRecorder::class)->enabled()) {
            throw new RuntimeException('Activa la publicación HQ antes de convertir las reglas existentes.');
        }
        Schema::table('catalogospos', function (Blueprint $table): void {
            $table->integer('stock_id')->nullable()->change();
        });
        if (! Schema::hasColumn('catalogospos', 'descuento_global_codigo')) {
            Schema::table('catalogospos', function (Blueprint $table): void {
                $table->string('descuento_global_codigo', 80)->nullable()->virtualAs("CASE WHEN tipo = 'DESCUENTO' THEN codigo ELSE NULL END");
                $table->unique('descuento_global_codigo', 'catalogospos_descuento_global_unique');
            });
        }
        DB::transaction(function (): void {
            DB::table('pos_descuento_control')->where('id', 1)->lockForUpdate()->firstOrFail();
            foreach (CatalogoPos::where('tipo', 'DESCUENTO')->whereNotNull('stock_id')->lockForUpdate()->get() as $rule) {
                $rule->stock_id = null;
                $rule->datos = array_replace($rule->datos, ['revision' => (int) $rule->datos['revision'] + 1]);
                $rule->save();
            }
        }, 3);
    }

    public function down(): void
    {
        throw new RuntimeException('El alcance global conserva identidades e historial y no se revierte automáticamente.');
    }
};
