<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        //  web: __DIR__.'/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        //  commands: __DIR__.'/../routes/console.php',
        //   health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('api', [
             \App\Http\Middleware\DeviceSecurityMiddleware::class,
            //   \App\Http\Middleware\AppVersionSecurityMiddleware::class
        ]);

        $middleware->group('auth:api', [
            \App\Http\Middleware\JWTAuthenticationMiddleware::class,
            // \App\Http\Middleware\MatchTokenMiddleware::class,
            //\App\Http\Middleware\ModuleSecurityMiddleware::class
              \App\Http\Middleware\DeviceSecurityMiddleware::class,
            //   \App\Http\Middleware\AppVersionSecurityMiddleware::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                $response = [
                    'message' => 'Error de autenticación',
                    'code' => 401,
                    'data' => null,
                ];
                return response()->json($response, 401);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'El método HTTP utilizado no está permitido.',
                    'code' => 405,
                    'data' => null
                ], 401);
            }
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Ruta no encontrada',
                    'code' => 404,
                    'data' => null
                ], 404);
            }
        });
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Error en la base de datos:' . $e->getMessage(),
                    'code' => 404,
                    'data' => null
                ], 401);
            }
        });
        $exceptions->render(function (ConnectionException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Error de conexión:' . $e->getMessage(),
                    'code' => 503,
                    'data' => null
                ], 401);
            }
        });
        $exceptions->render(function (Exception $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => "Error General->$e",
                    'code' => 404,
                    'data' => null
                ], 401);
            }
        });

    })->create();
