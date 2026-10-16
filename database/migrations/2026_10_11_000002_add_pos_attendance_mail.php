<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $queueTable = config('queue.connections.database.table');
        if (! is_string($queueTable) || ! preg_match('/\A[a-zA-Z0-9_]+\z/', $queueTable)
            || DB::connection(config('queue.connections.database.connection'))->getName() !== DB::connection()->getName()
            || ! Schema::hasTable($queueTable) || ! Schema::hasTable('pos_asistencia_solicitudes')) {
            throw new LogicException('Falta la cola database en la conexión central o el diario de asistencia.');
        }
        if (DB::getDriverName() === 'mysql') {
            $engine = DB::table('information_schema.tables')->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $queueTable)->value('engine');
            if (strcasecmp((string) $engine, 'InnoDB') !== 0) {
                if (strcasecmp((string) $engine, 'MyISAM') !== 0) {
                    throw new LogicException('Motor de cola database no reconocido.');
                }
                DB::statement('ALTER TABLE `'.$queueTable.'` ENGINE=InnoDB');
            }
        }
        foreach (['correo_encolado_at', 'correo_enviado_at'] as $column) {
            if (! Schema::hasColumn('pos_asistencia_solicitudes', $column)) {
                Schema::table('pos_asistencia_solicitudes', fn (Blueprint $table) => $table->timestamp($column)->nullable());
            }
        }
    }

    public function down(): void
    {
        throw new LogicException('El diario de avisos de asistencia no admite reversión automática.');
    }
};
