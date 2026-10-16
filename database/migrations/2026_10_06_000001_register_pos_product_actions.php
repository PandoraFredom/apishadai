<?php

use App\Models\Pos\BotonPos;
use App\Models\Pos\ModuloPos;
use App\Models\Pos\VistaPos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Model saves publish parents before children to HQ in this same transaction.
        // Register metadata only: individual assignments remain in ShAdmin.
        DB::transaction(function (): void {
            $module = ModuloPos::where('codigo', 'inventario')->lockForUpdate()->firstOrFail();
            $view = VistaPos::firstOrCreate(['codigo' => 'inventario.productos'], [
                'modulo_id' => $module->id, 'nombre' => 'Productos y lotes', 'activo' => true,
            ]);
            if ($view->modulo_id !== $module->id) {
                throw new LogicException('La vista de productos pertenece a otro módulo.');
            }
            foreach (['crear' => 'Crear producto', 'modificar' => 'Modificar producto',
                'eliminar' => 'Eliminar producto', 'ver' => 'Ver producto'] as $action => $name) {
                $button = BotonPos::firstOrCreate(['codigo' => 'producto.'.$action], [
                    'vista_id' => $view->id, 'nombre' => $name, 'activo' => true,
                ]);
                if ($button->vista_id !== $view->id) {
                    throw new LogicException('La acción de productos pertenece a otra vista.');
                }
            }
        });
    }

    public function down(): void
    {
        throw new LogicException('Conservar permisos e historial POS; retirar metadatos mediante una migración nueva.');
    }
};
