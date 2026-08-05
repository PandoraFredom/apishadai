<?php

namespace App\Services\Farma;

use App\Models\Farma\Concentracion;
use App\Models\Farma\FamAdministracion;
use App\Models\Farma\Familia;
use App\Models\Farma\FamPresentacion;
use App\Models\Farma\PrincipalActivo;
use App\Models\Farma\ProdCategoria;
use App\Models\Farma\ProdEstado;
use App\Models\Farma\ProdUnidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ProductCatalogRegistry
{
    /**
     * @var array<string, array{
     *     model: class-string<Model>,
     *     fields: array<int, string>,
     *     integer_fields: array<int, string>,
     *     relations: array<int, string>
     * }>
     */
    private const array CATALOGS = [
        'unidades' => [
            'model' => ProdUnidad::class,
            'fields' => ['abreviatura_c', 'abreviatura_v', 'cantidad_c', 'cantidad_v'],
            'integer_fields' => ['cantidad_c', 'cantidad_v'],
            'relations' => [],
        ],
        'estados' => [
            'model' => ProdEstado::class,
            'fields' => ['descripcion'],
            'integer_fields' => [],
            'relations' => [],
        ],
        'categorias' => [
            'model' => ProdCategoria::class,
            'fields' => ['nombre'],
            'integer_fields' => [],
            'relations' => [],
        ],
        'presentaciones' => [
            'model' => FamPresentacion::class,
            'fields' => ['descripcion'],
            'integer_fields' => [],
            'relations' => [],
        ],
        'administraciones' => [
            'model' => FamAdministracion::class,
            'fields' => ['descripcion'],
            'integer_fields' => [],
            'relations' => [],
        ],
        'familias' => [
            'model' => Familia::class,
            'fields' => ['presentacion', 'administracion', 'descripcion'],
            'integer_fields' => ['presentacion', 'administracion'],
            'relations' => ['presentacionDetalle', 'administracionDetalle'],
        ],
        'concentraciones' => [
            'model' => Concentracion::class,
            'fields' => ['valor'],
            'integer_fields' => [],
            'relations' => [],
        ],
        'principios-activos' => [
            'model' => PrincipalActivo::class,
            'fields' => ['nombre', 'concentracion'],
            'integer_fields' => ['concentracion'],
            'relations' => ['concentracionDetalle'],
        ],
    ];

    /** @return array<int, string> */
    public function names(): array
    {
        return array_keys(self::CATALOGS);
    }

    public function has(string $catalog): bool
    {
        return isset(self::CATALOGS[$catalog]);
    }

    public function model(string $catalog): Model
    {
        $model = $this->configuration($catalog)['model'];

        return app($model);
    }

    /** @return array<int, string> */
    public function relations(string $catalog): array
    {
        return $this->configuration($catalog)['relations'];
    }

    /** @return array<string, mixed> */
    public function rules(
        string $catalog,
        ?int $id = null,
        bool $updating = false,
        array $input = [],
    ): array {
        $rules = match ($catalog) {
            'unidades' => [
                'abreviatura_c' => ['required', 'string', 'max:10'],
                'abreviatura_v' => ['required', 'string', 'max:10'],
                'cantidad_c' => ['required', 'integer', 'min:1'],
                'cantidad_v' => [
                    'required',
                    'integer',
                    'min:1',
                    Rule::unique('prod_unidades', 'cantidad_v')
                        ->where(fn ($query) => $query
                            ->where('abreviatura_c', trim((string) ($input['abreviatura_c'] ?? '')))
                            ->where('abreviatura_v', trim((string) ($input['abreviatura_v'] ?? '')))
                            ->where('cantidad_c', (int) ($input['cantidad_c'] ?? 0)))
                        ->ignore($id),
                ],
            ],
            'estados' => [
                'descripcion' => [
                    'nullable', 'string', 'max:200',
                    Rule::unique('prod_estado', 'descripcion')->ignore($id),
                ],
            ],
            'categorias' => [
                'nombre' => [
                    'required', 'string', 'max:100',
                    Rule::unique('prod_categorias', 'nombre')->ignore($id),
                ],
            ],
            'presentaciones' => [
                'descripcion' => [
                    'required', 'string', 'max:150',
                    Rule::unique('fam_presentacion', 'descripcion')->ignore($id),
                ],
            ],
            'administraciones' => [
                'descripcion' => [
                    'required', 'string', 'max:150',
                    Rule::unique('fam_administracion', 'descripcion')->ignore($id),
                ],
            ],
            'familias' => [
                'presentacion' => ['required', 'integer', 'exists:fam_presentacion,id'],
                'administracion' => ['required', 'integer', 'exists:fam_administracion,id'],
                'descripcion' => [
                    'required',
                    'string',
                    'max:200',
                    Rule::unique('familia', 'descripcion')
                        ->where(fn ($query) => $query
                            ->where('presentacion', (int) ($input['presentacion'] ?? 0))
                            ->where('administracion', (int) ($input['administracion'] ?? 0)))
                        ->ignore($id),
                ],
            ],
            'concentraciones' => [
                'valor' => [
                    'required', 'string', 'max:100',
                    Rule::unique('concentraciones', 'valor')->ignore($id),
                ],
            ],
            'principios-activos' => [
                'nombre' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('principal_activos', 'nombre')
                        ->where(fn ($query) => $query
                            ->where('concentracion', (int) ($input['concentracion'] ?? 0)))
                        ->ignore($id),
                ],
                'concentracion' => ['required', 'integer', 'exists:concentraciones,id'],
            ],
            default => throw new InvalidArgumentException('Catálogo de productos no válido.'),
        };

        if ($updating) {
            return ['id' => ['required', 'integer', 'exists:'.$this->model($catalog)->getTable().',id'], ...$rules];
        }

        return $rules;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function sanitize(string $catalog, array $data): array
    {
        $configuration = $this->configuration($catalog);
        $sanitized = [];

        foreach ($configuration['fields'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if (in_array($field, $configuration['integer_fields'], true)) {
                $sanitized[$field] = (int) $value;

                continue;
            }

            if ($value === null) {
                $sanitized[$field] = null;

                continue;
            }

            $value = strip_tags((string) $value);
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
            $sanitized[$field] = trim($value);
        }

        return $sanitized;
    }

    /**
     * @return array{
     *     model: class-string<Model>,
     *     fields: array<int, string>,
     *     integer_fields: array<int, string>,
     *     relations: array<int, string>
     * }
     */
    private function configuration(string $catalog): array
    {
        if (! isset(self::CATALOGS[$catalog])) {
            throw new InvalidArgumentException('Catálogo de productos no válido.');
        }

        return self::CATALOGS[$catalog];
    }
}
