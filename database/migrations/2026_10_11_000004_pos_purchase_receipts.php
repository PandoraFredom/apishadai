<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        foreach (['lotes', 'pos_compras_centrales'] as $name) {
            if (! Schema::hasTable($name)) continue;
            if (DB::getDriverName() === 'mysql') {
                $engine = DB::table('information_schema.tables')->where('table_schema', DB::getDatabaseName())->where('table_name', $name)->value('engine');
                if (strcasecmp((string) $engine, 'InnoDB') !== 0) {
                    if (strcasecmp((string) $engine, 'MyISAM') !== 0) throw new LogicException('Motor no reconocido para recepción de compras.');
                    $count = DB::table($name)->count(); DB::statement('ALTER TABLE `'.$name.'` ENGINE=InnoDB');
                    if (DB::table($name)->count() !== $count) throw new LogicException('Cambió el conteo al convertir lotes.');
                }
            }
        }
        if (! Schema::hasTable('lotes')) throw new LogicException('Falta el catálogo de lotes.');
        if (DB::getDriverName() === 'mysql') {
            $column = collect(Schema::getColumns('lotes'))->firstWhere('name', 'compra');
            $type = $column['type'];
            if (! preg_match('/\A(?:tinyint|smallint|mediumint|int|bigint)(?:\([0-9]+\))?(?: unsigned)?\z/i', $type)) throw new LogicException('Tipo de compra de lote no reconocido.');
            DB::statement('ALTER TABLE lotes MODIFY compra '.$type.' NULL, MODIFY cantidad DECIMAL(19,4) NULL, MODIFY costo DECIMAL(19,4) NULL');
        } else {
            Schema::table('lotes', function (Blueprint $table): void {
                $table->unsignedBigInteger('compra')->nullable()->change();
                $table->decimal('cantidad', 19, 4)->nullable()->change(); $table->decimal('costo', 19, 4)->nullable()->change();
            });
        }
        if (! Schema::hasTable('pos_compra_lote_mutex')) {
            Schema::create('pos_compra_lote_mutex', function (Blueprint $table): void { $table->engine('InnoDB'); $table->unsignedInteger('id')->primary(); });
            DB::table('pos_compra_lote_mutex')->insert(['id' => 1]);
        }
        if (! Schema::hasTable('pos_compras_centrales')) Schema::create('pos_compras_centrales', function (Blueprint $table): void {
            $table->engine('InnoDB'); $table->uuid('request_id')->primary(); $table->uuid('purchase_uuid')->index();
            $table->uuid('compra_aceptada_uuid')->nullable()->unique();
            $table->unsignedBigInteger('device_id'); $table->unsignedBigInteger('stock_id')->index(); $table->unsignedBigInteger('usuario_pos_id');
            $table->unsignedBigInteger('supplier_id'); $table->string('documento_aceptado',100)->nullable();
            $table->unique(['stock_id','supplier_id','documento_aceptado'],'pos_compra_factura_aceptada');
            $table->char('huella', 64); $table->longText('datos'); $table->longText('respuesta'); $table->timestamp('created_at');
        });
    }
    public function down(): void { throw new LogicException('La recepción de compras y sus lotes no admiten reversión automática.'); }
};
