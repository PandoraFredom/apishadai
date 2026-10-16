<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Client writes and the HQ publication journal must commit together.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $engine = DB::table('information_schema.tables')->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'clientepos')->value('ENGINE');
            if (strtolower((string) $engine) !== 'innodb') {
                $count = DB::table('clientepos')->count();
                DB::statement('ALTER TABLE clientepos ENGINE=InnoDB');
                if (DB::table('clientepos')->count() !== $count) {
                    throw new LogicException('El cambio de motor debe conservar todos los clientes.');
                }
            }
        }
        Schema::table('clientepos', function (Blueprint $table): void {
            $table->string('tipo_documento', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
        });
        // No classification or birth date is inferred for previous customers.
    }

    public function down(): void
    {
        throw new RuntimeException('No se eliminan datos de identificación de Clientes POS automáticamente.');
    }
};
