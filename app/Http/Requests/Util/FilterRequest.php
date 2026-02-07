<?php

namespace App\Http\Requests\Util;

use App\Models\Utils\Filter\FilterItem;
use App\Models\Utils\Filter\FilterModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

use function array_map;
use function count;
use function in_array;
use function is_string;

class FilterRequest extends FormRequest
{
    /**
     * Campos permitidos para filtrar (whitelist)
     * Esto previene que se filtren campos sensibles o inexistentes
     */
    protected array $allowedKeys = [
        'docid',
        'pnombre',
        'papellido'
    ];

    /**
     * Operadores permitidos para filtros
     */
    protected array $allowedOperators = [
        '=',
        '!=',
        'LIKE',
        'LIKE_LEFT',      // %value (búsqueda al inicio)
        'LIKE_RIGHT',     // value% (búsqueda al final)
        'LIKE_ALL',       // %value% (búsqueda en cualquier lugar)
        'IN',
        '>',
        '<',
        '>=',
        '<=',
        'BETWEEN',
    ];

    /**
     * Longitud máxima para valores de búsqueda
     */
    protected int $maxValueLength = 100;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepara los datos antes de la validación
     */
    protected function prepareForValidation(): void
    {
        // Normalizar: aceptar tanto "filterItem" como "filterItems"
        if ($this->has('filterItem') && !$this->has('filterItems')) {
            $this->merge([
                'filterItems' => $this->input('filterItem')
            ]);
        }

        // Normalizar logicalOperator: AND u OR (por defecto AND)
        if ($this->has('logicalOperator')) {
            $operator = strtoupper(trim($this->input('logicalOperator')));
            if (!in_array($operator, ['AND', 'OR'], true)) {
                $this->merge(['logicalOperator' => 'AND']);
            }
        } else {
            $this->merge(['logicalOperator' => 'AND']);
        }

        // Sanitizar el nombre si existe
        if ($this->has('name')) {
            $this->merge([
                'name' => $this->sanitizeString($this->input('name'))
            ]);
        }

        // Sanitizar filterItems
        if ($this->has('filterItems')) {
            $sanitizedItems = array_map(fn($item) => [
                'key' => $this->sanitizeKey($item['key'] ?? ''),
                'value' => $this->sanitizeValue($item['value'] ?? ''),
                'operator' => $this->sanitizeOperator($item['operator'] ?? '='),
                'logicalOperator' => $item['logicalOperator'] ?? 'AND'
            ], $this->input('filterItems', []));

            $this->merge(['filterItems' => $sanitizedItems]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'nullable',
                'string',
                'max:150',
                'regex:/^[a-zA-Z0-9\s\-_áéíóúÁÉÍÓÚñÑ]+$/', // Solo alfanuméricos, espacios, guiones y acentos
            ],
            'filterItems' => 'required|array|min:1|max:10', // Limitar cantidad de filtros
            'filterItems.*.key' => [
                'required',
                'string',
                'max:50',
                'in:' . implode(',', $this->allowedKeys), // Solo claves permitidas
            ],
            'filterItems.*.value' => [
                'required',
                'string',
                "max:{$this->maxValueLength}",
                'regex:/^[a-zA-Z0-9\s\-_%@.áéíóúÁÉÍÓÚñÑ]+$/', // Solo caracteres seguros y '%'
            ],
            'filterItems.*.operator' => [
                'required',
                'string',
                'max:10',
                'in:' . implode(',', $this->allowedOperators), // Solo operadores permitidos
            ],
            'filterItems.*.logicalOperator' => [
                'nullable',
                'string',
                'in:AND,OR', // Solo AND u OR (opcional, por defecto AND)
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Nombre
            'name.string' => 'El nombre debe ser texto',
            'name.max' => 'El nombre no puede exceder 150 caracteres',
            'name.regex' => 'El nombre contiene caracteres no permitidos',

            // FilterItems general
            'filterItems.required' => 'Debe proporcionar al menos un filtro',
            'filterItems.array' => 'Los filtros deben ser un array',
            'filterItems.min' => 'Debe proporcionar al menos un filtro',
            'filterItems.max' => 'No se permiten más de 10 filtros simultáneos',

            // FilterItems key
            'filterItems.*.key.required' => 'La clave del filtro es requerida',
            'filterItems.*.key.string' => 'La clave del filtro debe ser texto',
            'filterItems.*.key.max' => 'La clave del filtro no puede exceder 50 caracteres',
            'filterItems.*.key.in' => 'La clave del filtro no es válida',

            // FilterItems value
            'filterItems.*.value.required' => 'El valor del filtro es requerido',
            'filterItems.*.value.string' => 'El valor del filtro debe ser texto',
            'filterItems.*.value.max' => 'El valor del filtro es demasiado largo',
            'filterItems.*.value.regex' => 'El valor contiene caracteres no permitidos',

            // FilterItems operator
            'filterItems.*.operator.required' => 'El operador del filtro es requerido',
            'filterItems.*.operator.string' => 'El operador debe ser texto',
            'filterItems.*.operator.max' => 'El operador no puede exceder 10 caracteres',
            'filterItems.*.operator.in' => 'El operador del filtro no es válido: use =, !=, LIKE, LIKE_LEFT, LIKE_RIGHT, LIKE_ALL, IN, >, <, >=, <=, BETWEEN',

            // FilterItems logicalOperator
            'filterItems.*.logicalOperator.string' => 'El operador lógico debe ser texto',
            'filterItems.*.logicalOperator.in' => 'El operador lógico debe ser AND u OR',
        ];
    }

    /**
     * Validación adicional después de las reglas básicas
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar que no haya claves duplicadas
            $keys = array_column($this->input('filterItems', []), 'key');
            if (count($keys) !== count(array_unique($keys))) {
                $validator->errors()->add(
                    'filterItems',
                    'No se permiten claves duplicadas en los filtros'
                );
            }

            // Validar que los valores no contengan patrones de inyección SQL
            foreach ($this->input('filterItems', []) as $index => $item) {
                if ($this->containsSqlInjection($item['value'])) {
                    $validator->errors()->add(
                        "filterItems.{$index}.value",
                        'El valor contiene patrones no permitidos'
                    );
                }
            }
        });
    }

    /**
     * Summary of failedValidation
     * @param Validator $validator
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $response = [
            'message' => $validator->errors()->first(),
            'code' => 400,
            'data' => null,
        ];
        http_response_code(400);
        exit(json_encode($response));
    }

    /**
     * Convierte el request a FilterModel
     * Traduce LIKE_LEFT, LIKE_RIGHT, LIKE_ALL a LIKE con % apropiados
     * Cada FilterItem tiene su propio logicalOperator (AND u OR)
     */
    public function toFilterModel(): FilterModel
    {
        $filterItemsRaw = $this->input('filterItems', []);
        $filterItems = [];

        foreach ($filterItemsRaw as $item) {
            $key = $this->sanitizeKey($item['key'] ?? '');
            $value = $this->sanitizeValue($item['value'] ?? '');
            $operator = $this->sanitizeOperator($item['operator'] ?? '=');
            $logicalOperator = strtoupper($item['logicalOperator'] ?? 'AND');

            // Traducir LIKE_* a LIKE con % apropiados
            if ($operator === 'LIKE_ALL') {
                $operator = 'LIKE';
                // Envolver solo si el usuario no lo hizo explícitamente
                if (strpos($value, '%') === false) {
                    $value = "%{$value}%";
                }
            } elseif ($operator === 'LIKE_LEFT') {
                $operator = 'LIKE';
                // Prefijo: %value
                if (strpos($value, '%') === false) {
                    $value = "%{$value}";
                }
            } elseif ($operator === 'LIKE_RIGHT') {
                $operator = 'LIKE';
                // Sufijo: value%
                if (strrpos($value, '%') !== strlen($value) - 1) {
                    $value = "{$value}%";
                }
            }

            // Crear FilterItem con logicalOperator (cómo se conecta con el siguiente)
            $filterItems[] = new FilterItem($key, $value, $operator, $logicalOperator);
        }

        return new FilterModel(
            $this->sanitizeString($this->input('name')),
            ...$filterItems
        );
    }

    /**
     * Obtener el operador lógico (para compatibilidad backwards, retorna el del primer item)
     */
    public function getLogicalOperator(): string
    {
        $filterItems = $this->input('filterItems', []);
        if (!empty($filterItems) && isset($filterItems[0]['logicalOperator'])) {
            return $filterItems[0]['logicalOperator'];
        }
        return 'AND';
    }

    /**
     * Sanitiza una cadena de texto general
     */
    protected function sanitizeString(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Eliminar espacios al inicio y final
        $value = trim($value);

        // Eliminar caracteres de control
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        // Escapar HTML
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return $value;
    }

    /**
     * Sanitiza una clave de filtro
     */
    protected function sanitizeKey(string $key): string
    {
        // Solo permitir letras, números y guiones bajos
        $key = preg_replace('/[^a-z0-9_]/i', '', $key);

        // Convertir a minúsculas
        return strtolower($key);
    }

    /**
     * Sanitiza un valor de filtro
     */
    protected function sanitizeValue(mixed $value): string
    {
        if (!is_string($value)) {
            $value = (string) $value;
        }

        // Eliminar espacios múltiples
        $value = preg_replace('/\s+/', ' ', $value);

        // Eliminar caracteres peligrosos
        $value = str_replace(['<', '>', '"', "'", '\\', '`'], '', $value);

        // Eliminar caracteres de control
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        // Trim
        $value = trim($value);

        // Limitar longitud
        return substr($value, 0, $this->maxValueLength);
    }

    /**
     * Sanitiza un operador de filtro
     * Acepta: =, !=, LIKE, LIKE_LEFT, LIKE_RIGHT, LIKE_ALL, IN, >, <, >=, <=, BETWEEN
     */
    protected function sanitizeOperator(string $operator): string
    {
        // Convertir a mayúsculas y limpiar espacios
        $operator = strtoupper(trim($operator));

        // Validar que esté en la lista de operadores permitidos
        if (!in_array($operator, $this->allowedOperators, true)) {
            // Retornar operador por defecto si no es válido
            return '=';
        }

        return $operator;
    }

    /**
     * Detecta patrones comunes de inyección SQL
     */
    protected function containsSqlInjection(string $value): bool
    {
        // Patrones peligrosos de SQL injection
        $dangerousPatterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\bexec\b|\bexecute\b)/i',
            '/(;|\-\-|\/\*|\*\/)/i', // Caracteres de comentario SQL
            '/(\bor\b.*=.*)/i',
            '/(\band\b.*=.*)/i',
            '/(0x[0-9a-f]+)/i', // Números hexadecimales
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener las claves permitidas (útil para otros componentes)
     */
    public function getAllowedKeys(): array
    {
        return $this->allowedKeys;
    }

    /**
     * Agregar claves permitidas dinámicamente si es necesario
     */
    public function addAllowedKeys(array $keys): void
    {
        $this->allowedKeys = array_unique([...$this->allowedKeys, ...$keys]);
    }

    /**
     * Obtener los operadores permitidos (útil para otros componentes)
     */
    public function getAllowedOperators(): array
    {
        return $this->allowedOperators;
    }

    /**
     * Agregar operadores permitidos dinámicamente si es necesario
     */
    public function addAllowedOperators(array $operators): void
    {
        $this->allowedOperators = array_unique([...$this->allowedOperators, ...$operators]);
    }
}
