<?php

namespace App\Http\Middleware;

use App\Interfaces\Config\PermisoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as FacadesLog;
use Symfony\Component\HttpFoundation\Response;

class ModuleSecurityMiddleware
{
    public function __construct(
        private PermisoService $permisoService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $resourceNames = $this->getResource($request);
        $action = $this->getAction($request);
        $userId = Auth::id();

        if (!$action) {
            return $this->sendResponse(null, 'Acción no especificada', 401);
        }

        $permissionStatus = $this->getPermissionStatus(
            $resourceNames['md'],
            $resourceNames['vw'],
            $userId,
            $action
        );

       /* FacadesLog::info(
            "Checking permissions for user {$userId} on module {$resourceNames['md']}, view {$resourceNames['vw']}, action {$action} Result: {$permissionStatus}"
        );*/

        if ($permissionStatus !== 'granted') {
            $message = $permissionStatus === 'expired'
                ? 'Permiso expirado'
                : 'Acceso denegado: no tiene permiso para esta acción';

            return $this->sendResponse(null, $message, 401);
        }

        return $next($request);
    }

    private function getPermissionStatus(string $moduleName, string $viewName, ?int $userId, string $action): string
    {
        if (!$userId) {
            return 'missing';
        }

        $joins = [
            [
                'table' => 'vistas',
                'first' => 'permisos.vista',
                'operator' => '=',
                'second' => 'vistas.id',
                'type' => 'inner'
            ],
            [
                'table' => 'modulos',
                'first' => 'permisos.modulo',
                'operator' => '=',
                'second' => 'modulos.id',
                'type' => 'inner'
            ],
            [
                'table' => 'actionsvistas',
                'first' => 'permisos.actionvista',
                'operator' => '=',
                'second' => 'actionsvistas.id',
                'type' => 'inner'
            ],
        ];

        $baseConditions = [
            ['modulos.nombre', '=', $moduleName],
            ['vistas.nombre', '=', $viewName],
            ['permisos.usuario', '=', $userId],
            ['actionsvistas.codigo', '=', $action],
        ];

        $unlimitedPermission = $this->permisoService->joinWhereFirst([
            ...$baseConditions,
            ['permisos.lifetime', 'IS', null],
        ], $joins);

        if ($unlimitedPermission) {
            return 'granted';
        }

        $activePermission = $this->permisoService->joinWhereFirst([
            ...$baseConditions,
            ['permisos.lifetime', '>=', now()],
        ], $joins);

        if ($activePermission) {
            return 'granted';
        }

        $expiredPermission = $this->permisoService->joinWhereFirst([
            ...$baseConditions,
            ['permisos.lifetime', '<', now()],
        ], $joins);

        return $expiredPermission ? 'expired' : 'missing';
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
