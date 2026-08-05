<?php

namespace Tests\Feature\Farma;

use App\Http\Resources\Farma\ProductActiveResource;
use App\Http\Resources\Farma\ProductResource;
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
use App\Services\Farma\ProductCatalogRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
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

        $registry = app(ProductCatalogRegistry::class);
        $input = $registry->sanitize('unidades', [
            'abreviatura_c' => ' <b>CAJ</b> ',
            'abreviatura_v' => ' UND ',
            'cantidad_c' => '1',
            'cantidad_v' => '20',
        ]);
        $validator = Validator::make(
            $input,
            $registry->rules('unidades', input: $input),
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
