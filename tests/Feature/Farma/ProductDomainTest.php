<?php

namespace Tests\Feature\Farma;

use App\DTO\Farma\ProdUnidadData;
use App\Http\Requests\Farma\Productos\ProdUnidadRequest;
use App\Http\Resources\Farma\ProductActiveResource;
use App\Http\Resources\Farma\ProductResource;
use App\Interfaces\Farma\FamAdministracionService;
use App\Interfaces\Farma\FamPresentacionService;
use App\Interfaces\Farma\ProdCategoriaService;
use App\Interfaces\Farma\ProdEstadoService;
use App\Interfaces\Farma\ProductService;
use App\Interfaces\RepositoryInterface;
use App\Models\Farma\Concentracion;
use App\Models\Farma\FamAdministracion;
use App\Models\Farma\Familia;
use App\Models\Farma\FamPresentacion;
use App\Models\Farma\PrincipalActivo;
use App\Models\Farma\ProdActivo;
use App\Models\Farma\ProdCategoria;
use App\Models\Farma\ProdEstado;
use App\Models\Farma\Producto;
use App\Models\Farma\ProdUnidad;
use App\Models\Laboratorio;
use App\Repositories\Farma\ProductActiveRepository;
use App\Repositories\Farma\ProductRepository;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductDomainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropAllTables();
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_models_resolve_relationships_without_colliding_with_foreign_keys(): void
    {
        [$product, $active] = $this->seedProductDomain();

        $product = Producto::query()
            ->with([
                'categoriaDetalle',
                'laboratorioDetalle',
                'unidadDetalle',
                'Familia.Presentacion',
                'Familia.Administracion',
                'estadoDetalle',
                'principiosActivos.concentracionDetalle',
            ])
            ->findOrFail($product->id);

        $this->assertIsInt($product->categoria);
        $this->assertInstanceOf(ProdCategoria::class, $product->categoriaDetalle);
        $this->assertInstanceOf(Laboratorio::class, $product->laboratorioDetalle);
        $this->assertInstanceOf(ProdUnidad::class, $product->unidadDetalle);
        $this->assertInstanceOf(Familia::class, $product->Familia);
        $this->assertInstanceOf(FamPresentacion::class, $product->Familia->Presentacion);
        $this->assertInstanceOf(FamAdministracion::class, $product->Familia->Administracion);
        $this->assertInstanceOf(ProdEstado::class, $product->estadoDetalle);
        $this->assertInstanceOf(PrincipalActivo::class, $product->principiosActivos->first());
        $this->assertInstanceOf(Concentracion::class, $product->principiosActivos->first()->concentracionDetalle);
        $this->assertInstanceOf(Producto::class, $active->fresh()->productoDetalle);
    }

    public function test_product_repository_excludes_blobs_and_returns_safe_resource_data(): void
    {
        [$seededProduct] = $this->seedProductDomain();
        $repository = new ProductRepository(new Producto);

        $product = $repository->findById($seededProduct->id);

        $this->assertNotNull($product);
        $this->assertTrue((bool) $product->tiene_imagen);
        $this->assertFalse(array_key_exists('imagen', $product->getAttributes()));
        $this->assertFalse(array_key_exists('imagen', $product->laboratorioDetalle->getAttributes()));

        $payload = json_decode(
            json_encode(ProductResource::make($product)->resolve(request()), JSON_THROW_ON_ERROR),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame($product->categoria, $payload['categoria_id']);
        $this->assertSame(['id' => $product->categoria, 'nombre' => 'Categoría prueba'], $payload['categoria']);
        $this->assertSame(['id' => $product->laboratorio, 'direccion' => 'Tegucigalpa'], $payload['laboratorio']);
        $this->assertSame(['id' => $product->unidad, 'cantidad_v' => 20], $payload['unidad']);
        $this->assertSame([
            'id' => $product->familia,
            'presentacion' => ['id' => $product->Familia->Presentacion->id, 'descripcion' => 'Tableta'],
            'administracion' => ['id' => $product->Familia->Administracion->id, 'descripcion' => 'Oral'],
        ], $payload['familia']);
        $this->assertSame(['id' => $product->estado, 'descripcion' => 'Activo'], $payload['estado']);
        $this->assertArrayNotHasKey('imagen', $payload);
        $this->assertArrayNotHasKey('imagen', $payload['laboratorio']);
        $this->assertArrayNotHasKey('pivot', $payload['principios_activos'][0]);
    }

    public function test_catalog_rules_reject_composite_duplicates_after_sanitizing_input(): void
    {
        ProdUnidad::query()->create([
            'abreviatura_c' => 'CAJ',
            'abreviatura_v' => 'UND',
            'cantidad_c' => 1,
            'cantidad_v' => 20,
        ]);

        $input = ProdUnidadData::sanitize([
            'abreviatura_c' => ' <b>CAJ</b> ',
            'abreviatura_v' => ' UND ',
            'cantidad_c' => '1',
            'cantidad_v' => '20',
        ]);
        $validator = Validator::make(
            $input,
            ProdUnidadRequest::create('/api/farma/productos/catalogos/unidades/crear', 'POST', $input)->rules(),
        );

        $this->assertSame('CAJ', $input['abreviatura_c']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cantidad_v', $validator->errors()->toArray());
    }

    public function test_active_repository_returns_compact_product_data(): void
    {
        [$product, $seededActive] = $this->seedProductDomain();
        $repository = new ProductActiveRepository(new ProdActivo);

        $active = $repository->findById($seededActive->id);
        $payload = json_decode(
            json_encode(ProductActiveResource::make($active)->resolve(request()), JSON_THROW_ON_ERROR),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame($product->id, $payload['producto_id']);
        $this->assertSame(
            ['id', 'codigo', 'codigobar', 'descripcion'],
            array_keys($payload['producto']),
        );
        $this->assertSame('500 mg', $payload['principio_activo']['concentracion_detalle']['valor']);
    }

    #[DataProvider('catalogEndpoints')]
    public function test_each_catalog_exposes_its_own_crud(string $catalog, string $table, array $input): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $base = $this->catalogBase($catalog);

        $this->postJson($base.'/crear', $input)
            ->assertCreated()
            ->assertJsonPath('data', true)
            ->assertJsonPath('message', 'Registro creado correctamente.');
        $id = DB::table($table)->where($input)->value('id');
        $this->assertIsInt($id);
        $this->assertDatabaseHas($table, ['id' => $id, ...$input]);

        $this->getJson($base.'/listar')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $id);
        $this->getJson($base.'/buscar/'.$id)
            ->assertOk()
            ->assertJsonPath('data.id', $id);

        // Updating the same row must not fail its own unique constraint.
        $this->putJson($this->updatePath($base, $id), $input)
            ->assertOk()
            ->assertJsonPath('data', true);
        $this->assertCatalogValidationFails('POST', $base.'/crear', $input);
        $this->assertCatalogValidationFails('PUT', $this->updatePath($base, '999999'), ['id' => $id, ...$input], 'id');
        $this->putJson($this->updatePath($base, 'not-a-number'), $input)->assertNotFound();
        $this->putJson($this->updatePath($base, ''), ['id' => $id, ...$input])->assertNotFound();

        foreach ($input as $field => $value) {
            if (is_string($value)) {
                $input[$field] = 'Nuevo';
                break;
            }
        }
        $original = (array) DB::table($table)->where('id', 1)->first();
        $this->putJson($this->updatePath($base, $id), ['id' => 1, ...$input])
            ->assertOk()->assertJsonPath('data', true);
        $this->assertDatabaseHas($table, ['id' => $id, ...$input]);
        $this->assertSame($original, (array) DB::table($table)->where('id', 1)->first());

        $this->deleteJson($base.'/eliminar/'.$id)
            ->assertOk()
            ->assertJsonPath('data', true);
        $this->assertDatabaseMissing($table, ['id' => $id]);
        $this->getJson($base.'/buscar/'.$id)->assertNotFound();
        $this->deleteJson($base.'/eliminar/'.$id)->assertNotFound()->assertJsonPath('data', false);
    }

    public static function catalogEndpoints(): array
    {
        return [
            'unidades' => ['unidades', 'prod_unidades', [
                'abreviatura_c' => 'PAQ', 'abreviatura_v' => 'UND', 'cantidad_c' => 1, 'cantidad_v' => 10,
            ]],
            'estados' => ['estados', 'prod_estado', ['descripcion' => 'Inactivo']],
            'categorias' => ['categorias', 'prod_categorias', ['nombre' => 'Vitaminas']],
            'presentaciones' => ['presentaciones', 'fam_presentacion', ['descripcion' => 'Cápsula']],
            'administraciones' => ['administraciones', 'fam_administracion', ['descripcion' => 'Tópica']],
            'familias' => ['familias', 'familia', [
                'presentacion' => 1, 'administracion' => 1, 'descripcion' => 'Antibiótico',
            ]],
            'concentraciones' => ['concentraciones', 'concentraciones', ['valor' => '250 mg']],
            'principios-activos' => ['principios-activos', 'principal_activos', [
                'nombre' => 'Ibuprofeno', 'concentracion' => 1,
            ]],
        ];
    }

    #[DataProvider('familyCombinations')]
    public function test_family_uniqueness_checks_the_entire_combination(
        string $method,
        bool $differentDescription,
        bool $differentAdministration,
        bool $differentPresentation,
        bool $nestedRelations,
    ): void {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $administration = FamAdministracion::query()->create(['descripcion' => 'Tópica']);
        $presentation = FamPresentacion::query()->create(['descripcion' => 'Crema']);
        $base = $this->catalogBase('familias');
        $input = [
            'descripcion' => $differentDescription ? 'Antibiótico' : 'Analgésico',
            'administracion' => $differentAdministration ? $administration->id : 1,
            'presentacion' => $differentPresentation ? $presentation->id : 1,
        ];
        $path = $base.'/crear';
        $target = null;

        if ($method === 'PUT') {
            $target = Familia::query()->create([
                'descripcion' => 'Familia por actualizar',
                'administracion' => 1,
                'presentacion' => 1,
            ]);
            $path = $this->updatePath($base, $target->id);
        }

        $count = Familia::query()->count();
        // Exercise the same sanitization used by real create and update requests.
        $payload = [
            ...$input,
            'descripcion' => ' <b>'.$input['descripcion'].'</b> ',
        ];
        if ($nestedRelations) {
            $payload['administracion'] = ['id' => $input['administracion']];
            $payload['presentacion'] = ['id' => $input['presentacion']];
        }
        $response = $this->json($method, $path, $payload);

        if (! $differentDescription && ! $differentAdministration && ! $differentPresentation) {
            $response->assertStatus(422)->assertJsonPath('message',
                'Ya existe una familia con la misma descripcion, tipo de administracion y tipo de presentacion.',
            );
            $this->assertDatabaseCount('familia', $count);
            if ($target !== null) {
                $this->assertSame('Familia por actualizar', $target->fresh()->descripcion);
            }

            return;
        }

        $response->assertStatus($method === 'POST' ? 201 : 200)->assertJsonPath('data', true);
        $this->assertDatabaseHas('familia', $target === null ? $input : ['id' => $target->id, ...$input]);
        $this->assertDatabaseCount('familia', $count + ($method === 'POST' ? 1 : 0));
    }

    public function test_family_update_accepts_its_own_combination_with_nested_and_scalar_ids(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();

        $this->putJson($this->updatePath($this->catalogBase('familias'), 1), [
            'descripcion' => 'Analgésico',
            'presentacion' => ['id' => '1', 'descripcion' => 'Tableta'],
            'administracion' => '1',
        ])->assertOk()->assertJsonPath('data', true);
        $this->assertDatabaseCount('familia', 1);
        $this->assertDatabaseHas('familia', [
            'id' => 1, 'descripcion' => 'Analgésico', 'presentacion' => 1, 'administracion' => 1,
        ]);
    }

    public function test_family_rejects_missing_or_invalid_related_ids_without_writing(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $base = $this->catalogBase('familias');
        $original = (array) DB::table('familia')->where('id', 1)->first();

        foreach (['POST' => $base.'/crear', 'PUT' => $this->updatePath($base, 1)] as $method => $path) {
            foreach (['presentacion', 'administracion'] as $field) {
                foreach ([[], ['descripcion' => 'Sin ID'], ['id' => []], ['id' => true], ['id' => '1abc'], ['id' => 1.5], ['id' => 999999]] as $invalid) {
                    $this->assertCatalogValidationFails($method, $path, [
                        'descripcion' => 'Nueva familia',
                        'presentacion' => 1,
                        'administracion' => 1,
                        $field => $invalid,
                    ], $field);
                }
            }
        }

        $this->assertDatabaseCount('familia', 1);
        $this->assertSame($original, (array) DB::table('familia')->where('id', 1)->first());
    }

    public static function familyCombinations(): array
    {
        $cases = [];
        foreach (['POST', 'PUT'] as $method) {
            foreach ([false, true] as $description) {
                foreach ([false, true] as $administration) {
                    foreach ([false, true] as $presentation) {
                        $name = sprintf('%s description=%d administration=%d presentation=%d',
                            $method, $description, $administration, $presentation);
                        $cases[$name.' scalar'] = [$method, $description, $administration, $presentation, false];
                        $cases[$name.' nested'] = [$method, $description, $administration, $presentation, true];
                    }
                }
            }
        }

        return $cases;
    }

    public function test_product_catalog_lists_return_paginated_records_and_family_relations(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $base = $this->productResourceBase('ProductController');

        $this->getJson($base.'/laboratorios')->assertOk()
            ->assertJsonPath('data.0.nombre', 'Laboratorio prueba')
            ->assertJsonPath('data.0.tiene_imagen', true)
            ->assertJsonMissingPath('data.0.imagen')
            ->assertJsonPath('meta.total', 1);
        $this->getJson($base.'/familias')->assertOk()
            ->assertJsonPath('data.0.descripcion', 'Analgésico')
            ->assertJsonPath('data.0.presentacion.descripcion', 'Tableta')
            ->assertJsonPath('data.0.administracion.descripcion', 'Oral')
            ->assertJsonPath('meta.total', 1);
        $this->getJson($base.'/unidades')->assertOk()
            ->assertJsonPath('data.0.abreviatura_c', 'CAJ')
            ->assertJsonPath('data.0.abreviatura_v', 'UND')
            ->assertJsonPath('meta.total', 1);
        $this->getJson($base.'/estados')->assertOk()
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.descripcion', 'Activo')
            ->assertJsonPath('meta.total', 1);
        $this->getJson($base.'/categorias')->assertOk()
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.0.nombre', 'Categoría prueba')
            ->assertJsonPath('meta.total', 1);
    }

    #[DataProvider('productCatalogFilters')]
    public function test_product_catalog_filters_search_partial_values(string $catalog, array $items, int $matches): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        Laboratorio::query()->create(['nombre' => 'Otro laboratorio']);
        Familia::query()->create(['descripcion' => 'Vitaminas', 'presentacion' => 1, 'administracion' => 1]);
        ProdUnidad::query()->create([
            'abreviatura_c' => 'PAQ', 'abreviatura_v' => 'CAJ', 'cantidad_c' => 1, 'cantidad_v' => 5,
        ]);
        $base = $this->productResourceBase('ProductController');

        $response = $this->getJson($base.'/filter_'.$catalog.'?'.http_build_query([
            'filterItems' => $items,
        ]))->assertOk()->assertJsonPath('meta.total', $matches)->assertJsonCount($matches, 'data');
        if ($catalog === 'laboratorios') {
            $response->assertJsonMissingPath('data.0.imagen')->assertJsonPath('data.0.tiene_imagen', true);
        }
        if ($catalog === 'familias' && $matches > 0) {
            $response->assertJsonPath('data.0.presentacion.descripcion', 'Tableta');
        }
    }

    public static function productCatalogFilters(): array
    {
        return [
            'laboratorio nombre' => ['laboratorios', [['key' => 'nombre', 'value' => 'prueb']], 1],
            'familia descripcion' => ['familias', [['key' => 'descripcion', 'value' => 'lgés']], 1],
            'unidad compra' => ['unidades', [['key' => 'abreviatura_c', 'value' => 'PA']], 1],
            'unidad venta' => ['unidades', [['key' => 'abreviatura_v', 'value' => 'UN']], 1],
            'unidad compra o venta' => ['unidades', [
                ['key' => 'abreviatura_v', 'value' => 'CA'],
                ['key' => 'abreviatura_c', 'value' => 'CA'],
            ], 2],
            'sin resultados' => ['familias', [['key' => 'descripcion', 'value' => 'Inexistente']], 0],
        ];
    }

    public function test_product_catalog_filters_reject_unconfigured_fields_and_client_operators(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $base = $this->productResourceBase('ProductController');

        foreach (['laboratorios' => 'nombre', 'familias' => 'descripcion', 'unidades' => 'abreviatura_c'] as $catalog => $key) {
            foreach ([
                [['key' => 'id', 'value' => '1']],
                [['key' => $key, 'value' => 'test', 'operator' => '=']],
                [['key' => $key, 'value' => 'test', 'logicalOperator' => 'OR']],
            ] as $items) {
                $this->getJson($base.'/filter_'.$catalog.'?'.http_build_query(['filterItems' => $items]))
                    ->assertStatus(422)->assertJsonPath('code', 422)
                    ->assertJsonStructure(['message', 'code', 'data' => ['errors'], 'meta'])
                    ->assertJsonPath('meta', null);
            }
        }
    }

    public function test_product_catalog_lists_and_filters_paginate(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        for ($index = 0; $index < 31; $index++) {
            ProdEstado::query()->create(['descripcion' => 'Estado extra '.$index]);
            ProdCategoria::query()->create(['nombre' => 'Categoría extra '.$index]);
            Laboratorio::query()->create(['nombre' => 'Laboratorio extra '.$index]);
            Familia::query()->create(['descripcion' => 'Familia extra '.$index, 'presentacion' => 1, 'administracion' => 1]);
            ProdUnidad::query()->create([
                'abreviatura_c' => 'CAJ', 'abreviatura_v' => 'UND', 'cantidad_c' => 2, 'cantidad_v' => $index + 1,
            ]);
        }
        $base = $this->productResourceBase('ProductController');
        foreach (['laboratorios' => ['nombre', 'Laboratorio'], 'familias' => ['descripcion', 'extra'], 'unidades' => ['abreviatura_c', 'CAJ']] as $catalog => [$key, $value]) {
            $this->getJson($base.'/'.$catalog.'?page=2')->assertOk()
                ->assertJsonPath('meta.total', 32)->assertJsonPath('meta.current_page', 2)->assertJsonCount(2, 'data');
            $this->getJson($base.'/filter_'.$catalog.'?'.http_build_query([
                'page' => 2, 'filterItems' => [['key' => $key, 'value' => $value]],
            ]))->assertOk()->assertJsonPath('meta.current_page', 2)
                ->assertJsonCount($catalog === 'familias' ? 1 : 2, 'data');
        }
        foreach (['estados', 'categorias'] as $catalog) {
            $this->getJson($base.'/'.$catalog.'?page=2')->assertOk()
                ->assertJsonPath('meta.total', 32)->assertJsonPath('meta.current_page', 2)->assertJsonCount(2, 'data');
        }
    }

    public function test_product_route_files_keep_urls_and_middleware(): void
    {
        $this->withoutMiddleware();
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/farma/productos/'));
        $this->assertCount(61, $routes);

        foreach ($routes as $route) {
            $this->assertStringNotContainsString('{catalogo}', $route->uri());
            $this->assertContains('auth:api', $route->gatherMiddleware());
            $this->assertContains('idempotency', $route->gatherMiddleware());
            $this->assertNotNull(app($route->getControllerClass()));
            if (in_array('PUT', $route->methods(), true)) {
                $this->assertMatchesRegularExpression('~/(?:update|actualizar)/\{id\}$~', $route->uri());
            }
            if (in_array($route->getActionMethod(), ['show', 'update', 'destroy'], true)) {
                $this->assertSame('[0-9]+', $route->wheres['id'] ?? null);
                $this->json($route->methods()[0], '/'.str_replace('{id}', 'invalid', $route->uri()))
                    ->assertNotFound();
            }
        }

        $this->assertCount(10, $routes->filter(fn ($route) => in_array('PUT', $route->methods(), true)));
        foreach (['listar', 'laboratorios', 'familias', 'unidades', 'estados', 'categorias', 'filter_laboratorios', 'filter_familias', 'filter_unidades', 'buscar/1', 'imagen/1', 'activos/listar'] as $path) {
            $controller = str_starts_with($path, 'activos/') ? 'ProductActiveController' : 'ProductController';
            $base = $this->productResourceBase($controller);
            $path = str_replace('activos/', '', $path);
            $route = app('router')->getRoutes()->match(
                Request::create($base.'/'.$path),
            );
            $this->assertStringEndsWith($controller, $route->getControllerClass());
        }
    }

    public function test_catalog_endpoints_preserve_sanitization_and_loaded_relations(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $base = $this->catalogBase('unidades');

        $this->assertCatalogValidationFails('POST', $base.'/crear', [
            'abreviatura_c' => ' <b>CAJ</b> ', 'abreviatura_v' => ' UND ',
            'cantidad_c' => '1', 'cantidad_v' => '20',
        ], 'cantidad_v');

        $this->postJson($this->catalogBase('categorias').'/crear', ['nombre' => ' <b>Vitaminas</b> '])
            ->assertCreated()->assertJsonPath('data', true);
        $this->assertDatabaseHas('prod_categorias', ['nombre' => 'Vitaminas']);
        $this->postJson($this->catalogBase('estados').'/crear', ['descripcion' => null])
            ->assertCreated()->assertJsonPath('data', true);
        $this->assertDatabaseHas('prod_estado', ['descripcion' => null]);

        $this->getJson($this->catalogBase('familias').'/buscar/1')->assertOk()
            ->assertJsonPath('data.presentacion', ['id' => 1, 'descripcion' => 'Tableta'])
            ->assertJsonPath('data.administracion', ['id' => 1, 'descripcion' => 'Oral']);
        $this->getJson($this->catalogBase('principios-activos').'/buscar/1')->assertOk()
            ->assertJsonPath('data.concentracion_detalle.valor', '500 mg');
        $this->assertCatalogValidationFails('POST', $this->catalogBase('principios-activos').'/crear', [
            'nombre' => 'Nuevo', 'concentracion' => 999,
        ], 'concentracion');
        $this->getJson($base.'/desconocido/listar')->assertNotFound();
    }

    public function test_family_lists_delegate_to_catalog_services_and_return_only_id_and_description(): void
    {
        $this->withoutMiddleware();
        $this->mock(FamAdministracionService::class)
            ->shouldReceive('getAll')->once()->andReturn(new Collection([
                (new FamAdministracion)->forceFill(['id' => 7, 'descripcion' => 'Oral', 'created_at' => now(), 'extra' => 'hidden']),
            ]));
        $this->mock(FamPresentacionService::class)
            ->shouldReceive('getAll')->once()->andReturn(new Collection([
                (new FamPresentacion)->forceFill(['id' => 8, 'descripcion' => 'Tableta', 'created_at' => now(), 'extra' => 'hidden']),
            ]));
        $base = $this->catalogBase('familias');

        $this->getJson($base.'/administraciones')->assertOk()->assertExactJson([
            'message' => 'ok', 'code' => 200, 'data' => [['id' => 7, 'descripcion' => 'Oral']],
        ]);
        $this->getJson($base.'/presentaciones')->assertOk()->assertExactJson([
            'message' => 'ok', 'code' => 200, 'data' => [['id' => 8, 'descripcion' => 'Tableta']],
        ]);
    }

    public function test_family_lists_return_empty_arrays_and_all_catalog_options_without_pagination(): void
    {
        $this->withoutMiddleware();
        $base = $this->catalogBase('familias');

        foreach (['administraciones' => 'fam_administracion', 'presentaciones' => 'fam_presentacion'] as $path => $table) {
            $this->getJson($base.'/'.$path)->assertOk()->assertExactJson([
                'message' => 'ok', 'code' => 200, 'data' => [],
            ]);

            $records = [];
            for ($id = 1; $id <= 31; $id++) {
                $records[] = ['id' => $id, 'descripcion' => 'Opción '.$id];
            }
            DB::table($table)->insert($records);
            $this->getJson($base.'/'.$path)->assertOk()->assertExactJson([
                'message' => 'ok', 'code' => 200, 'data' => array_reverse($records),
            ]);
        }
    }

    public function test_catalog_write_failures_return_false(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $repository = $this->mock(ProdCategoriaService::class);
        $repository->shouldReceive('create')->once()->andThrow(new \RuntimeException('Write failed'));
        $repository->shouldReceive('update')->once()->andReturnFalse();
        $repository->shouldReceive('delete')->once()->andThrow(new QueryException(
            'sqlite', 'delete from prod_categorias where id = ?', [1], new \PDOException('Referenced record', 23000),
        ));
        $base = $this->catalogBase('categorias');

        $this->postJson($base.'/crear', ['nombre' => 'Nueva'])
            ->assertStatus(500)->assertJsonPath('data', false);
        $this->putJson($this->updatePath($base, '1'), ['nombre' => 'Nueva'])
            ->assertNotFound()->assertJsonPath('data', false);
        $this->deleteJson($base.'/eliminar/1')
            ->assertStatus(409)->assertJsonPath('data', false);
    }

    public function test_catalog_controller_respects_false_from_the_base_repository(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $repository = $this->mock(ProdCategoriaService::class);
        $repository->shouldReceive('create')->once()->andReturnFalse();
        $repository->shouldReceive('update')->once()->andReturnFalse();
        $repository->shouldReceive('delete')->once()->andReturnFalse();
        $base = $this->catalogBase('categorias');

        $this->postJson($base.'/crear', ['nombre' => 'Nueva'])
            ->assertStatus(422)->assertJsonPath('data', false);
        $this->putJson($this->updatePath($base, 1), ['nombre' => 'Nueva'])
            ->assertNotFound()->assertJsonPath('data', false);
        $this->deleteJson($base.'/eliminar/1')->assertNotFound()->assertJsonPath('data', false);
    }

    public function test_product_repositories_support_inherited_reads_and_boolean_writes(): void
    {
        $this->seedProductDomain();
        foreach (['ProdUnidad', 'ProdEstado', 'ProdCategoria', 'FamPresentacion', 'FamAdministracion',
            'Familia', 'Concentracion', 'PrincipalActivo', 'ProductActive', 'Product'] as $name) {
            $repository = app('App\\Interfaces\\Farma\\'.$name.'Service');
            $this->assertInstanceOf(RepositoryInterface::class, $repository);
            $this->assertInstanceOf(Repository::class, $repository);
            $this->assertCount(1, $repository->getAll());
            $this->assertSame(1, $repository->findById(1)->id);
            $this->assertSame(1, $repository->whereFirst(['id' => 1])->id);
            $this->assertSame(1, $repository->setPerPage(1)->paginate()->perPage());
            $this->assertFalse($repository->update(999999, ['id' => 999999]));
            $this->assertFalse($repository->delete(999999));
        }

        $categories = app(ProdCategoriaService::class);
        $this->assertFalse($categories->create(['nombre' => 'Categoría prueba']));
        $this->assertTrue($categories->create(['nombre' => 'Nueva categoría']));
        $id = $categories->whereFirst(['nombre' => 'Nueva categoría'])->id;
        $this->assertTrue($categories->update($id, ['nombre' => 'Actualizada']));
        $this->assertTrue($categories->delete($id));
    }

    public function test_inherited_writes_preserve_nullable_fields_and_product_image(): void
    {
        [$product] = $this->seedProductDomain();
        $states = app(ProdEstadoService::class);
        $this->assertTrue($states->update(1, ['descripcion' => null]));
        $this->assertDatabaseHas('prod_estado', ['id' => 1, 'descripcion' => null]);

        $repository = app(ProductService::class);
        $image = base64_encode(str_repeat('image-data', 300));
        $this->assertTrue($repository->update($product->id, ['imagen' => $image, 'codigobar' => null]));
        $this->assertSame($image, $repository->getImage($product->id));
        $this->assertDatabaseHas('productos', ['id' => $product->id, 'codigobar' => null]);
        $this->assertArrayNotHasKey('imagen', $repository->findById($product->id)->getAttributes());

        $data = $product->only(['categoria', 'laboratorio', 'unidad', 'familia', 'codigo', 'descripcion', 'estado']);
        $data['codigo'] = 'NEW-PRODUCT';
        $data['imagen'] = $image;
        $this->assertTrue($repository->create($data, [1, 1]));
        $created = $repository->whereFirst(['codigo' => 'NEW-PRODUCT']);
        $this->assertSame([1], $created->principiosActivos->pluck('id')->all());
        $this->assertSame($image, $repository->getImage($created->id));
    }

    public function test_product_writes_roll_back_when_active_principle_sync_fails(): void
    {
        [$product] = $this->seedProductDomain();
        $repository = app(ProductService::class);
        $principle = PrincipalActivo::query()->create(['nombre' => 'Otro activo', 'concentracion' => 1]);
        DB::unprepared("CREATE TRIGGER reject_active_insert BEFORE INSERT ON prod_activo BEGIN SELECT RAISE(ABORT, 'Test sync failure'); END");

        $data = $product->only(['categoria', 'laboratorio', 'unidad', 'familia', 'codigo', 'codigobar', 'descripcion', 'estado']);
        $data['codigo'] = 'ROLLBACK-CREATE';
        $data['codigobar'] = null;
        $this->assertFalse($repository->create($data, [$principle->id]));
        $this->assertDatabaseMissing('productos', ['codigo' => 'ROLLBACK-CREATE']);

        $this->assertFalse($repository->update($product->id, ['descripcion' => 'No debe persistir'], [$principle->id]));
        $this->assertSame($product->descripcion, $product->fresh()->descripcion);
        $this->assertSame([1], $product->fresh()->principiosActivos->pluck('id')->all());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_product_update_uses_route_id_and_accepts_body_without_id(): void
    {
        $this->withoutMiddleware();
        [$product] = $this->seedProductDomain();
        $base = $this->productResourceBase('ProductController');
        $input = $product->only(['categoria', 'laboratorio', 'unidad', 'familia', 'codigo', 'codigobar', 'descripcion', 'estado']);

        $this->putJson($this->updatePath($base, $product->id), $input)
            ->assertOk()->assertJsonPath('data', true);
        $input['descripcion'] = 'Producto actualizado por URL';
        $this->putJson($this->updatePath($base, $product->id), ['id' => 999999, ...$input])
            ->assertOk()->assertJsonPath('data', true);
        $this->assertDatabaseHas('productos', ['id' => $product->id, 'descripcion' => $input['descripcion']]);
        $this->assertCatalogValidationFails('PUT', $this->updatePath($base, '999999'), ['id' => $product->id, ...$input], 'id');
        $this->putJson($this->updatePath($base, ''), ['id' => $product->id, ...$input])->assertNotFound();
        $this->putJson($this->updatePath($base, 'invalid'), $input)->assertNotFound();
    }

    public function test_active_update_uses_route_id_and_accepts_body_without_id(): void
    {
        $this->withoutMiddleware();
        [$product, $association] = $this->seedProductDomain();
        $base = $this->productResourceBase('ProductActiveController');
        $input = ['producto' => $product->id, 'pactivo' => $association->pactivo];

        $this->putJson($this->updatePath($base, $association->id), $input)
            ->assertOk()->assertJsonPath('data', true);
        $principle = PrincipalActivo::query()->create(['nombre' => 'Nuevo activo', 'concentracion' => 1]);
        $input['pactivo'] = $principle->id;
        $this->putJson($this->updatePath($base, $association->id), ['id' => 999999, ...$input])->assertOk();
        $this->assertDatabaseHas('prod_activo', ['id' => $association->id, ...$input]);
        $this->assertCatalogValidationFails('PUT', $this->updatePath($base, '999999'), ['id' => $association->id, ...$input], 'id');
        $this->putJson($this->updatePath($base, ''), ['id' => $association->id, ...$input])->assertNotFound();
        $this->putJson($this->updatePath($base, 'invalid'), $input)->assertNotFound();
    }

    public function test_validation_returns_the_first_message_and_all_errors_without_writing(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $base = $this->catalogBase('familias');
        $original = (array) DB::table('familia')->where('id', 1)->first();

        foreach (['POST' => $base.'/crear', 'PUT' => $this->updatePath($base, 1)] as $method => $path) {
            $this->json($method, $path, [])->assertStatus(422)->assertExactJson([
                'message' => 'El campo presentacion es obligatorio.',
                'code' => 422,
                'data' => ['errors' => [
                    'presentacion' => ['El campo presentacion es obligatorio.'],
                    'administracion' => ['El campo administracion es obligatorio.'],
                    'descripcion' => ['El campo descripcion es obligatorio.'],
                ]],
                'meta' => null,
            ]);
        }

        $this->assertSame(1, DB::table('familia')->count());
        $this->assertSame($original, (array) DB::table('familia')->where('id', 1)->first());
    }

    private function catalogBase(string $catalog): string
    {
        $controller = match ($catalog) {
            'unidades' => 'ProdUnidadController',
            'estados' => 'ProdEstadoController',
            'categorias' => 'ProdCategoriaController',
            'presentaciones' => 'FamPresentacionController',
            'administraciones' => 'FamAdministracionController',
            'familias' => 'FamiliaController',
            'concentraciones' => 'ConcentracionController',
            'principios-activos' => 'PrincipalActivoController',
        };

        return $this->productResourceBase($controller);
    }

    private function productResourceBase(string $controller): string
    {
        $action = 'App\\Http\\Controllers\\API\\Farma\\Productos\\'.$controller.'@index';
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => $route->getActionName() === $action);
        $this->assertNotNull($route, 'Missing list route for '.$controller);

        return '/'.substr($route->uri(), 0, -strlen('/listar'));
    }

    private function updatePath(string $base, int|string $id): string
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => str_starts_with('/'.$route->uri(), $base.'/')
                && $route->getActionMethod() === 'update');
        $this->assertNotNull($route, 'Missing update route for '.$base);

        return rtrim('/'.str_replace('{id}', (string) $id, $route->uri()), '/');
    }

    private function assertCatalogValidationFails(string $method, string $uri, array $input, ?string $field = null): void
    {
        $response = $this->json($method, $uri, $input)
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('code', 422)
            ->assertJsonPath('meta', null);
        $this->assertSame(['message', 'code', 'data', 'meta'], array_keys($response->json()));
        $this->assertNotEmpty($response->json('message'));
        $this->assertNotEmpty($response->json('data.errors'));

        if ($field !== null) {
            $response->assertJsonValidationErrors($field, 'data.errors');
            $this->assertStringContainsString(str_replace('_', ' ', $field), $response->json('message'));
        }
    }

    public function test_product_repository_exposes_options_and_syncs_active_principles(): void
    {
        [$product] = $this->seedProductDomain();
        $repository = new ProductRepository(new Producto);
        $options = $repository->options();

        $this->assertSame('Categoría prueba', $options['categorias'][0]['label']);
        $this->assertSame('Laboratorio prueba', $options['laboratorios'][0]['label']);
        $this->assertSame('Proveedor prueba', $options['proveedores'][0]['label']);
        $this->assertSame('CAJ x 1 → UND x 20', $options['unidades'][0]['label']);
        $this->assertSame('CAJ', $options['unidades'][0]['unidad_compra']);
        $this->assertSame('UND', $options['unidades'][0]['unidad_venta']);
        $this->assertSame('Analgésico', $options['familias'][0]['nombre']);
        $this->assertSame('Tableta', $options['familias'][0]['presentacion']);
        $this->assertSame('Oral', $options['familias'][0]['administracion']);
        $this->assertSame('Paracetamol 500 mg', $options['principios_activos'][0]['label']);
        $this->assertSame('Paracetamol', $options['principios_activos'][0]['nombre']);
        $this->assertSame('500 mg', $options['principios_activos'][0]['concentracion']);

        $secondPrinciple = PrincipalActivo::query()->create([
            'nombre' => 'Cafeína',
            'concentracion' => Concentracion::query()->value('id'),
        ]);
        $updated = $repository->update(
            $product->id,
            ['descripcion' => 'Producto actualizado'],
            [$secondPrinciple->id],
        );

        $this->assertTrue($updated);
        $updated = $repository->findById($product->id);
        $this->assertSame('Producto actualizado', $updated->descripcion);
        $this->assertSame(
            [$secondPrinciple->id],
            $updated->principiosActivos->pluck('id')->all(),
        );
    }

    public function test_product_repository_applies_advanced_search_filters(): void
    {
        [$product] = $this->seedProductDomain();
        $repository = new ProductRepository(new Producto);

        $matches = $repository->paginate(
            perPage: 10,
            search: '750000000001',
            laboratory: (int) $product->laboratorio,
            provider: 1,
        );

        $this->assertSame(1, $matches->total());
        $this->assertSame($product->id, $matches->items()[0]->id);

        $otherProvider = DB::table('proveedores')->insertGetId([
            'nombre' => 'Proveedor sin el producto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $repository->paginate(provider: $otherProvider)->total());
    }

    /** @return array{Producto, ProdActivo} */
    private function seedProductDomain(): array
    {
        $category = ProdCategoria::query()->create(['nombre' => 'Categoría prueba']);
        $laboratory = Laboratorio::query()->create([
            'nombre' => 'Laboratorio prueba',
            'telefono' => '2200-0000',
            'direccion' => 'Tegucigalpa',
            'imagen' => 'imagen-del-laboratorio',
        ]);
        $unit = ProdUnidad::query()->create([
            'abreviatura_c' => 'CAJ',
            'abreviatura_v' => 'UND',
            'cantidad_c' => 1,
            'cantidad_v' => 20,
        ]);
        $presentation = FamPresentacion::query()->create(['descripcion' => 'Tableta']);
        $administration = FamAdministracion::query()->create(['descripcion' => 'Oral']);
        $family = Familia::query()->create([
            'presentacion' => $presentation->id,
            'administracion' => $administration->id,
            'descripcion' => 'Analgésico',
        ]);
        $state = ProdEstado::query()->create(['descripcion' => 'Activo']);
        $concentration = Concentracion::query()->create(['valor' => '500 mg']);
        $principle = PrincipalActivo::query()->create([
            'nombre' => 'Paracetamol',
            'concentracion' => $concentration->id,
        ]);

        $product = Producto::query()->create([
            'categoria' => $category->id,
            'laboratorio' => $laboratory->id,
            'unidad' => $unit->id,
            'familia' => $family->id,
            'codigo' => 'PRD-001',
            'codigobar' => '750000000001',
            'descripcion' => 'Producto de prueba',
            'imagen' => 'imagen-del-producto',
            'estado' => $state->id,
        ]);
        $active = ProdActivo::query()->create([
            'producto' => $product->id,
            'pactivo' => $principle->id,
        ]);

        $provider = DB::table('proveedores')->insertGetId([
            'nombre' => 'Proveedor prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $purchase = DB::table('compras')->insertGetId(['proveedor' => $provider]);
        DB::table('compra_detalle')->insert([
            'compra' => $purchase,
            'producto' => $product->id,
        ]);

        return [$product, $active];
    }

    private function createSchema(): void
    {
        Schema::create('proveedores', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 180)->unique();
            $table->timestamps();
        });
        Schema::create('laboratorios', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 180)->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion')->nullable();
            $table->binary('imagen')->nullable();
            $table->timestamps();
        });
        Schema::create('prod_unidades', function (Blueprint $table): void {
            $table->id();
            $table->string('abreviatura_c', 10);
            $table->string('abreviatura_v', 10);
            $table->integer('cantidad_c');
            $table->integer('cantidad_v');
            $table->timestamps();
            $table->unique(
                ['abreviatura_c', 'abreviatura_v', 'cantidad_c', 'cantidad_v'],
                'uq_prod_unidades_conversion',
            );
        });
        Schema::create('prod_estado', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion', 200)->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('prod_categorias', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->timestamps();
        });
        Schema::create('fam_presentacion', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion', 150)->unique();
            $table->timestamps();
        });
        Schema::create('fam_administracion', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion', 150)->unique();
            $table->timestamps();
        });
        Schema::create('familia', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('presentacion');
            $table->unsignedBigInteger('administracion');
            $table->string('descripcion', 200);
            $table->timestamps();
            $table->unique(
                ['presentacion', 'administracion', 'descripcion'],
                'uq_familia_composicion',
            );
        });
        Schema::create('concentraciones', function (Blueprint $table): void {
            $table->id();
            $table->string('valor', 100)->unique();
            $table->timestamps();
        });
        Schema::create('principal_activos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->unsignedBigInteger('concentracion');
            $table->timestamps();
            $table->unique(['nombre', 'concentracion'], 'uq_principal_activo');
        });
        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('categoria');
            $table->unsignedBigInteger('laboratorio');
            $table->unsignedBigInteger('unidad');
            $table->unsignedBigInteger('familia');
            $table->string('codigo', 100)->unique();
            $table->string('codigobar', 150)->nullable()->unique();
            $table->string('descripcion');
            $table->binary('imagen')->nullable();
            $table->unsignedBigInteger('estado');
            $table->timestamps();
        });
        Schema::create('prod_activo', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto');
            $table->unsignedBigInteger('pactivo');
            $table->timestamps();
            $table->unique(['producto', 'pactivo'], 'uq_prod_activo');
        });
        Schema::create('compras', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('proveedor');
        });
        Schema::create('compra_detalle', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('compra');
            $table->unsignedBigInteger('producto');
        });
    }
}
