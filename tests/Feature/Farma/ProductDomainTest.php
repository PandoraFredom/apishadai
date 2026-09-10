<?php

namespace Tests\Feature\Farma;

use App\DTO\Farma\ProdUnidadData;
use App\Http\Requests\Farma\Productos\ProdUnidadRequest;
use App\Http\Resources\Farma\ProductActiveResource;
use App\Http\Resources\Farma\ProductResource;
use App\Interfaces\Farma\ProdCategoriaService;
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
                'familiaDetalle.presentacionDetalle',
                'familiaDetalle.administracionDetalle',
                'estadoDetalle',
                'principiosActivos.concentracionDetalle',
            ])
            ->findOrFail($product->id);

        $this->assertIsInt($product->categoria);
        $this->assertInstanceOf(ProdCategoria::class, $product->categoriaDetalle);
        $this->assertInstanceOf(Laboratorio::class, $product->laboratorioDetalle);
        $this->assertInstanceOf(ProdUnidad::class, $product->unidadDetalle);
        $this->assertInstanceOf(Familia::class, $product->familiaDetalle);
        $this->assertInstanceOf(FamPresentacion::class, $product->familiaDetalle->presentacionDetalle);
        $this->assertInstanceOf(FamAdministracion::class, $product->familiaDetalle->administracionDetalle);
        $this->assertInstanceOf(ProdEstado::class, $product->estadoDetalle);
        $this->assertInstanceOf(PrincipalActivo::class, $product->principiosActivos->first());
        $this->assertInstanceOf(Concentracion::class, $product->principiosActivos->first()->concentracionDetalle);
        $this->assertInstanceOf(Producto::class, $active->fresh()->productoDetalle);
    }

    public function test_product_repository_excludes_blobs_and_returns_safe_resource_data(): void
    {
        [$seededProduct] = $this->seedProductDomain();
        $repository = new ProductRepository(new Producto);

        $product = $repository->find($seededProduct->id);

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
        $this->assertSame('Categoría prueba', $payload['categoria']['nombre']);
        $this->assertSame('Laboratorio prueba', $payload['laboratorio']['nombre']);
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

        $active = $repository->find($seededActive->id);
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

    public function test_product_route_files_keep_urls_and_middleware(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/farma/productos/'));
        $this->assertCount(52, $routes);

        foreach ($routes as $route) {
            $this->assertStringNotContainsString('{catalogo}', $route->uri());
            $this->assertContains('auth:api', $route->gatherMiddleware());
            $this->assertContains('idempotency', $route->gatherMiddleware());
            $this->assertNotNull(app($route->getControllerClass()));
            if (in_array('PUT', $route->methods(), true)) {
                $this->assertMatchesRegularExpression('~/(?:update|actualizar)/\{id\}$~', $route->uri());
            }
        }

        $this->assertCount(10, $routes->filter(fn ($route) => in_array('PUT', $route->methods(), true)));
        foreach (['listar', 'opciones', 'buscar/1', 'imagen/1', 'activos/listar'] as $path) {
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
            ->assertJsonPath('data.presentacion_detalle.descripcion', 'Tableta')
            ->assertJsonPath('data.administracion_detalle.descripcion', 'Oral');
        $this->getJson($this->catalogBase('principios-activos').'/buscar/1')->assertOk()
            ->assertJsonPath('data.concentracion_detalle.valor', '500 mg');
        $this->assertCatalogValidationFails('POST', $this->catalogBase('principios-activos').'/crear', [
            'nombre' => 'Nuevo', 'concentracion' => 999,
        ], 'concentracion');
        $this->getJson($base.'/desconocido/listar')->assertNotFound();
    }

    public function test_catalog_write_failures_return_false(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $repository = $this->mock(ProdCategoriaService::class);
        $repository->shouldReceive('create')->once()->andThrow(new \RuntimeException('Write failed'));
        $repository->shouldReceive('update')->once()->andReturnNull();
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

    public function test_product_update_uses_route_id_and_accepts_body_without_id(): void
    {
        $this->withoutMiddleware();
        [$product] = $this->seedProductDomain();
        $base = $this->productResourceBase('ProductController');
        $input = $product->only(['categoria', 'laboratorio', 'unidad', 'familia', 'codigo', 'codigobar', 'descripcion', 'estado']);

        $this->putJson($this->updatePath($base, $product->id), $input)
            ->assertOk()->assertJsonPath('data.id', $product->id);
        $input['descripcion'] = 'Producto actualizado por URL';
        $this->putJson($this->updatePath($base, $product->id), ['id' => 999999, ...$input])
            ->assertOk()->assertJsonPath('data.descripcion', $input['descripcion']);
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
            ->assertOk()->assertJsonPath('data.id', $association->id);
        $principle = PrincipalActivo::query()->create(['nombre' => 'Nuevo activo', 'concentracion' => 1]);
        $input['pactivo'] = $principle->id;
        $this->putJson($this->updatePath($base, $association->id), ['id' => 999999, ...$input])->assertOk();
        $this->assertDatabaseHas('prod_activo', ['id' => $association->id, ...$input]);
        $this->assertCatalogValidationFails('PUT', $this->updatePath($base, '999999'), ['id' => $association->id, ...$input], 'id');
        $this->putJson($this->updatePath($base, ''), ['id' => $association->id, ...$input])->assertNotFound();
        $this->putJson($this->updatePath($base, 'invalid'), $input)->assertNotFound();
    }

    public function test_validation_returns_only_the_first_error_and_does_not_write(): void
    {
        $this->withoutMiddleware();
        $this->seedProductDomain();
        $base = $this->catalogBase('familias');
        $original = (array) DB::table('familia')->where('id', 1)->first();

        foreach (['POST' => $base.'/crear', 'PUT' => $this->updatePath($base, 1)] as $method => $path) {
            $this->json($method, $path, [])->assertStatus(400)->assertExactJson([
                'message' => 'El campo presentacion es obligatorio.',
                'code' => 400,
                'data' => null,
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
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('code', 400)
            ->assertJsonPath('data', null);
        $this->assertSame(['message', 'code', 'data'], array_keys($response->json()));
        $this->assertNotEmpty($response->json('message'));

        if ($field !== null) {
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

        $this->assertNotNull($updated);
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
