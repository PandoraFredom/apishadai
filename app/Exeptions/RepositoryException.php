<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class RepositoryException extends Exception
{
    protected array $context = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Renderizar la excepción como respuesta JSON
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->getCode() ?: 500,
            'data' => false,
        ], $this->getCode() ?: 500);
    }

    /**
     * Reportar la excepción
     */
    public function report(): void
    {
        \Illuminate\Support\Facades\Log::error('RepositoryException', [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context
        ]);
    }

    /**
     * Establecer contexto adicional
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Obtener contexto
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
