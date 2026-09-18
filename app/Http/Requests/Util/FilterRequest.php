<?php

namespace App\Http\Requests\Util;

use App\Http\Requests\BaseRequest;
use App\Models\Utils\Filter\FilterItem;
use App\Models\Utils\Filter\FilterModel;
use Illuminate\Contracts\Validation\ValidationRule;
use LogicException;

use function in_array;

abstract class FilterRequest extends BaseRequest
{
    /**
     * Mapa campo => [operador interno, conector lógico].
     *
     * El cliente nunca decide el operador SQL. Cada clase hija asigna el
     * operador y el conector de cada campo que expone para filtrado.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    protected array $allowedOperators = [];

    private const SUPPORTED_OPERATORS = [
        '=',
        '!=',
        'LIKE',
        'LIKE_LEFT',
        'LIKE_RIGHT',
        'LIKE_ALL',
        'IN',
        'NOT IN',
        '>',
        '<',
        '>=',
        '<=',
        'BETWEEN',
        'IS',
        'IS NOT',
    ];

    protected int $maxValueLength = 100;

    protected function prepareForValidation(): void
    {
        if ($this->has('filterItem') && ! $this->has('filterItems')) {
            $this->merge([
                'filterItems' => $this->input('filterItem'),
            ]);
        }

        if ($this->has('name')) {
            $this->merge([
                'name' => $this->sanitizeString($this->input('name')),
            ]);
        }

        if (! $this->has('filterItems') || ! is_array($this->input('filterItems'))) {
            return;
        }

        $sanitizedItems = array_map(function (mixed $item): mixed {
            if (! is_array($item)) {
                return $item;
            }

            // Se conservan campos desconocidos para que la regla array:...
            // los rechace en vez de descartarlos silenciosamente.
            $item['key'] = $this->sanitizeKey((string) ($item['key'] ?? ''));
            $item['value'] = $this->sanitizeValue($item['value'] ?? '');

            return $item;
        }, $this->input('filterItems'));

        $this->merge(['filterItems' => $sanitizedItems]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'nullable',
                'string',
                'max:150',
                'regex:/^[a-zA-Z0-9\s\-_áéíóúÁÉÍÓÚñÑ]+$/',
            ],
            'filterItems' => ['required', 'array', 'min:1', 'max:10'],
            'filterItems.*' => ['required', 'array:key,value'],
            'filterItems.*.key' => [
                'required',
                'string',
                'max:50',
                'in:'.implode(',', $this->getAllowedKeys()),
            ],
            'filterItems.*.value' => [
                'required',
                'string',
                "max:{$this->maxValueLength}",
                'regex:/^[a-zA-Z0-9\s\-_%@.áéíóúÁÉÍÓÚñÑ]+$/',
            ],
        ];
    }

    protected function customMessages(): array
    {
        return [
            'name.string' => 'El nombre debe ser texto',
            'name.max' => 'El nombre no puede exceder 150 caracteres',
            'name.regex' => 'El nombre contiene caracteres no permitidos',

            'filterItems.required' => 'Debe proporcionar al menos un filtro',
            'filterItems.array' => 'Los filtros deben ser un array',
            'filterItems.min' => 'Debe proporcionar al menos un filtro',
            'filterItems.max' => 'No se permiten más de 10 filtros simultáneos',
            'filterItems.*.array' => 'Cada filtro solo puede contener key y value',

            'filterItems.*.key.required' => 'La clave del filtro es requerida',
            'filterItems.*.key.string' => 'La clave del filtro debe ser texto',
            'filterItems.*.key.max' => 'La clave del filtro no puede exceder 50 caracteres',
            'filterItems.*.key.in' => 'La clave del filtro no es válida',

            'filterItems.*.value.required' => 'El valor del filtro es requerido',
            'filterItems.*.value.string' => 'El valor del filtro debe ser texto',
            'filterItems.*.value.max' => 'El valor del filtro es demasiado largo',
            'filterItems.*.value.regex' => 'El valor contiene caracteres no permitidos',

        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $keys = array_column($this->input('filterItems', []), 'key');
            if (count($keys) !== count(array_unique($keys))) {
                $validator->errors()->add(
                    'filterItems',
                    'No se permiten claves duplicadas en los filtros'
                );
            }

            foreach ($this->input('filterItems', []) as $index => $item) {
                if (is_array($item)
                    && isset($item['value'])
                    && is_string($item['value'])
                    && $this->containsSqlInjection($item['value'])
                ) {
                    $validator->errors()->add(
                        "filterItems.{$index}.value",
                        'El valor contiene patrones no permitidos'
                    );
                }
            }
        });
    }

    /**
     * Convierte el contrato público key/value al modelo interno, agregando el
     * operador y el conector lógico asignados por la clase hija.
     */
    public function toFilterModel(): FilterModel
    {
        $itemsByKey = [];
        foreach ($this->input('filterItems', $this->input('filterItem', [])) as $item) {
            $key = $this->sanitizeKey((string) ($item['key'] ?? ''));
            $itemsByKey[$key] = $item;
        }

        $filterItems = [];
        foreach ($this->allowedOperators as $key => $configuration) {
            if (! array_key_exists($key, $itemsByKey)) {
                continue;
            }

            $value = $this->sanitizeValue($itemsByKey[$key]['value'] ?? '');
            [$operator, $logicalOperator] = $this->getConfigurationForKey($key);

            [$operator, $value] = $this->normalizeLikeOperator($operator, $value);

            $filterItems[] = new FilterItem($key, $value, $operator, $logicalOperator);
        }

        return new FilterModel(
            $this->sanitizeString($this->input('name')),
            ...$filterItems
        );
    }

    protected function sanitizeString(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    protected function sanitizeKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-z0-9_]/i', '', $key));
    }

    protected function sanitizeValue(mixed $value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        $value = preg_replace('/\s+/', ' ', $value);
        $value = str_replace(['<', '>', '"', "'", '\\', '`'], '', $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        return substr(trim($value), 0, $this->maxValueLength);
    }

    protected function containsSqlInjection(string $value): bool
    {
        $dangerousPatterns = [
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
            '/(0x[0-9a-f]+)/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function getAllowedKeys(): array
    {
        return array_keys($this->allowedOperators);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public function getAllowedOperators(): array
    {
        return $this->allowedOperators;
    }

    /** @param array<string, array{0: string, 1: string}> $operators */
    public function addAllowedOperators(array $operators): void
    {
        $this->allowedOperators = array_replace($this->allowedOperators, $operators);
    }

    /** @return array{0: string, 1: string} */
    private function getConfigurationForKey(string $key): array
    {
        $configuration = $this->allowedOperators[$key] ?? null;

        if (! is_array($configuration) || count($configuration) !== 2) {
            throw new LogicException("El filtro [{$key}] debe configurar operador y conector lógico");
        }

        $operator = strtoupper(trim((string) $configuration[0]));
        $logicalOperator = strtoupper(trim((string) $configuration[1]));

        if (! in_array($operator, self::SUPPORTED_OPERATORS, true)) {
            throw new LogicException("No hay un operador válido configurado para el filtro [{$key}]");
        }

        if (! in_array($logicalOperator, ['AND', 'OR'], true)) {
            throw new LogicException("No hay un conector lógico válido configurado para el filtro [{$key}]");
        }

        return [$operator, $logicalOperator];
    }

    /** @return array{0: string, 1: string} */
    private function normalizeLikeOperator(string $operator, string $value): array
    {
        if ($operator === 'LIKE_ALL') {
            return ['LIKE', str_contains($value, '%') ? $value : "%{$value}%"];
        }

        if ($operator === 'LIKE_LEFT') {
            return ['LIKE', str_contains($value, '%') ? $value : "%{$value}"];
        }

        if ($operator === 'LIKE_RIGHT') {
            return ['LIKE', str_ends_with($value, '%') ? $value : "{$value}%"];
        }

        return [$operator, $value];
    }
}
