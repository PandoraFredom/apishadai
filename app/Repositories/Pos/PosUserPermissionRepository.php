<?php

namespace App\Repositories\Pos;

use App\DTO\PermisoDTO;
use App\Models\Pos\BotonPos;
use App\Models\Pos\ModuloPos;
use App\Models\Pos\PermisoUsuarioPos;
use App\Models\Pos\UsuarioPos;
use App\Models\Pos\VistaPos;
use App\Models\TipoTiempo;
use App\Utils\LifetimeResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosUserPermissionRepository
{
    public function list(int $user): array
    {
        return PermisoUsuarioPos::with(['moduloDetalle', 'vistaDetalle', 'accionDetalle'])->where('usuario', $user)->orderBy('id')->get()->map(fn ($p) => [
            'id' => $p->id, 'usuario' => $p->usuario, 'modulo' => ['id' => $p->modulo, 'nombre' => $p->moduloDetalle->nombre],
            'vista' => ['id' => $p->vista, 'nombre' => $p->vistaDetalle->nombre], 'actionvista' => ['id' => $p->actionvista, 'nombre' => $p->accionDetalle->nombre],
            'tipo_tiempo' => $p->tipo_tiempo, 'lifetime' => $p->lifetime, 'created_at' => $p->created_at])->all();
    }

    public function create(PermisoDTO $dto): bool
    {
        return DB::transaction(function () use ($dto): bool {
            UsuarioPos::whereKey($dto->usuario)->lockForUpdate()->firstOrFail();
            $module = ModuloPos::whereKey($dto->modulo)->lockForUpdate()->firstOrFail();
            $view = VistaPos::whereKey($dto->vista)->where('modulo_id', $dto->modulo)->lockForUpdate()->first();
            $button = BotonPos::whereKey($dto->actionvista)->where('vista_id', $dto->vista)->lockForUpdate()->first();
            if (! $module->activo || ! $view?->activo || ! $button?->activo) {
                throw ValidationException::withMessages(['actionvista' => 'Selecciona un módulo, vista y botón POS activos de la misma jerarquía.']);
            }
            if (PermisoUsuarioPos::where('usuario', $dto->usuario)->where('actionvista', $dto->actionvista)->exists()) {
                return false;
            }
            $time = TipoTiempo::whereKey($dto->tipo_tiempo)->firstOrFail();
            PermisoUsuarioPos::create(['usuario' => $dto->usuario, 'modulo' => $dto->modulo, 'vista' => $dto->vista, 'actionvista' => $dto->actionvista,
                'tipo_tiempo' => $dto->tipo_tiempo, 'lifetime' => LifetimeResolver::resolve($time)]);

            return true;
        }, 3);
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $owner = PermisoUsuarioPos::whereKey($id)->value('usuario');
            if ($owner === null) {
                return false;
            }
            UsuarioPos::whereKey($owner)->lockForUpdate()->firstOrFail();

            return (bool) PermisoUsuarioPos::whereKey($id)->lockForUpdate()->first()?->delete();
        }, 3);
    }

    public function grouped(int $user): array
    {
        if (! UsuarioPos::whereKey($user)->where('activo', true)->exists()) {
            return [];
        }

        return PermisoUsuarioPos::with(['moduloDetalle', 'vistaDetalle', 'accionDetalle'])->where('usuario', $user)
            ->where(fn ($q) => $q->whereNull('lifetime')->orWhere('lifetime', '>', now()->timestamp))->get()
            ->filter(fn ($p) => $p->moduloDetalle->activo && $p->vistaDetalle->activo && $p->accionDetalle->activo)
            ->groupBy('modulo')->map(fn ($group) => ['modulo_l' => ['codigo' => $group->first()->moduloDetalle->codigo],
                'vistas_l' => $group->groupBy('vista')->map(fn ($views) => ['vistai' => ['codigo' => $views->first()->vistaDetalle->codigo],
                    'accionesi' => $views->map(fn ($p) => ['codigo' => $p->accionDetalle->codigo])->values()->all()])->values()->all()])->values()->all();
    }
}
