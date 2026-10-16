<?php
namespace Database\Seeders;

use App\Models\Pos\BotonPos;
use App\Models\Pos\ModuloPos;
use App\Models\Pos\VistaPos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class KardexPermissionCatalogSeeder extends Seeder
{
    public const MODULE = 'inventario';
    public const VIEW = 'inventario.kardex';
    public const ACTIONS = [
        'kardex.ver' => 'Ver Kardex',
        'kardex.filtrar' => 'Filtrar Kardex',
        'kardex.entrada' => 'Registrar entrada de Kardex',
        'kardex.salida' => 'Registrar salida de Kardex',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $module = ModuloPos::where('codigo', self::MODULE)->lockForUpdate()->firstOrCreate(
                ['codigo' => self::MODULE], ['nombre' => 'Inventario', 'activo' => true]);
            $view = VistaPos::where('codigo', self::VIEW)->lockForUpdate()->firstOrCreate(
                ['codigo' => self::VIEW], ['modulo_id' => $module->id, 'nombre' => 'Kardex', 'activo' => true]);
            if ((string) $view->modulo_id !== (string) $module->id) {
                throw new LogicException('La vista de Kardex pertenece a otro módulo.');
            }
            foreach (self::ACTIONS as $code => $name) {
                $button = BotonPos::where('codigo', $code)->lockForUpdate()->firstOrCreate(
                    ['codigo' => $code], ['vista_id' => $view->id, 'nombre' => $name, 'activo' => true]);
                if ((string) $button->vista_id !== (string) $view->id) {
                    throw new LogicException('La acción de Kardex pertenece a otra vista.');
                }
            }
            // Metadata only: never create permissions for a user or infer them from a role.
        }, 3);
    }
}
