<?php

namespace App\Repositories\Traits;

use Illuminate\Support\Facades\{DB, Log};
use function array_slice;
use function in_array;
use function is_array;

trait ErrorHandlerTrait
{
    /**
     * Ejecutar una consulta con manejo de errores
     */
    protected function executeQuery(callable $callback, string $method, $defaultReturn = null, array $context = [])
    {
        try {
            return $callback();
        } catch (\Illuminate\Database\QueryException $e) {
            $this->logDatabaseError($method, $e, $context);
            return $defaultReturn;
        } catch (\Exception $e) {
            $this->logGeneralError($method, $e, $context);
            return $defaultReturn;
        }
    }

    /**
     * Ejecutar una transacción con manejo de errores
     */
    protected function executeTransaction(callable $callback, string $method, array $context = []): bool
    {
        try {
            DB::beginTransaction();

            $result = $callback();

            DB::commit();

            return $result === false ? false : true;
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            $this->logDatabaseError($method, $e, $context);
            return false;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logGeneralError($method, $e, $context);
            return false;
        }
    }

    /**
     * Log de errores de base de datos
     */
    protected function logDatabaseError(string $method, \Illuminate\Database\QueryException $e, array $context = []): void
    {
        Log::error("Repository::{$method} - Database Error", [
            'model' => $this->getModelClass(),
            'error_code' => $e->errorInfo[1] ?? null,
            'sql_state' => $e->errorInfo[0] ?? null,
            'message' => $e->getMessage(),
            'sql' => $e->getSql(),
            'bindings' => $e->getBindings(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'context' => $this->sanitizeLogContext($context)
        ]);
    }

    /**
     * Log de errores generales
     */
    protected function logGeneralError(string $method, \Exception $e, array $context = []): void
    {
        Log::error("Repository::{$method} - Error", [
            'model' => $this->getModelClass(),
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->getCleanTrace($e),
            'context' => $this->sanitizeLogContext($context)
        ]);
    }

    /**
     * Sanitizar contexto para logs (evitar datos sensibles)
     */
    protected function sanitizeLogContext(array $context): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'token', 'api_key',
            'secret', 'auth_token', 'access_token', 'refresh_token',
            'card_number', 'cvv', 'pin'
        ];

        return $this->recursiveSanitize($context, $sensitiveKeys);
    }

    /**
     * Sanitizar recursivamente arrays
     */
    protected function recursiveSanitize(array $data, array $sensitiveKeys): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveSanitize($value, $sensitiveKeys);
            } elseif (in_array(strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = '***REDACTED***';
            }
        }

        return $data;
    }

    /**
     * Obtener trace limpio (solo últimas 5 líneas)
     */
    protected function getCleanTrace(\Exception $e): array
    {
        $trace = $e->getTrace();
        return array_slice($trace, 0, 5);
    }

    /**
     * Validar y lanzar excepción personalizada
     */
    protected function throwRepositoryException(string $message, int $code = 0, ?\Throwable $previous = null): void
    {
        throw new \App\Exceptions\RepositoryException($message, $code, $previous);
    }
}
