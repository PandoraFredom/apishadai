<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('proveedores', 'imagen')) {
            return;
        }

        match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => DB::statement(
                'ALTER TABLE proveedores MODIFY imagen MEDIUMBLOB NULL',
            ),
            'sqlsrv' => DB::statement(
                'ALTER TABLE proveedores ALTER COLUMN imagen VARBINARY(MAX) NULL',
            ),
            default => null,
        };
    }

    public function down(): void
    {
        // No se reduce a BLOB porque truncaría imágenes válidas mayores de 64 KB.
    }
};
