<?php
namespace Database\Seeders;

use App\Models\Pos\BotonPos;
use App\Models\Pos\ModuloPos;
use App\Models\Pos\VistaPos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProfitReportPermissionCatalogSeeder extends Seeder
{
    public const MODULE = 'administracion';
    public const VIEW = 'administracion.reporte.utilidades';
    public const ACTIONS = [
        'reporte.utilidades.ver' => 'Ver reporte de utilidades',
        'reporte.utilidades.filtrar' => 'Filtrar reporte de utilidades',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $module = ModuloPos::where('codigo', self::MODULE)->lockForUpdate()->firstOrCreate(
                ['codigo' => self::MODULE], ['nombre' => 'Administración', 'activo' => true]);
            $view = VistaPos::where('codigo', self::VIEW)->lockForUpdate()->firstOrCreate(
                ['codigo' => self::VIEW], ['modulo_id' => $module->id, 'nombre' => 'Reportes · Utilidades', 'activo' => true]);
            if ((string) $view->modulo_id !== (string) $module->id) {
                throw new LogicException('La vista del reporte de utilidades pertenece a otro módulo.');
            }
            foreach (self::ACTIONS as $code => $name) {
                $button = BotonPos::where('codigo', $code)->lockForUpdate()->firstOrCreate(
                    ['codigo' => $code], ['vista_id' => $view->id, 'nombre' => $name, 'activo' => true]);
                if ((string) $button->vista_id !== (string) $view->id) {
                    throw new LogicException('La acción del reporte de utilidades pertenece a otra vista.');
                }
            }
            // Metadata only: never create permissions for a user or infer them from a role.
        }, 3);
    }
}
