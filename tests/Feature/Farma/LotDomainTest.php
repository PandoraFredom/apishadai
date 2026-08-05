<?php

namespace Tests\Feature\Farma;

use App\Http\Resources\Farma\DistributionLotResource;
use App\Http\Resources\Farma\DistributionProductResource;
use App\Http\Resources\Farma\LotProductResource;
use App\Http\Resources\Farma\LotResource;
use App\Models\Farma\Distribucion;
use App\Models\Farma\Lote;
use App\Repositories\Farma\DistributionRepository;
use App\Repositories\Farma\LotRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LotDomainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->createSchema();
        $this->seedLots();
    }

    protected function tearDown(): void
    {
        Schema::dropAllTables();
        DB::purge('sqlite');
        parent::tearDown();
    }

    public function test_it_lists_only_products_with_lots_and_aggregates_their_inventory(): void
    {
        $repository = new LotRepository(new Lote);
        $page = $repository->paginateProducts();

        $this->assertSame(2, $page->total());
        $first = LotProductResource::make($page->items()[0])->resolve(request());
        $this->assertSame(1, $first['producto_id']);
        $this->assertSame('Amoxicilina', $first['descripcion']);
        $this->assertSame('Laboratorio A', $first['laboratorio']);
        $this->assertSame('Tableta', $first['presentacion']);
        $this->assertSame('Oral', $first['administracion']);
        $this->assertTrue($first['tiene_imagen']);
        $this->assertSame(2, $first['lotes_count']);
        $this->assertSame(8, $first['cantidad_total']);
        $this->assertSame('2027-01-01', $first['proxima_expiracion']);

        $this->assertSame(1, $repository->paginateProducts(30, 'Laboratorio B')->total());
        $this->assertSame(0, $repository->paginateProducts(30, 'Producto sin lotes')->total());
    }

    public function test_product_lot_detail_includes_dates_quantity_and_purchase_invoice(): void
    {
        $lots = LotResource::collection((new LotRepository(new Lote))->productLots(1))->resolve(request());

        $this->assertCount(2, $lots);
        $this->assertSame('LOTE-A', $lots[0]['lote']);
        $this->assertSame('2026-01-01', $lots[0]['fecha_elaboracion']);
        $this->assertSame('2027-01-01', $lots[0]['fecha_expiracion']);
        $this->assertSame(3, $lots[0]['cantidad']);
        $this->assertSame(50.0, $lots[0]['costo']);
        $this->assertTrue($lots[0]['isv']);
        $this->assertSame('FAC-001', $lots[0]['factura']['nro']);
        $this->assertSame(10, $lots[0]['factura']['id']);
    }

    public function test_distribution_lists_the_same_products_and_tracks_configured_lots(): void
    {
        $repository = new DistributionRepository(new Lote, new Distribucion);
        $page = $repository->paginateProducts();
        $first = DistributionProductResource::make($page->items()[0])->resolve(request());

        $this->assertSame(1, $first['producto_id']);
        $this->assertSame(2, $first['lotes_count']);
        $this->assertSame(1, $first['lotes_configurados']);
        $this->assertSame(8, $first['cantidad_total']);

        $lots = DistributionLotResource::collection($repository->productLots(1))->resolve(request());
        $this->assertTrue($lots[0]['configurado']);
        $this->assertSame(50.0, $lots[0]['costo']);
        $this->assertTrue($lots[0]['isv']);
        $this->assertSame(125.5, $lots[0]['distribucion']['precio']);
        $this->assertFalse($lots[1]['configurado']);
    }

    public function test_distribution_is_created_or_updated_once_per_lot(): void
    {
        $repository = new DistributionRepository(new Lote, new Distribucion);
        $payload = ['precio' => 80.25, 'isv' => true, 'dto1' => 5, 'dto2' => 0, 'dto3' => 0, 'dto4' => 0];

        $created = $repository->saveForLot(2, $payload);
        $updated = $repository->saveForLot(2, [...$payload, 'precio' => 90]);

        $this->assertNotNull($created);
        $this->assertSame($created->id, $updated?->id);
        $this->assertSame('90.00', $updated?->precio);
        $this->assertFalse($updated?->isv);
        $this->assertSame(2, DB::table('distribucion')->count());
    }

    private function createSchema(): void
    {
        Schema::create('laboratorios', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });
        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('laboratorio');
            $table->unsignedBigInteger('familia');
            $table->string('codigo');
            $table->string('descripcion');
            $table->binary('imagen')->nullable();
            $table->timestamps();
        });
        Schema::create('fam_presentacion', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
            $table->timestamps();
        });
        Schema::create('fam_administracion', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion');
            $table->timestamps();
        });
        Schema::create('familia', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('presentacion');
            $table->unsignedBigInteger('administracion');
            $table->string('descripcion');
            $table->timestamps();
        });
        Schema::create('compras', function (Blueprint $table): void {
            $table->id();
            $table->string('nro');
            $table->timestamps();
        });
        Schema::create('lotes', function (Blueprint $table): void {
            $table->id();
            $table->integer('compra')->nullable();
            $table->integer('producto');
            $table->string('lote', 100);
            $table->date('fecha_elab');
            $table->date('fecha_exp');
            $table->integer('cantidad');
            $table->decimal('costo', 10, 2)->nullable();
            $table->boolean('isv')->nullable();
            $table->timestamps();
        });
        Schema::create('distribucion', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lote')->unique();
            $table->decimal('precio', 10, 2)->nullable();
            $table->boolean('isv');
            $table->decimal('dto1', 4, 2);
            $table->decimal('dto2', 4, 2);
            $table->decimal('dto3', 4, 2);
            $table->decimal('dto4', 4, 2);
            $table->timestamps();
        });
    }

    private function seedLots(): void
    {
        DB::table('laboratorios')->insert([
            ['id' => 1, 'nombre' => 'Laboratorio A'],
            ['id' => 2, 'nombre' => 'Laboratorio B'],
        ]);
        DB::table('fam_presentacion')->insert(['id' => 1, 'descripcion' => 'Tableta']);
        DB::table('fam_administracion')->insert(['id' => 1, 'descripcion' => 'Oral']);
        DB::table('familia')->insert(['id' => 1, 'presentacion' => 1, 'administracion' => 1, 'descripcion' => 'Medicamento']);
        DB::table('productos')->insert([
            ['id' => 1, 'laboratorio' => 1, 'familia' => 1, 'codigo' => 'P-001', 'descripcion' => 'Amoxicilina', 'imagen' => 'image'],
            ['id' => 2, 'laboratorio' => 2, 'familia' => 1, 'codigo' => 'P-002', 'descripcion' => 'Ibuprofeno', 'imagen' => null],
            ['id' => 3, 'laboratorio' => 1, 'familia' => 1, 'codigo' => 'P-003', 'descripcion' => 'Producto sin lotes', 'imagen' => null],
        ]);
        DB::table('compras')->insert([
            ['id' => 10, 'nro' => 'FAC-001'],
            ['id' => 11, 'nro' => 'FAC-002'],
        ]);
        DB::table('lotes')->insert([
            ['id' => 1, 'compra' => 10, 'producto' => 1, 'lote' => 'LOTE-A', 'fecha_elab' => '2026-01-01', 'fecha_exp' => '2027-01-01', 'cantidad' => 3, 'costo' => 50, 'isv' => true],
            ['id' => 2, 'compra' => 11, 'producto' => 1, 'lote' => 'LOTE-B', 'fecha_elab' => '2026-02-01', 'fecha_exp' => '2028-01-01', 'cantidad' => 5, 'costo' => 60, 'isv' => false],
            ['id' => 3, 'compra' => 11, 'producto' => 2, 'lote' => 'LOTE-C', 'fecha_elab' => '2026-03-01', 'fecha_exp' => '2029-01-01', 'cantidad' => 7, 'costo' => 20, 'isv' => false],
        ]);
        DB::table('distribucion')->insert([
            'lote' => 1,
            'precio' => 125.50,
            'isv' => 1,
            'dto1' => 5,
            'dto2' => 0,
            'dto3' => 0,
            'dto4' => 0,
        ]);
    }
}
