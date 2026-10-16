<?php

use Database\Seeders\TransferPermissionCatalogSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new TransferPermissionCatalogSeeder)->run();
    }

    public function down(): void
    {
        throw new LogicException('Conservar IDs y asignaciones POS; utilizar una migración nueva.');
    }
};
