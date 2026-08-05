<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ACTION_CODE = 'cmpbtn09';

    public function up(): void
    {
        if (Schema::hasTable('compras')) {
            Schema::table('compras', function (Blueprint $table): void {
                if (! Schema::hasColumn('compras', 'kardex_enviado_at')) {
                    $table->timestamp('kardex_enviado_at')->nullable()->after('estado')->index();
                }

                if (! Schema::hasColumn('compras', 'kardex_usuario')) {
                    $table->unsignedBigInteger('kardex_usuario')->nullable()->after('kardex_enviado_at');
                    $table->foreign('kardex_usuario')->references('id')->on('users')->restrictOnDelete();
                }
            });
        }

        if (! Schema::hasTable('vistas') || ! Schema::hasTable('actionsvistas')) {
            return;
        }

        $viewId = DB::table('vistas')->where('codigo', 'cmp')->value('id');

        if ($viewId !== null) {
            DB::table('actionsvistas')->updateOrInsert(
                ['codigo' => self::ACTION_CODE],
                [
                    'vista' => $viewId,
                    'nombre' => 'enviar a kardex',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('actionsvistas')) {
            $actionId = DB::table('actionsvistas')->where('codigo', self::ACTION_CODE)->value('id');
            $assigned = $actionId !== null && Schema::hasTable('permisos')
                && DB::table('permisos')->where('actionvista', $actionId)->exists();

            if ($actionId !== null && ! $assigned) {
                DB::table('actionsvistas')->where('id', $actionId)->delete();
            }
        }

        if (Schema::hasTable('compras')) {
            Schema::table('compras', function (Blueprint $table): void {
                if (Schema::hasColumn('compras', 'kardex_usuario')) {
                    $table->dropForeign(['kardex_usuario']);
                    $table->dropColumn('kardex_usuario');
                }

                if (Schema::hasColumn('compras', 'kardex_enviado_at')) {
                    $table->dropIndex(['kardex_enviado_at']);
                    $table->dropColumn('kardex_enviado_at');
                }
            });
        }
    }
};
