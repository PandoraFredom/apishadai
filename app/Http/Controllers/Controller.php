<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\Log;
/**
 * Clase base Controller
 */
abstract class Controller
{
    /**
     * Envía una respuesta JSON normalizada para operaciones exitosas.
     *
     * Maneja AbstractPaginator, ResourceCollection, JsonResource y otros tipos.
     */
  protected function sendResponse(
    mixed $result,
    string $message = 'Operación exitosa',
    int $code = 200,
    bool $withPagination = false
): JsonResponse {

    // Estructura base dependiendo de $ascollection
    $response = [
        'message' => $message,
        'code'    => $code,
        'data'    => null,
    ];

    if ($withPagination) {
        $response['meta'] = null;
    }

    // Paginator directo (LengthAwarePaginator / Paginator)
    if ($result instanceof AbstractPaginator) {

        $response['data'] = $result->items();

        if ($withPagination) {
            $response['meta'] = [
                'total'         => $result->total(),
                'current_page'  => $result->currentPage(),
                'last_page'     => method_exists($result, 'lastPage') ? $result->lastPage() : null,
                'path'          => preg_replace(
                    '/api\//',
                    '',
                    ltrim(parse_url($result->path(), PHP_URL_PATH), '/')
                ),
            ];
        }

    } elseif ($result instanceof ResourceCollection) {

        $serialized = $result->response()->getData(true);
        $response['data'] = $serialized['data'] ?? null;

        $underlying = $result->resource ?? null;

        if ($withPagination) {
            if ($underlying instanceof AbstractPaginator) {
                $response['meta'] = [
                    'total'        => $underlying->total(),
                    'current_page' => $underlying->currentPage(),
                    'last_page'    => $underlying->lastPage(),
                    'path'         => preg_replace(
                        '/api\//',
                        '',
                        ltrim(parse_url($underlying->path(), PHP_URL_PATH), '/')
                    ),
                ];
            } else {
                $count = is_countable($result->collection) ? count($result->collection) : 0;

                $response['meta'] = [
                    'total'        => $count,
                    'current_page' => 1,
                    'last_page'    => 1,
                    'path'         => preg_replace('/api\//', '', ltrim(request()->path(), '/')),
                ];
            }
        }

    } elseif ($result instanceof JsonResource) {

        $serialized = $result->response()->getData(true);
        $response['data'] = $serialized['data'] ?? null;

    } else {

        $response['data'] = $result;
    }

    return response()->json($response, $code);
}


    /**
     * Envía una respuesta JSON para errores.
     */
    protected function sendError(
        string $message = 'Error en la operación',
        mixed $data = false,
        int $code = 500
    ): JsonResponse {
        return response()->json([
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ], $code);
    }

    /**
     * Envía una respuesta para descarga de archivos mediante stream.
     */
    protected function sendFileResponse(
        string $fileContent,
        string $fileName,
        string $contentType = 'application/octet-stream',
        int $statusCode = 200
    ) {
        return response()->streamDownload(
            function () use ($fileContent) {
                echo $fileContent;
            },
            $fileName,
            ['Content-Type' => $contentType]
        )->setStatusCode($statusCode);
    }

    protected function logError(string $location ,\Throwable $e){
        Log::error("Error en: $location", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }


}
