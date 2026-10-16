<?php

namespace Database\Seeders;

use App\Models\Pos\BotonPos;
use App\Models\Pos\ModuloPos;
use App\Models\Pos\VistaPos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class TransferPermissionCatalogSeeder extends Seeder
{
    public const VIEWS = [
        'transferencias.enviadas' => ['Enviadas', [
            'transferencia.enviadas.ver' => 'Ver enviadas',
            'transferencia.enviadas.filtrar' => 'Filtrar enviadas',
            'transferencia.enviadas.enviar' => 'Crear y enviar',
            'transferencia.enviadas.anular' => 'Anular',
            'transferencia.enviadas.imprimir' => 'Imprimir',
        ]],
        'transferencias.recibidas' => ['Recibidas', [
            'transferencia.recibidas.ver' => 'Ver recibidas',
            'transferencia.recibidas.filtrar' => 'Filtrar recibidas',
            'transferencia.recibidas.recibir' => 'Recibir',
        ]],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $module = ModuloPos::where('codigo', 'transferencias')->lockForUpdate()->firstOrCreate(
                ['codigo' => 'transferencias'], ['nombre' => 'Transferencias', 'activo' => true],
            );
            $legacy = VistaPos::where('codigo', 'transferencias.documentos')->lockForUpdate()->first();
            if ($legacy && (string) $legacy->modulo_id !== (string) $module->id) {
                throw new LogicException('La vista de transferencias pertenece a otro módulo.');
            }
            foreach (self::VIEWS as $code => [$name, $actions]) {
                $view = VistaPos::where('codigo', $code)->lockForUpdate()->firstOrCreate(
                    ['codigo' => $code], ['modulo_id' => $module->id, 'nombre' => $name, 'activo' => true],
                );
                if ((string) $view->modulo_id !== (string) $module->id) {
                    throw new LogicException('La vista de transferencias pertenece a otro módulo.');
                }
                foreach ($actions as $action => $label) {
                    $button = BotonPos::where('codigo', $action)->lockForUpdate()->firstOrCreate(
                        ['codigo' => $action], ['vista_id' => $view->id, 'nombre' => $label, 'activo' => true],
                    );
                    if ((string) $button->vista_id === (string) $view->id) {
                        continue;
                    }
                    throw new LogicException('La acción de transferencias pertenece a otra vista.');
                }
            }
            if ($legacy && $legacy->activo) {
                $legacy->update(['activo' => false]);
            }
            foreach (['transferencia.ver', 'transferencia.enviar', 'transferencia.recibir', 'transferencia.anular', 'transferencia.imprimir'] as $oldCode) {
                $old = BotonPos::where('codigo', $oldCode)->lockForUpdate()->first();
                if ($old && $old->activo) {
                    $old->update(['activo' => false]);
                }
            }
        }, 3);
    }
}
