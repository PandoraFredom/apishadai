<?php

namespace App\Repositories\Traits;

use Illuminate\Support\Facades\Log;
use function array_map;
use function count;
use function in_array;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;

trait ConditionHandlerTrait
{
    /**
     * Lista blanca de columnas permitidas para filtros
     * Debe ser sobrescrito en cada repositorio específico
     */
    protected array $allowedColumns = [];

    /**
     * Lista blanca de tablas permitidas para JOINs
     * Debe ser sobrescrito en cada repositorio específico
     */
    protected array $allowedTables = [];

    /**
     * Sanitizar datos de entrada
     */
    protected function sanitizeData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // Validar nombre de columna
            if (!$this->isValidColumnName($key)) {
                continue;
            }

            // Sanitizar valor según tipo
            $sanitized[$key] = $this->sanitizeValue($value);
        }

        return $sanitized;
    }

    /**
     * Sanitizar condiciones
     */
    protected function sanitizeConditions(array $conditions): array
    {
        if ($this->isSingleCondition($conditions)) {
            // ['column', 'operator', 'value'] o ['column', 'operator', 'value', 'logicalOperator']
            return [
                $this->sanitizeColumnName($conditions[0]),
                $this->sanitizeOperator($conditions[1] ?? '='),
                $this->sanitizeValue($conditions[2] ?? null),
                $conditions[3] ?? 'AND'  // Preservar logicalOperator si existe
            ];
        }

        if ($this->isMultipleConditions($conditions)) {
            // [['col1', 'op1', 'val1', 'AND'], ['col2', 'op2', 'val2', 'OR']]
            return array_map(function ($condition) {
                if (count($condition) >= 2) {
                    return [
                        $this->sanitizeColumnName($condition[0]),
                        $this->sanitizeOperator($condition[1] ?? '='),
                        $this->sanitizeValue($condition[2] ?? null),
                        $condition[3] ?? 'AND'  // Preservar logicalOperator individual
                    ];
                }
                return $condition;
            }, $conditions);
        }

        // Array asociativo
        $sanitized = [];
        foreach ($conditions as $column => $value) {
            if (is_string($column) && $this->isValidColumnName($column)) {
                $sanitized[$this->sanitizeColumnName($column)] = $this->sanitizeValue($value);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitizar JOINs
     */
    protected function sanitizeJoins(array $tables): array
    {
        return array_filter(array_map(function ($join) {
            if (!is_array($join)) {
                return null;
            }

            $table = $join['table'] ?? null;
            $first = $join['first'] ?? null;
            $second = $join['second'] ?? null;

            // Validar tabla permitida
            if (!$this->isValidTableName($table)) {
                return null;
            }

            // Validar columnas
            if (!$this->isValidColumnName($first) || !$this->isValidColumnName($second)) {
                return null;
            }

            return [
                'table' => $this->sanitizeTableName($table),
                'first' => $this->sanitizeColumnName($first),
                'operator' => $this->sanitizeOperator($join['operator'] ?? '='),
                'second' => $this->sanitizeColumnName($second),
                'type' => $this->sanitizeJoinType($join['type'] ?? 'inner')
            ];
        }, $tables));
    }

    /**
     * Sanitizar selects
     */
    protected function sanitizeSelects(array $selects): array
    {
        return array_filter(array_map(function ($select) {
            // Permitir alias: 'table.column as alias' o 'table.*' o '*'
            if (preg_match('/^([a-z0-9_\.\*]+)(\s+as\s+([a-z0-9_]+))?$/i', $select, $matches)) {
                $column = $matches[1];
                $alias = $matches[3] ?? null;

                if ($this->isValidColumnName($column)) {
                    return $alias ? "$column as $alias" : $column;
                }
            }
            return null;
        }, $selects));
    }

    /**
     * Sanitizar nombre de columna
     */
    protected function sanitizeColumnName(string $column): string
    {
        // Permitir tabla.columna y alias
        $column = trim($column);
        $column = preg_replace('/[^a-z0-9_\.\*]/i', '', $column);

        return $column;
    }

    /**
     * Sanitizar nombre de tabla
     */
    protected function sanitizeTableName(string $table): string
    {
        $table = trim($table);
        return preg_replace('/[^a-z0-9_]/i', '', $table);
    }

    /**
     * Sanitizar operador
     */
    protected function sanitizeOperator(string $operator): string
    {
        $operator = strtoupper(trim($operator));

        $allowed = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'IS', 'IS NOT'];

        return in_array($operator, $allowed, true) ? $operator : '=';
    }

    /**
     * Sanitizar tipo de JOIN
     */
    protected function sanitizeJoinType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, ['inner', 'left', 'right', 'cross'], true) ? $type : 'inner';
    }

    /**
     * Sanitizar valor
     */
    protected function sanitizeValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return array_map([$this, 'sanitizeValue'], $value);
        }

        if (is_numeric($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            // Eliminar caracteres peligrosos
            $value = $this->removeDangerousPatterns($value);

            // Trim y limitar longitud
            $value = trim($value);
            $value = mb_substr($value, 0, 1000); // Límite de 1000 caracteres

            return $value;
        }

        return (string) $value;
    }

    /**
     * Remover patrones peligrosos de SQL injection
     */
    protected function removeDangerousPatterns(string $value): string
    {
        // Eliminar comentarios SQL
        $value = preg_replace('/(--|\/\*|\*\/|;)/', '', $value);

        // Eliminar caracteres de control
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        return $value;
    }

    /**
     * Validar nombre de columna
     */
    protected function isValidColumnName(string $column): bool
    {
        // Si hay whitelist configurada, validar contra ella
        if (!empty($this->allowedColumns)) {
            // Soportar tabla.columna
            $parts = explode('.', $column);
            $columnName = end($parts);

            return in_array($columnName, $this->allowedColumns, true) || $column === '*';
        }

        // Validación general: solo letras, números, puntos y guiones bajos
        // Reemplazar underscores por espacios para evitar emparejar subcadenas
        // ejemplo: 'phone_updated_at' -> 'phone updated at' (no match con 'update')
        $columnForKeywordCheck = str_replace('_', ' ', $column);

        return preg_match('/^[a-z0-9_\.\*]+$/i', $column) === 1
            && !preg_match('/\b(?:union|select|insert|update|delete|drop|exec|script)\b/i', $columnForKeywordCheck);
    }

    /**
     * Validar nombre de tabla
     */
    protected function isValidTableName(?string $table): bool
    {
        if (!$table) {
            return false;
        }

        // Si hay whitelist configurada, validar contra ella
        if (!empty($this->allowedTables)) {
            return in_array($table, $this->allowedTables, true);
        }

        // Validación general
        return preg_match('/^[a-z0-9_]+$/i', $table) === 1
            && !preg_match('/(union|select|insert|update|delete|drop|exec|script)/i', $table);
    }

    /**
     * Validar campos requeridos
     */
    protected function validateRequiredFields(array $data, array $required = []): void
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                throw new \InvalidArgumentException("El campo '{$field}' es requerido");
            }
        }
    }

    /**
     * Detecta inyección SQL en valores
     */
    protected function containsSqlInjection(string $value): bool
    {
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\bexec\b|\bexecute\b)/i',
            '/(;|\-\-|\/\*|\*\/)/i',
            '/(\bor\b.*=.*)/i',
            '/(\band\b.*=.*)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
