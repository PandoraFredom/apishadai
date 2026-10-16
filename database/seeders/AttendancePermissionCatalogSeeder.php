<?php

namespace Database\Seeders;

use App\Models\Pos\BotonPos;
use App\Models\Pos\ModuloPos;
use App\Models\Pos\VistaPos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendancePermissionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $module = ModuloPos::where('codigo', 'asistencia')->lockForUpdate()->firstOrCreate(
                ['codigo' => 'asistencia'], ['nombre' => 'Asistencia', 'activo' => true]);
            $view = VistaPos::where('codigo', 'asistencia.registro')->lockForUpdate()->firstOrCreate(
                ['codigo' => 'asistencia.registro'], ['modulo_id' => $module->id, 'nombre' => 'Registro de asistencia', 'activo' => true]);
            if ((string) $view->modulo_id !== (string) $module->id) {
                throw new \LogicException('La vista de asistencia pertenece a otro módulo.');
            }
            foreach (['asistencia.jornada' => 'Registrar entrada y salida', 'asistencia.break' => 'Iniciar y finalizar break'] as $code => $name) {
                $button = BotonPos::where('codigo', $code)->lockForUpdate()->firstOrCreate(
                    ['codigo' => $code], ['vista_id' => $view->id, 'nombre' => $name, 'activo' => true]);
                if ((string) $button->vista_id !== (string) $view->id) {
                    throw new \LogicException('La acción de asistencia pertenece a otra vista.');
                }
            }
        }, 3);
    }
}
