<?php

use App\Models\Pos\BotonPos;
use App\Models\Pos\ModuloPos;
use App\Models\Pos\VistaPos;
use App\Services\Headquarters\ChangeRecorder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $module = ModuloPos::where('codigo', 'compras')->lockForUpdate()->firstOrFail();
            $view = VistaPos::where('codigo', 'compras.documentos')->lockForUpdate()->firstOrFail();
            if ((string) $view->modulo_id !== (string) $module->id) {
                throw new LogicException('La vista de compras pertenece a otro módulo.');
            }

            // Publish the existing parents before the actions, in this transaction.
            $recorder = app(ChangeRecorder::class);
            $recorder->record($module, 'UPDATE');
            $recorder->record($view, 'UPDATE');
            foreach ([
                'compra.crear' => 'Crear compras',
                'compra.modificar' => 'Modificar compras',
                'compra.ver' => 'Ver compras',
                'compra.eliminar' => 'Eliminar compras',
                'compra.recibir' => 'Recibir compras',
            ] as $code => $name) {
                $button = BotonPos::where('codigo', $code)->lockForUpdate()->firstOrCreate(
                    ['codigo' => $code], ['vista_id' => $view->id, 'nombre' => $name, 'activo' => true],
                );
                if ((string) $button->vista_id !== (string) $view->id) {
                    throw new LogicException('La acción de compras pertenece a otra vista.');
                }
                if (! $button->wasRecentlyCreated) {
                    $recorder->record($button, 'UPDATE');
                }
            }
            // Preserve IDs, disabled metadata and all existing user/role assignments.
            // Catalog registration never grants actions to any account.
        }, 3);
    }

    public function down(): void
    {
        throw new LogicException('Conservar permisos e historial POS; retirar metadatos mediante una migración nueva.');
    }
};
