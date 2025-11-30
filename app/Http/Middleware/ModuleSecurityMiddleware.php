<?php

namespace App\Http\Middleware;

use App\Models\Permisos;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ModuleSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $resourceNames = $this->getResource($request);
        $action = $this->getAction($request);

        if (!$action) {
            return $this->sendResponse(null, 'Acción no especificada', 401);
        }

        $permiso = DB::table('permisos')
            ->join('vistas', 'permisos.vista', '=', 'vistas.id')
            ->join('modulos', 'permisos.modulo', '=', 'modulos.id')
            ->join('actionsvistas', 'permisos.actionvista', '=', 'actionsvistas.id')
            ->where('modulos.nombre', $resourceNames['md'])
            ->where('vistas.nombre', $resourceNames['vw'])
            ->where('permisos.usuario', auth()->id())
            ->where('actionsvistas.codigo', $action)

            ->where(function ($q) {
                $q->whereNull('permisos.lifetime')
                    ->orWhere('permisos.lifetime', '>=', now());
            })
            ->exists();

        if (!$permiso) {
            return $this->sendResponse(null, 'Acceso denegado o permiso expirado', 401);
        }

        return $next($request);
    }


    private function getResource(Request $request)
    {
        $partes = explode('/', trim(parse_url($request->url(), PHP_URL_PATH), '/'));

        if (!empty($partes) && $partes[0] === 'api') {
            array_shift($partes);
        }

        if (!empty($partes) && is_numeric(end($partes))) {
            array_pop($partes);
        }

        return [
            'md' => $partes[0] ?? '',
            'vw' => $partes[1] ?? '',
            'ac' => $partes[2] ?? '',
        ];
    }



    private function sendResponse($result, $message, $code)
    {
        $response = [
            'message' => $message,
            'code' => $code,
            'data' => $result,
        ];
        return response()->json($response, $code);
    }

    private function getAction(Request $request)
    {
        // check if exists an action in the header
        $action = $request->header('Action-Btn');
        if ($action) {
            return $action;
        }
        // if not, return null
        return null;
    }
}
