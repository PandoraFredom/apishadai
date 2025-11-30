<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResourceTypeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() === 'GET') {
            if ($request->has('rtype')) {
                $type = $request->get('rtype');

                if ($type === null || $type !== 'S' && $type !== 'T') {
                    return $this->sendResponse(null, 'No se proporciona el tipo de recurso', 400);
                }
            } else {
                return $this->sendResponse(null, 'No se proporciona el tipo de recurso', 400);
            }
        }
        return $next($request);
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
}
