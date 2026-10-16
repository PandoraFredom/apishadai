<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        // Existing catalogs were created after the outbox's original engine migration.
        // A transactional outbox must share an InnoDB catalog; never truncate or rewrite rows.
        foreach (['catalogospos', 'pos_descuento_control', 'pos_descuento_solicitudes', 'sync_changes', 'hq_outbox'] as $name) {
            if (! Schema::hasTable($name)) {
                throw new LogicException('Falta una tabla del despliegue transaccional POS.');
            }
            $engine = DB::table('information_schema.tables')->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $name)->value('ENGINE');
            if (strtolower((string) $engine) === 'innodb') {
                continue;
            }
            $count = DB::table($name)->count();
            DB::statement('ALTER TABLE `'.$name.'` ENGINE=InnoDB');
            if (DB::table($name)->count() !== $count) {
                throw new LogicException('El cambio de motor debe conservar todos los registros.');
            }
        }
    }

    public function down(): void
    {
        throw new LogicException('Conservar los motores transaccionales y las solicitudes POS.');
    }
};
