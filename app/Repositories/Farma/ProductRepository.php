<?php

namespace App\Repositories\Farma;

use App\Models\Farma\Familia;
use App\Models\Farma\PrincipalActivo;
use App\Models\Farma\ProdCategoria;
use App\Models\Farma\ProdEstado;
use App\Models\Farma\Producto;
use App\Models\Farma\ProdUnidad;
use App\Models\Laboratorio;
use App\Models\Proveedores;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    /** @var array<int, string> */
    private const RELATIONS = [
        'categoriaDetalle',
        'laboratorioDetalle:id,nombre,telefono,direccion',
        'unidadDetalle',
        'familiaDetalle.presentacionDetalle',
        'familiaDetalle.administracionDetalle',
        'estadoDetalle',
        'principiosActivos.concentracionDetalle',
    ];

    public function __construct(private readonly Producto $model) {}

    public function paginate(
        int $perPage = 30,
        ?string $search = null,
        ?int $laboratory = null,
        ?int $provider = null,
    ): LengthAwarePaginator {
        $query = $this->baseQuery();
        $search = trim((string) $search);

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
            ->paginate(max(1, min($perPage, 100)));
    }

    public function find(int $id): ?Producto
    {
        /** @var Producto|null */
        return $this->baseQuery()->find($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, array $principiosActivos = []): Producto
    {
        return DB::transaction(function () use ($data, $principiosActivos): Producto {
            /** @var Producto $product */
            $product = $this->model->newQuery()->create($data);
            $product->principiosActivos()->sync($this->uniqueIds($principiosActivos));

            return $this->find((int) $product->getKey()) ?? $product;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>|null  $principiosActivos
     */
    public function update(int $id, array $data, ?array $principiosActivos = null): ?Producto
    {
        return DB::transaction(function () use ($id, $data, $principiosActivos): ?Producto {
            /** @var Producto|null $product */
            $product = $this->model->newQuery()->lockForUpdate()->find($id);

            if ($product === null) {
                return null;
            }

            $product->update($data);

            if ($principiosActivos !== null) {
                $product->principiosActivos()->sync($this->uniqueIds($principiosActivos));
            }

            return $this->find($id);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $product = $this->model->newQuery()->lockForUpdate()->find($id);

            return $product !== null && (bool) $product->delete();
        });
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
            ->with(['presentacionDetalle:id,descripcion', 'administracionDetalle:id,descripcion'])
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
                'presentacion' => (string) ($family->presentacionDetalle?->descripcion ?? ''),
                'administracion' => (string) ($family->administracionDetalle?->descripcion ?? ''),
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

    private function baseQuery(): Builder
    {
        return $this->model->newQuery()
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
            ->selectRaw('imagen IS NOT NULL AS tiene_imagen')
            ->with(self::RELATIONS);
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
