<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsurePosManagementPermission
{
    public function handle(Request $request, Closure $next, string $view, string $action): Response
    {
        $allowed = $request->user() !== null && $request->header('Action-Btn') === $action
            && DB::table('permisos')->join('modulos', 'permisos.modulo', '=', 'modulos.id')
                ->join('vistas', 'permisos.vista', '=', 'vistas.id')->join('actionsvistas', 'permisos.actionvista', '=', 'actionsvistas.id')
                ->join('modulo_estados', 'modulos.estado', '=', 'modulo_estados.id')->join('vista_estados', 'vistas.estado', '=', 'vista_estados.id')
                ->where('permisos.usuario', $request->user()->getAuthIdentifier())
                ->where('modulos.codigo', 'pos')->where('vistas.codigo', $view)->where('actionsvistas.codigo', $action)
                ->whereColumn('vistas.modulo', 'modulos.id')->whereColumn('actionsvistas.vista', 'vistas.id')
                ->where('modulo_estados.descripcion', 'ACTIVO')->where('vista_estados.descripcion', 'ACTIVO')
                ->where(fn ($q) => $q->whereNull('permisos.lifetime')->orWhere('permisos.lifetime', '>=', now()))->exists();
        if (! $allowed) {
            return response()->json(['message' => 'No tienes permiso para administrar este recurso POS.', 'code' => 403, 'data' => null], 403);
        }
        if (! app()->environment(['local', 'testing']) && ! $request->isSecure()) {
            return response()->json(['message' => 'La administración POS requiere HTTPS.', 'code' => 400, 'data' => null], 400);
        }

        return $next($request);
    }
}
