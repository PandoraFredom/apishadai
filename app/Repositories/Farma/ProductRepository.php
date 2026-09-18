<?php

namespace App\Repositories\Farma;

use App\Interfaces\Farma\ProductService;
use App\Models\Farma\Familia;
use App\Models\Farma\PrincipalActivo;
use App\Models\Farma\ProdCategoria;
use App\Models\Farma\ProdEstado;
use App\Models\Farma\Producto;
use App\Models\Farma\ProdUnidad;
use App\Models\Laboratorio;
use App\Models\Proveedores;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends Repository implements ProductService
{
    /** @var array<int, string> */
    private const RELATIONS = [
        'categoriaDetalle',
        'laboratorioDetalle:id,nombre,telefono,direccion',
        'unidadDetalle',
        'Familia.Presentacion',
        'Familia.Administracion',
        'estadoDetalle',
        'principiosActivos.concentracionDetalle',
    ];

    public function __construct(Producto $model)
    {
        parent::__construct($model);
        $this->perPage = 30;
        $this->orderBy = ['id', 'DESC'];
        $this->defaultRelations = self::RELATIONS;
    }

    public function paginate(
        ?int $perPage = null,
        ?string $search = null,
        ?int $laboratory = null,
        ?int $provider = null,
    ): LengthAwarePaginator {
        if ($perPage !== null) {
            $this->setPerPage($perPage);
        }
        $search = trim((string) $search);

        if ($search === '' && ! $laboratory && ! $provider) {
            return parent::paginate();
        }

        return $this->executeQuery(function () use ($search, $laboratory, $provider) {
            $query = $this->buildBaseQuery();

            if ($search !== '') {
                $query->where(static function (Builder $query) use ($search): void {
                    $query
                        ->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%")
                        ->orWhere('codigobar', 'like', "%{$search}%");
                });
            }

            if ($laboratory !== null && $laboratory > 0) {
                $query->where('laboratorio', $laboratory);
            }

            if ($provider !== null && $provider > 0) {
                $query->whereExists(static function (QueryBuilder $query) use ($provider): void {
                    $query
                        ->selectRaw('1')
                        ->from('compra_detalle')
                        ->join('compras', 'compras.id', '=', 'compra_detalle.compra')
                        ->whereColumn('compra_detalle.producto', 'productos.id')
                        ->where('compras.proveedor', $provider);
                });
            }

            return $query
                ->orderByDesc('id')
                ->paginate($this->perPage);
        }, 'paginate', $this->getEmptyPaginator());
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, array $principiosActivos = []): bool
    {
        return $this->executeTransaction(function () use ($data, $principiosActivos): bool {
            if (! parent::create($data)) {
                return false;
            }

            $product = $this->model->newQuery()->where('codigo', $this->sanitizeData($data)['codigo'])->firstOrFail();
            $product->principiosActivos()->sync($this->uniqueIds($principiosActivos));

            return true;
        }, 'createWithPrinciples');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>|null  $principiosActivos
     */
    public function update(int $id, array $data, ?array $principiosActivos = null): bool
    {
        return $this->executeTransaction(function () use ($id, $data, $principiosActivos): bool {
            if (! parent::update($id, $data)) {
                return false;
            }

            if ($principiosActivos !== null) {
                $product = $this->model->newQuery()->findOrFail($id);
                $product->principiosActivos()->sync($this->uniqueIds($principiosActivos));
            }

            return true;
        }, 'updateWithPrinciples', ['id' => $id]);
    }

    public function getImage(int $id): ?string
    {
        $image = $this->model->newQuery()->whereKey($id)->value('imagen');

        return is_string($image) && $image !== '' ? $image : null;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function options(): array
    {
        $categories = ProdCategoria::query()->orderBy('nombre')->get(['id', 'nombre']);
        $laboratories = Laboratorio::query()->orderBy('nombre')->get(['id', 'nombre']);
        $providers = Proveedores::query()->orderBy('nombre')->get(['id', 'nombre']);
        $units = ProdUnidad::query()->orderBy('abreviatura_c')->get([
            'id', 'abreviatura_c', 'cantidad_c', 'abreviatura_v', 'cantidad_v',
        ]);
        $families = Familia::query()
            ->with(['Presentacion:id,descripcion', 'Administracion:id,descripcion'])
            ->orderBy('descripcion')
            ->get(['id', 'presentacion', 'administracion', 'descripcion']);
        $states = ProdEstado::query()->orderBy('descripcion')->get(['id', 'descripcion']);
        $principles = PrincipalActivo::query()
            ->with('concentracionDetalle:id,valor')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'concentracion']);

        return [
            'categorias' => $this->labeled($categories, 'nombre'),
            'laboratorios' => $this->labeled($laboratories, 'nombre'),
            'proveedores' => $this->labeled($providers, 'nombre'),
            'unidades' => $units->map(static fn (ProdUnidad $unit): array => [
                'id' => (int) $unit->id,
                'label' => sprintf(
                    '%s x %d → %s x %d',
                    $unit->abreviatura_c,
                    $unit->cantidad_c,
                    $unit->abreviatura_v,
                    $unit->cantidad_v,
                ),
                'unidad_compra' => (string) $unit->abreviatura_c,
                'cantidad_compra' => (int) $unit->cantidad_c,
                'unidad_venta' => (string) $unit->abreviatura_v,
                'cantidad_venta' => (int) $unit->cantidad_v,
            ])->all(),
            'familias' => $families->map(static fn (Familia $family): array => [
                'id' => (int) $family->id,
                'label' => (string) $family->descripcion,
                'nombre' => (string) $family->descripcion,
                'presentacion' => (string) ($family->Presentacion?->descripcion ?? ''),
                'administracion' => (string) ($family->Administracion?->descripcion ?? ''),
            ])->all(),
            'estados' => $this->labeled($states, 'descripcion'),
            'principios_activos' => $principles->map(static function (PrincipalActivo $principle): array {
                $concentration = trim((string) ($principle->concentracionDetalle?->valor ?? ''));

                return [
                    'id' => (int) $principle->id,
                    'label' => trim($principle->nombre.' '.$concentration),
                    'nombre' => (string) $principle->nombre,
                    'concentracion' => $concentration,
                ];
            })->all(),
        ];
    }

    protected function buildBaseQuery(): Builder
    {
        return parent::buildBaseQuery()
            ->select([
                'id',
                'categoria',
                'laboratorio',
                'unidad',
                'familia',
                'codigo',
                'codigobar',
                'descripcion',
                'estado',
                'created_at',
                'updated_at',
            ])
            ->selectRaw('imagen IS NOT NULL AS tiene_imagen');
    }

    protected function sanitizeData(array $data): array
    {
        $image = $data['imagen'] ?? null;
        unset($data['imagen']);
        $sanitized = parent::sanitizeData(array_intersect_key($data, array_flip($this->model->getFillable())));

        // La imagen ya fue validada por el controlador; no truncar ni alterar sus bytes.
        if ($image !== null) {
            $sanitized['imagen'] = $image;
        }

        return $sanitized;
    }

    protected function filterEmptyData(array $data): array
    {
        $filtered = parent::filterEmptyData($data);
        if (array_key_exists('codigobar', $data) && $data['codigobar'] === null) {
            $filtered['codigobar'] = null;
        }

        return $filtered;
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return array<int, array{id: int, label: string}>
     */
    private function labeled(Collection $records, string $field): array
    {
        return $records->map(static fn ($record): array => [
            'id' => (int) $record->getKey(),
            'label' => (string) $record->getAttribute($field),
        ])->all();
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function uniqueIds(array $ids): array
    {
        return array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            $ids,
        )));
    }
}
